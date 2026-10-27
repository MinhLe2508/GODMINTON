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

/** Mã thanh toán in vào nội dung chuyển khoản: GDM000123 (chỉ chữ + số, ngân hàng không cắt mất). */
function payment_code_for_booking(int $bookingId): string
{
    return payment_config()['prefix'] . str_pad((string)$bookingId, 6, '0', STR_PAD_LEFT);
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
 * Tự tạo bảng payments / sepay_transactions + cột bookings.payment_status nếu DB chưa có.
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
    $s = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME='payment_status'");
    $s->execute();
    if (!$s->fetchColumn()) {
        $pdo->exec("ALTER TABLE bookings ADD COLUMN payment_status ENUM('unpaid','deposit_paid','refunded') NOT NULL DEFAULT 'unpaid' AFTER status");
    }
    if (!$hasTable('payments')) {
        $pdo->exec("CREATE TABLE payments (
            id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id    INT UNSIGNED NOT NULL,
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
            KEY idx_pay_status_exp (status, expires_at),
            CONSTRAINT fk_pay_booking FOREIGN KEY (booking_id) REFERENCES bookings (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
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

/** Tóm tắt booking để hiển thị (dùng khi trả lại đơn chờ thanh toán đã tồn tại). */
function payment_booking_summary(PDO $pdo, int $bookingId): array
{
    $s = $pdo->prepare('SELECT b.*, c.name AS court_name FROM bookings b JOIN courts c ON c.id=b.court_id WHERE b.id=?');
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
        'sub_court_name' => $first['sub_court_name'] ?? '',
        'booking_date'   => $first['slot_date'] ?? null,
        'start_time'     => $start,
        'end_time'       => $end,
        'time_range'     => $start . ' - ' . $end,
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
            $s = $pdo->prepare("SELECT id, booking_id FROM payments WHERE id=? AND status='pending' AND expires_at < NOW() FOR UPDATE");
            $s->execute([(int)$paymentId]);
            $p = $s->fetch();
            if ($p) {
                payment_release_booking($pdo, (int)$p['booking_id']);
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
        // Khớp cả GDM000123, GDM 000123, GDM-123, GDM123
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
 * @return array{result:string, payment_id:?int, booking_id:?int, confirmed:bool}
 * @throws Throwable khi lỗi DB tạm thời (caller trả 5xx để SePay retry)
 */
function payment_process_webhook(PDO $pdo, array $payload): array
{
    payment_ensure_schema($pdo);
    $out = ['result' => 'ignored', 'payment_id' => null, 'booking_id' => null, 'confirmed' => false];

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
            return ['result' => 'duplicate', 'payment_id' => $prev['payment_id'] ? (int)$prev['payment_id'] : null, 'booking_id' => null, 'confirmed' => false];
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
        $bookingId = (int)$p['booking_id'];
        $out['payment_id'] = $paymentId;
        $out['booking_id'] = $bookingId;
        $paidTotal = (int)$p['paid_amount'] + $amount;
        $pdo->prepare('UPDATE payments SET paid_amount=? WHERE id=?')->execute([$paidTotal, $paymentId]);

        if (in_array($p['status'], ['paid', 'late_paid', 'refunded'], true)) {
            $mark('overpaid', $paymentId, 'Đơn đã ở trạng thái ' . $p['status'] . ', khoản này cần hoàn lại.');
            $out['result'] = 'overpaid';
        } elseif ($paidTotal < (int)$p['amount']) {
            $mark('underpaid', $paymentId, 'Đã nhận ' . $paidTotal . '/' . (int)$p['amount'] . 'đ.');
            $out['result'] = 'underpaid';
        } elseif ($p['status'] === 'cancelled') {
            // Khách tự huỷ rồi vẫn chuyển → không tự nhận lại, admin hoàn tiền.
            $pdo->prepare("UPDATE payments SET status='late_paid', paid_at=NOW() WHERE id=?")->execute([$paymentId]);
            $mark('late_paid', $paymentId, 'Đơn đã bị khách huỷ trước khi tiền về.');
            $out['result'] = 'late_paid';
        } else {
            // pending (còn hạn hoặc quá hạn nhưng chưa dọn) hoặc expired → thử xác nhận
            if ($p['status'] === 'pending' && $p['is_expired']) {
                payment_release_booking($pdo, $bookingId);
            }
            if (payment_try_confirm_booking($pdo, $bookingId)) {
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

/** Gửi email xác nhận đặt cọc thành công (lỗi chỉ ghi log, không ảnh hưởng thanh toán). */
function payment_send_confirmation_email(PDO $pdo, int $bookingId): void
{
    try {
        require_once __DIR__ . '/auth-mailer.php';
        $summary = payment_booking_summary($pdo, $bookingId);
        if (!$summary || $summary['booking_status'] !== 'confirmed') {
            return;
        }
        $s = $pdo->prepare('SELECT u.name, u.email FROM bookings b JOIN users u ON u.id=b.user_id WHERE b.id=?');
        $s->execute([$bookingId]);
        $user = $s->fetch();
        if (!$user || !filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
            return;
        }
        $e = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $money = static fn($n) => number_format((int)$n, 0, ',', '.') . ' đ';
        $date = $summary['booking_date'] ? date('d/m/Y', strtotime($summary['booking_date'])) : '';
        $historyUrl = rtrim((string)getenv('APP_URL'), '/') . '/history.php';
        $rows = [
            'Mã đặt sân'   => $summary['booking_code'],
            'Sân'          => $summary['court_name'] . ' · ' . $summary['sub_court_name'],
            'Ngày chơi'    => $date,
            'Khung giờ'    => $summary['time_range'],
            'Tổng tiền'    => $money($summary['total_price']),
            'Đã đặt cọc'   => $money($summary['deposit_amount']),
            'Còn lại tại sân' => $money($summary['remain_amount']),
        ];
        $tr = '';
        foreach ($rows as $k => $v) {
            $tr .= '<tr><td style="padding:8px 0;color:#64748b">' . $e($k) . '</td><td style="padding:8px 0;text-align:right;font-weight:700;color:#0f172a">' . $e($v) . '</td></tr>';
        }
        $html = '<div style="font-family:Arial,sans-serif;max-width:520px;margin:auto;padding:24px;border:1px solid #e2e8f0;border-radius:16px">'
            . '<h2 style="color:#0d9488;margin:0 0 8px">Đặt cọc thành công!</h2>'
            . '<p style="color:#334155">Xin chào ' . $e($user['name']) . ', GODMINTON đã nhận được tiền cọc của bạn qua chuyển khoản ngân hàng.</p>'
            . '<table style="width:100%;border-collapse:collapse;font-size:14px">' . $tr . '</table>'
            . '<p style="margin-top:20px"><a href="' . $e($historyUrl) . '" style="background:#0d9488;color:#fff;padding:10px 18px;border-radius:999px;text-decoration:none;font-weight:700">Xem lịch đặt sân</a></p>'
            . '<p style="color:#94a3b8;font-size:12px">Khoản đặt cọc không hoàn lại nếu huỷ trong vòng 2 giờ trước giờ chơi.</p></div>';
        $text = "Đặt cọc thành công!\n";
        foreach ($rows as $k => $v) {
            $text .= "$k: $v\n";
        }
        $mail = god_auth_mailer();
        $mail->addAddress($user['email'], (string)$user['name']);
        $mail->Subject = 'Xác nhận đặt cọc ' . $summary['booking_code'] . ' - GODMINTON';
        $mail->isHTML(true);
        $mail->Body = $html;
        $mail->AltBody = $text;
        $mail->send();
    } catch (Throwable $e) {
        error_log('[payment-email] booking #' . $bookingId . ': ' . $e->getMessage());
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
            if (!empty($res['confirmed']) && !empty($res['booking_id'])) {
                payment_send_confirmation_email($pdo, (int)$res['booking_id']);
            }
        }
    } catch (Throwable $e) {
        error_log('[sepay-api-sync] ' . $e->getMessage());
    }
}
