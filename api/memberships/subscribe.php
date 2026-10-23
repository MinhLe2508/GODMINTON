<?php
/**
 * api/memberships/subscribe.php
 * API Đăng ký / Gia hạn Gói Hội Viên (Silver 5%, Gold 10%, Platinum 15%).
 * Phương thức: POST
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/membership-service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    booking_account_out(405, null, 'Chỉ chấp nhận phương thức POST.');
}

try {
    // 1. Bắt buộc đăng nhập
    $user = booking_account_user(false);
    $userId = (int)$user['id'];

    // 2. Đọc payload
    $in = booking_account_input();
    $postId = isset($in['post_id']) ? (int)$in['post_id'] : 0;
    $paymentMethod = isset($in['payment_method']) ? trim((string)$in['payment_method']) : 'VietQR';

    if ($postId <= 0) {
        booking_account_out(422, null, 'Vui lòng chọn gói hội viên hợp lệ.');
    }

    $validMethods = ['VietQR', 'MoMo', 'ZaloPay', 'Card', 'Bank'];
    if (!in_array($paymentMethod, $validMethods, true)) {
        $paymentMethod = 'VietQR';
    }

    // 3. Thực hiện đăng ký / gia hạn qua service
    $result = membership_subscribe($pdo, $userId, $postId, $paymentMethod);

    booking_account_out(200, $result, $result['message']);

} catch (MembershipError $e) {
    booking_account_out($e->httpCode, null, $e->getMessage());
} catch (Throwable $e) {
    error_log('[GODMINTON api/memberships/subscribe.php] ' . $e->getMessage());
    booking_account_out(500, null, 'Lỗi hệ thống khi đăng ký gói hội viên: ' . $e->getMessage());
}
