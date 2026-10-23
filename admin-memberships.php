<?php
/**
 * admin-memberships.php
 * Kênh Quản Trị — Quản lý Hội Viên GODMINTON (Bạc 5%, Vàng 10%, Bạch Kim 15%).
 * Chỉ Quản trị viên (role = 'admin') mới được truy cập.
 */

require_once __DIR__ . '/includes/auth-check.php';
header('Cache-Control: no-store, max-age=0');

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('403 — Chỉ Quản trị viên (Admin) mới có quyền truy cập trang quản lý hội viên.');
}

$currentUser = [
    'id'    => (int)$_SESSION['user_id'],
    'name'  => $_SESSION['name'] ?? '',
    'email' => $_SESSION['email'] ?? '',
    'role'  => $_SESSION['role'] ?? ''
];
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản Lý Hội Viên | GODMINTON Admin</title>
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

    <style>
        .tier-badge-silver {
            background: linear-gradient(135deg, #475569 0%, #64748b 100%);
            color: #ffffff;
        }
        .tier-badge-gold {
            background: linear-gradient(135deg, #b45309 0%, #d97706 100%);
            color: #ffffff;
        }
        .tier-badge-platinum {
            background: linear-gradient(135deg, #090d16 0%, #1e293b 100%);
            color: #f1f5f9;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .user-avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: #e2e8f0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            color: #0d9488;
            font-size: 1rem;
            flex-shrink: 0;
        }
    </style>

    <script>
        window.__GODMINTON_USER__ = <?php echo json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
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
                <div class="brand-sub">Kênh quản trị</div>
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
            <button class="btn btn-outline-secondary admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Mở menu"><i class="fa-solid fa-bars"></i></button>
            <div class="admin-topbar-title flex-grow-1">
                <div class="admin-breadcrumb" id="adminBreadcrumb">Quản trị hệ thống</div>
                <h1 id="adminPageTitle">Quản lý hội viên</h1>
            </div>
            <div class="admin-topbar-actions d-flex align-items-center gap-2">
                <button id="themeToggle" type="button" class="admin-icon-btn" aria-label="Chuyển giao diện sáng/tối" title="Chuyển giao diện sáng/tối">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <a href="admin-posts.php?type=membership" class="btn btn-outline-teal rounded-pill px-3 fw-semibold d-none d-md-inline-flex" title="Cấu hình giá và đặc quyền gói">
                    <i class="fa-solid fa-sliders me-1"></i> Cấu hình gói bài viết
                </a>
                <button class="btn btn-teal rounded-pill px-3 fw-semibold" id="btnOpenGrantModal">
                    <i class="fa-solid fa-crown me-1 text-warning"></i> Cấp gói hội viên
                </button>
            </div>
        </header>

        <main class="admin-content">

            <!-- Thống kê KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card border-warning border-opacity-25">
                        <div class="admin-stat-icon bg-warning bg-opacity-15 text-warning">
                            <i class="fa-solid fa-user-clock"></i>
                        </div>
                        <div>
                            <div class="admin-stat-value text-warning fw-extrabold" id="statPendingApproval">0</div>
                            <div class="admin-stat-label">Chờ admin duyệt xác nhận</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon bg-teal-subtle text-teal">
                            <i class="fa-solid fa-crown text-warning"></i>
                        </div>
                        <div>
                            <div class="admin-stat-value text-teal" id="statActiveMembers">0</div>
                            <div class="admin-stat-label">Hội viên đang hoạt động</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fa-solid fa-layer-group"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-1 mb-1">
                                <span class="badge tier-badge-silver rounded-pill px-2 py-0" style="font-size:0.7rem;" id="statCountSilver">Bạc: 0</span>
                                <span class="badge tier-badge-gold rounded-pill px-2 py-0" style="font-size:0.7rem;" id="statCountGold">Vàng: 0</span>
                                <span class="badge tier-badge-platinum rounded-pill px-2 py-0" style="font-size:0.7rem;" id="statCountPlatinum">B.Kim: 0</span>
                            </div>
                            <div class="admin-stat-label">Phân bố hạng thành viên</div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon bg-success bg-opacity-10 text-success">
                            <i class="fa-solid fa-sack-dollar"></i>
                        </div>
                        <div>
                            <div class="admin-stat-value text-success" id="statTotalRevenue">0 đ</div>
                            <div class="admin-stat-label">Doanh thu bán gói hội viên</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bảng danh sách & Bộ lọc -->
            <div class="admin-panel">
                <div class="admin-panel-head d-flex flex-column flex-xl-row align-items-xl-center justify-content-between gap-3">
                    <h2 class="mb-0 text-nowrap"><i class="fa-solid fa-users text-teal me-2"></i>Danh sách đăng ký & Hội viên</h2>
                    <div class="d-flex align-items-center gap-2 flex-nowrap overflow-x-auto pb-1 pb-xl-0" style="scrollbar-width: thin;">
                        <input type="search" id="searchKeyword" class="form-control form-control-sm rounded-pill" placeholder="Tìm theo tên, email, SĐT..." style="width: 220px; flex-shrink: 0;">
                        
                        <select id="filterTier" class="form-select form-select-sm rounded-pill" style="width: 150px; flex-shrink: 0;">
                            <option value="">Tất cả hạng gói</option>
                            <option value="silver">Bạc (Giảm 5%)</option>
                            <option value="gold">Vàng (Giảm 10%)</option>
                            <option value="platinum">Bạch Kim (Giảm 15%)</option>
                        </select>

                        <select id="filterStatus" class="form-select form-select-sm rounded-pill" style="width: 170px; flex-shrink: 0;">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending">Chờ admin xác nhận</option>
                            <option value="active">Đang kích hoạt</option>
                            <option value="expired">Đã hết hạn</option>
                            <option value="cancelled">Đã hủy / Từ chối</option>
                        </select>

                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 flex-shrink-0" id="btnResetFilters" title="Đặt lại bộ lọc">
                            <i class="fa-solid fa-rotate-left"></i>
                        </button>
                    </div>
                </div>

                <div class="admin-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Khách hàng</th>
                                    <th>Gói & Hạng Hội Viên</th>
                                    <th>Thời hạn gói</th>
                                    <th>Thanh toán</th>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="membershipTableBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <div class="spinner-border spinner-border-sm text-teal mb-2" role="status"></div>
                                        <div>Đang tải dữ liệu hội viên...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Trạng thái trống -->
                    <div id="membershipEmpty" class="text-center text-muted py-5" style="display:none;">
                        <i class="fa-regular fa-folder-open fs-2 d-block mb-2 text-secondary opacity-50"></i>
                        <h6 class="fw-bold">Không tìm thấy hội viên nào</h6>
                        <p class="small text-muted mb-3">Chưa có bản ghi hội viên nào phù hợp với bộ lọc hiện tại.</p>
                        <button class="btn btn-sm btn-teal rounded-pill px-3" onclick="document.getElementById('btnOpenGrantModal').click()">
                            <i class="fa-solid fa-crown me-1 text-warning"></i> Cấp gói ngay
                        </button>
                    </div>

                    <!-- Phân trang -->
                    <div class="d-flex align-items-center justify-content-between p-3 border-top flex-wrap gap-2" id="paginationWrap" style="display:none !important;">
                        <div class="small text-muted" id="paginationInfo">Hiển thị 0 - 0 trên 0 bản ghi</div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0 gap-1" id="paginationUl"></ul>
                        </nav>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- ==================== MODAL: CẤP / TẶNG GÓI HỘI VIÊN ==================== -->
<div class="modal fade" id="modalGrantMembership" tabindex="-1" aria-labelledby="modalGrantLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalGrantLabel">
                    <i class="fa-solid fa-crown text-warning me-2"></i>Cấp Gói Hội Viên Cho Thành Viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formGrantMembership">
                <div class="modal-body pt-3">
                    <p class="small text-muted mb-3">
                        Quản trị viên có thể cấp hoặc tặng gói hội viên cho khách hàng đã đăng ký tài khoản (khi khách đóng tiền mặt tại quầy hoặc chương trình tri ân).
                    </p>

                    <!-- Chọn thành viên -->
                    <div class="mb-3">
                        <label for="grantUserId" class="form-label fw-semibold small">Chọn khách hàng <span class="text-danger">*</span></label>
                        <select class="form-select" id="grantUserId" required>
                            <option value="">-- Chọn khách hàng --</option>
                        </select>
                    </div>

                    <!-- Chọn gói -->
                    <div class="mb-3">
                        <label for="grantPostId" class="form-label fw-semibold small">Gói hội viên <span class="text-danger">*</span></label>
                        <select class="form-select" id="grantPostId" required>
                            <option value="">-- Chọn gói hội viên --</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <!-- Thời hạn -->
                        <div class="col-6">
                            <label for="grantDurationDays" class="form-label fw-semibold small">Thời hạn (ngày)</label>
                            <input type="number" class="form-control" id="grantDurationDays" min="1" max="1000" value="30">
                            <div class="form-text" style="font-size:0.72rem;">Số ngày hiệu lực của gói</div>
                        </div>
                        <!-- Số tiền thanh toán -->
                        <div class="col-6">
                            <label for="grantPricePaid" class="form-label fw-semibold small">Số tiền ghi nhận (đ)</label>
                            <input type="number" class="form-control" id="grantPricePaid" min="0" step="1000" value="0">
                            <div class="form-text" style="font-size:0.72rem;">Nhập 0đ nếu là gói tặng</div>
                        </div>
                    </div>

                    <!-- Phương thức thanh toán -->
                    <div class="mb-3">
                        <label for="grantPaymentMethod" class="form-label fw-semibold small">Phương thức thanh toán / Ghi chú</label>
                        <select class="form-select" id="grantPaymentMethod">
                            <option value="Admin cấp / Tiền mặt quầy">Tiền mặt tại quầy</option>
                            <option value="Chuyển khoản trực tiếp">Chuyển khoản trực tiếp</option>
                            <option value="Tặng VIP / Tri ân khách hàng">Tặng VIP / Tri ân khách hàng</option>
                            <option value="VietQR">VietQR</option>
                            <option value="MoMo">MoMo</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold" id="btnSubmitGrant">
                        <i class="fa-solid fa-circle-check me-1"></i> Xác nhận cấp gói
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL: GIA HẠN NHANH ==================== -->
<div class="modal fade" id="modalExtendMembership" tabindex="-1" aria-labelledby="modalExtendLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="modalExtendLabel">
                    <i class="fa-solid fa-arrows-rotate text-warning me-2"></i>Gia Hạn Thêm Ngày Hội Viên
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="formExtendMembership">
                <input type="hidden" id="extendMembershipId">
                <div class="modal-body pt-3">
                    <div class="p-3 rounded-3 bg-light border border-secondary border-opacity-10 mb-3">
                        <div class="fw-bold fs-6 text-dark mb-1" id="extendTargetName">Tên khách hàng</div>
                        <div class="small text-muted mb-2" id="extendPackageTitle">Gói Hội Viên Bạc</div>
                        <div class="small">
                            Hạn hiện tại: <strong id="extendCurrentEndDate">--/--/----</strong>
                        </div>
                    </div>

                    <label class="form-label fw-semibold small">Chọn số ngày cộng thêm:</label>
                    <div class="d-flex gap-2 flex-wrap mb-3">
                        <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 btn-preset-days" data-days="30">+30 ngày (1 tháng)</button>
                        <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 btn-preset-days" data-days="60">+60 ngày (2 tháng)</button>
                        <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 btn-preset-days" data-days="90">+90 ngày (1 quý)</button>
                        <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 btn-preset-days" data-days="365">+365 ngày (1 năm)</button>
                    </div>

                    <div class="mb-3">
                        <label for="extendDaysInput" class="form-label fw-semibold small">Hoặc nhập số ngày tùy chỉnh:</label>
                        <input type="number" class="form-control" id="extendDaysInput" min="1" max="1000" value="30" required>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning text-dark rounded-pill px-4 fw-bold" id="btnSubmitExtend">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> Xác nhận gia hạn
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/swal-godminton.js?v=<?= filemtime(__DIR__ . '/assets/css/swal-godminton.css') ?>"></script>
<script src="assets/js/booking-account-client.js?v=<?= filemtime(__DIR__ . '/assets/js/booking-account-client.js') ?>"></script>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script src="assets/js/admin.js?v=<?= filemtime(__DIR__ . '/assets/js/admin.js') ?>"></script>
<script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
<script src="assets/js/admin-memberships.js?v=<?= time() ?>"></script>

</body>
</html>
