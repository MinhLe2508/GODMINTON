<?php
/**
 * api/vouchers/collect.php
 * Khách hàng bấm nhận mã giảm giá từ bài viết để lưu vào kho cá nhân.
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/voucher-service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        booking_account_out(405, null, 'Phương thức không được hỗ trợ.');
    }

    // 1. Yêu cầu đăng nhập tài khoản
    $user = booking_account_user(true);
    $userId = (int)$user['id'];

    $input = booking_account_input();
    $voucherId = (int)($input['voucher_id'] ?? 0);
    $postId = !empty($input['post_id']) ? (int)$input['post_id'] : null;

    if ($voucherId <= 0 && $postId > 0) {
        // Tìm voucher_id từ post_id
        $postStmt = $pdo->prepare('SELECT voucher_id FROM posts WHERE id = ?');
        $postStmt->execute([$postId]);
        $voucherId = (int)$postStmt->fetchColumn();
    }

    if ($voucherId <= 0) {
        booking_account_out(422, null, 'Mã giảm giá không hợp lệ hoặc bài viết không đính kèm mã.');
    }

    // 2. Kiểm tra voucher có tồn tại và đang active không
    $stmt = $pdo->prepare('
        SELECT v.*, c.name AS court_name
        FROM vouchers v
        LEFT JOIN courts c ON c.id = v.court_id
        WHERE v.id = ?
    ');
    $stmt->execute([$voucherId]);
    $voucher = $stmt->fetch();

    if (!$voucher) {
        booking_account_out(404, null, 'Mã giảm giá không tồn tại.');
    }

    if ($voucher['status'] !== 'active') {
        booking_account_out(422, null, 'Mã giảm giá này hiện đã ngừng kích hoạt.');
    }

    $now = date('Y-m-d H:i:s');
    if ($now < $voucher['start_at']) {
        booking_account_out(422, null, 'Chương trình ưu đãi này chưa bắt đầu.');
    }
    if ($now > $voucher['end_at']) {
        booking_account_out(422, null, 'Chương trình ưu đãi này đã hết hạn.');
    }

    if ($voucher['usage_limit'] !== null && (int)$voucher['used_count'] >= (int)$voucher['usage_limit']) {
        booking_account_out(422, null, 'Mã giảm giá đã hết lượt phát hành.');
    }

    // 3. Kiểm tra xem người dùng đã nhận mã này vào kho chưa
    $checkStmt = $pdo->prepare('SELECT id, collected_at FROM user_vouchers WHERE user_id = ? AND voucher_id = ?');
    $checkStmt->execute([$userId, $voucherId]);
    $existing = $checkStmt->fetch();

    if ($existing) {
        booking_account_out(409, [
            'voucher_id'   => $voucherId,
            'voucher_code' => $voucher['code'],
            'collected_at' => $existing['collected_at']
        ], 'Bạn đã lưu mã này vào kho rồi.');
    }

    // 4. Lưu mã vào bảng user_vouchers
    $insertStmt = $pdo->prepare('
        INSERT INTO user_vouchers (user_id, voucher_id, post_id, collected_at)
        VALUES (?, ?, ?, NOW())
    ');
    $insertStmt->execute([$userId, $voucherId, $postId]);

    booking_account_out(200, [
        'voucher_id'       => $voucherId,
        'voucher_code'     => $voucher['code'],
        'discount_percent' => (int)$voucher['discount_percent'],
        'max_discount'     => (int)($voucher['max_discount'] ?? 0),
        'min_order'        => (int)($voucher['min_order'] ?? 0),
        'end_at'           => $voucher['end_at'],
        'court_name'       => $voucher['court_name'] ?? 'Toàn hệ thống'
    ], "Đã nhận mã \"{$voucher['code']}\" thành công! Mã đã được lưu vào Kho Mã Giảm Giá của bạn.");

} catch (BookingAccountError $e) {
    booking_account_out($e->getCode() ?: 400, null, $e->getMessage());
} catch (Throwable $e) {
    error_log('[GODMINTON api/vouchers/collect.php] ' . $e->getMessage());
    booking_account_out(500, null, 'Có lỗi xảy ra khi nhận mã giảm giá. Vui lòng thử lại sau.');
}
