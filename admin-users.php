<?php
require_once __DIR__.'/includes/auth-check.php';
$workflowUser=god_auth_user($pdo);
header('Cache-Control: no-store');
?>
<!doctype html><html lang="vi" data-bs-theme="light"><head><title>Quản lý tài khoản | GODMINTON</title><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css?v=<?=filemtime(__DIR__.'/assets/css/style.css')?>">
<link rel="stylesheet" href="assets/css/admin_style.css?v=<?=filemtime(__DIR__.'/assets/css/admin_style.css')?>">
<link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?=filemtime(__DIR__.'/assets/css/swal-godminton.css')?>">
<link rel="stylesheet" href="assets/css/owner-account-workflow.css?v=<?=filemtime(__DIR__.'/assets/css/owner-account-workflow.css')?>">
<script>window.__GODMINTON_USER__=<?=json_encode($workflowUser,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?>;</script></head><body class="admin-body" data-workflow-page="users">
<div class="admin-shell"><aside class="admin-sidebar" id="adminSidebar"><div class="admin-sidebar-brand"><img src="assets/img/logo.svg" alt="GODMINTON"><div><strong class="text-white">GODMINTON</strong><div class="brand-sub">Kênh quản trị</div></div></div><nav class="admin-nav"><div class="admin-nav-label">Điều hướng</div><div id="adminSidebarNav"></div></nav><div class="admin-sidebar-footer"><div id="adminUserBox"></div><a class="admin-back-link" href="index.php">← Quay lại trang khách</a></div></aside><div class="admin-overlay" id="adminOverlay"></div>
<div class="admin-main"><header class="admin-topbar"><button class="btn btn-outline-secondary admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Mở điều hướng"><i class="fa-solid fa-bars"></i></button><div class="admin-topbar-title flex-grow-1"><div id="adminBreadcrumb" class="admin-breadcrumb">Quản trị</div><h1 id="adminPageTitle">Quản lý tài khoản</h1></div><button class="btn btn-outline-secondary rounded-circle" id="themeToggle" aria-label="Đổi giao diện"><i class="fa-solid fa-moon"></i></button></header><main class="admin-content">
<section class="wf-card mb-4"><div class="wf-heading"><div><span class="wf-eyebrow">QUẢN TRỊ HỆ THỐNG</span><h2>Tài khoản GODMINTON</h2><p>Quản lý trạng thái tài khoản, theo dõi hồ sơ chủ sân và lịch sử xử lý.</p></div><a class="btn btn-teal rounded-pill" href="admin-owner-requests.php">Yêu cầu chủ sân →</a></div>
<form id="usersFilters" class="wf-filters"><label>Tìm tài khoản<input name="search" class="form-control" placeholder="Tên, email, số điện thoại"></label><label>Vai trò<select class="form-select" name="role"><option value="">Tất cả</option><option value="customer">Khách hàng</option><option value="owner">Chủ sân</option><option value="admin">Admin</option></select></label><label>Trạng thái<select class="form-select" name="state"><option value="">Tất cả</option><option value="active">Hoạt động</option><option value="locked">Bị khóa</option><option value="deleted">Đã xóa</option></select></label><label>Email<select name="verified" class="form-select"><option value="">Tất cả</option><option value="1">Đã xác minh</option><option value="0">Chưa xác minh</option></select></label><button class="btn btn-teal" type="submit">Tìm kiếm</button><button class="btn btn-outline-secondary" type="reset">Đặt lại</button></form></section>
<section class="wf-card"><div id="usersResult" aria-live="polite">Đang tải tài khoản…</div><div id="usersPager" class="wf-pager"></div></section>
<div id="workflowDetail" class="wf-detail" hidden tabindex="-1"></div>
</main></div></div><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/swal-godminton.js?v=<?=filemtime(__DIR__.'/assets/js/swal-godminton.js')?>"></script>
<script src="assets/js/booking-account-client.js?v=<?=filemtime(__DIR__.'/assets/js/booking-account-client.js')?>"></script>
<script src="assets/js/app.js?v=<?=filemtime(__DIR__.'/assets/js/app.js')?>"></script>
<script src="assets/js/admin.js?v=<?=filemtime(__DIR__.'/assets/js/admin.js')?>"></script>
<script src="assets/js/owner-account-ui.js?v=<?=filemtime(__DIR__.'/assets/js/owner-account-ui.js')?>"></script></body></html>
