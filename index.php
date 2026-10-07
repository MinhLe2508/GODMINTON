<?php
/**
 * index.php — Trang Chủ Đặt Sân Cầu Lông Trực Tuyến | GODMINTON
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 * Cập nhật:
 * - Tách partial: navbar.php, bottom-nav.php, footer.php
 * - Dữ liệu mẫu tập trung từ sample_data.php
 * - Gỡ bỏ: tin tức, sự kiện, voucher, rating hiển thị
 * - Chuyển toàn bộ liên kết sang .php
 */

// DỮ LIỆU MẪU - thay bằng truy vấn DB ở tuần 2
require_once __DIR__ . '/includes/sample_data.php';

$currentPage = 'index';
$assetsPath = 'assets';
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Trang Chủ - Đặt Sân Cầu Lông Trực Tuyến | GODMINTON</title>
    <meta name="description" content="Hệ thống đặt sân cầu lông trực tuyến theo thời gian thực, hỗ trợ kiểm tra slot trống và giữ chỗ tự động 5 phút.">
    <meta name="theme-color" content="#0d9488">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- AOS -->
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <!-- Swiper.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Index Explore Tabs Style CSS -->
    <link rel="stylesheet" href="assets/css/index_explore_tabs_style.css">
    <!-- UI States CSS -->
    <link rel="stylesheet" href="assets/css/ui-states.css">
</head>
<body>

    <!-- Navbar Partial -->
    <?php include __DIR__ . '/includes/partials/navbar.php'; ?>

    <main class="min-vh-100">

    <!-- Hộp Tìm Kiếm Nổi -->
    <div class="container pt-4">
        <div class="search-card-floating" data-aos="zoom-in" data-aos-delay="100">
            <form class="row g-3 align-items-end" onsubmit="handleHomeSearch(event)">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-location-dot text-teal me-1"></i> Khu Vực (Đô thị lõi)</label>
                    <select id="homeDistrict" class="form-select form-select-lg rounded-3 fs-6">
                        <option value="">Tất cả khu vực (TP.HCM)</option>
                        <?php foreach ($SAMPLE_WARDS as $w): ?>
                            <option value="<?= htmlspecialchars($w['name']) ?>"><?= htmlspecialchars($w['name']) ?> (<?= htmlspecialchars($w['old_area_label']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-tag text-teal me-1"></i> Mức Giá Thuê</label>
                    <select id="homeMaxPrice" class="form-select form-select-lg rounded-3 fs-6">
                        <option value="">Tất cả mức giá</option>
                        <option value="80000">Dưới 80.000 đ/h</option>
                        <option value="100000">Dưới 100.000 đ/h</option>
                        <option value="150000">Dưới 150.000 đ/h</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-dumbbell text-teal me-1"></i> Tiện Ích Sân</label>
                    <select id="homeAmenity" class="form-select form-select-lg rounded-3 fs-6">
                        <option value="">Mọi tiện ích</option>
                        <option value="aircon">Có Điều Hòa</option>
                        <option value="parking">Bãi Đỗ Xe</option>
                        <option value="shower">Phòng Tắm</option>
                        <option value="rental">Cho Thuê Vợt</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-teal btn-lg w-100 rounded-3 fw-bold fs-6">
                        <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm Sân
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Section: Sân Cầu Lông Nổi Bật -->
    <section class="py-5 mt-2">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
                <div>
                    <span class="text-teal fw-bold text-uppercase tracking-wider small">Điểm Đến Tiêu Biểu</span>
                    <h2 class="fw-extrabold mb-0">Cụm Sân Cầu Lông Chất Lượng Cao</h2>
                </div>
                <a href="courts.php" class="btn btn-outline-teal rounded-pill px-3 mt-2 mt-sm-0">
                    Xem tất cả sân <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Swiper Carousel -->
            <div class="swiper featuredCourtsSwiper pb-5" data-aos="fade-up">
                <div class="swiper-wrapper" id="featuredCourtsWrapper">
                    <?php foreach ($SAMPLE_COURTS as $c): ?>
                    <div class="swiper-slide">
                        <div class="court-card">
                            <div class="court-img-wrapper">
                                <img src="<?= htmlspecialchars($c['images'][0] ?? 'assets/img/gmdiscount.png') ?>" alt="<?= htmlspecialchars($c['name']) ?>" class="court-img" loading="lazy">
                                <div class="court-badge-price"><?= format_vnd($c['min_price']) ?> <span class="small opacity-75">/ giờ</span></div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-teal-subtle text-teal">
                                        <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($c['ward_name']) ?>
                                    </span>
                                    <span class="badge bg-success-subtle text-success small">
                                        <i class="fa-solid fa-circle-check me-1"></i>Hoạt động
                                    </span>
                                </div>
                                <h5 class="fw-bold mb-2 text-truncate"><?= htmlspecialchars($c['name']) ?></h5>
                                <p class="text-muted small mb-3 flex-grow-1 line-clamp-2"><?= htmlspecialchars($c['description']) ?></p>
                                <div class="d-flex gap-2">
                                    <a href="court-detail.php?id=<?= $c['id'] ?>" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                                    <a href="booking.php?court_id=<?= $c['id'] ?>" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- Section: Khám Phá Hoạt Động (Đã gỡ Sự kiện và Voucher) -->
    <section class="py-5" id="khamPhaSection">
        <div class="container">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4" data-aos="fade-up">
                <div>
                    <span class="text-teal fw-bold text-uppercase small">Cộng Đồng GODMINTON</span>
                    <h2 class="fw-extrabold mb-0">Khám Phá Sân Bãi & Hội Viên</h2>
                </div>
                <a href="courts.php" class="btn btn-outline-teal rounded-pill px-3 mt-2 mt-sm-0">
                    Đặt sân ngay <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Category tabs (Đã gỡ 'event' và 'promo/voucher') -->
            <div class="explore-tabs-wrap mb-4" data-aos="fade-up" data-aos-delay="50">
                <div class="explore-tabs">
                    <button class="explore-tab active" onclick="filterExplore(this,'all')">
                        <i class="fa-solid fa-border-all"></i> Tất cả
                    </button>
                    <button class="explore-tab" onclick="filterExplore(this,'membership')">
                        <i class="fa-solid fa-id-card"></i> Gói hội viên
                    </button>
                    <button class="explore-tab" onclick="filterExplore(this,'class')">
                        <i class="fa-solid fa-person-chalkboard"></i> Khóa học
                    </button>
                    <button class="explore-tab" onclick="filterExplore(this,'notice')">
                        <i class="fa-solid fa-bell"></i> Thông báo
                    </button>
                    <button class="explore-tab" onclick="filterExplore(this,'pass')">
                        <i class="fa-solid fa-ticket"></i> Pass sân
                    </button>
                </div>
            </div>

            <!-- Feed cards grid -->
            <div class="row g-4" id="exploreFeed" data-aos="fade-up" data-aos-delay="100">
                <!-- Rendered by JS -->
            </div>
        </div>
    </section>

    </main>

    <!-- Footer Partial -->
    <?php include __DIR__ . '/includes/partials/footer.php'; ?>

    <!-- Bottom Navigation Partial -->
    <?php include __DIR__ . '/includes/partials/bottom-nav.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <!-- Swiper.js -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Mock Data -->
    <script src="assets/js/mock-data.js"></script>
    <!-- UI States Helper -->
    <script src="assets/js/ui-states.js"></script>
    <!-- App JS -->
    <script src="assets/js/app.js"></script>
    <script src="assets/js/index_explore_dt.js"></script>
    <!-- Bottom Nav Script -->
    <script src="assets/js/bottom-nav.js"></script>
</body>
</html>
