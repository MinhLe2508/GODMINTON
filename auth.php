<?php
// Tự sinh timestamp chống cache trình duyệt
$v = time();
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
    <link rel="stylesheet" href="assets/css/style.css?v=<?= $v ?>">
    <link rel="stylesheet" href="assets/css/auth_style.css?v=<?= $v ?>">

    <!-- Glassmorphism & Theme Tuning CSS -->
    <style>
        /* ---- Body Reset & Sports Background ---- */
        body {
            min-height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow-x: hidden !important;
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif !important;
            background: linear-gradient(135deg, rgba(4, 20, 17, 0.38) 0%, rgba(2, 12, 10, 0.55) 100%),
                        url('assets/img/auth-bg.jpg?v=<?= $v ?>') center/cover no-repeat fixed !important;
            transition: background 0.3s ease, color 0.3s ease !important;
        }
        body.has-bottom-nav {
            padding-bottom: 0 !important;
        }
        #godmintonBottomNav, .godminton-bottom-nav {
            display: none !important;
            visibility: hidden !important;
        }

        /* ---- Top Navigation Bar ---- */
        .auth-top-bar {
            position: fixed !important;
            top: 0; left: 0; right: 0;
            height: 68px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 1.5rem;
            z-index: 100;
            pointer-events: none;
        }
        .auth-top-bar > * {
            pointer-events: auto;
        }
        .auth-back-link {
            display: inline-flex !important;
            align-items: center;
            gap: 0.55rem;
            padding: 0.5rem 1.15rem;
            border-radius: 9999px;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            font-weight: 700;
            text-decoration: none !important;
            font-size: 0.88rem;
            transition: all 0.22s ease !important;
        }
        /* Light mode back button */
        [data-bs-theme="light"] .auth-back-link {
            background: rgba(255, 255, 255, 0.28) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        [data-bs-theme="light"] .auth-back-link:hover {
            background: rgba(255, 255, 255, 0.45) !important;
            transform: translateX(-3px);
        }
        /* Dark mode back button */
        [data-bs-theme="dark"] .auth-back-link {
            background: rgba(4, 18, 22, 0.65) !important;
            border: 1px solid rgba(45, 212, 191, 0.35) !important;
            color: #2dd4bf !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }
        [data-bs-theme="dark"] .auth-back-link:hover {
            background: rgba(13, 148, 136, 0.3) !important;
            border-color: #2dd4bf !important;
            color: #ffffff !important;
            transform: translateX(-3px);
        }

        /* Theme toggle button */
        .auth-theme-btn {
            width: 44px; height: 44px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            transition: all 0.22s ease !important;
            font-size: 1.05rem;
        }
        [data-bs-theme="light"] .auth-theme-btn {
            background: rgba(255, 255, 255, 0.28) !important;
            border: 1px solid rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        [data-bs-theme="light"] .auth-theme-btn:hover {
            background: rgba(255, 255, 255, 0.45) !important;
            transform: scale(1.08) rotate(15deg);
        }
        [data-bs-theme="dark"] .auth-theme-btn {
            background: rgba(4, 18, 22, 0.65) !important;
            border: 1px solid rgba(45, 212, 191, 0.35) !important;
            color: #fbbf24 !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
        }
        [data-bs-theme="dark"] .auth-theme-btn:hover {
            background: rgba(13, 148, 136, 0.3) !important;
            border-color: #2dd4bf !important;
            transform: scale(1.08) rotate(15deg);
        }

        /* ---- Center Viewport ---- */
        .auth-viewport {
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 5.25rem 1rem 3rem !important;
            position: relative;
        }

        /* ---- GLASSMORPHISM AUTH CARD ---- */
        .auth-card {
            width: 100% !important;
            max-width: 560px !important;
            border-radius: 1.6rem !important;
            padding: 2.5rem 2.75rem !important;
            margin: 0 auto !important;
            position: relative !important;
            backdrop-filter: blur(24px) saturate(190%) !important;
            -webkit-backdrop-filter: blur(24px) saturate(190%) !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }

        /* Light Mode Card (Trong suốt Frosted Glass mờ ảo) */
        [data-bs-theme="light"] .auth-card {
            background: rgba(255, 255, 255, 0.85) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.75) !important;
            box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.4) !important;
            color: #0f172a !important;
        }

        /* Dark Mode Card (Trong suốt Deep Emerald Neon mờ ảo) */
        [data-bs-theme="dark"] .auth-card {
            background: rgba(6, 25, 29, 0.82) !important;
            border: 1.5px solid rgba(45, 212, 191, 0.38) !important;
            box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.88), 0 0 35px rgba(13, 148, 136, 0.3) !important;
            color: #f8fafc !important;
        }

        /* Logo Brand */
        .auth-brand-wrap { text-align: center; margin-bottom: 1.4rem; }
        .auth-brand-logo { height: 44px; width: auto; filter: drop-shadow(0 3px 10px rgba(13, 148, 136, 0.25)); }

        /* Tabs Switcher */
        .auth-tabs {
            display: flex;
            border-radius: 0.85rem;
            padding: 4px;
            margin-bottom: 1.6rem;
            gap: 4px;
            transition: background 0.25s ease, border-color 0.25s ease;
        }
        [data-bs-theme="light"] .auth-tabs {
            background: rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(0, 0, 0, 0.06);
        }
        [data-bs-theme="dark"] .auth-tabs {
            background: rgba(0, 0, 0, 0.45);
            border: 1px solid rgba(45, 212, 191, 0.2);
        }

        .auth-tab-btn {
            flex: 1; padding: 0.65rem;
            border-radius: 0.65rem; border: none;
            background: transparent;
            font-weight: 700; font-size: 0.92rem;
            cursor: pointer;
            transition: all 0.22s ease;
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
        }
        [data-bs-theme="light"] .auth-tab-btn { color: #475569; }
        [data-bs-theme="dark"] .auth-tab-btn { color: #94a3b8; }
        [data-bs-theme="light"] .auth-tab-btn.active {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35);
        }
        [data-bs-theme="dark"] .auth-tab-btn.active {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 0 18px rgba(20, 184, 166, 0.5) !important;
        }

        /* Panel Headings */
        .auth-panel { display: none; }
        .auth-panel.active { display: block; animation: fadeInAuth 0.25s ease-out; }
        @keyframes fadeInAuth {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-card-title {
            font-size: 1.35rem; font-weight: 800; margin-bottom: 0.35rem;
        }
        [data-bs-theme="light"] .auth-card-title { color: #0f172a !important; }
        [data-bs-theme="dark"] .auth-card-title { color: #ffffff !important; text-shadow: 0 2px 10px rgba(0,0,0,0.5); }

        .auth-card-subtitle {
            font-size: 0.86rem; margin-bottom: 0; line-height: 1.45;
        }
        [data-bs-theme="light"] .auth-card-subtitle { color: #475569 !important; }
        [data-bs-theme="dark"] .auth-card-subtitle { color: #cbd5e1 !important; }

        /* Form Fields */
        .auth-field { margin-bottom: 1.1rem; position: relative; }
        .auth-field label {
            display: block; font-size: 0.82rem; font-weight: 700; margin-bottom: 0.4rem; letter-spacing: 0.2px;
        }
        [data-bs-theme="light"] .auth-field label { color: #334155 !important; }
        [data-bs-theme="dark"] .auth-field label { color: #cbd5e1 !important; }

        .auth-field .field-wrap { position: relative; }
        .auth-field .field-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            font-size: 0.95rem; pointer-events: none; z-index: 1;
            transition: color 0.18s ease;
        }
        [data-bs-theme="light"] .auth-field .field-icon { color: #0d9488 !important; }
        [data-bs-theme="dark"] .auth-field .field-icon { color: #2dd4bf !important; }

        .auth-field input, .auth-field select {
            width: 100% !important; height: 48px !important;
            padding: 0 1rem 0 2.65rem !important;
            border-radius: 0.75rem !important;
            font-size: 0.92rem !important;
            font-family: inherit !important;
            outline: none !important;
            transition: all 0.2s ease !important;
        }
        /* Light mode inputs */
        [data-bs-theme="light"] .auth-field input,
        [data-bs-theme="light"] .auth-field select {
            background: rgba(255, 255, 255, 0.82) !important;
            border: 1.5px solid rgba(13, 148, 136, 0.25) !important;
            color: #0f172a !important;
        }
        [data-bs-theme="light"] .auth-field input:focus,
        [data-bs-theme="light"] .auth-field select:focus {
            background: #ffffff !important;
            border-color: #0d9488 !important;
            box-shadow: 0 0 0 3.5px rgba(13, 148, 136, 0.18) !important;
        }
        /* Dark mode inputs */
        [data-bs-theme="dark"] .auth-field input,
        [data-bs-theme="dark"] .auth-field select {
            background: rgba(3, 16, 20, 0.78) !important;
            border: 1.5px solid rgba(45, 212, 191, 0.3) !important;
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .auth-field input:focus,
        [data-bs-theme="dark"] .auth-field select:focus {
            background: rgba(6, 26, 32, 0.95) !important;
            border-color: #2dd4bf !important;
            box-shadow: 0 0 0 3.5px rgba(45, 212, 191, 0.25) !important;
        }

        .auth-field .btn-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 6px; font-size: 0.95rem;
            transition: color 0.15s ease;
        }
        [data-bs-theme="light"] .auth-field .btn-eye { color: #64748b !important; }
        [data-bs-theme="light"] .auth-field .btn-eye:hover { color: #0d9488 !important; }
        [data-bs-theme="dark"] .auth-field .btn-eye { color: #94a3b8 !important; }
        [data-bs-theme="dark"] .auth-field .btn-eye:hover { color: #2dd4bf !important; }

        .auth-forgot-link {
            font-size: 0.78rem; text-decoration: none; font-weight: 700;
        }
        [data-bs-theme="light"] .auth-forgot-link { color: #0d9488 !important; }
        [data-bs-theme="dark"] .auth-forgot-link { color: #2dd4bf !important; }

        .auth-check-label {
            cursor: pointer; user-select: none; font-size: 0.84rem;
        }
        [data-bs-theme="light"] .auth-check-label { color: #475569 !important; }
        [data-bs-theme="dark"] .auth-check-label { color: #cbd5e1 !important; }

        /* Submit Button */
        .btn-auth-submit {
            width: 100% !important; height: 50px !important;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
            color: #ffffff !important; border: none !important;
            border-radius: 0.75rem !important;
            font-size: 0.96rem !important; font-weight: 800 !important;
            letter-spacing: 0.4px !important;
            cursor: pointer !important;
            display: flex !align-items: center; justify-content: center;
            box-shadow: 0 6px 18px rgba(13, 148, 136, 0.35);
            margin-top: 1.25rem;
            transition: all 0.22s ease !important;
        }
        .btn-auth-submit:hover {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(13, 148, 136, 0.45);
            color: #ffffff !important;
        }

        /* Divider */
        .auth-divider {
            display: flex; align-items: center; gap: 0.75rem;
            font-size: 0.78rem; font-weight: 600;
            text-transform: uppercase; letter-spacing: 0.5px;
            margin: 1.35rem 0 1.15rem;
        }
        .auth-divider::before, .auth-divider::after {
            content: ''; flex: 1; height: 1px;
        }
        [data-bs-theme="light"] .auth-divider { color: #64748b !important; }
        [data-bs-theme="light"] .auth-divider::before, [data-bs-theme="light"] .auth-divider::after { background: rgba(0, 0, 0, 0.12) !important; }
        [data-bs-theme="dark"] .auth-divider { color: #94a3b8 !important; }
        [data-bs-theme="dark"] .auth-divider::before, [data-bs-theme="dark"] .auth-divider::after { background: rgba(255, 255, 255, 0.16) !important; }

        /* Google Login Button */
        .btn-social-google {
            width: 100% !important; height: 48px !important;
            border-radius: 0.75rem !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
            gap: 0.75rem !important;
            font-size: 0.92rem !important; font-weight: 700 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            margin-top: 0.5rem !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04) !important;
        }
        [data-bs-theme="light"] .btn-social-google {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 1.5px solid rgba(0, 0, 0, 0.12) !important;
            color: #0f172a !important;
        }
        [data-bs-theme="light"] .btn-social-google:hover {
            border-color: #0d9488 !important;
            background: #ffffff !important;
            color: #0d9488 !important;
            transform: translateY(-1.5px);
            box-shadow: 0 6px 16px rgba(13, 148, 136, 0.16) !important;
        }
        [data-bs-theme="dark"] .btn-social-google {
            background: rgba(10, 30, 36, 0.85) !important;
            border: 1.5px solid rgba(45, 212, 191, 0.32) !important;
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .btn-social-google:hover {
            border-color: #2dd4bf !important;
            background: rgba(13, 148, 136, 0.25) !important;
            color: #2dd4bf !important;
            transform: translateY(-1.5px);
            box-shadow: 0 0 18px rgba(45, 212, 191, 0.25) !important;
        }

        /* Switch Tab Link */
        .auth-switch-link {
            text-align: center; font-size: 0.86rem; margin-top: 1.25rem; margin-bottom: 0;
        }
        [data-bs-theme="light"] .auth-switch-link { color: #475569 !important; }
        [data-bs-theme="dark"] .auth-switch-link { color: #94a3b8 !important; }

        .auth-switch-btn {
            border: none; background: transparent; font-weight: 800; cursor: pointer; padding: 0;
            text-decoration: none;
        }
        [data-bs-theme="light"] .auth-switch-btn { color: #0d9488 !important; }
        [data-bs-theme="dark"] .auth-switch-btn { color: #2dd4bf !important; }
        .auth-switch-btn:hover { text-decoration: underline; }

        /* Demo Accounts Section */
        .auth-demo-wrap {
            margin-top: 1.35rem; padding-top: 1.15rem;
            border-top: 1px dashed;
        }
        [data-bs-theme="light"] .auth-demo-wrap { border-color: rgba(0, 0, 0, 0.12) !important; }
        [data-bs-theme="dark"] .auth-demo-wrap { border-color: rgba(45, 212, 191, 0.22) !important; }

        .auth-demo-title {
            font-size: 0.76rem; font-weight: 700; text-align: center; text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 0.65rem;
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
        }
        [data-bs-theme="light"] .auth-demo-title { color: #475569 !important; }
        [data-bs-theme="dark"] .auth-demo-title { color: #cbd5e1 !important; }

        .auth-demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; }
        .demo-pill {
            display: flex; align-items: center; gap: 0.55rem;
            border-radius: 0.75rem; padding: 0.45rem 0.65rem;
            cursor: pointer; width: 100%; transition: all 0.18s ease; text-align: left;
        }
        [data-bs-theme="light"] .demo-pill {
            background: rgba(255, 255, 255, 0.8) !important;
            border: 1px solid rgba(0, 0, 0, 0.1) !important;
            color: #0f172a !important;
        }
        [data-bs-theme="light"] .demo-pill:hover {
            border-color: #0d9488 !important;
            background: #ffffff !important;
            transform: translateY(-1px);
        }
        [data-bs-theme="dark"] .demo-pill {
            background: rgba(5, 20, 24, 0.78) !important;
            border: 1px solid rgba(45, 212, 191, 0.26) !important;
            color: #f8fafc !important;
        }
        [data-bs-theme="dark"] .demo-pill:hover {
            border-color: #2dd4bf !important;
            background: rgba(13, 148, 136, 0.22) !important;
            transform: translateY(-1px);
        }

        .demo-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.76rem; font-weight: 800; color: #fff; flex-shrink: 0;
        }
        .demo-meta-sub {
            font-size: 0.68rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        }
        [data-bs-theme="light"] .demo-meta-sub { color: #64748b !important; }
        [data-bs-theme="dark"] .demo-meta-sub { color: #94a3b8 !important; }

        .auth-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

        /* Responsive */
        @media (max-width: 576px) {
            .auth-viewport { padding: 4.5rem 1rem 2rem !important; }
            .auth-card { padding: 1.75rem 1.25rem 1.6rem !important; border-radius: 1.25rem !important; }
            .auth-grid-2 { grid-template-columns: 1fr !important; gap: 0 !important; }
            .auth-demo-grid { grid-template-columns: 1fr !important; }
        }
    </style>
</head>
<body>

<!-- ========== TOP BAR (Back link & Theme toggle) ========== -->
<div class="auth-top-bar">
    <a href="index.php" class="auth-back-link" title="Quay lại trang chủ">
        <i class="fa-solid fa-chevron-left"></i>
        <span>Trang chủ</span>
    </a>
    <button class="auth-theme-btn" id="themeToggle" title="Chuyển chế độ Sáng / Tối" aria-label="Chuyển chế độ Sáng / Tối">
        <i class="fa-solid fa-moon"></i>
    </button>
</div>

<!-- ========== CENTER VIEWPORT ========== -->
<div class="auth-viewport">

    <div class="auth-card">

        <!-- Logo thương hiệu -->
        <div class="auth-brand-wrap">
            <a href="index.php" title="Về trang chủ GODMINTON">
                <img src="assets/img/logo-teal.svg" alt="GODMINTON" class="auth-brand-logo brand-logo-img">
            </a>
        </div>

        <!-- Tab switcher (Đăng nhập / Đăng ký) -->
        <div class="auth-tabs" role="tablist">
            <button class="auth-tab-btn active" id="tabLoginBtn" type="button" onclick="switchTab('login')">
                <i class="fa-solid fa-right-to-bracket me-1"></i>Đăng Nhập
            </button>
            <button class="auth-tab-btn" id="tabRegisterBtn" type="button" onclick="switchTab('register')">
                <i class="fa-solid fa-user-plus me-1"></i>Đăng Ký
            </button>
        </div>

        <!-- ==================== PANEL LOGIN ==================== -->
        <div class="auth-panel active" id="panelLogin">
            <div style="text-align:center; margin-bottom:1.4rem;">
                <h4 class="auth-card-title">Chào mừng trở lại! 👋</h4>
                <p class="auth-card-subtitle">Đăng nhập để đặt sân và quản lý lịch đấu của bạn</p>
            </div>

            <form id="loginForm">
                <div class="auth-field">
                    <label for="loginEmail">Địa chỉ Email</label>
                    <div class="field-wrap">
                        <i class="fa-regular fa-envelope field-icon"></i>
                        <input type="email" id="loginEmail" placeholder="name@example.com" value="khach1@gmail.com" required autocomplete="email">
                    </div>
                </div>

                <div class="auth-field">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="mb-0" for="loginPassword">Mật khẩu</label>
                        <a href="javascript:void(0)" onclick="handleForgotPassword()" class="auth-forgot-link">Quên mật khẩu?</a>
                    </div>
                    <div class="field-wrap">
                        <i class="fa-solid fa-lock field-icon"></i>
                        <input type="password" id="loginPassword" placeholder="Nhập mật khẩu..." value="Khach@123" required autocomplete="current-password">
                        <button type="button" class="btn-eye" onclick="toggleEye('loginPassword', this)" aria-label="Ẩn / hiện mật khẩu">
                            <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-2">
                    <input type="checkbox" class="form-check-input me-2 mt-0" id="rememberMe" checked>
                    <label class="auth-check-label" for="rememberMe">Ghi nhớ đăng nhập trên thiết bị này</label>
                </div>

                <button type="submit" class="btn-auth-submit" id="btnLoginSubmit">
                    <i class="fa-solid fa-right-to-bracket me-2"></i>ĐĂNG NHẬP
                </button>
            </form>

            <!-- Phân cách -->
            <div class="auth-divider">hoặc tiếp tục với</div>

            <!-- Nút đăng nhập Google (Không có Facebook) -->
            <button type="button" class="btn-social-google" id="btnGoogleLogin" onclick="handleGoogleLogin()">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" height="18" alt="Google">
                <span>Đăng nhập với Google</span>
            </button>

            <!-- Chuyển sang Đăng ký -->
            <p class="auth-switch-link">
                Chưa có tài khoản?
                <button type="button" class="auth-switch-btn" onclick="switchTab('register')">Đăng ký ngay</button>
            </p>

            <!-- Tài khoản demo nhanh -->
            <div class="auth-demo-wrap">
                <div class="auth-demo-title">
                    <i class="fa-solid fa-key" style="color:var(--brand-teal,#0d9488);"></i> Tài khoản demo nhanh
                </div>
                <div class="auth-demo-grid">
                    <button class="demo-pill" type="button" onclick="fillLogin('khach1@gmail.com','Khach@123')" title="Click để điền tài khoản Khách hàng">
                        <div class="demo-avatar" style="background:linear-gradient(135deg,#0d9488,#0f766e);">K</div>
                        <div style="overflow:hidden; text-align:left;">
                            <div style="font-size:0.78rem; font-weight:700;">Khách hàng</div>
                            <div class="demo-meta-sub">khach1@gmail.com</div>
                        </div>
                    </button>
                    <button class="demo-pill" type="button" onclick="fillLogin('admin@datsancaulong.vn','Admin@123')" title="Click để điền tài khoản Quản trị viên">
                        <div class="demo-avatar" style="background:linear-gradient(135deg,#f59e0b,#d97706);">A</div>
                        <div style="overflow:hidden; text-align:left;">
                            <div style="font-size:0.78rem; font-weight:700;">Quản trị viên</div>
                            <div class="demo-meta-sub">admin@...</div>
                        </div>
                    </button>
                </div>
            </div>
        </div><!-- /#panelLogin -->

        <!-- ==================== PANEL REGISTER ==================== -->
        <div class="auth-panel" id="panelRegister">
            <div style="text-align:center; margin-bottom:1.4rem;">
                <h4 class="auth-card-title">Tạo tài khoản mới 🎉</h4>
                <p class="auth-card-subtitle">Đăng ký nhanh chóng để bắt đầu đặt sân cầu lông</p>
            </div>

            <form id="registerForm">
                <div class="auth-grid-2">
                    <div class="auth-field">
                        <label for="regName">Họ và Tên *</label>
                        <div class="field-wrap">
                            <i class="fa-solid fa-user field-icon"></i>
                            <input type="text" id="regName" placeholder="Nguyễn Văn A" required autocomplete="name">
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="regPhone">Số Điện Thoại *</label>
                        <div class="field-wrap">
                            <i class="fa-solid fa-phone field-icon"></i>
                            <input type="tel" id="regPhone" placeholder="0912345678" required autocomplete="tel">
                        </div>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="regEmail">Email *</label>
                    <div class="field-wrap">
                        <i class="fa-regular fa-envelope field-icon"></i>
                        <input type="email" id="regEmail" placeholder="name@gmail.com" required autocomplete="email">
                    </div>
                </div>

                <div class="auth-grid-2">
                    <div class="auth-field">
                        <label for="regPassword">Mật Khẩu *</label>
                        <div class="field-wrap">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <input type="password" id="regPassword" placeholder="Tối thiểu 6 ký tự" minlength="6" required autocomplete="new-password">
                            <button type="button" class="btn-eye" onclick="toggleEye('regPassword', this)" aria-label="Ẩn / hiện mật khẩu">
                                <i class="fa-regular fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label for="regConfirmPassword">Xác Nhận MK *</label>
                        <div class="field-wrap">
                            <i class="fa-solid fa-lock field-icon"></i>
                            <input type="password" id="regConfirmPassword" placeholder="Nhập lại mật khẩu" required autocomplete="new-password">
                        </div>
                    </div>
                </div>

                <div class="auth-field">
                    <label for="regRole">Vai trò tài khoản</label>
                    <div class="field-wrap">
                        <i class="fa-solid fa-id-badge field-icon"></i>
                        <select id="regRole">
                            <option value="customer">Khách hàng — Đặt sân thể thao</option>
                            <option value="owner">Chủ sân — Đăng ký quản lý sân</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-2 mb-2">
                    <input type="checkbox" class="form-check-input mt-1 flex-shrink-0" id="agreeTerms" required>
                    <label class="auth-check-label" for="agreeTerms" style="font-size:0.8rem; line-height:1.4;">
                        Tôi đồng ý với <a href="#" class="auth-forgot-link">Điều khoản dịch vụ</a> và <a href="#" class="auth-forgot-link">Chính sách bảo mật</a>
                    </label>
                </div>

                <button type="submit" class="btn-auth-submit" id="btnRegisterSubmit">
                    <i class="fa-solid fa-user-plus me-2"></i>TẠO TÀI KHOẢN MIỄN PHÍ
                </button>
            </form>

            <!-- Phân cách -->
            <div class="auth-divider">hoặc tiếp tục với</div>

            <!-- Nút đăng ký Google (Không có Facebook) -->
            <button type="button" class="btn-social-google" id="btnGoogleRegister" onclick="handleGoogleLogin()">
                <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="18" height="18" alt="Google">
                <span>Tiếp tục với Google</span>
            </button>

            <!-- Chuyển sang Đăng nhập -->
            <p class="auth-switch-link">
                Đã có tài khoản?
                <button type="button" class="auth-switch-btn" onclick="switchTab('login')">Đăng nhập ngay</button>
            </p>
        </div><!-- /#panelRegister -->

    </div><!-- /.auth-card -->

</div><!-- /.auth-viewport -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/mock-data.js?v=<?= $v ?>"></script>
<script src="assets/js/app.js?v=<?= $v ?>"></script>
<script src="assets/js/auth.js?v=<?= $v ?>"></script>
</body>
</html>
