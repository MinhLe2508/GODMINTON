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
    require_once __DIR__ . '/includes/membership-service.php';
    $userStmt = $pdo->prepare('SELECT id,name,email,phone,role FROM users WHERE id=?');
    $userStmt->execute([(int)$_SESSION['user_id']]);
    $currentUser = $userStmt->fetch() ?: null;
    if ($currentUser) {
        $currentUser['membership'] = membership_get_active_for_user($pdo, (int)$_SESSION['user_id']);
    }
}
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh Toán Đặt Cọc | GODMINTON</title>
    <meta name="description" content="Xác nhận và thanh toán đặt cọc sân cầu lông trực tuyến tại GODMINTON.">
<?php include_once __DIR__ . '/includes/partials/pwa-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(__DIR__ . '/assets/css/swal-godminton.css') ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/payment_style.css?v=<?= filemtime(__DIR__ . '/assets/css/payment_style.css') ?>">
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
                    <div class="qr-box" onclick="openQrModal()" title="Nhấn để phóng to mã QR">
                        <img src="assets/img/vietqr-payment.png?v=<?= filemtime(__DIR__ . '/assets/img/vietqr-payment.png') ?>"
                             alt="VietQR MB Bank - TRUONG MINH TRIET"
                             onerror="this.src='assets/img/vietqr-card.png?v=<?= filemtime(__DIR__ . '/assets/img/vietqr-card.png') ?>';">
                        <div class="qr-zoom-hint">
                            <i class="fa-solid fa-magnifying-glass-plus"></i> Phóng to
                        </div>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small">Thông tin chuyển khoản VietQR</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1" style="font-size:0.7rem;">
                                <i class="fa-solid fa-bolt me-1"></i>Tự động 24/7
                            </span>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:65px;">Ngân hàng</span>
                            <strong>MB Bank (Ngân hàng Quân Đội)</strong>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:65px;">Số TK</span>
                            <strong id="bankAccNum">0783547289</strong>
                            <button class="copy-btn" onclick="copyText('0783547289')">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:65px;">Chủ TK</span>
                            <strong>TRUONG MINH TRIET</strong>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:65px;">Nội dung</span>
                            <strong id="transferRef" style="word-break:break-all;">DATSAN-...</strong>
                            <button class="copy-btn" onclick="copyTransferRef()">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="mt-2 d-flex align-items-start gap-1" style="font-size:0.73rem; color:var(--text-secondary);">
                            <i class="fa-solid fa-circle-exclamation text-warning mt-1 flex-shrink-0"></i>
                            <span>Chuyển đúng nội dung để hệ thống tự xác nhận. Số tiền: <strong class="text-warning" id="bankAmountText">--</strong></span>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill" onclick="openQrModal()" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                <i class="fa-solid fa-expand me-1"></i> Xem QR cỡ lớn
                            </button>
                            <a href="assets/img/vietqr-payment.png" download="VietQR-GODMINTON.png" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                <i class="fa-solid fa-download me-1"></i> Tải ảnh QR
                            </a>
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
            <button class="btn-pay-cta mb-4" id="btnPayMobile" onclick="handlePayment()">
                <span id="btnPayMobileText">Xác Nhận & Đặt Cọc</span>
            </button>
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

                    <!-- Membership VIP Banner -->
                    <div id="memberVipBanner" class="alert d-flex align-items-center gap-2 mb-3 py-2 px-3 border-0 rounded-3 shadow-sm" style="display:none;">
                        <i id="memberBannerIcon" class="fa-solid fa-crown fs-5"></i>
                        <div id="memberBannerText" style="font-size:0.82rem; line-height:1.35;">
                            <strong>Đặc quyền Hội viên <span id="memberBannerTier" class="text-uppercase fw-bold"></span></strong>: Tự động giảm <strong id="memberBannerPercent" class="text-danger">0%</strong> toàn bộ tiền sân!
                        </div>
                    </div>

                    <!-- Voucher input box -->
                    <div class="voucher-box">
                        <label for="voucherCodeInput" class="voucher-box-title">
                            <i class="fa-solid fa-ticket"></i>
                            <span>Mã giảm giá & Ưu đãi</span>
                        </label>
                        <div class="input-group">
                            <input type="text"
                                   class="form-control text-uppercase"
                                   id="voucherCodeInput"
                                   placeholder="VD: WELCOME15"
                                   maxlength="50"
                                   autocomplete="off"
                                   aria-describedby="voucherError">
                            <button class="btn btn-teal px-3" type="button" id="btnApplyVoucher">
                                <span id="btnApplyVoucherText">Áp dụng</span>
                                <span id="btnApplyVoucherSpinner" class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true" style="display:none;"></span>
                            </button>
                            <button class="btn btn-outline-danger" type="button" id="btnRemoveVoucher" style="display:none;" title="Bỏ mã giảm giá">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <div id="voucherError" role="alert" class="text-danger small mt-1 fw-medium" style="display:none; font-size:0.78rem;"></div>
                        <div id="voucherSuccess" class="text-success small mt-1 fw-semibold" style="display:none; font-size:0.78rem;">
                            <i class="fa-solid fa-circle-check me-1"></i><span id="voucherSuccessText"></span>
                        </div>
                    </div>

                    <!-- Price breakdown -->
                    <div class="price-section">
                        <div class="prow subtotal">
                            <span>Giá thuê sân</span>
                            <span id="prBase">--</span>
                        </div>
                        <!-- Membership discount row -->
                        <div class="prow subtotal" id="prowMembership" style="display:none;">
                            <span id="prMemberLabel"><i class="fa-solid fa-crown me-1 text-warning"></i>Ưu đãi hội viên (<span id="prMemberPercent">0%</span>)</span>
                            <span id="prMemberDiscount" class="fw-bold">-0 đ</span>
                        </div>
                        <div class="prow subtotal text-success" id="prowDiscount" style="display:none;">
                            <span><i class="fa-solid fa-tag me-1"></i>Mã giảm giá (<span id="prDiscountPercent">0%</span>)</span>
                            <span id="prDiscount" class="fw-bold">-0 đ</span>
                        </div>
                        <div class="prow subtotal">
                            <span id="prFeeLabel">Phí dịch vụ</span>
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
                            </div>
                            <span class="prow-val" id="prDeposit">--</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop CTA -->
            <div class="d-none d-lg-block">
                <button class="btn-pay-cta mb-3" id="btnPayDesktop" onclick="handlePayment()">
                    <span id="btnPayDesktopText">Xác Nhận & Đặt Cọc</span>
                </button>
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

<!-- ===================== SUCCESS SCREEN ===================== -->
<section class="success-overlay" id="successOverlay" aria-labelledby="successTitle" aria-hidden="true" tabindex="-1" inert>
    <div class="success-content">
        <div class="success-inner">
            <div class="success-ring" aria-hidden="true"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="m5 12 4 4L19 6"/></svg></div>
            <p class="success-eyebrow">GODMINTON · XÁC NHẬN ĐẶT SÂN</p>
            <h1 class="success-heading" id="successTitle">Đặt cọc thành công!</h1>
            <p class="success-description">Thông tin đặt sân đã được lưu. Bạn có thể xem lại đơn trong lịch sử đặt sân.</p>

            <div class="success-code-box">
                <span class="success-code-label">Mã đặt sân</span>
                <strong class="booking-code" id="successCode">—</strong>
            </div>

            <div class="success-detail-card">
                <div class="sdrow"><span class="text-muted">Sân cầu lông</span><strong id="sdCourt">—</strong></div>
                <div class="sdrow"><span class="text-muted">Ngày chơi</span><strong id="sdDate">—</strong></div>
                <div class="sdrow"><span class="text-muted">Khung giờ</span><strong id="sdTime">—</strong></div>
                <div class="sdrow success-deposit-row"><span>Tiền đặt cọc</span><strong id="sdDeposit">—</strong></div>
                <div class="sdrow"><span class="text-muted">Phương thức</span><strong id="sdMethod">—</strong></div>
            </div>
            <p class="success-contact"><i class="fa-regular fa-envelope" aria-hidden="true"></i> Email liên hệ: <strong id="sdEmail">—</strong></p>
            <p class="success-demo-note" id="successPaymentNote" hidden>Giao dịch đang ở chế độ mô phỏng, chưa xác nhận thu tiền thực tế.</p>
            <div class="success-actions">
                <a href="history.php" class="btn btn-teal rounded-pill fw-bold"><i class="fa-solid fa-calendar-check me-2" aria-hidden="true"></i>Xem lịch đặt</a>
                <a href="courts.php" class="btn btn-outline-secondary rounded-pill">Đặt sân khác <i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i></a>
            </div>
        </div>
    </div>
    <footer class="success-footer">&copy; 2026 GODMINTON · Đặt sân cầu lông trực tuyến</footer>
</section>

<!-- Modal Phóng to mã VietQR MB Bank -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-3 px-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="qrModalLabel">
                    <i class="fa-solid fa-qrcode text-teal"></i> Quét Mã VietQR MB Bank
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3">
                <div class="qr-modal-img-wrap rounded-3 overflow-hidden shadow-sm bg-white p-2 mb-3">
                    <img src="assets/img/vietqr-payment.png?v=<?= filemtime(__DIR__ . '/assets/img/vietqr-payment.png') ?>" alt="Mã VietQR" class="img-fluid rounded-3" style="max-height: 520px; width: auto; object-fit: contain;">
                </div>
                <div class="bg-light rounded-3 p-3 text-start small mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Chủ TK:</span>
                        <span class="fw-bold">TRUONG MINH TRIET</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Số TK:</span>
                        <span class="fw-bold font-monospace text-teal">0783547289</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Ngân hàng:</span>
                        <span class="fw-bold">MB Bank (Quân Đội)</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Nội dung:</span>
                        <span class="fw-bold font-monospace text-warning" id="modalTransferRef">DATSAN-...</span>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <a href="assets/img/vietqr-payment.png" download="VietQR-GODMINTON.png" class="btn btn-teal rounded-pill px-3 py-1 text-sm">
                        <i class="fa-solid fa-download me-1"></i> Tải ảnh QR
                    </a>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-1 text-sm" data-bs-dismiss="modal">
                        Đóng
                    </button>
                </div>
            </div>
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
<script src="assets/js/swal-godminton.js?v=<?= filemtime(__DIR__ . '/assets/js/swal-godminton.js') ?>"></script>
<script src="assets/js/booking-account-client.js?v=<?= filemtime(__DIR__ . '/assets/js/booking-account-client.js') ?>"></script>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script src="assets/js/payment.js?v=<?= filemtime(__DIR__ . '/assets/js/payment.js') ?>"></script>
</body>
</html>
