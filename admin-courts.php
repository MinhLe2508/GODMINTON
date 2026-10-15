<?php
require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/config/db.php';

// Giới hạn khu vực đô thị lõi TP.HCM (maxBounds)
define('CORE_BOUNDS_LAT_MIN', 10.6500000);
define('CORE_BOUNDS_LAT_MAX', 10.9500000);
define('CORE_BOUNDS_LNG_MIN', 106.5500000);
define('CORE_BOUNDS_LNG_MAX', 106.8500000);

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$currentUserRole = $_SESSION['role'] ?? 'customer';

// Xử lý API POST lưu sân / xóa sân
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;
    $action = $_GET['action'];

    if ($action === 'save') {
        $id = !empty($input['id']) ? (int)$input['id'] : null;
        $name = trim($input['name'] ?? '');
        $district = trim($input['district'] ?? '');
        $address = trim($input['address'] ?? '');
        $price = (float)($input['price_per_hour'] ?? 0);
        $status = in_array($input['status'] ?? '', ['active', 'inactive'], true) ? $input['status'] : 'active';
        $description = trim($input['description'] ?? '');
        $imageUrl = trim($input['image_url'] ?? '');
        $amenities = is_array($input['amenities'] ?? null) ? $input['amenities'] : [];

        // Validate cơ bản
        if ($name === '' || $address === '' || $price < 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đầy đủ tên sân, địa chỉ và giá hợp lệ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Validate tọa độ server-side
        $hasLat = isset($input['latitude']) && $input['latitude'] !== '' && $input['latitude'] !== null;
        $hasLng = isset($input['longitude']) && $input['longitude'] !== '' && $input['longitude'] !== null;
        $lat = null;
        $lng = null;

        if ($hasLat || $hasLng) {
            if (!$hasLat || !$hasLng || !is_numeric($input['latitude']) || !is_numeric($input['longitude'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Tọa độ kinh độ và vĩ độ phải cùng được nhập và là số thực hợp lệ.'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            $lat = (float)$input['latitude'];
            $lng = (float)$input['longitude'];

            if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Tọa độ vượt quá giới hạn địa lý chuẩn (lat: [-90, 90], lng: [-180, 180]).'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($lat < CORE_BOUNDS_LAT_MIN || $lat > CORE_BOUNDS_LAT_MAX || $lng < CORE_BOUNDS_LNG_MIN || $lng > CORE_BOUNDS_LNG_MAX) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Tọa độ nằm ngoài phạm vi khu vực đô thị TP.HCM (vĩ độ: 10.65–10.95, kinh độ: 106.55–106.85).'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        // Tìm ward_id tương ứng
        $wardId = 1;
        if ($district !== '') {
            $wStmt = $pdo->prepare("SELECT id FROM wards WHERE name LIKE :q OR old_area_label LIKE :q LIMIT 1");
            $wStmt->execute([':q' => '%' . $district . '%']);
            $wRow = $wStmt->fetch();
            if ($wRow) {
                $wardId = (int)$wRow['id'];
            }
        }

        try {
            $pdo->beginTransaction();

            if ($id) {
                // Kiểm tra quyền theo owner_id: chủ sân A không được sửa sân của chủ sân B
                $checkStmt = $pdo->prepare("SELECT owner_id FROM courts WHERE id = :id FOR UPDATE");
                $checkStmt->execute([':id' => $id]);
                $courtRow = $checkStmt->fetch();

                if (!$courtRow) {
                    $pdo->rollBack();
                    http_response_code(404);
                    echo json_encode(['status' => 'error', 'message' => 'Sân không tồn tại.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                if ($currentUserRole !== 'admin' && (int)$courtRow['owner_id'] !== $currentUserId) {
                    $pdo->rollBack();
                    http_response_code(403);
                    echo json_encode(['status' => 'error', 'message' => '403 Forbidden: Bạn không có quyền chỉnh sửa sân này.'], JSON_UNESCAPED_UNICODE);
                    exit;
                }

                $uStmt = $pdo->prepare("
                    UPDATE courts
                    SET name = :name,
                        ward_id = :ward_id,
                        address = :address,
                        latitude = :lat,
                        longitude = :lng,
                        price_per_hour = :price,
                        description = :desc,
                        status = :status
                    WHERE id = :id
                ");
                $uStmt->execute([
                    ':name'     => $name,
                    ':ward_id'  => $wardId,
                    ':address'  => $address,
                    ':lat'      => $lat ?? 0.0000000,
                    ':lng'      => $lng ?? 0.0000000,
                    ':price'    => $price,
                    ':desc'     => $description,
                    ':status'   => $status,
                    ':id'       => $id
                ]);
            } else {
                $insStmt = $pdo->prepare("
                    INSERT INTO courts (owner_id, ward_id, name, address, latitude, longitude, price_per_hour, description, status)
                    VALUES (:owner_id, :ward_id, :name, :address, :lat, :lng, :price, :desc, :status)
                ");
                $insStmt->execute([
                    ':owner_id' => $currentUserId ?: 3,
                    ':ward_id'  => $wardId,
                    ':name'     => $name,
                    ':address'  => $address,
                    ':lat'      => $lat ?? 0.0000000,
                    ':lng'      => $lng ?? 0.0000000,
                    ':price'    => $price,
                    ':desc'     => $description,
                    ':status'   => $status
                ]);
                $id = (int)$pdo->lastInsertId();
            }

            // Lưu ảnh nếu có
            if ($imageUrl !== '') {
                $delImg = $pdo->prepare("DELETE FROM court_images WHERE court_id = :id");
                $delImg->execute([':id' => $id]);

                $insImg = $pdo->prepare("INSERT INTO court_images (court_id, image_url, sort_order) VALUES (:id, :url, 1)");
                $insImg->execute([':id' => $id, ':url' => $imageUrl]);
            }

            // Lưu tiện ích nếu có
            if (!empty($amenities)) {
                $delAmen = $pdo->prepare("DELETE FROM court_amenities WHERE court_id = :id");
                $delAmen->execute([':id' => $id]);

                $findAmen = $pdo->query("SELECT code, id FROM amenities");
                $allAmen = $findAmen->fetchAll(PDO::FETCH_KEY_PAIR);

                $insAmen = $pdo->prepare("INSERT INTO court_amenities (court_id, amenity_id) VALUES (:court_id, :amenity_id)");
                foreach ($amenities as $code) {
                    if (isset($allAmen[$code])) {
                        $insAmen->execute([':court_id' => $id, ':amenity_id' => $allAmen[$code]]);
                    }
                }
            }

            $pdo->commit();

            echo json_encode([
                'status'  => 'success',
                'message' => 'Lưu thông tin sân thành công!',
                'data'    => ['id' => $id, 'latitude' => $lat, 'longitude' => $lng]
            ], JSON_UNESCAPED_UNICODE);
            exit;

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi cơ sở dữ liệu: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    if ($action === 'delete') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID sân không hợp lệ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        try {
            $checkStmt = $pdo->prepare("SELECT owner_id FROM courts WHERE id = :id");
            $checkStmt->execute([':id' => $id]);
            $courtRow = $checkStmt->fetch();

            if (!$courtRow) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Sân không tồn tại.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($currentUserRole !== 'admin' && (int)$courtRow['owner_id'] !== $currentUserId) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => '403 Forbidden: Bạn không có quyền xóa sân này.'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $delStmt = $pdo->prepare("UPDATE courts SET status = 'inactive' WHERE id = :id");
            $delStmt->execute([':id' => $id]);

            echo json_encode(['status' => 'success', 'message' => 'Đã cập nhật trạng thái sân thành công.'], JSON_UNESCAPED_UNICODE);
            exit;
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Lỗi xóa sân: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
}
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
                        <div class="col-md-6">
                            <label for="courtLatitude" class="form-label fw-semibold small">Vĩ độ (Latitude)</label>
                            <input type="number" step="0.0000001" min="-90" max="90" class="form-control" id="courtLatitude" placeholder="VD: 10.7769000">
                            <div class="form-text text-muted" style="font-size:0.75rem;">Phạm vi đô thị TP.HCM: 10.6500000 đến 10.9500000</div>
                            <div class="invalid-feedback" id="courtLatError">Vĩ độ không hợp lệ hoặc nằm ngoài khu vực đô thị (10.65 – 10.95).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="courtLongitude" class="form-label fw-semibold small">Kinh độ (Longitude)</label>
                            <input type="number" step="0.0000001" min="-180" max="180" class="form-control" id="courtLongitude" placeholder="VD: 106.7009000">
                            <div class="form-text text-muted" style="font-size:0.75rem;">Phạm vi đô thị TP.HCM: 106.5500000 đến 106.8500000</div>
                            <div class="invalid-feedback" id="courtLngError">Kinh độ không hợp lệ hoặc nằm ngoài khu vực đô thị (106.55 – 106.85).</div>
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

    document.getElementById('courtLatitude').classList.remove('is-invalid');
    document.getElementById('courtLongitude').classList.remove('is-invalid');

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
        document.getElementById('courtLatitude').value = (court.latitude !== undefined && court.latitude !== null && court.latitude !== 0) ? court.latitude : '';
        document.getElementById('courtLongitude').value = (court.longitude !== undefined && court.longitude !== null && court.longitude !== 0) ? court.longitude : '';

        (court.amenities || []).forEach(a => {
            const chk = document.getElementById('am_' + a);
            if (chk) chk.checked = true;
        });
    } else {
        document.getElementById('courtModalTitle').textContent = 'Thêm sân mới';
        document.getElementById('courtId').value = '';
        document.getElementById('courtLatitude').value = '';
        document.getElementById('courtLongitude').value = '';
    }
    modalInstance.show();
}

async function handleSaveCourt(e) {
    e.preventDefault();
    const id = document.getElementById('courtId').value;
    const price = Number(document.getElementById('courtPrice').value);
    const amenities = Array.from(document.querySelectorAll('#amenityChecks input:checked')).map(c => c.value);

    // Validate Tọa độ (Latitude / Longitude)
    const latStr = document.getElementById('courtLatitude').value.trim();
    const lngStr = document.getElementById('courtLongitude').value.trim();
    const latInput = document.getElementById('courtLatitude');
    const lngInput = document.getElementById('courtLongitude');
    latInput.classList.remove('is-invalid');
    lngInput.classList.remove('is-invalid');

    let lat = null;
    let lng = null;
    let hasCoordError = false;

    // Giới hạn maxBounds đô thị TP.HCM
    const BOUNDS = {
        latMin: 10.6500,
        latMax: 10.9500,
        lngMin: 106.5500,
        lngMax: 106.8500
    };

    if (latStr !== '' || lngStr !== '') {
        if (latStr === '' || isNaN(Number(latStr))) {
            latInput.classList.add('is-invalid');
            hasCoordError = true;
        } else {
            lat = Number(latStr);
            if (lat < -90 || lat > 90 || lat < BOUNDS.latMin || lat > BOUNDS.latMax) {
                latInput.classList.add('is-invalid');
                hasCoordError = true;
            }
        }

        if (lngStr === '' || isNaN(Number(lngStr))) {
            lngInput.classList.add('is-invalid');
            hasCoordError = true;
        } else {
            lng = Number(lngStr);
            if (lng < -180 || lng > 180 || lng < BOUNDS.lngMin || lng > BOUNDS.lngMax) {
                lngInput.classList.add('is-invalid');
                hasCoordError = true;
            }
        }
    }

    if (hasCoordError) {
        AdminApp.toast('error', 'Tọa độ không hợp lệ hoặc nằm ngoài khu vực đô thị (TP.HCM: 10.65–10.95, 106.55–106.85). Vui lòng kiểm tra lại!');
        return;
    }

    const payload = {
        id: id ? Number(id) : null,
        name: document.getElementById('courtName').value.trim(),
        district: document.getElementById('courtDistrict').value.trim(),
        address: document.getElementById('courtAddress').value.trim(),
        city: 'TP. Hồ Chí Minh',
        latitude: lat,
        longitude: lng,
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
        owner_name: currentUser ? currentUser.name : '',
        owner_phone: currentUser ? currentUser.phone : ''
    };

    // Gửi lưu phía server qua PHP (Transaction + Prepared Statements)
    try {
        const res = await fetch('admin-courts.php?action=save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const json = await res.json();

        if (json.status !== 'success') {
            AdminApp.toast('error', json.message || 'Lỗi khi lưu sân.');
            return;
        }

        // Cập nhật mảng lưu trữ giao diện
        const allCourts = AdminApp.getAllCourts();
        if (id) {
            const idx = allCourts.findIndex(c => c.id === Number(id));
            if (idx > -1) allCourts[idx] = { ...allCourts[idx], ...payload };
            AdminApp.toast('success', 'Đã cập nhật thông tin sân thành công');
        } else {
            const newId = json.data && json.data.id ? json.data.id : (allCourts.reduce((max, c) => Math.max(max, c.id), 0) + 1);
            allCourts.push({
                id: newId,
                avg_rating: 0,
                total_reviews: 0,
                reviews: [],
                ...payload
            });
            AdminApp.toast('success', 'Đã thêm sân mới thành công');
        }

        AdminApp.saveAllCourts(allCourts);
        modalInstance.hide();
        renderCourts();
    } catch (err) {
        // Fallback lưu local nếu mất kết nối server
        const allCourts = AdminApp.getAllCourts();
        if (id) {
            const idx = allCourts.findIndex(c => c.id === Number(id));
            if (idx > -1) allCourts[idx] = { ...allCourts[idx], ...payload };
            AdminApp.toast('success', 'Đã cập nhật thông tin sân (offline)');
        } else {
            const newId = allCourts.reduce((max, c) => Math.max(max, c.id), 0) + 1;
            allCourts.push({ id: newId, avg_rating: 0, total_reviews: 0, reviews: [], ...payload });
            AdminApp.toast('success', 'Đã thêm sân mới (offline)');
        }
        AdminApp.saveAllCourts(allCourts);
        modalInstance.hide();
        renderCourts();
    }
}

async function deleteCourt(courtId) {
    const court = AdminApp.getMyCourts(currentUser).find(c => c.id === courtId);
    if (!court) return;
    const ok = await AdminApp.confirmAction('Xóa sân này?', `"${court.name}" sẽ bị xóa khỏi hệ thống. Khung giờ đã tạo cho sân này sẽ không còn hiển thị cho khách.`);
    if (!ok) return;

    try {
        await fetch('admin-courts.php?action=delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: courtId })
        });
    } catch (err) {
        console.warn('Lỗi gọi API xóa sân phía server:', err);
    }

    const allCourts = AdminApp.getAllCourts().filter(c => c.id !== courtId);
    AdminApp.saveAllCourts(allCourts);
    AdminApp.toast('success', 'Đã xóa sân');
    renderCourts();
}
</script>
</body>
</html>
