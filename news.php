<?php header('Cache-Control: no-store, max-age=0'); ?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tin Tức & Cẩm Nang Cầu Lông | GODMINTON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
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
        <div class="mb-4 pb-2 border-bottom">
            <h2 class="fw-extrabold mb-1">Cẩm Nang & Tin Tức Cầu Lông</h2>
            <p class="text-muted small mb-0">Cập nhật tin tức giải đấu, kỹ thuật thi đấu và mẹo chọn dụng cụ từ chuyên gia</p>
        </div>

        <div class="row g-4" id="newsContainer">
            <div class="text-center py-5 col-12">
                <div class="spinner-border text-teal" role="status"></div>
            </div>
        </div>
    </div>
    </main>

    <footer class="footer-custom mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="mb-3"><img src="assets/img/logo.svg" alt="GODMINTON" style="height:38px; width:auto;"></div>
                    <p class="text-muted-light">Nền tảng đặt sân cầu lông trực tuyến thông minh.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-icon"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-icon"><i class="fa-brands fa-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Liên Kết Nhanh</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="index.php">Trang chủ</a></li>
                        <li><a href="courts.php">Tìm kiếm sân</a></li>
                        <li><a href="contact.php">Hỗ trợ</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary opacity-25 my-4">
            <div class="small text-muted-light">&copy; 2026 GODMINTON - Đồ án Lập trình Web.</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <script src="assets/js/content-client.js?v=<?= filemtime(__DIR__ . '/assets/js/content-client.js') ?>"></script>
    <script src="assets/js/news.js?v=<?= filemtime(__DIR__ . '/assets/js/news.js') ?>"></script>
    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
</body>
</html>





