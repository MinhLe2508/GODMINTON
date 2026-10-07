/**
 * GODMINTON - App JS (Theme, PWA, Navbar Render)
 * Phiên bản tĩnh - không cần PHP
 */

// ========================
// THEME TOGGLE (Dark Mode)
// ========================
function initTheme() {
    const saved = localStorage.getItem('bm_theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', saved);
    updateThemeIcon(saved);
}

function updateThemeIcon(theme) {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    btn.innerHTML = theme === 'dark'
        ? '<i class="fa-solid fa-sun"></i>'
        : '<i class="fa-solid fa-moon"></i>';
}

function toggleTheme() {
    const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('bm_theme', next);
    updateThemeIcon(next);
}

// ========================
// AOS INIT
// ========================
function initAOS() {
    if (typeof AOS !== 'undefined') {
        AOS.init({ once: true, duration: 700, offset: 60 });
    }
}

// ========================
// NAVBAR USER STATE
// ========================
function renderNavbarUser() {
    const user = MockDB.getUser();
    const userArea = document.getElementById('navbarUserArea');
    if (!userArea) return;

    const initial = user && user.name ? user.name.charAt(0).toUpperCase() : 'A';
    const displayName = user ? user.name : 'Tài Khoản';
    const displayRole = user && user.role ? (user.role === 'admin' ? 'QUẢN TRỊ' : (user.role === 'owner' ? 'CHỦ SÂN' : 'KHÁCH HÀNG')) : 'KHÁCH HÀNG';

    userArea.innerHTML = `
    <!-- Nút chọn Kênh Admin trực tiếp trên header -->
    <a href="admin-dashboard.php" class="btn btn-sm btn-outline-warning rounded-pill px-3 fw-bold d-flex align-items-center gap-1" title="Kênh Quản trị / Chủ sân">
        <i class="fa-solid fa-gauge-high"></i> <span class="d-none d-sm-inline">Kênh Admin</span>
    </a>

    <!-- Dropdown Chọn Trang & Tài Khoản -->
    <div class="dropdown">
        <button class="btn dropdown-toggle d-flex align-items-center gap-2 border-0 py-1 px-2 rounded-pill shadow-sm" type="button" data-bs-toggle="dropdown"
            style="background: rgba(255,255,255,0.15); color:#fff;">
            <div class="avatar-sm rounded-circle bg-teal text-white d-flex align-items-center justify-content-center fw-bold" style="width:32px;height:32px;font-size:0.85rem;">
                ${initial}
            </div>
            <span class="d-none d-md-inline fw-semibold small pe-1">${displayName}</span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2 py-2" style="min-width: 240px;">
            <li class="px-3 py-2 border-bottom mb-1">
                <div class="fw-bold text-truncate">${displayName}</div>
                <div class="small text-muted text-truncate">${user ? user.email : 'khach1@gmail.com'}</div>
                <span class="badge bg-teal-subtle text-teal mt-1 fw-bold">${displayRole}</span>
            </li>
            <li><h6 class="dropdown-header text-uppercase fs-xs fw-bold text-muted py-1" style="font-size:0.72rem;">CHỌN TRANG NHANH</h6></li>
            <li><a class="dropdown-item py-2 fw-semibold" href="profile.php"><i class="fa-solid fa-circle-user me-2 text-teal"></i>Trang cá nhân</a></li>
            <li><a class="dropdown-item py-2 fw-semibold" href="admin-dashboard.php"><i class="fa-solid fa-gauge-high me-2 text-warning"></i>Kênh Quản trị (Admin)</a></li>
            <li><a class="dropdown-item py-2 fw-semibold" href="history.php"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Lịch sử đặt sân</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><h6 class="dropdown-header text-uppercase fs-xs fw-bold text-muted py-1" style="font-size:0.72rem;">XÁC THỰC TÀI KHOẢN</h6></li>
            <li><a class="dropdown-item py-2" href="auth.php"><i class="fa-solid fa-right-to-bracket me-2 text-success"></i>Trang Đăng nhập</a></li>
            <li><a class="dropdown-item py-2" href="auth.php#register"><i class="fa-solid fa-user-plus me-2 text-info"></i>Trang Đăng ký</a></li>
            <li><hr class="dropdown-divider my-1"></li>
            <li><a class="dropdown-item py-2 text-danger" href="#" id="btnLogout"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
        </ul>
    </div>`;

    document.getElementById('btnLogout')?.addEventListener('click', (e) => {
        e.preventDefault();
        MockDB.logout();
        window.location.reload();
    });
}

// ========================
// ACTIVE NAV LINK
// ========================
function setActiveNav(pageId) {
    document.querySelectorAll('.navbar-custom .nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.dataset.page === pageId) {
            link.classList.add('active');
        }
    });
}

// ========================
// DOCUMENT READY
// ========================
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initAOS();
    renderNavbarUser();

    // Theme toggle button
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn && !themeBtn.dataset.themeBound) {
        themeBtn.dataset.themeBound = 'true';
        themeBtn.addEventListener('click', toggleTheme);
    }
});
