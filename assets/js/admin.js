/**
 * GODMINTON - ADMIN & OWNER PANEL (Tuần 3 — Kết nối API MySQL thật)
 * Người phụ trách: Huy (Task 13, Task 14, Dashboard)
 *
 * Cung cấp các hàm gọi API thật (api/admin/courts.php, api/admin/slots.php,
 * api/admin/dashboard.php, api/auth/logout.php) đồng thời giữ các hàm
 * tương thích ngược cho admin-bookings.php.
 */

const AdminApp = (() => {
    let cachedCourts = [];
    let cachedWards  = [];

    /** Định dạng tiền tệ VNĐ thuần JS */
    function formatVND(amount) {
        return new Intl.NumberFormat('vi-VN').format(Number(amount) || 0) + ' đ';
    }

    const escapeHTML=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    /* ================= AUTH GUARD (SERVER SESSION) ================= */
    function requireAuth() {
        const user = window.__GODMINTON_USER__ || null;

        if (!user || (user.role !== 'owner' && user.role !== 'admin')) {
            const redirectUrl = encodeURIComponent(window.location.pathname + window.location.search);
            window.location.href = `auth.php?redirect=${redirectUrl}`;
            return null;
        }
        return user;
    }

    /* ================= API HELPER ================= */
    async function apiRequest(url, options = {}) {
        const method=(options.method||'GET').toUpperCase();
        const defaultHeaders = { 'Content-Type': 'application/json' };
        if(method!=='GET' && method!=='HEAD'){
            const context=await fetch('api/auth/context.php',{credentials:'same-origin',cache:'no-store'});
            const identity=await context.json();if(!context.ok)throw new Error('Không thể tạo phiên thao tác.');
            defaultHeaders['X-GODMINTON-CSRF']=identity.data.csrf_token;
            if(url.startsWith('api/admin/courts.php') && ['PUT','PATCH'].includes(method) && window.__GODMINTON_USER__?.role==='admin'){
                const body=JSON.parse(options.body||'{}');
                const auth=await Swal.fire({
                    titleText:'Xác nhận thay đổi sân',
                    html:'<label class="d-block text-start">Lý do quản trị<textarea id="courtAdminReason" class="form-control mt-2" rows="3" maxlength="1000"></textarea></label><label class="d-block text-start mt-3">Mật khẩu Admin<input id="courtAdminPassword" class="form-control mt-2" type="password" autocomplete="current-password"></label>',
                    showCancelButton:true,
                    confirmButtonText:'Lưu thay đổi',
                    cancelButtonText:'Hủy',
                    didOpen:(popup)=>{
                        document.querySelectorAll('.modal.show').forEach(m=>window.bootstrap?.Modal?.getInstance(m)?._focustrap?.deactivate());
                        setTimeout(()=>popup.querySelector('#courtAdminReason')?.focus(), 50);
                    },
                    preConfirm:()=>{const reason=document.getElementById('courtAdminReason').value.trim(),password=document.getElementById('courtAdminPassword').value;if(reason.length<5||!password){Swal.showValidationMessage('Nhập lý do ít nhất 5 ký tự và mật khẩu Admin.');return false;}return {admin_reason:reason,admin_password:password};}
                });
                if(!auth.isConfirmed)throw new Error('Đã hủy thay đổi.');options={...options,body:JSON.stringify({...body,...auth.value})};
            }
        }
        const config = {
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
            headers: { ...defaultHeaders, ...(options.headers || {}) }
        };
        const res = await fetch(url, config);
        let json = null;
        try {
            json = await res.json();
        } catch (_) {
            json = { status: 'error', message: 'Phản hồi máy chủ không hợp lệ.' };
        }
        if (!res.ok || json.status === 'error') {
            const err = new Error(json?.message || `Lỗi HTTP ${res.status}`);
            err.status = res.status;
            err.payload = json;
            throw err;
        }
        return json;
    }

    /* ================= COURTS API (Task 13) ================= */
    async function fetchCourts(search = '') {
        const q = search ? `?search=${encodeURIComponent(search)}` : '';
        const res = await apiRequest(`api/admin/courts.php${q}`, { method: 'GET' });
        cachedCourts = Array.isArray(res.data) ? res.data : [];
        if (Array.isArray(res.wards)) {
            cachedWards = res.wards;
        }
        return { courts: cachedCourts, wards: cachedWards, owners: res.owners || [] };
    }

    async function createCourt(payload) {
        const res = await apiRequest('api/admin/courts.php', {
            method: 'POST',
            body: JSON.stringify(payload)
        });
        return res.data;
    }

    async function updateCourt(id, payload) {
        const res = await apiRequest('api/admin/courts.php', {
            method: 'PUT',
            body: JSON.stringify({ id: Number(id), ...payload })
        });
        return res.data;
    }

    async function setCourtStatusApi(id, status) {
        const res = await apiRequest('api/admin/courts.php', {
            method: 'PATCH',
            body: JSON.stringify({ id: Number(id), status: status })
        });
        return res;
    }

    async function deleteCourtApi(id) {
        return setCourtStatusApi(id, 'inactive');
    }

    /* ================= SLOTS API (Task 14) ================= */
    async function fetchSlots(courtId, dateStr) {
        const params = new URLSearchParams({ court_id: courtId, date: dateStr });
        const res = await apiRequest(`api/admin/slots.php?${params.toString()}`, { method: 'GET' });
        return res.data || [];
    }

    async function generateSlotsApi(courtId, dateStr, opts) {
        const res = await apiRequest('api/admin/slots.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'generate',
                court_id: Number(courtId),
                slot_date: dateStr,
                start_time: opts.startTime,
                end_time: opts.endTime,
                duration_min: Number(opts.durationMin),
                price: Number(opts.price),
                sub_courts: opts.subCourts
            })
        });
        return res;
    }

    async function setSlotStatusApi(slotId, status) {
        const res = await apiRequest('api/admin/slots.php', {
            method: 'PATCH',
            body: JSON.stringify({ slot_id: Number(slotId), status })
        });
        return res;
    }

    async function updateSlotApi(slotId, data = {}) {
        const payload = { slot_id: Number(slotId), ...data };
        const res = await apiRequest('api/admin/slots.php', {
            method: 'PATCH',
            body: JSON.stringify(payload)
        });
        return res;
    }

    async function bulkMaintenanceSlotsApi(courtId, dateStr, opts = {}) {
        const res = await apiRequest('api/admin/slots.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'bulk_maintenance',
                court_id: Number(courtId),
                slot_date: dateStr,
                sub_court_name: opts.subCourtName || 'all',
                start_time: opts.startTime || '',
                end_time: opts.endTime || '',
                status: opts.status || 'maintenance',
                note: opts.note || ''
            })
        });
        return res;
    }

    async function bulkReopenSlotsApi(courtId, dateStr, opts = {}) {
        const res = await apiRequest('api/admin/slots.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'bulk_reopen',
                court_id: Number(courtId),
                slot_date: dateStr,
                sub_court_name: opts.subCourtName || 'all',
                start_time: opts.startTime || '',
                end_time: opts.endTime || ''
            })
        });
        return res;
    }

    async function bulkPriceSlotsApi(courtId, dateStr, opts = {}) {
        const res = await apiRequest('api/admin/slots.php', {
            method: 'POST',
            body: JSON.stringify({
                action: 'bulk_price',
                court_id: Number(courtId),
                slot_date: dateStr,
                sub_court_name: opts.subCourtName || 'all',
                price: Number(opts.price)
            })
        });
        return res;
    }

    async function deleteSlotApi(slotId) {
        const res = await apiRequest(`api/admin/slots.php?id=${encodeURIComponent(slotId)}`, {
            method: 'DELETE'
        });
        return res;
    }

    async function bulkDeleteSlotsApi(courtId, dateStr, opts = {}) {
        const body = {
            bulk_delete: 1,
            court_id: Number(courtId),
            slot_date: dateStr,
            start_time: opts.startTime || '',
            end_time: opts.endTime || '',
            sub_courts: opts.subCourts || []
        };
        const res = await apiRequest('api/admin/slots.php', {
            method: 'DELETE',
            body: JSON.stringify(body)
        });
        return res;
    }

    async function clearDaySlotsApi(courtId, dateStr) {
        const params = new URLSearchParams({
            court_id: courtId,
            date: dateStr,
            clear_day: '1'
        });
        const res = await apiRequest(`api/admin/slots.php?${params.toString()}`, {
            method: 'DELETE'
        });
        return res;
    }

    /* ================= DASHBOARD API ================= */
    async function fetchDashboard() {
        const res = await apiRequest('api/admin/dashboard.php', { method: 'GET' });
        return res.data;
    }

    function minutesToTime(total) {
        const h = String(Math.floor(total / 60)).padStart(2, '0');
        const m = String(total % 60).padStart(2, '0');
        return `${h}:${m}`;
    }

    /* ================= UI: SIDEBAR / TOPBAR ================= */
    function renderShell(activePage, pageTitle, breadcrumb) {
        const user = requireAuth();
        if (!user) return null;

        const escapeShell=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
        const initial = escapeShell(user.name ? user.name.charAt(0).toUpperCase() : 'C');
        const roleLabel = user.role === 'admin' ? 'Quản trị viên' : 'Chủ sân';

        const navItems = [
            { key: 'dashboard', href: 'admin-dashboard.php', icon: 'fa-gauge-high', label: 'Tổng quan' },
            { key: 'courts', href: 'admin-courts.php', icon: 'fa-building-shield', label: 'Quản lý sân' },
            { key: 'slots', href: 'admin-slots.php', icon: 'fa-clock', label: 'Quản lý khung giờ' },
            { key: 'bookings', href: 'admin-bookings.php', icon: 'fa-clipboard-list', label: 'Quản lý đơn đặt' },
            { key: 'vouchers', href: 'admin-vouchers.php', icon: 'fa-ticket', label: 'Mã giảm giá' },
        ];

        if(user.role==='admin')navItems.push(
            {key:'memberships',href:'admin-memberships.php',icon:'fa-crown',label:'Quản lý hội viên'},
            {key:'users',href:'admin-users.php',icon:'fa-users-gear',label:'Quản lý tài khoản'},
            {key:'owner-requests',href:'admin-owner-requests.php',icon:'fa-file-circle-check',label:'Yêu cầu chủ sân'},
            {key:'posts',href:'admin-posts.php',icon:'fa-newspaper',label:'Quản lý bài viết'}
        );
        else navItems.push({key:'application',href:'owner-application.php',icon:'fa-file-circle-plus',label:'Đăng ký sân mới'});
        document.querySelectorAll('.brand-sub').forEach(el=>el.textContent=user.role==='admin'?'Kênh quản trị':'Kênh chủ sân');
        const navHtml = navItems.map(item => `
            <a class="admin-nav-link ${item.key === activePage ? 'active' : ''}" href="${item.href}">
                <i class="fa-solid ${item.icon}"></i> ${item.label}
            </a>`).join('');

        const navEl = document.getElementById('adminSidebarNav');
        if (navEl) navEl.innerHTML = navHtml;

        const userBoxEl = document.getElementById('adminUserBox');
        if (userBoxEl) {
            userBoxEl.innerHTML = `
            <div class="dropdown w-100">
                <button class="btn w-100 d-flex align-items-center gap-2 border-0 text-start px-2" type="button" data-bs-toggle="dropdown" style="background:rgba(255,255,255,.04); border-radius:.75rem; color:#e2e8f0;">
                    <div class="avatar-sm rounded-circle bg-teal text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0">${initial}</div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold small text-truncate" style="color:#f1f5f9;">${escapeShell(user.name)}</div>
                        <div class="text-truncate" style="font-size:.72rem; color:#94a3b8;">${roleLabel}</div>
                    </div>
                    <i class="fa-solid fa-chevron-up small" style="color:#64748b;"></i>
                </button>
                <ul class="dropdown-menu shadow-lg border-0 rounded-4">
                    <li><a class="dropdown-item py-2" href="profile.php"><i class="fa-solid fa-circle-user me-2 text-teal"></i>Trang cá nhân</a></li>
                    <li><a class="dropdown-item py-2" href="index.php"><i class="fa-solid fa-house me-2 text-muted"></i>Về trang khách</a></li>
                    <li><a class="dropdown-item py-2 text-primary fw-semibold" href="auth.php"><i class="fa-solid fa-arrow-right-arrow-left me-2"></i>Đổi tài khoản khác</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger fw-semibold" href="#" id="adminLogoutBtn"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất tài khoản</a></li>
                </ul>
            </div>`;
        }

        document.getElementById('adminLogoutBtn')?.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch('api/auth/logout.php', { method: 'POST', credentials: 'same-origin' });
                if (!response.ok) throw new Error('Đăng xuất thất bại.');
            } catch (error) {toast('error', error.message);return;}
            window.location.href = 'auth.php';
        });

        const titleEl = document.getElementById('adminPageTitle');
        if (titleEl) titleEl.textContent = pageTitle;
        const crumbEl = document.getElementById('adminBreadcrumb');
        if (crumbEl) crumbEl.textContent = breadcrumb || '';

        // Mobile sidebar toggle
        document.getElementById('adminSidebarToggle')?.addEventListener('click', () => {
            document.getElementById('adminSidebar')?.classList.add('show');
            document.getElementById('adminOverlay')?.classList.add('show');
        });
        document.getElementById('adminOverlay')?.addEventListener('click', () => {
            document.getElementById('adminSidebar')?.classList.remove('show');
            document.getElementById('adminOverlay')?.classList.remove('show');
        });

        return user;
    }

    function toast(icon, title) {
        if (typeof Swal === 'undefined') { alert(title); return; }
        Swal.fire({ toast: true, position: 'top-end', icon, title, showConfirmButton: false, timer: 2200, timerProgressBar: true });
    }

    function confirmAction(title, text) {
        if (typeof Swal === 'undefined') return Promise.resolve(confirm(title));
        return Swal.fire({
            title, text, icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Xác nhận', cancelButtonText: 'Hủy bỏ',
            confirmButtonColor: '#0d9488', cancelButtonColor: '#94a3b8'
        }).then(r => r.isConfirmed);
    }

    return {
        escapeHTML,
        formatVND,
        requireAuth,
        fetchCourts,
        createCourt,
        updateCourt,
        setCourtStatusApi,
        deleteCourtApi,
        fetchSlots,
        generateSlotsApi,
        setSlotStatusApi,
        updateSlotApi,
        bulkMaintenanceSlotsApi,
        bulkReopenSlotsApi,
        bulkPriceSlotsApi,
        deleteSlotApi,
        bulkDeleteSlotsApi,
        clearDaySlotsApi,
        fetchDashboard,
        renderShell,
        toast,
        confirmAction,
        minutesToTime,
        initAdminTheme,
        initAdminPushBell
    };
})();

// Quản lý Dark/Light Theme trên các trang Admin
function initAdminTheme() {
    const saved = localStorage.getItem('godminton_theme') || localStorage.getItem('bm_theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', saved);
    updateAdminThemeIcon(saved);

    const themeBtn = document.getElementById('themeToggle');
    if (themeBtn) {
        // Nếu app.js đã quản lý toggleTheme, không gắn thêm listener để tránh bị toggle 2 lần (double-toggle)
        if (typeof toggleTheme === 'function') {
            return;
        }
        if (!themeBtn.dataset.adminThemeBound) {
            themeBtn.dataset.adminThemeBound = 'true';
            themeBtn.addEventListener('click', function (e) {
                if (e && e.preventDefault) e.preventDefault();
                const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-bs-theme', next);
                localStorage.setItem('godminton_theme', next);
                localStorage.setItem('bm_theme', next);
                updateAdminThemeIcon(next);
            });
        }
    }
}

function updateAdminThemeIcon(theme) {
    const themeBtn = document.getElementById('themeToggle');
    if (!themeBtn) return;
    if (theme === 'dark') {
        themeBtn.innerHTML = '<i class="fa-solid fa-sun text-warning"></i>';
        themeBtn.setAttribute('title', 'Chuyển sang chế độ Sáng');
    } else {
        themeBtn.innerHTML = '<i class="fa-solid fa-moon"></i>';
        themeBtn.setAttribute('title', 'Chuyển sang chế độ Tối');
    }
}

// Loại bỏ nút chuông thông báo trên kênh Quản trị & Chủ sân
function initAdminPushBell() {
    const bellBtn = document.getElementById('pushBellToggle');
    if (bellBtn) bellBtn.remove();
}

function initAdminComponents() {
    initAdminTheme();
    initAdminPushBell();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAdminComponents);
} else {
    initAdminComponents();
}

