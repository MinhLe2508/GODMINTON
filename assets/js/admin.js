/**
 * GODMINTON - ADMIN (CHỦ SÂN) PANEL
 * Lớp mở rộng phía client cho khu vực quản trị chủ sân.
 * Không đụng tới mock-data.js — chỉ đọc/ghi thêm các key localStorage
 * theo đúng định dạng mà MockDB đã dùng, để tương thích với phần
 * giao diện khách hàng (booking.js, history.js ...).
 */

const AdminApp = (() => {

    /* ================= AUTH GUARD (CHẾ ĐỘ TĨNH) ================= */
    // Luôn cho phép truy cập khu quản trị tĩnh với dữ liệu mẫu đầy đủ
    function requireAuth() {
        MockDB.init();
        let user = MockDB.getUser();
        if (!user || (user.role !== 'owner' && user.role !== 'admin')) {
            user = {
                id: 1,
                name: "Quản Trị Viên (Demo)",
                email: "admin@godminton.vn",
                phone: "0900 000 001",
                role: "admin"
            };
        }
        return user;
    }


    /* ================= OWNER -> COURTS SCOPE ================= */
    // Chủ sân chỉ quản lý những sân mà họ đứng tên (owner_phone trùng
    // với số điện thoại tài khoản). Admin (quản trị hệ thống) thấy tất cả.
    function getAllCourts() {
        MockDB.init();
        return JSON.parse(localStorage.getItem('bm_courts') || '[]');
    }

    function saveAllCourts(courts) {
        localStorage.setItem('bm_courts', JSON.stringify(courts));
    }

    function getMyCourts(user) {
        const all = getAllCourts();
        if (!user) return [];
        if (user.role === 'admin') return all;
        return all.filter(c => c.owner_phone && user.phone && c.owner_phone.replace(/\s/g, '') === user.phone.replace(/\s/g, ''));
    }

    function getMyCourtIds(user) {
        return getMyCourts(user).map(c => c.id);
    }

    /* ================= BOOKINGS SCOPE ================= */
    function getMyBookings(user) {
        const ids = getMyCourtIds(user);
        return MockDB.getBookings().filter(b => ids.includes(b.court_id));
    }

    function saveBookings(bookings) {
        MockDB.saveBookings(bookings);
    }

    /* ================= SLOTS ================= */
    function slotKey(courtId, dateStr) {
        return `bm_slots_${courtId}_${dateStr}`;
    }

    function getSlots(courtId, dateStr) {
        return JSON.parse(localStorage.getItem(slotKey(courtId, dateStr)) || '[]');
    }

    function saveSlots(courtId, dateStr, slots) {
        localStorage.setItem(slotKey(courtId, dateStr), JSON.stringify(slots));
    }

    // Sinh khung giờ hàng loạt cho 1 sân / 1 ngày.
    // opts: { startTime, endTime, durationMin, price, subCourts: [names] }
    function generateSlots(court, dateStr, opts) {
        const existing = getSlots(court.id, dateStr);
        const [sh, sm] = opts.startTime.split(':').map(Number);
        const [eh, em] = opts.endTime.split(':').map(Number);
        let cursor = sh * 60 + sm;
        const end = eh * 60 + em;
        const created = [];
        let idCounter = existing.reduce((max, s) => Math.max(max, s.id), Date.now() % 100000);

        while (cursor + opts.durationMin <= end) {
            const startStr = minutesToTime(cursor);
            const endStr = minutesToTime(cursor + opts.durationMin);

            opts.subCourts.forEach(subName => {
                const clash = existing.some(s => s.sub_court_name === subName && s.start_time === startStr && s.end_time === endStr);
                if (!clash) {
                    idCounter += 1;
                    created.push({
                        id: idCounter,
                        court_id: court.id,
                        court_name: court.name,
                        sub_court_name: subName,
                        slot_date: dateStr,
                        start_time: startStr,
                        end_time: endStr,
                        time_range: `${startStr} - ${endStr}`,
                        price: opts.price,
                        price_formatted: MockDB.formatVND(opts.price),
                        status: 'available',
                        holding_user_id: null,
                        hold_expire: null
                    });
                }
            });
            cursor += opts.durationMin;
        }

        const merged = existing.concat(created);
        saveSlots(court.id, dateStr, merged);
        return { createdCount: created.length, total: merged.length };
    }

    function minutesToTime(total) {
        const h = String(Math.floor(total / 60)).padStart(2, '0');
        const m = String(total % 60).padStart(2, '0');
        return `${h}:${m}`;
    }

    function setSlotStatus(courtId, dateStr, slotId, status) {
        const slots = getSlots(courtId, dateStr);
        const slot = slots.find(s => s.id === slotId);
        if (!slot) return false;
        if (slot.status === 'booked' && status !== 'booked') {
            // Không cho phép mở lại slot đã có khách đặt & xác nhận từ màn hình khung giờ.
            return false;
        }
        slot.status = status;
        if (status === 'available') { slot.holding_user_id = null; slot.hold_expire = null; }
        saveSlots(courtId, dateStr, slots);
        return true;
    }

    function deleteSlot(courtId, dateStr, slotId) {
        const slots = getSlots(courtId, dateStr).filter(s => s.id !== slotId);
        saveSlots(courtId, dateStr, slots);
    }

    /* ================= STATS ================= */
    function computeStats(user) {
        const courts = getMyCourts(user);
        const bookings = getMyBookings(user);
        const todayStr = new Date().toISOString().split('T')[0];

        const startOfWeek = new Date();
        const day = (startOfWeek.getDay() + 6) % 7; // Monday = 0
        startOfWeek.setDate(startOfWeek.getDate() - day);
        startOfWeek.setHours(0, 0, 0, 0);

        const todayBookings = bookings.filter(b => b.booking_date === todayStr);
        const weekBookings = bookings.filter(b => new Date(b.booking_date) >= startOfWeek);
        const revenue = bookings
            .filter(b => b.booking_status === 'confirmed' || b.booking_status === 'completed')
            .reduce((sum, b) => sum + (b.total_amount || 0), 0);

        return {
            totalCourts: courts.length,
            activeCourts: courts.filter(c => c.status === 'active').length,
            todayCount: todayBookings.length,
            weekCount: weekBookings.length,
            totalBookings: bookings.length,
            pendingCount: bookings.filter(b => b.booking_status === 'confirmed').length,
            cancelledCount: bookings.filter(b => b.booking_status === 'cancelled').length,
            revenue
        };
    }

    /* ================= UI: SIDEBAR / TOPBAR ================= */
    function renderShell(activePage, pageTitle, breadcrumb) {
        const user = requireAuth();
        if (!user) return null;

        const initial = user.name ? user.name.charAt(0) : 'C';
        const roleLabel = user.role === 'admin' ? 'Quản trị viên' : 'Chủ sân';

        const navItems = [
            { key: 'dashboard', href: 'admin-dashboard.html', icon: 'fa-gauge-high', label: 'Tổng quan' },
            { key: 'courts', href: 'admin-courts.html', icon: 'fa-building-shield', label: 'Quản lý sân' },
            { key: 'slots', href: 'admin-slots.html', icon: 'fa-clock', label: 'Quản lý khung giờ' },
            { key: 'bookings', href: 'admin-bookings.html', icon: 'fa-clipboard-list', label: 'Quản lý đơn đặt' },
        ];

        const navHtml = navItems.map(item => `
            <a class="admin-nav-link ${item.key === activePage ? 'active' : ''}" href="${item.href}">
                <i class="fa-solid ${item.icon}"></i> ${item.label}
            </a>`).join('');

        document.getElementById('adminSidebarNav').innerHTML = navHtml;

        document.getElementById('adminUserBox').innerHTML = `
            <div class="dropdown w-100">
                <button class="btn w-100 d-flex align-items-center gap-2 border-0 text-start px-2" type="button" data-bs-toggle="dropdown" style="background:rgba(255,255,255,.04); border-radius:.75rem; color:#e2e8f0;">
                    <div class="avatar-sm rounded-circle bg-teal text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0">${initial}</div>
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold small text-truncate" style="color:#f1f5f9;">${user.name}</div>
                        <div class="text-truncate" style="font-size:.72rem; color:#94a3b8;">${roleLabel}</div>
                    </div>
                    <i class="fa-solid fa-chevron-up small" style="color:#64748b;"></i>
                </button>
                <ul class="dropdown-menu shadow-lg border-0 rounded-4">
                    <li><a class="dropdown-item py-2" href="profile.html"><i class="fa-solid fa-circle-user me-2 text-teal"></i>Trang cá nhân</a></li>
                    <li><a class="dropdown-item py-2" href="index.html"><i class="fa-solid fa-house me-2 text-muted"></i>Về trang khách</a></li>
                    <li><a class="dropdown-item py-2" href="login.html"><i class="fa-solid fa-right-to-bracket me-2 text-primary"></i>Đổi tài khoản</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item py-2 text-danger" href="#" id="adminLogoutBtn"><i class="fa-solid fa-right-from-bracket me-2"></i>Đăng xuất</a></li>
                </ul>
            </div>`;

        document.getElementById('adminLogoutBtn')?.addEventListener('click', (e) => {
            e.preventDefault();
            MockDB.logout();
            window.location.href = 'index.html';
        });

        const titleEl = document.getElementById('adminPageTitle');
        if (titleEl) titleEl.textContent = pageTitle;
        const crumbEl = document.getElementById('adminBreadcrumb');
        if (crumbEl) crumbEl.textContent = breadcrumb || '';

        // Mobile sidebar toggle
        document.getElementById('adminSidebarToggle')?.addEventListener('click', () => {
            document.getElementById('adminSidebar').classList.add('show');
            document.getElementById('adminOverlay').classList.add('show');
        });
        document.getElementById('adminOverlay')?.addEventListener('click', () => {
            document.getElementById('adminSidebar').classList.remove('show');
            document.getElementById('adminOverlay').classList.remove('show');
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
        requireAuth, getAllCourts, saveAllCourts, getMyCourts, getMyCourtIds,
        getMyBookings, saveBookings, getSlots, saveSlots, generateSlots,
        setSlotStatus, deleteSlot, computeStats, renderShell, toast, confirmAction,
        minutesToTime
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
});
