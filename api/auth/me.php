<?php

require_once __DIR__ . '/../../includes/auth-security.php';
try {
    $user = god_auth_user($pdo);
    if (!$user)
        god_auth_out(401, 'Chưa đăng nhập.', ['user' => null]);
    god_auth_out(200, '', ['user' => $user, 'csrf_token' => $_SESSION['csrf_token']]);
} catch (Throwable $e) {
    god_auth_failure($e);
}
