<?php
/**
 * includes/partials/navbar.php
 * Thanh thương hiệu & điều hướng chính (Header) của GODMINTON.
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 */
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$assetsPath = $assetsPath ?? 'assets';
?>
<!-- App Brand Header (Khung Cố Định) -->
<header class="app-brand-head">
    <div class="container d-flex align-items-center justify-content-between position-relative">
        <div class="d-none d-md-block" style="width: 40px;"></div>
        
        <a href="index.php" class="brand-logo-link mx-auto mx-md-0" aria-label="GODMINTON Trang chủ">
            <img src="<?= htmlspecialchars($assetsPath) ?>/img/logo-teal.svg" alt="GODMINTON" class="brand-logo-img" style="height: 38px; width: auto;">
        </a>

        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-sm btn-outline-secondary rounded-circle" id="themeToggle" title="Chuyển chế độ Sáng / Tối" style="width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-moon"></i>
            </button>
        </div>
    </div>
</header>
