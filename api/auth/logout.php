<?php
/**
 * api/auth/logout.php
 * Huỷ session đăng nhập.
 *
 * POST (hoặc GET cho tiện gọi từ link). Luôn trả success dù chưa đăng nhập.
 *
 * Người phụ trách: Huy (Tuần 1)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/api-helpers.php';
api_start();

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

api_ok(null, 'Đã đăng xuất.');
