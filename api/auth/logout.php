<?php
/**
 * api/auth/logout.php
 * Huỷ session đăng nhập.
 */
header('Content-Type: application/json; charset=utf-8');

session_start();
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
