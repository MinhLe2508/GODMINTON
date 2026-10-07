<?php
/**
 * includes/auth-check.php
 * Include ở đầu các trang yêu cầu đăng nhập (booking.php, confirm.php, payment.php,
 * history.php, profile.php, admin-*.php). Chặn truy cập nếu chưa có session.
 *
 * Người phụ trách: Huy (đi kèm api/auth/*.php, Tuần 1).
 *
 * Trang bình thường:   <?php require_once __DIR__ . '/includes/auth-check.php'; ?>
 * Trang admin/owner:   <?php $REQUIRED_ROLES = ['admin', 'owner'];
 *                            require_once __DIR__ . '/includes/auth-check.php'; ?>
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    // Đường dẫn tương đối (các trang nằm ở thư mục gốc dự án) -> chạy được dù đặt ở /godminton/ hay /
    header('Location: auth.php?redirect=' . urlencode(basename($_SERVER['REQUEST_URI'])));
    exit;
}

if (!empty($REQUIRED_ROLES) && !in_array($_SESSION['role'] ?? '', $REQUIRED_ROLES, true)) {
    http_response_code(403);
    exit('403 — Bạn không có quyền truy cập trang này.');
}
