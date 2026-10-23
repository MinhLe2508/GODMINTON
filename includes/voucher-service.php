<?php
/**
 * includes/voucher-service.php
 * Logic nghiệp vụ xử lý mã giảm giá (Vouchers) dùng chung cho GODMINTON.
 * 
 * Đảm bảo:
 * - Chuẩn hóa mã (chữ in hoa, cắt khoảng trắng).
 * - Sử dụng cùng hàm tính tiền duy nhất voucher_calculate_pricing() để API xem trước và đặt sân khớp 100%.
 * - Định dạng lỗi thống nhất: code, message, meta, http_code.
 */

require_once __DIR__ . '/../config/db.php';

class VoucherError extends RuntimeException {
    public function __construct(
        public int $httpCode,
        public string $errorCode,
        string $message,
        public array $meta = []
    ) {
        parent::__construct($message);
    }
}

/**
 * Định dạng tiền tệ VND rút gọn hoặc đủ
 */
function voucher_format_vnd(int|float $amount): string {
    return number_format((int)round($amount), 0, ',', '.') . 'đ';
}

/**
 * Kiểm tra và ghi nhận giới hạn tần suất nhập sai mã (5 lần / 10 phút)
 */
function voucher_check_rate_limit(PDO $pdo, int $userId): void {
    if ($userId <= 0) return;
    $scope = 'voucher_attempt';
    $key = (string)$userId;
    $hash = hash('sha256', $scope . '|' . $key);
    $seconds = 600; // 10 phút
    $cutoff = date('Y-m-d H:i:s', time() - $seconds);

    // Lấy thông tin hiện tại
    $stmt = $pdo->prepare('SELECT attempts, window_started_at FROM auth_rate_limits WHERE key_hash = ?');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();

    if ($row) {
        $startedAt = strtotime($row['window_started_at']);
        $elapsed = time() - $startedAt;
        if ($elapsed < $seconds && (int)$row['attempts'] >= 5) {
            $remainingMins = max(1, (int)ceil(($seconds - $elapsed) / 60));
            throw new VoucherError(
                429,
                'VOUCHER_RATE_LIMIT',
                "Bạn nhập sai quá nhiều lần. Thử lại sau {$remainingMins} phút.",
                ['retry_after_minutes' => $remainingMins]
            );
        }
    }
}

/**
 * Tăng số lần nhập sai khi mã không tồn tại
 */
function voucher_record_failure(PDO $pdo, int $userId): void {
    if ($userId <= 0) return;
    $scope = 'voucher_attempt';
    $key = (string)$userId;
    $hash = hash('sha256', $scope . '|' . $key);
    $seconds = 600; // 10 phút
    $cutoff = date('Y-m-d H:i:s', time() - $seconds);

    $stmt = $pdo->prepare('
        INSERT INTO auth_rate_limits (key_hash, attempts, window_started_at)
        VALUES (?, 1, NOW())
        ON DUPLICATE KEY UPDATE
            attempts = IF(window_started_at <= ?, 1, attempts + 1),
            window_started_at = IF(window_started_at <= ?, NOW(), window_started_at)
    ');
    $stmt->execute([$hash, $cutoff, $cutoff]);
}

/**
 * Xóa giới hạn tần suất khi nhập đúng mã thành công
 */
function voucher_clear_failure(PDO $pdo, int $userId): void {
    if ($userId <= 0) return;
    $scope = 'voucher_attempt';
    $key = (string)$userId;
    $hash = hash('sha256', $scope . '|' . $key);
    $stmt = $pdo->prepare('DELETE FROM auth_rate_limits WHERE key_hash = ?');
    $stmt->execute([$hash]);
}

/**
 * Hàm tính tiền duy nhất của hệ thống Voucher & Gói hội viên
 * Áp dụng cùng 1 công thức cho cả Preview và Confirm Booking.
 */
function voucher_calculate_pricing(int $courtAmount, ?array $voucher = null, ?array $membership = null): array {
    $courtAmount = max(0, $courtAmount);

    // 1. Tính chiết khấu gói hội viên (nếu có)
    $memberPercent = 0;
    $membershipDiscount = 0;
    $membershipId = null;
    if ($membership && !empty($membership['discount_percent'])) {
        $memberPercent = (int)$membership['discount_percent'];
        $membershipDiscount = (int)round(($courtAmount * $memberPercent) / 100);
        $membershipId = isset($membership['id']) ? (int)$membership['id'] : null;
    }

    // 2. Tính chiết khấu voucher (nếu có)
    $voucherDiscount = 0;
    $voucherPercent = 0;
    $voucherId = null;
    $voucherCode = null;
    $voucherFundedBy = 'platform';

    if ($voucher) {
        $voucherId = (int)$voucher['id'];
        $voucherCode = strtoupper(trim($voucher['code']));
        $voucherPercent = (int)($voucher['discount_percent'] ?? 0);
        $rawDiscount = (int)round(($courtAmount * $voucherPercent) / 100);

        $maxDiscount = isset($voucher['max_discount']) && $voucher['max_discount'] !== null
            ? (int)$voucher['max_discount']
            : null;

        if ($maxDiscount !== null && $maxDiscount > 0) {
            $voucherDiscount = min($rawDiscount, $maxDiscount);
        } else {
            $voucherDiscount = $rawDiscount;
        }
        $voucherFundedBy = $voucher['funded_by'] ?? 'platform';
    }

    // Tổng tiền giảm không vượt quá tiền sân gốc
    $totalDiscount = min($courtAmount, max(0, $membershipDiscount + $voucherDiscount));
    $afterDiscount = max(0, $courtAmount - $totalDiscount);

    // Phí sàn = 5% của tiền sân sau giảm
    $platformFee = (int)round($afterDiscount * 0.05);

    // Tổng khách trả = tiền sau giảm + phí sàn
    $totalAmount = $afterDiscount + $platformFee;

    // Cọc = 50% tổng khách trả
    $depositAmount = (int)round($totalAmount * 0.5);

    // Tiền chủ sân nhận
    if ($voucher && $voucherFundedBy === 'owner') {
        // Chủ sân chịu phần giảm của voucher, nền tảng chịu phần giảm hội viên
        $ownerAmount = max(0, $courtAmount - $voucherDiscount);
    } else {
        // Nền tảng chịu toàn bộ chiết khấu, chủ sân nhận đủ tiền sân gốc
        $ownerAmount = $courtAmount;
    }

    $fundedBy = ($totalDiscount > 0) ? ($voucher && $voucherFundedBy === 'owner' ? 'owner' : 'platform') : null;

    return [
        'voucher_id'                  => $voucherId,
        'voucher_code'                => $voucherCode,
        'user_membership_id'          => $membershipId,
        'membership_discount_amount'  => $membershipDiscount,
        'membership_discount_percent' => $memberPercent,
        'court_amount'                => $courtAmount,
        'discount_amount'             => $totalDiscount,
        'discount_funded_by'          => $fundedBy,
        'platform_fee'                => $platformFee,
        'total_amount'                => $totalAmount,
        'deposit_amount'              => $depositAmount,
        'owner_amount'                => $ownerAmount,
        'discount_percent'            => $voucherPercent,
    ];
}

/**
 * Kiểm tra tính hợp lệ của voucher và trả về thông tin voucher
 * Ném VoucherError với httpCode và mã lỗi tương ứng nếu không hợp lệ.
 */
function voucher_validate_and_get(
    PDO $pdo,
    string $code,
    int $userId,
    int $courtId,
    int $courtAmount,
    bool $forUpdate = false
): array {
    $normalizedCode = strtoupper(trim($code));
    if ($normalizedCode === '') {
        throw new VoucherError(422, 'EMPTY_CODE', 'Vui lòng nhập mã giảm giá.');
    }

    // 1. Kiểm tra rate limit
    voucher_check_rate_limit($pdo, $userId);

    // 2. Tìm mã trong database
    $sql = 'SELECT * FROM vouchers WHERE UPPER(code) = ?';
    if ($forUpdate) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$normalizedCode]);
    $voucher = $stmt->fetch();

    if (!$voucher) {
        voucher_record_failure($pdo, $userId);
        throw new VoucherError(422, 'VOUCHER_NOT_FOUND', 'Mã giảm giá không đúng. Vui lòng kiểm tra lại.');
    }

    // Nếu tìm thấy mã hợp lệ, xóa bộ đếm nhập sai
    voucher_clear_failure($pdo, $userId);

    // 3. Trạng thái bật/tắt
    if ($voucher['status'] !== 'active') {
        throw new VoucherError(422, 'VOUCHER_DISABLED', 'Mã này hiện không còn được áp dụng.');
    }

    // 4. Thời gian hiệu lực
    $now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    $nowStr = $now->format('Y-m-d H:i:s');
    $startAt = new DateTime($voucher['start_at'], new DateTimeZone('Asia/Ho_Chi_Minh'));
    $endAt = new DateTime($voucher['end_at'], new DateTimeZone('Asia/Ho_Chi_Minh'));

    if ($nowStr < $voucher['start_at']) {
        $meta = ['start_at' => $startAt->format('c')];
        throw new VoucherError(
            422,
            'VOUCHER_NOT_STARTED',
            'Mã chưa bắt đầu, dùng được từ ' . $startAt->format('d/m/Y H:i') . '.',
            $meta
        );
    }

    if ($nowStr > $voucher['end_at']) {
        $meta = ['end_at' => $endAt->format('c')];
        throw new VoucherError(
            422,
            'VOUCHER_EXPIRED',
            'Mã đã hết hạn vào ' . $endAt->format('d/m/Y H:i') . '.',
            $meta
        );
    }

    // 5. Kiểm tra phạm vi áp dụng theo sân và chủ sân
    $courtStmt = $pdo->prepare('SELECT id, owner_id FROM courts WHERE id = ?');
    $courtStmt->execute([$courtId]);
    $court = $courtStmt->fetch();
    if (!$court) {
        throw new VoucherError(422, 'VOUCHER_WRONG_COURT', 'Mã này không áp dụng cho sân này.');
    }

    // Nếu voucher giới hạn theo 1 sân cụ thể
    if ($voucher['court_id'] !== null && (int)$voucher['court_id'] !== $courtId) {
        throw new VoucherError(422, 'VOUCHER_WRONG_COURT', 'Mã này không áp dụng cho sân này.');
    }

    // Nếu voucher do chủ sân tạo cho tất cả sân của họ
    if ($voucher['owner_id'] !== null && (int)$voucher['owner_id'] !== (int)$court['owner_id']) {
        throw new VoucherError(422, 'VOUCHER_WRONG_COURT', 'Mã này không áp dụng cho sân này.');
    }

    // 6. Kiểm tra đơn hàng tối thiểu
    $minOrder = (int)$voucher['min_order'];
    if ($minOrder > 0 && $courtAmount < $minOrder) {
        throw new VoucherError(
            422,
            'VOUCHER_MIN_ORDER',
            'Đơn cần từ ' . voucher_format_vnd($minOrder) . ' để dùng mã này.',
            ['min_order' => $minOrder]
        );
    }

    // 7. Kiểm tra tổng lượt sử dụng toàn hệ thống
    if ($voucher['usage_limit'] !== null) {
        if ((int)$voucher['used_count'] >= (int)$voucher['usage_limit']) {
            throw new VoucherError(422, 'VOUCHER_USED_UP', 'Mã đã hết lượt sử dụng.');
        }
    }

    // 8. Kiểm tra giới hạn số lần dùng trên mỗi tài khoản khách
    if ($userId > 0) {
        $perUserLimit = (int)($voucher['per_user_limit'] ?? 1);
        $usageCountStmt = $pdo->prepare('
            SELECT COUNT(*) FROM voucher_usages
            WHERE voucher_id = ? AND user_id = ? AND released_at IS NULL
        ');
        $usageCountStmt->execute([(int)$voucher['id'], $userId]);
        $userUsages = (int)$usageCountStmt->fetchColumn();

        if ($userUsages >= $perUserLimit) {
            throw new VoucherError(422, 'VOUCHER_ALREADY_USED', 'Bạn đã sử dụng mã này rồi.');
        }

        // 9. Kiểm tra mã chỉ dành cho đơn đầu tiên
        if (!empty($voucher['first_booking_only'])) {
            $priorBookingStmt = $pdo->prepare("
                SELECT 1 FROM bookings
                WHERE user_id = ? AND status IN ('confirmed', 'completed')
                LIMIT 1
            ");
            $priorBookingStmt->execute([$userId]);
            if ($priorBookingStmt->fetch()) {
                throw new VoucherError(422, 'VOUCHER_FIRST_ONLY', 'Mã chỉ dành cho đơn đặt sân đầu tiên.');
            }
        }
    }

    return $voucher;
}
