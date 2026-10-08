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

                <!-- Giao diện Sáng / Tối -->
                <button class="sb-btn" id="themeToggle" title="Chuyển chế độ Sáng / Tối">
                    <i class="fa-solid fa-moon"></i>
                    <span class="sb-lbl">Giao diện</span>
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
            <button class="drawer-close" onclick="closeFilterDrawer()" title="Đóng">
                <i class="fa-solid fa-arrow-left"></i>
            </button>
            <h5>Bộ Lọc Tìm Kiếm</h5>
        </div>
        <button class="drawer-close" onclick="closeFilterDrawer()" title="Đóng">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <!-- Body -->
    <div class="drawer-body">

        <!-- Khu vực -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-location-dot text-teal me-1"></i>Khu Vực /Quận
            </div>
            <select class="drawer-select" id="filterDistrict">
                <option value="">Tất cả khu vực TP.HCM</option>
                <option value="Cầu Giấy">Quận 1</option>
                <option value="Đống Đa">Quận 2</option>
                <option value="Thanh Xuân">Quận 3</option>
                <option value="Tây Hồ">Quận 4</option>
                <option value="Hai Bà Trưng">Quận 5</option>
                <option value="Ba Đình">Quận 6</option>
                <option value="Hoàng Mai">Quận 7</option>
                <option value="Long Biên">Thủ Đức</option>
            </select>
        </div>

        <!-- Mức giá -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-tag text-teal me-1"></i>Mức Giá Thuê
            </div>
            <select class="drawer-select" id="filterPrice">
                <option value="">Tất cả mức giá</option>
                <option value="80000">Dưới 80.000 đ/giờ</option>
                <option value="90000">Dưới 90.000 đ/giờ</option>
                <option value="100000">Dưới 100.000 đ/giờ</option>
                <option value="150000">Dưới 150.000 đ/giờ</option>
            </select>
        </div>

        <!-- Tiện ích sân -->
        <div class="drawer-section">
            <div class="drawer-section-title">
                <i class="fa-solid fa-star text-teal me-1"></i>Tiện Ích Sân
            </div>
            <div class="amenity-grid">
                <button class="amenity-chip" data-value="aircon" data-label="Điều hòa" onclick="toggleAmenityChip(this)">
                    <i class="fa-solid fa-snowflake text-info"></i> Điều hòa
                </button>
                <button class="amenity-chip" data-value="parking" data-label="Bãi đỗ xe" onclick="toggleAmenityChip(this)">
                    <i class="fa-solid fa-square-parking text-primary"></i> Bãi đỗ xe
                </button>
                <button class="amenity-chip" data-value="shower" data-label="Phòng tắm" onclick="toggleAmenityChip(this)">
                    <i class="fa-solid fa-shower text-success"></i> Phòng tắm
                </button>
                <button class="amenity-chip" data-value="rental" data-label="Cho thuê vợt" onclick="toggleAmenityChip(this)">
                    <i class="fa-solid fa-medal text-warning"></i> Cho thuê vợt
                </button>
                <button class="amenity-chip" data-value="cafe" data-label="Canteen/Cafe" onclick="toggleAmenityChip(this)">
                    <i class="fa-solid fa-mug-hot" style="color:#f97316;"></i> Canteen/Cafe
                </button>
                <button class="amenity-chip" data-value="locker" data-label="Tủ khóa" onclick="toggleAmenityChip(this)">
                    <i class="fa-solid fa-lock text-secondary"></i> Tủ khóa
                </button>
                <button class="amenity-chip" data-value="wifi" data-label="Wi-Fi" onclick="toggleAmenityChip(this)">
                    <i class="fa-solid fa-wifi" style="color:#8b5cf6;"></i> Wi-Fi
                </button>
                <button class="amenity-chip" data-value="court_indoor" data-label="Sân trong nhà" onclick="toggleAmenityChip(this)">
                    <i class="fa-solid fa-warehouse" style="color:#06b6d4;"></i> Sân trong nhà
                </button>
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
                <button class="amenity-chip" data-value="type_event" data-label="Sự kiện/Xẻ đôi" onclick="toggleBookingType(this)">
                    Sự kiện / Xẻ đôi
                </button>
                <button class="amenity-chip" data-value="type_group" data-label="Ghép đội" onclick="toggleBookingType(this)">
                    Ghép đội
                </button>
            </div>
        </div>

        <!-- Rating -->
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

    <!-- Map placeholder -->
    <div id="mapSection" class="mt-5 rounded-4 overflow-hidden border" style="height:300px; background:#0e1b2a; display:flex; align-items:center; justify-content:center;">
        <div class="text-center text-muted">
            <i class="fa-solid fa-map-location-dot fs-1 mb-2" style="color:#0d9488;"></i>
            <div class="fw-bold">Bản đồ sân cầu lông</div>
            <div class="small">Google Maps API — hiển thị vị trí các sân</div>
        </div>
    </div>
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
                    <li><a href="news.php">Tin tức</a></li>
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
<script src="assets/js/mock-data.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/courts.js"></script>
<!-- Bottom Nav -->
<script src="assets/js/bottom-nav.js"></script>
</body>
</html>
