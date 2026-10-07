<?php
/**
 * profile.php — Trang Quản Lý Hồ Sơ Cá Nhân | GODMINTON
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 * Cập nhật Task #5 & #6:
 * - Tách và tích hợp partials: navbar.php, bottom-nav.php, footer.php, ui-states.php
 * - Dữ liệu mẫu tập trung từ sample_data.php
 * - Gỡ bỏ tính năng voucher / godpoints bị cắt giảm
 * - Chuyển toàn bộ liên kết sang .php
 */

// DỮ LIỆU MẪU - thay bằng truy vấn DB ở tuần 2
require_once __DIR__ . '/includes/sample_data.php';

$currentPage = 'profile';
$assetsPath = 'assets';
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang Cá Nhân | GODMINTON</title>
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
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- UI States CSS -->
    <link rel="stylesheet" href="assets/css/ui-states.css">
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
    </style>
</head>

<body>

    <!-- App Brand Header Partial -->
    <?php include __DIR__ . '/includes/partials/navbar.php'; ?>

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
                                    <h3 class="fw-extrabold mb-0 text-white" id="profileHeaderName">Nguyễn Văn An</h3>
                                    <span class="badge bg-warning text-dark fw-bold rounded-pill px-2 py-1">
                                        <i class="fa-solid fa-crown me-1"></i>Thành viên
                                    </span>
                                </div>
                                <div class="opacity-90 small mb-1" id="profileHeaderEmail">
                                    <i class="fa-regular fa-envelope me-1"></i> khach1@gmail.com
                                </div>
                                <div class="opacity-75 small">
                                    <i class="fa-regular fa-calendar-check me-1"></i> Khách hàng thân thiết · TP. Hồ Chí Minh
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats -->
                    <div class="col-md-5">
                        <div class="row g-2">
                            <div class="col-4">
                                <div class="profile-stat-box">
                                    <div class="fs-4 fw-extrabold text-warning" id="statBookingsCount"><?= count($sample_bookings) ?></div>
                                    <div class="small opacity-80" style="font-size: 0.75rem;">Lượt đặt sân</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="profile-stat-box">
                                    <div class="fs-4 fw-extrabold text-teal">Bạc</div>
                                    <div class="small opacity-80" style="font-size: 0.75rem;">Hạng hội viên</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="profile-stat-box">
                                    <div class="fs-4 fw-extrabold text-success">Đang bật</div>
                                    <div class="small opacity-80" style="font-size: 0.75rem;">Trạng thái</div>
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
                            <button class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tabProfileSecurity" type="button" role="tab">
                                <i class="fa-solid fa-shield-halved"></i> Mật khẩu & Bảo mật
                            </button>
                        </div>

                        <hr class="my-3 border-secondary opacity-25">

                        <!-- Quick actions -->
                        <div class="d-flex flex-column gap-2">
                            <a href="courts.php" class="btn btn-teal rounded-pill w-100 fw-bold">
                                <i class="fa-solid fa-plus me-1"></i> Đặt sân mới
                            </a>
                            <a href="admin-courts.php" class="btn btn-outline-secondary rounded-pill w-100 fw-semibold text-start px-3">
                                <i class="fa-solid fa-gauge-high me-2 text-warning"></i> Chuyển trang Admin
                            </a>
                            <a href="auth.php" class="btn btn-outline-secondary rounded-pill w-100 fw-semibold text-start px-3">
                                <i class="fa-solid fa-arrow-right-arrow-left me-2 text-primary"></i> Đổi tài khoản khác
                            </a>
                            <button type="button" class="btn btn-outline-danger rounded-pill w-100 fw-semibold text-start px-3" onclick="if(confirm('Bạn có chắc chắn muốn đăng xuất không?')) { if(typeof MockDB !== 'undefined' && MockDB.logout) MockDB.logout(); window.location.href='auth.php'; }">
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
                                        <p class="text-muted small mb-0">Quản lý thông tin liên hệ của bạn tại GODMINTON</p>
                                    </div>
                                    <span class="badge bg-teal-subtle text-teal px-3 py-2 rounded-pill fw-bold">Tài khoản Hoạt động</span>
                                </div>

                                <form id="formUpdateProfile">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Họ và tên <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-transparent"><i class="fa-regular fa-user text-muted"></i></span>
                                                <input type="text" class="form-control" id="inpFullName" value="Nguyễn Văn An" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Địa chỉ Email <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-transparent"><i class="fa-regular fa-envelope text-muted"></i></span>
                                                <input type="email" class="form-control" id="inpEmail" value="khach1@gmail.com" required>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Số điện thoại liên hệ</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-transparent"><i class="fa-solid fa-phone text-muted"></i></span>
                                                <input type="tel" class="form-control" id="inpPhone" value="0912 345 678">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold small">Vai trò tài khoản</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-transparent"><i class="fa-solid fa-id-badge text-muted"></i></span>
                                                <input type="text" class="form-control bg-light text-muted" value="Khách hàng (customer)" disabled readonly>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-4 pt-2 border-top d-flex gap-2 justify-content-end">
                                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="resetProfileForm()">Đặt lại</button>
                                            <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">
                                                <i class="fa-solid fa-floppy-disk me-1"></i> Lưu thay đổi
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- TAB 2: LỊCH SỬ ĐẶT SÂN -->
                        <div class="tab-pane fade" id="tabProfileBookings" role="tabpanel">
                            <div class="profile-card">
                                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
                                    <div>
                                        <h5 class="fw-bold mb-1"><i class="fa-solid fa-clock-rotate-left text-teal me-2"></i>Lịch Sử Đặt Sân Gần Đây</h5>
                                        <p class="text-muted small mb-0">Theo dõi trạng thái các đơn đặt sân cầu lông của bạn</p>
                                    </div>
                                    <a href="courts.php" class="btn btn-sm btn-outline-teal rounded-pill px-3">
                                        <i class="fa-solid fa-plus me-1"></i>Đặt sân mới
                                    </a>
                                </div>

                                <div id="profileBookingsList" class="d-flex flex-column gap-3">
                                    <!-- Dynamic Bookings Content rendered by profile.js -->
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: MẬT KHẨU & BẢO MẬT -->
                        <div class="tab-pane fade" id="tabProfileSecurity" role="tabpanel">
                            <div class="profile-card">
                                <div class="mb-4 pb-2 border-bottom">
                                    <h5 class="fw-bold mb-1"><i class="fa-solid fa-lock text-teal me-2"></i>Đổi Mật Khẩu</h5>
                                    <p class="text-muted small mb-0">Cập nhật mật khẩu thường xuyên để bảo vệ tài khoản của bạn</p>
                                </div>

                                <form id="formChangePassword" style="max-width: 500px;">
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Mật khẩu hiện tại</label>
                                        <input type="password" class="form-control" id="oldPass" placeholder="Nhập mật khẩu cũ..." value="Khach@123">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small">Mật khẩu mới</label>
                                        <input type="password" class="form-control" id="newPass" placeholder="Tối thiểu 6 ký tự..." required>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label fw-bold small">Xác nhận mật khẩu mới</label>
                                        <input type="password" class="form-control" id="confirmPass" placeholder="Nhập lại mật khẩu mới..." required>
                                    </div>
                                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">
                                        <i class="fa-solid fa-key me-1"></i> Cập nhật mật khẩu
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div><!-- /tab-content -->
                </div><!-- /col-lg-9 -->
            </div><!-- /row -->

        </div>
    </main>

    <!-- UI States Toast & Loading Partial -->
    <?php include __DIR__ . '/includes/partials/ui-states.php'; ?>

    <!-- Footer Partial -->
    <?php include __DIR__ . '/includes/partials/footer.php'; ?>

    <!-- Bottom Navigation Partial -->
    <?php include __DIR__ . '/includes/partials/bottom-nav.php'; ?>

    <!-- Bootstrap 5.3 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Mock Data -->
    <script src="assets/js/mock-data.js"></script>
    <!-- UI States JS -->
    <script src="assets/js/ui-states.js"></script>
    <!-- App JS -->
    <script src="assets/js/app.js"></script>
    <!-- Profile JS -->
    <script src="assets/js/profile.js"></script>
    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js"></script>
</body>
</html>
