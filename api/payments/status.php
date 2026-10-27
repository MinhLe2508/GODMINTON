<?php
/**
 * api/payments/status.php
 * GET ?code=GDM000123 → trạng thái thanh toán của đơn (chỉ chủ đơn được xem).
 * Frontend payment.js gọi mỗi 3 giây trong lúc chờ ngân hàng báo có tiền.
 */
require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/payment-service.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        booking_account_out(405, null, 'Chỉ hỗ trợ GET.');
    }
    $user = booking_account_user();
    session_write_close();

    $code = strtoupper(trim((string)($_GET['code'] ?? '')));
    if (!preg_match('/^[A-Z]{1,10}\d{6,9}$/', $code)) {
        booking_account_out(422, null, 'Mã thanh toán không hợp lệ.');
    }
    if (!payment_tables_ready($pdo)) {
        booking_account_out(404, null, 'Không tìm thấy giao dịch.');
    }

    payment_expire_overdue($pdo, 20);

    $s = $pdo->prepare('SELECT p.*, TIMESTAMPDIFF(SECOND, NOW(), p.expires_at) AS expires_in_seconds,
                               b.status AS booking_status, b.booking_code
                          FROM payments p JOIN bookings b ON b.id=p.booking_id
                         WHERE p.payment_code=? AND b.user_id=?');
    $s->execute([$code, $user['id']]);
    $p = $s->fetch();
    if (!$p) {
        booking_account_out(404, null, 'Không tìm thấy giao dịch.');
    }

    // Nếu đơn vẫn đang chờ thanh toán và có cấu hình SEPAY_API_TOKEN, chủ động đồng bộ từ SePay API
    if ($p['status'] === 'pending') {
        payment_sync_from_sepay_api($pdo);
        $s->execute([$code, $user['id']]);
        $p = $s->fetch() ?: $p;
    }

    $data = payment_public_data($p);
    $data['booking_id'] = (int)$p['booking_id'];
    $data['booking_code'] = $p['booking_code'];
    $data['booking_status'] = $p['booking_status'];
    booking_account_out(200, $data);
} catch (Throwable $e) {
    booking_account_error($e);
}
