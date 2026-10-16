<?php header('Cache-Control: no-store, max-age=0'); ?>
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
    <title>Quản Lý Đơn Đặt | Kênh Chủ Sân - GODMINTON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin_style.css?v=<?= filemtime(__DIR__ . '/assets/css/admin_style.css') ?>">
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
                <h1 id="adminPageTitle">Quản lý đơn đặt</h1>
            </div>
            <button id="themeToggle" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;" title="Chuyển giao diện sáng/tối">
                <i class="fa-solid fa-moon"></i>
            </button>
        </header>

        <main class="admin-content">
            <div class="admin-panel">
                <div class="admin-panel-head">
                    <h2><i class="fa-solid fa-clipboard-list text-teal me-2"></i>Danh sách đơn đặt sân</h2>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="search" id="searchBooking" class="form-control form-control-sm rounded-pill" placeholder="Tìm mã đơn / khách hàng..." style="min-width:200px;">
                        <select id="filterStatus" class="form-select form-select-sm rounded-pill" style="min-width:160px;">
                            <option value="">Tất cả trạng thái</option>
                            <option value="confirmed">Đã xác nhận</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                    </div>
                </div>
                <div class="admin-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Mã đơn</th>
                                    <th>Sân</th>
                                    <th>Khách hàng</th>
                                    <th>Ngày / giờ</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="bookingsTableBody"></tbody>
                        </table>
                    </div>
                    <div id="bookingsEmpty" class="text-center text-muted py-5" style="display:none;">
                        <i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i> Không có đơn đặt nào phù hợp.
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/mock-data.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/admin.js"></script>
<script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
<script>
let currentUser = null;

const STATUS_MAP = {
    confirmed: { badge: 'bg-success', text: 'Đã xác nhận' },
    completed: { badge: 'bg-primary', text: 'Hoàn thành' },
    cancelled: { badge: 'bg-secondary', text: 'Đã hủy' }
};

document.addEventListener('DOMContentLoaded', () => {
    currentUser = AdminApp.renderShell('bookings', 'Quản lý đơn đặt', 'Kênh chủ sân / Quản lý đơn đặt');
    if (!currentUser) return;

    document.getElementById('searchBooking').addEventListener('input', renderBookings);
    document.getElementById('filterStatus').addEventListener('change', renderBookings);
    renderBookings();
});

function renderBookings() {
    const q = (document.getElementById('searchBooking').value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('filterStatus').value;

    let bookings = AdminApp.getMyBookings(currentUser)
        .slice()
        .sort((a, b) => new Date(b.created_at) - new Date(a.created_at));

    if (statusFilter) bookings = bookings.filter(b => b.booking_status === statusFilter);
    if (q) {
        bookings = bookings.filter(b =>
            b.booking_code.toLowerCase().includes(q) ||
            b.user_name.toLowerCase().includes(q) ||
            b.court_name.toLowerCase().includes(q));
    }

    const body = document.getElementById('bookingsTableBody');
    document.getElementById('bookingsEmpty').style.display = bookings.length ? 'none' : 'block';

    body.innerHTML = bookings.map(b => {
        const s = STATUS_MAP[b.booking_status] || STATUS_MAP.confirmed;
        const actions = b.booking_status === 'confirmed' ? `
            <button class="btn btn-sm btn-outline-primary rounded-pill me-1" onclick="setStatus(${b.id}, 'completed')" title="Đánh dấu hoàn thành"><i class="fa-solid fa-flag-checkered"></i></button>
            <button class="btn btn-sm btn-outline-danger rounded-pill" onclick="setStatus(${b.id}, 'cancelled')" title="Hủy đơn"><i class="fa-solid fa-ban"></i></button>
        ` : '<span class="text-muted small">—</span>';

        return `<tr>
            <td class="fw-semibold">${b.booking_code}</td>
            <td>${b.court_name}<div class="small text-muted">${b.sub_court_name || ''}</div></td>
            <td>${b.user_name}<div class="small text-muted">${b.user_phone || ''}</div></td>
            <td>${b.booking_date}<div class="small text-muted">${b.time_range}</div></td>
            <td>${MockDB.formatVND(b.total_amount)}</td>
            <td><span class="badge ${s.badge}">${s.text}</span></td>
            <td class="text-end">${actions}</td>
        </tr>`;
    }).join('');
}

async function setStatus(bookingId, newStatus) {
    const label = newStatus === 'completed' ? 'đánh dấu hoàn thành' : 'hủy';
    const ok = await AdminApp.confirmAction(`Xác nhận ${label} đơn này?`, 'Hành động này sẽ cập nhật trạng thái đơn đặt sân.');
    if (!ok) return;

    const all = MockDB.getBookings();
    const idx = all.findIndex(b => b.id === bookingId);
    if (idx === -1) return;
    all[idx].booking_status = newStatus;
    AdminApp.saveBookings(all);
    AdminApp.toast('success', 'Đã cập nhật trạng thái đơn đặt');
    renderBookings();
}
</script>
</body>
</html>
