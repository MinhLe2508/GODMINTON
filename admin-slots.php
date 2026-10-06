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
            <a href="index.html" class="admin-back-link"><i class="fa-solid fa-arrow-left"></i> Quay lại trang khách</a>
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
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Chọn sân</label>
                            <select id="slotCourtSelect" class="form-select"></select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Chọn ngày</label>
                            <input type="date" id="slotDateInput" class="form-control">
                        </div>
                        <div class="col-md-4 text-md-end">
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
                    <div class="d-flex align-items-center gap-3 small">
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
                                <option value="60">60 phút</option>
                                <option value="90" selected>90 phút</option>
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
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">Tạo khung giờ</button>
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
let genModal = null;

document.addEventListener('DOMContentLoaded', () => {
    currentUser = AdminApp.renderShell('slots', 'Quản lý khung giờ', 'Kênh chủ sân / Quản lý khung giờ');
    if (!currentUser) return;

    genModal = new bootstrap.Modal(document.getElementById('generateModal'));
    myCourts = AdminApp.getMyCourts(currentUser);

    const select = document.getElementById('slotCourtSelect');
    if (myCourts.length === 0) {
        select.innerHTML = '<option>Bạn chưa có sân nào</option>';
        select.disabled = true;
    } else {
        select.innerHTML = myCourts.map(c => `<option value="${c.id}">${c.name}</option>`).join('');
        const preselect = new URLSearchParams(location.search).get('court');
        if (preselect && myCourts.some(c => c.id === Number(preselect))) select.value = preselect;
    }

    const dateInput = document.getElementById('slotDateInput');
    dateInput.value = new Date().toISOString().split('T')[0];
    dateInput.min = new Date().toISOString().split('T')[0];

    select.addEventListener('change', () => { renderSubCourtChecks(); renderGrid(); });
    dateInput.addEventListener('change', renderGrid);
    document.getElementById('generateForm').addEventListener('submit', handleGenerate);

    renderSubCourtChecks();
    renderGrid();
});

function getSelectedCourt() {
    const id = Number(document.getElementById('slotCourtSelect').value);
    return myCourts.find(c => c.id === id);
}

function renderSubCourtChecks() {
    const court = getSelectedCourt();
    const wrap = document.getElementById('genSubCourtChecks');
    if (!court) { wrap.innerHTML = ''; return; }
    const names = Array.from({ length: court.total_sub_courts || 1 }, (_, i) => `Sân ${i + 1}`);
    wrap.innerHTML = names.map((n, i) => `
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="${n}" id="sub_${i}" checked>
            <label class="form-check-label small" for="sub_${i}">${n}</label>
        </div>`).join('');
    document.getElementById('genPrice').value = court.price_per_hour || '';
}

function renderGrid() {
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    const grid = document.getElementById('slotGrid');
    const empty = document.getElementById('slotEmpty');
    if (!court || !dateStr) { grid.innerHTML = ''; empty.style.display = 'block'; return; }

    const slots = AdminApp.getSlots(court.id, dateStr)
        .slice()
        .sort((a, b) => (a.sub_court_name + a.start_time).localeCompare(b.sub_court_name + b.start_time));

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
        return `
        <div class="col-6 col-md-3 col-lg-2">
            <div class="slot-chip ${statusClass}" onclick="handleSlotClick(${court.id}, '${dateStr}', ${s.id}, '${s.status}')" title="Nhấn để đổi trạng thái">
                ${s.sub_court_name}<br>${s.start_time}-${s.end_time}
                <small>${statusText}</small>
            </div>
        </div>`;
    }).join('');
}

function handleGenerate(e) {
    e.preventDefault();
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    if (!court || !dateStr) return;

    const subCourts = Array.from(document.querySelectorAll('#genSubCourtChecks input:checked')).map(c => c.value);
    if (subCourts.length === 0) { AdminApp.toast('warning', 'Vui lòng chọn ít nhất 1 sân con'); return; }

    const opts = {
        startTime: document.getElementById('genStart').value,
        endTime: document.getElementById('genEnd').value,
        durationMin: Number(document.getElementById('genDuration').value),
        price: Number(document.getElementById('genPrice').value),
        subCourts
    };

    if (opts.startTime >= opts.endTime) { AdminApp.toast('warning', 'Giờ kết thúc phải sau giờ bắt đầu'); return; }

    const result = AdminApp.generateSlots(court, dateStr, opts);
    genModal.hide();
    renderGrid();
    AdminApp.toast('success', `Đã tạo thêm ${result.createdCount} khung giờ mới`);
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

    if (status === 'available') {
        const ok = await AdminApp.confirmAction('Ngừng nhận đặt khung giờ này?', 'Khách sẽ không thể chọn khung giờ này để đặt sân nữa.');
        if (!ok) return;
        AdminApp.setSlotStatus(courtId, dateStr, slotId, 'blocked');
        AdminApp.toast('success', 'Đã tạm ngừng nhận đặt khung giờ');
    } else if (status === 'blocked') {
        AdminApp.setSlotStatus(courtId, dateStr, slotId, 'available');
        AdminApp.toast('success', 'Đã mở lại khung giờ cho khách đặt');
    }
    renderGrid();
}
</script>
</body>
</html>
