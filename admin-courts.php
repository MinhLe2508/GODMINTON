<?php
require_once __DIR__ . '/includes/auth-check.php';
header('Cache-Control: no-store, max-age=0');
$currentUser = ['id' => (int)$_SESSION['user_id'], 'name' => $_SESSION['name'] ?? '', 'role' => $_SESSION['role'] ?? '', 'phone' => $_SESSION['phone'] ?? ''];
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Sân | Kênh Chủ Sân - GODMINTON</title>
<?php include_once __DIR__ . '/includes/partials/pwa-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(__DIR__ . '/assets/css/swal-godminton.css') ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin_style.css?v=<?= filemtime(__DIR__ . '/assets/css/admin_style.css') ?>">
    <script>
        window.__GODMINTON_USER__ = <?php echo json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
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
            <div class="admin-topbar-actions">
                <button id="themeToggle" type="button" class="admin-icon-btn" aria-label="Chuyển giao diện sáng/tối" title="Chuyển giao diện sáng/tối">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button class="btn btn-teal rounded-pill px-3 fw-semibold ms-1" id="btnAddCourt" title="Thêm sân">
                    <i class="fa-solid fa-plus me-1"></i><span class="btn-label-text">Thêm sân</span>
                </button>
            </div>
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
                        Bạn chưa có sân nào. Gửi hồ sơ đăng ký sân để bắt đầu.
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
                        <div class="col-12" id="courtOwnerWrap" style="display: none;">
                            <label class="form-label fw-semibold small text-teal"><i class="fa-solid fa-user-tie me-1"></i>Chủ sân phụ trách (Admin phân quyền)</label>
                            <select class="form-select border-teal" id="courtOwnerId"></select>
                            <div class="form-text" style="font-size:0.75rem;">Chỉ định tài khoản Chủ sân của quận/huyện tương ứng để quản lý sân này.</div>
                        </div>
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
                        <div class="col-md-6">
                            <label for="courtLatitude" class="form-label fw-semibold small">Vĩ độ (Latitude)</label>
                            <input type="number" step="0.0000001" min="-90" max="90" class="form-control" id="courtLatitude" placeholder="VD: 10.7769000">
                            <div class="form-text text-muted" style="font-size:0.75rem;">Phạm vi đô thị TP.HCM: 10.3500000 đến 11.2000000</div>
                            <div class="invalid-feedback" id="courtLatError">Vĩ độ không hợp lệ hoặc nằm ngoài khu vực đô thị (10.35 – 11.20).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="courtLongitude" class="form-label fw-semibold small">Kinh độ (Longitude)</label>
                            <input type="number" step="0.0000001" min="-180" max="180" class="form-control" id="courtLongitude" placeholder="VD: 106.7009000">
                            <div class="form-text text-muted" style="font-size:0.75rem;">Phạm vi đô thị TP.HCM: 106.3000000 đến 107.0500000</div>
                            <div class="invalid-feedback" id="courtLngError">Kinh độ không hợp lệ hoặc nằm ngoài khu vực đô thị (106.30 – 107.05).</div>
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

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Ảnh đại diện sân (Tải ảnh lên hoặc nhập URL)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="courtImage" placeholder="https://... hoặc bấm Chọn ảnh để tải lên">
                                <label class="btn btn-outline-teal fw-semibold mb-0 d-inline-flex align-items-center" for="courtImageFile" id="courtImageUploadLabel" style="cursor:pointer;">
                                    <i class="fa-solid fa-cloud-arrow-up me-1"></i>Chọn ảnh
                                </label>
                                <input type="file" id="courtImageFile" class="d-none" accept="image/jpeg,image/png,image/webp">
                            </div>
                            <div class="form-text small">Hỗ trợ JPG, PNG, WEBP (tối đa 5MB) hoặc dán đường dẫn ảnh trực tiếp.</div>
                            <div id="courtImagePreviewWrap" class="mt-2 d-none">
                                <img id="courtImagePreview" src="" alt="Xem trước ảnh sân" class="rounded-3 border object-fit-cover" style="height:96px;width:160px;">
                            </div>
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
<script src="assets/js/swal-godminton.js?v=<?= filemtime(__DIR__ . '/assets/js/swal-godminton.js') ?>"></script>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script src="assets/js/admin.js?v=<?= filemtime(__DIR__ . '/assets/js/admin.js') ?>"></script>
<script>
let currentUser = null;
let modalInstance = null;
let myCourts = [];
let wardsList = [];
let ownersList = [];

document.addEventListener('DOMContentLoaded', async () => {
    currentUser = AdminApp.renderShell('courts', 'Quản lý sân', 'Kênh chủ sân / Quản lý sân');
    if (!currentUser) return;

    if (currentUser.role === 'admin') {
        const titleEl = document.getElementById('panelCourtsTitle');
        if (titleEl) {
            titleEl.innerHTML = '<i class="fa-solid fa-building-shield text-teal me-2"></i>Danh sách toàn bộ sân (Quyền Admin)';
        }
    }

    modalInstance = new bootstrap.Modal(document.getElementById('courtModal'), { focus: false });

    document.getElementById('btnAddCourt').addEventListener('click', () => {location.href=currentUser.role==='admin'?'admin-owner-requests.php':'owner-application.php';});
    document.getElementById('searchCourt').addEventListener('input', () => renderCourtsTable());
    document.getElementById('courtForm').addEventListener('submit', handleSaveCourt);
    document.getElementById('courtImage').addEventListener('input', (e) => updateCourtImagePreview(e.target.value));
    document.getElementById('courtImageFile').addEventListener('change', handleUploadCourtImage);

    await loadCourtsFromServer();
    const editId=Number(new URLSearchParams(location.search).get('edit'));if(editId && myCourts.some(c=>Number(c.id)===editId))openCourtModal(editId);
});

async function loadCourtsFromServer() {
    try {
        const { courts, wards, owners } = await AdminApp.fetchCourts();
        myCourts = courts;
        wardsList = wards;
        ownersList = owners || [];
        populateWardSelect();
        populateOwnerSelect();
        renderCourtsTable();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không tải được danh sách sân.');
    }
}

function populateOwnerSelect() {
    const sel = document.getElementById('courtOwnerId');
    const wrap = document.getElementById('courtOwnerWrap');
    if (!sel || !wrap) return;
    if (currentUser.role !== 'admin' || !ownersList.length) {
        wrap.style.display = 'none';
        return;
    }
    wrap.style.display = 'block';
    sel.innerHTML = ownersList.map(o => `<option value="${o.id}">${AdminApp.escapeHTML(o.name)} (${AdminApp.escapeHTML(o.email)})</option>`).join('');
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
        return `<option value="${w.id}">${AdminApp.escapeHTML(label)}</option>`;
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
            ? `<div class="small text-teal"><i class="fa-regular fa-user me-1"></i>Chủ sân: ${AdminApp.escapeHTML(c.owner_name)}</div>`
            : '';
        const isActive = c.status === 'active';
        return `
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${AdminApp.escapeHTML(c.image_url || 'assets/img/logo.jpg')}" class="admin-court-thumb" alt="">
                    <div class="min-w-0">
                        <div class="admin-table-court-name text-truncate" style="max-width:240px;">${AdminApp.escapeHTML(c.name)}</div>
                        <div class="small text-muted text-truncate" style="max-width:240px;">${AdminApp.escapeHTML(c.address)}</div>
                        ${ownerSub}
                    </div>
                </div>
            </td>
            <td>${AdminApp.escapeHTML(areaText)}</td>
            <td>${AdminApp.formatVND(c.price_per_hour)}</td>
            <td>${c.total_sub_courts || 6}</td>
            <td>${c.opening_time || '06:00'} - ${c.closing_time || '22:00'}</td>
            <td><span class="badge ${isActive ? 'bg-success' : 'bg-secondary'}">${isActive ? 'Hoạt động' : 'Tạm ngưng'}</span></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-teal rounded-pill me-1" onclick="openCourtModal(${c.id})" title="Chỉnh sửa sân"><i class="fa-solid fa-pen"></i></button>
                <a class="btn btn-sm btn-outline-secondary rounded-pill me-1" href="admin-slots.php?court=${c.id}" title="Quản lý khung giờ"><i class="fa-solid fa-clock"></i></a>
                <button class="btn btn-sm ${isActive ? 'btn-outline-danger' : 'btn-outline-success'} rounded-pill" onclick="toggleCourtStatus(${c.id}, '${isActive ? 'inactive' : 'active'}')" title="${isActive ? 'Tạm ngưng nhận đặt' : 'Mở hoạt động lại'}">
                    <i class="fa-solid ${isActive ? 'fa-power-off' : 'fa-play'}"></i>
                </button>
            </td>
        </tr>`;
    }).join('');
}

function openCourtModal(courtId) {
    const form = document.getElementById('courtForm');
    form.reset();
    document.querySelectorAll('#amenityChecks input').forEach(chk => chk.checked = false);

    document.getElementById('courtLatitude').classList.remove('is-invalid');
    document.getElementById('courtLongitude').classList.remove('is-invalid');

    if (courtId) {
        const court = myCourts.find(c => Number(c.id) === Number(courtId));
        if (!court) return;
        document.getElementById('courtModalTitle').textContent = 'Chỉnh sửa sân';
        document.getElementById('courtId').value = court.id;
        document.getElementById('courtName').value = court.name || '';
        if (court.ward_id) {
            document.getElementById('courtWardId').value = String(court.ward_id);
        }
        if (currentUser.role === 'admin' && document.getElementById('courtOwnerId')) {
            document.getElementById('courtOwnerId').value = String(court.owner_id || '');
        }
        document.getElementById('courtAddress').value = court.address || '';
        document.getElementById('courtPrice').value = court.price_per_hour || 0;
        document.getElementById('courtSubCount').value = court.total_sub_courts || 6;
        document.getElementById('courtStatus').value = court.status || 'active';
        document.getElementById('courtOpen').value = court.opening_time || '06:00';
        document.getElementById('courtClose').value = court.closing_time || '22:00';
        document.getElementById('courtImage').value = court.image_url || '';
        updateCourtImagePreview(court.image_url || '');
        document.getElementById('courtDesc').value = court.description || '';
        document.getElementById('courtLatitude').value = (court.latitude !== undefined && court.latitude !== null && court.latitude !== 0) ? court.latitude : '';
        document.getElementById('courtLongitude').value = (court.longitude !== undefined && court.longitude !== null && court.longitude !== 0) ? court.longitude : '';

        (court.amenities || []).forEach(a => {
            const code = a === 'canteen' ? 'cafe' : a;
            const chk = document.getElementById('am_' + code);
            if (chk) chk.checked = true;
        });
    } else {
        document.getElementById('courtModalTitle').textContent = 'Thêm sân mới';
        document.getElementById('courtId').value = '';
        document.getElementById('courtStatus').value = 'active';
        updateCourtImagePreview('');
    }
    modalInstance.show();
}

function updateCourtImagePreview(url) {
    const wrap = document.getElementById('courtImagePreviewWrap');
    const img = document.getElementById('courtImagePreview');
    if (!wrap || !img) return;
    const clean = String(url || '').trim();
    if (clean) {
        img.src = clean;
        wrap.classList.remove('d-none');
    } else {
        img.removeAttribute('src');
        wrap.classList.add('d-none');
    }
}

async function handleUploadCourtImage(e) {
    const file = e.target.files?.[0];
    if (!file) return;
    const label = document.getElementById('courtImageUploadLabel');
    const oldHtml = label ? label.innerHTML : '';
    if (label) {
        label.classList.add('disabled');
        label.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Đang tải...';
    }
    try {
        const fd = new FormData();
        fd.append('image', file);
        const res = await fetch('api/upload-image.php?type=court', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-GODMINTON-CSRF': window.__GODMINTON_ADMIN__?.csrf_token || ''
            },
            body: fd
        });
        const json = await res.json();
        if (!res.ok || json.status !== 'success') {
            throw new Error(json.message || 'Không thể tải ảnh lên.');
        }
        const url = json.data?.url || '';
        document.getElementById('courtImage').value = url;
        updateCourtImagePreview(url);
        AdminApp.toast('success', 'Đã tải ảnh đại diện sân lên thành công.');
    } catch (err) {
        AdminApp.toast('error', err.message || 'Lỗi tải ảnh lên.');
    } finally {
        e.target.value = '';
        if (label) {
            label.classList.remove('disabled');
            label.innerHTML = oldHtml;
        }
    }
}

async function handleSaveCourt(e) {
    e.preventDefault();
    const btn = document.getElementById('btnSaveCourt');
    const id = document.getElementById('courtId').value;
    const price = Number(document.getElementById('courtPrice').value);
    const amenities = Array.from(document.querySelectorAll('#amenityChecks input:checked')).map(c => c.value);

    if (btn.disabled) return;
    const latitude = Number(document.getElementById('courtLatitude').value);
    const longitude = Number(document.getElementById('courtLongitude').value);
    if (!Number.isFinite(latitude) || !Number.isFinite(longitude) || latitude < 10.35 || latitude > 11.2 || longitude < 106.3 || longitude > 107.05) {
        AdminApp.toast('error', 'Vui lòng nhập tọa độ hợp lệ trong phạm vi TP.HCM.');
        return;
    }

    const payload = {
        id: id ? Number(id) : null,
        name: document.getElementById('courtName').value.trim(),
        ward_id: Number(document.getElementById('courtWardId').value),
        address: document.getElementById('courtAddress').value.trim(),
        city: 'TP. Hồ Chí Minh',
        price_per_hour: price,
        total_sub_courts: Number(document.getElementById('courtSubCount').value) || 6,
        status: document.getElementById('courtStatus').value,
        opening_time: document.getElementById('courtOpen').value,
        closing_time: document.getElementById('courtClose').value,
        latitude,
        longitude,
        image_url: document.getElementById('courtImage').value.trim() || 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop',
        description: document.getElementById('courtDesc').value.trim(),
        amenities,
        owner_name: currentUser.name,
        owner_phone: currentUser.phone
    };

    if (currentUser.role === 'admin' && document.getElementById('courtOwnerId')?.value) {
        payload.owner_id = Number(document.getElementById('courtOwnerId').value);
    }

    const originalLabel = btn.innerHTML;
    btn.disabled = true;
    btn.textContent = 'Đang lưu...';
    try {
        const savedCourt = id ? await AdminApp.updateCourt(Number(id), payload) : await AdminApp.createCourt(payload);

        // Reload from MySQL so reopening the editor reflects exactly what was saved.
        const {courts, wards} = await AdminApp.fetchCourts();
        myCourts = courts;
        wardsList = wards;
        populateWardSelect();
        renderCourtsTable();
        modalInstance.hide();
        AdminApp.toast('success', savedCourt?.review_pending?'Đã gửi thay đổi nhận diện sân cho Admin duyệt. Thông tin hiện tại được giữ nguyên.':'Đã cập nhật thông tin và tiện ích sân.');
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể lưu thông tin sân. Vui lòng thử lại.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalLabel;
    }
}

async function toggleCourtStatus(courtId, targetStatus) {
    const court = myCourts.find(c => Number(c.id) === Number(courtId));
    if (!court) return;
    const isActivating = targetStatus === 'active';
    const title = isActivating ? 'Kích hoạt sân hoạt động?' : 'Tạm ngừng nhận đặt sân này?';
    const message = isActivating
        ? `"${court.name}" sẽ mở nhận lịch đặt sân trở lại cho khách hàng.`
        : `"${court.name}" sẽ tạm ngừng nhận đơn mới. Lịch sử giao dịch và các đơn hiện có vẫn được giữ nguyên.`;

    const ok = await AdminApp.confirmAction(title, message);
    if (!ok) return;

    try {
        await AdminApp.setCourtStatusApi(courtId, targetStatus);
        AdminApp.toast('success', isActivating ? 'Đã kích hoạt sân hoạt động' : 'Đã tạm ngưng nhận đặt');
        await loadCourtsFromServer();
    } catch (err) {
        AdminApp.toast('error', err.message || (isActivating ? 'Không thể kích hoạt sân.' : 'Không thể tạm ngưng sân.'));
    }
}

function deleteCourt(courtId) {
    return toggleCourtStatus(courtId, 'inactive');
}
</script>
</body>
</html>
