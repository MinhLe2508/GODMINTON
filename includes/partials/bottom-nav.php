<?php
/**
 * includes/partials/bottom-nav.php
 * Thanh điều hướng đáy màn hình (Bottom Navigation Bar) dùng chung cho Mobile.
 * Đã gỡ bỏ: tab Khám phá (news) và Liên hệ (contact) theo yêu cầu Task #6.
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 */
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
?>
<!-- Bottom Navigation (Server-rendered markup) -->
<nav id="godmintonBottomNav" aria-label="Điều hướng chính">
    <a href="index.php" class="bnav-item <?= in_array($currentPage, ['index', '']) ? 'active' : '' ?>">
        <i class="fa-solid fa-house bnav-icon"></i>
        <span class="bnav-label">Trang chủ</span>
    </a>

    <a href="courts.php" class="bnav-item bnav-center <?= in_array($currentPage, ['courts', 'court-detail', 'booking', 'confirm', 'payment']) ? 'active' : '' ?>">
        <div class="bnav-center-bubble">
            <i class="fa-solid fa-layer-group bnav-icon"></i>
        </div>
        <span class="bnav-label">Đặt sân</span>
    </a>

    <a href="history.php" class="bnav-item <?= ($currentPage === 'history') ? 'active' : '' ?>">
        <i class="fa-solid fa-calendar-check bnav-icon"></i>
        <span class="bnav-label">Lịch sử</span>
    </a>

    <a href="auth.php" class="bnav-item <?= in_array($currentPage, ['auth', 'profile']) ? 'active' : '' ?>">
        <i class="fa-solid fa-circle-user bnav-icon"></i>
        <span class="bnav-label">Tài khoản</span>
    </a>
</nav>
