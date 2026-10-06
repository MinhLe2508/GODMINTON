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
    <link rel="stylesheet" href="assets/css/index_explore_tabs_style.css">
</head>
<body>

    <!-- App Brand Header (Khung Cố Định) -->
    <header class="app-brand-head">
        <div class="container text-center">
            <a href="index.html" class="brand-logo-link" aria-label="GODMINTON Trang chủ">
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
                        <a href="courts.html" class="btn btn-teal btn-lg rounded-pill px-4 fw-bold">
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
            <form class="row g-3 align-items-end" onsubmit="handleHomeSearch(event)">
                <div class="col-md-4">
                    <label class="form-label fw-bold small text-muted"><i class="fa-solid fa-location-dot text-teal me-1"></i> Khu Vực /Quận</label>
                    <select id="homeDistrict" class="form-select form-select-lg rounded-3 fs-6">
                        <option value="">Tất cả khu vực (TP.HCM)</option>
                        <option value="1">Quận 1</option>
                        <option value="2">Quận 2</option>
                        <option value="3">Quận 3</option>
                        <option value="4">Quận 4</option>
                        <option value="5">Quận 5</option>
                        <option value="6">Quận 6</option>
                        <option value="7">Quận 7</option>
                        <option value="TD">Thủ Đức</option>
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

    <!-- Section: Sân Cầu Lông Nổi Bật -->
    <section class="py-5 mt-4">
        <div class="container">
            <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
                <div>
                    <span class="text-teal fw-bold text-uppercase tracking-wider small">Điểm Đến Hàng Đầu</span>
                    <h2 class="fw-extrabold mb-0">Tổ Hợp Sân Cầu Lông Cao Cấp</h2>
                </div>
                <a href="courts.html" class="btn btn-outline-teal rounded-pill px-3 mt-2 mt-sm-0">
                    Xem tất cả sân <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <!-- Swiper Carousel -->
            <div class="swiper featuredCourtsSwiper pb-5" data-aos="fade-up">
                <div class="swiper-wrapper" id="featuredCourtsWrapper">
                    <!-- Sân 1 -->
                    <div class="swiper-slide">
                        <div class="court-card">
                            <div class="court-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop" alt="Cầu Lông Quận 1 – Lê Thánh Tôn Sport" class="court-img" loading="lazy">
                                <div class="court-badge-price">100.000 đ <span class="small opacity-75">/ giờ</span></div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-teal-subtle text-teal">
                                        <i class="fa-solid fa-location-dot me-1"></i>Quận 1
                                    </span>
                                    <div class="small text-warning fw-bold">
                                        <i class="fa-solid fa-star me-1"></i>4.8
                                        <span class="text-muted fw-normal">(64)</span>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2 text-truncate">Cầu Lông Quận 1 – Lê Thánh Tôn Sport</h5>
                                <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">Tổ hợp sân cầu lông hiện đại ngay trung tâm Quận 1, 6 sân thảm tiêu chuẩn BWF, hệ thống chiếu sáng LED chống lóa...</p>
                                <div class="d-flex gap-2">
                                    <a href="court-detail.html?id=1" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                                    <a href="booking.html?court_id=1" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Sân 2 -->
                    <div class="swiper-slide">
                        <div class="court-card">
                            <div class="court-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop" alt="CLB Cầu Lông Bình Thạnh – Arena Xô Viết" class="court-img" loading="lazy">
                                <div class="court-badge-price">80.000 đ <span class="small opacity-75">/ giờ</span></div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-teal-subtle text-teal">
                                        <i class="fa-solid fa-location-dot me-1"></i>Bình Thạnh
                                    </span>
                                    <div class="small text-warning fw-bold">
                                        <i class="fa-solid fa-star me-1"></i>4.7
                                        <span class="text-muted fw-normal">(89)</span>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2 text-truncate">CLB Cầu Lông Bình Thạnh – Arena Xô Viết</h5>
                                <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">Sân cầu lông quy mô lớn với 8 sân thảm Yonex cao cấp, dịch vụ căng vợt điện tử lấy ngay trong 30 phút...</p>
                                <div class="d-flex gap-2">
                                    <a href="court-detail.html?id=2" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                                    <a href="booking.html?court_id=2" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Sân 3 -->
                    <div class="swiper-slide">
                        <div class="court-card">
                            <div class="court-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=800&auto=format&fit=crop" alt="Trung Tâm Thể Thao Tân Bình – TBSport" class="court-img" loading="lazy">
                                <div class="court-badge-price">75.000 đ <span class="small opacity-75">/ giờ</span></div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-teal-subtle text-teal">
                                        <i class="fa-solid fa-location-dot me-1"></i>Tân Bình
                                    </span>
                                    <div class="small text-warning fw-bold">
                                        <i class="fa-solid fa-star me-1"></i>4.6
                                        <span class="text-muted fw-normal">(52)</span>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2 text-truncate">Trung Tâm Thể Thao Tân Bình – TBSport</h5>
                                <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">Cụm 10 sân tiêu chuẩn quốc tế, bãi đỗ xe ô tô rộng rãi, phòng thay đồ hiện đại có tắm nước nóng...</p>
                                <div class="d-flex gap-2">
                                    <a href="court-detail.html?id=3" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                                    <a href="booking.html?court_id=3" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Sân 4 -->
                    <div class="swiper-slide">
                        <div class="court-card">
                            <div class="court-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=800&auto=format&fit=crop" alt="Sân Cầu Lông Gò Vấp – GV Arena" class="court-img" loading="lazy">
                                <div class="court-badge-price">70.000 đ <span class="small opacity-75">/ giờ</span></div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-teal-subtle text-teal">
                                        <i class="fa-solid fa-location-dot me-1"></i>Gò Vấp
                                    </span>
                                    <div class="small text-warning fw-bold">
                                        <i class="fa-solid fa-star me-1"></i>4.5
                                        <span class="text-muted fw-normal">(41)</span>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2 text-truncate">Sân Cầu Lông Gò Vấp – GV Arena</h5>
                                <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">Sân cầu lông giá sinh viên chất lượng cao, thảm PVC chống trơn trượt đạt chuẩn, quầy nước giải khát đầy đủ...</p>
                                <div class="d-flex gap-2">
                                    <a href="court-detail.html?id=4" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                                    <a href="booking.html?court_id=4" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Sân 5 -->
                    <div class="swiper-slide">
                        <div class="court-card">
                            <div class="court-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1517649763962-0c623266ddc0?w=800&auto=format&fit=crop" alt="Cầu Lông Quận 7 – Phú Mỹ Hưng Sport" class="court-img" loading="lazy">
                                <div class="court-badge-price">120.000 đ <span class="small opacity-75">/ giờ</span></div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-teal-subtle text-teal">
                                        <i class="fa-solid fa-location-dot me-1"></i>Quận 7
                                    </span>
                                    <div class="small text-warning fw-bold">
                                        <i class="fa-solid fa-star me-1"></i>4.9
                                        <span class="text-muted fw-normal">(112)</span>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2 text-truncate">Cầu Lông Quận 7 – Phú Mỹ Hưng Sport</h5>
                                <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">Tổ hợp thể thao cao cấp tại khu đô thị Phú Mỹ Hưng, trang bị máy lạnh 100%, sàn gỗ giảm chấn...</p>
                                <div class="d-flex gap-2">
                                    <a href="court-detail.html?id=5" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                                    <a href="booking.html?court_id=5" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Sân 6 -->
                    <div class="swiper-slide">
                        <div class="court-card">
                            <div class="court-img-wrapper">
                                <img src="https://images.unsplash.com/photo-1519766304817-4f37bda74a29?w=800&auto=format&fit=crop" alt="CLB Thể Thao Thủ Đức – ThủĐức Arena" class="court-img" loading="lazy">
                                <div class="court-badge-price">65.000 đ <span class="small opacity-75">/ giờ</span></div>
                            </div>
                            <div class="p-3 d-flex flex-column flex-grow-1">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="badge bg-teal-subtle text-teal">
                                        <i class="fa-solid fa-location-dot me-1"></i>TP. Thủ Đức
                                    </span>
                                    <div class="small text-warning fw-bold">
                                        <i class="fa-solid fa-star me-1"></i>4.4
                                        <span class="text-muted fw-normal">(38)</span>
                                    </div>
                                </div>
                                <h5 class="fw-bold mb-2 text-truncate">CLB Thể Thao Thủ Đức – ThủĐức Arena</h5>
                                <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">Cụm sân cầu lông quy mô lớn tại khu vực Làng Đại Học, thoáng mát, giá hợp lý, thích hợp cho giải đấu phong trào...</p>
                                <div class="d-flex gap-2">
                                    <a href="court-detail.html?id=6" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                                    <a href="booking.html?court_id=6" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                                </div>
                            </div>
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
                <a href="news.html" class="btn btn-outline-teal rounded-pill px-3 mt-2 mt-sm-0">
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
    <footer class="footer-custom mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="mb-3"><img src="assets/img/logo.svg" alt="GODMINTON" style="height:38px; width:auto;"></div>
                    <p class="text-muted-light">
                        Nền tảng đặt sân cầu lông trực tuyến thông minh với cập nhật trạng thái thời gian thực (AJAX), hỗ trợ cài đặt PWA và thông báo đẩy nhắc lịch thi đấu.
                    </p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fa-brands fa-youtube"></i></a>
                        <a href="#" class="social-icon"><i class="fa-brands fa-tiktok"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Liên Kết Nhanh</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="index.html">Trang chủ</a></li>
                        <li><a href="courts.html">Tìm kiếm sân</a></li>
                        <li><a href="profile.html">Trang cá nhân</a></li>
                        <li><a href="admin-dashboard.html" class="text-warning fw-bold"><i class="fa-solid fa-gauge-high me-1"></i>Kênh Quản trị Admin</a></li>
                        <li><a href="news.html">Tin tức - Giải đấu</a></li>
                        <li><a href="contact.html">Hỗ trợ khách hàng</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Tính Năng Nổi Bật</h6>
                    <ul class="list-unstyled footer-links">
                        <li><i class="fa-solid fa-bolt text-warning me-2"></i>Lưới Slot Realtime (AJAX)</li>
                        <li><i class="fa-solid fa-shield-halved text-success me-2"></i>Khóa chống đặt trùng (ACID)</li>
                        <li><i class="fa-solid fa-mobile-screen text-info me-2"></i>Cài đặt App PWA</li>
                        <li><i class="fa-solid fa-bell text-primary me-2"></i>Web Push VAPID</li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Hotline Hỗ Trợ</h6>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="icon-circle bg-teal text-white">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div>
                            <div class="small text-muted-light">Tổng đài đặt sân 24/7</div>
                            <div class="fs-5 fw-bold text-white">1900 8888</div>
                        </div>
                    </div>
                    <div class="small text-muted-light">
                        <i class="fa-solid fa-location-dot me-2 text-teal"></i>TP. Hồ Chí Minh, Việt Nam
                    </div>
                </div>
            </div>

            <hr class="border-secondary opacity-25 my-4">

            <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted-light">
                <div>&copy; 2026 GODMINTON - Đồ án môn Lập trình Web & Ứng dụng (Đề tài 5, Nhóm 2).</div>
                <div class="d-flex gap-3 mt-2 mt-md-0">
                    <span class="badge bg-dark border border-secondary text-secondary">Vanilla HTML/JS</span>
                    <span class="badge bg-dark border border-secondary text-secondary">Bootstrap 5.3</span>
                    <span class="badge bg-dark border border-secondary text-secondary">LocalStorage Mock</span>
                </div>
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
    <!-- Mock Data -->
    <script src="assets/js/mock-data.js"></script>
    <!-- App JS -->
    <script src="assets/js/app.js"></script>

    <script src="assets/js/index_explore_dt.js"></script>
    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js"></script>
</body>
</html>





