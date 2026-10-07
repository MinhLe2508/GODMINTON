<?php
/**
 * admin-courts.php — Quản Lý Sân | Kênh Chủ Sân - GODMINTON
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 */
// DỮ LIỆU MẪU - thay bằng truy vấn DB ở tuần 2
require_once __DIR__ . '/includes/sample_data.php';

$currentPage = 'admin-courts';
$assetsPath = 'assets';
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
    <link rel="stylesheet" href="assets/css/ui-states.css">
</head>
<body class="admin-body">

<div class="admin-shell">
    <?php 
    $adminActive = 'courts';
    include __DIR__ . '/includes/partials/admin-sidebar.php'; 
    ?>

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
            <button class="btn btn-teal rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#courtModal" id="btnAddCourt">
                <i class="fa-solid fa-plus me-1"></i> Thêm sân
            </button>
        </header>

        <main class="admin-content">
            <div class="admin-panel">
                <div class="admin-panel-head">
                    <h2><i class="fa-solid fa-building-shield text-teal me-2"></i>Danh sách sân do tôi sở hữu</h2>
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
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Tên sân</label>
                            <input type="text" class="form-control" id="courtName" required placeholder="VD: Cầu Lông Quận 1 – Lê Thánh Tôn Sport">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Khu vực / Quận</label>
                            <input type="text" class="form-control" id="courtDistrict" required placeholder="VD: Quận 1">
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
                            <input type="number" min="1" step="1" class="form-control" id="courtSubCount" required placeholder="6">
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
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="wifi" id="am_wifi"><label class="form-check-label small" for="am_wifi">Wifi</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="parking" id="am_parking"><label class="form-check-label small" for="am_parking">Bãi đỗ xe</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="shower" id="am_shower"><label class="form-check-label small" for="am_shower">Phòng tắm</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="aircon" id="am_aircon"><label class="form-check-label small" for="am_aircon">Điều hòa</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="canteen" id="am_canteen"><label class="form-check-label small" for="am_canteen">Căng-tin</label></div>
                                <div class="form-check"><input class="form-check-input" type="checkbox" value="rental" id="am_rental"><label class="form-check-label small" for="am_rental">Cho thuê vợt/giày</label></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold">Lưu sân</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/mock-data.js"></script>
<script src="assets/js/ui-states.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/admin.js"></script>
<script src="assets/js/bottom-nav.js"></script>
<script>
let currentUser = null;
let modalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    currentUser = AdminApp.renderShell('courts', 'Quản lý sân', 'Kênh chủ sân / Quản lý sân');
    if (!currentUser) return;

    modalInstance = new bootstrap.Modal(document.getElementById('courtModal'));
    renderCourts();

    document.getElementById('btnAddCourt').addEventListener('click', () => openCourtModal(null));
    document.getElementById('searchCourt').addEventListener('input', () => renderCourts());
    document.getElementById('courtForm').addEventListener('submit', handleSaveCourt);
});

function renderCourts() {
    const q = (document.getElementById('searchCourt').value || '').toLowerCase().trim();
    let courts = AdminApp.getMyCourts(currentUser);
    if (q) {
        courts = courts.filter(c => c.name.toLowerCase().includes(q) || c.district.toLowerCase().includes(q));
    }

    const body = document.getElementById('courtsTableBody');
    document.getElementById('courtsEmpty').style.display = courts.length ? 'none' : 'block';

    body.innerHTML = courts.map(c => `
        <tr>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${c.image_url || 'assets/img/logo.jpg'}" class="admin-court-thumb" alt="">
                    <div class="min-w-0">
                        <div class="admin-table-court-name text-truncate" style="max-width:220px;">${c.name}</div>
                        <div class="small text-muted text-truncate" style="max-width:220px;">${c.address}</div>
                    </div>
                </div>
            </td>
            <td>${c.district}</td>
            <td>${MockDB.formatVND(c.price_per_hour)}</td>
            <td>${c.total_sub_courts}</td>
            <td>${c.opening_time} - ${c.closing_time}</td>
            <td><span class="badge ${c.status === 'active' ? 'bg-success' : 'bg-secondary'}">${c.status === 'active' ? 'Hoạt động' : 'Tạm ngưng'}</span></td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-teal rounded-pill me-1" onclick="openCourtModal(${c.id})"><i class="fa-solid fa-pen"></i></button>
                <a class="btn btn-sm btn-outline-secondary rounded-pill me-1" href="admin-slots.php?court=${c.id}" title="Quản lý khung giờ"><i class="fa-solid fa-clock"></i></a>
                <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="deleteCourt(${c.id})"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>`).join('');
}

function openCourtModal(courtId) {
    const form = document.getElementById('courtForm');
    form.reset();
    document.querySelectorAll('#amenityChecks input').forEach(chk => chk.checked = false);

    if (courtId) {
        const court = AdminApp.getMyCourts(currentUser).find(c => c.id === courtId);
        if (!court) return;
        document.getElementById('courtModalTitle').textContent = 'Chỉnh sửa sân';
        document.getElementById('courtId').value = court.id;
        document.getElementById('courtName').value = court.name;
        document.getElementById('courtDistrict').value = court.district;
        document.getElementById('courtAddress').value = court.address;
        document.getElementById('courtPrice').value = court.price_per_hour;
        document.getElementById('courtSubCount').value = court.total_sub_courts;
        document.getElementById('courtStatus').value = court.status;
        document.getElementById('courtOpen').value = court.opening_time;
        document.getElementById('courtClose').value = court.closing_time;
        document.getElementById('courtImage').value = court.image_url || '';
        document.getElementById('courtDesc').value = court.description || '';
        (court.amenities || []).forEach(a => {
            const chk = document.getElementById('am_' + a);
            if (chk) chk.checked = true;
        });
    } else {
        document.getElementById('courtModalTitle').textContent = 'Thêm sân mới';
        document.getElementById('courtId').value = '';
    }
    modalInstance.show();
}

function handleSaveCourt(e) {
    e.preventDefault();
    const id = document.getElementById('courtId').value;
    const price = Number(document.getElementById('courtPrice').value);
    const amenities = Array.from(document.querySelectorAll('#amenityChecks input:checked')).map(c => c.value);

    const allCourts = AdminApp.getAllCourts();

    const payload = {
        name: document.getElementById('courtName').value.trim(),
        district: document.getElementById('courtDistrict').value.trim(),
        address: document.getElementById('courtAddress').value.trim(),
        city: 'TP. Hồ Chí Minh',
        price_per_hour: price,
        min_price: price,
        price_formatted: MockDB.formatVND(price),
        total_sub_courts: Number(document.getElementById('courtSubCount').value),
        status: document.getElementById('courtStatus').value,
        opening_time: document.getElementById('courtOpen').value,
        closing_time: document.getElementById('courtClose').value,
        image_url: document.getElementById('courtImage').value.trim() || 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop',
        description: document.getElementById('courtDesc').value.trim(),
        amenities,
        owner_name: currentUser.name,
        owner_phone: currentUser.phone
    };

    if (id) {
        const idx = allCourts.findIndex(c => c.id === Number(id));
        if (idx > -1) allCourts[idx] = { ...allCourts[idx], ...payload };
        AdminApp.toast('success', 'Đã cập nhật thông tin sân');
    } else {
        const newId = allCourts.reduce((max, c) => Math.max(max, c.id), 0) + 1;
        allCourts.push({
            id: newId,
            avg_rating: 0,
            total_reviews: 0,
            reviews: [],
            ...payload
        });
        AdminApp.toast('success', 'Đã thêm sân mới');
    }

    AdminApp.saveAllCourts(allCourts);
    modalInstance.hide();
    renderCourts();
}

async function deleteCourt(courtId) {
    const court = AdminApp.getMyCourts(currentUser).find(c => c.id === courtId);
    if (!court) return;
    const ok = await AdminApp.confirmAction('Xóa sân này?', `"${court.name}" sẽ bị xóa khỏi hệ thống. Khung giờ đã tạo cho sân này sẽ không còn hiển thị cho khách.`);
    if (!ok) return;

    const allCourts = AdminApp.getAllCourts().filter(c => c.id !== courtId);
    AdminApp.saveAllCourts(allCourts);
    AdminApp.toast('success', 'Đã xóa sân');
    renderCourts();
}
</script>
</body>
</html>
