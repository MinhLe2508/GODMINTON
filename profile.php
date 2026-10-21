<?php
require_once __DIR__ . '/includes/auth-check.php';
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Cá Nhân | GODMINTON</title>
<?php include_once __DIR__ . '/includes/partials/pwa-head.php'; ?>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6.5.2 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(__DIR__ . '/assets/css/swal-godminton.css') ?>">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <style>
        .profile-hero {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 50%, #042f2e 100%);
            border-radius: 1.5rem;
            color: #fff;
            padding: 2.5rem 2rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(13, 148, 136, 0.2);
            margin-bottom: 2rem;
        }
        .profile-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 350px;
            height: 350px;
            background: radial-gradient(circle, rgba(45, 212, 191, 0.25) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .profile-avatar-wrapper {
            position: relative;
            width: 96px;
            height: 96px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            background: #0d9488;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: #fff;
            box-shadow: 0 8px 24px rgba(0,0,0,0.25);
            flex-shrink: 0;
        }
        .profile-avatar-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #f59e0b;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            cursor: pointer;
            border: 2px solid #fff;
        }
        .profile-stat-box {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 1rem;
            padding: 1rem 1.25rem;
            text-align: center;
        }
        .profile-nav-pills .nav-link {
            color: var(--text-secondary);
            font-weight: 600;
            border-radius: 0.75rem;
            padding: 0.75rem 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s ease;
        }
        .profile-nav-pills .nav-link.active {
            background: var(--brand-teal);
            color: #fff;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }
        .profile-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 1.25rem;
            padding: 1.75rem;
            box-shadow: var(--shadow-sm);
        }
        .voucher-card {
            border: 2px dashed var(--brand-teal);
            background: rgba(13, 148, 136, 0.04);
            border-radius: 1rem;
            padding: 1.25rem;
            position: relative;
        }

        /* Nút Đổi tài khoản khác: căn giữa, viền mỏng thanh lịch, hover xanh nổi bật */
        .btn-switch-account {
            color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.05) !important;
            border: 1.5px solid rgba(13, 110, 253, 0.45) !important;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
        }
        .btn-switch-account i {
            color: #0d6efd !important;
            transition: color 0.2s ease;
        }
        .btn-switch-account:hover {
            color: #ffffff !important;
            background-color: #0d6efd !important;
            border-color: #0d6efd !important;
            box-shadow: 0 4px 14px rgba(13, 110, 253, 0.3) !important;
            transform: translateY(-2px);
        }
        .btn-switch-account:hover i {
            color: #ffffff !important;
        }

        /* Nút Đăng xuất tài khoản: căn giữa, viền mỏng thanh lịch, hover đỏ nổi bật */
        .btn-logout-account {
            color: #dc2626 !important;
            background-color: rgba(220, 38, 38, 0.05) !important;
            border: 1.5px solid rgba(220, 38, 38, 0.45) !important;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
        }
        .btn-logout-account i {
            color: #dc2626 !important;
            transition: color 0.2s ease;
        }
        .btn-logout-account:hover {
            color: #ffffff !important;
            background-color: #dc2626 !important;
            border-color: #dc2626 !important;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.3) !important;
            transform: translateY(-2px);
        }
        .btn-logout-account:hover i {
            color: #ffffff !important;
        }

        /* Dark mode cho 2 nút */
        [data-bs-theme="dark"] .btn-switch-account {
            color: #38bdf8 !important;
            background-color: rgba(56, 189, 248, 0.08) !important;
            border-color: rgba(56, 189, 248, 0.4) !important;
        }
        [data-bs-theme="dark"] .btn-switch-account i {
            color: #38bdf8 !important;
        }
        [data-bs-theme="dark"] .btn-switch-account:hover {
            color: #ffffff !important;
            background-color: #0284c7 !important;
            border-color: #0284c7 !important;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35) !important;
        }
        [data-bs-theme="dark"] .btn-switch-account:hover i {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .btn-logout-account {
            color: #f87171 !important;
            background-color: rgba(248, 113, 113, 0.08) !important;
            border-color: rgba(248, 113, 113, 0.4) !important;
        }
        [data-bs-theme="dark"] .btn-logout-account i {
            color: #f87171 !important;
        }
        [data-bs-theme="dark"] .btn-logout-account:hover {
            color: #ffffff !important;
            background-color: #dc2626 !important;
            border-color: #dc2626 !important;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35) !important;
        }
        [data-bs-theme="dark"] .btn-logout-account:hover i {
            color: #ffffff !important;
        }
    </style>
<link rel="stylesheet" href="assets/css/password-requirements.css?v=<?= filemtime(__DIR__.'/assets/css/password-requirements.css') ?>">
</head>

<body>

    <!-- App Brand Header -->
    <header class="app-brand-head">
        <div class="container text-center">
            <a href="index.php" class="brand-logo-link" aria-label="GODMINTON Trang chủ">
                <img src="assets/img/logo-teal.svg" alt="GODMINTON" class="brand-logo-img">
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-vh-100 pt-2 pb-5">
        <div class="container">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
                    <li class="breadcrumb-item active text-teal fw-bold" aria-current="page">Trang cá nhân</li>
                </ol>
            </nav>

            <!-- Profile Hero Banner -->
            <div class="profile-hero">
                <div class="row align-items-center g-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-3">
                            <div class="profile-avatar-wrapper" id="profileAvatarInitial">
                                A
                                <span class="profile-avatar-edit" title="Đổi ảnh đại diện">
                                    <i class="fa-solid fa-camera"></i>
                                </span>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                    <h3 class="fw-extrabold mb-0 text-white" id="profileHeaderName">Đang tải</h3>
                                    <span class="badge bg-warning text-dark fw-bold rounded-pill px-2 py-1">
                                        <span id="profileRoleLabel">Đang tải</span>
                                    </span>
                                </div>
                                <div class="opacity-90 small mb-1" id="profileHeaderEmail">
                                    Đang tải email
                                </div>
                                <div class="opacity-75 small">
                                    <i class="fa-regular fa-calendar-check me-1"></i> <span id="profileMemberSince">Đang tải ngày tham gia</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="col-md-5">
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="profile-stat-box">
                                    <div class="fs-4 fw-extrabold text-warning" id="statBookingsCount">0</div>
                                    <div class="small opacity-80" style="font-size: 0.75rem;">Lượt đặt sân</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="profile-stat-box">
                                    <div class="fs-4 fw-extrabold text-info" id="statVouchersCount">0</div>
                                    <div class="small opacity-80" style="font-size: 0.75rem;">Mã ưu đãi</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="profile-stat-box">
                                    <div class="fs-4 fw-extrabold text-success">—</div>
                                    <div class="small opacity-80" style="font-size: 0.75rem;">Chưa hỗ trợ</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Sections & Tabs -->
            <div class="row g-4">
                <!-- Left Sidebar Nav -->
                <div class="col-lg-3">
                    <div class="profile-card p-3">
                        <div class="nav flex-column nav-pills profile-nav-pills gap-1" id="profileTabs" role="tablist">
                            <button class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#tabProfileInfo" type="button" role="tab">
                                <i class="fa-regular fa-id-card"></i> Thông tin cá nhân
                            </button>
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tabProfileBookings" type="button" role="tab">
                                <i class="fa-solid fa-clock-rotate-left"></i> Lịch sử đặt sân
                            </button>
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tabProfileVouchers" type="button" role="tab">
                                <i class="fa-solid fa-ticket"></i> Mã giảm giá & Ưu đãi
                            </button>
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tabProfileSecurity" type="button" role="tab">
                                <i class="fa-solid fa-shield-halved"></i> Mật khẩu & Bảo mật
                            </button>
                        </div>

                        <hr class="my-3 border-secondary opacity-25">

                        <!-- Quick actions -->
                        <div class="d-flex flex-column gap-2">
                            <a href="courts.php" class="btn btn-teal rounded-pill w-100 fw-bold py-2">
                                <i class="fa-solid fa-plus me-1"></i> Đặt sân mới
                            </a>
                            <a href="auth.php" class="btn btn-switch-account rounded-pill w-100 fw-semibold text-center justify-content-center py-2">
                                <i class="fa-solid fa-arrow-right-arrow-left me-2"></i> Đổi tài khoản khác
                            </a>
                            <button type="button" id="profileLogout" class="btn btn-logout-account rounded-pill w-100 fw-semibold text-center justify-content-center py-2">
                                <i class="fa-solid fa-right-from-bracket me-2"></i> Đăng xuất tài khoản
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right Content Area -->
                <div class="col-lg-9">
                    <div class="tab-content" id="profileTabContent">

                        <!-- TAB 1: THÔNG TIN CÁ NHÂN -->
                        <div class="tab-pane fade show active" id="tabProfileInfo" role="tabpanel">
                            <div class="profile-card">
                                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                                    <div>
                                        <h5 class="fw-bold mb-1"><i class="fa-regular fa-user text-teal me-2"></i>Hồ Sơ Cá Nhân</h5>
                                        <p class="text-muted small mb-0">Quản lý thông tin liên hệ và sở thích chơi cầu lông của bạn</p>
                                    </div>
                                    <span class="badge bg-teal-subtle text-teal px-3 py-2 rounded-pill fw-bold">Tài khoản Hoạt động</span>
                                </div>

                                <form id="formUpdateProfile">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Họ và tên <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body-tertiary border-end-0"><i class="fa-regular fa-user text-muted"></i></span>
                                                <input type="text" class="form-control border-start-0" id="inpFullName" value="" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Số điện thoại <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body-tertiary border-end-0"><i class="fa-solid fa-phone text-muted"></i></span>
                                                <input type="tel" class="form-control border-start-0" id="inpPhone" value="" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Địa chỉ Email <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body-tertiary border-end-0"><i class="fa-regular fa-envelope text-muted"></i></span>
                                                <input type="email" class="form-control border-start-0" id="inpEmail" value="" readonly aria-describedby="emailVerifiedNote">
                                            </div>
<small id="emailVerifiedNote" class="text-muted">Email đăng nhập đã xác minh. Liên hệ quản trị nếu cần thay đổi.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Khu vực sinh sống</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-body-tertiary border-end-0"><i class="fa-solid fa-location-dot text-muted"></i></span>
                                                <select class="form-select border-start-0" id="inpDistrict" disabled title="Chưa có trường lưu trong schema hiện tại"><option value="">Chưa hỗ trợ lưu</option></select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Trình độ chơi cầu lông</label>
                                            <select class="form-select" id="inpLevel" disabled title="Chưa có trường lưu trong schema hiện tại"><option value="">Chưa hỗ trợ lưu</option></select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Thời gian chơi yêu thích</label>
                                            <select class="form-select" id="inpPreferredTime" disabled title="Chưa có trường lưu trong schema hiện tại"><option value="">Chưa hỗ trợ lưu</option></select>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="resetProfileForm()">Khôi phục</button>
                                        <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">
                                            Lưu thay đổi
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- TAB 2: LỊCH SỬ ĐẶT SÂN -->
                        <div class="tab-pane fade" id="tabProfileBookings" role="tabpanel">
                            <div class="profile-card">
                                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
                                    <div>
                                        <h5 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-teal me-2"></i>Đơn Đặt Sân Của Tôi</h5>
                                        <p class="text-muted small mb-0">Theo dõi lịch trình thi đấu và trạng thái cọc slot</p>
                                    </div>
                                    <a href="courts.php" class="btn btn-sm btn-teal rounded-pill px-3 fw-semibold">
                                        <i class="fa-solid fa-plus me-1"></i> Đặt thêm sân
                                    </a>
                                </div>

                                <div id="profileBookingsList" class="d-flex flex-column gap-3">
                                    <!-- Dynamic Bookings Content rendered by profile.js -->
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: MÃ GIẢM GIÁ & ƯU ĐÃI -->
                        <div class="tab-pane fade" id="tabProfileVouchers" role="tabpanel">
                            <div class="profile-card">
                                <div class="d-flex align-items-center justify-content-between mb-4 pb-2 border-bottom flex-wrap gap-2">
                                    <div>
                                        <h5 class="fw-bold mb-1"><i class="fa-solid fa-ticket text-teal me-2"></i>Kho Mã Giảm Giá & Ưu Đãi</h5>
                                        <p class="text-muted small mb-0">Sao chép mã ưu đãi để áp dụng khi thanh toán đặt sân</p>
                                    </div>
                                    <a href="courts.php" class="btn btn-sm btn-teal rounded-pill px-3 fw-semibold">
                                        <i class="fa-solid fa-magnifying-glass me-1"></i> Tìm sân đặt ngay
                                    </a>
                                </div>

                                <div id="profileVouchersList" class="row g-3">
                                    <div class="col-12 text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-teal mb-2" role="status"></div>
                                        <p class="small mb-0">Đang tải danh sách ưu đãi...</p>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <!-- TAB 4: MẬT KHẨU & BẢO MẬT -->
                        <div class="tab-pane fade" id="tabProfileSecurity" role="tabpanel">
                            <div class="profile-card">
                                <div class="mb-4 pb-2 border-bottom">
                                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-lock text-teal me-2"></i>Đổi Mật Khẩu</h5>
                                    <p class="text-muted small mb-0">Cập nhật mật khẩu thường xuyên để bảo vệ tài khoản của bạn</p>
                                </div>

                                <form id="formChangePassword" style="max-width: 500px;">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Mật khẩu hiện tại</label>
                                        <input type="password" class="form-control" id="oldPass" placeholder="Nhập mật khẩu cũ..." required autocomplete="current-password">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Mật khẩu mới</label>
                                        <input type="password" class="form-control" id="newPass" minlength="8" aria-describedby="newPassRules" pattern="(?=[\s\S]*\p{Lu})(?=[\s\S]*\p{Ll})(?=[\s\S]*\p{Nd})(?=[\s\S]*[\p{P}\p{S}])[\s\S]{8,}" title="Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường, chữ số và ký tự đặc biệt." autocomplete="new-password" placeholder="Chữ hoa, chữ thường, số và ký tự đặc biệt" required>
<ul id="newPassRules" class="password-requirements" data-password-for="newPass" aria-label="Điều kiện mật khẩu"><li data-password-rule="length"><span class="password-rule-icon" aria-hidden="true">○</span><span>Tối thiểu 8 ký tự</span></li><li data-password-rule="upper"><span class="password-rule-icon" aria-hidden="true">○</span><span>Chữ hoa (A–Z)</span></li><li data-password-rule="lower"><span class="password-rule-icon" aria-hidden="true">○</span><span>Chữ thường (a–z)</span></li><li data-password-rule="number"><span class="password-rule-icon" aria-hidden="true">○</span><span>Chữ số (0–9)</span></li><li data-password-rule="special"><span class="password-rule-icon" aria-hidden="true">○</span><span>Ký tự đặc biệt (!, @, #…)</span></li></ul><span class="password-rules-status" aria-live="polite" data-password-status="newPass"></span>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold small">Xác nhận mật khẩu mới</label>
                                        <input type="password" class="form-control" id="confirmPass" placeholder="Nhập lại mật khẩu mới..." required>
                                    </div>
                                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">
                                        <i class="fa-solid fa-key me-1"></i> Cập nhật mật khẩu
                                    </button>
                                </form>
<section class="mt-4 pt-4 border-top" aria-labelledby="deleteAccountTitle">
    <h6 id="deleteAccountTitle" class="text-danger fw-bold">Xóa tài khoản</h6>
    <p class="text-muted small">Nếu không còn sử dụng GODMINTON, bạn có thể yêu cầu xóa tài khoản. Thông tin hồ sơ sẽ được ẩn danh; lịch sử giao dịch được giữ để đối soát.</p>
    <button type="button" id="btnDeleteAccount" class="btn btn-outline-danger rounded-pill px-4"><i class="fa-solid fa-user-xmark me-2"></i>Xóa tài khoản</button>
</section>

                            </div>
                        </div>

                    </div><!-- /tab-content -->
                </div><!-- /col-lg-9 -->
            </div><!-- /row -->

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-custom mt-5 pt-5 pb-4 border-top">
        <div class="container text-center small text-light">
            <p style="color:#cbd5e1">&copy; 2026 GODMINTON - Đồ án môn Lập trình Web & Ứng dụng. Dữ liệu hồ sơ và lịch sử được lấy từ MySQL.</p>
        </div>
    </footer>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/swal-godminton.js?v=<?= filemtime(__DIR__ . '/assets/js/swal-godminton.js') ?>"></script>
    <!-- API helpers -->
    <script src="assets/js/booking-account-client.js?v=<?= filemtime(__DIR__ . '/assets/js/booking-account-client.js') ?>"></script>
    <!-- Web Push Client -->
    <script src="assets/js/push-client.js?v=<?= filemtime(__DIR__ . '/assets/js/push-client.js') ?>"></script>
    <!-- App JS -->
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <!-- Profile JS -->
    <script src="assets/js/profile.js?v=<?= filemtime(__DIR__ . '/assets/js/profile.js') ?>"></script>
    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
<script src="assets/js/password-requirements.js?v=<?= filemtime(__DIR__.'/assets/js/password-requirements.js') ?>"></script>
</body>
</html>
