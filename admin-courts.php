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
    <title>Quản Lý Sân | Kênh Chủ Sân - GODMINTON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin_style.css">
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
                <h1 id="adminPageTitle">Quản lý sân</h1>
            </div>
            <button id="themeToggle" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;" title="Chuyển giao diện sáng/tối">
                <i class="fa-solid fa-moon"></i>
            </button>
            <button class="btn btn-teal rounded-pill px-3 fw-semibold" id="btnAddCourt">
                <i class="fa-solid fa-plus me-1"></i> Thêm sân
            </button>
        </header>

        <main class="admin-content">
            <div class="admin-panel">
                <div class="admin-panel-head">
                    <h2 id="panelCourtsTitle"><i class="fa-solid fa-building-shield text-teal me-2"></i>Danh sách sân do tôi sở hữu</h2>
                    <div class="d-flex align-items-center gap-2">
                        <input type="search" id="searchCourt" class="form-control form-control-sm rounded-pill" placeholder="Tìm theo tên / khu vực..." style="min-width:220px;">
                    </div>
                </div>
                <div class="admin-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Sân</th>
                                    <th>Khu vực</th>
                                    <th>Giá / giờ</th>
                                    <th>Số sân con</th>
                                    <th>Giờ mở cửa</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="courtsTableBody"></tbody>
                        </table>
                    </div>
                    <div id="courtsEmpty" class="text-center text-muted py-5" style="display:none;">
                        <i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i>
                        Bạn chưa có sân nào. Nhấn "Thêm sân" để bắt đầu.
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ==================== MODAL: THÊM / SỬA SÂN ==================== -->
<div class="modal fade" id="courtModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="courtModalTitle">Thêm sân mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="courtForm">
                <div class="modal-body">
                    <input type="hidden" id="courtId">
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold small">Tên sân</label>
                            <input type="text" class="form-control" id="courtName" required placeholder="VD: Cầu Lông Quận 1 – Lê Thánh Tôn Sport">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Phường / Khu vực</label>
                            <select class="form-select" id="courtWardId" required></select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Địa chỉ chi tiết</label>
                            <input type="text" class="form-control" id="courtAddress" required placeholder="Số nhà, đường, phường...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Giá / giờ (đ)</label>
                            <input type="number" min="0" step="1000" class="form-control" id="courtPrice" required placeholder="80000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Số sân con</label>
                            <input type="number" min="1" step="1" class="form-control" id="courtSubCount" value="6" required placeholder="6">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Trạng thái</label>
                            <select class="form-select" id="courtStatus">
                                <option value="active">Hoạt động</option>
                                <option value="inactive">Tạm ngưng</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Giờ mở cửa</label>
                            <input type="time" class="form-control" id="courtOpen" value="06:00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Giờ đóng cửa</label>
                            <input type="time" class="form-control" id="courtClose" value="22:00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Vĩ độ (Latitude)</label>
                            <input type="number" step="0.0000001" class="form-control" id="courtLat" value="10.7769000" placeholder="10.7769000">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Kinh độ (Longitude)</label>
                            <input type="number" step="0.0000001" class="form-control" id="courtLng" value="106.7009000" placeholder="106.7009000">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Ảnh đại diện (URL)</label>
                            <input type="url" class="form-control" id="courtImage" placeholder="https://...">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Mô tả</label>
                            <textarea class="form-control" id="courtDesc" rows="3" placeholder="Mô tả ngắn về sân, tiện ích nổi bật..."></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small d-block">Tiện ích</label>
                            <div class="d-flex flex-wrap gap-3" id="amenityChecks">
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="wifi" id="am_wifi"><label class="form-check-label small" for="am_wifi">Wi-Fi</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="parking" id="am_parking"><label class="form-check-label small" for="am_parking">Bãi đỗ xe</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="shower" id="am_shower"><label class="form-check-label small" for="am_shower">Phòng tắm</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="aircon" id="am_aircon"><label class="form-check-label small" for="am_aircon">Điều hòa</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="cafe" id="am_cafe"><label class="form-check-label small" for="am_cafe">Canteen/Cafe</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="rental" id="am_rental"><label class="form-check-label small" for="am_rental">Cho thuê vợt</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="locker" id="am_locker"><label class="form-check-label small" for="am_locker">Tủ khóa</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="indoor" id="am_indoor"><label class="form-check-label small" for="am_indoor">Sân trong nhà</label></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold" id="btnSaveCourt">Lưu sân</button>
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
let modalInstance = null;
let myCourts = [];
let wardsList = [];

document.addEventListener('DOMContentLoaded', async () => {
    currentUser = AdminApp.renderShell('courts', 'Quản lý sân', 'Kênh chủ sân / Quản lý sân');
    if (!currentUser) return;

    if (currentUser.role === 'admin') {
        const titleEl = document.getElementById('panelCourtsTitle');
        if (titleEl) {
            titleEl.innerHTML = '<i class="fa-solid fa-building-shield text-teal me-2"></i>Danh sách toàn bộ sân (Quyền Admin)';
        }
    }

    modalInstance = new bootstrap.Modal(document.getElementById('courtModal'));

    document.getElementById('btnAddCourt').addEventListener('click', () => openCourtModal(null));
    document.getElementById('searchCourt').addEventListener('input', () => renderCourtsTable());
    document.getElementById('courtForm').addEventListener('submit', handleSaveCourt);

    await loadCourtsFromServer();
});

async function loadCourtsFromServer() {
    try {
        const { courts, wards } = await AdminApp.fetchCourts();
        myCourts = courts;
        wardsList = wards;
        populateWardSelect();
        renderCourtsTable();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không tải được danh sách sân.');
    }
}

function populateWardSelect() {
    const sel = document.getElementById('courtWardId');
    if (!sel) return;
    if (!wardsList.length) {
        sel.innerHTML = '<option value="8">Bến Nghé — Quận 1 (cũ)</option>';
        return;
    }
    sel.innerHTML = wardsList.map(w => {
        const label = w.old_area_label ? `${w.name} — ${w.old_area_label}` : w.name;
        return `<option value="${w.id}">${label}</option>`;
    }).join('');
}

function renderCourtsTable() {
    const q = (document.getElementById('searchCourt').value || '').toLowerCase().trim();
    let list = myCourts.slice();
    if (q) {
        list = list.filter(c =>
            (c.name || '').toLowerCase().includes(q) ||
            (c.district || '').toLowerCase().includes(q) ||
            (c.ward_name || '').toLowerCase().includes(q) ||
            (c.address || '').toLowerCase().includes(q)
        );
    }

    const body = document.getElementById('courtsTableBody');
    document.getElementById('courtsEmpty').style.display = list.length ? 'none' : 'block';

    body.innerHTML = list.map(c => {
        const areaText = c.old_area_label ? `${c.ward_name} (${c.old_area_label})` : (c.district || c.ward_name || '');
        const ownerSub = (currentUser.role === 'admin' && c.owner_name)
            ? `<div class="small text-teal"><i class="fa-regular fa-user me-1"></i>Chủ sân: ${c.owner_name}</div>`
            : '';
        return `
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${c.image_url || 'assets/img/logo.jpg'}" class="admin-court-thumb" alt="">
                    <div class="min-w-0">
                        <div class="admin-table-court-name text-truncate" style="max-width:240px;">${c.name}</div>
                        <div class="small text-muted text-truncate" style="max-width:240px;">${c.address}</div>
                        ${ownerSub}
                    </div>
                </div>
            </td>
            <td>${areaText}</td>
            <td>${AdminApp.formatVND(c.price_per_hour)}</td>
            <td>${c.total_sub_courts || 6}</td>
            <td>${c.opening_time || '06:00'} - ${c.closing_time || '22:00'}</td>
            <td><span class="badge ${c.status === 'active' ? 'bg-success' : 'bg-secondary'}">${c.status === 'active' ? 'Hoạt động' : 'Tạm ngưng'}</span></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-teal rounded-pill me-1" onclick="openCourtModal(${c.id})" title="Chỉnh sửa sân"><i class="fa-solid fa-pen"></i></button>
                <a class="btn btn-sm btn-outline-secondary rounded-pill me-1" href="admin-slots.php?court=${c.id}" title="Quản lý khung giờ"><i class="fa-solid fa-clock"></i></a>
                <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="deleteCourt(${c.id})" title="Xóa sân"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>`;
    }).join('');
}

function openCourtModal(courtId) {
    const form = document.getElementById('courtForm');
    form.reset();
    document.querySelectorAll('#amenityChecks input').forEach(chk => chk.checked = false);

    if (courtId) {
        const court = myCourts.find(c => Number(c.id) === Number(courtId));
        if (!court) return;
        document.getElementById('courtModalTitle').textContent = 'Chỉnh sửa sân';
        document.getElementById('courtId').value = court.id;
        document.getElementById('courtName').value = court.name || '';
        if (court.ward_id) {
            document.getElementById('courtWardId').value = String(court.ward_id);
        }
        document.getElementById('courtAddress').value = court.address || '';
        document.getElementById('courtPrice').value = court.price_per_hour || 0;
        document.getElementById('courtSubCount').value = court.total_sub_courts || 6;
        document.getElementById('courtStatus').value = court.status || 'active';
        document.getElementById('courtOpen').value = court.opening_time || '06:00';
        document.getElementById('courtClose').value = court.closing_time || '22:00';
        document.getElementById('courtLat').value = court.latitude ?? 10.7769000;
        document.getElementById('courtLng').value = court.longitude ?? 106.7009000;
        document.getElementById('courtImage').value = court.image_url || '';
        document.getElementById('courtDesc').value = court.description || '';
        (court.amenities || []).forEach(a => {
            const code = a === 'canteen' ? 'cafe' : a;
            const chk = document.getElementById('am_' + code);
            if (chk) chk.checked = true;
        });
    } else {
        document.getElementById('courtModalTitle').textContent = 'Thêm sân mới';
        document.getElementById('courtId').value = '';
        document.getElementById('courtSubCount').value = 6;
        document.getElementById('courtOpen').value = '06:00';
        document.getElementById('courtClose').value = '22:00';
        document.getElementById('courtLat').value = '10.7769000';
        document.getElementById('courtLng').value = '106.7009000';
    }
    modalInstance.show();
}

async function handleSaveCourt(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveCourt');
    const id = document.getElementById('courtId').value;
    const price = Number(document.getElementById('courtPrice').value);
    const amenities = Array.from(document.querySelectorAll('#amenityChecks input:checked')).map(c => c.value);

    const payload = {
        name: document.getElementById('courtName').value.trim(),
        ward_id: Number(document.getElementById('courtWardId').value),
        address: document.getElementById('courtAddress').value.trim(),
        price_per_hour: price,
        total_sub_courts: Number(document.getElementById('courtSubCount').value) || 6,
        status: document.getElementById('courtStatus').value,
        opening_time: document.getElementById('courtOpen').value,
        closing_time: document.getElementById('courtClose').value,
        latitude: parseFloat(document.getElementById('courtLat').value) || 10.7769000,
        longitude: parseFloat(document.getElementById('courtLng').value) || 106.7009000,
        image_url: document.getElementById('courtImage').value.trim() || 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop',
        description: document.getElementById('courtDesc').value.trim(),
        amenities
    };

    btn.disabled = true;
    try {
        if (id) {
            await AdminApp.updateCourt(id, payload);
            AdminApp.toast('success', 'Đã cập nhật thông tin sân');
        } else {
            await AdminApp.createCourt(payload);
            AdminApp.toast('success', 'Đã thêm sân mới');
        }
        modalInstance.hide();
        await loadCourtsFromServer();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Lỗi khi lưu thông tin sân.');
    } finally {
        btn.disabled = false;
    }
}

async function deleteCourt(courtId) {
    const court = myCourts.find(c => Number(c.id) === Number(courtId));
    if (!court) return;
    const ok = await AdminApp.confirmAction(
        'Xóa sân này?',
        `"${court.name}" sẽ bị xóa khỏi hệ thống. Khung giờ đã tạo cho sân này sẽ không còn hiển thị cho khách.`
    );
    if (!ok) return;

    try {
        await AdminApp.deleteCourtApi(courtId);
        AdminApp.toast('success', 'Đã xóa sân');
        await loadCourtsFromServer();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể xóa sân.');
    }
}
</script>
</body>
</html>
