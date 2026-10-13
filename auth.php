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

        /* ---- Top Control Row inside Card ---- */
        .auth-card-topbar {
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            margin-bottom: 1.25rem !important;
            padding-bottom: 0.85rem !important;
            border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
        }
        [data-bs-theme="light"] .auth-card-topbar {
            border-bottom: 1px solid rgba(13, 148, 136, 0.15) !important;
        }
        [data-bs-theme="dark"] .auth-card-topbar {
            border-bottom: 1px solid rgba(45, 212, 191, 0.18) !important;
        }

        .auth-card-back-btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.5rem !important;
            padding: 0.45rem 1.05rem !important;
            border-radius: 9999px !important;
            font-size: 0.86rem !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: all 0.2s ease !important;
        }
        [data-bs-theme="light"] .auth-card-back-btn {
            background: rgba(255, 255, 255, 0.8) !important;
            border: 1.5px solid rgba(13, 148, 136, 0.28) !important;
            color: #0f766e !important;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.1) !important;
        }
        [data-bs-theme="light"] .auth-card-back-btn:hover {
            background: #0d9488 !important;
            border-color: #0d9488 !important;
            color: #ffffff !important;
            transform: translateX(-3px) !important;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.3) !important;
        }
        [data-bs-theme="dark"] .auth-card-back-btn {
            background: rgba(45, 212, 191, 0.12) !important;
            border: 1px solid rgba(45, 212, 191, 0.32) !important;
            color: #2dd4bf !important;
        }
        [data-bs-theme="dark"] .auth-card-back-btn:hover {
            background: #0d9488 !important;
            border-color: #2dd4bf !important;
            color: #ffffff !important;
            transform: translateX(-3px) !important;
            box-shadow: 0 0 16px rgba(45, 212, 191, 0.4) !important;
        }

        /* ---- Fixed Floating Theme Toggle at Top-Right Corner ---- */
        .auth-theme-corner-btn {
            position: fixed !important;
            top: 1.25rem !important;
            right: 1.35rem !important;
            width: 44px !important;
            height: 44px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            cursor: pointer !important;
            z-index: 1050 !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) !important;
            font-size: 1.05rem !important;
            outline: none !important;
        }
        [data-bs-theme="light"] .auth-theme-corner-btn {
            background: rgba(255, 255, 255, 0.85) !important;
            border: 1.5px solid rgba(13, 148, 136, 0.35) !important;
            color: #0d9488 !important;
            box-shadow: 0 4px 18px rgba(13, 148, 136, 0.22), 0 2px 6px rgba(0, 0, 0, 0.08) !important;
        }
        [data-bs-theme="light"] .auth-theme-corner-btn:hover {
            background: #0d9488 !important;
            border-color: #0d9488 !important;
            color: #ffffff !important;
            transform: scale(1.1) rotate(15deg) !important;
            box-shadow: 0 6px 22px rgba(13, 148, 136, 0.4) !important;
        }
        [data-bs-theme="dark"] .auth-theme-corner-btn {
            background: rgba(6, 26, 32, 0.82) !important;
            border: 1.5px solid rgba(45, 212, 191, 0.45) !important;
            color: #fbbf24 !important;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6), 0 0 16px rgba(251, 191, 36, 0.25) !important;
        }
        [data-bs-theme="dark"] .auth-theme-corner-btn:hover {
            background: rgba(13, 148, 136, 0.35) !important;
            border-color: #2dd4bf !important;
            transform: scale(1.1) rotate(15deg) !important;
            box-shadow: 0 0 24px rgba(251, 191, 36, 0.5) !important;
        }

        /* ---- Left-Aligned Viewport Container ---- */
        .auth-viewport {
            min-height: 100vh !important;
            display: flex !important;
            align-items: center !important;
            justify-content: flex-start !important;
            padding: 2.5rem 1.5rem 2.5rem clamp(2rem, 4.5vw, 5rem) !important;
            position: relative;
        }

        /* ---- GLASSMORPHISM AUTH CARD (50% Màn hình & Frosted Blur) ---- */
        .auth-card {
            width: 48vw !important;
            max-width: 660px !important;
            min-width: 440px !important;
            border-radius: 1.85rem !important;
            padding: 2.25rem 2.5rem !important;
            margin: 0 !important;
            position: relative !important;
            backdrop-filter: blur(30px) saturate(190%) !important;
            -webkit-backdrop-filter: blur(30px) saturate(190%) !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
        }

        /* Light Mode Card (Trong suốt 20% Gradient Xanh ngọc - Trắng xanh ngọc + Frosted Glass Blur) */
        [data-bs-theme="light"] .auth-card {
            background: linear-gradient(145deg, rgba(211, 244, 238, 0.24) 0%, rgba(227, 248, 244, 0.18) 45%, rgba(255, 255, 255, 0.25) 100%) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.4) !important;
            box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.5), 0 0 35px rgba(20, 184, 166, 0.22), inset 0 1px 2px rgba(255, 255, 255, 0.5) !important;
            color: #ffffff !important;
        }

        /* Dark Mode Card (Trong suốt 20% Deep Emerald Neon + Frosted Glass Blur) */
        [data-bs-theme="dark"] .auth-card {
            background: rgba(5, 22, 26, 0.24) !important;
            border: 1.5px solid rgba(45, 212, 191, 0.35) !important;
            box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.85), 0 0 40px rgba(13, 148, 136, 0.3), inset 0 1px 2px rgba(45, 212, 191, 0.25) !important;
            color: #ffffff !important;
        }

        /* Logo Brand */
        .auth-brand-wrap { text-align: center; margin-bottom: 1.4rem; }
        .auth-brand-logo { height: 44px; width: auto; filter: drop-shadow(0 3px 10px rgba(13, 148, 136, 0.4)); }

        /* Tabs Switcher */
        .auth-tabs {
            display: flex;
            border-radius: 0.85rem;
            padding: 4px;
            margin-bottom: 1.6rem;
            gap: 4px;
            background: rgba(0, 0, 0, 0.28) !important;
            border: 1.5px solid rgba(45, 212, 191, 0.3) !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
            transition: background 0.25s ease, border-color 0.25s ease;
        }

        .auth-tab-btn {
            flex: 1; padding: 0.65rem;
            border-radius: 0.65rem; border: none;
            background: transparent;
            font-weight: 800 !important; font-size: 0.94rem !important;
            color: rgba(255, 255, 255, 0.85) !important;
            cursor: pointer;
            transition: all 0.22s ease;
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
        }
        .auth-tab-btn.active {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 0 18px rgba(20, 184, 166, 0.5) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        /* Panel Headings */
        .auth-panel { display: none; }
        .auth-panel.active { display: block; animation: fadeInAuth 0.25s ease-out; }
        @keyframes fadeInAuth {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-card-title {
            font-size: 1.45rem !important; font-weight: 800 !important; margin-bottom: 0.35rem;
            color: #ffffff !important; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.75) !important;
        }

        .auth-card-subtitle {
            font-size: 0.9rem !important; font-weight: 700 !important; margin-bottom: 0; line-height: 1.45;
            color: #ffffff !important; opacity: 0.95 !important; text-shadow: 0 1px 4px rgba(0, 0, 0, 0.75) !important;
        }

        /* Form Fields - 20% Translucent Frosted & Bold Unified Typography */
        .auth-field { margin-bottom: 1.15rem; position: relative; }
        .auth-field label {
            display: block; font-size: 0.86rem !important; font-weight: 800 !important; margin-bottom: 0.4rem; letter-spacing: 0.25px;
            color: #ffffff !important; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.75) !important;
        }

        .auth-field .field-wrap { position: relative; }
        .auth-field .field-icon {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            font-size: 1rem; pointer-events: none; z-index: 1;
            color: #2dd4bf !important;
            transition: color 0.18s ease;
        }

        .auth-field input, .auth-field select {
            width: 100% !important; height: 50px !important;
            padding: 0 1rem 0 2.65rem !important;
            border-radius: 0.75rem !important;
            font-size: 0.95rem !important;
            font-weight: 700 !important;
            font-family: inherit !important;
            outline: none !important;
            background: rgba(255, 255, 255, 0.18) !important;
            border: 1.5px solid rgba(45, 212, 191, 0.38) !important;
            color: #ffffff !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            transition: all 0.2s ease !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6);
        }
        .auth-field input::placeholder {
            color: rgba(255, 255, 255, 0.7) !important;
            font-weight: 600 !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }
        .auth-field select option {
            background: #062026 !important;
            color: #ffffff !important;
            font-weight: 700 !important;
        }
        .auth-field input:focus, .auth-field select:focus {
            background: rgba(255, 255, 255, 0.26) !important;
            border-color: #2dd4bf !important;
            box-shadow: 0 0 0 3.5px rgba(45, 212, 191, 0.32) !important;
            color: #ffffff !important;
        }

        .auth-field .btn-eye {
            position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; padding: 6px; font-size: 0.95rem;
            color: #ffffff !important; opacity: 0.85;
            transition: all 0.15s ease;
        }
        .auth-field .btn-eye:hover { color: #2dd4bf !important; opacity: 1; }

        .auth-forgot-link {
            font-size: 0.82rem; text-decoration: none; font-weight: 800 !important;
            color: #2dd4bf !important; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7) !important;
            transition: color 0.18s ease;
        }
        .auth-forgot-link:hover { color: #5eead4 !important; text-decoration: underline !important; }

        .auth-check-label {
            cursor: pointer; user-select: none; font-size: 0.85rem; font-weight: 700 !important;
            color: #ffffff !important; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7) !important;
        }

        /* Submit Button */
        .btn-auth-submit {
            width: 100% !important; height: 50px !important;
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%) !important;
            color: #ffffff !important; border: none !important;
            border-radius: 0.75rem !important;
            font-size: 0.98rem !important; font-weight: 800 !important;
            letter-spacing: 0.5px !important;
            cursor: pointer !important;
            display: flex !align-items: center; justify-content: center;
            box-shadow: 0 6px 20px rgba(13, 148, 136, 0.45);
            margin-top: 1.25rem;
            transition: all 0.22s ease !important;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
        }
        .btn-auth-submit:hover {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%) !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 26px rgba(13, 148, 136, 0.55);
            color: #ffffff !important;
        }

        /* Divider */
        .auth-divider {
            display: flex; align-items: center; gap: 0.75rem;
            font-size: 0.8rem; font-weight: 800 !important;
            text-transform: uppercase; letter-spacing: 0.5px;
            margin: 1.35rem 0 1.15rem;
            color: #ffffff !important; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7) !important;
        }
        .auth-divider::before, .auth-divider::after {
            content: ''; flex: 1; height: 1px;
            background: rgba(255, 255, 255, 0.3) !important;
        }

        /* Google Login Button - 20% Translucent Frosted Glass */
        .btn-social-google {
            width: 100% !important; height: 48px !important;
            border-radius: 0.75rem !important;
            display: flex !important; align-items: center !important; justify-content: center !important;
            gap: 0.75rem !important;
            font-size: 0.94rem !important; font-weight: 800 !important;
            cursor: pointer !important;
            transition: all 0.2s ease !important;
            margin-top: 0.5rem !important;
            background: rgba(255, 255, 255, 0.2) !important;
            border: 1.5px solid rgba(255, 255, 255, 0.38) !important;
            color: #ffffff !important;
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6) !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2) !important;
        }
        .btn-social-google:hover {
            border-color: #2dd4bf !important;
            background: rgba(255, 255, 255, 0.3) !important;
            color: #2dd4bf !important;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.35) !important;
        }

        /* Switch Tab Link */
        .auth-switch-link {
            text-align: center; font-size: 0.88rem; margin-top: 1.25rem; margin-bottom: 0;
            color: #ffffff !important; font-weight: 700 !important;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7) !important;
        }

        .auth-switch-btn {
            border: none; background: transparent; font-weight: 800 !important; cursor: pointer; padding: 0;
            color: #2dd4bf !important; text-decoration: underline !important;
        }
        .auth-switch-btn:hover { color: #5eead4 !important; }

        /* Demo Accounts Section */
        .auth-demo-wrap {
            margin-top: 1.35rem; padding-top: 1.15rem;
            border-top: 1px dashed rgba(45, 212, 191, 0.32) !important;
        }

        .auth-demo-title {
            font-size: 0.78rem; font-weight: 800 !important; text-align: center; text-transform: uppercase;
            letter-spacing: 0.6px; margin-bottom: 0.65rem;
            display: flex; align-items: center; justify-content: center; gap: 0.4rem;
            color: #2dd4bf !important; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7) !important;
        }

        .auth-demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.65rem; }
        .demo-pill {
            display: flex; align-items: center; gap: 0.55rem;
            border-radius: 0.75rem; padding: 0.45rem 0.65rem;
            cursor: pointer; width: 100%; transition: all 0.18s ease; text-align: left;
            background: rgba(255, 255, 255, 0.16) !important;
            border: 1.5px solid rgba(45, 212, 191, 0.32) !important;
            color: #ffffff !important;
            backdrop-filter: blur(10px) !important;
            -webkit-backdrop-filter: blur(10px) !important;
        }
        .demo-pill:hover {
            border-color: #2dd4bf !important;
            background: rgba(13, 148, 136, 0.35) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 14px rgba(20, 184, 166, 0.3) !important;
        }

        .demo-avatar {
            width: 28px; height: 28px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 0.76rem; font-weight: 800; color: #fff; flex-shrink: 0;
        }
        .demo-meta-role {
            font-size: 0.78rem !important; font-weight: 800 !important; line-height: 1.2 !important;
            color: #ffffff !important; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6) !important;
        }
        .demo-meta-sub {
            font-size: 0.7rem !important; font-weight: 700 !important;
            white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
            color: #e2e8f0 !important; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.6) !important;
        }

        .auth-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }

        /* Responsive */
        @media (max-width: 991.98px) {
            body {
                background-attachment: scroll !important;
                background-position: 72% center !important;
                background-size: cover !important;
            }
            .auth-viewport {
                justify-content: center !important;
                padding: 4.25rem 1.25rem 2.5rem !important;
            }
            .auth-card {
                width: 100% !important;
                max-width: 540px !important;
                min-width: 0 !important;
                margin: 0 auto !important;
            }
        }

        @media (max-width: 576px) {
            body {
                background-attachment: scroll !important;
                background-position: 68% 18% !important;
                background-size: cover !important;
                background-image:
                    linear-gradient(180deg, rgba(4, 20, 17, 0.48) 0%, rgba(2, 12, 10, 0.72) 100%),
                    url('assets/img/auth-bg.jpg?v=<?= $v ?>') !important;
            }
            .auth-viewport { padding: 3.85rem 0.85rem 2rem !important; }
            .auth-card { padding: 1.5rem 1.15rem 1.35rem !important; border-radius: 1.25rem !important; width: 100% !important; min-width: 0 !important; }
            .auth-grid-2 { grid-template-columns: 1fr !important; gap: 0 !important; }
            .auth-demo-grid { grid-template-columns: 1fr !important; }
            .auth-theme-corner-btn {
                top: 0.85rem !important;
                right: 0.85rem !important;
                width: 38px !important;
                height: 38px !important;
                font-size: 0.95rem !important;
            }
        }
    </style>
</head>
<body>

<!-- Nút chuyển chế độ Sáng / Tối ở góc phải màn hình -->
<button type="button" class="auth-theme-corner-btn" id="themeToggle" onclick="toggleTheme(event)" title="Chuyển chế độ Sáng / Tối" aria-label="Chuyển chế độ Sáng / Tối">
    <i class="fa-solid fa-moon"></i>
</button>

<!-- ========== CENTER VIEWPORT ========== -->
<div class="auth-viewport">

    <div class="auth-card">

        <!-- Header điều khiển tích hợp trong Card: Nút về trang chủ -->
        <div class="auth-card-topbar">
            <a href="index.php" class="auth-card-back-btn" title="Quay lại trang chủ">
                <i class="fa-solid fa-arrow-left me-1"></i>
                <span>Trang chủ</span>
            </a>
        </div>

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
