<?php
/**
 * api/vouchers/my-vouchers.php
 * GET danh sách mã giảm giá khả dụng dành cho khách hàng hiện tại.
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/voucher-service.php';

try {
    $user = booking_account_user(false);
    $userId = (int)$user['id'];

    // 1. Kiểm tra khách đã có đơn đặt sân nào chưa (để xét first_booking_only)
    $hasBookingStmt = $pdo->prepare("
        SELECT 1 FROM bookings
        WHERE user_id = ? AND status IN ('confirmed', 'completed')
        LIMIT 1
    ");
    $hasBookingStmt->execute([$userId]);
    $hasPriorBookings = (bool)$hasBookingStmt->fetch();

    // 2. Lấy TẤT CẢ các voucher mà người dùng ĐÃ BẤM THU THẬP TỪ BÀI VIẾT (user_vouchers)
    $sql = "
        SELECT v.*, c.name AS court_name, uv.collected_at, uv.post_id
        FROM user_vouchers uv
        JOIN vouchers v ON v.id = uv.voucher_id
        LEFT JOIN courts c ON c.id = v.court_id
        WHERE uv.user_id = ?
        ORDER BY uv.collected_at DESC, v.id DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $vouchers = $stmt->fetchAll();

    // 3. Đếm số lần user đã sử dụng từng voucher
    $usagesStmt = $pdo->prepare("
        SELECT voucher_id, COUNT(*) AS used_by_me
        FROM voucher_usages
        WHERE user_id = ? AND released_at IS NULL
        GROUP BY voucher_id
    ");
    $usagesStmt->execute([$userId]);
    $userUsages = $usagesStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

    $nowStr = date('Y-m-d H:i:s');
    $list = [];
    foreach ($vouchers as $v) {
        $vId = (int)$v['id'];
        $usedByMe = (int)($userUsages[$vId] ?? 0);
        $perUserLimit = (int)($v['per_user_limit'] ?? 1);

        $isExpired = ($nowStr > $v['end_at']);
        $notStarted = ($nowStr < $v['start_at']);
        $isInactive = ($v['status'] !== 'active');
        $isUsedUp = ($v['usage_limit'] !== null && (int)$v['used_count'] >= (int)$v['usage_limit']);
        $isUserLimitReached = ($usedByMe >= $perUserLimit);
        $isFirstBookingOnlyConflict = (!empty($v['first_booking_only']) && $hasPriorBookings);

        $isUsable = !$isExpired && !$notStarted && !$isInactive && !$isUsedUp && !$isUserLimitReached && !$isFirstBookingOnlyConflict;

        $unusableReason = null;
        if ($isInactive) $unusableReason = 'Tạm ngưng';
        elseif ($isExpired) $unusableReason = 'Đã hết hạn';
        elseif ($notStarted) $unusableReason = 'Chưa bắt đầu';
        elseif ($isUsedUp) $unusableReason = 'Hết lượt toàn sàn';
        elseif ($isUserLimitReached) $unusableReason = 'Đã sử dụng';
        elseif ($isFirstBookingOnlyConflict) $unusableReason = 'Chỉ đơn đầu tiên';

        $list[] = [
            'id'                 => $vId,
            'code'               => strtoupper($v['code']),
            'discount_percent'   => (int)$v['discount_percent'],
            'max_discount'       => $v['max_discount'] !== null ? (int)$v['max_discount'] : null,
            'max_discount_text'  => $v['max_discount'] !== null ? voucher_format_vnd($v['max_discount']) : null,
            'min_order'          => (int)$v['min_order'],
            'min_order_text'     => $v['min_order'] > 0 ? voucher_format_vnd($v['min_order']) : '0đ',
            'start_at'           => $v['start_at'],
            'end_at'             => $v['end_at'],
            'court_name'         => $v['court_name'] ?: 'Áp dụng cho mọi sân',
            'first_booking_only' => (bool)$v['first_booking_only'],
            'funded_by'          => $v['funded_by'],
            'used_by_me'         => $usedByMe,
            'per_user_limit'     => $perUserLimit,
            'is_usable'          => $isUsable,
            'unusable_reason'    => $unusableReason,
            'collected_at'       => $v['collected_at'] ?? null,
        ];
    }

    booking_account_out(200, $list, 'Lấy danh sách mã giảm giá thành công.');

} catch (Throwable $e) {
    booking_account_error($e);
}
