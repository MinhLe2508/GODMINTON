/**
 * GODMINTON - App JS (Theme, PWA, Navbar Render)
 * Phiên bản tĩnh - không cần PHP
 */

// ========================
// THEME TOGGLE (Dark Mode)
// ========================
function initTheme() {
    const saved = localStorage.getItem('bm_theme') || localStorage.getItem('godminton_theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', saved);
    updateThemeIcon(saved);
    if (typeof updateLogoTheme === 'function') {
        updateLogoTheme();
    }
}

function updateThemeIcon(theme) {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    if (theme === 'dark') {
        btn.innerHTML = '<i class="fa-solid fa-sun text-warning"></i>';
        btn.setAttribute('title', 'Chuyển sang chế độ Sáng');
    } else {
        btn.innerHTML = '<i class="fa-solid fa-moon"></i>';
        btn.setAttribute('title', 'Chuyển sang chế độ Tối');
    }
}

function toggleTheme(e) {
    if (e && e.preventDefault) e.preventDefault();
    const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('bm_theme', next);
    localStorage.setItem('godminton_theme', next);
    updateThemeIcon(next);
    if (typeof updateLogoTheme === 'function') {
        updateLogoTheme();
    }
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
async function renderNavbarUser() {
    const area = document.getElementById('navbarUserArea');
    if (!area) return;
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    try {
        const response = await fetch('api/auth/me.php', {credentials:'same-origin',cache:'no-store'});
        const result = await response.json();
        if (!response.ok || result.status !== 'success') throw new Error('Không tải được tài khoản.');
        const user = result.data.user;
        if (!user) {area.innerHTML='<a class="btn btn-light rounded-pill" href="auth.php">Đăng nhập / Đăng ký</a>';return;}
        area.innerHTML=`<span class="text-white me-2">${esc(user.name)}</span><a class="btn btn-light btn-sm" href="profile.php">Hồ sơ</a> ${['admin','owner'].includes(user.role)?'<a class="btn btn-warning btn-sm" href="admin-bookings.php">Quản lý đơn</a>':''} <button class="btn btn-outline-light btn-sm" id="btnLogout">Đăng xuất</button>`;
        document.getElementById('btnLogout').addEventListener('click',async()=>{
            const out=await fetch('api/auth/logout.php',{method:'POST',credentials:'same-origin'});
            if(out.ok)location.href='auth.php';else alert('Đăng xuất thất bại.');
        });
    } catch(e) {area.textContent=e.message;}
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
// PWA & SERVICE WORKER
// ========================
let deferredInstallPrompt = null;

function initServiceWorker() {
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('sw.js')
                .then(registration => {
                    console.log('[PWA] Service Worker registered with scope:', registration.scope);
                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        if (newWorker) {
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    console.log('[PWA] Phiên bản mới đã sẵn sàng. Tự động cập nhật.');
                                }
                            });
                        }
                    });
                })
                .catch(error => {
                    console.warn('[PWA] Đăng ký Service Worker thất bại:', error);
                });
        });
    }
}

function initPwaInstallPrompt() {
    window.addEventListener('beforeinstallprompt', e => {
        // Ngăn chặn thanh prompt mặc định của trình duyệt để hiển thị giao diện tùy chỉnh đẹp mắt
        e.preventDefault();
        deferredInstallPrompt = e;

        // Hiển thị nút hoặc banner cài đặt nếu có chỗ đặt
        const installBtn = document.getElementById('btnPwaInstall');
        const installBanner = document.getElementById('pwaInstallBanner');

        if (installBtn) {
            installBtn.style.display = 'inline-flex';
            installBtn.onclick = triggerPwaInstall;
        }

        if (installBanner) {
            installBanner.classList.add('show');
            const actionBtn = document.getElementById('btnBannerInstall');
            if (actionBtn) actionBtn.onclick = triggerPwaInstall;
            const closeBtn = document.getElementById('btnBannerDismiss');
            if (closeBtn) closeBtn.onclick = () => installBanner.classList.remove('show');
        }
    });

    window.addEventListener('appinstalled', () => {
        console.log('[PWA] Ứng dụng GODMINTON đã được cài đặt thành công!');
        deferredInstallPrompt = null;
        const installBtn = document.getElementById('btnPwaInstall');
        if (installBtn) installBtn.style.display = 'none';
        const installBanner = document.getElementById('pwaInstallBanner');
        if (installBanner) installBanner.classList.remove('show');
    });
}

async function triggerPwaInstall() {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    const { outcome } = await deferredInstallPrompt.userChoice;
    console.log('[PWA] Lựa chọn của người dùng:', outcome);
    deferredInstallPrompt = null;
}

// ========================
// DOCUMENT READY
// ========================
document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initAOS();
    renderNavbarUser();
    initServiceWorker();
    initPwaInstallPrompt();

    // Theme toggle button
    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn && !themeBtn.hasAttribute('onclick') && !themeBtn.dataset.themeInit) {
        themeBtn.dataset.themeInit = 'true';
        themeBtn.addEventListener('click', toggleTheme);
    }
});
