<?php
header('Cache-Control: no-store, max-age=0');
define('GODMINTON_DB_OPTIONAL', true);
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Sân Cầu Lông - Tìm Kiếm & Lọc Nhanh | GODMINTON</title>
    <meta name="description" content="Tìm kiếm và lọc sân cầu lông theo khu vực, giá và tiện ích tại TP.HCM.">
<?php include_once __DIR__ . '/includes/partials/pwa-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(__DIR__ . '/assets/css/swal-godminton.css') ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/courts_style.css?v=<?= filemtime(__DIR__ . '/assets/css/courts_style.css') ?>">
</head>
<body>

<!-- App Brand Header (Khung Cố Định) -->
<header class="app-brand-head">
    <div class="container text-center">
        <a href="index.php" class="brand-logo-link" aria-label="GODMINTON Trang chủ">
            <img src="assets/img/logo-teal.svg" alt="GODMINTON" class="brand-logo-img">
        </a>
    </div>
</header>

<!-- ==================== SLIM SEARCH BAR ==================== -->
<div class="search-bar-wrap">
    <div class="container-fluid px-0">

        <!-- Main bar -->
        <div class="search-bar-inner">

            <!-- 🔍 Text search -->
            <div class="sb-input-wrap">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="filterSearch" placeholder="Tìm tên sân, địa điểm..." autocomplete="off">
            </div>

            <!-- Action buttons (right side) -->
            <div class="sb-actions">

                <!-- 🎛️ Filter button with badge -->
                <button class="sb-filter-btn" id="btnOpenFilter" type="button" onclick="openFilterDrawer()">
                    <i class="fa-solid fa-sliders"></i>
                    <span>Bộ lọc</span>
                    <span class="sb-filter-badge" id="filterBadge" style="display:none;">0</span>
                </button>

                <!-- ✕ Quick clear (shows when filters active) -->
                <button class="sb-clear-btn" id="btnQuickClear" type="button"
                        onclick="resetAllFilters()" title="Xóa bộ lọc" style="display:none;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <!-- Giao diện Sáng / Tối -->
                <button class="sb-btn" id="themeToggle" type="button" title="Chuyển chế độ Sáng / Tối" aria-label="Chuyển chế độ Sáng / Tối">
                    <i class="fa-solid fa-moon"></i>
                    <span class="sb-lbl">Giao diện</span>
                </button>
            </div>
        </div>

        <!-- Active filter pills row (populated by JS) -->
        <div class="active-Filter-pills" id="activePillsRow" style="display:none;"></div>

    </div>
</div>

<!-- ==================== FILTER DRAWER (Slide-over) ==================== -->
<!-- Backdrop -->
<div class="filter-backdrop" id="filterBackdrop" onclick="closeFilterDrawer()"></div>

<!-- Drawer panel -->
<div class="filter-drawer" id="filterDrawer" role="dialog" aria-label="Bộ lọc tìm kiếm">

    <!-- Header -->
    <div class="drawer-header">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-sliders text-teal"></i>
            <span class="fw-extrabold" style="font-size:1rem;">Bộ Lọc Tìm Kiếm</span>
        </div>
        <button class="drawer-close-btn" onclick="closeFilterDrawer()" aria-label="Đóng">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Scrollable body -->
    <div class="drawer-body">

        <!-- 1. Khu vực / Phường -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-location-dot text-teal me-1"></i>Khu Vực / Phường
            </div>
            <!-- Quick area chips -->
            <div class="area-chips-wrap" id="wardChipsWrap">
                <button class="area-chip selected" data-ward="" onclick="selectWardChip(this, '')">
                    Tất cả
                </button>
                <!-- JS sẽ điền thêm từ API wards.php -->
            </div>
            <!-- Fallback select (ẩn, đồng bộ với chips để courts.js đọc) -->
            <select id="filterDistrict" class="d-none">
                <option value="">Tất cả khu vực</option>
            </select>
        </div>

        <!-- 2. Mức giá tối đa -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-tag text-teal me-1"></i>Mức Giá / Giờ
            </div>
            <div class="price-presets" id="pricePresets">
                <button class="price-chip selected" data-price="" onclick="selectPriceChip(this, '')">
                    Tất cả mức giá
                </button>
                <button class="price-chip" data-price="80000" onclick="selectPriceChip(this, '80000')">
                    <span>Dưới 80.000đ</span>
                    <small class="text-muted d-block" style="font-size:0.65rem;">Tiết kiệm</small>
                </button>
                <button class="price-chip" data-price="100000" onclick="selectPriceChip(this, '100000')">
                    <span>Dưới 100.000đ</span>
                    <small class="text-muted d-block" style="font-size:0.65rem;">Phổ biến</small>
                </button>
                <button class="price-chip" data-price="120000" onclick="selectPriceChip(this, '120000')">
                    <span>Dưới 120.000đ</span>
                    <small class="text-muted d-block" style="font-size:0.65rem;">Cao cấp</small>
                </button>
                <button class="price-chip" data-price="150000" onclick="selectPriceChip(this, '150000')">
                    <span>Dưới 150.000đ</span>
                    <small class="text-muted d-block" style="font-size:0.65rem;">VIP / Thi đấu</small>
                </button>
            </div>
            <!-- Hidden select synced for courts.js -->
            <select id="filterPrice" class="d-none">
                <option value="">Mọi mức giá</option>
                <option value="80000">Dưới 80.000đ</option>
                <option value="100000">Dưới 100.000đ</option>
                <option value="120000">Dưới 120.000đ</option>
                <option value="150000">Dưới 150.000đ</option>
            </select>
        </div>

        <!-- 3. Tiện ích sân -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-wand-magic-sparkles text-teal me-1"></i>Tiện Ích Sân
                <span class="text-muted fw-normal ms-1" style="font-size:0.68rem;">(chọn nhiều)</span>
            </div>
            <div class="amenity-grid" id="amenityGroup">
                <button class="amenity-chip" data-value="indoor" onclick="toggleAmenity(this)">
                    <i class="fa-solid fa-house-chimney"></i> Sân trong nhà
                </button>
                <button class="amenity-chip" data-value="aircon" onclick="toggleAmenity(this)">
                    <i class="fa-solid fa-snowflake"></i> Điều hòa
                </button>
                <button class="amenity-chip" data-value="parking" onclick="toggleAmenity(this)">
                    <i class="fa-solid fa-square-parking"></i> Bãi đỗ xe
                </button>
                <button class="amenity-chip" data-value="wifi" onclick="toggleAmenity(this)">
                    <i class="fa-solid fa-wifi"></i> Wi-Fi
                </button>
                <button class="amenity-chip" data-value="shower" onclick="toggleAmenity(this)">
                    <i class="fa-solid fa-shower"></i> Phòng tắm
                </button>
                <button class="amenity-chip" data-value="rental" onclick="toggleAmenity(this)">
                    <i class="fa-solid fa-racquet"></i> Thuê vợt
                </button>
                <button class="amenity-chip" data-value="cafe" onclick="toggleAmenity(this)">
                    <i class="fa-solid fa-mug-hot"></i> Canteen / Cafe
                </button>
                <button class="amenity-chip" data-value="locker" onclick="toggleAmenity(this)">
                    <i class="fa-solid fa-lock"></i> Tủ khóa
                </button>
            </div>
        </div>

        <!-- 4. Đánh giá tối thiểu -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-star text-warning me-1"></i>Đánh Giá Tối Thiểu
            </div>
            <div class="d-flex gap-2" id="ratingGroup">
                <button class="amenity-chip" data-value="4.5" onclick="setRating(this, 4.5)">
                    <i class="fa-solid fa-star text-warning"></i> 4.5+
                </button>
                <button class="amenity-chip" data-value="4.0" onclick="setRating(this, 4.0)">
                    <i class="fa-solid fa-star text-warning"></i> 4.0+
                </button>
                <button class="amenity-chip" data-value="3.5" onclick="setRating(this, 3.5)">
                    <i class="fa-solid fa-star text-warning"></i> 3.5+
                </button>
            </div>
        </div>

    </div><!-- /drawer-body -->

    <!-- Footer with reset + apply -->
    <div class="drawer-footer">
        <button class="btn-drawer-reset" type="button" onclick="resetAllFilters()" title="Đặt lại tất cả bộ lọc">
            <i class="fa-solid fa-rotate-left"></i> Đặt lại
        </button>
        <button type="button" id="btnApplyFilters" class="btn-drawer-apply" onclick="applyDrawerFilters()">
            <i class="fa-solid fa-magnifying-glass me-1"></i>Tìm kiếm
        </button>
    </div>
</div>

<!-- ==================== MAIN CONTENT ==================== -->
<main class="min-vh-100">
<div class="container py-4">

    <!-- Results header -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
        <div>
            <h5 class="fw-extrabold mb-0">Hệ Thống Sân Cầu Lông</h5>
            <div class="small text-muted mt-1">
                Tìm thấy <strong id="courtCountText" class="text-teal">...</strong> sân
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="sort-tab-btn active" onclick="setSortMode(this,'default')">Tất cả</button>
            <button class="sort-tab-btn" onclick="setSortMode(this,'price_asc')"><i class="fa-solid fa-arrow-up me-1"></i>Giá thấp</button>
            <button class="sort-tab-btn" onclick="setSortMode(this,'rating')"><i class="fa-solid fa-star me-1"></i>Đánh giá</button>
        </div>
    </div>

    <!-- Court grid -->
    <div id="courtListContainer" class="row g-4">
        <div class="text-center py-5 col-12">
            <div class="spinner-border text-teal" role="status"></div>
            <div class="mt-2 text-muted">Đang nạp danh sách sân...</div>
        </div>
    </div>

    <!-- Bản Đồ Vị Trí Sân (Leaflet.js + OpenStreetMap) -->
    <?php
    $courtMapData = [];
    if (!empty($pdo)) {
        try {
            $mapSql = "
                SELECT c.id, c.name, c.address, c.latitude, c.longitude, c.price_per_hour,
                       (SELECT ci.image_url FROM court_images ci WHERE ci.court_id = c.id ORDER BY ci.sort_order ASC LIMIT 1) AS image_url
                FROM courts c
                WHERE c.status = 'active'
            ";
            $mapStmt = $pdo->query($mapSql);
            $courtMapData = $mapStmt->fetchAll();
        } catch (Throwable $e) {
            $courtMapData = [];
        }
    }
    include __DIR__ . '/includes/partials/court-map.php';
    ?>
</div>
</main>

<!-- ==================== FOOTER ==================== -->
<footer class="footer-custom mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="mb-3"><img src="assets/img/logo.svg" alt="GODMINTON" style="height:38px; width:auto;"></div>
                <p class="text-muted-light">Nền tảng đặt sân cầu lông trực tuyến thông minh.</p>
                <div class="d-flex gap-3">
                    <a href="#" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-icon"><i class="fa-brands fa-youtube"></i></a>
                    <a href="#" class="social-icon"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6 class="text-white fw-bold mb-3">Liên Kết Nhanh</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="courts.php">Tìm kiếm sân</a></li>
                    <li><a href="explore.php">Khám phá</a></li>
                    <li><a href="contact.php">Hỗ trợ</a></li>
                </ul>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3">Hotline</h6>
                <div class="d-flex align-items-center gap-3">
                    <div class="icon-circle bg-teal text-white"><i class="fa-solid fa-phone"></i></div>
                    <div>
                        <div class="small text-muted-light">Tổng đài 24/7</div>
                        <div class="fs-5 fw-bold text-white">1900 8888</div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="border-secondary opacity-25 my-4">
        <div class="small text-muted-light">&copy; 2026 GODMINTON - Đồ án Lập trình Web.</div>
    </div>
</footer>

<!-- ==================== SCRIPTS ==================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/swal-godminton.js?v=<?= filemtime(__DIR__ . '/assets/js/swal-godminton.js') ?>"></script>
<script src="assets/js/booking-account-client.js?v=<?= filemtime(__DIR__ . '/assets/js/booking-account-client.js') ?>"></script>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script src="assets/js/courts.js?v=<?= filemtime(__DIR__ . '/assets/js/courts.js') ?>"></script>
<!-- Push Client PWA -->
<script src="assets/js/push-client.js?v=<?= filemtime(__DIR__ . '/assets/js/push-client.js') ?>"></script>
<!-- Bottom Nav -->
<script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
</body>
</html>
