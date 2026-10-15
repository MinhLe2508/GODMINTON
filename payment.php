<?php
header('Cache-Control: no-store, max-age=0');
require_once __DIR__ . '/includes/auth-check.php';
$currentUser = [
    'id' => (int)$_SESSION['user_id'],
    'name' => $_SESSION['name'] ?? '',
    'role' => $_SESSION['role'] ?? ''
];
if ($currentUser !== null) {
    require_once __DIR__ . '/config/db.php';
    $userStmt = $pdo->prepare('SELECT id,name,email,phone,role FROM users WHERE id=?');
    $userStmt->execute([(int)$_SESSION['user_id']]);
    $currentUser = $userStmt->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Đặt Cọc | GODMINTON</title>
    <meta name="description" content="Xác nhận và thanh toán đặt cọc sân cầu lông trực tuyến tại GODMINTON.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/payment_style.css">
    <script>
        window.__GODMINTON_USER__ = <?php echo json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
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

<!-- ===================== STEP BAR ===================== -->
<div class="border-bottom" style="background:var(--bg-surface);">
    <div class="container">
        <div class="step-bar">
            <div class="step-item done">
                <div class="step-dot"><i class="fa-solid fa-check" style="font-size:0.75rem;"></i></div>
                <span class="step-label">Chọn sân</span>
            </div>
            <div class="step-item done">
                <div class="step-dot"><i class="fa-solid fa-check" style="font-size:0.75rem;"></i></div>
                <span class="step-label">Chọn giờ</span>
            </div>
            <div class="step-item active">
                <div class="step-dot">3</div>
                <span class="step-label">Thanh toán</span>
            </div>
            <div class="step-item">
                <div class="step-dot">4</div>
                <span class="step-label">Hoàn tất</span>
            </div>
        </div>
    </div>
</div>

<!-- ===================== MAIN CONTENT ===================== -->
<main class="py-4 min-vh-100">
<div class="container">
<div class="row g-4 justify-content-center">

    <!-- ============ LEFT: FORM ============ -->
    <div class="col-lg-7">

        <!-- 1. Thông tin người đặt -->
        <div class="card border rounded-4 p-4 shadow-sm mb-4">
            <p class="pay-section-title">
                <i class="fa-solid fa-circle-user"></i> Thông Tin Người Đặt
            </p>
            <form id="billingForm" novalidate>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="pay-field">
                            <label class="pay-label">Họ và Tên <span class="text-danger">*</span></label>
                            <input type="text" class="pay-input" id="billingName" placeholder="Nguyễn Văn A" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="pay-field">
                            <label class="pay-label">Số Điện Thoại <span class="text-danger">*</span></label>
                            <input type="tel" class="pay-input" id="billingPhone" placeholder="0912 345 678" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="pay-field mb-0">
                            <label class="pay-label">Email nhận xác nhận <span class="text-danger">*</span></label>
                            <input type="email" class="pay-input" id="billingEmail" placeholder="email@gmail.com" required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="pay-field mb-0">
                            <label class="pay-label">Ghi chú cho sân <span class="text-muted fw-normal">(tuỳ chọn)</span></label>
                            <textarea class="pay-input" id="billingNote" rows="2"
                                placeholder="Vd: Cần thêm cầu, nhờ bật đèn sân trước 15 phút..."></textarea>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- 2. Chọn phương thức thanh toán -->
        <div class="card border rounded-4 p-4 shadow-sm mb-4">
            <p class="pay-section-title">
                <i class="fa-solid fa-wallet"></i> Phương Thức Thanh Toán
            </p>

            <div class="pm-grid" id="pmGrid">

                <!-- VNPay -->
                <button class="pm-btn selected" data-method="vnpay" id="pm-vnpay" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-VNPAY-QR.png"
                             alt="VNPay" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-qrcode\' style=\'color:#005baa; font-size:1.8rem;\'></i>'">
                    </div>
                    <span class="pm-name">VNPay</span>
                </button>

                <!-- MoMo -->
                <button class="pm-btn" data-method="momo" id="pm-momo" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png"
                             alt="MoMo" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-mobile-screen\' style=\'color:#ae2070; font-size:1.8rem;\'></i>'">
                    </div>
                    <span class="pm-name">MoMo</span>
                </button>

                <!-- ZaloPay -->
                <button class="pm-btn" data-method="zalopay" id="pm-zalopay" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png"
                             alt="ZaloPay" onerror="this.parentElement.innerHTML='<i class=\'fa-brands fa-z\' style=\'color:#0068ff; font-size:1.8rem;\'></i>'">
                    </div>
                    <span class="pm-name">ZaloPay</span>
                </button>

                <!-- Chuyển khoản -->
                <button class="pm-btn" data-method="bank" id="pm-bank" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <i class="fa-solid fa-building-columns" style="color:#0d9488; font-size:1.8rem;"></i>
                    </div>
                    <span class="pm-name">Chuyển khoản</span>
                </button>

                <!-- Thẻ QT -->
                <button class="pm-btn" data-method="card" id="pm-card" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap" style="gap:4px;">
                        <i class="fa-brands fa-cc-visa" style="color:#1a1f71; font-size:1.4rem;"></i>
                        <i class="fa-brands fa-cc-mastercard" style="color:#eb001b; font-size:1.4rem;"></i>
                    </div>
                    <span class="pm-name">Thẻ quốc tế</span>
                </button>

                <!-- Tiền mặt -->
                <button class="pm-btn" data-method="cash" id="pm-cash" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <i class="fa-solid fa-money-bills" style="color:#16a34a; font-size:1.8rem;"></i>
                    </div>
                    <span class="pm-name">Tiền mặt tại sân</span>
                </button>

            </div>

            <!-- QR bank transfer details -->
            <div class="bank-panel" id="bankPanel">
                <div class="d-flex gap-3 flex-wrap align-items-start">
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?data=GODMINTON-BANK&size=120x120&bgcolor=ffffff&color=0d9488"
                             alt="QR" onerror="this.style.padding='1rem'; this.alt='QR Code';">
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-bold small mb-2">Thông tin chuyển khoản</div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0;">Ngân hàng</span>
                            <strong>Vietcombank (VCB)</strong>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0;">Số TK</span>
                            <strong>1234 5678 9012</strong>
                            <button class="copy-btn" onclick="copyText('1234567890 12')">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0;">Chủ TK</span>
                            <strong>GODMINTON JSC</strong>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0;">Nội dung</span>
                            <strong id="transferRef" style="word-break:break-all;">DATSAN-...</strong>
                            <button class="copy-btn" onclick="copyTransferRef()">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="mt-2 d-flex align-items-start gap-1" style="font-size:0.73rem; color:var(--text-secondary);">
                            <i class="fa-solid fa-circle-exclamation text-warning mt-1 flex-shrink-0"></i>
                            <span>Chuyển đúng nội dung để hệ thống tự xác nhận. Số tiền: <strong class="text-warning" id="bankAmountText">--</strong></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card input -->
            <div class="card-panel" id="cardPanel">
                <div class="row g-2">
                    <div class="col-12">
                        <div class="pay-field">
                            <label class="pay-label">Số thẻ</label>
                            <div class="card-number-wrap">
                                <input type="text" class="pay-input" id="cardNumber"
                                    placeholder="1234 5678 9012 3456" maxlength="19"
                                    oninput="formatCard(this)">
                                <div class="card-scheme-icons">
                                    <i class="fa-brands fa-cc-visa" style="color:#1a1f71;"></i>
                                    <i class="fa-brands fa-cc-mastercard" style="color:#eb001b;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="pay-field mb-0">
                            <label class="pay-label">Hết hạn (MM/YY)</label>
                            <input type="text" class="pay-input" placeholder="09/28" maxlength="5"
                                oninput="formatExpiry(this)">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="pay-field mb-0">
                            <label class="pay-label">Mã CVV</label>
                            <input type="text" class="pay-input" placeholder="123" maxlength="4">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="pay-field mb-0">
                            <label class="pay-label">Tên in trên thẻ</label>
                            <input type="text" class="pay-input" placeholder="NGUYEN VAN AN"
                                style="text-transform:uppercase; letter-spacing:1px;">
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- 3. Điều khoản -->
        <div class="d-flex align-items-start gap-2 mb-4">
            <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" id="agreeTerms" required style="min-width:16px;">
            <label for="agreeTerms" style="font-size:0.82rem; cursor:pointer; color:var(--text-secondary); line-height:1.5;">
                Tôi đã đọc và đồng ý với
                <a href="#" style="color:var(--brand-teal); text-decoration:none;">Điều khoản đặt sân</a>
                của GODMINTON. Khoản đặt cọc <strong>không hoàn lại</strong> nếu huỷ trước 2 giờ thi đấu.
            </label>
        </div>

        <!-- Mobile: CTA button (bên dưới form trên mobile) -->
        <div class="d-lg-none">
            <button class="btn-pay-cta mb-3" id="btnPayMobile" onclick="handlePayment()">
                <i class="fa-solid fa-shield-halved"></i>
                <span id="btnPayMobileText">Xác Nhận & Đặt Cọc</span>
            </button>
            <div class="trust-badges mb-4">
                <span class="trust-badge"><i class="fa-solid fa-lock"></i> SSL 256-bit</span>
                <span class="trust-badge"><i class="fa-solid fa-shield-check"></i> PCI DSS</span>
                <span class="trust-badge"><i class="fa-solid fa-rotate-left"></i> Hoàn tiền 24h</span>
            </div>
        </div>

    </div><!-- /col-lg-7 -->

    <!-- ============ RIGHT: ORDER SUMMARY ============ -->
    <div class="col-lg-5">
        <div class="sticky-top" style="top: 72px;">

            <!-- Countdown -->
            <div class="countdown-wrap">
                <div>
                    <div style="font-size:0.72rem; font-weight:700; color:var(--text-secondary);">
                        <i class="fa-regular fa-clock me-1"></i>Phiên giữ chỗ còn
                    </div>
                </div>
                <div class="countdown-timer" id="countdownEl">10:00</div>
            </div>

            <!-- Order card -->
            <div class="order-wrap mb-3">

                <!-- Hero image -->
                <div class="order-hero">
                    <img id="courtHeroImg"
                         src="https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=700&auto=format&fit=crop"
                         alt="Sân cầu lông">
                    <div class="order-hero-overlay">
                        <div class="text-white">
                            <div class="fw-extrabold" id="courtNameEl" style="font-size:0.95rem; text-shadow:0 1px 4px rgba(0,0,0,0.5);">
                                Sân Cầu Giấy Arena
                            </div>
                            <div style="font-size:0.72rem; opacity:0.85;" id="courtAddrEl">
                                <i class="fa-solid fa-location-dot me-1"></i>346A Lê Hồng Phong, Cầu Giấy
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Booking details -->
                <div class="order-body">
                    <div class="mb-2">
                        <div class="bdr">
                            <span class="bdr-label"><i class="fa-solid fa-calendar-day me-1 text-teal" style="color:var(--brand-teal);"></i>Ngày</span>
                            <span class="bdr-value" id="bdrDate">Thứ 7, 20/09/2026</span>
                        </div>
                        <div class="bdr">
                            <span class="bdr-label"><i class="fa-regular fa-clock me-1" style="color:var(--brand-teal);"></i>Khung giờ</span>
                            <span class="bdr-value" id="bdrTime">07:00 – 08:30</span>
                        </div>
                        <div class="bdr">
                            <span class="bdr-label"><i class="fa-solid fa-hourglass-half me-1" style="color:var(--brand-teal);"></i>Thời lượng</span>
                            <span class="bdr-value" id="bdrDuration">1.5 giờ</span>
                        </div>
                        <div class="bdr">
                            <span class="bdr-label"><i class="fa-solid fa-layer-group me-1" style="color:var(--brand-teal);"></i>Số sân</span>
                            <span class="bdr-value" id="bdrCourt">Sân 2</span>
                        </div>
                    </div>

                    <!-- Price breakdown -->
                    <div class="price-section">
                        <div class="prow subtotal">
                            <span>Giá thuê sân</span>
                            <span id="prBase">--</span>
                        </div>
                        <div class="prow subtotal">
                            <span>Phí dịch vụ (5%)</span>
                            <span id="prFee">--</span>
                        </div>
                        <div class="prow total-row">
                            <span>Tổng cộng</span>
                            <span class="prow-val" id="prTotal">--</span>
                        </div>
                        <div class="prow deposit-row">
                            <div>
                                <div class="fw-extrabold" style="font-size:0.85rem;">Đặt cọc ngay
                                    <span class="badge ms-1" style="background:rgba(245,158,11,0.15); color:#f59e0b; font-size:0.62rem;">50%</span>
                                </div>
                                <div style="font-size:0.7rem; color:var(--text-secondary);">Còn lại thanh toán tại sân</div>
                            </div>
                            <span class="prow-val" id="prDeposit">--</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop CTA -->
            <div class="d-none d-lg-block">
                <button class="btn-pay-cta mb-3" id="btnPayDesktop" onclick="handlePayment()">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span id="btnPayDesktopText">Xác Nhận & Đặt Cọc</span>
                </button>
                <div class="trust-badges mb-3">
                    <span class="trust-badge"><i class="fa-solid fa-lock"></i> SSL 256-bit</span>
                    <span class="trust-badge"><i class="fa-solid fa-shield-check"></i> PCI DSS</span>
                    <span class="trust-badge"><i class="fa-solid fa-rotate-left"></i> Hoàn tiền 24h</span>
                </div>
            </div>

            <!-- Back link -->
            <div class="text-center">
                <a href="booking.php" style="font-size:0.8rem; color:var(--text-secondary); text-decoration:none;">
                    <i class="fa-solid fa-arrow-left me-1"></i>Quay lại chọn khung giờ
                </a>
            </div>

        </div><!-- /sticky -->
    </div><!-- /col-lg-5 -->

</div><!-- /row -->
</div><!-- /container -->
</main>

<!-- ===================== SUCCESS OVERLAY ===================== -->
<div class="success-overlay" id="successOverlay">
    <div class="success-inner">
        <div class="success-ring"><i class="fa-solid fa-check"></i></div>

        <h3 class="fw-extrabold mb-1">Đặt Cọc Thành Công!</h3>
        <p class="text-muted mb-3" style="font-size:0.88rem;">
            Sân của bạn đã được giữ chỗ. Mã đặt lịch:
        </p>

        <div class="booking-code mb-4" id="successCode">GDM-000000</div>

        <div class="success-detail-card mb-4 mx-auto" style="max-width:360px;">
            <div class="sdrow"><span class="text-muted">Sân</span><strong id="sdCourt">--</strong></div>
            <div class="sdrow"><span class="text-muted">Ngày</span><strong id="sdDate">--</strong></div>
            <div class="sdrow"><span class="text-muted">Giờ</span><strong id="sdTime">--</strong></div>
            <div class="sdrow"><span class="text-muted">Đã cọc</span><strong class="text-teal" style="color:var(--brand-teal);" id="sdDeposit">--</strong></div>
            <div class="sdrow"><span class="text-muted">Phương thức</span><strong id="sdMethod">--</strong></div>
        </div>

        <p class="small text-muted mb-4">
            <i class="fa-solid fa-envelope me-1" style="color:var(--brand-teal);"></i>
            Xác nhận đã gửi đến <strong id="sdEmail">--</strong>
        </p>

        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="history.php" class="btn btn-teal rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-calendar-check me-1"></i>Xem lịch đặt
            </a>
            <a href="courts.php" class="btn btn-outline-secondary rounded-pill px-4">
                Đặt sân khác
            </a>
        </div>
    </div>
</div>

<!-- ===================== FOOTER ===================== -->
<footer class="footer-custom mt-5 pt-4 pb-4">
    <div class="container">
        <div class="small text-muted-light text-center">
            &copy; 2026 GODMINTON — Đồ án Lập trình Web & Ứng dụng.
        </div>
    </div>
</footer>

<!-- ===================== SCRIPTS ===================== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/booking-account-client.js?v=<?= filemtime(__DIR__ . '/assets/js/booking-account-client.js') ?>"></script>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script src="assets/js/payment.js?v=<?= filemtime(__DIR__ . '/assets/js/payment.js') ?>"></script>
<!-- Bottom Nav -->
<script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
</body>
</html>
