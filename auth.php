<?php
/**
 * auth.php — Trang Đăng Nhập / Đăng Ký GODMINTON
 * Tuần 1: Task #6 — Đơn giản hóa form đăng ký và gỡ social login.
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 */
$currentPage = 'auth';
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng Nhập / Đăng Ký | GODMINTON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/auth_style.css">
    <link rel="stylesheet" href="assets/css/ui-states.css">
</head>
<body>

<button class="auth-theme-btn" id="themeToggle" title="Chuyển chế độ sáng/tối">
    <i class="fa-solid fa-moon"></i>
</button>

<div class="auth-wrapper">

    <!-- ========== LEFT PANEL ========== -->
    <div class="auth-left">
        <div>
            <a href="index.php">
                <img src="assets/img/logo.svg" alt="GODMINTON" style="height:44px; width:auto;">
            </a>
        </div>

        <div class="my-auto py-4">
            <div class="auth-badge-row mb-3">
                <span class="auth-badge"><i class="fa-solid fa-bolt-lightning"></i> REAL-TIME SLOT</span>
                <span class="auth-badge"><i class="fa-solid fa-shield-check"></i> BẢO MẬT</span>
            </div>
            <h2 class="auth-hero-title mb-3">
                Đặt Sân Cầu Lông<br>
                <span style="color:#2dd4bf;">Mọi Lúc, Mọi Nơi</span>
            </h2>
            <p style="opacity:0.82; font-size:0.95rem; line-height:1.7; max-width:380px;">
                Hệ thống đặt sân thông minh — kiểm tra slot trống tức thì, khóa chỗ tự động chống đặt trùng và nhận thông báo lịch đấu qua thiết bị của bạn.
            </p>
        </div>

        <div class="auth-stat-row">
            <div class="auth-stat">
                <div class="auth-stat-num">4+</div>
                <div class="auth-stat-label">Cụm sân tiêu chuẩn</div>
            </div>
            <div class="auth-stat">
                <div class="auth-stat-num">5s</div>
                <div class="auth-stat-label">Cập nhật real-time</div>
            </div>
            <div class="auth-stat">
                <div class="auth-stat-num">24/7</div>
                <div class="auth-stat-label">Đặt sân mọi lúc</div>
            </div>
        </div>
    </div>

    <!-- ========== RIGHT PANEL ========== -->
    <div class="auth-right">
        <div class="auth-form-box">

            <!-- Tab switcher -->
            <div class="auth-tabs" role="tablist">
                <button class="auth-tab-btn active" id="tabLoginBtn" onclick="switchTab('login')">
                    <i class="fa-solid fa-right-to-bracket me-1"></i>Đăng Nhập
                </button>
                <button class="auth-tab-btn" id="tabRegisterBtn" onclick="switchTab('register')">
                    <i class="fa-solid fa-user-plus me-1"></i>Đăng Ký
                </button>
            </div>

            <!-- ===== LOGIN PANEL ===== -->
            <div class="auth-panel active" id="panelLogin">
                <h4 class="fw-extrabold mb-1">Chào mừng trở lại! 👋</h4>
                <p class="text-muted small mb-4">Đăng nhập bằng tài khoản để quản lý lịch đặt sân của bạn</p>

                <form id="loginForm" novalidate>
                    <div class="auth-field">
                        <label for="loginEmail">Địa chỉ Email</label>
                        <div class="field-wrap">
                            <i class="fa-regular fa-envelope field-icon"></i>
                            <input type="email" id="loginEmail" placeholder="name@example.com" value="khach1@gmail.com" required>
                        </div>
                    </div>

                    <div class="auth-field">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="loginPassword" class="mb-0">Mật khẩu</label>
                            <a href="#" style="font-size:0.78rem; color:var(--brand-teal); text-decoration:none; font-weight:600;">Quên mật khẩu?</a>
                        </div>
                        <div class="field-wrap">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <input type="password" id="loginPassword" placeholder="Nhập mật khẩu..." value="Khach@123" required>
                            <button type="button" class="btn-eye" onclick="toggleEye('loginPassword', this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <input type="checkbox" class="form-check-input me-2 mt-0" id="rememberMe" checked>
                        <label class="small" for="rememberMe" style="cursor:pointer;">Ghi nhớ đăng nhập trên thiết bị này</label>
                    </div>

                    <button type="submit" class="btn-auth-submit" id="btnLoginSubmit">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Đăng Nhập
                    </button>
                </form>

                <p class="text-center small text-muted mt-3 mb-3">
                    Chưa có tài khoản?
                    <button type="button" class="border-0 bg-transparent p-0 fw-bold" style="color:var(--brand-teal); cursor:pointer;" onclick="switchTab('register')">Đăng ký miễn phí</button>
                </p>

                <!-- Demo accounts -->
                <div class="pt-3 border-top">
                    <p class="text-center text-muted small fw-semibold mb-2">
                        <i class="fa-solid fa-key me-1" style="color:var(--brand-teal);"></i>Tài khoản demo nhanh
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <button class="demo-pill" type="button" onclick="fillLogin('khach1@gmail.com','Khach@123')">
                            <div class="demo-avatar" style="background:linear-gradient(135deg,#0d9488,#0f766e);">K</div>
                            <div>
                                <div style="font-size:0.78rem; font-weight:700;">Khách hàng</div>
                                <div style="font-size:0.7rem; color:var(--text-secondary);">khach1@gmail.com / Khach@123</div>
                            </div>
                            <i class="fa-solid fa-arrow-right ms-auto" style="font-size:0.7rem; color:var(--text-secondary);"></i>
                        </button>
                        <button class="demo-pill" type="button" onclick="fillLogin('admin@godminton.vn','Admin@123')">
                            <div class="demo-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);">A</div>
                            <div>
                                <div style="font-size:0.78rem; font-weight:700;">Quản trị viên</div>
                                <div style="font-size:0.7rem; color:var(--text-secondary);">admin@godminton.vn / Admin@123</div>
                            </div>
                            <i class="fa-solid fa-arrow-right ms-auto" style="font-size:0.7rem; color:var(--text-secondary);"></i>
                        </button>
                    </div>
                </div>
            </div><!-- /panelLogin -->

            <!-- ===== REGISTER PANEL (ĐÃ ĐƠN GIẢN HÓA 3 TRƯỜNG THEO TASK #6) ===== -->
            <div class="auth-panel" id="panelRegister">
                <h4 class="fw-extrabold mb-1">Tạo tài khoản mới 🎉</h4>
                <p class="text-muted small mb-4">Đăng ký tài khoản khách hàng để đặt sân nhanh chóng</p>

                <form id="registerForm" novalidate>
                    <!-- Trường 1: Email -->
                    <div class="auth-field">
                        <label for="regEmail">Email *</label>
                        <div class="field-wrap">
                            <i class="fa-regular fa-envelope field-icon"></i>
                            <input type="email" id="regEmail" placeholder="name@example.com" autocomplete="email" required>
                        </div>
                        <div class="auth-field-error d-none" id="regEmailError"></div>
                    </div>

                    <!-- Trường 2: Tên hiển thị -->
                    <div class="auth-field mt-3">
                        <label for="regName">Tên hiển thị *</label>
                        <div class="field-wrap">
                            <i class="fa-solid fa-user field-icon"></i>
                            <input type="text" id="regName" placeholder="Nguyễn Văn A" autocomplete="name" required>
                        </div>
                        <div class="auth-field-error d-none" id="regNameError"></div>
                    </div>

                    <!-- Trường 3: Mật khẩu (nhập 2 lần) -->
                    <div class="auth-row mt-3">
                        <div class="auth-field" style="margin-bottom:0;">
                            <label for="regPassword">Mật khẩu *</label>
                            <div class="field-wrap">
                                <i class="fa-solid fa-lock field-icon"></i>
                                <input type="password" id="regPassword" placeholder="Tối thiểu 6 ký tự" minlength="6" autocomplete="new-password" required>
                                <button type="button" class="btn-eye" onclick="toggleEye('regPassword', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div class="auth-field-error d-none" id="regPasswordError"></div>
                        </div>
                        <div class="auth-field" style="margin-bottom:0;">
                            <label for="regConfirmPassword">Xác nhận mật khẩu *</label>
                            <div class="field-wrap">
                                <i class="fa-solid fa-lock field-icon"></i>
                                <input type="password" id="regConfirmPassword" placeholder="Nhập lại mật khẩu" autocomplete="new-password" required>
                                <button type="button" class="btn-eye" onclick="toggleEye('regConfirmPassword', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                            <div class="auth-field-error d-none" id="regConfirmPasswordError"></div>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-2 mb-1 mt-3">
                        <input type="checkbox" class="form-check-input mt-1 flex-shrink-0" id="agreeTerms" required checked>
                        <label class="small" for="agreeTerms" style="cursor:pointer;">
                            Tôi đồng ý với <a href="#" style="color:var(--brand-teal);">Điều khoản dịch vụ</a> và <a href="#" style="color:var(--brand-teal);">Chính sách bảo mật</a> của GODMINTON
                        </label>
                    </div>

                    <button type="submit" class="btn-auth-submit mt-3" id="btnRegisterSubmit">
                        <i class="fa-solid fa-user-plus me-2"></i>Tạo Tài Khoản Khách Hàng
                    </button>
                </form>

                <p class="text-center small text-muted mt-3">
                    Đã có tài khoản?
                    <button type="button" class="border-0 bg-transparent p-0 fw-bold" style="color:var(--brand-teal); cursor:pointer;" onclick="switchTab('login')">Đăng nhập ngay</button>
                </p>
            </div><!-- /panelRegister -->

        </div><!-- /auth-form-box -->
    </div><!-- /auth-right -->
</div><!-- /auth-wrapper -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/mock-data.js"></script>
<script src="assets/js/ui-states.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/auth.js"></script>
<!-- Bottom Nav -->
<script src="assets/js/bottom-nav.js"></script>
</body>
</html>
