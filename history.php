<?php
/**
 * history.php — Lịch Sử Đặt Sân Của Bạn
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 * Cập nhật:
 * - Tách partial: navbar.php, bottom-nav.php, footer.php
 * - Dữ liệu mẫu tập trung từ sample_data.php
 * - Chuyển toàn bộ liên kết sang .php
 */

// DỮ LIỆU MẪU - thay bằng truy vấn DB ở tuần 2
require_once __DIR__ . '/includes/sample_data.php';

$currentPage = 'history';
$assetsPath = 'assets';
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lịch Sử Đặt Sân | GODMINTON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/ui-states.css">
</head>
<body>

    <!-- Navbar Partial -->
    <?php include __DIR__ . '/includes/partials/navbar.php'; ?>

    <main class="min-vh-100">
        <div class="container py-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-4 pb-2 border-bottom">
                <div>
                    <h3 class="fw-extrabold mb-1">Lịch Sử Đặt Sân Của Bạn</h3>
                    <p class="text-muted small mb-0">Theo dõi trạng thái đơn đặt sân, thông tin khung giờ và thanh toán cọc</p>
                </div>
                <a href="courts.php" class="btn btn-teal rounded-pill px-4 mt-2 mt-sm-0">
                    <i class="fa-solid fa-plus me-1"></i> Đặt Sân Mới
                </a>
            </div>

            <div id="bookingListContainer">
                <div class="text-center py-5">
                    <div class="spinner-border text-teal" role="status"></div>
                    <div class="mt-2 text-muted">Đang tải lịch sử...</div>
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
    <script src="assets/js/history.js"></script>
    <!-- Bottom Nav Script -->
    <script src="assets/js/bottom-nav.js"></script>
</body>
</html>
