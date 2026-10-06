<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt Sân Real-time | GODMINTON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/booking_style.css">
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

    <main class="min-vh-100 pb-5">
    <div class="container-fluid px-3 px-lg-4 py-4">

        <!-- ===== COURT INFO HEADER ===== -->
        <div class="booking-toolbar mb-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="brand-logo-badge" style="border-radius:10px; width:44px; height:44px; font-size:1.1rem;">
                    <i class="fa-solid fa-layer-group"></i>
                </div>
                <div>
                    <div class="d-flex align-items-center gap-2">
                        <h5 class="fw-extrabold mb-0" id="courtNameTitle">Cầu Lông Quận 1 – Lê Thánh Tôn Sport</h5>
                        <span class="badge bg-success bg-opacity-10 text-success" style="font-size:0.7rem;">
                            <i class="fa-solid fa-circle" style="font-size:0.5rem;"></i> Đang mở cửa
                        </span>
                    </div>
                    <div class="small text-muted">
                        <i class="fa-solid fa-map-pin text-teal me-1"></i>Số 12 Lê Thánh Tôn, Quận 1, TP.HCM
                        <span class="mx-2 opacity-50">|</span>
                        <i class="fa-regular fa-clock text-teal me-1"></i>06:00 – 23:00
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="court-detail.html?id=1" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                    <i class="fa-solid fa-circle-info me-1"></i>Thông tin sân
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-teal btn-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-shuffle me-1"></i>Đổi sân
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                        <li><a class="dropdown-item" href="#">Quận 1 –  Lê Thánh Tôn Sport</a></li>
                        <li><a class="dropdown-item" href="#">Quận 3 – Nguyễn Đình Chiểu Arena</a></li>
                        <li><a class="dropdown-item" href="#">Quận 5 – Nguyễn Trãi Badminton</a></li>
                        <li><a class="dropdown-item" href="#">Quận 10 – Sư Vạn Hạnh Court</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- ===== DATE SELECTOR + LEGEND ===== -->
        <div class="booking-toolbar mb-3">
            <div class="row align-items-center g-3">
                <!-- Date pills -->
                <div class="col-lg-7">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <button class="btn btn-icon border rounded-circle" id="btnPrevWeek" style="width:32px;height:32px;">
                            <i class="fa-solid fa-chevron-left" style="font-size:0.75rem;"></i>
                        </button>
                        <span class="fw-bold small text-muted" id="weekRangeLabel">17/09 – 23/09/2026</span>
                        <button class="btn btn-icon border rounded-circle" id="btnNextWeek" style="width:32px;height:32px;">
                            <i class="fa-solid fa-chevron-right" style="font-size:0.75rem;"></i>
                        </button>
                    </div>
                    <div class="date-nav-scroll" id="datePillRow">
                        <!-- Rendered by JS -->
                    </div>
                </div>

                <!-- Legend -->
                <div class="col-lg-5">
                    <div class="legend-bar justify-content-lg-end">
                        <div class="legend-chip">
                            <span class="legend-swatch" style="background:#e2e8f0; border:1.5px solid #cbd5e1;"></span>Trống
                        </div>
                        <div class="legend-chip">
                            <span class="legend-swatch" style="background:#ef4444;"></span>Đã đặt
                        </div>
                        <div class="legend-chip">
                            <span class="legend-swatch" style="background:#334155;"></span>Khoá / Đóng
                        </div>
                        <div class="legend-chip">
                            <span class="legend-swatch" style="background:#ec4899;"></span>Sự kiện
                        </div>
                        <div class="legend-chip">
                            <span class="legend-swatch" style="background:#0d9488; border:1.5px solid #0d9488;"></span>Bạn giữ
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== COURT FILTER TABS ===== -->
        <div class="booking-toolbar mb-3 d-flex align-items-center justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small fw-bold text-muted">Hiển thị sân:</span>
                <div class="court-tabs" id="courtTabsRow">
                    <button class="court-tab-btn active" data-court="all">Tất cả</button>
                    <button class="court-tab-btn" data-court="0">Thảm A1</button>
                    <button class="court-tab-btn" data-court="1">Thảm A2</button>
                    <button class="court-tab-btn" data-court="2">Thảm A3</button>
                    <button class="court-tab-btn" data-court="3">Thảm A4</button>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-teal-subtle text-teal">
                    <i class="fa-solid fa-sync fa-spin me-1" style="font-size:0.65rem;"></i>Cập nhật mỗi 5s
                </span>
                <input type="date" id="bookingDateInput" class="form-control form-control-sm rounded-3" style="width:155px;">
            </div>
        </div>

        <!-- ===== CALENDAR GRID ===== -->
        <div class="cal-wrapper mb-4" style="position:relative;">

            <!-- Scroll arrows -->
            <button class="scroll-arrow scroll-arrow-left d-none d-lg-flex" id="scrollLeft">
                <i class="fa-solid fa-chevron-left" style="font-size:0.75rem;"></i>
            </button>
            <button class="scroll-arrow scroll-arrow-right d-none d-lg-flex" id="scrollRight">
                <i class="fa-solid fa-chevron-right" style="font-size:0.75rem;"></i>
            </button>

            <div class="cal-scroll-container" id="calScrollContainer">
                <table class="cal-table" id="calGrid">
                    <!-- Rendered by JS -->
                </table>
            </div>
        </div>

        <!-- ===== BOOKING SUMMARY BAR ===== -->
        <div class="booking-summary-bar" id="bookingSummaryBar" style="display:none !important;">
            <div class="d-flex align-items-center gap-3">
                <i class="fa-solid fa-cart-shopping fs-4"></i>
                <div>
                    <div class="fw-bold" id="summaryTitle">Thảm A1 • 17:00 – 18:00</div>
                    <div class="small opacity-85" id="summaryDetail">90.000 đ • Đặt cọc 50.000 đ</div>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-light btn-sm rounded-pill px-3 text-teal fw-bold" id="btnCancelSel">
                    <i class="fa-solid fa-xmark me-1"></i>Huỷ chọn
                </button>
                <a id="btnGoPayment" href="payment.html" class="btn btn-light fw-bold rounded-pill px-4" style="color:var(--brand-teal);">
                    <i class="fa-solid fa-lock me-2"></i>Đặt Cọc & Thanh Toán
                </a>
            </div>
        </div>

        <!-- ===== GUIDE ===== -->
        <div class="mt-3 p-3 rounded-3 bg-body-tertiary border small text-muted text-center">
            <i class="fa-solid fa-hand-pointer text-teal me-1"></i>
            Click vào ô <strong>trắng/xám</strong> để chọn khung giờ trống •
            Kéo qua nhiều ô để chọn nhiều giờ liên tiếp (tính năng drag sẽ được bổ sung)
        </div>

    </div><!-- /container -->
    </main>

    <!-- Footer minimal -->
    <footer class="footer-custom py-3">
        <div class="container-fluid px-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center small text-muted-light">
                <div>&copy; 2026 GODMINTON - Đồ án Lập trình Web.</div>
                <div class="d-flex gap-3 mt-1 mt-md-0">
                    <span class="badge bg-dark border border-secondary text-secondary">Bootstrap 5.3</span>
                    <span class="badge bg-dark border border-secondary text-secondary">LocalStorage Mock</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/mock-data.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/calendar_booking.js"></script>
    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js"></script>
</body>
</html>





