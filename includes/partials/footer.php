<?php
/**
 * includes/partials/footer.php
 * Chân trang dùng chung (Footer) của GODMINTON.
 * Đã gỡ bỏ: social icons (Facebook), liên kết News / Contact theo yêu cầu Task #6.
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 */
$assetsPath = $assetsPath ?? 'assets';
?>
<!-- Footer -->
<footer class="footer-custom mt-5 pt-5 pb-4">
    <div class="container">
        <div class="row g-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="mb-3">
                    <img src="<?= htmlspecialchars($assetsPath) ?>/img/logo.svg" alt="GODMINTON" style="height: 38px; width: auto;">
                </div>
                <p class="text-muted-light">
                    Nền tảng đặt sân cầu lông trực tuyến thông minh với cập nhật trạng thái thời gian thực, hỗ trợ kiểm tra slot trống và giữ chỗ tự động.
                </p>
                <div class="text-muted-light small">
                    <i class="fa-solid fa-shield-halved text-teal me-1"></i> Hệ thống vận hành minh bạch & tin cậy
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h6 class="text-white fw-bold mb-3">Liên Kết Nhanh</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="index.php">Trang chủ</a></li>
                    <li><a href="courts.php">Tìm kiếm sân</a></li>
                    <li><a href="history.php">Lịch sử đặt sân</a></li>
                    <li><a href="auth.php">Đăng nhập / Đăng ký</a></li>
                    <li><a href="admin-dashboard.php" class="text-warning fw-bold"><i class="fa-solid fa-gauge-high me-1"></i>Kênh Quản trị</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h6 class="text-white fw-bold mb-3">Tính Năng Nổi Bật</h6>
                <ul class="list-unstyled footer-links">
                    <li><i class="fa-solid fa-bolt text-warning me-2"></i>Lưới Slot Realtime</li>
                    <li><i class="fa-solid fa-shield-halved text-success me-2"></i>Khóa chống đặt trùng</li>
                    <li><i class="fa-solid fa-clock-rotate-left text-info me-2"></i>Giữ chỗ 5 phút chuẩn xác</li>
                    <li><i class="fa-solid fa-qrcode text-primary me-2"></i>Thanh toán cọc QR tiện lợi</li>
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
                <span class="badge bg-dark border border-secondary text-secondary">PHP 8.2</span>
                <span class="badge bg-dark border border-secondary text-secondary">Bootstrap 5.3</span>
                <span class="badge bg-dark border border-secondary text-secondary">MySQL 8.0</span>
            </div>
        </div>
    </div>
</footer>
