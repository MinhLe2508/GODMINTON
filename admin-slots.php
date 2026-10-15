<?php
require_once __DIR__ . '/includes/auth-check.php';
$currentUser = [
    'id'    => (int)$_SESSION['user_id'],
    'name'  => $_SESSION['name'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'phone' => $_SESSION['phone'] ?? '',
    'role'  => $_SESSION['role'] ?? ''
];
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Khung Giờ | Kênh Chủ Sân - GODMINTON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin_style.css">
    <style>
        .slot-chip { position: relative; }
        .slot-del-btn {
            position: absolute;
            top: 4px;
            right: 6px;
            border: 0;
            background: transparent;
            color: inherit;
            opacity: .65;
            font-size: .78rem;
            line-height: 1;
            padding: 2px 4px;
            border-radius: 4px;
        }
        .slot-del-btn:hover { opacity: 1; background: rgba(0,0,0,.1); }
    </style>
    <script>
        window.__GODMINTON_USER__ = <?php echo json_encode($currentUser, JSON_UNESCAPED_UNICODE); ?>;
    </script>
</head>
<body class="admin-body">

<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-brand">
            <img src="assets/img/logo.svg" alt="GODMINTON">
            <div>
                <div class="fw-bold text-white" style="font-size:.9rem;">GODMINTON</div>
                <div class="brand-sub">Kênh Chủ Sân</div>
            </div>
        </div>
        <nav class="admin-nav">
            <div class="admin-nav-label">Điều hướng</div>
            <div id="adminSidebarNav"></div>
        </nav>
        <div class="admin-sidebar-footer">
            <div id="adminUserBox" class="mb-2"></div>
            <a href="index.php" class="admin-back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại trang khách</a>
        </div>
    </aside>
    <div class="admin-overlay" id="adminOverlay"></div>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="btn btn-outline-secondary admin-sidebar-toggle" id="adminSidebarToggle"><i class="fa-solid fa-bars"></i></button>
            <div class="admin-topbar-title flex-grow-1">
                <div class="admin-breadcrumb" id="adminBreadcrumb">Kênh chủ sân</div>
                <h1 id="adminPageTitle">Quản lý khung giờ</h1>
            </div>
            <button id="themeToggle" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;" title="Chuyển giao diện sáng/tối">
                <i class="fa-solid fa-moon"></i>
            </button>
        </header>

        <main class="admin-content">

            <!-- Bộ chọn sân + ngày -->
            <div class="admin-panel mb-4">
                <div class="admin-panel-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Chọn sân</label>
                            <select id="slotCourtSelect" class="form-select"></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Chọn ngày</label>
                            <input type="date" id="slotDateInput" class="form-control">
                        </div>
                        <div class="col-md-5 text-md-end d-flex flex-wrap justify-content-md-end gap-2">
                            <button class="btn btn-outline-danger rounded-pill px-3 fw-semibold" id="btnClearDaySlots" title="Xóa toàn bộ khung giờ trống/ngừng nhận trong ngày">
                                <i class="fa-regular fa-trash-can me-1"></i> Xóa khung trống
                            </button>
                            <button class="btn btn-teal rounded-pill px-4 fw-bold" data-bs-toggle="modal" data-bs-target="#generateModal">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Tạo khung giờ hàng loạt
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Slot grid -->
            <div class="admin-panel">
                <div class="admin-panel-head">
                    <h2><i class="fa-solid fa-clock text-teal me-2"></i>Khung giờ trong ngày</h2>
                    <div class="d-flex align-items-center gap-3 small flex-wrap">
                        <span><span class="slot-legend-dot" style="background:#16a34a;"></span>Trống</span>
                        <span><span class="slot-legend-dot" style="background:#f59e0b;"></span>Đang giữ chỗ</span>
                        <span><span class="slot-legend-dot" style="background:#dc2626;"></span>Đã đặt</span>
                        <span><span class="slot-legend-dot" style="background:#94a3b8;"></span>Ngừng nhận</span>
                    </div>
                </div>
                <div class="admin-panel-body">
                    <div id="slotGrid" class="row g-2"></div>
                    <div id="slotEmpty" class="text-center text-muted py-5" style="display:none;">
                        <i class="fa-regular fa-calendar-xmark fs-2 d-block mb-2"></i>
                        Chưa có khung giờ nào cho ngày này. Nhấn "Tạo khung giờ hàng loạt" để thiết lập.
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ==================== MODAL: TẠO KHUNG GIỜ HÀNG LOẠT ==================== -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Tạo khung giờ hàng loạt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="generateForm">
                <div class="modal-body">
                    <p class="text-muted small">Áp dụng cho sân và ngày đang chọn ở trên. Hệ thống sẽ tự chia khung giờ theo thời lượng bạn chọn, cho từng sân con đã tick.</p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Giờ bắt đầu</label>
                            <input type="time" class="form-control" id="genStart" value="06:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Giờ kết thúc</label>
                            <input type="time" class="form-control" id="genEnd" value="22:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Thời lượng / khung (phút)</label>
                            <select class="form-select" id="genDuration">
                                <option value="30">30 phút (chuẩn lưới đặt sân)</option>
                                <option value="60" selected>60 phút</option>
                                <option value="90">90 phút</option>
                                <option value="120">120 phút</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Giá / khung (đ)</label>
                            <input type="number" min="0" step="1000" class="form-control" id="genPrice" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small d-block">Áp dụng cho sân con</label>
                            <div id="genSubCourtChecks" class="d-flex flex-wrap gap-3"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold" id="btnSubmitGenerate">Tạo khung giờ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/mock-data.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/admin.js"></script>
<script src="assets/js/bottom-nav.js"></script>
<script>
let currentUser = null;
let myCourts = [];
let currentSlots = [];
let genModal = null;

document.addEventListener('DOMContentLoaded', async () => {
    currentUser = AdminApp.renderShell('slots', 'Quản lý khung giờ', 'Kênh chủ sân / Quản lý khung giờ');
    if (!currentUser) return;

    genModal = new bootstrap.Modal(document.getElementById('generateModal'));

    const dateInput = document.getElementById('slotDateInput');
    const todayStr = new Date().toLocaleDateString('sv-SE');
    dateInput.value = todayStr;

    const select = document.getElementById('slotCourtSelect');
    try {
        const { courts } = await AdminApp.fetchCourts();
        myCourts = courts;
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không tải được danh sách sân.');
        myCourts = [];
    }

    if (myCourts.length === 0) {
        select.innerHTML = '<option value="">Bạn chưa có sân nào</option>';
        select.disabled = true;
    } else {
        select.innerHTML = myCourts.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        const preselect = new URLSearchParams(location.search).get('court');
        if (preselect && myCourts.some(c => Number(c.id) === Number(preselect))) {
            select.value = preselect;
        }
    }

    select.addEventListener('change', () => {
        renderSubCourtChecks();
        renderGrid();
    });
    dateInput.addEventListener('change', renderGrid);
    document.getElementById('genDuration').addEventListener('change', updateSuggestedPrice);
    document.getElementById('generateForm').addEventListener('submit', handleGenerate);
    document.getElementById('btnClearDaySlots').addEventListener('click', handleClearDaySlots);

    renderSubCourtChecks();
    await renderGrid();
});

function getSelectedCourt() {
    const id = Number(document.getElementById('slotCourtSelect').value);
    return myCourts.find(c => Number(c.id) === id);
}

function updateSuggestedPrice() {
    const court = getSelectedCourt();
    if (!court) return;
    const dur = Number(document.getElementById('genDuration').value) || 60;
    document.getElementById('genPrice').value = Math.round((Number(court.price_per_hour) || 80000) * (dur / 60));
}

function renderSubCourtChecks() {
    const court = getSelectedCourt();
    const wrap = document.getElementById('genSubCourtChecks');
    if (!court) { wrap.innerHTML = ''; return; }

    const count = Number(court.total_sub_courts) || 4;
    const names = Array.from({ length: count }, (_, i) => `Thảm A${i + 1}`);
    wrap.innerHTML = names.map((n, i) => `
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="${n}" id="sub_${i}" checked>
            <label class="form-check-label small" for="sub_${i}">${n}</label>
        </div>`).join('');

    if (court.opening_time) document.getElementById('genStart').value = court.opening_time;
    if (court.closing_time) document.getElementById('genEnd').value = court.closing_time;
    updateSuggestedPrice();
}

async function renderGrid() {
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    const grid = document.getElementById('slotGrid');
    const empty = document.getElementById('slotEmpty');
    if (!court || !dateStr) {
        grid.innerHTML = '';
        empty.style.display = 'block';
        return;
    }

    try {
        currentSlots = await AdminApp.fetchSlots(court.id, dateStr);
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không tải được danh sách khung giờ.');
        currentSlots = [];
    }

    const slots = currentSlots.slice().sort((a, b) =>
        (a.sub_court_name + a.start_time).localeCompare(b.sub_court_name + b.start_time, 'vi', { numeric: true })
    );

    empty.style.display = slots.length ? 'none' : 'block';

    grid.innerHTML = slots.map(s => {
        const statusClass = s.status === 'available' ? 'is-available'
            : s.status === 'holding' ? 'is-holding'
            : s.status === 'booked' ? 'is-booked'
            : 'is-blocked';
        const statusText = s.status === 'available' ? 'Trống'
            : s.status === 'holding' ? 'Đang giữ'
            : s.status === 'booked' ? 'Đã đặt'
            : 'Ngừng nhận';
        const canDelete = s.status === 'available' || s.status === 'blocked';
        const delBtn = canDelete
            ? `<button type="button" class="slot-del-btn" onclick="handleDeleteSlot(event, ${s.id}, '${s.sub_court_name}', '${s.start_time}-${s.end_time}')" title="Xóa khung giờ"><i class="fa-solid fa-xmark"></i></button>`
            : '';

        return `
        <div class="col-6 col-md-3 col-lg-2">
            <div class="slot-chip ${statusClass}" onclick="handleSlotClick(${court.id}, '${dateStr}', ${s.id}, '${s.status}')" title="Nhấn để đổi trạng thái Trống / Ngừng nhận">
                ${delBtn}
                ${s.sub_court_name}<br>${s.start_time}-${s.end_time}
                <small>${statusText} · ${AdminApp.formatVND(s.price)}</small>
            </div>
        </div>`;
    }).join('');
}

async function handleGenerate(e) {
    e.preventDefault();
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    if (!court || !dateStr) return;

    const subCourts = Array.from(document.querySelectorAll('#genSubCourtChecks input:checked')).map(c => c.value);
    if (subCourts.length === 0) {
        AdminApp.toast('warning', 'Vui lòng chọn ít nhất 1 sân con');
        return;
    }

    const opts = {
        startTime: document.getElementById('genStart').value,
        endTime: document.getElementById('genEnd').value,
        durationMin: Number(document.getElementById('genDuration').value),
        price: Number(document.getElementById('genPrice').value),
        subCourts
    };

    if (opts.startTime >= opts.endTime) {
        AdminApp.toast('warning', 'Giờ kết thúc phải sau giờ bắt đầu');
        return;
    }

    const btn = document.getElementById('btnSubmitGenerate');
    btn.disabled = true;
    try {
        const res = await AdminApp.generateSlotsApi(court.id, dateStr, opts);
        genModal.hide();
        await renderGrid();
        AdminApp.toast('success', res.message || `Đã tạo thêm ${res.data?.createdCount ?? 0} khung giờ mới`);
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể tạo khung giờ hàng loạt.');
    } finally {
        btn.disabled = false;
    }
}

async function handleSlotClick(courtId, dateStr, slotId, status) {
    if (status === 'booked') {
        AdminApp.toast('info', 'Khung giờ đã có khách đặt — xử lý ở mục "Quản lý đơn đặt"');
        return;
    }
    if (status === 'holding') {
        AdminApp.toast('info', 'Khung giờ đang được khách giữ chỗ tạm thời');
        return;
    }

    try {
        if (status === 'available') {
            const ok = await AdminApp.confirmAction(
                'Ngừng nhận đặt khung giờ này?',
                'Khách sẽ không thể chọn khung giờ này để đặt sân nữa.'
            );
            if (!ok) return;
            await AdminApp.setSlotStatusApi(slotId, 'blocked');
            AdminApp.toast('success', 'Đã tạm ngừng nhận đặt khung giờ');
        } else if (status === 'blocked') {
            await AdminApp.setSlotStatusApi(slotId, 'available');
            AdminApp.toast('success', 'Đã mở lại khung giờ cho khách đặt');
        }
        await renderGrid();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể cập nhật trạng thái khung giờ.');
    }
}

async function handleDeleteSlot(e, slotId, subName, timeRange) {
    e.stopPropagation();
    const ok = await AdminApp.confirmAction(
        'Xóa khung giờ này?',
        `Khung giờ ${subName} (${timeRange}) sẽ bị xóa khỏi lịch ngày đang chọn.`
    );
    if (!ok) return;

    try {
        await AdminApp.deleteSlotApi(slotId);
        AdminApp.toast('success', 'Đã xóa khung giờ');
        await renderGrid();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể xóa khung giờ.');
    }
}

async function handleClearDaySlots() {
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    if (!court || !dateStr) return;

    const ok = await AdminApp.confirmAction(
        'Xóa các khung giờ trống trong ngày?',
        `Tất cả khung giờ chưa có khách đặt của "${court.name}" ngày ${dateStr} sẽ bị xóa (các khung giờ đã đặt được giữ nguyên).`
    );
    if (!ok) return;

    try {
        const res = await AdminApp.clearDaySlotsApi(court.id, dateStr);
        AdminApp.toast('success', res.message || 'Đã xóa các khung giờ trống.');
        await renderGrid();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể xóa khung giờ.');
    }
}
</script>
</body>
</html>
