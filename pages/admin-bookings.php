<?php header('Cache-Control: no-store, max-age=0'); ?>
<?php
require_once dirname(__DIR__) . '/includes/auth-check.php';
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
<?php include_once dirname(__DIR__) . '/includes/partials/pwa-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/swal-godminton.css') ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin_style.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/admin_style.css') ?>">
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
            <div class="admin-topbar-actions">
                <button id="themeToggle" type="button" class="admin-icon-btn" aria-label="Chuyển giao diện sáng/tối" title="Chuyển giao diện sáng/tối">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
        </header>

        <main class="admin-content">
            <div class="admin-panel booking-list-panel">
                <div class="admin-panel-head">
                    <h2><i class="fa-solid fa-clipboard-list text-teal me-2"></i>Danh sách đơn đặt sân</h2>
                    <div class="booking-filter-toolbar">
                        <input type="search" id="searchBooking" class="form-control form-control-sm rounded-pill" placeholder="Tìm mã đơn, tên, SĐT...">
                        <select id="filterStatus" class="form-select form-select-sm rounded-pill">
                            <option value="">Tất cả trạng thái</option>
                            <option value="confirmed">Đã cọc</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="no_show">Bùng sân</option>
                            <option value="cancelled">Đã hủy</option>
                        </select>
                        <button class="btn btn-sm btn-outline-secondary rounded-circle" id="btnResetBookingFilters" type="button" title="Làm mới bộ lọc">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>
                <div class="admin-panel-body p-0">
                    <div class="table-responsive booking-table-wrap">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Mã đơn</th>
                                    <th>Sân &amp; Thảm</th>
                                    <th>Lịch chơi</th>
                                    <th>Khách hàng</th>
                                    <th>Đã cọc / Thu tại quầy</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end pe-3" style="min-width:145px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="bookingsTableBody"></tbody>
                        </table>
                    </div>
                    <div id="bookingsEmpty" class="text-center text-muted py-5" style="display:none;">
                        <i class="fa-regular fa-folder-open fs-2 d-block mb-2 text-teal"></i> Không có đơn đặt nào phù hợp.
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Chi Tiết & Vận Hành Sân Chuyên Nghiệp (Live Match & Billing Modal) -->
<div class="modal fade" id="bookingDetailModal" tabindex="-1" aria-labelledby="modalBookingTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="modal-title fw-bold fs-5 mb-1" id="modalBookingTitle">Chi tiết đơn đặt</h5>
                    <div class="small text-muted" id="modalBookingSub"></div>
                </div>
                <div class="d-flex align-items-center gap-3 ms-auto">
                    <span id="modalBookingBadge"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
            </div>
            <div class="modal-body p-4" id="modalBookingBody">
                <!-- Nội dung chi tiết được render động qua JS -->
            </div>
            <div class="modal-footer border-top bg-light-subtle rounded-bottom-4 d-flex justify-content-end align-items-center gap-2" id="modalFooterActions">
                <!-- Nút thao tác chuyển trạng thái linh hoạt -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/swal-godminton.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/swal-godminton.js') ?>"></script>
<script src="assets/js/app.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/app.js') ?>"></script>
<script src="assets/js/admin.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/admin.js') ?>"></script>
<script src="assets/js/booking-account-client.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/booking-account-client.js') ?>"></script>
<script src="assets/js/admin-bookings.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/admin-bookings.js') ?>"></script>
</body>
</html>
