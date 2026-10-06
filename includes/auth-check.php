<?php
/**
 * includes/auth-check.php
 * Include ở đầu các trang yêu cầu đăng nhập (booking.php, confirm.php, payment.php,
 * history.php, profile.php, admin-*.php). Chặn truy cập nếu chưa có session.
 *
 * Người phụ trách: Huy (đi kèm api/auth/*.php, Tuần 1).
 */
session_start();

if (empty($_SESSION['user_id'])) {
    header('Location: /auth.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// TODO (admin-*.php): sau khi có $_SESSION['role'], thêm kiểm tra:
// if (!in_array($_SESSION['role'], ['admin', 'owner'])) { http_response_code(403); exit; }
