<?php
/**
 * api/payments/simulate.php — CHỈ DÙNG KHI PHÁT TRIỂN.
 * POST {"code":"GDM000123"} → giả lập SePay gửi webhook "đã nhận đủ tiền" cho đơn của chính mình.
 * Đi qua đúng hàm payment_process_webhook() như webhook thật, nên test được toàn bộ luồng không mất tiền.
 *
 * Chỉ hoạt động khi .env có  SEPAY_ALLOW_SIMULATE=1  và  APP_ENV khác production.
 * TUYỆT ĐỐI không bật trên hosting thật (ai cũng có thể tự xác nhận đơn).
 */
require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/payment-service.php';

try {
    if (!payment_config()['simulate']) {
        booking_account_out(404, null, 'Không hỗ trợ.');
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        booking_account_out(405, null, 'Chỉ hỗ trợ POST.');
    }
    $user = booking_account_user();
    session_write_close();

    $in = booking_account_input();
    $code = strtoupper(trim((string)($in['code'] ?? '')));
    payment_ensure_schema($pdo);
    $s = $pdo->prepare('SELECT p.payment_code, p.amount, p.paid_amount, p.booking_id, p.membership_id
                          FROM payments p
                          LEFT JOIN bookings b ON b.id = p.booking_id
                          LEFT JOIN user_memberships um ON um.id = p.membership_id
                         WHERE p.payment_code = ? AND COALESCE(b.user_id, um.user_id) = ?');
    $s->execute([$code, $user['id']]);
    $p = $s->fetch();
    if (!$p) {
        booking_account_out(404, null, 'Không tìm thấy giao dịch.');
    }

    $payload = [
        'id'              => (int)(9000000000000 + random_int(1, 999999999)),
        'gateway'         => 'SIMULATOR',
        'transactionDate' => date('Y-m-d H:i:s'),
        'accountNumber'   => payment_config()['account_number'],
        'subAccount'      => null,
        'code'            => $p['payment_code'],
        'content'         => $p['payment_code'] . (!empty($p['membership_id']) ? ' dang ky goi hoi vien GODMINTON' : ' dat coc san GODMINTON'),
        'transferType'    => 'in',
        'description'     => 'Giao dich gia lap',
        'transferAmount'  => max(0, (int)$p['amount'] - (int)$p['paid_amount']),
        'accumulated'     => 0,
        'referenceCode'   => 'SIM' . time(),
    ];
    $result = payment_process_webhook($pdo, $payload);
    if (!empty($result['confirmed'])) {
        if (!empty($result['booking_id'])) {
            payment_send_confirmation_email($pdo, (int)$result['booking_id']);
        } elseif (!empty($result['membership_id'])) {
            payment_send_membership_confirmation_email($pdo, (int)$result['membership_id']);
        }
    }
    booking_account_out(200, $result, 'Đã giả lập giao dịch: ' . $result['result']);
} catch (Throwable $e) {
    booking_account_error($e);
}
