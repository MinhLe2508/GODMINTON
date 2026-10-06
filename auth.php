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
</head>
<body>

<button class="auth-theme-btn" id="themeToggle">
    <i class="fa-solid fa-moon"></i>
</button>

<div class="auth-wrapper">

    <!-- ========== LEFT PANEL ========== -->
    <div class="auth-left">
        <div>
            <img src="assets/img/logo.svg" alt="GODMINTON" style="height:44px; width:auto;">
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
                <p class="text-muted small mb-4">Đăng nhập để quản lý lịch đặt sân của bạn</p>

                <!-- Social -->
                <div class="d-flex gap-2 mb-0">
                    <button class="btn-social">
                        <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="17" height="17" alt="">
                        Google
                    </button>
                    <button class="btn-social">
                        <img src="https://www.svgrepo.com/show/475647/facebook-color.svg" width="17" height="17" alt="">
                        Facebook
                    </button>
                </div>

                <div class="auth-divider">hoặc đăng nhập bằng email</div>

                <form id="loginForm">
                    <div class="auth-field">
                        <label>Địa chỉ Email</label>
                        <div class="field-wrap">
                            <i class="fa-regular fa-envelope field-icon"></i>
                            <input type="email" id="loginEmail" placeholder="name@example.com" value="khach1@gmail.com">
                        </div>
                    </div>

                    <div class="auth-field">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="mb-0">Mật khẩu</label>
                            <a href="#" style="font-size:0.78rem; color:var(--brand-teal); text-decoration:none; font-weight:600;">Quên mật khẩu?</a>
                        </div>
                        <div class="field-wrap">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <input type="password" id="loginPassword" placeholder="Nhập mật khẩu..." value="Khach@123">
                            <button type="button" class="btn-eye" onclick="toggleEye('loginPassword', this)">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-flex align-items-center mb-1">
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
                        <button class="demo-pill" type="button" onclick="fillLogin('admin@datsancaulong.vn','Admin@123')">
                            <div class="demo-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);">A</div>
                            <div>
                                <div style="font-size:0.78rem; font-weight:700;">Quản trị viên</div>
                                <div style="font-size:0.7rem; color:var(--text-secondary);">admin@datsancaulong.vn / Admin@123</div>
                            </div>
                            <i class="fa-solid fa-arrow-right ms-auto" style="font-size:0.7rem; color:var(--text-secondary);"></i>
                        </button>
                    </div>
                </div>
            </div><!-- /panelLogin -->

            <!-- ===== REGISTER PANEL ===== -->
            <div class="auth-panel" id="panelRegister">
                <h4 class="fw-extrabold mb-1">Tạo tài khoản mới 🎉</h4>
                <p class="text-muted small mb-4">Đăng ký miễn phí để bắt đầu đặt sân cầu lông dễ dàng</p>

                <form id="registerForm">
                    <div class="auth-row">
                        <div class="auth-field" style="margin-bottom:0;">
                            <label>Họ và Tên *</label>
                            <div class="field-wrap">
                                <i class="fa-solid fa-user field-icon"></i>
                                <input type="text" id="regName" placeholder="Nguyễn Văn A" required>
                            </div>
                        </div>
                        <div class="auth-field" style="margin-bottom:0;">
                            <label>Số Điện Thoại *</label>
                            <div class="field-wrap">
                                <i class="fa-solid fa-phone field-icon"></i>
                                <input type="tel" id="regPhone" placeholder="0912345678" required>
                            </div>
                        </div>
                    </div>

                    <div class="auth-field mt-3">
                        <label>Email *</label>
                        <div class="field-wrap">
                            <i class="fa-regular fa-envelope field-icon"></i>
                            <input type="email" id="regEmail" placeholder="name@gmail.com" required>
                        </div>
                    </div>

                    <div class="auth-row">
                        <div class="auth-field" style="margin-bottom:0;">
                            <label>Mật Khẩu *</label>
                            <div class="field-wrap">
                                <i class="fa-solid fa-lock field-icon"></i>
                                <input type="password" id="regPassword" placeholder="Tối thiểu 6 ký tự" minlength="6" required>
                                <button type="button" class="btn-eye" onclick="toggleEye('regPassword', this)">
                                    <i class="fa-regular fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="auth-field" style="margin-bottom:0;">
                            <label>Xác Nhận Mật Khẩu *</label>
                            <div class="field-wrap">
                                <i class="fa-solid fa-lock field-icon"></i>
                                <input type="password" id="regConfirmPassword" placeholder="Nhập lại mật khẩu" required>
                            </div>
                        </div>
                    </div>

                    <div class="auth-field mt-3">
                        <label>Vai trò đăng ký</label>
                        <div class="field-wrap">
                            <i class="fa-solid fa-id-badge field-icon"></i>
                            <select id="regRole" style="padding-left:2.5rem;">
                                <option value="customer">Khách hàng — Đặt sân thể thao</option>
                                <option value="owner">Chủ sân — Đăng ký quản lý sân</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex align-items-start gap-2 mb-1 mt-1">
                        <input type="checkbox" class="form-check-input mt-1 flex-shrink-0" id="agreeTerms" required>
                        <label class="small" for="agreeTerms" style="cursor:pointer;">
                            Tôi đồng ý với <a href="#" style="color:var(--brand-teal);">Điều khoản sử dụng</a> và <a href="#" style="color:var(--brand-teal);">Chính sách bảo mật</a> của GODMINTON
                        </label>
                    </div>

                    <button type="submit" class="btn-auth-submit" id="btnRegisterSubmit">
                        <i class="fa-solid fa-user-plus me-2"></i>Tạo Tài Khoản Miễn Phí
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
<script src="assets/js/app.js"></script>
<script src="assets/js/auth.js"></script>
<!-- Bottom Nav -->
<script src="assets/js/bottom-nav.js"></script>
</body>
</html>
