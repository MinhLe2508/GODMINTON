<?php
require_once __DIR__.'/../../includes/booking-service.php';
try {
    $user = booking_account_user();
    if (!in_array($user['role'], ['admin', 'owner'], true)) {
        booking_account_out(403, null, 'Chỉ chủ sân hoặc quản trị viên được quản lý đơn.');
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        booking_account_out(200, booking_list($user, true));
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
        booking_account_out(405, null, 'Chỉ hỗ trợ GET/PATCH.');
    }
    booking_account_csrf();
    $in = booking_account_input();
    $id = booking_account_id($in['booking_id'] ?? null);
    $target = $in['status'] ?? null;
    if (!in_array($target, ['checked_in', 'completed', 'no_show', 'cancelled'], true)) {
        booking_account_out(422, null, 'Trạng thái cập nhật không hợp lệ.');
    }
    $res = booking_transition($id, $target, $user, true);
    $messages = [
        'checked_in' => 'Đã check-in nhận sân cho khách. Đồng hồ tính giờ chơi bắt đầu!',
        'completed'  => 'Đã xác nhận thu đủ tiền tại quầy và hoàn thành lượt chơi.',
        'no_show'    => 'Đã ghi nhận khách bùng sân và mở lại khung giờ cho khách vãng lai.',
        'cancelled'  => 'Đã hủy đơn đặt sân và giải phóng khung giờ.'
    ];
    $msg = !empty($res['message']) ? $res['message'] : ($messages[$target] ?? 'Đã cập nhật trạng thái đơn.');
    booking_account_out(200, $res, $msg);
} catch (Throwable $e) {
    booking_account_error($e);
}
