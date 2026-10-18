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
    <title>Tổng Quan | Kênh Chủ Sân - GODMINTON</title>
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
    <!-- ==================== SIDEBAR ==================== -->
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

    <!-- ==================== MAIN ==================== -->
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="btn btn-outline-secondary admin-sidebar-toggle" id="adminSidebarToggle"><i class="fa-solid fa-bars"></i></button>
            <div class="admin-topbar-title flex-grow-1">
                <div class="admin-breadcrumb" id="adminBreadcrumb">Kênh chủ sân</div>
                <h1 id="adminPageTitle">Tổng quan</h1>
            </div>
            <button id="themeToggle" class="btn btn-outline-secondary rounded-circle" style="width:40px;height:40px;" title="Chuyển giao diện sáng/tối">
                <i class="fa-solid fa-moon"></i>
            </button>
            <a href="admin-bookings.php" class="btn btn-teal rounded-pill px-3 fw-semibold d-none d-md-inline-flex">
                <i class="fa-solid fa-clipboard-list me-1"></i> Xem đơn đặt
            </a>
        </header>

        <main class="admin-content">

            <!-- Stat cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon bg-teal-subtle text-teal"><i class="fa-solid fa-building-shield"></i></div>
                        <div>
                            <div class="admin-stat-value" id="statCourts">0</div>
                            <div class="admin-stat-label">Sân đang quản lý</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon bg-warning bg-opacity-10 text-warning"><i class="fa-solid fa-calendar-day"></i></div>
                        <div>
                            <div class="admin-stat-value" id="statToday">0</div>
                            <div class="admin-stat-label">Lượt đặt hôm nay</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon bg-info bg-opacity-10 text-info"><i class="fa-solid fa-calendar-week"></i></div>
                        <div>
                            <div class="admin-stat-value" id="statWeek">0</div>
                            <div class="admin-stat-label">Lượt đặt tuần này</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon bg-success bg-opacity-10 text-success"><i class="fa-solid fa-sack-dollar"></i></div>
                        <div>
                            <div class="admin-stat-value" id="statRevenue">0 đ</div>
                            <div class="admin-stat-label">Doanh thu (đơn hợp lệ)</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent bookings -->
                <div class="col-lg-8">
                    <div class="admin-panel h-100">
                        <div class="admin-panel-head">
                            <h2><i class="fa-solid fa-receipt text-teal me-2"></i>Đơn đặt gần đây</h2>
                            <a href="admin-bookings.php" class="btn btn-sm btn-outline-teal rounded-pill">Xem tất cả</a>
                        </div>
                        <div class="admin-panel-body p-0">
                            <div class="table-responsive">
                                <table class="table admin-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Mã đơn</th>
                                            <th>Sân</th>
                                            <th>Khách hàng</th>
                                            <th>Ngày giờ</th>
                                            <th>Trạng thái</th>
                                        </tr>
                                    </thead>
                                    <tbody id="recentBookingsBody"></tbody>
                                </table>
                            </div>
                            <div id="recentBookingsEmpty" class="text-center text-muted py-4" style="display:none;">
                                <i class="fa-regular fa-folder-open fs-3 d-block mb-2"></i> Chưa có đơn đặt nào.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Courts summary -->
                <div class="col-lg-4">
                    <div class="admin-panel h-100">
                        <div class="admin-panel-head">
                            <h2><i class="fa-solid fa-layer-group text-teal me-2"></i>Sân của tôi</h2>
                            <a href="admin-courts.php" class="btn btn-sm btn-outline-teal rounded-pill">Quản lý</a>
                        </div>
                        <div class="admin-panel-body">
                            <div id="myCourtsList" class="d-flex flex-column gap-3"></div>
                            <div id="myCourtsEmpty" class="text-center text-muted py-3" style="display:none;">
                                <i class="fa-regular fa-face-frown fs-3 d-block mb-2"></i> Bạn chưa quản lý sân nào.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script src="assets/js/admin.js?v=<?= filemtime(__DIR__ . '/assets/js/admin.js') ?>"></script>
<script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', async () => {
    const user = AdminApp.renderShell('dashboard', 'Tổng quan', 'Kênh chủ sân / Thống kê tổng quan');
    if (!user) return;

    try {
        const data = await AdminApp.fetchDashboard();
        const stats = data.stats || {};
        const bookings = data.recentBookings || [];
        const courts = data.courts || [];

        document.getElementById('statCourts').textContent = stats.totalCourts ?? 0;
        document.getElementById('statToday').textContent = stats.todayCount ?? 0;
        document.getElementById('statWeek').textContent = stats.weekCount ?? 0;
        document.getElementById('statRevenue').textContent = AdminApp.formatVND(stats.revenue ?? 0);

        const statusMap = {
            pending:   { badge: 'bg-warning text-dark', text: 'Chờ xác nhận' },
            confirmed: { badge: 'bg-success', text: 'Đã xác nhận' },
            completed: { badge: 'bg-primary', text: 'Hoàn thành' },
            cancelled: { badge: 'bg-secondary', text: 'Đã hủy' }
        };

        const body = document.getElementById('recentBookingsBody');
        if (bookings.length === 0) {
            body.innerHTML = '';
            document.getElementById('recentBookingsEmpty').style.display = 'block';
        } else {
            document.getElementById('recentBookingsEmpty').style.display = 'none';
            body.innerHTML = bookings.map(b => {
                const s = statusMap[b.booking_status] || statusMap.confirmed;
                return `<tr>
                    <td class="fw-semibold">${b.booking_code}</td>
                    <td>${b.court_name}<div class="small text-muted">${b.sub_court_name || ''}</div></td>
                    <td>${b.user_name}</td>
                    <td>${b.booking_date}<div class="small text-muted">${b.time_range}</div></td>
                    <td><span class="badge ${s.badge}">${s.text}</span></td>
                </tr>`;
            }).join('');
        }

        const list = document.getElementById('myCourtsList');
        if (courts.length === 0) {
            list.innerHTML = '';
            document.getElementById('myCourtsEmpty').style.display = 'block';
        } else {
            document.getElementById('myCourtsEmpty').style.display = 'none';
            list.innerHTML = courts.slice(0, 5).map(c => `
                <div class="d-flex align-items-center gap-3">
                    <img src="${c.image_url}" class="admin-court-thumb" alt="">
                    <div class="flex-grow-1 min-w-0">
                        <div class="fw-semibold text-truncate">${c.name}</div>
                        <div class="small text-muted">${c.district} · ${c.total_sub_courts || 6} sân con</div>
                    </div>
                    <span class="badge ${c.status === 'active' ? 'bg-success' : 'bg-secondary'}">${c.status === 'active' ? 'Hoạt động' : 'Tạm ngưng'}</span>
                </div>`).join('');
        }
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không tải được dữ liệu thống kê.');
    }
});
</script>
</body>
</html>
