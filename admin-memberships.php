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
        /* Segmented Tier Distribution KPI */
        .stat-tier-segmented {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(0, 0, 0, 0.06);
            border-radius: 0.75rem;
            padding: 0.35rem 0.5rem;
            gap: 0.35rem;
            margin-bottom: 0;
        }
        [data-bs-theme="dark"] .stat-tier-segmented {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.08);
        }
        .stat-tier-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-width: 0;
            text-align: center;
            padding: 0.1rem 0;
        }
        .stat-tier-divider {
            width: 1px;
            height: 24px;
            background: rgba(0, 0, 0, 0.08);
            flex-shrink: 0;
        }
        [data-bs-theme="dark"] .stat-tier-divider {
            background: rgba(255, 255, 255, 0.1);
        }
        .stat-tier-num {
            font-size: 1.15rem;
            font-weight: 800;
            line-height: 1.1;
        }
        .stat-tier-name {
            font-size: 0.65rem;
            font-weight: 700;
            line-height: 1.2;
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            margin-top: 0.15rem;
            white-space: nowrap;
        }
        .stat-tier-name .tier-icon {
            font-size: 0.6rem;
        }
        /* Tier colors */
        .tier-col-silver .stat-tier-num,
        .tier-col-silver .stat-tier-name {
            color: #64748b;
        }
        .tier-col-gold .stat-tier-num,
        .tier-col-gold .stat-tier-name {
            color: #d97706;
        }
        .tier-col-platinum .stat-tier-num,
        .tier-col-platinum .stat-tier-name {
            color: #0d9488;
        }
        [data-bs-theme="dark"] .tier-col-silver .stat-tier-num,
        [data-bs-theme="dark"] .tier-col-silver .stat-tier-name {
            color: #94a3b8;
        }
        [data-bs-theme="dark"] .tier-col-gold .stat-tier-num,
        [data-bs-theme="dark"] .tier-col-gold .stat-tier-name {
            color: #fbbf24;
        }
        [data-bs-theme="dark"] .tier-col-platinum .stat-tier-num,
        [data-bs-theme="dark"] .tier-col-platinum .stat-tier-name {
            color: #2dd4bf;
        }
        @media (max-width: 576px) {
            .stat-tier-segmented {
                padding: 0.2rem 0.25rem;
                gap: 0.15rem;
            }
            .stat-tier-num {
                font-size: 0.95rem;
            }
            .stat-tier-name {
                font-size: 0.6rem;
                gap: 0.1rem;
            }
            .stat-tier-divider {
                height: 18px;
            }
        }
        .user-avatar-sm {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            background: rgba(13, 148, 136, 0.12);
            color: var(--brand-teal, #0d9488);
            border: 1px solid rgba(13, 148, 136, 0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .membership-filter-toolbar {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            justify-content: flex-end !important;
            flex-wrap: nowrap !important;
            gap: 0.5rem !important;
        }
        .membership-filter-toolbar .form-control,
        .membership-filter-toolbar .form-select,
        .membership-filter-toolbar .btn {
            display: inline-block !important;
            margin-bottom: 0 !important;
            height: 34px !important;
            font-size: 0.8125rem !important;
        }
        .membership-filter-toolbar #searchKeyword {
            width: 220px !important;
            min-width: 180px !important;
            max-width: 220px !important;
            flex: 0 0 220px !important;
        }
        .membership-filter-toolbar #filterTier {
            width: 135px !important;
            min-width: 120px !important;
            max-width: 135px !important;
            flex: 0 0 135px !important;
        }
        .membership-filter-toolbar #filterStatus {
            width: 165px !important;
            min-width: 150px !important;
            max-width: 165px !important;
            flex: 0 0 165px !important;
        }
        .membership-filter-toolbar #btnResetFilters {
            flex: 0 0 auto !important;
            width: 36px !important;
            padding: 0 !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        @media (max-width: 991.98px) {
            .membership-filter-toolbar {
                overflow-x: auto !important;
                max-width: 100% !important;
                padding-bottom: 0.35rem !important;
            }
        .admin-table th.text-nowrap,
        .admin-table td.text-nowrap {
            white-space: nowrap !important;
        }
        .admin-table .btn-open-extend {
            white-space: nowrap !important;
            vertical-align: middle !important;
        }
        #grantUserResultsList {
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15) !important;
            border-color: #cbd5e1 !important;
        }
        #grantUserResultsList .user-search-item {
            transition: background-color 0.15s ease;
            cursor: pointer;
        }
        #grantUserResultsList .user-search-item:hover {
            background-color: rgba(13, 148, 136, 0.08) !important;
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
                <button class="btn btn-teal rounded-pill px-3 fw-semibold" id="btnOpenGrantModal" title="Cấp gói hội viên">
                    <i class="fa-solid fa-crown me-1 text-warning"></i><span class="btn-label-text">Cấp gói hội viên</span>
                </button>
            </div>
        </header>

        <main class="admin-content">

            <!-- Thống kê KPIs -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card border-warning border-opacity-25">
                        <div class="admin-stat-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                            <i class="fa-solid fa-user-clock"></i>
                        </div>
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <span class="admin-stat-value text-warning fw-extrabold" id="statPendingApproval">0</span>
                            <span class="admin-stat-label">Chờ admin duyệt xác nhận</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="admin-stat-icon bg-teal-subtle text-teal">
                            <i class="fa-solid fa-crown text-warning"></i>
                        </div>
                        <div class="d-flex align-items-center gap-2 min-w-0">
                            <span class="admin-stat-value text-teal" id="statActiveMembers">0</span>
                            <span class="admin-stat-label">Hội viên đang hoạt động</span>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="admin-stat-card">
                        <div class="w-100 min-w-0">
                            <div class="stat-tier-segmented">
                                <div class="stat-tier-col tier-col-silver" title="Hội viên Bạc">
                                    <span class="stat-tier-num" id="statCountSilver">0</span>
                                    <span class="stat-tier-name">
                                        <i class="fa-solid fa-medal tier-icon"></i>Bạc
                                    </span>
                                </div>
                                <div class="stat-tier-divider"></div>
                                <div class="stat-tier-col tier-col-gold" title="Hội viên Vàng">
                                    <span class="stat-tier-num" id="statCountGold">0</span>
                                    <span class="stat-tier-name">
                                        <i class="fa-solid fa-crown tier-icon"></i>Vàng
                                    </span>
                                </div>
                                <div class="stat-tier-divider"></div>
                                <div class="stat-tier-col tier-col-platinum" title="Hội viên Bạch Kim">
                                    <span class="stat-tier-num" id="statCountPlatinum">0</span>
                                    <span class="stat-tier-name">
                                        <i class="fa-solid fa-gem tier-icon"></i>B.Kim
                                    </span>
                                </div>
                            </div>
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
                <div class="admin-panel-head d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h2 class="mb-0 text-nowrap"><i class="fa-solid fa-users text-teal me-2"></i>Danh sách đăng ký & Hội viên</h2>
                    <div class="membership-filter-toolbar">
                        <input type="search" id="searchKeyword" class="form-control form-control-sm rounded-pill" placeholder="Tìm theo tên, email, SĐT...">
                        
                        <select id="filterTier" class="form-select form-select-sm rounded-pill">
                            <option value="">Tất cả hạng gói</option>
                            <option value="silver">Bạc</option>
                            <option value="gold">Vàng</option>
                            <option value="platinum">Bạch Kim</option>
                        </select>

                        <select id="filterStatus" class="form-select form-select-sm rounded-pill">
                            <option value="">Tất cả trạng thái</option>
                            <option value="pending">Chờ admin xác nhận</option>
                            <option value="active">Đang kích hoạt</option>
                            <option value="expired">Đã hết hạn</option>
                            <option value="cancelled">Đã hủy / Từ chối</option>
                        </select>

                        <button class="btn btn-sm btn-outline-secondary rounded-pill" id="btnResetFilters" title="Đặt lại bộ lọc">
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
                                    <th class="text-end text-nowrap">Thao tác</th>
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
                        Quản trị viên có thể cấp gói hội viên cho khách hàng đã đăng ký tài khoản sau khi xác nhận chuyển khoản thành công.
                    </p>

                    <!-- Tìm khách hàng -->
                    <div class="mb-3 position-relative" id="grantUserSearchGroup">
                        <label for="grantUserSearchInput" class="form-label fw-semibold small">Tìm & Chọn tài khoản khách hàng <span class="text-danger">*</span></label>
                        <div class="input-group" id="grantUserSearchInputWrap">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="grantUserSearchInput" placeholder="Nhập tên, email hoặc số điện thoại để tìm tài khoản..." autocomplete="off">
                            <button type="button" class="btn btn-outline-secondary" id="btnClearUserSearch" style="display:none;" title="Xóa từ khóa">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                        <input type="hidden" id="grantUserId" name="user_id">
                        
                        <!-- Dropdown danh sách gợi ý khi tìm kiếm -->
                        <div id="grantUserResultsList" class="list-group position-absolute w-100 shadow border rounded-3 mt-1 overflow-y-auto" style="max-height: 220px; z-index: 1060; display: none; background: var(--bg-card, #ffffff);">
                        </div>

                        <!-- Khách hàng đã chọn -->
                        <div id="grantSelectedUserCard" class="mt-2 p-2 px-3 rounded-3 border d-flex align-items-center justify-content-between" style="display:none; background: rgba(13, 148, 136, 0.08); border-color: rgba(13, 148, 136, 0.25) !important;">
                            <div class="d-flex align-items-center gap-2 overflow-hidden">
                                <div class="bg-teal text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px; font-size: 0.8rem; font-weight: 700;">
                                    <i class="fa-solid fa-user-check"></i>
                                </div>
                                <div class="text-truncate">
                                    <div class="fw-bold admin-text-primary small text-truncate" id="grantSelectedUserName">--</div>
                                    <div class="text-muted text-truncate" style="font-size: 0.74rem;" id="grantSelectedUserDetails">--</div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none fw-semibold flex-shrink-0 ms-2" id="btnRemoveSelectedUser">
                                <i class="fa-solid fa-xmark me-1"></i>Đổi
                            </button>
                        </div>
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
                        <label for="grantPaymentMethod" class="form-label fw-semibold small">Phương thức thanh toán</label>
                        <select class="form-select" id="grantPaymentMethod">
                            <option value="Chuyển khoản" selected>Chuyển khoản</option>
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
                    <div class="p-3 rounded-3 border border-secondary border-opacity-10 mb-3" style="background: var(--bg-surface, rgba(13, 148, 136, 0.05));">
                        <div class="fw-bold fs-6 admin-text-primary mb-1" id="extendTargetName">Tên khách hàng</div>
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

<!-- ==================== MODAL: CHI TIẾT HÓA ĐƠN GÓI HỘI VIÊN ==================== -->
<div class="modal fade" id="membershipInvoiceModal" tabindex="-1" aria-labelledby="modalInvoiceTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div>
                    <h5 class="modal-title fw-bold fs-5 mb-1" id="modalInvoiceTitle">
                        <i class="fa-solid fa-file-invoice-dollar text-teal me-2"></i>Chi Tiết Hóa Đơn Hội Viên
                    </h5>
                    <div class="small text-muted" id="modalInvoiceSub">Mã đăng ký: #MB-000000</div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <span id="modalInvoiceStatusBadge"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
            </div>
            <div class="modal-body p-4" id="modalInvoiceBody">
                <!-- Nội dung chi tiết hóa đơn render bằng JS -->
            </div>
            <div class="modal-footer border-top bg-light-subtle rounded-bottom-4 d-flex justify-content-between align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="window.print()">
                    <i class="fa-solid fa-print me-1"></i> In hóa đơn
                </button>
                <div class="d-flex gap-2" id="modalInvoiceFooterActions">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
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
<script src="assets/js/admin-memberships.js?v=<?= time() ?>"></script>

</body>
</html>
