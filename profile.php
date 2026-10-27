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

        /* Nút Đổi tài khoản khác: căn giữa, nền trắng viền xanh ngọc thanh lịch, chữ #0f766e */
        .btn-switch-account {
            color: #0f766e !important;
            background-color: #ffffff !important;
            border: 1.5px solid #92cfc9 !important;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
        }
        .btn-switch-account i {
            color: #0f766e !important;
            transition: color 0.2s ease;
        }
        .btn-switch-account:hover,
        .btn-switch-account:focus-visible {
            color: #ffffff !important;
            background-color: #0d9488 !important;
            border-color: #0d9488 !important;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.28) !important;
            transform: translateY(-2px);
        }
        .btn-switch-account:hover i,
        .btn-switch-account:focus-visible i {
            color: #ffffff !important;
        }

        /* Nút Đăng xuất tài khoản: căn giữa, nền hồng phấn #fef4f4, viền #f8b7b7, chữ #b91c1c */
        .btn-logout-account {
            color: #b91c1c !important;
            background-color: #fef4f4 !important;
            border: 1.5px solid #f8b7b7 !important;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1) !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            text-align: center !important;
        }
        .btn-logout-account i {
            color: #b91c1c !important;
            transition: color 0.2s ease;
        }
        .btn-logout-account:hover,
        .btn-logout-account:focus-visible {
            color: #ffffff !important;
            background-color: #dc2626 !important;
            border-color: #dc2626 !important;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.28) !important;
            transform: translateY(-2px);
        }
        .btn-logout-account:hover i,
        .btn-logout-account:focus-visible i {
            color: #ffffff !important;
        }

        /* Dark mode cho 2 nút */
        [data-bs-theme="dark"] .btn-switch-account {
            color: #2dd4bf !important;
            background-color: rgba(20, 184, 166, 0.1) !important;
            border-color: rgba(45, 212, 191, 0.4) !important;
        }
        [data-bs-theme="dark"] .btn-switch-account i {
            color: #2dd4bf !important;
        }
        [data-bs-theme="dark"] .btn-switch-account:hover,
        [data-bs-theme="dark"] .btn-switch-account:focus-visible {
            color: #ffffff !important;
            background-color: #0d9488 !important;
            border-color: #0d9488 !important;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35) !important;
        }
        [data-bs-theme="dark"] .btn-switch-account:hover i,
        [data-bs-theme="dark"] .btn-switch-account:focus-visible i {
            color: #ffffff !important;
        }

        [data-bs-theme="dark"] .btn-logout-account {
            color: #f87171 !important;
            background-color: rgba(248, 113, 113, 0.1) !important;
            border-color: rgba(248, 113, 113, 0.4) !important;
        }
        [data-bs-theme="dark"] .btn-logout-account i {
            color: #f87171 !important;
        }
        [data-bs-theme="dark"] .btn-logout-account:hover,
        [data-bs-theme="dark"] .btn-logout-account:focus-visible {
            color: #ffffff !important;
            background-color: #dc2626 !important;
            border-color: #dc2626 !important;
            box-shadow: 0 4px 14px rgba(220, 38, 38, 0.35) !important;
        }
        [data-bs-theme="dark"] .btn-logout-account:hover i,
        [data-bs-theme="dark"] .btn-logout-account:focus-visible i {
            color: #ffffff !important;
        }

        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }
        .sepay-wait-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0d9488;
            margin-right: 6px;
            animation: pulseDot 1.4s infinite ease-in-out;
        }

        /* Responsive */
        @media (max-width: 991.98px) {
            .profile-nav-pills {
                flex-direction: row !important;
                overflow-x: auto;
                flex-wrap: nowrap;
                gap: 0.5rem !important;
                padding-bottom: 0.5rem;
                -webkit-overflow-scrolling: touch;
            }
            .profile-nav-pills .nav-link {
                white-space: nowrap;
                flex-shrink: 0;
                padding: 0.6rem 1rem;
                font-size: 0.88rem;
            }
            .profile-card-actions {
                display: flex;
                flex-direction: row;
                flex-wrap: wrap;
                gap: 0.5rem;
            }
            .profile-card-actions > * {
                flex: 1 1 calc(50% - 0.25rem);
            }
            .profile-card-actions > .btn-teal {
                flex: 1 1 100%;
            }
        }

        @media (max-width: 576px) {
            .profile-hero {
                padding: 1.5rem 1rem !important;
                border-radius: 1.15rem;
                margin-bottom: 1.25rem;
            }
            .profile-avatar-wrapper {
                width: 68px !important;
                height: 68px !important;
                font-size: 1.75rem !important;
            }
            .profile-stat-box {
                padding: 0.75rem 0.45rem !important;
                border-radius: 0.75rem;
            }
            .profile-stat-box .fs-4 {
                font-size: 1.2rem !important;
            }
            .profile-card {
                padding: 1.15rem 1rem !important;
                border-radius: 1rem;
            }
        }

        @media (max-width: 380px) {
            .profile-hero {
                padding: 1.15rem 0.75rem !important;
            }
            .profile-avatar-wrapper {
                width: 54px !important;
                height: 54px !important;
                font-size: 1.4rem !important;
            }
            #profileHeaderName {
                font-size: 1.15rem !important;
            }
            .profile-stat-box {
                padding: 0.5rem 0.2rem !important;
            }
            .profile-stat-box .small {
                font-size: 0.68rem !important;
            }
            .profile-card-actions > * {
                flex: 1 1 100% !important;
            }
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
                                <div id="profileMembershipBadge" class="mb-1">
                                    <!-- Rendered by profile.js: Hội viên Bạc / Vàng / Bạch Kim -->
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
                                    <div class="fs-5 fw-extrabold text-warning text-truncate" id="statMembershipTier">—</div>
                                    <div class="small opacity-80" style="font-size: 0.75rem;">Gói hội viên</div>
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
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tabProfileMembership" type="button" role="tab" id="tabBtnMembership">
                                <i class="fa-solid fa-crown text-warning"></i> Gói Hội Viên
                            </button>
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tabProfileSecurity" type="button" role="tab">
                                <i class="fa-solid fa-shield-halved"></i> Mật khẩu & Bảo mật
                            </button>
                        </div>

                        <hr class="my-3 border-secondary opacity-25">

                        <!-- Quick actions -->
                        <div class="profile-card-actions d-flex flex-column gap-2">
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
                    <div class="mb-3"><a href="owner-application.php" class="btn btn-outline-success rounded-pill"><i class="fa-solid fa-building-shield me-2"></i>Đăng ký / Theo dõi hồ sơ sân</a></div>
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
                                                <select class="form-select border-start-0" id="inpDistrict">
                                                    <option value="Quận 1" selected>Quận 1, TP.HCM</option>
                                                    <option value="Quận 3">Quận 3, TP.HCM</option>
                                                    <option value="Quận 7">Quận 7, TP.HCM</option>
                                                    <option value="Quận 10">Quận 10, TP.HCM</option>
                                                    <option value="Bình Thạnh">Bình Thạnh, TP.HCM</option>
                                                    <option value="TP. Thủ Đức">TP. Thủ Đức, TP.HCM</option>
                                                    <option value="Tân Bình">Tân Bình, TP.HCM</option>
                                                    <option value="Gò Vấp">Gò Vấp, TP.HCM</option>
                                                    <option value="Phú Nhuận">Phú Nhuận, TP.HCM</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Trình độ chơi cầu lông</label>
                                            <select class="form-select" id="inpLevel">
                                                <option value="beginner">Người mới chơi (Dưới 6 tháng)</option>
                                                <option value="intermediate" selected>Trung bình (Phong trào 1 - 3 năm)</option>
                                                <option value="advanced">Bán chuyên / Chuyên nghiệp (Trên 3 năm)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Thời gian chơi yêu thích</label>
                                            <select class="form-select" id="inpPreferredTime">
                                                <option value="evening" selected>Buổi tối (18:00 - 22:00)</option>
                                                <option value="morning">Buổi sáng (06:00 - 09:00)</option>
                                                <option value="afternoon">Buổi chiều (14:00 - 18:00)</option>
                                                <option value="weekend">Cuối tuần (Thứ 7 & CN)</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="resetProfileForm(true)">Khôi phục</button>
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
<section class="mt-4 pt-4 border-top" aria-labelledby="ownerApplicationTitle">
<h6 id="ownerApplicationTitle" class="fw-bold">Đăng ký sân trên GODMINTON</h6>
<p class="text-muted small">Gửi hồ sơ sân mới hoặc yêu cầu nhận quản lý sân có sẵn. Quyền chủ sân được cấp sau khi Admin xét duyệt.</p>
<a href="owner-application.php" class="btn btn-outline-success rounded-pill px-4">Đăng ký / Theo dõi hồ sơ chủ sân</a></section>
<section class="mt-4 pt-4 border-top" aria-labelledby="deleteAccountTitle">
    <h6 id="deleteAccountTitle" class="text-danger fw-bold">Xóa tài khoản</h6>
    <p class="text-muted small">Nếu không còn sử dụng GODMINTON, bạn có thể yêu cầu xóa tài khoản. Thông tin hồ sơ sẽ được ẩn danh; lịch sử giao dịch được giữ để đối soát.</p>
    <button type="button" id="btnDeleteAccount" class="btn btn-outline-danger rounded-pill px-4"><i class="fa-solid fa-user-xmark me-2"></i>Xóa tài khoản</button>
</section>

                            </div>
                        </div>

                        <!-- TAB 5: GÓI HỘI VIÊN -->
                        <div class="tab-pane fade" id="tabProfileMembership" role="tabpanel">
                            <div class="profile-card">
                                <div class="mb-4 pb-2 border-bottom">
                                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-crown text-warning me-2"></i>Gói Hội Viên Của Tôi</h5>
                                </div>

                                <!-- Box chi tiết gói hiện tại (Render bởi profile.js) -->
                                <div id="profileMembershipActiveBox" class="mb-4">
                                    <div class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-teal mb-2" role="status"></div>
                                        <p class="small mb-0">Đang tải thông tin gói hội viên...</p>
                                    </div>
                                </div>

                                <!-- Lịch sử đăng ký gói -->
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-clock-rotate-left text-muted me-2"></i>Lịch sử đăng ký & gia hạn gói</h6>
                                <div id="profileMembershipHistoryList" class="d-flex flex-column gap-2">
                                    <!-- Rendered by profile.js -->
                                </div>
                            </div>
                        </div>

                    </div><!-- /tab-content -->
                </div><!-- /col-lg-9 -->
            </div><!-- /row -->

        </div>
    </main>

    <!-- Modal Gia Hạn / Thanh Toán Gói Hội Viên (Tích hợp SePay VietQR 24/7 tự động) -->
    <div class="modal fade" id="modalRenewMembership" tabindex="-1" aria-labelledby="modalRenewLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <!-- BƯỚC 1: XÁC NHẬN GIA HẠN & KHỞI TẠO VIETQR SEPAY -->
                <div id="renewStep1">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="modalRenewLabel">
                            <i class="fa-solid fa-arrows-rotate text-warning me-2"></i>Gia Hạn Gói Hội Viên
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <div class="p-3 rounded-3 mb-3 bg-body-tertiary border border-secondary border-opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                                <span class="badge bg-teal text-white fw-bold px-3 py-1 rounded-pill" id="renewPackageTitle">Gói Hội Viên</span>
                                <span class="badge bg-danger text-white fw-bold px-2 py-1 rounded-pill" id="renewDiscountBadge">-5% Tiền Sân</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="text-muted">Hạn sử dụng hiện tại:</span>
                                <strong id="renewCurrentEnd" class="text-body">--/--/----</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1 small">
                                <span class="text-muted">Thời hạn cộng dồn thêm:</span>
                                <strong class="text-success" id="renewAddDays">+1 tháng (cộng dồn)</strong>
                            </div>
                            <hr class="my-2 border-secondary opacity-15">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Phí gia hạn:</span>
                                <span class="fs-4 fw-extrabold text-teal" id="renewPriceText">-- đ</span>
                            </div>
                        </div>

                        <!-- Phương thức thanh toán: Chuyển khoản VietQR SePay 24/7 -->
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Phương thức thanh toán</label>
                        <div class="p-3 border rounded-3 d-flex align-items-center gap-3 bg-surface border-teal shadow-2xs mb-3" style="border-width: 1.5px !important;">
                            <div class="icon-circle bg-teal text-white flex-shrink-0" style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold small text-teal d-flex align-items-center justify-content-between">
                                    <span>Chuyển khoản VietQR 24/7 (SePay)</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0" style="font-size:0.7rem;">Cộng dồn tự động</span>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">Tự động cộng dồn hạn gói & gửi hóa đơn sau 2–10 giây</div>
                            </div>
                        </div>

                        <div class="p-2 rounded-3 border bg-body-tertiary text-center small text-muted">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i>
                            Thời hạn gói sẽ được <strong>cộng dồn tự động ngay khi SePay nhận thanh toán</strong>.
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm text-dark" id="btnConfirmRenew">
                            <span id="btnRenewText"><i class="fa-solid fa-qrcode me-1"></i> Tạo mã QR & Gia hạn</span>
                            <span id="btnRenewSpinner" class="spinner-border spinner-border-sm ms-1" style="display:none;" role="status"></span>
                        </button>
                    </div>
                </div>

                <!-- BƯỚC 2: HIỂN THỊ MÃ VIETQR SEPAY & TỰ ĐỘNG XÁC NHẬN -->
                <div id="renewStep2" style="display: none;">
                    <div style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); padding: 14px 20px; color: #fff;" class="d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.85; font-weight: 700;">Thanh Toán Gói Hội Viên</div>
                            <div class="fw-extrabold fs-6" id="renewStep2HeaderTitle">Gói Hội Viên GODMINTON</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-teal fw-extrabold px-2 py-1 rounded-pill font-monospace" id="renewQrTimerBadge" style="font-size: 0.82rem;">15:00</span>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                    </div>

                    <div class="modal-body p-3">
                        <!-- Mã QR VietQR -->
                        <div class="text-center mb-3">
                            <div class="p-3 d-inline-block bg-white rounded-4 border shadow-sm">
                                <img id="renewQrImg"
                                     src=""
                                     alt="VietQR SePay GODMINTON"
                                     class="img-fluid rounded-2"
                                     style="max-height: 215px; width: auto; object-fit: contain;">
                            </div>
                        </div>

                        <!-- Thông tin tài khoản ngân hàng SePay -->
                        <div class="p-3 rounded-4 bg-body-tertiary border border-secondary border-opacity-15 small mb-3">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary border-opacity-10">
                                <span class="text-muted">Số tiền cần chuyển:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-body fw-extrabold fs-5" id="renewQrPriceText">-- đ</strong>
                                    <button type="button" class="btn btn-sm btn-outline-teal py-0 px-2 rounded-2" id="btnCopyRenewPrice" title="Sao chép số tiền">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-10">
                                <span class="text-muted">Nội dung chuyển:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-teal font-monospace fs-6" id="renewTransferRef">--</strong>
                                    <button type="button" class="btn btn-sm btn-outline-teal py-0 px-2 rounded-2" id="btnCopyRenewRef" title="Sao chép nội dung">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary border-opacity-10">
                                <span class="text-muted">Ngân hàng thụ hưởng:</span>
                                <strong class="text-body text-end" id="renewBankName">MB Bank (Ngân hàng Quân Đội)</strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary border-opacity-10">
                                <span class="text-muted">Số tài khoản:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-body font-monospace" id="renewAccNum">--</strong>
                                    <button type="button" class="btn btn-sm btn-outline-teal py-0 px-2 rounded-2" id="btnCopyRenewAcc" title="Sao chép STK">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-1">
                                <span class="text-muted">Chủ tài khoản:</span>
                                <strong class="text-body" id="renewAccName">--</strong>
                            </div>
                        </div>

                        <!-- Trạng thái chờ ngân hàng xác nhận tự động -->
                        <div class="p-3 rounded-3 text-center mb-2" style="background: rgba(13, 148, 136, 0.08); border: 1px solid rgba(13, 148, 136, 0.25);" id="renewPayStatusBox">
                            <div class="fw-bold text-teal small mb-1" id="renewPayStatusTitle">
                                <span class="sepay-wait-dot"></span>Đang chờ ngân hàng xác nhận giao dịch...
                            </div>
                            <div class="text-muted" style="font-size: 0.74rem;" id="renewPayStatusDesc">
                                Quét mã bằng app ngân hàng / ví điện tử. Hệ thống tự động kích hoạt / gia hạn gói hội viên ngay khi tiền vào (thường từ 2–10 giây).
                            </div>
                        </div>

                        <!-- Nút giả lập dành cho môi trường DEV TEST -->
                        <button type="button" id="btnSimulateRenewPay" class="btn btn-sm btn-outline-warning text-dark w-100 rounded-pill fw-bold mb-2" style="display: none;">
                            <i class="fa-solid fa-flask me-1"></i> [DEV TEST] Giả lập SePay nhận tiền thành công
                        </button>

                        <div class="d-flex justify-content-between align-items-center pt-1 small">
                            <a href="#" id="renewDownloadQrLink" download class="text-teal text-decoration-none fw-semibold">
                                <i class="fa-solid fa-download me-1"></i> Tải ảnh QR
                            </a>
                            <button type="button" class="btn btn-sm btn-link text-muted text-decoration-none p-0" onclick="showRenewStep1()">
                                <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODAL: CHI TIẾT HÓA ĐƠN GÓI HỘI VIÊN (USER) ==================== -->
    <div class="modal fade" id="userMembershipInvoiceModal" tabindex="-1" aria-labelledby="modalUserInvoiceTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                    <div>
                        <h5 class="modal-title fw-bold fs-5 mb-1" id="modalUserInvoiceTitle">
                            <i class="fa-solid fa-file-invoice-dollar text-teal me-2"></i>Chi Tiết Hóa Đơn Hội Viên
                        </h5>
                        <div class="small text-muted" id="userModalInvoiceSub">Mã hóa đơn: #MB-000000</div>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <span id="userModalInvoiceStatusBadge"></span>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                </div>
                <div class="modal-body p-4" id="userModalInvoiceBody">
                    <!-- Nội dung hóa đơn render tự động bằng JS -->
                </div>
                <div class="modal-footer border-top bg-light-subtle rounded-bottom-4 d-flex justify-content-between align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.print()">
                        <i class="fa-solid fa-print me-1"></i> In hóa đơn
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>

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
<script>
// Đảm bảo nút Khôi phục luôn đặt lại toàn bộ các ô về trạng thái ban đầu kể cả khi trình duyệt lưu cache JS cũ
window.resetProfileForm = function(isManualReset = false) {
    const form = document.getElementById('formUpdateProfile');
    if (form) form.reset();
    if (typeof savedProfile !== 'undefined' && savedProfile) {
        const elName = document.getElementById('inpFullName');
        const elEmail = document.getElementById('inpEmail');
        const elPhone = document.getElementById('inpPhone');
        if (elName) elName.value = savedProfile.name || '';
        if (elEmail) elEmail.value = savedProfile.email || '';
        if (elPhone) elPhone.value = savedProfile.phone || '';
    }
    const defaults = { district: 'Quận 1', level: 'intermediate', preferredTime: 'evening' };
    const userId = (typeof savedProfile !== 'undefined' && savedProfile && savedProfile.id) ? savedProfile.id : 'default';
    if (isManualReset) {
        try { localStorage.removeItem('godminton_profile_prefs_' + userId); } catch (e) {}
    }
    let prefs = { ...defaults };
    if (!isManualReset) {
        try {
            const raw = localStorage.getItem('godminton_profile_prefs_' + userId);
            if (raw) prefs = { ...defaults, ...JSON.parse(raw) };
        } catch (e) {}
    }
    const elDist = document.getElementById('inpDistrict');
    const elLvl = document.getElementById('inpLevel');
    const elTime = document.getElementById('inpPreferredTime');
    if (elDist) elDist.value = prefs.district;
    if (elLvl) elLvl.value = prefs.level;
    if (elTime) elTime.value = prefs.preferredTime;
    if (isManualReset && window.Swal) {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'info',
            title: 'Đã khôi phục về trạng thái ban đầu',
            showConfirmButton: false,
            timer: 1800,
            timerProgressBar: true
        });
    }
};
</script>
</body>
</html>
