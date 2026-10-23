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
            <div class="d-flex align-items-center justify-content-between mb-2">
                <p class="pay-section-title mb-0">
                    <i class="fa-solid fa-wallet"></i> Phương Thức Thanh Toán
                </p>
                <span class="pm-verified-pill" id="onlineVerifiedBadge" style="display:none;">
                    <i class="fa-solid fa-circle-check me-1"></i>Đã xác thực trực tuyến
                </span>
            </div>

            <div class="pm-grid" id="pmGrid">

                <!-- VNPay -->
                <button type="button" class="pm-btn selected" data-method="vnpay" id="pm-vnpay" onclick="selectPM(this)">
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

                <!-- Chuyển khoản -->
                <button type="button" class="pm-btn" data-method="bank" id="pm-bank" onclick="selectPM(this)">
                    <span class="pm-check"><i class="fa-solid fa-check"></i></span>
                    <div class="pm-icon-wrap">
                        <i class="fa-solid fa-building-columns" style="color:#0d9488; font-size:1.8rem;"></i>
                    </div>
                    <span class="pm-name">Chuyển khoản</span>
                    <span class="pm-sub">VietQR 24/7</span>
                </button>

            </div>

            <!-- ==================== 2A. VNPAY WEB PANEL ==================== -->
            <div class="bank-panel gateway-panel vnpay-theme show" id="vnpayPanel">
                <!-- Sub-navigation: Quét QR hoặc Thẻ ATM nội địa (chuẩn cổng VNPAY trên web) -->
                <div class="gw-mode-tabs mb-3" role="tablist">
                    <button type="button" class="gw-mode-tab active" onclick="switchGatewayTab('vnpay', 'qr', this)">
                        <i class="fa-solid fa-qrcode me-1"></i> Quét mã VNPAY-QR
                    </button>
                    <button type="button" class="gw-mode-tab" onclick="switchGatewayTab('vnpay', 'web', this)">
                        <i class="fa-solid fa-credit-card me-1"></i> Thẻ ATM / Ngân hàng nội địa
                    </button>
                </div>

                <!-- Tab 1: VNPAY-QR -->
                <div class="gw-tab-pane show" id="vnpay-tab-qr">
                    <div class="d-flex gap-3 flex-wrap align-items-start">
                        <div class="qr-box qr-box-vnpay" onclick="openQrModal('vnpay')" title="Nhấn để phóng to mã VNPAY-QR">
                            <span class="qr-scan-line"></span>
                            <img src="assets/img/vnpay-payment.png?v=<?= @filemtime(__DIR__ . '/assets/img/vnpay-payment.png') ?: time() ?>"
                                 alt="VNPAY-QR - GODMINTON"
                                 onerror="this.src='assets/img/vietqr-payment.png';">
                            <div class="qr-zoom-hint">
                                <i class="fa-solid fa-magnifying-glass-plus"></i> Phóng to
                            </div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-1">
                                <span class="fw-bold small d-flex align-items-center gap-1">
                                    <span class="gw-dot vnpay-dot"></span> Cổng thanh toán VNPAY-QR
                                </span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:0.7rem;">
                                    <i class="fa-solid fa-shield-halved me-1"></i>Cổng VNPAY 24/7
                                </span>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Đơn vị nhận</span>
                                <strong>GODMINTON (TRUONG MINH TRIET)</strong>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Mã Terminal</span>
                                <strong id="vnpayAccNum">0783547289 (MB Bank)</strong>
                                <button type="button" class="copy-btn" onclick="copyText('0783547289')">
                                    <i class="fa-regular fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Mã GD</span>
                                <strong id="vnpayTransCode" class="text-primary">VNP-...</strong>
                                <button type="button" class="copy-btn" onclick="copyById('vnpayTransCode')">
                                    <i class="fa-regular fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Nội dung</span>
                                <strong class="js-transfer-ref" style="word-break:break-all;">DATSAN-...</strong>
                                <button type="button" class="copy-btn" onclick="copyTransferRef()">
                                    <i class="fa-regular fa-copy"></i> Copy
                                </button>
                            </div>

                            <div class="gw-supported-Row mt-2">
                                <span class="gw-chip"><i class="fa-solid fa-building-columns me-1"></i>Vietcombank</span>
                                <span class="gw-chip">BIDV</span>
                                <span class="gw-chip">VietinBank</span>
                                <span class="gw-chip">MB Bank</span>
                                <span class="gw-chip">Techcombank</span>
                                <span class="gw-chip">Ví VNPAY</span>
                                <span class="gw-chip">+35 App NH</span>
                            </div>

                            <div class="mt-2 d-flex align-items-start gap-1" style="font-size:0.73rem; color:var(--text-secondary);">
                                <i class="fa-solid fa-circle-exclamation text-warning mt-1 flex-shrink-0"></i>
                                <span>Mở tính năng <strong>QR Pay</strong> trên ứng dụng Ngân hàng hoặc Ví VNPAY để quét mã. Số tiền: <strong class="text-warning js-deposit-amount">--</strong></span>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-teal rounded-pill" onclick="openQrModal('vnpay')" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                    <i class="fa-solid fa-expand me-1"></i> Xem QR cỡ lớn
                                </button>
                                <a href="assets/img/vnpay-payment.png" download="VNPAY-QR-GODMINTON.png" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                    <i class="fa-solid fa-download me-1"></i> Tải ảnh QR
                                </a>
                                <button type="button" class="btn btn-sm btn-gw-action vnpay-action rounded-pill" onclick="openGatewayCheckout('vnpay')" style="font-size:0.75rem; padding:0.25rem 0.85rem;">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Mở Cổng VNPAY Web
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Thẻ ATM / Ngân hàng nội địa qua VNPAY -->
                <div class="gw-tab-pane" id="vnpay-tab-web">
                    <div class="gw-web-form">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small">Chọn ngân hàng nội địa (NAPAS 247)</span>
                            <span class="text-muted" style="font-size:0.72rem;">Miễn phí giao dịch</span>
                        </div>
                        <div class="gw-bank-grid mb-3" id="vnpayBankGrid">
                            <button type="button" class="gw-bank-item active" onclick="selectVnpayBank(this, 'MB Bank')">MB Bank</button>
                            <button type="button" class="gw-bank-item" onclick="selectVnpayBank(this, 'Vietcombank')">Vietcombank</button>
                            <button type="button" class="gw-bank-item" onclick="selectVnpayBank(this, 'Techcombank')">Techcombank</button>
                            <button type="button" class="gw-bank-item" onclick="selectVnpayBank(this, 'BIDV')">BIDV</button>
                            <button type="button" class="gw-bank-item" onclick="selectVnpayBank(this, 'VietinBank')">VietinBank</button>
                            <button type="button" class="gw-bank-item" onclick="selectVnpayBank(this, 'ACB')">ACB</button>
                            <button type="button" class="gw-bank-item" onclick="selectVnpayBank(this, 'VPBank')">VPBank</button>
                            <button type="button" class="gw-bank-item" onclick="selectVnpayBank(this, 'TPBank')">TPBank</button>
                        </div>
                        <div class="row g-2">
                            <div class="col-sm-7">
                                <label class="pay-label">Số thẻ ATM / Số tài khoản (<span id="vnpaySelectedBankLabel">MB Bank</span>)</label>
                                <input type="text" class="pay-input" id="vnpayCardNumber" placeholder="9704 22xx xxxx xxxx" maxlength="23" oninput="formatAtmCard(this)">
                            </div>
                            <div class="col-sm-5">
                                <label class="pay-label">Tháng/Năm phát hành</label>
                                <input type="text" class="pay-input" id="vnpayCardIssue" placeholder="MM/YY (VD: 08/24)" maxlength="5" oninput="formatIssueDate(this)">
                            </div>
                            <div class="col-12">
                                <label class="pay-label">Tên chủ thẻ (không dấu)</label>
                                <input type="text" class="pay-input text-uppercase" id="vnpayCardHolder" placeholder="NGUYEN VAN A">
                            </div>
                        </div>
                        <div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="small text-muted">
                                Số tiền thanh toán: <strong class="text-warning js-deposit-amount">--</strong>
                            </span>
                            <button type="button" class="btn btn-sm btn-gw-action vnpay-action rounded-pill px-3 py-2" onclick="verifyInlineGateway('vnpay')">
                                <i class="fa-solid fa-lock me-1"></i> Xác thực OTP qua VNPAY
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== 2B. MOMO WEB PANEL ==================== -->
            <div class="bank-panel gateway-panel momo-theme" id="momoPanel">
                <div class="gw-mode-tabs mb-3" role="tablist">
                    <button type="button" class="gw-mode-tab active" onclick="switchGatewayTab('momo', 'qr', this)">
                        <i class="fa-solid fa-qrcode me-1"></i> Quét mã QR MoMo
                    </button>
                    <button type="button" class="gw-mode-tab" onclick="switchGatewayTab('momo', 'web', this)">
                        <i class="fa-solid fa-mobile-screen-button me-1"></i> Thanh toán nhanh qua SĐT Ví MoMo
                    </button>
                </div>

                <!-- Tab 1: MoMo QR -->
                <div class="gw-tab-pane show" id="momo-tab-qr">
                    <div class="d-flex gap-3 flex-wrap align-items-start">
                        <div class="qr-box qr-box-momo" onclick="openQrModal('momo')" title="Nhấn để phóng to mã QR MoMo">
                            <span class="qr-scan-line momo-line"></span>
                            <img src="assets/img/momo-payment.png?v=<?= @filemtime(__DIR__ . '/assets/img/momo-payment.png') ?: time() ?>"
                                 alt="MoMo QR - TRUONG MINH TRIET"
                                 onerror="this.src='assets/img/vietqr-payment.png';">
                            <div class="qr-zoom-hint">
                                <i class="fa-solid fa-magnifying-glass-plus"></i> Phóng to
                            </div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-1">
                                <span class="fw-bold small d-flex align-items-center gap-1">
                                    <span class="gw-dot momo-dot"></span> Thanh toán Ví điện tử MoMo
                                </span>
                                <span class="badge momo-badge px-2 py-1" style="font-size:0.7rem;">
                                    <i class="fa-solid fa-bolt me-1"></i>Xử lý tức thì
                                </span>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Ví nhận</span>
                                <strong>Siêu ứng dụng MoMo (Hỗ trợ VietQR)</strong>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">SĐT Ví</span>
                                <strong id="momoAccNum">0783547289</strong>
                                <button type="button" class="copy-btn" onclick="copyText('0783547289')">
                                    <i class="fa-regular fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Chủ ví</span>
                                <strong>TRUONG MINH TRIET</strong>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Lời nhắn</span>
                                <strong class="js-transfer-ref" style="word-break:break-all;">DATSAN-...</strong>
                                <button type="button" class="copy-btn" onclick="copyTransferRef()">
                                    <i class="fa-regular fa-copy"></i> Copy
                                </button>
                            </div>

                            <div class="gw-steps-inline mt-2">
                                <span><strong>B1:</strong> Mở App MoMo</span>
                                <i class="fa-solid fa-chevron-right"></i>
                                <span><strong>B2:</strong> Chọn <strong>Quét Mã</strong></span>
                                <i class="fa-solid fa-chevron-right"></i>
                                <span><strong>B3:</strong> Xác nhận <strong class="text-warning js-deposit-amount">--</strong></span>
                            </div>

                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-teal rounded-pill" onclick="openQrModal('momo')" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                    <i class="fa-solid fa-expand me-1"></i> Xem QR cỡ lớn
                                </button>
                                <a href="assets/img/momo-payment.png" download="MoMo-QR-GODMINTON.png" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                    <i class="fa-solid fa-download me-1"></i> Tải ảnh QR
                                </a>
                                <button type="button" class="btn btn-sm btn-gw-action momo-action rounded-pill" onclick="openGatewayCheckout('momo')" style="font-size:0.75rem; padding:0.25rem 0.85rem;">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Mở Cổng MoMo Web
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Liên kết nhanh SĐT Ví MoMo trên Web -->
                <div class="gw-tab-pane" id="momo-tab-web">
                    <div class="gw-web-form">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small">Gửi yêu cầu thanh toán tới ứng dụng MoMo</span>
                            <span class="badge momo-badge" style="font-size:0.68rem;">MoMo E-Wallet</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size:0.76rem;">
                            Nhập số điện thoại đăng ký Ví MoMo của bạn để nhận thông báo xác nhận thanh toán trực tiếp hoặc nhập mã giao dịch MoMo.
                        </p>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="pay-label">SĐT Ví MoMo của bạn</label>
                                <input type="tel" class="pay-input" id="momoUserPhone" placeholder="0912 345 678" maxlength="12">
                            </div>
                            <div class="col-sm-6">
                                <label class="pay-label">Nguồn tiền thanh toán</label>
                                <select class="pay-input" id="momoSourceSelect">
                                    <option value="wallet">Số dư Ví MoMo</option>
                                    <option value="later">Ví Trả Sau MoMo</option>
                                    <option value="bank">Ngân hàng liên kết MoMo</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="small text-muted">
                                Cọc cần thanh toán: <strong class="text-warning js-deposit-amount">--</strong>
                            </span>
                            <button type="button" class="btn btn-sm btn-gw-action momo-action rounded-pill px-3 py-2" onclick="verifyInlineGateway('momo')">
                                <i class="fa-solid fa-paper-plane me-1"></i> Gửi xác thực MoMo OTP
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== 2C. ZALOPAY WEB PANEL ==================== -->
            <div class="bank-panel gateway-panel zalopay-theme" id="zalopayPanel">
                <div class="gw-mode-tabs mb-3" role="tablist">
                    <button type="button" class="gw-mode-tab active" onclick="switchGatewayTab('zalopay', 'qr', this)">
                        <i class="fa-solid fa-qrcode me-1"></i> Quét mã QR ZaloPay / Zalo
                    </button>
                    <button type="button" class="gw-mode-tab" onclick="switchGatewayTab('zalopay', 'web', this)">
                        <i class="fa-solid fa-bolt me-1"></i> Liên kết nhanh qua Zalo / ZaloPay
                    </button>
                </div>

                <!-- Tab 1: ZaloPay QR -->
                <div class="gw-tab-pane show" id="zalopay-tab-qr">
                    <div class="d-flex gap-3 flex-wrap align-items-start">
                        <div class="qr-box qr-box-zalopay" onclick="openQrModal('zalopay')" title="Nhấn để phóng to mã QR ZaloPay">
                            <span class="qr-scan-line zalopay-line"></span>
                            <img src="assets/img/zalopay-payment.png?v=<?= @filemtime(__DIR__ . '/assets/img/zalopay-payment.png') ?: time() ?>"
                                 alt="ZaloPay QR - TRUONG MINH TRIET"
                                 onerror="this.src='assets/img/vietqr-payment.png';">
                            <div class="qr-zoom-hint">
                                <i class="fa-solid fa-magnifying-glass-plus"></i> Phóng to
                            </div>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-1">
                                <span class="fw-bold small d-flex align-items-center gap-1">
                                    <span class="gw-dot zalopay-dot"></span> Cổng thanh toán Ví ZaloPay
                                </span>
                                <span class="badge zalopay-badge px-2 py-1" style="font-size:0.7rem;">
                                    <i class="fa-solid fa-check-double me-1"></i>Quét từ Zalo / ZaloPay
                                </span>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Cổng nhận</span>
                                <strong>Ví ZaloPay (Tích hợp trong ứng dụng Zalo)</strong>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">SĐT / TK</span>
                                <strong id="zalopayAccNum">0783547289</strong>
                                <button type="button" class="copy-btn" onclick="copyText('0783547289')">
                                    <i class="fa-regular fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Chủ TK</span>
                                <strong>TRUONG MINH TRIET</strong>
                            </div>
                            <div class="copy-row">
                                <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:72px;">Nội dung</span>
                                <strong class="js-transfer-ref" style="word-break:break-all;">DATSAN-...</strong>
                                <button type="button" class="copy-btn" onclick="copyTransferRef()">
                                    <i class="fa-regular fa-copy"></i> Copy
                                </button>
                            </div>

                            <div class="mt-2 d-flex align-items-start gap-1" style="font-size:0.73rem; color:var(--text-secondary);">
                                <i class="fa-solid fa-circle-info text-primary mt-1 flex-shrink-0"></i>
                                <span>Bạn có thể mở trực tiếp <strong>ứng dụng Zalo</strong> (mục Quét QR) hoặc <strong>ZaloPay</strong> để thanh toán. Số tiền: <strong class="text-warning js-deposit-amount">--</strong></span>
                            </div>
                            <div class="mt-2 d-flex flex-wrap gap-2">
                                <button type="button" class="btn btn-sm btn-outline-teal rounded-pill" onclick="openQrModal('zalopay')" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                    <i class="fa-solid fa-expand me-1"></i> Xem QR cỡ lớn
                                </button>
                                <a href="assets/img/zalopay-payment.png" download="ZaloPay-QR-GODMINTON.png" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                    <i class="fa-solid fa-download me-1"></i> Tải ảnh QR
                                </a>
                                <button type="button" class="btn btn-sm btn-gw-action zalopay-action rounded-pill" onclick="openGatewayCheckout('zalopay')" style="font-size:0.75rem; padding:0.25rem 0.85rem;">
                                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Mở Cổng ZaloPay Web
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Thanh toán nhanh qua tài khoản Zalo / ZaloPay trên Web -->
                <div class="gw-tab-pane" id="zalopay-tab-web">
                    <div class="gw-web-form">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small">Thanh toán qua tài khoản Zalo / ZaloPay</span>
                            <span class="badge zalopay-badge" style="font-size:0.68rem;">ZaloPay Gateway</span>
                        </div>
                        <p class="text-muted mb-2" style="font-size:0.76rem;">
                            Nhập số điện thoại Zalo/ZaloPay để nhận thông báo xác nhận thanh toán ngay trong khung chat Zalo hoặc ứng dụng ZaloPay.
                        </p>
                        <div class="row g-2">
                            <div class="col-sm-6">
                                <label class="pay-label">Số điện thoại Zalo / ZaloPay</label>
                                <input type="tel" class="pay-input" id="zalopayUserPhone" placeholder="0912 345 678" maxlength="12">
                            </div>
                            <div class="col-sm-6">
                                <label class="pay-label">Phương thức nguồn tiền</label>
                                <select class="pay-input" id="zalopaySourceSelect">
                                    <option value="zalopay_wallet">Số dư Ví ZaloPay</option>
                                    <option value="zalo_bank">Thẻ / Ngân hàng liên kết trong Zalo</option>
                                    <option value="zalopay_postpaid">Tài khoản Trả Sau ZaloPay</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="small text-muted">
                                Cọc cần thanh toán: <strong class="text-warning js-deposit-amount">--</strong>
                            </span>
                            <button type="button" class="btn btn-sm btn-gw-action zalopay-action rounded-pill px-3 py-2" onclick="verifyInlineGateway('zalopay')">
                                <i class="fa-solid fa-check-circle me-1"></i> Gửi yêu cầu xác thực ZaloPay
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ==================== 2D. CHUYỂN KHOẢN NGÂN HÀNG (VIETQR MB BANK) ==================== -->
            <div class="bank-panel" id="bankPanel">
                <div class="d-flex gap-3 flex-wrap align-items-start">
                    <div class="qr-box" onclick="openQrModal('bank')" title="Nhấn để phóng to mã QR">
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
                            <button type="button" class="copy-btn" onclick="copyText('0783547289')">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:65px;">Chủ TK</span>
                            <strong>TRUONG MINH TRIET</strong>
                        </div>
                        <div class="copy-row">
                            <span class="text-muted" style="font-size:0.75rem; flex-shrink:0; min-width:65px;">Nội dung</span>
                            <strong id="transferRef" class="js-transfer-ref" style="word-break:break-all;">DATSAN-...</strong>
                            <button type="button" class="copy-btn" onclick="copyTransferRef()">
                                <i class="fa-regular fa-copy"></i> Copy
                            </button>
                        </div>
                        <div class="mt-2 d-flex align-items-start gap-1" style="font-size:0.73rem; color:var(--text-secondary);">
                            <i class="fa-solid fa-circle-exclamation text-warning mt-1 flex-shrink-0"></i>
                            <span>Chuyển đúng nội dung để hệ thống tự xác nhận. Số tiền: <strong class="text-warning js-deposit-amount" id="bankAmountText">--</strong></span>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill" onclick="openQrModal('bank')" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                <i class="fa-solid fa-expand me-1"></i> Xem QR cỡ lớn
                            </button>
                            <a href="assets/img/vietqr-payment.png" download="VietQR-GODMINTON.png" class="btn btn-sm btn-outline-secondary rounded-pill" style="font-size:0.75rem; padding:0.25rem 0.75rem;">
                                <i class="fa-solid fa-download me-1"></i> Tải ảnh QR
                            </a>
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

<!-- Modal Phóng to mã QR đa năng (VNPay / MoMo / ZaloPay / VietQR MB Bank) -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 pb-0 pt-3 px-3">
                <h6 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="qrModalLabel">
                    <i class="fa-solid fa-qrcode text-teal"></i> <span id="qrModalTitleText">Quét Mã Thanh Toán</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-3">
                <div class="qr-modal-img-wrap rounded-3 overflow-hidden shadow-sm bg-white p-2 mb-3">
                    <img id="qrModalImg" src="assets/img/vietqr-payment.png?v=<?= filemtime(__DIR__ . '/assets/img/vietqr-payment.png') ?>" alt="Mã QR Thanh Toán" class="img-fluid rounded-3" style="max-height: 480px; width: auto; object-fit: contain;">
                </div>
                <div class="bg-light rounded-3 p-3 text-start small mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Chủ TK / Ví:</span>
                        <span class="fw-bold" id="modalOwnerName">TRUONG MINH TRIET</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted" id="modalAccLabel">Số TK / SĐT:</span>
                        <span class="fw-bold font-monospace text-teal" id="modalAccValue">0783547289</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Cổng / Ngân hàng:</span>
                        <span class="fw-bold" id="modalProviderName">MB Bank (Quân Đội)</span>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Số tiền cọc:</span>
                        <span class="fw-bold text-danger" id="modalDepositAmount">--</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Nội dung:</span>
                        <span class="fw-bold font-monospace text-warning" id="modalTransferRef">DATSAN-...</span>
                    </div>
                </div>
                <div class="d-flex gap-2 justify-content-center">
                    <a id="qrModalDownloadBtn" href="assets/img/vietqr-payment.png" download="QR-GODMINTON.png" class="btn btn-teal rounded-pill px-3 py-1 text-sm">
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

<!-- Modal Cổng Thanh Toán Web Trực Tuyến (VNPay / MoMo / ZaloPay Web Checkout) -->
<div class="modal fade" id="gatewayCheckoutModal" tabindex="-1" aria-labelledby="gwCheckoutLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 480px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="gw-modal-header" id="gwModalHeader">
                <div class="d-flex align-items-center gap-2">
                    <div class="gw-modal-logo-wrap" id="gwModalLogoWrap">
                        <img id="gwModalLogo" src="https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-VNPAY-QR.png" alt="Gateway">
                    </div>
                    <div>
                        <h6 class="mb-0 fw-extrabold text-white" id="gwCheckoutLabel">Cổng Thanh Toán VNPAY</h6>
                        <span class="text-white-50" style="font-size:0.72rem;" id="gwCheckoutSub">Kết nối bảo mật chuẩn PCI-DSS</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="d-flex justify-content-between align-items-center p-3 rounded-3 mb-3" style="background:var(--bg-body); border:1px solid var(--border-color);">
                    <div>
                        <div class="text-muted" style="font-size:0.72rem;">Đơn vị thụ hưởng</div>
                        <div class="fw-bold small">GODMINTON SPORT</div>
                        <div class="text-muted" style="font-size:0.72rem;">Mã tham chiếu: <strong class="text-dark" id="gwModalRef">DATSAN-...</strong></div>
                    </div>
                    <div class="text-end">
                        <div class="text-muted" style="font-size:0.72rem;">Số tiền thanh toán</div>
                        <div class="fw-extrabold fs-5 text-teal" id="gwModalAmount">--</div>
                    </div>
                </div>

                <div class="text-center mb-3">
                    <div class="d-inline-block p-2 bg-white rounded-4 border shadow-sm mb-2">
                        <img id="gwModalQrImg" src="assets/img/vnpay-payment.png" alt="QR Gateway" style="width: 210px; height: auto; border-radius: 10px;">
                    </div>
                    <div class="small text-muted" id="gwModalHint">
                        Mở ứng dụng Ngân hàng hoặc Ví điện tử quét mã QR hoặc bấm xác nhận thanh toán trực tuyến bên dưới.
                    </div>
                </div>

                <div class="p-2 rounded-3 mb-3 text-center" style="background:rgba(13,148,136,0.08); font-size:0.76rem; color:var(--brand-teal); font-weight:600;">
                    <i class="fa-solid fa-rotate fa-spin me-1"></i> Hệ thống đang chờ tín hiệu thanh toán tự động 24/7...
                </div>

                <div class="d-grid gap-2">
                    <button type="button" class="btn btn-teal rounded-pill fw-bold py-2" id="btnGwConfirmNow" onclick="confirmGatewayCheckout()">
                        <i class="fa-solid fa-circle-check me-1"></i> Tôi đã thanh toán — Xác nhận & Đặt sân ngay
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill" data-bs-dismiss="modal">
                        Quay lại trang thanh toán
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
