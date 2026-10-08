<?php
/**
 * includes/auth-check.php
 * Include ở đầu các trang yêu cầu đăng nhập (booking.php, confirm.php, payment.php,
 * history.php, profile.php, admin-*.php). Chặn truy cập nếu chưa có session.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// Thư mục gốc web của dự án (vd "/godminton"), tránh hard-code "/auth.php"
$base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

if (empty($_SESSION['user_id'])) {
    header('Location: ' . $base . '/auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Các trang admin-*.php chỉ cho admin / owner
if (strpos(basename($_SERVER['SCRIPT_NAME']), 'admin-') === 0
    && !in_array($_SESSION['role'] ?? '', ['admin', 'owner'], true)) {
    http_response_code(403);
    exit('403 — Bạn không có quyền truy cập trang này.');
}
