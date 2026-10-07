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
<!-- Bottom Navigation (Server-rendered markup khớp chuẩn 5 tabs Image 2) -->
<nav id="godmintonBottomNav" aria-label="Điều hướng chính">
    <a href="index.php" class="bnav-item <?= in_array($currentPage, ['index', '']) ? 'active' : '' ?>" aria-label="Trang chủ" data-bnav-id="home">
        <i class="fa-solid fa-house" aria-hidden="true"></i>
        <span>Trang chủ</span>
    </a>

    <a href="profile.php" class="bnav-item <?= in_array($currentPage, ['profile', 'auth']) ? 'active' : '' ?>" aria-label="Cá nhân" data-bnav-id="profile">
        <i class="fa-solid fa-circle-user" aria-hidden="true"></i>
        <span>Cá nhân</span>
    </a>

    <a href="courts.php" class="bnav-item bnav-center <?= in_array($currentPage, ['courts', 'court-detail', 'booking', 'confirm', 'payment']) ? 'active' : '' ?>" aria-label="Đặt sân" data-bnav-id="courts">
        <div class="bnav-center-fab" aria-hidden="true">
            <i class="fa-solid fa-layer-group"></i>
        </div>
        <span>Đặt sân</span>
    </a>

    <a href="news.php" class="bnav-item <?= ($currentPage === 'news') ? 'active' : '' ?>" aria-label="Khám phá" data-bnav-id="news">
        <i class="fa-solid fa-compass" aria-hidden="true"></i>
        <span>Khám phá</span>
    </a>

    <a href="contact.php" class="bnav-item <?= ($currentPage === 'contact') ? 'active' : '' ?>" aria-label="Liên hệ" data-bnav-id="contact">
        <i class="fa-solid fa-headset" aria-hidden="true"></i>
        <span>Liên hệ</span>
    </a>
</nav>
