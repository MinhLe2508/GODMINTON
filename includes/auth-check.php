<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth-session.php';
/**
 * includes/auth-check.php
 * Include ở đầu các trang yêu cầu đăng nhập (booking.php, confirm.php, payment.php,
 * history.php, profile.php, admin-*.php). Tự động khôi phục từ cookie Ghi nhớ đăng nhập
 * hoặc chặn truy cập nếu chưa có phiên hợp lệ.
 */
god_auth_start();
god_auth_user($pdo);

// Thư mục gốc web của dự án (vd "/godminton"), tránh hard-code "/auth.php"
$base = preg_replace('~/pages$~i', '', rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'));

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

if (in_array(basename($_SERVER['SCRIPT_NAME']), ['admin-users.php', 'admin-owner-requests.php'], true) && ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('403 — Chỉ quản trị viên được truy cập trang này.');
}
