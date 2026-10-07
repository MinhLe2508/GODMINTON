<?php
/**
 * courts.php — Danh Sách Sân Cầu Lông - Tìm Kiếm & Lọc Nhanh
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 * Cập nhật:
 * - Tách partial: navbar.php, bottom-nav.php, footer.php
 * - Dữ liệu mẫu tập trung từ sample_data.php
 * - Gỡ bỏ: rating filter, rating sort, sự kiện, link news/contact
 * - Chuyển toàn bộ liên kết sang .php
 */

// DỮ LIỆU MẪU - thay bằng truy vấn DB ở tuần 2
require_once __DIR__ . '/includes/sample_data.php';

$currentPage = 'courts';
$assetsPath = 'assets';
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh Sách Sân Cầu Lông - Tìm Kiếm & Lọc Nhanh | GODMINTON</title>
    <meta name="description" content="Tìm kiếm và lọc sân cầu lông theo khu vực, giá và tiện ích tại TP.HCM.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/courts_style.css">
    <link rel="stylesheet" href="assets/css/ui-states.css">
</head>
<body>

<!-- Navbar Partial -->
<?php include __DIR__ . '/includes/partials/navbar.php'; ?>

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

                <!-- Bộ lọc -->
                <button class="sb-btn" id="btnOpenFilter" onclick="openFilterDrawer()" title="Bộ lọc">
                    <i class="fa-solid fa-sliders"></i>
                    <span class="sb-lbl">Bộ lọc</span>
                    <span class="filter-count" id="filterCountBadge">0</span>
                </button>

                <!-- Bản đồ -->
                <a href="#mapSection" class="sb-btn" title="Bản đồ sân">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span class="sb-lbl">Bản đồ</span>
                </a>

                <!-- Sân đã đặt -->
                <a href="history.php" class="sb-btn" title="Sân đã đặt">
                    <i class="fa-solid fa-calendar-check"></i>
                    <span class="sb-lbl">Đã đặt</span>
                </a>

                <!-- Yêu thích -->
                <button class="sb-btn" id="btnFavorite" onclick="toggleFavoriteFilter()" title="Sân yêu thích">
                    <i class="fa-regular fa-heart"></i>
                    <span class="sb-lbl">Yêu thích</span>
                </button>

            </div>
        </div>

        <!-- Active filter tags strip (shown when filters applied) -->
        <div class="active-tags-strip" id="activeTagsStrip">
            <span class="small fw-bold text-muted me-1" style="font-size:0.7rem; flex-shrink:0;">ĐÃ LỌC:</span>
            <div id="activeTagsContainer" class="d-flex flex-wrap gap-1 align-items-center"></div>
            <button class="btn-reset-all" id="btnResetAll" onclick="resetAllFilters()">
                <i class="fa-solid fa-rotate-left"></i> Đặt lại
            </button>
        </div>

    </div>
</div>

<!-- ============================================================
     FILTER DRAWER OVERLAY + DRAWER
     ============================================================ -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeFilterDrawer()"></div>

<div class="filter-drawer" id="filterDrawer">

    <!-- Header -->
    <div class="drawer-header">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-sliders text-teal"></i>
            <span class="drawer-title">Bộ Lọc Tìm Kiếm</span>
        </div>
        <button class="drawer-close" onclick="closeFilterDrawer()" aria-label="Đóng">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Body -->
    <div class="drawer-body">

        <!-- Khu vực (Quận / Phường) -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-location-dot text-teal me-1"></i>Khu Vực (Đô Thị Lõi Cũ)
            </div>
            <select class="form-select form-select-sm" id="filterDistrict">
                <option value="">Tất cả khu vực</option>
                <?php foreach ($SAMPLE_WARDS as $w): ?>
                    <option value="<?= htmlspecialchars($w['name']) ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars($w['old_area_label']) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Mức giá / giờ -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-tag text-teal me-1"></i>Mức Giá / Giờ (Tối đa)
            </div>
            <select class="form-select form-select-sm" id="filterPrice">
                <option value="">Tất cả mức giá</option>
                <option value="80000">Dưới 80.000 đ/h</option>
                <option value="100000">Dưới 100.000 đ/h</option>
                <option value="120000">Dưới 120.000 đ/h</option>
            </select>
        </div>

        <!-- Tiện ích (Amenities) -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-wand-magic-sparkles text-teal me-1"></i>Tiện Ích
            </div>
            <div class="d-flex flex-wrap gap-2" id="amenityChipsGroup">
                <?php
                $amenityIcons = [
                    'aircon' => 'fa-snowflake text-info',
                    'parking' => 'fa-square-parking text-primary',
                    'shower' => 'fa-shower text-success',
                    'rental' => 'fa-medal text-warning',
                    'cafe' => 'fa-mug-hot text-danger',
                    'locker' => 'fa-lock text-secondary',
                    'wifi' => 'fa-wifi text-primary',
                    'indoor' => 'fa-warehouse text-info'
                ];
                foreach ($SAMPLE_AMENITIES as $code => $label):
                ?>
                    <button class="amenity-chip" data-value="<?= htmlspecialchars($code) ?>" data-label="<?= htmlspecialchars($label) ?>" onclick="toggleAmenityChip(this)">
                        <i class="fa-solid <?= $amenityIcons[$code] ?? 'fa-check' ?>"></i> <?= htmlspecialchars($label) ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Loại hình -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-table-tennis-paddle-ball text-teal me-1"></i>Loại Lịch
            </div>
            <div class="d-flex flex-wrap gap-2" id="bookingTypeGroup">
                <button class="amenity-chip" data-value="type_single" data-label="Sân trống" onclick="toggleBookingType(this)">
                    Sân trống
                </button>
                <button class="amenity-chip" data-value="type_group" data-label="Ghép đội" onclick="toggleBookingType(this)">
                    Ghép đội
                </button>
            </div>
        </div>

    </div><!-- /drawer-body -->

    <!-- Footer with reset + apply -->
    <div class="drawer-footer">
        <button class="btn-drawer-reset" onclick="resetDrawerFilters()" title="Đặt lại tất cả bộ lọc">
            <i class="fa-solid fa-rotate-left"></i> Đặt lại
        </button>
        <button class="btn-drawer-apply" onclick="applyDrawerFilters()">
            <i class="fa-solid fa-magnifying-glass me-1"></i>Tìm Sân
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
                Tìm thấy <strong id="courtCountText" class="text-teal"><?= count($SAMPLE_COURTS) ?></strong> sân
            </div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <button class="sort-tab-btn active" onclick="setSortMode(this,'default')">Tất cả</button>
            <button class="sort-tab-btn" onclick="setSortMode(this,'price_asc')"><i class="fa-solid fa-arrow-up me-1"></i>Giá thấp</button>
            <button class="sort-tab-btn" onclick="setSortMode(this,'price_desc')"><i class="fa-solid fa-arrow-down me-1"></i>Giá cao</button>
        </div>
    </div>

    <!-- Court grid -->
    <div id="courtListContainer" class="row g-4">
        <div class="text-center py-5 col-12">
            <div class="spinner-border text-teal" role="status"></div>
            <div class="mt-2 text-muted">Đang nạp danh sách sân...</div>
        </div>
    </div>

    <!-- Map placeholder -->
    <div id="mapSection" class="mt-5 rounded-4 overflow-hidden border" style="height:300px; background:#0e1b2a; display:flex; align-items:center; justify-content:center;">
        <div class="text-center text-muted">
            <i class="fa-solid fa-map-location-dot fs-1 mb-2" style="color:#0d9488;"></i>
            <div class="fw-bold">Bản đồ sân cầu lông</div>
            <div class="small">Google Maps API — hiển thị vị trí các sân tại TP.HCM</div>
        </div>
    </div>
</div>
</main>

<!-- Footer Partial -->
<?php include __DIR__ . '/includes/partials/footer.php'; ?>

<!-- Bottom Navigation Partial -->
<?php include __DIR__ . '/includes/partials/bottom-nav.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/mock-data.js"></script>
<script src="assets/js/ui-states.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/courts.js"></script>
<!-- Bottom Nav Script -->
<script src="assets/js/bottom-nav.js"></script>
</body>
</html>
