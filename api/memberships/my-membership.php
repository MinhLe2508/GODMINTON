<?php
/**
 * api/memberships/my-membership.php
 * Trả về thông tin gói hội viên đang kích hoạt và lịch sử của người dùng hiện tại.
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/membership-service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    booking_account_out(405, null, 'Chỉ chấp nhận phương thức GET.');
}

try {
    $user = booking_account_user(true);
    if (!$user) {
        booking_account_out(200, [
            'logged_in' => false,
            'active_membership' => null,
            'history' => []
        ], 'Chưa đăng nhập.');
    }

    $active = membership_get_active_for_user($pdo, (int)$user['id']);
    $pending = membership_get_pending_for_user($pdo, (int)$user['id']);
    $history = membership_get_history($pdo, (int)$user['id']);

    booking_account_out(200, [
        'logged_in' => true,
        'user' => [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email']
        ],
        'active_membership' => $active,
        'pending_membership' => $pending,
        'history' => $history
    ], 'Lấy thông tin gói hội viên thành công.');

} catch (Throwable $e) {
    error_log('[GODMINTON my-membership.php] ' . $e->getMessage());
    booking_account_out(500, null, 'Lỗi máy chủ khi lấy thông tin gói hội viên.');
}
