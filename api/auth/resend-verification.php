<?php

require_once __DIR__ . '/../../includes/auth-security.php';
try {
    $in = god_auth_input();
    $email = god_auth_email($in);
    $message = 'Nếu email có tài khoản chưa xác minh, bạn sẽ nhận được liên kết trong hộp thư đến hoặc thư rác. Nếu đã yêu cầu nhiều lần, vui lòng chờ khoảng một giờ rồi thử lại.';
    // Verification and password-reset requests use separate buckets so one cannot silently block the other.
    $ipAllowed = god_auth_limit($pdo, 'verify-mail-ip', god_auth_ip(), 20, 3600);
    $emailAllowed = god_auth_limit($pdo, 'verify-mail-email', $email, 5, 3600);
    if (!$ipAllowed || !$emailAllowed)
        god_auth_out(200, $message);
    $mail = god_auth_mailer();
    $pdo->beginTransaction();
    $s = $pdo->prepare('SELECT id,name,email,is_verified,account_status,deleted_at FROM users WHERE email=? FOR UPDATE');
    $s->execute([$email]);
    $user = $s->fetch();
    $purpose = 'verify';
    if ($user && $user['account_status'] === 'active' && $user['deleted_at'] === null && ($purpose === 'reset' || !$user['is_verified'])) {
        $token = god_auth_token($pdo, (int) $user['id'], $purpose);
        god_auth_send($mail, $user, $purpose, $token);
    }

    $pdo->commit();
    god_auth_out(200, $message);
} catch (Throwable $e) {
    god_auth_failure($e);
}
