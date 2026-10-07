<?php
/**
 * includes/partials/admin-sidebar.php
 * Sidebar khu vực Quản trị / Kênh Chủ Sân của GODMINTON.
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 */
$adminActive = $adminActive ?? 'dashboard';
$assetsPath = $assetsPath ?? 'assets';
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="admin-sidebar-brand">
        <img src="<?= htmlspecialchars($assetsPath) ?>/img/logo.svg" alt="GODMINTON">
        <div>
            <div class="fw-bold text-white" style="font-size:.9rem;">GODMINTON</div>
            <div class="brand-sub">Kênh Quản Trị</div>
        </div>
    </div>
    <nav class="admin-nav">
        <div class="admin-nav-label">Điều hướng</div>
        <div id="adminSidebarNav">
            <a class="admin-nav-link <?= ($adminActive === 'dashboard') ? 'active' : '' ?>" href="admin-dashboard.php">
                <i class="fa-solid fa-gauge-high"></i> Tổng quan
            </a>
            <a class="admin-nav-link <?= ($adminActive === 'courts') ? 'active' : '' ?>" href="admin-courts.php">
                <i class="fa-solid fa-building-shield"></i> Quản lý sân
            </a>
            <a class="admin-nav-link <?= ($adminActive === 'slots') ? 'active' : '' ?>" href="admin-slots.php">
                <i class="fa-solid fa-clock"></i> Quản lý khung giờ
            </a>
            <a class="admin-nav-link <?= ($adminActive === 'bookings') ? 'active' : '' ?>" href="admin-bookings.php">
                <i class="fa-solid fa-clipboard-list"></i> Quản lý đơn đặt
            </a>
        </div>
    </nav>
    <div class="admin-sidebar-footer">
        <div id="adminUserBox" class="mb-2"></div>
        <a href="index.php" class="admin-back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại trang khách</a>
    </div>
</aside>
<div class="admin-overlay" id="adminOverlay"></div>
