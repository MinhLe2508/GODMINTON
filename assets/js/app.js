/**
 * GODMINTON - App JS (Theme, PWA, Navbar Render)
 * Dùng chung cho giao diện PHP, lấy trạng thái tài khoản từ API.
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
    const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    try {
        const response = await fetch('api/auth/me.php', { credentials: 'same-origin', cache: 'no-store' });
        const result = await response.json();
        if (!response.ok || result.status !== 'success') throw new Error('Không tải được tài khoản.');
        const user = result.data.user;
        if (!user) { area.innerHTML = '<a class="btn btn-light rounded-pill" href="auth.php">Đăng nhập / Đăng ký</a>'; return; }
        const avatarIcon = user.avatar_url
            ? `<img src="${esc(user.avatar_url)}" alt="" class="rounded-circle me-1 flex-shrink-0" style="width:20px;height:20px;object-fit:cover;vertical-align:text-bottom;" onerror="this.outerHTML='<i class=\\'fa-regular fa-circle-user me-1 text-teal\\'></i>'">`
            : `<i class="fa-regular fa-circle-user me-1 text-teal"></i>`;
        area.innerHTML = `
            <div class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                <a class="btn btn-sm btn-light rounded-pill px-3 fw-semibold shadow-sm text-truncate d-inline-flex align-items-center" style="max-width: 185px;" href="profile.php">
                    ${avatarIcon}<span class="text-truncate">${esc(user.name)}</span>
                </a>
                ${['admin', 'owner'].includes(user.role) ? '<a class="btn btn-warning btn-sm rounded-pill px-3 fw-semibold" href="admin-bookings.php"><i class="fa-solid fa-gauge-high me-1"></i> Quản lý</a>' : ''}
                <a href="auth.php" class="btn btn-sm btn-switch-account rounded-pill px-3 fw-semibold" title="Đổi tài khoản khác">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i> Đổi tài khoản
                </a>
                <button class="btn btn-sm btn-logout-account rounded-pill px-3 fw-semibold" id="btnLogout" title="Đăng xuất tài khoản">
                    <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                </button>
            </div>
        `;
        document.getElementById('btnLogout').addEventListener('click', async () => {
            const out = await fetch('api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
            if (out.ok) {
                location.href = 'auth.php';
            } else if (window.Swal) {
                Swal.fire({ icon: 'error', title: 'Đăng xuất thất bại', text: 'Vui lòng thử lại sau.' });
            } else {
                alert('Đăng xuất thất bại.');
            }
        });
    } catch (e) { area.textContent = e.message; }
}

// ========================
// PWA & SERVICE WORKER
// ========================
let deferredInstallPrompt = null;

function initServiceWorker() {
    if ('serviceWorker' in navigator) {
        const registerSW = () => {
            navigator.serviceWorker.register('sw.js')
                .catch(error => {
                    console.warn('[PWA] Đăng ký Service Worker thất bại:', error);
                });
        };

        if (document.readyState === 'complete') {
            registerSW();
        } else {
            window.addEventListener('load', registerSW);
        }
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
    await deferredInstallPrompt.userChoice;
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
