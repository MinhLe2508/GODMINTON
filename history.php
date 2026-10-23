<?php header('Cache-Control: no-store, max-age=0'); ?>
<?php require_once __DIR__ . '/includes/auth-check.php'; ?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đặt Sân | GODMINTON</title>
<?php include_once __DIR__ . '/includes/partials/pwa-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(__DIR__ . '/assets/css/swal-godminton.css') ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        main {
            padding-bottom: calc(75px + env(safe-area-inset-bottom, 0px)) !important;
        }
        #bookingListContainer .card {
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        @media (max-width: 576px) {
            #bookingListContainer .card {
                padding: 1.15rem 1rem !important;
                border-radius: 1rem !important;
            }
            .header-history-actions .btn {
                padding: 0.45rem 0.85rem !important;
                font-size: 0.85rem !important;
            }
        }
        @media (max-width: 380px) {
            h3.fw-extrabold {
                font-size: 1.25rem !important;
            }
            #bookingListContainer .card {
                padding: 1rem 0.85rem !important;
            }
            .header-history-actions .btn {
                padding: 0.38rem 0.65rem !important;
                font-size: 0.78rem !important;
            }
        }
    </style>
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
        <div class="container py-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-2 border-bottom">
                <div>
                    <h3 class="fw-extrabold mb-1">Lịch Sử Đặt Sân Của Bạn</h3>
                    <p class="text-muted small mb-0">Theo dõi trạng thái đơn đặt sân, thông tin khung giờ và thanh toán
                    </p>
                </div>
                <div class="header-history-actions d-flex align-items-center gap-2 flex-wrap mt-2 mt-sm-0">
                    <a href="courts.php" class="btn btn-teal rounded-pill px-3 fw-bold">
                        <i class="fa-solid fa-plus me-1"></i> Đặt Sân Mới
                    </a>
                    <a href="auth.php" class="btn btn-switch-account rounded-pill px-3 fw-semibold">
                        <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Đổi tài khoản
                    </a>
                    <button type="button" id="historyLogout" class="btn btn-logout-account rounded-pill px-3 fw-semibold">
                        <i class="fa-solid fa-right-from-bracket me-1"></i> Đăng xuất
                    </button>
                </div>
            </div>

            <div id="bookingListContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-teal" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải lịch sử...</div>
                </div>
            </div>
        </div>
    </main>

    <footer class="footer-custom mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="mb-3"><img src="assets/img/logo.svg" alt="GODMINTON"
                            style="height:38px; width:auto;">
                    </div>
                    <p class="text-muted-light">Nền tảng đặt sân cầu lông trực tuyến thông minh.</p>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/swal-godminton.js?v=<?= filemtime(__DIR__ . '/assets/js/swal-godminton.js') ?>"></script>
    <script src="assets/js/booking-account-client.js?v=<?= filemtime(__DIR__ . '/assets/js/booking-account-client.js') ?>"></script>
    <script src="assets/js/push-client.js?v=<?= filemtime(__DIR__ . '/assets/js/push-client.js') ?>"></script>
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <script src="assets/js/history.js?v=<?= filemtime(__DIR__ . '/assets/js/history.js') ?>"></script>
    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
</body>

</html>

