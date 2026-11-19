<?php

require_once __DIR__ . '/../../includes/auth-security.php';
try {
    $in = god_auth_input();
    $email = god_auth_email($in);
    $password = god_auth_text($in, 'password');
    $remember = !empty($in['remember_me']);

    $ipAllowed = god_auth_limit($pdo, 'login-ip', god_auth_ip(), 40, 900);
    $accountAllowed = god_auth_limit($pdo, 'login-email', $email, 10, 900);
    if (!$ipAllowed || !$accountAllowed) {
        header('Retry-After: 900');
        god_auth_out(429, 'Quá nhiều lần thử đăng nhập. Vui lòng thử lại sau 15 phút.');
    }

    $s = $pdo->prepare('SELECT * FROM users WHERE email = ?');
    $s->execute([$email]);
    $user = $s->fetch();
    $dummy = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi';

    if (!password_verify($password, $user['password_hash'] ?? $dummy) || !$user) {
        god_auth_out(401, 'Email hoặc mật khẩu không đúng.');
    }

    if ($user['deleted_at'] !== null || $user['account_status'] !== 'active') {
        god_auth_out(403, 'Tài khoản không khả dụng. Vui lòng liên hệ quản trị viên.');
    }

    if (!$user['is_verified']) {
        god_auth_out(403, 'Vui lòng xác minh email trước khi đăng nhập. Bạn có thể yêu cầu gửi lại email xác minh.', ['email' => $email], 'EMAIL_NOT_VERIFIED');
    }

    god_auth_login($user, $pdo, $remember);
    god_auth_clear_saved_credentials();
    $pdo->prepare('DELETE FROM auth_rate_limits WHERE key_hash = ?')->execute([hash('sha256', 'login-email|' . $email)]);
    god_auth_out(200, 'Đăng nhập thành công.', [
        'user' => god_auth_user($pdo),
        'csrf_token' => $_SESSION['csrf_token'],
        'remember_me' => $remember,
    ]);
} catch (Throwable $e) {
    god_auth_failure($e);
}
