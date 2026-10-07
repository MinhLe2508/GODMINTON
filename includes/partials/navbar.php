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
<!-- App Brand Header (Khung Cố Định - Logo trung tâm & 2 cánh sáng đối xứng) -->
<header class="app-brand-head">
    <div class="container text-center">
        <a href="index.php" class="brand-logo-link" aria-label="GODMINTON Trang chủ">
            <img src="<?= htmlspecialchars($assetsPath) ?>/img/logo-teal.svg" alt="GODMINTON" class="brand-logo-img">
        </a>
    </div>
</header>
