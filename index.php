<?php header('Cache-Control: no-store, max-age=0'); ?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Trang Chủ - Đặt Sân Cầu Lông Trực Tuyến | GODMINTON</title>
    <meta name="description" content="Hệ thống đặt sân cầu lông trực tuyến theo thời gian thực (AJAX), hỗ trợ PWA cài đặt app và thông báo đẩy nhắc lịch thi đấu.">
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
    <link rel="stylesheet" href="assets/css/index_explore_tabs_style.css?v=<?= filemtime(__DIR__ . '/assets/css/index_explore_tabs_style.css') ?>">
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

    <main class="min-vh-100">

    <!-- Hero Banner Section -->
    <!-- <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-7" data-aos="fade-right" data-aos-duration="900">
                    <div class="hero-badge mb-3">
                        <i class="fa-solid fa-bolt-lightning"></i> CẬP NHẬT TRẠNG THÁI REAL-TIME 100%
                    </div>
                    <h1 class="hero-title mb-4">
                        Đặt Sân Cầu Lông <br>
                        <span class="text-teal">Nhanh Chóng & Chuẩn Xác</span>
                    </h1>
                    <p class="lead text-light opacity-90 mb-4 pe-lg-5">
                        Hệ thống đặt sân thông minh hỗ trợ kiểm tra slot trống tức thì không cần tải lại trang, công nghệ khóa giữ chỗ tự động chống đặt trùng và nhắc nhở lịch chơi qua PWA.
                    </p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="courts.php" class="btn btn-teal btn-lg rounded-pill px-4 fw-bold">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Tìm Sân Ngay
                        </a>
                        <a href="#howItWorks" class="btn btn-outline-light btn-lg rounded-pill px-4">
                            <i class="fa-solid fa-circle-play me-2"></i>Xem Cách Đặt Sân
                        </a>
                    </div>
                </div>

                <div class="col-lg-5 d-none d-lg-block" data-aos="fade-left" data-aos-duration="1000">
                    <div class="p-4 rounded-4 bg-dark bg-opacity-50 border border-light border-opacity-10 shadow-lg text-white">
                        <div class="d-flex align-items-center justify-content-between pb-3 border-bottom border-secondary border-opacity-25 mb-3">
                            <span class="fw-bold fs-5"><i class="fa-solid fa-stopwatch-20 me-2 text-warning"></i>Tình Trạng Sân Trực Tiếp</span>
                            <span class="badge bg-success bg-opacity-75 animate-pulse">Live Polling</span>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <div class="p-2 rounded-3 bg-secondary bg-opacity-25 d-flex justify-content-between align-items-center">
                                <span>Cầu Lông Quận 1 – Lê Thánh Tôn Sport (Thảm A1)</span>
                                <span class="badge bg-success">Còn trống</span>
                            </div>
                            <div class="p-2 rounded-3 bg-secondary bg-opacity-25 d-flex justify-content-between align-items-center">
                                <span>CLB Cầu Lông Bình Thạnh – Arena Xô Viết (Thảm 1)</span>
                                <span class="badge bg-warning text-dark">Đang giữ chỗ</span>
                            </div>
                            <div class="p-2 rounded-3 bg-secondary bg-opacity-25 d-flex justify-content-between align-items-center">
                                <span>Trung Tâm Thể Thao Tân Bình – TBSport (Thảm 2)</span>
                                <span class="badge bg-danger">Đã đặt</span>
                            </div>
                        </div>
                        <div class="mt-3 text-center small text-muted-light">
                            <i class="fa-solid fa-sync fa-spin me-1 text-teal"></i> Cập nhật liên tục mỗi 5 giây qua AJAX
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Hộp Tìm Kiếm Nổi -->
    <div class="container">
        <div class="search-card-floating" data-aos="zoom-in" data-aos-delay="200">
            <form class="row g-3 align-items-end" id="homeSearchForm">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-location-dot text-teal me-1"></i> Khu Vực /Quận</label>
                    <select id="homeDistrict" class="form-select form-select-lg rounded-3 fs-6">
                        <option value="">Tất cả khu vực (TP.HCM)</option>
                        <!-- Sẽ được populate bởi AJAX từ api/wards.php -->
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
                        <option value="parking">Bãi Đỗ Xe Ô Tô</option>
                        <option value="shower">Phòng Tắm Nóng Lạnh</option>
                        <option value="rental">Cho Thuê Vợt / Giày</option>
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

    <!-- Section: Sân Cầu Lông Nổi Bật (AJAX) -->
    <section class="py-5 mt-4">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
                <div>
                    <span class="text-teal fw-bold text-uppercase tracking-wider small">Tìm Sân Phù Hợp</span>
                    <h2 class="fw-extrabold mb-0">Khám Phá Các Cụm Sân Cầu Lông</h2>
                </div>
                <a href="courts.php" class="btn btn-outline-teal rounded-pill px-3 mt-2 mt-sm-0">
                    Xem tất cả sân <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Swiper Carousel — slides sẽ được render bởi JS/AJAX -->
            <div class="swiper featuredCourtsSwiper pb-5" data-aos="fade-up">
                <div class="swiper-wrapper" id="featuredCourtsWrapper">
                    <!-- Loading placeholder -->
                    <div class="swiper-slide">
                        <div class="text-center py-5">
                            <div class="spinner-border text-teal" role="status"></div>
                            <div class="mt-2 text-muted small">Đang tải sân nổi bật...</div>
                        </div>
                    </div>
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- Section: Điểm Nhấn Công Nghệ & Cách Hoạt Động -->
    <!-- <section id="howItWorks" class="py-5 bg-body-tertiary">
        <div class="container">
            <div class="text-center max-w-700 mx-auto mb-5" data-aos="fade-up">
                <span class="text-teal fw-bold text-uppercase small">Công Nghệ Hiện Đại</span>
                <h2 class="fw-extrabold">Quy Trình Đặt Sân Chuẩn 3 Bước</h2>
                <p class="text-muted">Không còn cảnh xếp hàng, không lo trùng lịch với hệ thống đồng bộ theo thời gian thực</p>
            </div>

            <div class="row g-4 text-center">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="p-4 rounded-4 bg-surface border h-100 shadow-sm">
                        <div class="icon-circle bg-teal-subtle text-teal mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <h5 class="fw-bold">1. Chọn Khung Giờ Trực Quan</h5>
                        <p class="text-muted small mb-0">
                            Xem lưới slot realtime. Màu xanh là trống, màu vàng đang có người giữ, màu đỏ đã đặt. Tự động refresh mỗi 5s bằng Fetch API.
                        </p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="200">
                    <div class="p-4 rounded-4 bg-surface border h-100 shadow-sm">
                        <div class="icon-circle bg-warning bg-opacity-10 text-warning mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <h5 class="fw-bold">2. Giữ Chỗ Tự Động (5 Phút)</h5>
                        <p class="text-muted small mb-0">
                            Giao dịch ACID (SELECT ... FOR UPDATE) khóa ngay slot của bạn, chống tuyệt đối việc 2 người cùng đặt 1 giờ. Bạn có 5 phút hoàn tất.
                        </p>
                    </div>
                </div>

                <div class="col-md-4" data-aos="fade-up" data-aos-delay="300">
                    <div class="p-4 rounded-4 bg-surface border h-100 shadow-sm">
                        <div class="icon-circle bg-success bg-opacity-10 text-success mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        <h5 class="fw-bold">3. Nhận Thông Báo Đẩy (PWA)</h5>
                        <p class="text-muted small mb-0">
                            Cài đặt app trực tiếp từ trình duyệt ra màn hình điện thoại. Nhận Web Push nhắc lịch đấu trước giờ thi đấu.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section> -->

    <!-- Section: Khám Phá -->
    <section class="py-5" id="khamPhaSection">
        <div class="container">
            <!-- Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4" data-aos="fade-up">
                <div>
                    <span class="text-teal fw-bold text-uppercase small">Cộng Đồng GODMINTON</span>
                    <h2 class="fw-extrabold mb-0">Khám Phá</h2>
                </div>
                <a href="news.php" class="btn btn-outline-teal rounded-pill px-3 mt-2 mt-sm-0">
                    Xem tất cả <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Category tabs -->
            <div class="explore-tabs-wrap mb-4" data-aos="fade-up" data-aos-delay="50">
                <div class="explore-tabs">
                    <button class="explore-tab active" onclick="filterExplore(this,'all')">
                        <i class="fa-solid fa-border-all"></i> Tất cả
                    </button>
                    <button class="explore-tab" onclick="filterExplore(this,'event')">
                        <i class="fa-solid fa-trophy"></i> Sự kiện
                    </button>
                    <button class="explore-tab" onclick="filterExplore(this,'membership')">
                        <i class="fa-solid fa-id-card"></i> Gói hội viên
                    </button>
                    <button class="explore-tab" onclick="filterExplore(this,'class')">
                        <i class="fa-solid fa-person-chalkboard"></i> Khóa học
                    </button>
                    <button class="explore-tab" onclick="filterExplore(this,'promo')">
                        <i class="fa-solid fa-gift"></i> Ưu đãi
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

    <!-- Footer -->
    <footer class="footer-custom mt-4 py-4">
        <div class="container">
            <div class="row gy-4 gx-5 align-items-start">
                <div class="col-md-6">
                    <a href="index.php" class="d-inline-block mb-2" aria-label="GODMINTON - Trang chủ">
                        <img src="assets/img/logo.svg" alt="GODMINTON" style="height:38px; width:auto;" loading="lazy">
                    </a>
                    <p class="text-muted-light small mb-0">Tìm sân phù hợp, chọn giờ thuận tiện và quản lý lịch đặt sân của bạn.</p>
                </div>
                <div class="col-6 col-md-3">
                    <h6 class="text-white fw-bold mb-2">Đặt sân</h6>
                    <nav aria-label="Liên kết đặt sân">
                        <ul class="list-unstyled footer-links small mb-0 d-grid gap-1">
                            <li><a href="courts.php">Tìm sân cầu lông</a></li>
                            <li><a href="history.php">Lịch sử đặt sân</a></li>
                            <li><a href="profile.php">Tài khoản của tôi</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-6 col-md-3">
                    <h6 class="text-white fw-bold mb-2">Hỗ trợ</h6>
                    <nav aria-label="Liên kết hỗ trợ">
                        <ul class="list-unstyled footer-links small mb-0 d-grid gap-1">
                            <li><a href="contact.php">Liên hệ hỗ trợ</a></li>
                            <li><a href="news.php">Tin tức cầu lông</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="border-top border-secondary border-opacity-25 mt-3 pt-3 small text-muted-light">
                &copy; 2026 GODMINTON. Đặt sân cầu lông trực tuyến.
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <!-- Swiper.js -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- API helpers -->
    <script src="assets/js/booking-account-client.js?v=<?= filemtime(__DIR__ . '/assets/js/booking-account-client.js') ?>"></script>
    <!-- App JS -->
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>

    <script src="assets/js/content-client.js?v=<?= filemtime(__DIR__ . '/assets/js/content-client.js') ?>"></script>
    <script src="assets/js/index_explore_dt.js?v=<?= filemtime(__DIR__ . '/assets/js/index_explore_dt.js') ?>"></script>

    <script src="assets/js/home-court-cards.js?v=<?= filemtime(__DIR__ . '/assets/js/home-court-cards.js') ?>"></script>

    <!-- ===== AJAX: Load Featured Courts & Home Search ===== -->
    <script>
    document.addEventListener('DOMContentLoaded', async () => {
        // --- Load wards dropdown ---
        try {
            const wardRes  = await fetch('api/wards.php');
            const wardJson = await wardRes.json();
            if (wardJson.status === 'success') {
                const sel = document.getElementById('homeDistrict');
                wardJson.data.forEach(w => {
                    const opt = document.createElement('option');
                    opt.value = w.id;
                    opt.textContent = `${w.name} (${w.old_area_label})`;
                    sel.appendChild(opt);
                });
            }
        } catch (e) { console.warn('Không tải được danh sách phường:', e); }

        // --- Load featured courts via AJAX ---
        try {
            const res  = await fetch('api/courts.php', {cache:'no-store'});
            const json = await res.json();

            if (!res.ok || json.status !== 'success' || !Array.isArray(json.data)) throw new Error('Không tải được danh sách sân.');
            if (json.data.length === 0) document.getElementById('featuredCourtsWrapper').innerHTML = HomeCourtCards.render([]);
            if (json.data.length > 0) {
                const wrapper = document.getElementById('featuredCourtsWrapper');
                wrapper.innerHTML = HomeCourtCards.render(json.data);

                // Khởi tạo Swiper sau khi có slides
                new Swiper('.featuredCourtsSwiper', {
                    slidesPerView: 1,
                    spaceBetween: 20,
                    loop: json.data.length > 3,
                    autoplay: { delay: 4000, disableOnInteraction: false },
                    pagination: { el: '.swiper-pagination', clickable: true },
                    breakpoints: {
                        576:  { slidesPerView: 2 },
                        992:  { slidesPerView: 3 },
                        1200: { slidesPerView: 3 }
                    }
                });
            }
        } catch (err) {
            console.error('Lỗi tải sân nổi bật:', err);
            document.getElementById('featuredCourtsWrapper').innerHTML = `
                <div class="swiper-slide">
                    <div class="text-center py-5">
                        <i class="fa-solid fa-triangle-exclamation text-danger fs-3"></i>
                        <div class="mt-2 text-muted small">Không thể tải dữ liệu sân. Kiểm tra kết nối CSDL.</div>
                    </div>
                </div>`;
        }

        // --- Home Search Form ---
        document.getElementById('homeSearchForm').addEventListener('submit', (e) => {
            e.preventDefault();
            const params = new URLSearchParams();
            const ward    = document.getElementById('homeDistrict').value;
            const price   = document.getElementById('homeMaxPrice').value;
            const amenity = document.getElementById('homeAmenity').value;
            if (ward)    params.set('ward_id', ward);
            if (price)   params.set('max_price', price);
            if (amenity) params.set('amenity', amenity);
            window.location.href = `courts.php?${params.toString()}`;
        });
    });
    </script>

    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
</body>
</html>



