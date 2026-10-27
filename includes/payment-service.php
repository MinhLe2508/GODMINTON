<?php
/**
 * includes/payment-service.php
 * Thanh toán tiền cọc thật qua SePay (chuyển khoản ngân hàng + webhook biến động số dư).
 *
 * Nguyên tắc:
 *   - Số tiền cọc luôn do server tính (api/bookings.php), client chỉ hiển thị.
 *   - CHỈ webhook SePay (hoặc giả lập dev có bật cờ) mới được chuyển booking sang 'confirmed'.
 *   - Mọi webhook được lưu nguyên văn vào sepay_transactions, chống xử lý trùng bằng UNIQUE(sepay_id).
 *
 * Trạng thái:
 *   payments.status : pending → paid | expired | cancelled | late_paid (cần hoàn tiền) | refunded
 *   bookings.status : pending → confirmed | cancelled
 *   time_slots      : holding (giữ đến payments.expires_at) → booked | available
 */
require_once __DIR__ . '/../config/load-env.php';

/** Cấu hình SePay đọc từ .env (cache theo request). */
function payment_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    $prefix = strtoupper(preg_replace('/[^A-Za-z]/', '', (string)(getenv('SEPAY_PAYMENT_PREFIX') ?: 'GDM')));
    $env = strtolower((string)(getenv('APP_ENV') ?: 'production'));
    $config = [
        'bank_code'      => trim((string)(getenv('SEPAY_BANK_CODE') ?: 'MBBank')),
        'bank_name'      => trim((string)(getenv('SEPAY_BANK_NAME') ?: 'MB Bank (Ngân hàng Quân Đội)')),
        'account_number' => preg_replace('/\s+/', '', (string)(getenv('SEPAY_ACCOUNT_NUMBER') ?: '')),
        'account_name'   => trim((string)(getenv('SEPAY_ACCOUNT_NAME') ?: '')),
        'webhook_key'    => (string)(getenv('SEPAY_WEBHOOK_API_KEY') ?: ''),
        'prefix'         => $prefix !== '' ? $prefix : 'GDM',
        'timeout'        => max(5, min(60, (int)(getenv('PAYMENT_TIMEOUT_MINUTES') ?: 15))),
        // Giả lập chỉ dùng khi phát triển: phải bật rõ ràng VÀ không phải production.
        'simulate'       => getenv('SEPAY_ALLOW_SIMULATE') === '1' && $env !== 'production',
    ];
    return $config;
}

/** Mã thanh toán in vào nội dung chuyển khoản đặt sân: GDM000123 (chỉ chữ + số, ngân hàng không cắt mất). */
function payment_code_for_booking(int $bookingId): string
{
    return payment_config()['prefix'] . str_pad((string)$bookingId, 6, '0', STR_PAD_LEFT);
}

/** Mã thanh toán SePay cho gói hội viên: GDM800001 (dải 800000+ để SePay nhận diện cùng prefix GDM 6 chữ số). */
function payment_code_for_membership(int $membershipId): string
{
    return payment_config()['prefix'] . str_pad((string)(800000 + $membershipId), 6, '0', STR_PAD_LEFT);
}

/** Ảnh VietQR động của SePay, đã điền sẵn số tiền + nội dung. */
function payment_qr_url(string $paymentCode, int $amount): string
{
    $c = payment_config();
    return 'https://qr.sepay.vn/img?' . http_build_query([
        'acc'      => $c['account_number'],
        'bank'     => $c['bank_code'],
        'amount'   => $amount,
        'des'      => $paymentCode,
        'template' => 'compact',
    ]);
}

/** Thông tin ngân hàng công khai cho frontend (không chứa khoá bí mật). */
function payment_public_bank(): array
{
    $c = payment_config();
    return [
        'code'           => $c['bank_code'],
        'name'           => $c['bank_name'],
        'account_number' => $c['account_number'],
        'account_name'   => $c['account_name'],
    ];
}

/**
 * Tự tạo bảng payments / sepay_transactions + cột bookings.payment_status + cột payments.membership_id nếu DB chưa có.
 * DDL tự commit nên KHÔNG được gọi bên trong transaction.
 */
function payment_ensure_schema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    if ($pdo->inTransaction()) {
        throw new RuntimeException('payment_ensure_schema() không được gọi trong transaction.');
    }
    $hasTable = static function (string $table) use ($pdo): bool {
        $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $s->execute([$table]);
        return (bool)$s->fetchColumn();
    };
    $hasCol = static function (string $table, string $col) use ($pdo): bool {
        $s = $pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $s->execute([$table, $col]);
        return (bool)$s->fetchColumn();
    };
    if (!$hasCol('bookings', 'payment_status')) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN payment_status ENUM('unpaid','deposit_paid','refunded') NOT NULL DEFAULT 'unpaid' AFTER status");
    }
    if (!$hasTable('payments')) {
        $pdo->exec("CREATE TABLE payments (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id    INT UNSIGNED DEFAULT NULL,
            membership_id INT UNSIGNED DEFAULT NULL,
            payment_code  VARCHAR(20) NOT NULL,
            amount        DECIMAL(12,0) NOT NULL,
            paid_amount   DECIMAL(12,0) NOT NULL DEFAULT 0,
            status        ENUM('pending','paid','expired','cancelled','late_paid','refunded') NOT NULL DEFAULT 'pending',
            expires_at    DATETIME NOT NULL,
            paid_at       DATETIME DEFAULT NULL,
            created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_payment_code (payment_code),
            UNIQUE KEY uq_payment_booking (booking_id),
            UNIQUE KEY uq_payment_membership (membership_id),
            KEY idx_pay_status_exp (status, expires_at),
            CONSTRAINT fk_pay_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } else {
        // Nâng cấp bảng payments hiện có để hỗ trợ thanh toán Gói Hội Viên (membership_id)
        try {
            $nullableStmt = $pdo->prepare("SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='booking_id'");
            $nullableStmt->execute();
            if (strtoupper((string)$nullableStmt->fetchColumn()) === 'NO') {
                $pdo->exec("ALTER TABLE payments MODIFY COLUMN booking_id INT UNSIGNED DEFAULT NULL");
            }
            if (!$hasCol('payments', 'membership_id')) {
                $pdo->exec("ALTER TABLE payments ADD COLUMN membership_id INT UNSIGNED DEFAULT NULL AFTER booking_id, ADD UNIQUE KEY uq_payment_membership (membership_id)");
            }
        } catch (Throwable $e) {
            error_log('[payment-schema-upgrade] ' . $e->getMessage());
        }
    }
    if (!$hasTable('sepay_transactions')) {
        $pdo->exec("CREATE TABLE sepay_transactions (
            id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
            sepay_id         BIGINT UNSIGNED NOT NULL,
            payment_id       INT UNSIGNED DEFAULT NULL,
            gateway          VARCHAR(100) DEFAULT NULL,
            account_number   VARCHAR(50) DEFAULT NULL,
            transfer_type    VARCHAR(5) DEFAULT NULL,
            transfer_amount  DECIMAL(14,0) NOT NULL DEFAULT 0,
            content          VARCHAR(500) DEFAULT NULL,
            code             VARCHAR(100) DEFAULT NULL,
            reference_code   VARCHAR(100) DEFAULT NULL,
            transaction_date DATETIME DEFAULT NULL,
            raw_payload      TEXT NOT NULL,
            result           VARCHAR(30) NOT NULL DEFAULT 'received',
            note             VARCHAR(255) DEFAULT NULL,
            created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_sepay_id (sepay_id),
            KEY idx_st_payment (payment_id),
            KEY idx_st_result (result),
            CONSTRAINT fk_st_payment FOREIGN KEY (payment_id) REFERENCES payments (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    $ready = true;
}

/** Có bảng payments chưa (dùng ở những chỗ chỉ đọc / dọn dẹp, không muốn chạy DDL). */
function payment_tables_ready(PDO $pdo): bool
{
    static $ready = null;
    if ($ready === true) {
        return true;
    }
    try {
        $s = $pdo->query("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments'");
        $ready = (bool)$s->fetchColumn();
    } catch (Throwable $e) {
        $ready = false;
    }
    return $ready;
}

/** Dữ liệu thanh toán trả cho frontend. */
function payment_public_data(array $payment): array
{
    $amount = (int)$payment['amount'];
    $expiresIn = isset($payment['expires_in_seconds']) ? max(0, (int)$payment['expires_in_seconds']) : null;
    return [
        'payment_code'       => $payment['payment_code'],
        'amount'             => $amount,
        'paid_amount'        => (int)($payment['paid_amount'] ?? 0),
        'status'             => $payment['status'],
        'expires_at'         => (new DateTime($payment['expires_at'], new DateTimeZone('Asia/Ho_Chi_Minh')))->format('c'),
        'expires_in_seconds' => $expiresIn,
        'qr_url'             => payment_qr_url($payment['payment_code'], $amount),
        'bank'               => payment_public_bank(),
        'simulate_available' => payment_config()['simulate'],
    ];
}

/** Đọc payment theo booking kèm số giây còn lại (MySQL NOW() là nguồn thời gian duy nhất). */
function payment_fetch_by_booking(PDO $pdo, int $bookingId): ?array
{
    $s = $pdo->prepare('SELECT p.*, TIMESTAMPDIFF(SECOND, NOW(), p.expires_at) AS expires_in_seconds FROM payments p WHERE p.booking_id=?');
    $s->execute([$bookingId]);
    return $s->fetch() ?: null;
}

/** Đọc payment theo gói hội viên (membership_id) kèm số giây còn lại. */
function payment_fetch_by_membership(PDO $pdo, int $membershipId): ?array
{
    $s = $pdo->prepare('SELECT p.*, TIMESTAMPDIFF(SECOND, NOW(), p.expires_at) AS expires_in_seconds FROM payments p WHERE p.membership_id=?');
    $s->execute([$membershipId]);
    return $s->fetch() ?: null;
}

/** Tóm tắt booking để hiển thị (dùng khi trả lại đơn chờ thanh toán đã tồn tại). */
function payment_booking_summary(PDO $pdo, int $bookingId): array
{
    $s = $pdo->prepare('SELECT b.*, c.name AS court_name, c.address AS court_address FROM bookings b JOIN courts c ON c.id=b.court_id WHERE b.id=?');
    $s->execute([$bookingId]);
    $b = $s->fetch();
    if (!$b) {
        return [];
    }
    $s = $pdo->prepare('SELECT s.sub_court_name, s.slot_date, s.start_time, s.end_time FROM booking_slots bs JOIN time_slots s ON s.id=bs.slot_id WHERE bs.booking_id=? ORDER BY s.slot_date, s.start_time');
    $s->execute([$bookingId]);
    $slots = $s->fetchAll();
    $first = $slots[0] ?? null;
    $last = $slots ? end($slots) : null;
    $start = $first ? substr($first['start_time'], 0, 5) : '';
    $end = $last ? substr($last['end_time'], 0, 5) : '';
    return [
        'booking_id'     => (int)$b['id'],
        'booking_code'   => $b['booking_code'],
        'court_id'       => (int)$b['court_id'],
        'court_name'     => $b['court_name'],
        'court_address'  => $b['court_address'] ?? '',
        'sub_court_name' => $first['sub_court_name'] ?? '',
        'booking_date'   => $first['slot_date'] ?? null,
        'start_time'     => $start,
        'end_time'       => $end,
        'time_range'     => $start . ' - ' . $end,
        'slot_count'     => count($slots),
        'court_amount'   => (int)$b['court_amount'],
        'discount_amount'=> (int)$b['discount_amount'],
        'platform_fee'   => (int)$b['platform_fee'],
        'total_price'    => (int)$b['total_price'],
        'deposit_amount' => (int)$b['deposit_amount'],
        'remain_amount'  => (int)$b['total_price'] - (int)$b['deposit_amount'],
        'payment_method' => $b['payment_method'],
        'booking_status' => $b['status'],
        'payment_status' => $b['payment_status'] ?? 'unpaid',
    ];
}

/**
 * Huỷ booking pending: booking → cancelled, trả slot đang giữ về available, hoàn lượt voucher.
 * Gọi bên trong transaction, sau khi đã khoá payment.
 */
function payment_release_booking(PDO $pdo, int $bookingId): void
{
    $s = $pdo->prepare('SELECT id, user_id, status, voucher_id FROM bookings WHERE id=? FOR UPDATE');
    $s->execute([$bookingId]);
    $b = $s->fetch();
    if (!$b || $b['status'] !== 'pending') {
        return;
    }
    $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$bookingId]);
    $pdo->prepare("UPDATE time_slots s JOIN booking_slots bs ON bs.slot_id=s.id
                      SET s.status='available', s.held_by=NULL, s.hold_expires_at=NULL
                    WHERE bs.booking_id=? AND s.status='holding' AND s.held_by=?")
        ->execute([$bookingId, (int)$b['user_id']]);
    if (!empty($b['voucher_id'])) {
        $vu = $pdo->prepare('SELECT id, voucher_id FROM voucher_usages WHERE booking_id=? AND released_at IS NULL FOR UPDATE');
        $vu->execute([$bookingId]);
        if ($row = $vu->fetch()) {
            $pdo->prepare('UPDATE voucher_usages SET released_at=NOW() WHERE id=?')->execute([$row['id']]);
            $pdo->prepare('UPDATE vouchers SET used_count=GREATEST(0, used_count-1) WHERE id=?')->execute([$row['voucher_id']]);
        }
    }
}

/**
 * Hết hạn các payment pending quá expires_at → payment expired, booking cancelled, nhả slot + voucher.
 * An toàn khi chạy song song với webhook: cả hai cùng khoá dòng payments bằng FOR UPDATE.
 *
 * @return int số payment đã cho hết hạn
 */
function payment_expire_overdue(PDO $pdo, int $limit = 50): int
{
    if ($pdo->inTransaction() || !payment_tables_ready($pdo)) {
        return 0;
    }
    $ids = $pdo->query("SELECT id FROM payments WHERE status='pending' AND expires_at < NOW() ORDER BY id LIMIT " . max(1, $limit))
               ->fetchAll(PDO::FETCH_COLUMN);
    $done = 0;
    foreach ($ids as $paymentId) {
        try {
            $pdo->beginTransaction();
            $s = $pdo->prepare("SELECT id, booking_id, membership_id FROM payments WHERE id=? AND status='pending' AND expires_at < NOW() FOR UPDATE");
            $s->execute([(int)$paymentId]);
            $p = $s->fetch();
            if ($p) {
                if (!empty($p['booking_id'])) {
                    payment_release_booking($pdo, (int)$p['booking_id']);
                }
                if (!empty($p['membership_id'])) {
                    $pdo->prepare("UPDATE user_memberships SET status='cancelled', updated_at=NOW() WHERE id=? AND status='pending'")
                        ->execute([(int)$p['membership_id']]);
                }
                $pdo->prepare("UPDATE payments SET status='expired' WHERE id=?")->execute([(int)$p['id']]);
                $done++;
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('[payment-expire] #' . $paymentId . ': ' . $e->getMessage());
        }
    }
    return $done;
}

/** Tách mã thanh toán từ payload SePay (ưu tiên field code, sau đó tới content/description). */
function payment_extract_code(array $payload): ?string
{
    $prefix = preg_quote(payment_config()['prefix'], '/');
    foreach (['code', 'content', 'description'] as $field) {
        $value = isset($payload[$field]) && is_scalar($payload[$field]) ? (string)$payload[$field] : '';
        if ($value === '') {
            continue;
        }
        // Khớp cả GDM000123, GDM800001, GDM 000123, GDM-123, GDM123
        if (preg_match('/' . $prefix . '[-_\s]*0*(\d{1,9})/i', $value, $m) && (int)$m[1] > 0) {
            return payment_code_for_booking((int)$m[1]);
        }
        // Dự phòng trường hợp SePay đang để prefix mặc định DH trên my.sepay.vn
        if (preg_match('/\bDH[-_\s]*0*(\d{1,9})\b/i', $value, $m) && (int)$m[1] > 0) {
            return payment_code_for_booking((int)$m[1]);
        }
    }
    return null;
}

/**
 * Kích hoạt gói hội viên tự động sau khi SePay báo đã nhận đủ 100% tiền gói.
 * Gọi bên trong transaction.
 */
function payment_try_confirm_membership(PDO $pdo, int $membershipId): bool
{
    $s = $pdo->prepare('SELECT id, user_id, tier, duration_days, status FROM user_memberships WHERE id=? FOR UPDATE');
    $s->execute([$membershipId]);
    $mem = $s->fetch();
    if (!$mem) {
        return false;
    }
    if ($mem['status'] === 'active') {
        return true;
    }
    if (!in_array($mem['status'], ['pending', 'cancelled'], true)) {
        return false;
    }

    $userId = (int)$mem['user_id'];
    $tier = (string)$mem['tier'];
    $durationDays = max(1, (int)$mem['duration_days']);
    $now = date('Y-m-d H:i:s');

    // Kiểm tra gói active hiện tại của user (nếu có)
    $actStmt = $pdo->prepare("SELECT id, tier, start_date, end_date FROM user_memberships
        WHERE user_id = ? AND status = 'active' AND end_date >= NOW() AND id <> ?
        ORDER BY discount_percent DESC, end_date DESC LIMIT 1 FOR UPDATE");
    $actStmt->execute([$userId, $membershipId]);
    $active = $actStmt->fetch();

    if ($active) {
        if ($active['tier'] === $tier) {
            // Gia hạn cùng hạng: cộng dồn thời hạn tiếp nối hạn cũ, xóa/chuyển bản ghi cũ sang cancelled để không trùng
            $curEnd = strtotime((string)$active['end_date']);
            $baseTime = ($curEnd > time()) ? $curEnd : time();
            $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", $baseTime));
            $newEnd = $targetDate . ' 23:59:59';
            $newStart = $active['start_date'];
            $pdo->prepare("UPDATE user_memberships SET status = 'cancelled', updated_at = NOW() WHERE id = ?")->execute([(int)$active['id']]);
        } else {
            // Đổi hạng: chuyển gói cũ sang cancelled, tính mới từ NOW()
            $pdo->prepare("UPDATE user_memberships SET status = 'cancelled', updated_at = NOW() WHERE id = ?")->execute([(int)$active['id']]);
            $newStart = $now;
            $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", time()));
            $newEnd = $targetDate . ' 23:59:59';
        }
    } else {
        $newStart = $now;
        $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", time()));
        $newEnd = $targetDate . ' 23:59:59';
    }

    $upd = $pdo->prepare("UPDATE user_memberships
        SET start_date = ?, end_date = ?, status = 'active', payment_method = 'VietQR (SePay 24/7)', updated_at = NOW()
        WHERE id = ?");
    $upd->execute([$newStart, $newEnd, $membershipId]);

    return true;
}

/**
 * Xác nhận booking đã thanh toán đủ: slot → booked, booking → confirmed.
 * Nếu đơn đã bị huỷ do hết hạn mà slot vẫn trống thì vẫn nhận lại (khách chuyển trễ nhưng còn sân).
 * Trả true nếu xác nhận được, false nếu slot đã bị người khác lấy.
 */
function payment_try_confirm_booking(PDO $pdo, int $bookingId): bool
{
    $s = $pdo->prepare('SELECT id, user_id, status, voucher_id FROM bookings WHERE id=? FOR UPDATE');
    $s->execute([$bookingId]);
    $b = $s->fetch();
    if (!$b || !in_array($b['status'], ['pending', 'cancelled'], true)) {
        return false;
    }
    $userId = (int)$b['user_id'];
    $s = $pdo->prepare('SELECT s.id, s.status, s.held_by, (s.hold_expires_at IS NOT NULL AND s.hold_expires_at < NOW()) AS hold_expired,
                               (TIMESTAMP(s.slot_date, s.start_time) > NOW()) AS future_start
                          FROM booking_slots bs JOIN time_slots s ON s.id=bs.slot_id
                         WHERE bs.booking_id=? ORDER BY s.id FOR UPDATE');
    $s->execute([$bookingId]);
    $slots = $s->fetchAll();
    if (!$slots) {
        return false;
    }
    foreach ($slots as $slot) {
        if (!$slot['future_start']) {
            return false;
        }
        $mine = $slot['status'] === 'holding' && (int)$slot['held_by'] === $userId;
        $free = $slot['status'] === 'available' || ($slot['status'] === 'holding' && $slot['hold_expired']);
        if (!$mine && !$free) {
            return false;
        }
        // Slot không được thuộc một đơn đang hoạt động khác
        $other = $pdo->prepare("SELECT 1 FROM booking_slots bs JOIN bookings ob ON ob.id=bs.booking_id
                                 WHERE bs.slot_id=? AND bs.booking_id<>? AND ob.status IN ('pending','confirmed','completed') LIMIT 1");
        $other->execute([(int)$slot['id'], $bookingId]);
        if ($other->fetchColumn()) {
            return false;
        }
    }
    $upd = $pdo->prepare("UPDATE time_slots SET status='booked', held_by=NULL, hold_expires_at=NULL WHERE id=?");
    foreach ($slots as $slot) {
        $upd->execute([(int)$slot['id']]);
    }
    // Đơn từng bị huỷ do hết hạn → lấy lại lượt voucher đã hoàn
    if ($b['status'] === 'cancelled' && !empty($b['voucher_id'])) {
        $vu = $pdo->prepare('SELECT id, voucher_id FROM voucher_usages WHERE booking_id=? AND released_at IS NOT NULL FOR UPDATE');
        $vu->execute([$bookingId]);
        if ($row = $vu->fetch()) {
            $pdo->prepare('UPDATE voucher_usages SET released_at=NULL WHERE id=?')->execute([$row['id']]);
            $pdo->prepare('UPDATE vouchers SET used_count=used_count+1 WHERE id=?')->execute([$row['voucher_id']]);
        }
    }
    $pdo->prepare("UPDATE bookings SET status='confirmed', payment_status='deposit_paid' WHERE id=?")->execute([$bookingId]);
    return true;
}

/**
 * Xử lý 1 webhook SePay. Idempotent theo sepay_id.
 *
 * @return array{result:string, payment_id:?int, booking_id:?int, membership_id:?int, confirmed:bool}
 * @throws Throwable khi lỗi DB tạm thời (caller trả 5xx để SePay retry)
 */
function payment_process_webhook(PDO $pdo, array $payload): array
{
    payment_ensure_schema($pdo);
    $out = ['result' => 'ignored', 'payment_id' => null, 'booking_id' => null, 'membership_id' => null, 'confirmed' => false];

    $sepayId = isset($payload['id']) && is_scalar($payload['id']) && preg_match('/^\d{1,19}$/', (string)$payload['id']) ? (string)$payload['id'] : null;
    if ($sepayId === null) {
        $out['result'] = 'invalid_payload';
        return $out;
    }
    $str = static fn($k, $max) => isset($payload[$k]) && is_scalar($payload[$k]) ? mb_substr(trim((string)$payload[$k]), 0, $max) : null;
    $amount = isset($payload['transferAmount']) && is_numeric($payload['transferAmount']) ? (int)round((float)$payload['transferAmount']) : 0;
    $txDate = $str('transactionDate', 30);
    $txDate = ($txDate && strtotime($txDate) !== false) ? date('Y-m-d H:i:s', strtotime($txDate)) : null;

    // 1. Lưu log + chống trùng
    $ins = $pdo->prepare('INSERT IGNORE INTO sepay_transactions
        (sepay_id, gateway, account_number, transfer_type, transfer_amount, content, code, reference_code, transaction_date, raw_payload, result)
        VALUES (?,?,?,?,?,?,?,?,?,?,\'received\')');
    $ins->execute([
        $sepayId, $str('gateway', 100), $str('accountNumber', 50), $str('transferType', 5), $amount,
        $str('content', 500), $str('code', 100), $str('referenceCode', 100), $txDate,
        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    if ($ins->rowCount() === 0) {
        $s = $pdo->prepare('SELECT result, payment_id FROM sepay_transactions WHERE sepay_id=?');
        $s->execute([$sepayId]);
        $prev = $s->fetch();
        // Lần trước đã xử lý xong → bỏ qua. Lần trước lỗi giữa chừng (vẫn 'received') → xử lý lại.
        if ($prev && $prev['result'] !== 'received') {
            return ['result' => 'duplicate', 'payment_id' => $prev['payment_id'] ? (int)$prev['payment_id'] : null, 'booking_id' => null, 'membership_id' => null, 'confirmed' => false];
        }
    }
    $mark = static function (string $result, ?int $paymentId = null, ?string $note = null) use ($pdo, $sepayId): void {
        $pdo->prepare('UPDATE sepay_transactions SET result=?, payment_id=?, note=? WHERE sepay_id=?')
            ->execute([$result, $paymentId, $note, $sepayId]);
    };

    // 2. Chỉ quan tâm tiền vào, đúng tài khoản nhận
    if (strtolower((string)$str('transferType', 5)) !== 'in') {
        $mark('ignored_out');
        $out['result'] = 'ignored_out';
        return $out;
    }
    if ($amount <= 0) {
        $mark('unmatched', null, 'Số tiền giao dịch không hợp lệ.');
        $out['result'] = 'unmatched';
        return $out;
    }
    $code = payment_extract_code($payload);
    if ($code === null) {
        // Dự phòng: Nếu khách quên ghi mã GDM hoặc ngân hàng cắt nội dung,
        // kiểm tra xem hiện tại có ĐÚNG 1 đơn đang chờ thanh toán (pending, còn hạn) khớp đúng số tiền không.
        $sFallback = $pdo->prepare("SELECT payment_code FROM payments WHERE status='pending' AND expires_at >= NOW() AND amount=? LIMIT 2");
        $sFallback->execute([$amount]);
        $candidates = $sFallback->fetchAll(PDO::FETCH_COLUMN);
        if (count($candidates) === 1) {
            $code = (string)$candidates[0];
        }
    }
    if ($code === null) {
        $cfgAcc = payment_config()['account_number'];
        $payloadAcc = preg_replace('/\s+/', '', (string)$str('accountNumber', 50));
        if ($cfgAcc !== '' && $payloadAcc !== '' && $payloadAcc !== $cfgAcc) {
            $mark('wrong_account', null, 'Tài khoản nhận ' . $payloadAcc . ' không khớp cấu hình.');
            $out['result'] = 'wrong_account';
            return $out;
        }
        $mark('unmatched', null, 'Không tìm thấy mã thanh toán trong nội dung.');
        $out['result'] = 'unmatched';
        return $out;
    }

    // 3. Khớp đơn trong transaction
    $pdo->beginTransaction();
    try {
        $s = $pdo->prepare('SELECT p.*, (p.expires_at < NOW()) AS is_expired FROM payments p WHERE p.payment_code=? FOR UPDATE');
        $s->execute([$code]);
        $p = $s->fetch();
        if (!$p) {
            $mark('unmatched', null, 'Mã ' . $code . ' không tồn tại.');
            $pdo->commit();
            $out['result'] = 'unmatched';
            return $out;
        }
        $paymentId = (int)$p['id'];
        $bookingId = !empty($p['booking_id']) ? (int)$p['booking_id'] : null;
        $membershipId = !empty($p['membership_id']) ? (int)$p['membership_id'] : null;
        $out['payment_id'] = $paymentId;
        $out['booking_id'] = $bookingId;
        $out['membership_id'] = $membershipId;
        $paidTotal = (int)$p['paid_amount'] + $amount;
        $pdo->prepare('UPDATE payments SET paid_amount=? WHERE id=?')->execute([$paidTotal, $paymentId]);

        if (in_array($p['status'], ['paid', 'late_paid', 'refunded'], true)) {
            $mark('overpaid', $paymentId, 'Đơn đã ở trạng thái ' . $p['status'] . ', khoản này cần hoàn lại.');
            $out['result'] = 'overpaid';
        } elseif ($paidTotal < (int)$p['amount']) {
            $mark('underpaid', $paymentId, 'Đã nhận ' . $paidTotal . '/' . (int)$p['amount'] . 'đ.');
            $out['result'] = 'underpaid';
        } elseif ($membershipId !== null) {
            // Thanh toán Gói Hội Viên: Kích hoạt tự động ngay cả khi vừa quá hạn 15 phút
            if (payment_try_confirm_membership($pdo, $membershipId)) {
                $pdo->prepare("UPDATE payments SET status='paid', paid_at=NOW() WHERE id=?")->execute([$paymentId]);
                $late = $p['status'] === 'expired' || $p['is_expired'];
                $mark($late ? 'matched_late' : 'matched', $paymentId);
                $out['result'] = $late ? 'matched_late' : 'matched';
                $out['confirmed'] = true;
            } else {
                $pdo->prepare("UPDATE payments SET status='late_paid', paid_at=NOW() WHERE id=?")->execute([$paymentId]);
                $mark('late_paid', $paymentId, 'Không thể kích hoạt gói hội viên.');
                $out['result'] = 'late_paid';
            }
        } elseif ($p['status'] === 'cancelled') {
            // Khách tự huỷ rồi vẫn chuyển → không tự nhận lại, admin hoàn tiền.
            $pdo->prepare("UPDATE payments SET status='late_paid', paid_at=NOW() WHERE id=?")->execute([$paymentId]);
            $mark('late_paid', $paymentId, 'Đơn đã bị khách huỷ trước khi tiền về.');
            $out['result'] = 'late_paid';
        } else {
            // pending (còn hạn hoặc quá hạn nhưng chưa dọn) hoặc expired → thử xác nhận
            if ($p['status'] === 'pending' && $p['is_expired']) {
                payment_release_booking($pdo, (int)$bookingId);
            }
            if ($bookingId !== null && payment_try_confirm_booking($pdo, $bookingId)) {
                $pdo->prepare("UPDATE payments SET status='paid', paid_at=NOW() WHERE id=?")->execute([$paymentId]);
                $late = $p['status'] === 'expired' || $p['is_expired'];
                $mark($late ? 'matched_late' : 'matched', $paymentId);
                $out['result'] = $late ? 'matched_late' : 'matched';
                $out['confirmed'] = true;
            } else {
                $pdo->prepare("UPDATE payments SET status='late_paid', paid_at=NOW() WHERE id=?")->execute([$paymentId]);
                $mark('late_paid', $paymentId, 'Tiền về sau khi khung giờ đã có người khác đặt.');
                $out['result'] = 'late_paid';
            }
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
    return $out;
}

/** Gửi email hóa đơn xác nhận đặt cọc thành công (kèm logo GODMINTON đính kèm CID). */
function payment_send_confirmation_email(PDO $pdo, int $bookingId): void
{
    try {
        require_once __DIR__ . '/auth-mailer.php';
        $summary = payment_booking_summary($pdo, $bookingId);
        if (!$summary || $summary['booking_status'] !== 'confirmed') {
            return;
        }
        $s = $pdo->prepare('SELECT u.name, u.email, u.phone FROM bookings b JOIN users u ON u.id=b.user_id WHERE b.id=?');
        $s->execute([$bookingId]);
        $user = $s->fetch();
        if (!$user || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $payment = payment_fetch_by_booking($pdo, $bookingId);
        $paymentCode = $payment['payment_code'] ?? payment_code_for_booking($bookingId);
        $paidAtStr = !empty($payment['paid_at'])
            ? date('H:i · d/m/Y', strtotime((string)$payment['paid_at']))
            : date('H:i · d/m/Y');

        $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $money = static fn($n) => number_format((int)$n, 0, ',', '.') . ' đ';

        $dateStr = $summary['booking_date'] ? date('d/m/Y', strtotime((string)$summary['booking_date'])) : '';
        $durationHours = max(0.5, ((int)($summary['slot_count'] ?? 1)) * 0.5);
        $durationText = ($durationHours == (int)$durationHours ? (int)$durationHours : $durationHours) . ' giờ';
        $historyUrl = rtrim((string)getenv('APP_URL'), '/') . '/history.php';
        $year = date('Y');

        $logoPath = __DIR__ . '/../assets/img/logo.jpg';
        $logoHtml = is_file($logoPath)
            ? '<img src="cid:godminton-logo" width="175" height="118" alt="GODMINTON" style="display:block;width:175px;max-width:100%;height:auto;border:0;margin:0 auto;">'
            : '<span style="font-size:26px;font-weight:800;color:#0d9488;letter-spacing:1px;">GODMINTON</span>';

        $bookingCodeSafe = $e($summary['booking_code']);
        $paymentCodeSafe = $e($paymentCode);
        $userNameSafe    = $e($user['name']);
        $userEmailSafe   = $e($user['email']);
        $userPhoneSafe   = $e($user['phone'] ?? '—');
        $courtNameSafe   = $e($summary['court_name']);
        $courtAddrSafe   = $e($summary['court_address'] ?: 'Hệ thống sân cầu lông GODMINTON');
        $subCourtSafe    = $e($summary['sub_court_name']);
        $timeRangeSafe   = $e($summary['time_range'] . ' (' . $durationText . ')');
        $methodSafe      = $e($summary['payment_method'] ?: 'Chuyển khoản VietQR 24/7 (SePay)');
        $safeHistoryUrl  = $e($historyUrl);

        $courtAmountTxt  = $e($money($summary['court_amount'] ?: $summary['total_price']));
        $discountAmount  = (int)($summary['discount_amount'] ?? 0);
        $platformFee     = (int)($summary['platform_fee'] ?? 0);
        $platformFeeTxt  = $platformFee > 0 ? $e($money($platformFee)) : '0 đ (Miễn phí)';
        $totalPriceTxt   = $e($money($summary['total_price']));
        $depositTxt      = $e($money($summary['deposit_amount']));
        $remainTxt       = $e($money($summary['remain_amount']));

        $discountRowHtml = '';
        if ($discountAmount > 0) {
            $discountTxt = '- ' . $e($money($discountAmount));
            $discountRowHtml = <<<HTML
<tr>
    <td style="padding:10px 14px;border-bottom:1px solid #e2f2ee;color:#0f766e;font-size:14px;">Ưu đãi / Giảm giá</td>
    <td align="right" style="padding:10px 14px;border-bottom:1px solid #e2f2ee;color:#0f766e;font-weight:700;font-size:14px;">{$discountTxt}</td>
</tr>
HTML;
        }

        $html = <<<HTML
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hóa đơn đặt sân {$bookingCodeSafe} - GODMINTON</title>
</head>
<body style="margin:0;padding:0;background-color:#e5f5f1;background-image:linear-gradient(140deg,#d9f7ed 0%,#edf5ff 100%);font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;color:#193d42;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Hóa đơn điện tử &amp; Vé đặt sân {$bookingCodeSafe} — Đã nhận cọc {$depositTxt} tại {$courtNameSafe}.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#e5f5f1" style="background-image:linear-gradient(140deg,#d9f7ed 0%,#edf5ff 100%);font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;">
<tr>
<td align="center" style="padding:28px 12px;">
    <table role="presentation" width="620" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:620px;border:1px solid #c8e7e0;border-radius:22px;box-shadow:0 18px 46px rgba(15,118,110,0.10);overflow:hidden;">

        <!-- HEADER CÓ LOGO GODMINTON -->
        <tr>
            <td align="center" bgcolor="#0f766e" style="padding:28px 24px 22px;background-image:linear-gradient(125deg,#115e59 0%,#0d9488 60%,#0891b2 100%);border-radius:22px 22px 0 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,0.12);">
                    <tr>
                        <td align="center" style="padding:10px 18px;border-radius:16px;">{$logoHtml}</td>
                    </tr>
                </table>
                <p style="font-size:11px;line-height:1.6;letter-spacing:1.2px;font-weight:700;color:#e3fffa;margin:16px 0 4px;text-transform:uppercase;">NỀN TẢNG ĐẶT SÂN CẦU LÔNG TRỰC TUYẾN</p>
                <p style="font-size:13px;color:#ccfbf1;margin:0;font-weight:500;">HÓA ĐƠN ĐIỆN TỬ &amp; VÉ XÁC NHẬN ĐẶT SÂN</p>
            </td>
        </tr>
        <tr>
            <td height="5" bgcolor="#5eead4" style="height:5px;font-size:0;line-height:0;background-image:linear-gradient(90deg,#5eead4 0%,#67e8f9 100%);">&nbsp;</td>
        </tr>

        <!-- BODY -->
        <tr>
            <td style="padding:28px 26px 24px;">
                <!-- Badge trạng thái -->
                <div style="text-align:center;margin-bottom:18px;">
                    <span style="display:inline-block;padding:6px 14px;border-radius:999px;background-color:#dcfce7;border:1px solid #86efac;font-size:12px;letter-spacing:0.4px;color:#15803d;font-weight:700;">
                        &#10003; ĐÃ NHẬN TIỀN CỌC · XÁC NHẬN TỰ ĐỘNG 24/7
                    </span>
                </div>

                <h1 style="font-size:22px;line-height:1.35;font-weight:800;margin:0 0 12px;color:#115e59;text-align:center;">
                    Đặt cọc sân thành công!
                </h1>
                <p style="font-size:14.5px;line-height:1.7;color:#334155;margin:0 0 22px;text-align:center;">
                    Xin chào <strong>{$userNameSafe}</strong>, hệ thống <strong>GODMINTON</strong> đã nhận được khoản thanh toán tiền cọc của bạn. Dưới đây là chi tiết vé đặt sân và hóa đơn điện tử của bạn:
                </p>

                <!-- THẺ VÉ ĐIỆN TỬ / MÃ CHECK-IN -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f0fdfa" style="border:2px dashed #14b8a6;border-radius:16px;margin-bottom:24px;">
                    <tr>
                        <td align="center" style="padding:18px 16px;">
                            <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:1px;color:#0f766e;text-transform:uppercase;">MÃ ĐẶT SÂN (XUẤT TRÌNH KHI CHECK-IN)</p>
                            <p style="margin:0 0 8px;font-size:24px;font-weight:800;letter-spacing:1px;color:#0f172a;">{$bookingCodeSafe}</p>
                            <p style="margin:0;font-size:12.5px;color:#475569;">
                                Mã GD: <strong>{$paymentCodeSafe}</strong> &nbsp;&bull;&nbsp; Thời gian xác nhận: <strong>{$paidAtStr}</strong>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- PHẦN 1: THÔNG TIN LỊCH ĐẶT SÂN & KHÁCH HÀNG -->
                <p style="margin:0 0 10px;font-size:12px;font-weight:800;letter-spacing:0.6px;color:#0f766e;text-transform:uppercase;">
                    1. Thông tin sân &amp; lịch thi đấu
                </p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #d5ebe5;border-radius:12px;overflow:hidden;margin-bottom:22px;font-size:14px;">
                    <tr bgcolor="#f8fafc">
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;width:38%;">Cụm sân cầu lông</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:700;">{$courtNameSafe}</td>
                    </tr>
                    <tr>
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;">Địa chỉ sân</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#334155;font-size:13px;">{$courtAddrSafe}</td>
                    </tr>
                    <tr bgcolor="#f8fafc">
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;">Sân / Thảm thi đấu</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#0d9488;font-weight:700;">{$subCourtSafe}</td>
                    </tr>
                    <tr>
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;">Ngày thi đấu</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:700;">{$dateStr}</td>
                    </tr>
                    <tr bgcolor="#f8fafc">
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;">Khung giờ chơi</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:700;">{$timeRangeSafe}</td>
                    </tr>
                    <tr>
                        <td style="padding:11px 14px;color:#64748b;">Người đặt sân</td>
                        <td align="right" style="padding:11px 14px;color:#334155;font-weight:600;">{$userNameSafe} ({$userPhoneSafe})</td>
                    </tr>
                </table>

                <!-- PHẦN 2: CHI TIẾT HÓA ĐƠN THANH TOÁN -->
                <p style="margin:0 0 10px;font-size:12px;font-weight:800;letter-spacing:0.6px;color:#0f766e;text-transform:uppercase;">
                    2. Chi tiết hóa đơn thanh toán
                </p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #cdece3;border-radius:12px;overflow:hidden;margin-bottom:24px;">
                    <tr>
                        <td style="padding:10px 14px;border-bottom:1px solid #e2f2ee;color:#64748b;font-size:14px;">Giá thuê sân</td>
                        <td align="right" style="padding:10px 14px;border-bottom:1px solid #e2f2ee;color:#0f172a;font-weight:600;font-size:14px;">{$courtAmountTxt}</td>
                    </tr>
                    {$discountRowHtml}
                    <tr>
                        <td style="padding:10px 14px;border-bottom:1px solid #e2f2ee;color:#64748b;font-size:14px;">Phí dịch vụ</td>
                        <td align="right" style="padding:10px 14px;border-bottom:1px solid #e2f2ee;color:#0f172a;font-weight:600;font-size:14px;">{$platformFeeTxt}</td>
                    </tr>
                    <tr>
                        <td style="padding:10px 14px;border-bottom:1px solid #e2f2ee;color:#64748b;font-size:14px;">Phương thức thanh toán</td>
                        <td align="right" style="padding:10px 14px;border-bottom:1px solid #e2f2ee;color:#334155;font-weight:600;font-size:13.5px;">{$methodSafe}</td>
                    </tr>
                    <tr bgcolor="#f8fafc">
                        <td style="padding:12px 14px;border-bottom:1px solid #cdece3;color:#0f172a;font-weight:700;font-size:15px;">Tổng cộng tiền sân</td>
                        <td align="right" style="padding:12px 14px;border-bottom:1px solid #cdece3;color:#0f172a;font-weight:800;font-size:16px;">{$totalPriceTxt}</td>
                    </tr>
                    <tr bgcolor="#ecfdf5">
                        <td style="padding:12px 14px;border-bottom:1px solid #bbf7d0;color:#15803d;font-weight:700;font-size:14.5px;">
                            &#10003; Đã đặt cọc (50%)
                        </td>
                        <td align="right" style="padding:12px 14px;border-bottom:1px solid #bbf7d0;color:#15803d;font-weight:800;font-size:16px;">
                            {$depositTxt}
                        </td>
                    </tr>
                    <tr bgcolor="#fffbeb">
                        <td style="padding:12px 14px;color:#b45309;font-weight:700;font-size:14.5px;">
                            Còn lại thanh toán tại sân
                        </td>
                        <td align="right" style="padding:12px 14px;color:#d97706;font-weight:800;font-size:16px;">
                            {$remainTxt}
                        </td>
                    </tr>
                </table>

                <!-- NÚT XEM LỊCH ĐẶT SÂN -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 22px;">
                    <tr>
                        <td align="center" bgcolor="#0d9488" style="border-radius:999px;background-image:linear-gradient(120deg,#0f766e 0%,#0891b2 100%);box-shadow:0 6px 16px rgba(13,148,136,0.25);">
                            <a href="{$safeHistoryUrl}" style="display:inline-block;padding:13px 28px;font-size:14.5px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">
                                Xem Vé Điện Tử &amp; Lịch Đặt Sân
                            </a>
                        </td>
                    </tr>
                </table>

                <!-- LƯU Ý CHECK-IN -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8fafc" style="border:1px solid #e2e8f0;border-left:4px solid #0d9488;border-radius:10px;">
                    <tr>
                        <td style="padding:12px 16px;font-size:12.5px;line-height:1.65;color:#475569;">
                            <strong style="color:#0f172a;">Lưu ý khi đến sân:</strong><br>
                            &bull; Vui lòng có mặt trước giờ chơi <strong>10–15 phút</strong> và cung cấp mã đặt sân <strong>{$bookingCodeSafe}</strong> tại quầy lễ tân để nhận sân.<br>
                            &bull; Khoản đặt cọc không hoàn lại nếu hủy trong vòng <strong>2 giờ</strong> trước giờ thi đấu (Hội viên áp dụng đặc quyền hủy trước 30 phút).
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td align="center" bgcolor="#edf8f5" style="padding:18px 24px;border-top:1px solid #d5ebe5;border-radius:0 0 22px 22px;background-image:linear-gradient(110deg,#edf9f4 0%,#eef6ff 100%);">
                <p style="margin:0;color:#58726e;font-size:12px;line-height:1.7;">
                    &copy; {$year} <strong>GODMINTON</strong> &middot; Hệ thống Đặt Sân Cầu Lông Trực Tuyến<br>
                    Hóa đơn điện tử được gửi tự động đến {$userEmailSafe}. Chúc bạn có những trận cầu thật bùng nổ!
                </p>
            </td>
        </tr>
    </table>
</td>
</tr>
</table>
</body>
</html>
HTML;

        $rows = [
            'Mã đặt sân'              => $summary['booking_code'],
            'Mã giao dịch SePay'      => $paymentCode,
            'Cụm sân'                 => $summary['court_name'] . ' · ' . $summary['sub_court_name'],
            'Địa chỉ'                 => $summary['court_address'] ?: 'GODMINTON',
            'Ngày chơi'               => $dateStr,
            'Khung giờ'               => $summary['time_range'] . ' (' . $durationText . ')',
            'Giá thuê sân'            => $money($summary['court_amount'] ?: $summary['total_price']),
            'Tổng tiền'               => $money($summary['total_price']),
            'Đã đặt cọc (50%)'        => $money($summary['deposit_amount']),
            'Còn lại thanh toán sân'  => $money($summary['remain_amount']),
            'Xem lịch đặt sân'        => $historyUrl,
        ];
        $text = "HÓA ĐƠN XÁC NHẬN ĐẶT SÂN - GODMINTON\n"
              . "====================================\n"
              . "Xin chào {$user['name']}, GODMINTON đã nhận được tiền cọc của bạn.\n\n";
        foreach ($rows as $k => $v) {
            $text .= "{$k}: {$v}\n";
        }

        $mail = god_auth_mailer();
        if (is_file($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'godminton-logo', 'godminton-logo.jpg', 'base64', 'image/jpeg');
        }
        $mail->addAddress($user['email'], (string)$user['name']);
        $mail->Subject = 'Hóa đơn xác nhận đặt sân ' . $summary['booking_code'] . ' - GODMINTON';
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
    } catch (Throwable $e) {
        error_log('[payment-email] booking #' . $bookingId . ': ' . $e->getMessage());
    }
}

/** Gửi email Hóa đơn điện tử & Xác nhận kích hoạt Gói Hội Viên VIP (kèm logo GODMINTON đính kèm CID). */
function payment_send_membership_confirmation_email(PDO $pdo, int $membershipId): void
{
    try {
        require_once __DIR__ . '/auth-mailer.php';
        $stmt = $pdo->prepare(
            "SELECT um.*, p.title AS package_title, p.benefits,
                    u.name AS user_name, u.email AS user_email, u.phone AS user_phone
               FROM user_memberships um
               JOIN posts p ON p.id = um.post_id
               JOIN users u ON u.id = um.user_id
              WHERE um.id = ? LIMIT 1"
        );
        $stmt->execute([$membershipId]);
        $mem = $stmt->fetch();
        if (!$mem || $mem['status'] !== 'active' || !filter_var($mem['user_email'], FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $payment = payment_fetch_by_membership($pdo, $membershipId);
        $paymentCode = $payment['payment_code'] ?? payment_code_for_membership($membershipId);
        $paidAtStr = !empty($payment['paid_at'])
            ? date('H:i · d/m/Y', strtotime((string)$payment['paid_at']))
            : date('H:i · d/m/Y');

        $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $money = static fn($n) => number_format((int)round((float)$n), 0, ',', '.') . ' đ';

        $membershipCode = 'MB-' . str_pad((string)$membershipId, 6, '0', STR_PAD_LEFT);
        $tierLabel = match ((string)$mem['tier']) {
            'platinum' => 'Hội Viên Bạch Kim (Platinum VIP)',
            'gold'     => 'Hội Viên Vàng (Golden Racket)',
            default    => 'Hội Viên Bạc (Silver Member)',
        };
        $discountPercent = (int)($mem['discount_percent'] ?? 5);
        $durationDays    = (int)($mem['duration_days'] ?? 30);
        $startStr        = $mem['start_date'] ? date('d/m/Y', strtotime((string)$mem['start_date'])) : date('d/m/Y');
        $endStr          = $mem['end_date'] ? date('d/m/Y', strtotime((string)$mem['end_date'])) : '';
        $priceTxt        = $e($money($mem['price_paid'] ?? 0));
        $profileUrl      = rtrim((string)getenv('APP_URL'), '/') . '/profile.php?tab=membership';
        $courtsUrl       = rtrim((string)getenv('APP_URL'), '/') . '/courts.php';
        $year            = date('Y');

        $logoPath = __DIR__ . '/../assets/img/logo.jpg';
        $logoHtml = is_file($logoPath)
            ? '<img src="cid:godminton-logo" width="175" height="118" alt="GODMINTON" style="display:block;width:175px;max-width:100%;height:auto;border:0;margin:0 auto;">'
            : '<span style="font-size:26px;font-weight:800;color:#0d9488;letter-spacing:1px;">GODMINTON</span>';

        $userNameSafe      = $e($mem['user_name']);
        $userEmailSafe     = $e($mem['user_email']);
        $userPhoneSafe     = $e($mem['user_phone'] ?: '—');
        $packageTitleSafe  = $e($mem['package_title']);
        $tierLabelSafe     = $e($tierLabel);
        $memCodeSafe       = $e($membershipCode);
        $payCodeSafe       = $e($paymentCode);
        $safeProfileUrl    = $e($profileUrl);
        $safeCourtsUrl     = $e($courtsUrl);

        $html = <<<HTML
<!doctype html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Hóa đơn kích hoạt Gói Hội Viên {$memCodeSafe} - GODMINTON</title>
</head>
<body style="margin:0;padding:0;background-color:#e5f5f1;background-image:linear-gradient(140deg,#d9f7ed 0%,#edf5ff 100%);font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;color:#193d42;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">Đã kích hoạt {$packageTitleSafe} ({$memCodeSafe}) — Giảm {$discountPercent}% tiền sân tự động!</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#e5f5f1" style="background-image:linear-gradient(140deg,#d9f7ed 0%,#edf5ff 100%);font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;">
<tr>
<td align="center" style="padding:28px 12px;">
    <table role="presentation" width="620" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:620px;border:1px solid #c8e7e0;border-radius:22px;box-shadow:0 18px 46px rgba(15,118,110,0.10);overflow:hidden;">

        <!-- HEADER CÓ LOGO GODMINTON -->
        <tr>
            <td align="center" bgcolor="#0f766e" style="padding:28px 24px 22px;background-image:linear-gradient(125deg,#115e59 0%,#0d9488 60%,#0891b2 100%);border-radius:22px 22px 0 0;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,0.12);">
                    <tr>
                        <td align="center" style="padding:10px 18px;border-radius:16px;">{$logoHtml}</td>
                    </tr>
                </table>
                <p style="font-size:11px;line-height:1.6;letter-spacing:1.2px;font-weight:700;color:#e3fffa;margin:16px 0 4px;text-transform:uppercase;">NỀN TẢNG ĐẶT SÂN CẦU LÔNG TRỰC TUYẾN</p>
                <p style="font-size:13px;color:#fde68a;margin:0;font-weight:700;">HÓA ĐƠN ĐIỆN TỬ &amp; CHỨNG NHẬN THẺ HỘI VIÊN VIP</p>
            </td>
        </tr>
        <tr>
            <td height="5" bgcolor="#f59e0b" style="height:5px;font-size:0;line-height:0;background-image:linear-gradient(90deg,#f59e0b 0%,#5eead4 100%);">&nbsp;</td>
        </tr>

        <!-- BODY -->
        <tr>
            <td style="padding:28px 26px 24px;">
                <div style="text-align:center;margin-bottom:18px;">
                    <span style="display:inline-block;padding:6px 14px;border-radius:999px;background-color:#fef3c7;border:1px solid #f59e0b;font-size:12px;letter-spacing:0.4px;color:#b45309;font-weight:800;">
                        &#9733; ĐÃ THANH TOÁN &amp; KÍCH HOẠT TỰ ĐỘNG 24/7
                    </span>
                </div>

                <h1 style="font-size:22px;line-height:1.35;font-weight:800;margin:0 0 12px;color:#115e59;text-align:center;">
                    Kích hoạt Gói Hội Viên thành công!
                </h1>
                <p style="font-size:14.5px;line-height:1.7;color:#334155;margin:0 0 22px;text-align:center;">
                    Xin chào <strong>{$userNameSafe}</strong>, hệ thống <strong>GODMINTON</strong> đã nhận đủ thanh toán và kích hoạt ngay đặc quyền <strong>Giảm {$discountPercent}% tiền sân</strong> cho tài khoản của bạn.
                </p>

                <!-- THẺ HỘI VIÊN ĐIỆN TỬ -->
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#fffbeb" style="border:2px dashed #f59e0b;border-radius:16px;margin-bottom:24px;">
                    <tr>
                        <td align="center" style="padding:18px 16px;">
                            <p style="margin:0 0 4px;font-size:11px;font-weight:700;letter-spacing:1px;color:#b45309;text-transform:uppercase;">MÃ HÓA ĐƠN HỘI VIÊN VIP</p>
                            <p style="margin:0 0 6px;font-size:24px;font-weight:800;letter-spacing:1px;color:#0f172a;">#{$memCodeSafe}</p>
                            <p style="margin:0 0 6px;font-size:14px;font-weight:800;color:#0d9488;">{$tierLabelSafe} · Giảm {$discountPercent}% Tiền Sân</p>
                            <p style="margin:0;font-size:12.5px;color:#475569;">
                                Mã GD SePay: <strong>{$payCodeSafe}</strong> &nbsp;&bull;&nbsp; Xác nhận lúc: <strong>{$paidAtStr}</strong>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- CHI TIẾT GÓI & HÓA ĐƠN -->
                <p style="margin:0 0 10px;font-size:12px;font-weight:800;letter-spacing:0.6px;color:#0f766e;text-transform:uppercase;">
                    Thông tin gói hội viên &amp; thanh toán
                </p>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="border:1px solid #d5ebe5;border-radius:12px;overflow:hidden;margin-bottom:24px;font-size:14px;">
                    <tr bgcolor="#f8fafc">
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;width:38%;">Gói đăng ký</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:700;">{$packageTitleSafe}</td>
                    </tr>
                    <tr>
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;">Đặc quyền chiết khấu</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#15803d;font-weight:800;">Tự động giảm {$discountPercent}% mọi lượt đặt sân</td>
                    </tr>
                    <tr bgcolor="#f8fafc">
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;">Thời hạn hiệu lực</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#0f172a;font-weight:700;">{$startStr} – {$endStr} ({$durationDays} ngày)</td>
                    </tr>
                    <tr>
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;">Thành viên sở hữu</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#334155;font-weight:600;">{$userNameSafe} ({$userPhoneSafe})</td>
                    </tr>
                    <tr bgcolor="#f8fafc">
                        <td style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#64748b;">Phương thức thanh toán</td>
                        <td align="right" style="padding:11px 14px;border-bottom:1px solid #e2e8f0;color:#334155;font-weight:600;">Chuyển khoản VietQR 24/7 (SePay)</td>
                    </tr>
                    <tr bgcolor="#ecfdf5">
                        <td style="padding:13px 14px;color:#15803d;font-weight:800;font-size:15px;">&#10003; Đã thanh toán (100%)</td>
                        <td align="right" style="padding:13px 14px;color:#15803d;font-weight:800;font-size:17px;">{$priceTxt}</td>
                    </tr>
                </table>

                <!-- NÚT HÀNH ĐỘNG -->
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:0 auto 16px;">
                    <tr>
                        <td align="center" bgcolor="#0d9488" style="border-radius:999px;background-image:linear-gradient(120deg,#0f766e 0%,#0891b2 100%);box-shadow:0 6px 16px rgba(13,148,136,0.25);">
                            <a href="{$safeCourtsUrl}" style="display:inline-block;padding:13px 28px;font-size:14.5px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:999px;">
                                Đặt Sân Với Ưu Đãi -{$discountPercent}% Ngay
                            </a>
                        </td>
                    </tr>
                </table>
                <p style="text-align:center;margin:0;font-size:13px;">
                    <a href="{$safeProfileUrl}" style="color:#0d9488;font-weight:700;text-decoration:none;">Quản lý Thẻ Hội Viên tại Trang Cá Nhân &rarr;</a>
                </p>
            </td>
        </tr>

        <!-- FOOTER -->
        <tr>
            <td align="center" bgcolor="#edf8f5" style="padding:18px 24px;border-top:1px solid #d5ebe5;border-radius:0 0 22px 22px;background-image:linear-gradient(110deg,#edf9f4 0%,#eef6ff 100%);">
                <p style="margin:0;color:#58726e;font-size:12px;line-height:1.7;">
                    &copy; {$year} <strong>GODMINTON</strong> &middot; Hệ thống Đặt Sân Cầu Lông Trực Tuyến<br>
                    Hóa đơn điện tử được gửi tự động đến {$userEmailSafe}.
                </p>
            </td>
        </tr>
    </table>
</td>
</tr>
</table>
</body>
</html>
HTML;

        $text = "HÓA ĐƠN KÍCH HOẠT GÓI HỘI VIÊN #{$membershipCode} - GODMINTON\n"
              . "==================================================\n"
              . "Xin chào {$mem['user_name']}, Gói {$mem['package_title']} (Giảm {$discountPercent}% tiền sân) đã được kích hoạt!\n"
              . "Mã giao dịch SePay: {$paymentCode}\n"
              . "Hiệu lực: {$startStr} - {$endStr} ({$durationDays} ngày)\n"
              . "Tổng thanh toán: {$money($mem['price_paid'] ?? 0)}\n"
              . "Quản lý hội viên: {$profileUrl}\n";

        $mail = god_auth_mailer();
        if (is_file($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'godminton-logo', 'godminton-logo.jpg', 'base64', 'image/jpeg');
        }
        $mail->addAddress($mem['user_email'], (string)$mem['user_name']);
        $mail->Subject = 'Kích hoạt thành công ' . $tierLabel . ' (#' . $membershipCode . ') - GODMINTON';
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
    } catch (Throwable $e) {
        error_log('[payment-membership-email] membership #' . $membershipId . ': ' . $e->getMessage());
    }
}

/**
 * Dự phòng: Nếu có cấu hình SEPAY_API_TOKEN (tạo tại my.sepay.vn -> Cấu hình công ty -> API Access),
 * hệ thống tự động kéo danh sách giao dịch mới nhất từ SePay API khi client đang chờ thanh toán.
 * Nhờ đó kể cả khi chạy ở localhost hoặc Webhook chưa cấu hình đúng, đơn vẫn tự xác nhận tức thì.
 */
function payment_sync_from_sepay_api(PDO $pdo): void
{
    $apiToken = trim((string)(getenv('SEPAY_API_TOKEN') ?: ''));
    if ($apiToken === '' || !function_exists('curl_init')) {
        return;
    }
    try {
        $ch = curl_init('https://my.sepay.vn/userapi/transactions/list?limit=15');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 4,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $apiToken,
                'Content-Type: application/json',
            ],
        ]);
        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode !== 200 || !$raw) {
            return;
        }
        $data = json_decode((string)$raw, true);
        if (!is_array($data) || empty($data['transactions']) || !is_array($data['transactions'])) {
            return;
        }
        foreach ($data['transactions'] as $tx) {
            $amountIn = isset($tx['amount_in']) ? (int)round((float)$tx['amount_in']) : 0;
            if ($amountIn <= 0) {
                continue;
            }
            $payload = [
                'id'              => $tx['id'] ?? null,
                'gateway'         => $tx['bank_brand_name'] ?? 'MBBank',
                'transactionDate' => $tx['transaction_date'] ?? null,
                'accountNumber'   => $tx['account_number'] ?? null,
                'subAccount'      => $tx['sub_account'] ?? null,
                'code'            => $tx['code'] ?? null,
                'content'         => $tx['transaction_content'] ?? '',
                'transferType'    => 'in',
                'description'     => $tx['transaction_content'] ?? '',
                'transferAmount'  => $amountIn,
                'accumulated'     => isset($tx['accumulated']) ? (int)round((float)$tx['accumulated']) : 0,
                'referenceCode'   => $tx['reference_number'] ?? null,
            ];
            $res = payment_process_webhook($pdo, $payload);
            if (!empty($res['confirmed'])) {
                if (!empty($res['booking_id'])) {
                    payment_send_confirmation_email($pdo, (int)$res['booking_id']);
                }
                if (!empty($res['membership_id'])) {
                    payment_send_membership_confirmation_email($pdo, (int)$res['membership_id']);
                }
            }
        }
    } catch (Throwable $e) {
        error_log('[sepay-api-sync] ' . $e->getMessage());
    }
}
