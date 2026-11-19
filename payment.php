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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/payment_style.css?v=<?= filemtime(__DIR__ . '/assets/css/payment_style.css') ?>">
    <script>
        window.__GODMINTON_USER__ = <?php echo json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
</head>
<body class="payment-page">

<!-- App Brand Header -->
<header class="app-brand-head">
    <div class="container text-center">
        <a href="index.php" class="brand-logo-link" aria-label="GODMINTON Trang chủ">
            <img src="assets/img/logo-teal.svg" alt="GODMINTON" class="brand-logo-img">
        </a>
    </div>
    <button class="bnav-bell-float" id="pushBellToggle" type="button" aria-label="Bật / Tắt thông báo đặt sân" title="Bật / Tắt thông báo đặt sân">
        <i class="fa-regular fa-bell"></i>
    </button>
    <button class="bnav-theme-float" id="themeToggle" type="button" aria-label="Chuyển chế độ Sáng / Tối" title="Chuyển chế độ Sáng / Tối"></button>
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

    <!-- ============ LEFT: FORM (Desktop: Left, Mobile: Bottom) ============ -->
    <div class="col-lg-7 order-2 order-lg-1">

        <!-- 1. Thông tin người đặt -->
        <div class="card border rounded-4 p-3 p-sm-4 shadow-sm mb-4">
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
        <div class="card border rounded-4 p-3 p-sm-4 shadow-sm mb-4">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <p class="pay-section-title mb-0">
                    <i class="fa-solid fa-wallet"></i> Phương Thức Thanh Toán
                </p>
                <span class="pm-verified-pill" id="onlineVerifiedBadge" style="display:none;">
                    <i class="fa-solid fa-circle-check me-1"></i>Đã xác thực trực tuyến
                </span>
            </div>

            <div class="pm-grid" id="pmGrid">

                <!-- Chuyển khoản (Mặc định) -->
                <button type="button" class="pm-btn selected" data-method="bank" id="pm-bank" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <i class="fa-solid fa-building-columns" style="color:#0d9488; font-size:1.8rem;"></i>
                    </div>
                    <span class="pm-name">Chuyển khoản</span>
                    <span class="pm-sub">VietQR 24/7</span>
                </button>

                <!-- VNPay -->
                <button type="button" class="pm-btn" data-method="vnpay" id="pm-vnpay" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-VNPAY-QR.png"
                             alt="VNPay" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-qrcode\' style=\'color:#005baa; font-size:1.8rem;\'></i>'">
                    </div>
                    <span class="pm-name">VNPay</span>
                    <span class="pm-sub">VNPAY-QR / ATM</span>
                </button>

                <!-- MoMo -->
                <button type="button" class="pm-btn" data-method="momo" id="pm-momo" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <img src="https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png"
                             alt="MoMo" onerror="this.parentElement.innerHTML='<i class=\'fa-solid fa-mobile-screen\' style=\'color:#ae2070; font-size:1.8rem;\'></i>'">
                    </div>
                    <span class="pm-name">MoMo</span>
                    <span class="pm-sub">Ví điện tử MoMo</span>
                </button>

                <!-- ZaloPay -->
                <button type="button" class="pm-btn" data-method="zalopay" id="pm-zalopay" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <img src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png"
                             alt="ZaloPay" onerror="this.parentElement.innerHTML='<i class=\'fa-brands fa-z\' style=\'color:#0068ff; font-size:1.8rem;\'></i>'">
                    </div>
                    <span class="pm-name">ZaloPay</span>
                    <span class="pm-sub">Ví & App Zalo</span>
                </button>

            </div>

            <!-- Thẻ tóm tắt phương thức thanh toán đã chọn (Gọn gàng & Rõ ràng) -->
            <div class="pm-summary-card mt-3 p-3 rounded-4" id="pmSummaryCard">
                <div class="d-flex align-items-center gap-3">
                    <div class="pm-summary-icon" id="pmSummaryIcon">
                        <i class="fa-solid fa-building-columns fs-4" style="color:#0d9488;"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="fw-bold text-dark small" id="pmSummaryTitle">Chuyển khoản VietQR 24/7 (MB Bank)</span>
                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0" style="font-size:0.68rem;" id="pmSummaryBadge">Tự động 24/7</span>
                        </div>
                        <div class="text-muted" style="font-size:0.78rem;" id="pmSummaryDesc">
                            Quét mã QR tự động từ hơn 40 ứng dụng ngân hàng & ví điện tử.
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-2 px-sm-3 py-1 text-nowrap d-inline-flex align-items-center gap-1" style="font-size:0.75rem;" onclick="openPaymentModalPreview()">
                        <i class="fa-solid fa-qrcode"></i> <span class="d-none d-sm-inline">Xem mã </span>QR
                    </button>
                </div>
                <div class="pm-summary-hint mt-2 pt-2 border-top d-flex align-items-center gap-2" style="font-size:0.75rem; color:var(--text-secondary);">
                    <i class="fa-solid fa-shield-halved text-teal flex-shrink-0"></i>
                    <span>Mã thanh toán QR, số tiền và nội dung chuyển khoản sẽ hiển thị trực tiếp khi bạn nhấn <strong>"Thanh Toán & Đặt Cọc"</strong>.</span>
                </div>
            </div>

        </div>

        <!-- 3. Điều khoản đặt sân & Chính sách hoàn cọc -->
        <div class="p-3 rounded-4 mb-4 border" style="background:var(--bg-surface, #f8fafc); border-color:var(--border-color, #e2e8f0);">
            <div class="d-flex align-items-start gap-2">
                <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" id="agreeTerms" required style="min-width:16px; cursor:pointer;">
                <label for="agreeTerms" style="font-size:0.83rem; cursor:pointer; color:var(--text-primary); line-height:1.55;">
                    Tôi đã đọc và đồng ý với
                    <a href="#terms" onclick="openBookingTermsModal(event)" class="fw-bold" style="color:var(--brand-teal); text-decoration:underline;">Điều khoản đặt sân &amp; Chính sách hoàn cọc</a>
                    của GODMINTON.
                </label>
            </div>
            <div class="mt-2 ps-4 d-flex flex-column gap-1" style="font-size:0.76rem; color:var(--text-secondary); line-height:1.45;">
                <div><i class="fa-solid fa-circle-check text-success me-1"></i><strong>Hoàn 100% tiền cọc (50%):</strong> Hủy trong <strong>15 phút đầu</strong> sau khi đặt (cách giờ chơi &ge; 1h) hoặc hủy sớm <strong>trước giờ thi đấu &ge; 6 giờ</strong>.</div>
                <div><i class="fa-solid fa-triangle-exclamation text-warning me-1"></i><strong>Không hoàn cọc (Giữ cọc):</strong> Hủy muộn trong khoảng <strong>từ 2 giờ đến dưới 6 giờ</strong> trước giờ thi đấu (khóa hủy khi còn dưới 2 giờ).</div>
                <div><i class="fa-solid fa-crown text-teal me-1"></i><strong>Đặc quyền Hội viên VIP:</strong> Được hủy đơn và <strong>hoàn 100% cọc</strong> đến trước giờ thi đấu <strong>30 phút</strong>.</div>
            </div>
        </div>

        <!-- Mobile: CTA button (bên dưới form trên mobile) -->
        <div class="d-lg-none mb-4">
            <button class="btn-pay-cta mb-3" id="btnPayMobile" onclick="handlePaymentCta()">
                <span id="btnPayMobileText">Thanh Toán & Đặt Cọc</span>
            </button>
            <div class="text-center">
                <a href="booking.php" style="font-size:0.8rem; color:var(--text-secondary); text-decoration:none;">
                    <i class="fa-solid fa-arrow-left me-1"></i>Quay lại chọn khung giờ
                </a>
            </div>
        </div>

    </div><!-- /col-lg-7 -->

    <!-- ============ RIGHT: ORDER SUMMARY (Desktop: Right Sticky, Mobile: Top) ============ -->
    <div class="col-lg-5 order-1 order-lg-2">
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
                        <div class="bdr">
                            <span class="bdr-label"><i class="fa-solid fa-credit-card me-1" style="color:var(--brand-teal);"></i>Phương thức</span>
                            <span class="bdr-value text-teal" id="bdrMethod">VNPay (VNPAY-QR)</span>
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
                <button class="btn-pay-cta mb-3" id="btnPayDesktop" onclick="handlePaymentCta()">
                    <span id="btnPayDesktopText">Thanh Toán & Đặt Cọc</span>
                </button>
            </div>

            <!-- Desktop Back link -->
            <div class="d-none d-lg-block text-center">
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

<!-- ===================== MODAL CỔNG THANH TOÁN DUY NHẤT ===================== -->
<div class="modal fade" id="checkoutGatewayModal" tabindex="-1" aria-labelledby="gwCheckoutLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered gw-checkout-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <!-- Modal Header (Màu sắc và Logo thay đổi theo Cổng thanh toán) -->
            <div class="gw-modal-header p-3 text-white" id="gwModalHeader" style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);">
                <div class="d-flex align-items-center justify-content-between w-100">
                    <div class="d-flex align-items-center gap-2">
                        <div class="gw-modal-logo-wrap bg-white rounded-3 p-1 d-flex align-items-center justify-content-center" id="gwModalLogoWrap" style="width:38px; height:38px; box-shadow:0 2px 6px rgba(0,0,0,0.15);">
                            <img id="gwModalLogo" src="assets/img/logo-teal.svg" alt="Gateway" style="max-height:26px; max-width:28px; object-fit:contain;">
                        </div>
                        <div>
                            <h6 class="mb-0 fw-extrabold text-white" id="gwCheckoutLabel">Chuyển Khoản Ngân Hàng VietQR</h6>
                            <span class="text-white-50" style="font-size:0.72rem;" id="gwCheckoutSub">MB Bank · Tự động xác nhận 24/7</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 p-sm-4 text-center">
                <!-- 1. KÈM CÁI MÃ: Ảnh QR Code to rõ ràng đặt chính giữa -->
                <div class="gw-qr-card-container mb-3">
                    <div class="gw-qr-card shadow-sm">
                        <div class="gw-qr-img-box">
                            <img id="gwModalQrImg"
                                 src="assets/img/vietqr-payment.png"
                                 alt="Mã QR Thanh Toán"
                                 class="gw-qr-img">
                        </div>
                    </div>
                </div>

                <!-- 2. THÔNG TIN CHUYỂN KHOẢN (Đồng bộ thẳng hàng & cùng màu) -->
                <div class="transfer-info-card text-start">
                    <!-- Số tiền -->
                    <div class="ti-row">
                        <span class="ti-label">Số tiền cần chuyển:</span>
                        <div class="ti-value-wrap">
                            <span class="ti-value highlight-amount js-deposit-amount" id="gwModalAmount">--</span>
                            <button type="button" class="ti-copy-btn" onclick="copyById('gwModalAmount')" title="Sao chép số tiền">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Nội dung chuyển khoản / Mã GD -->
                    <div class="ti-row">
                        <span class="ti-label">Nội dung chuyển:</span>
                        <div class="ti-value-wrap">
                            <span class="ti-value code-val js-transfer-ref" id="gwModalRef">DATSAN-...</span>
                            <button type="button" class="ti-copy-btn" onclick="copyTransferRef()" title="Sao chép nội dung">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Ngân hàng thụ hưởng -->
                    <div class="ti-row">
                        <span class="ti-label" id="gwModalProviderLabel">Ngân hàng thụ hưởng:</span>
                        <div class="ti-value-wrap">
                            <span class="ti-value" id="gwModalProviderName">MB Bank (Ngân hàng Quân Đội)</span>
                            <span class="ti-copy-placeholder" aria-hidden="true"></span>
                        </div>
                    </div>

                    <!-- Số tài khoản -->
                    <div class="ti-row">
                        <span class="ti-label" id="gwModalAccLabel">Số tài khoản:</span>
                        <div class="ti-value-wrap">
                            <span class="ti-value code-val" id="gwModalAccValue">0783547289</span>
                            <button type="button" class="ti-copy-btn" onclick="copyById('gwModalAccValue')" title="Sao chép số tài khoản">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Chủ tài khoản -->
                    <div class="ti-row">
                        <span class="ti-label">Chủ tài khoản:</span>
                        <div class="ti-value-wrap">
                            <span class="ti-value" id="gwModalOwnerName">TRUONG MINH TRIET</span>
                            <span class="ti-copy-placeholder" aria-hidden="true"></span>
                        </div>
                    </div>
                </div>

                <!-- Thông báo & Trạng thái nhận diện biến động số dư SePay 24/7 -->
                <div class="payment-status-card p-3 rounded-3 text-center mb-3 border" id="gwPaymentStatusCard" style="background: #f0fdfa; border-color: #99f6e4 !important;">
                    <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                        <div class="spinner-grow spinner-grow-sm text-teal" role="status" style="width: 0.9rem; height: 0.9rem;"></div>
                        <strong class="text-teal small" id="gwLiveStatusText">Đang chờ ngân hàng xác nhận giao dịch...</strong>
                    </div>
                    <div class="text-muted" style="font-size:0.74rem;" id="gwLiveStatusSub">
                        Quét mã bằng app ngân hàng / ví điện tử. Hệ thống tự động xuất vé ngay khi tiền vào (thường từ 2–10 giây).
                    </div>
                </div>

                <!-- Nút test dev simulate (chỉ hiện khi server bật cờ SEPAY_ALLOW_SIMULATE) -->
                <div id="devSimulateWrap" class="mb-3" style="display:none;">
                    <button type="button" class="btn btn-outline-warning btn-sm rounded-pill w-100 py-1" onclick="devSimulatePayment()" style="font-size:0.75rem;">
                        <i class="fa-solid fa-flask me-1"></i> [DEV TEST] Giả lập SePay nhận tiền thành công
                    </button>
                </div>

                <!-- Tải ảnh QR & Quay lại -->
                <div class="d-flex justify-content-between align-items-center px-1 pt-2 border-top">
                    <a id="gwModalDownloadQr" href="assets/img/vietqr-payment.png" download="VietQR-GODMINTON.png" class="text-decoration-none text-muted small" style="font-size:0.75rem;">
                        <i class="fa-solid fa-download me-1 text-teal"></i> Tải ảnh QR
                    </a>
                    <button type="button" class="btn btn-link btn-sm text-secondary text-decoration-none p-0" data-bs-dismiss="modal" style="font-size:0.75rem;">
                        Quay lại kiểm tra
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
<!-- Bottom Nav -->
<script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
</body>
</html>
