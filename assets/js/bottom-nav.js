/**
 * GODMINTON — Bottom Navigation Bar
 * Tự động inject vào tất cả trang, phát hiện trang active qua URL.
 * Thêm vào bất kỳ trang nào: <script src="assets/js/bottom-nav.js"></script>
 */
(function () {
    'use strict';

    /* ============================================================
       1. CẤU HÌNH CÁC TAB
       ============================================================ */
    const user = (typeof MockDB !== 'undefined' && MockDB.getUser) ? MockDB.getUser() : null;

    const TABS = [
        {
            id:      'home',
            label:   'Trang chủ',
            icon:    'fa-house',
            href:    'index.php',
            match:   ['index.php', 'index.html', '/', ''],
        },
        {
            id:      'profile',
            label:   'Cá nhân',
            icon:    'fa-circle-user',
            href:    'profile.php',
            match:   ['profile.php', 'history.php', 'auth.php', 'login.php', 'register.php', 'admin-dashboard.php', 'admin-courts.php', 'admin-slots.php', 'admin-bookings.php',
                      'profile.html', 'history.html', 'login.html', 'register.html', 'auth.html', 'admin-dashboard.html', 'admin-courts.html', 'admin-slots.html', 'admin-bookings.html'],
        },
        {
            id:      'courts',
            label:   'Đặt sân',
            icon:    'fa-layer-group',
            href:    'courts.php',
            match:   ['courts.php', 'court-detail.php', 'booking.php', 'confirm.php', 'payment.php',
                      'courts.html', 'court-detail.html', 'booking.html', 'confirm.html', 'payment.html'],
            center:  true,                        // FAB trung tâm nổi bật
        },
        {
            id:      'news',
            label:   'Khám phá',
            icon:    'fa-compass',
            href:    'news.php',
            match:   ['news.php', 'news.html'],
        },
        {
            id:      'contact',
            label:   'Liên hệ',
            icon:    'fa-headset',
            href:    'contact.php',
            match:   ['contact.php', 'contact.html'],
        },
    ];

    /* ============================================================
       2. XÁC ĐỊNH TRANG ĐANG ACTIVE
       ============================================================ */
    const currentPage = (window.location.pathname.split('/').pop() || 'index.php').split('?')[0];

    function isActive(tab) {
        if (!tab.match) return false;
        return tab.match.includes(currentPage);
    }

    /* ============================================================
       3. INJECT CSS
       ============================================================ */
    const CSS = `
    /* =====================================================
       BOTTOM NAVIGATION BAR — GODMINTON
       ===================================================== */

    /* Dành chỗ cho thanh nav ở cuối trang */
    body.has-bottom-nav {
        padding-bottom: calc(68px + env(safe-area-inset-bottom, 0px)) !important;
    }

    /* Thanh nav cố định phía dưới */
    #godmintonBottomNav {
        position: fixed;
        bottom: 0; left: 0; right: 0;
        z-index: 1000;
        height: calc(64px + env(safe-area-inset-bottom, 0px));
        padding-bottom: env(safe-area-inset-bottom, 0px);
        background: var(--bg-body, #fff);
        border-top: 1px solid rgba(0,0,0,0.07);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: stretch;
    }

    /* Desktop admin layout: không đè sidebar */
    @media (min-width: 992px) {
        body.admin-body #godmintonBottomNav {
            left: 260px;
        }
    }

    /* Dark mode adjustment */
    [data-bs-theme="dark"] #godmintonBottomNav {
        background: #111827;
        border-top-color: rgba(255,255,255,0.07);
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
    }

    /* ---- Mỗi tab item ---- */
    .bnav-item {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        text-decoration: none !important;
        color: #94a3b8;
        cursor: pointer;
        border: none;
        background: transparent;
        padding: 0;
        transition: color 0.18s ease;
        position: relative;
        overflow: hidden;
    }

    .bnav-item i {
        font-size: 1.2rem;
        line-height: 1;
        transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1), color 0.18s;
    }

    .bnav-item span {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 0.2px;
        white-space: nowrap;
        font-family: 'Plus Jakarta Sans', sans-serif;
        transition: color 0.18s;
    }

    /* Hover state */
    .bnav-item:hover {
        color: #0d9488;
    }
    .bnav-item:hover i {
        transform: translateY(-2px);
    }

    /* Active state */
    .bnav-item.active {
        color: #0d9488;
    }
    .bnav-item.active i {
        transform: translateY(-1px) scale(1.08);
    }

    /* Active indicator dot */
    .bnav-item.active::after {
        content: '';
        position: absolute;
        bottom: 3px;
        left: 50%; transform: translateX(-50%);
        width: 4px; height: 4px;
        border-radius: 50%;
        background: #0d9488;
        opacity: 0.8;
    }
    .bnav-item.bnav-center.active::after { display: none; }

    /* ---- Nút trung tâm "Đặt sân" (Hero FAB nổi bật) ---- */
    .bnav-item.bnav-center {
        justify-content: flex-end;
        padding-bottom: 4px;
        gap: 4px;
        overflow: visible;
    }

    .bnav-center-fab {
        width: 54px;
        height: 54px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d9488 0%, #14b8a6 50%, #2dd4bf 100%);
        border: 3.5px solid #111827;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
        margin-top: -24px;
        box-shadow: 0 8px 24px rgba(13, 148, 136, 0.45), 0 2px 8px rgba(0, 0, 0, 0.3);
        transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        flex-shrink: 0;
        animation: bnavGlowPulse 3s infinite alternate ease-in-out;
    }
    [data-bs-theme="light"] .bnav-center-fab {
        border-color: #ffffff;
        box-shadow: 0 8px 22px rgba(13, 148, 136, 0.35), 0 2px 6px rgba(0, 0, 0, 0.1);
    }
    .bnav-center-fab i {
        font-size: 1.35rem;
        color: #ffffff !important;
        transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.25));
    }

    /* Center FAB Hover & Active */
    .bnav-item.bnav-center:hover .bnav-center-fab {
        transform: scale(1.14) translateY(-5px);
        box-shadow: 0 14px 34px rgba(13, 148, 136, 0.65), 0 0 18px rgba(45, 212, 191, 0.45);
        background: linear-gradient(135deg, #14b8a6 0%, #2dd4bf 100%);
    }
    .bnav-item.bnav-center:hover .bnav-center-fab i {
        transform: scale(1.15) rotate(-6deg);
    }
    .bnav-item.bnav-center.active .bnav-center-fab {
        transform: scale(1.1) translateY(-3px);
        box-shadow: 0 10px 28px rgba(13, 148, 136, 0.6), 0 0 16px rgba(45, 212, 191, 0.4);
        background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
    }
    .bnav-item.bnav-center span {
        font-weight: 800;
        color: #2dd4bf;
    }

    @keyframes bnavGlowPulse {
        0% {
            box-shadow: 0 8px 24px rgba(13, 148, 136, 0.4), 0 0 0 0 rgba(45, 212, 191, 0.3);
        }
        100% {
            box-shadow: 0 10px 28px rgba(13, 148, 136, 0.55), 0 0 0 6px rgba(45, 212, 191, 0.12);
        }
    }

    /* Dark mode text */
    [data-bs-theme="dark"] .bnav-item { color: #64748b; }
    [data-bs-theme="dark"] .bnav-item:hover,
    [data-bs-theme="dark"] .bnav-item.active { color: #14b8a6; }
    [data-bs-theme="dark"] .bnav-item.active::after { background: #14b8a6; }

    /* =====================================================
       PAGE SLIDE TRANSITIONS (HIỆU ỨNG TRƯỢT CHUYỂN TRANG ĐỒNG BỘ 0.22s)
       ===================================================== */
    .page-slide-in-right {
        animation: pageSlideInRight var(--page-transition-duration, 0.22s) var(--page-transition-timing, cubic-bezier(0.16, 1, 0.3, 1)) both !important;
        will-change: transform, opacity;
    }
    .page-slide-in-left {
        animation: pageSlideInLeft var(--page-transition-duration, 0.22s) var(--page-transition-timing, cubic-bezier(0.16, 1, 0.3, 1)) both !important;
        will-change: transform, opacity;
    }
    @keyframes pageSlideInRight {
        0% { transform: translate3d(28px, 0, 0); opacity: 0.88; }
        100% { transform: translate3d(0, 0, 0); opacity: 1; }
    }
    @keyframes pageSlideInLeft {
        0% { transform: translate3d(-28px, 0, 0); opacity: 0.88; }
        100% { transform: translate3d(0, 0, 0); opacity: 1; }
    }

    /* ---- Ripple animation ---- */
    .bnav-ripple {
        position: absolute;
        border-radius: 50%;
        background: rgba(13, 148, 136, 0.18);
        transform: scale(0);
        animation: bnavRippleAnim 0.4s linear;
        pointer-events: none;
    }
    @keyframes bnavRippleAnim {
        to { transform: scale(4); opacity: 0; }
    }

    /* ---- Nút chuyển đổi Theme nổi góc trên ---- */
    .bnav-theme-float {
        position: fixed;
        top: 14px;
        right: 14px;
        z-index: 1040;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(15, 23, 42, 0.75);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
        cursor: pointer;
        transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .bnav-theme-float:hover {
        transform: scale(1.1);
        background: #0d9488;
        border-color: #0d9488;
        color: #fff;
    }
    [data-bs-theme="light"] .bnav-theme-float {
        background: rgba(255, 255, 255, 0.85);
        border-color: rgba(0, 0, 0, 0.12);
        color: #1e293b;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }
    `;

    const styleEl = document.createElement('style');
    styleEl.id = 'godminton-bottom-nav-css';
    styleEl.textContent = CSS;
    document.head.appendChild(styleEl);

    /* ============================================================
       4. BUILD HTML
       ============================================================ */
    function buildNavHTML() {
        return TABS.map(tab => {
            const active = isActive(tab) ? ' active' : '';
            const logoutAttr = tab.isLogout ? ' data-is-logout="true"' : '';

            if (tab.center) {
                return `
                <a href="${tab.href}" class="bnav-item bnav-center${active}"
                   aria-label="${tab.label}" data-bnav-id="${tab.id}"${logoutAttr}>
                    <div class="bnav-center-fab" aria-hidden="true">
                        <i class="fa-solid ${tab.icon}"></i>
                    </div>
                    <span>${tab.label}</span>
                </a>`;
            }

            return `
            <a href="${tab.href}" class="bnav-item${active}"
               aria-label="${tab.label}" data-bnav-id="${tab.id}"${logoutAttr}>
                <i class="fa-solid ${tab.icon}" aria-hidden="true"></i>
                <span>${tab.label}</span>
            </a>`;
        }).join('');
    }

    /* ============================================================
       5. NÚT CHUYỂN GIAO DIỆN SÁNG / TỐI NỔI & ĐỒNG BỘ LOGO
       ============================================================ */
    function updateLogoTheme() {
        const isLight = document.documentElement.getAttribute('data-bs-theme') === 'light';
        document.querySelectorAll('.brand-logo-img').forEach(img => {
            const targetSrc = isLight ? 'assets/img/logo-teal.svg' : 'assets/img/logo.svg';
            if (img.getAttribute('src') !== targetSrc) {
                img.src = targetSrc;
            }
        });
    }

    // Tự động lắng nghe thay đổi data-bs-theme trên toàn trang
    if (typeof MutationObserver !== 'undefined') {
        const themeObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.attributeName === 'data-bs-theme') {
                    updateLogoTheme();
                }
            });
        });
        themeObserver.observe(document.documentElement, { attributes: true, attributeFilter: ['data-bs-theme'] });
    }

    function injectThemeToggle() {
        if (document.getElementById('themeToggle')) {
            updateLogoTheme();
            return;
        }

        const btn = document.createElement('button');
        btn.id = 'themeToggle';
        btn.className = 'bnav-theme-float';
        btn.title = 'Chuyển chế độ Sáng / Tối';
        btn.setAttribute('aria-label', 'Chuyển chế độ Sáng / Tối');

        function updateIcon() {
            const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
            btn.innerHTML = isDark ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
            updateLogoTheme();
        }
        updateIcon();

        btn.addEventListener('click', function () {
            if (typeof toggleTheme === 'function') {
                toggleTheme();
                setTimeout(updateIcon, 50);
            } else {
                const current = document.documentElement.getAttribute('data-bs-theme') || 'dark';
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('godminton_theme', next);
                updateIcon();
            }
        });

        document.body.appendChild(btn);
    }

    /* ============================================================
       6. INJECT VÀO DOM
       ============================================================ */
    function inject() {
        // Không inject thanh điều hướng trên trang Đăng nhập / Đăng ký
        if (currentPage === 'auth.php' || currentPage === 'auth.html') return;

        // Inject Theme Button nếu trang chưa có
        injectThemeToggle();

        // Tránh inject 2 lần
        if (document.getElementById('godmintonBottomNav')) return;

        const nav = document.createElement('nav');
        nav.id = 'godmintonBottomNav';
        nav.setAttribute('aria-label', 'Điều hướng chính');
        nav.innerHTML = buildNavHTML();
        document.body.appendChild(nav);

        // Thêm class để body có padding-bottom đúng
        document.body.classList.add('has-bottom-nav');

        /* ---- Ripple effect khi tap ---- */
        nav.querySelectorAll('.bnav-item').forEach(item => {
            item.addEventListener('pointerdown', function (e) {
                const ripple = document.createElement('span');
                ripple.className = 'bnav-ripple';
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                ripple.style.cssText = `
                    width: ${size}px; height: ${size}px;
                    left: ${e.clientX - rect.left - size / 2}px;
                    top: ${e.clientY - rect.top - size / 2}px;
                `;
                this.appendChild(ripple);
                ripple.addEventListener('animationend', () => ripple.remove());
            });
        });

        /* ---- Xử lý Đăng xuất khi click tab Đăng xuất ---- */
        nav.querySelectorAll('.bnav-item').forEach(item => {
            if (item.dataset.isLogout === 'true') {
                item.addEventListener('click', function (e) {
                    e.preventDefault();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Đăng xuất tài khoản?',
                            text: `Bạn đang đăng nhập với tên "${user ? user.name : 'Khách'}". Bạn có chắc chắn muốn đăng xuất không?`,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#0d9488',
                            cancelButtonColor: '#64748b',
                            confirmButtonText: '<i class="fa-solid fa-right-from-bracket me-1"></i> Đăng xuất',
                            cancelButtonText: 'Ở lại'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                if (typeof MockDB !== 'undefined' && MockDB.logout) {
                                    MockDB.logout();
                                }
                                window.location.href = 'auth.php';
                            }
                        });
                    } else {
                        if (confirm('Bạn có chắc chắn muốn đăng xuất không?')) {
                            if (typeof MockDB !== 'undefined' && MockDB.logout) {
                                MockDB.logout();
                            }
                            window.location.href = 'auth.php';
                        }
                    }
                });
            }
        });

        /* ---- Kích hoạt hiệu ứng Slide trượt chuyển trang ---- */
        initPageSlideTransition(nav);
    }

    /* ============================================================
       7. HIỆU ỨNG SLIDE TRƯỢT CHUYỂN TRANG ĐỒNG BỘ TOÀN DỰ ÁN (0.22s)
       ============================================================ */
    const PAGE_INDEX_MAP = {
        'index.php': 0, 'index.html': 0,
        'courts.php': 10, 'courts.html': 10,
        'court-detail.php': 12, 'court-detail.html': 12,
        'booking.php': 14, 'booking.html': 14,
        'payment.php': 16, 'payment.html': 16,
        'confirm.php': 18, 'confirm.html': 18,
        'history.php': 20, 'history.html': 20,
        'news.php': 30, 'news.html': 30,
        'contact.php': 40, 'contact.html': 40,
        'profile.php': 50, 'profile.html': 50,
        'auth.php': 52, 'auth.html': 52,
        'login.html': 54, 'register.html': 56,
        'admin-dashboard.php': 60, 'admin-dashboard.html': 60,
        'admin-courts.php': 62, 'admin-courts.html': 62,
        'admin-slots.php': 64, 'admin-slots.html': 64,
        'admin-bookings.php': 66, 'admin-bookings.html': 66
    };

    function getNavDirection(fromPage, toPage, element) {
        // 1. Kiểm tra nếu là nút/thẻ quay lại (Back)
        if (element) {
            const elText = (element.innerText || '').trim().toLowerCase();
            if (element.classList.contains('btn-back') ||
                element.classList.contains('drawer-close') ||
                element.hasAttribute('data-nav-back') ||
                element.querySelector('.fa-arrow-left') ||
                elText.startsWith('quay lại') ||
                elText.includes('trở về')) {
                return 'left';
            }
            if (element.classList.contains('btn-next') ||
                element.classList.contains('btn-pay-cta') ||
                element.querySelector('.fa-arrow-right')) {
                return 'right';
            }
        }

        // 2. So sánh thứ tự phân cấp trang
        const fromIdx = PAGE_INDEX_MAP[fromPage] !== undefined ? PAGE_INDEX_MAP[fromPage] : 0;
        const toIdx = PAGE_INDEX_MAP[toPage] !== undefined ? PAGE_INDEX_MAP[toPage] : fromIdx;

        if (toIdx !== fromIdx) {
            return toIdx > fromIdx ? 'right' : 'left';
        }

        return 'right';
    }

    // Hàm điều hướng toàn cục có hỗ trợ hướng trượt
    window.godmintonNavigate = function (url, explicitDir) {
        if (!url) return;
        const toPage = url.split('?')[0].split('#')[0].split('/').pop() || 'index.php';
        const direction = explicitDir || getNavDirection(currentPage, toPage);
        sessionStorage.setItem('godminton_slide_direction', direction);
        sessionStorage.setItem('godminton_last_page', currentPage);
        sessionStorage.setItem('godminton_last_tab_idx', String(PAGE_INDEX_MAP[currentPage] !== undefined ? PAGE_INDEX_MAP[currentPage] : 0));
        window.location.href = url;
    };

    function initPageSlideTransition(nav) {
        // A. Kích hoạt hiệu ứng slide khi trang vừa tải xong
        applyEntrySlideTransition();

        // B. Bắt sự kiện click trên toàn bộ liên kết nội bộ của trang
        document.addEventListener('click', function (e) {
            const link = e.target.closest('a');
            if (!link) return;

            const href = link.getAttribute('href');
            if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
            if (link.target === '_blank' || link.hasAttribute('download')) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

            try {
                const destUrl = new URL(link.href, window.location.href);
                if (destUrl.origin !== window.location.origin) return;

                const toPage = destUrl.pathname.split('/').pop() || 'index.php';
                if (toPage === currentPage && destUrl.search === window.location.search) return;

                const direction = getNavDirection(currentPage, toPage, link);
                sessionStorage.setItem('godminton_slide_direction', direction);
                sessionStorage.setItem('godminton_last_page', currentPage);
                sessionStorage.setItem('godminton_last_tab_idx', String(PAGE_INDEX_MAP[currentPage] !== undefined ? PAGE_INDEX_MAP[currentPage] : 0));

                // Nếu click vào tab bottom-nav thì active lập tức
                if (nav) {
                    nav.querySelectorAll('.bnav-item').forEach(item => {
                        const itemHref = item.getAttribute('href');
                        if (itemHref && itemHref.split('?')[0] === toPage) {
                            nav.querySelectorAll('.bnav-item').forEach(x => x.classList.remove('active'));
                            item.classList.add('active');
                        }
                    });
                }
            } catch (err) {}
        }, true);
    }

    function applyEntrySlideTransition() {
        const savedDir = sessionStorage.getItem('godminton_slide_direction');
        const lastIdxStr = sessionStorage.getItem('godminton_last_tab_idx');
        const lastIdx = lastIdxStr !== null ? parseInt(lastIdxStr, 10) : null;
        const currentIdx = PAGE_INDEX_MAP[currentPage] !== undefined ? PAGE_INDEX_MAP[currentPage] : 0;

        let direction = savedDir;
        if (!direction && lastIdx !== null && lastIdx !== currentIdx) {
            direction = currentIdx > lastIdx ? 'right' : 'left';
        }

        sessionStorage.setItem('godminton_last_tab_idx', String(currentIdx));
        sessionStorage.setItem('godminton_last_page', currentPage);
        sessionStorage.removeItem('godminton_slide_direction');

        if (!direction) return;

        const slideClass = direction === 'right' ? 'page-slide-in-right' : 'page-slide-in-left';

        // Thu thập các thành phần nội dung cần trượt (giữ cố định header và bottom-nav)
        const targets = [];
        const mainContent = document.querySelector('main') || 
                            document.querySelector('.auth-wrapper') || 
                            document.querySelector('.admin-content');
        if (mainContent) targets.push(mainContent);

        // Trên courts.html: search-bar-wrap cũng trượt đồng bộ với main
        const searchBar = document.querySelector('.search-bar-wrap');
        if (searchBar) targets.push(searchBar);

        // Fallback nếu không có thẻ đặc thù
        if (targets.length === 0) {
            const fallback = document.querySelector('.container') || document.body;
            if (fallback) targets.push(fallback);
        }

        targets.forEach(el => {
            el.classList.add(slideClass);
            el.addEventListener('animationend', () => {
                el.classList.remove(slideClass);
            }, { once: true });
        });
    }

    // Tránh kẹt animation khi dùng Back/Forward cache của trình duyệt
    window.addEventListener('pageshow', function (event) {
        if (event.persisted) {
            document.querySelectorAll('.page-slide-in-right, .page-slide-in-left').forEach(el => {
                el.classList.remove('page-slide-in-right', 'page-slide-in-left');
            });
        }
    });

    /* ============================================================
       8. KHỞI CHẠY
       ============================================================ */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inject);
    } else {
        inject();
    }

})();
