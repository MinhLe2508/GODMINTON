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
    <title>Quản Lý Khung Giờ | Kênh Chủ Sân - GODMINTON</title>
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
        .slot-chip { position: relative; }
        .slot-del-btn {
            position: absolute;
            top: 4px;
            right: 6px;
            border: 0;
            background: transparent;
            color: inherit;
            opacity: .65;
            font-size: .78rem;
            line-height: 1;
            padding: 2px 4px;
            border-radius: 4px;
        }
        .slot-del-btn:hover { opacity: 1; background: rgba(0,0,0,.1); }
        #slotCourtCombobox { position: relative; }
        #slotCourtDropdown {
            max-height: 280px;
            overflow-y: auto;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            z-index: 1050;
            background: var(--bg-card, #ffffff);
            border: 1px solid var(--border-color, rgba(0, 0, 0, 0.15)) !important;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.18);
        }
        #slotCourtDropdown .dropdown-item {
            cursor: pointer;
            border-radius: 8px;
            padding: 8px 12px;
            margin: 2px 4px;
            width: calc(100% - 8px);
            transition: all 0.15s ease;
        }
        #slotCourtDropdown .dropdown-item:hover {
            background-color: rgba(13, 148, 136, 0.12);
            color: var(--brand-teal, #0d9488);
        }
        #slotCourtDropdown .dropdown-item.active {
            background-color: rgba(13, 148, 136, 0.18);
            color: var(--brand-teal, #0d9488);
            font-weight: 600;
        }
        [data-bs-theme="dark"] #slotCourtDropdown {
            background-color: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.12) !important;
        }
        [data-bs-theme="dark"] #slotCourtDropdown .dropdown-item:hover {
            background-color: rgba(20, 184, 166, 0.2);
            color: #2dd4bf;
        }
    </style>
    <script>
        window.__GODMINTON_USER__ = <?php echo json_encode($currentUser, JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
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
                <h1 id="adminPageTitle">Quản lý khung giờ</h1>
            </div>
            <div class="admin-topbar-actions">
                <button id="themeToggle" type="button" class="admin-icon-btn" aria-label="Chuyển giao diện sáng/tối" title="Chuyển giao diện sáng/tối">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
        </header>

        <main class="admin-content">

            <!-- Bộ chọn sân + ngày + View Switcher & Action Toolbar -->
            <div class="admin-panel mb-4" style="overflow: visible;">
                <div class="admin-panel-body" style="overflow: visible;">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-5 col-md-6">
                            <label class="form-label fw-semibold small" for="slotCourtInput">Nhập hoặc bấm chọn sân</label>
                            <div class="position-relative" id="slotCourtCombobox">
                                <div class="input-group">
                                    <input type="text" id="slotCourtInput" class="form-control" placeholder="Gõ tên sân hoặc bấm chọn..." autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" id="btnToggleCourtDropdown" title="Bấm để xem danh sách tất cả các sân" tabindex="-1">
                                        <i class="fa-solid fa-chevron-down small" id="courtDropdownIcon"></i>
                                    </button>
                                </div>
                                <input type="hidden" id="slotCourtId" value="">
                                <div id="slotCourtDropdown" class="dropdown-menu w-100 shadow-lg border-0 rounded-3 mt-1 py-1" style="display:none;"></div>
                            </div>
                        </div>

                        <div class="col-lg-4 col-md-6">
                            <label class="form-label fw-semibold small mb-1" for="slotDateInput">Chọn ngày</label>
                            <input type="date" id="slotDateInput" class="form-control">
                        </div>

                        <div class="col-lg-3 col-md-12 text-lg-end text-start">
                            <button class="btn btn-teal w-100 rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#generateModal">
                                <i class="fa-solid fa-wand-magic-sparkles me-1"></i> Tạo khung giờ hàng loạt
                            </button>
                        </div>
                    </div>

                    <!-- Thanh Thao tác nghiệp vụ nhanh: Bảo trì, Đổi giá, Xóa khung giờ -->
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mt-3 pt-3 border-top">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-teal-subtle text-teal rounded-pill px-3 py-2 fw-bold" style="font-size:0.85rem;">
                                <i class="fa-solid fa-table-cells me-1"></i> KHUNG GIỜ SÂN
                            </span>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-outline-warning rounded-pill px-3 fw-semibold btn-sm" id="btnOpenBulkMaintenance" title="Bảo trì hoặc khóa hàng loạt theo thảm hoặc theo khung giờ">
                                <i class="fa-solid fa-screwdriver-wrench me-1"></i> Bảo trì sân
                            </button>
                            <button class="btn btn-outline-primary rounded-pill px-3 fw-semibold btn-sm" id="btnOpenBulkPrice" title="Điều chỉnh giá vé hàng loạt cho các khung giờ">
                                <i class="fa-solid fa-tags me-1"></i> Thay đổi giá
                            </button>
                            <button class="btn btn-outline-danger rounded-pill px-3 fw-semibold btn-sm" id="btnOpenBulkDelete" title="Xóa khung giờ theo khoảng thời gian và thảm con">
                                <i class="fa-regular fa-trash-can me-1"></i> Xóa khung giờ
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel chính hiển thị Khung giờ theo Thảm -->
            <div class="admin-panel">
                <div class="admin-panel-head flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h2 class="mb-0"><i class="fa-solid fa-calendar-days text-teal me-2"></i>Khung giờ sân</h2>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 fw-semibold" id="slotSummaryDate"></span>
                    </div>

                    <!-- KPI Badges Summary -->
                    <div class="slot-kpi-bar" id="slotKpiBar">
                        <span class="slot-kpi-chip"><span class="slot-legend-dot" style="background:#16a34a;"></span>Trống: <b id="kpiAvailable">0</b></span>
                        <span class="slot-kpi-chip"><span class="slot-legend-dot" style="background:#f59e0b;"></span>Đang giữ: <b id="kpiHolding">0</b></span>
                        <span class="slot-kpi-chip"><span class="slot-legend-dot" style="background:#dc2626;"></span>Đã đặt: <b id="kpiBooked">0</b></span>
                        <span class="slot-kpi-chip"><span class="slot-legend-dot" style="background:#ea580c;"></span>Bảo trì: <b id="kpiMaintenance">0</b></span>
                        <span class="slot-kpi-chip"><span class="slot-legend-dot" style="background:#94a3b8;"></span>Khóa: <b id="kpiBlocked">0</b></span>
                        <span class="slot-kpi-chip bg-teal-subtle text-teal fw-bold">Tổng: <b id="kpiTotal">0</b></span>
                    </div>
                </div>

                <div class="admin-panel-body">

                    <!-- ==================== MA TRẬN BẢNG LỊCH THẢM (TIMELINE MATRIX) ==================== -->
                    <div class="matrix-container" id="matrixContainer">
                        <table class="matrix-table" id="matrixTable"></table>
                    </div>

                    <!-- Trạng thái trống -->
                    <div id="slotEmpty" class="text-center text-muted py-5" style="display:none;">
                        <i class="fa-regular fa-calendar-xmark fs-2 d-block mb-2"></i>
                        <span id="slotEmptyMsg">Chưa có khung giờ nào cho ngày này. Nhấn "Tạo khung giờ hàng loạt" để thiết lập.</span>
                    </div>

                </div>
            </div>

        </main>
    </div>
</div>

<!-- ==================== MODAL 1: THAO TÁC / QUẢN LÝ KHUNG GIỜ CHI TIẾT ==================== -->
<div class="modal fade" id="slotActionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title fw-bold" id="slotModalTitle">Chi tiết khung giờ</h5>
                    <div class="text-muted small" id="slotModalSubtitle"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="slotActionForm">
                <input type="hidden" id="modalSlotId">
                <div class="modal-body py-3">

                    <!-- Trường hợp 1: Khung giờ đã được đặt (Booked) -->
                    <div id="slotBookedInfo" class="alert alert-danger rounded-3 p-3 mb-3" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="text-danger"><i class="fa-solid fa-circle-check me-1"></i> Khung giờ đã có khách đặt</strong>
                            <span class="badge bg-danger text-white rounded-pill" id="modalBookingStatusBadge">Đã đặt</span>
                        </div>
                        <div class="small" id="modalBookingDetails"></div>
                        <div class="mt-2 text-end">
                            <a href="admin-bookings.php" class="btn btn-sm btn-outline-danger rounded-pill fw-semibold" id="modalBookingLink">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Chuyển tới Quản lý đơn đặt
                            </a>
                        </div>
                    </div>

                    <!-- Trường hợp 2: Khung giờ đang bảo trì (Maintenance) -->
                    <div id="slotMaintenanceInfo" class="alert alert-warning border-warning-subtle rounded-3 p-3 mb-3" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="text-warning-emphasis"><i class="fa-solid fa-screwdriver-wrench me-1"></i> Thông tin bảo trì</strong>
                            <span class="badge bg-warning text-dark rounded-pill">Đang bảo trì</span>
                        </div>
                        <div class="small">
                            <div><strong>Lý do bảo trì:</strong> <span id="modalMaintenanceNote" class="text-dark fw-semibold"></span></div>
                            <div class="text-muted mt-1" style="font-size:0.78rem;"><i class="fa-regular fa-clock me-1"></i> Khung giờ này đang tạm dừng để bảo dưỡng / sửa chữa.</div>
                        </div>
                    </div>

                    <!-- Trường hợp 3: Khung giờ đang đóng / tạm khóa (Blocked) -->
                    <div id="slotBlockedInfo" class="alert alert-secondary border-secondary-subtle rounded-3 p-3 mb-3" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="text-secondary-emphasis"><i class="fa-solid fa-lock me-1"></i> Thông tin sân đóng / tạm khóa</strong>
                            <span class="badge bg-secondary text-white rounded-pill">Đang đóng sân</span>
                        </div>
                        <div class="small">
                            <div><strong>Lý do đóng sân:</strong> <span id="modalBlockedNote" class="text-dark fw-semibold"></span></div>
                            <div class="text-muted mt-1" style="font-size:0.78rem;"><i class="fa-regular fa-calendar-xmark me-1"></i> Khung giờ này đang tạm ngừng nhận khách.</div>
                        </div>
                    </div>

                    <!-- Trường hợp 4: Khung giờ đang giữ chỗ (Holding) -->
                    <div id="slotHoldingInfo" class="alert alert-info border-info-subtle rounded-3 p-3 mb-3" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong class="text-info-emphasis"><i class="fa-regular fa-hourglass-half me-1"></i> Khung giờ đang được giữ chỗ</strong>
                            <span class="badge bg-info text-dark rounded-pill">Đang giữ chỗ</span>
                        </div>
                        <div class="small text-muted">Khách hàng đang trong quá trình thanh toán cho khung giờ này. Giữ chỗ sẽ tự hết hạn nếu không hoàn tất thanh toán.</div>
                    </div>

                    <!-- Trường hợp 5: Khung giờ trống (Available) -->
                    <div id="slotAvailableInfo" class="alert alert-success border-success-subtle rounded-3 p-3 mb-3" style="display:none;">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <strong class="text-success-emphasis"><i class="fa-solid fa-circle-check me-1"></i> Khung giờ đang trống</strong>
                            <span class="badge bg-success text-white rounded-pill">Sẵn sàng nhận khách</span>
                        </div>
                        <div class="small text-muted">Khung giờ hiện mở cho khách đặt. Bạn có thể khóa sân hoặc bảo trì khung giờ này dưới đây.</div>
                    </div>

                    <!-- Nút bấm chuyển sang chế độ chỉnh sửa (khi đang ở bảo trì hoặc đóng sân) -->
                    <div id="slotEditToggleWrap" class="text-end mb-2" style="display:none;">
                        <button type="button" class="btn btn-link btn-sm text-decoration-none text-muted p-0" id="btnToggleEditSlot" style="font-size:0.8rem;">
                            <i class="fa-solid fa-pen-to-square me-1"></i> Chỉnh sửa trạng thái / lý do
                        </button>
                    </div>

                    <!-- Nhóm form điều chỉnh (Dropdown trạng thái + Textarea nhập lý do) -->
                    <div id="slotEditFields" style="display:none;">
                        <!-- Thanh chọn Trạng thái khung giờ -->
                        <div id="slotStatusControlGroup" class="mb-3">
                            <label class="form-label fw-semibold small" for="modalSlotStatus">Cập nhật trạng thái</label>
                            <select class="form-select" id="modalSlotStatus">
                                <option value="available">🟢 Trống (Nhận khách)</option>
                                <option value="maintenance">🟠 Bảo trì sân</option>
                                <option value="blocked">⚪ Tạm khóa</option>
                            </select>
                        </div>

                        <!-- Lý do bảo trì / Ghi chú nội bộ -->
                        <div id="slotNoteControlGroup" class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold small mb-0" for="modalSlotNote">
                                    Lý do bảo trì / Ghi chú nội bộ
                                </label>
                                <span class="text-muted" style="font-size:0.75rem;">(Hiển thị trực quan trên lịch)</span>
                            </div>
                            <textarea class="form-control" id="modalSlotNote" rows="3" placeholder="Nhập lý do bảo trì hoặc ghi chú nội bộ cho khung giờ này..." maxlength="255"></textarea>
                        </div>
                    </div>

                </div>

                <div class="modal-footer border-0 pt-0">
                    <div class="d-flex w-100 gap-2" id="modalFooterActions">
                        <button type="button" class="btn btn-outline-secondary rounded-pill py-2 fw-semibold flex-fill" data-bs-dismiss="modal" id="btnModalCloseSlot">Đóng</button>
                        <button type="button" class="btn btn-teal rounded-pill py-2 fw-bold flex-fill" id="btnModalReopenSlot" style="display:none;">
                            Mở lại nhận khách
                        </button>
                        <button type="submit" class="btn btn-teal rounded-pill py-2 fw-bold flex-fill" id="btnModalSaveSlot" style="display:none;">Lưu thay đổi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL 2: BẢO TRÌ NHANH HÀNG LOẠT ==================== -->
<div class="modal fade" id="bulkMaintenanceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-screwdriver-wrench text-warning me-2"></i>Bảo trì sân hàng loạt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkMaintenanceForm">
                <div class="modal-body">
                    <p class="text-muted small">Khóa hoặc đặt chế độ bảo trì hàng loạt cho các khung giờ chưa có khách đặt.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Áp dụng cho Thảm con</label>
                            <select class="form-select" id="bulkMaintSubCourt">
                                <option value="all">Tất cả các thảm</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Phạm vi thời gian</label>
                            <div class="d-flex gap-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="bulkMaintScope" id="maintScopeDay" value="day" checked>
                                    <label class="form-check-label small" for="maintScopeDay">Cả ngày hôm nay</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="bulkMaintScope" id="maintScopeTime" value="time">
                                    <label class="form-check-label small" for="maintScopeTime">Theo khoảng giờ cụ thể</label>
                                </div>
                            </div>
                            <div class="row g-2" id="bulkMaintTimeInputs" style="display:none;">
                                <div class="col-6">
                                    <label class="form-label small text-muted">Từ giờ</label>
                                    <input type="time" class="form-control form-control-sm" id="bulkMaintStart" value="08:00">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small text-muted">Đến giờ</label>
                                    <input type="time" class="form-control form-control-sm" id="bulkMaintEnd" value="17:00">
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Chế độ</label>
                            <select class="form-select" id="bulkMaintStatus">
                                <option value="maintenance" selected>🟠 Bảo trì sân</option>
                                <option value="blocked">⚪ Tạm khóa</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold small">Lý do bảo trì / Ghi chú</label>
                            <div class="d-flex flex-wrap gap-1 mb-2">
                                <button type="button" class="btn btn-outline-secondary rounded-pill py-1 px-2" style="font-size:0.75rem;" onclick="setBulkMaintNote('Sửa chữa hệ thống đèn chiếu')">Sửa đèn</button>
                                <button type="button" class="btn btn-outline-secondary rounded-pill py-1 px-2" style="font-size:0.75rem;" onclick="setBulkMaintNote('Bảo dưỡng & vệ sinh mặt thảm')">Bảo dưỡng thảm</button>
                                <button type="button" class="btn btn-outline-secondary rounded-pill py-1 px-2" style="font-size:0.75rem;" onclick="setBulkMaintNote('Mưa dột tạm dừng hoạt động')">Mưa dột</button>
                                <button type="button" class="btn btn-outline-secondary rounded-pill py-1 px-2" style="font-size:0.75rem;" onclick="setBulkMaintNote('Tổ chức giải đấu nội bộ')">Giải đấu nội bộ</button>
                            </div>
                            <input type="text" class="form-control" id="bulkMaintNote" value="Bảo dưỡng mặt thảm định kỳ" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold" id="btnSubmitBulkMaint">
                        <i class="fa-solid fa-wrench me-1"></i> Áp dụng bảo trì
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL 3: ĐIỀU CHỈNH GIÁ VÉ HÀNG LOẠT ==================== -->
<div class="modal fade" id="bulkPriceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-tags text-primary me-2"></i>Điều chỉnh giá vé hàng loạt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkPriceForm">
                <div class="modal-body">
                    <p class="text-muted small">Cập nhật mức giá mới cho các khung giờ chưa có khách đặt trong ngày đang chọn.</p>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Áp dụng cho Thảm con</label>
                            <select class="form-select" id="bulkPriceSubCourt">
                                <option value="all">Tất cả các thảm</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small">Mức giá mới / khung giờ (VNĐ)</label>
                            <div class="input-group">
                                <input type="number" min="0" step="1000" class="form-control" id="bulkPriceValue" required placeholder="Ví dụ: 90000">
                                <span class="input-group-text">đ</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" id="btnSubmitBulkPrice">
                        <i class="fa-solid fa-check me-1"></i> Cập nhật giá vé
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL 4: TẠO KHUNG GIỜ HÀNG LOẠT ==================== -->
<div class="modal fade" id="generateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-wand-magic-sparkles text-teal me-2"></i>Tạo khung giờ hàng loạt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="generateForm">
                <div class="modal-body">
                    <p class="text-muted small">Áp dụng cho sân và ngày đang chọn. Hệ thống sẽ tự chia khung giờ theo thời lượng bạn chọn cho từng thảm con đã tick.</p>
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
                                <option value="30">30 phút (chuẩn lưới đặt sân)</option>
                                <option value="60" selected>60 phút</option>
                                <option value="90">90 phút</option>
                                <option value="120">120 phút</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Giá / khung (đ)</label>
                            <input type="number" min="0" step="1000" class="form-control" id="genPrice" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold small d-block">Áp dụng cho các thảm con</label>
                            <div id="genSubCourtChecks" class="d-flex flex-wrap gap-3"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold" id="btnSubmitGenerate">
                        <i class="fa-solid fa-check me-1"></i> Tạo khung giờ
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==================== MODAL 5: XÓA KHUNG GIỜ THEO THẢM ==================== -->
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="fa-regular fa-trash-can me-2"></i>Xóa khung giờ theo thảm</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="bulkDeleteForm">
                <div class="modal-body">
                    <p class="text-muted small">Áp dụng cho sân và ngày đang chọn. Hệ thống sẽ xóa các khung giờ chưa có khách đặt trong khoảng giờ và các thảm con đã tick (khung giờ đã đặt được bảo vệ an toàn).</p>
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Giờ bắt đầu</label>
                            <input type="time" class="form-control" id="delStart" value="06:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold small">Giờ kết thúc</label>
                            <input type="time" class="form-control" id="delEnd" value="23:00" required>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label fw-semibold small mb-0">Áp dụng cho các thảm con</label>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none" onclick="toggleAllDelSubCourts(true)" style="font-size:0.75rem;">Chọn tất cả</button>
                                    <span class="text-muted" style="font-size:0.75rem;">·</span>
                                    <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-muted" onclick="toggleAllDelSubCourts(false)" style="font-size:0.75rem;">Bỏ chọn</button>
                                </div>
                            </div>
                            <div id="delSubCourtChecks" class="d-flex flex-wrap gap-3 p-2 bg-light rounded-3 border"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold" id="btnSubmitBulkDelete">
                        Xóa khung giờ
                    </button>
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
let myCourts = [];
let currentSlots = [];

let genModal = null;
let slotActionModal = null;
let bulkMaintModal = null;
let bulkPriceModal = null;
let bulkDelModal = null;

document.addEventListener('DOMContentLoaded', async () => {
    currentUser = AdminApp.renderShell('slots', 'Quản lý khung giờ', 'Kênh chủ sân / Quản lý khung giờ');
    if (!currentUser) return;

    genModal = new bootstrap.Modal(document.getElementById('generateModal'));
    slotActionModal = new bootstrap.Modal(document.getElementById('slotActionModal'));
    bulkMaintModal = new bootstrap.Modal(document.getElementById('bulkMaintenanceModal'));
    bulkPriceModal = new bootstrap.Modal(document.getElementById('bulkPriceModal'));
    bulkDelModal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));

    const dateInput = document.getElementById('slotDateInput');
    const todayStr = new Date().toLocaleDateString('sv-SE');
    dateInput.value = todayStr;

    // Combobox chọn sân
    const courtInput = document.getElementById('slotCourtInput');
    const courtIdInput = document.getElementById('slotCourtId');
    const dropdownMenu = document.getElementById('slotCourtDropdown');
    const toggleBtn = document.getElementById('btnToggleCourtDropdown');
    const dropdownIcon = document.getElementById('courtDropdownIcon');

    try {
        const { courts } = await AdminApp.fetchCourts();
        myCourts = courts;
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không tải được danh sách sân.');
        myCourts = [];
    }

    if (myCourts.length === 0) {
        courtInput.value = '';
        courtInput.placeholder = 'Bạn chưa có sân nào';
        courtInput.disabled = true;
        if (toggleBtn) toggleBtn.disabled = true;
    } else {
        const preselect = new URLSearchParams(location.search).get('court');
        let initialCourt = null;
        if (preselect) {
            initialCourt = myCourts.find(c => Number(c.id) === Number(preselect));
        }
        if (!initialCourt && myCourts.length > 0) {
            initialCourt = myCourts[0];
        }
        if (initialCourt) {
            courtInput.value = initialCourt.name;
            courtIdInput.value = initialCourt.id;
        }
    }

    function renderCourtDropdownItems(filterText = '') {
        if (!dropdownMenu) return;
        const q = filterText.trim().toLowerCase();
        const currentCourt = getSelectedCourt();
        const filtered = q ? myCourts.filter(c => c.name.toLowerCase().includes(q) || (c.address && c.address.toLowerCase().includes(q))) : myCourts;

        if (filtered.length === 0) {
            dropdownMenu.innerHTML = `<div class="px-3 py-2 text-muted small"><i class="fa-solid fa-circle-exclamation me-1 text-warning"></i>Không tìm thấy sân phù hợp</div>`;
        } else {
            dropdownMenu.innerHTML = filtered.map(c => {
                const isSelected = currentCourt && Number(currentCourt.id) === Number(c.id);
                return `
                    <div class="dropdown-item d-flex align-items-center justify-content-between ${isSelected ? 'active' : ''}" 
                         data-id="${c.id}" data-name="${AdminApp.escapeHTML(c.name)}">
                        <div class="me-2 text-truncate">
                            <div class="fw-semibold small"><i class="fa-solid fa-building-shield me-2 text-teal opacity-75"></i>${AdminApp.escapeHTML(c.name)}</div>
                            ${c.address ? `<div class="text-muted" style="font-size:0.75rem;"><i class="fa-solid fa-location-dot me-1 text-danger opacity-75"></i>${AdminApp.escapeHTML(c.address)}</div>` : ''}
                        </div>
                        ${isSelected ? '<i class="fa-solid fa-check text-teal flex-shrink-0"></i>' : ''}
                    </div>
                `;
            }).join('');
        }
    }

    function openCourtDropdown(filterText = '') {
        if (myCourts.length === 0) return;
        renderCourtDropdownItems(filterText);
        dropdownMenu.style.display = 'block';
        if (dropdownIcon) {
            dropdownIcon.classList.remove('fa-chevron-down');
            dropdownIcon.classList.add('fa-chevron-up');
        }
    }

    function closeCourtDropdown() {
        if (dropdownMenu) dropdownMenu.style.display = 'none';
        if (dropdownIcon) {
            dropdownIcon.classList.remove('fa-chevron-up');
            dropdownIcon.classList.add('fa-chevron-down');
        }
    }

    function selectCourt(court) {
        if (!court) return;
        courtInput.value = court.name;
        courtIdInput.value = court.id;
        closeCourtDropdown();
        renderSubCourtChecks();
        populateModalSubCourtSelects();
        renderAll();
    }

    function syncCourtFromInput() {
        const val = (courtInput.value || '').trim().toLowerCase();
        if (!val) {
            courtIdInput.value = '';
            renderSubCourtChecks();
            populateModalSubCourtSelects();
            renderAll();
            return;
        }
        let matched = myCourts.find(c => c.name.toLowerCase() === val);
        if (!matched) {
            matched = myCourts.find(c => c.name.toLowerCase().includes(val));
        }
        if (matched) {
            courtIdInput.value = matched.id;
        } else {
            courtIdInput.value = '';
        }
        renderSubCourtChecks();
        populateModalSubCourtSelects();
        renderAll();
    }

    if (toggleBtn) {
        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            if (dropdownMenu.style.display === 'block') {
                closeCourtDropdown();
            } else {
                openCourtDropdown('');
                courtInput.focus();
            }
        });
    }

    courtInput.addEventListener('focus', () => openCourtDropdown(''));
    courtInput.addEventListener('input', () => {
        openCourtDropdown(courtInput.value);
        syncCourtFromInput();
    });
    courtInput.addEventListener('change', () => {
        const court = getSelectedCourt();
        if (court) selectCourt(court);
    });

    courtInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeCourtDropdown();
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const firstItem = dropdownMenu.querySelector('.dropdown-item[data-id]');
            if (firstItem && dropdownMenu.style.display === 'block') {
                const found = myCourts.find(c => Number(c.id) === Number(firstItem.dataset.id));
                if (found) selectCourt(found);
            } else {
                const court = getSelectedCourt();
                if (court) selectCourt(court);
            }
        }
    });

    dropdownMenu.addEventListener('click', (e) => {
        const item = e.target.closest('.dropdown-item[data-id]');
        if (!item) return;
        const court = myCourts.find(c => Number(c.id) === Number(item.dataset.id));
        if (court) selectCourt(court);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#slotCourtCombobox')) {
            closeCourtDropdown();
        }
    });

    dateInput.addEventListener('change', renderAll);
    document.getElementById('genDuration').addEventListener('change', updateSuggestedPrice);
    document.getElementById('generateForm').addEventListener('submit', handleGenerate);
    document.getElementById('btnOpenBulkDelete')?.addEventListener('click', () => {
        renderSubCourtChecks();
        bulkDelModal.show();
    });
    document.getElementById('bulkDeleteForm')?.addEventListener('submit', handleBulkDelete);

    // Modal action form listeners
    document.getElementById('slotActionForm').addEventListener('submit', handleSaveSlotAction);
    document.getElementById('btnModalReopenSlot')?.addEventListener('click', handleModalReopenSlot);
    document.getElementById('btnToggleEditSlot')?.addEventListener('click', handleToggleEditSlot);

    // Bulk Maintenance Modal listeners
    document.querySelectorAll('input[name="bulkMaintScope"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const timeInputs = document.getElementById('bulkMaintTimeInputs');
            timeInputs.style.display = radio.value === 'time' ? 'flex' : 'none';
        });
    });
    document.getElementById('btnOpenBulkMaintenance').addEventListener('click', () => {
        populateModalSubCourtSelects();
        bulkMaintModal.show();
    });
    document.getElementById('bulkMaintenanceForm').addEventListener('submit', handleBulkMaintenanceSubmit);

    // Bulk Price Modal listeners
    document.getElementById('btnOpenBulkPrice').addEventListener('click', () => {
        populateModalSubCourtSelects();
        const court = getSelectedCourt();
        if (court) {
            document.getElementById('bulkPriceValue').value = court.price_per_hour || 80000;
        }
        bulkPriceModal.show();
    });
    document.getElementById('bulkPriceForm').addEventListener('submit', handleBulkPriceSubmit);

    renderSubCourtChecks();
    populateModalSubCourtSelects();
    await renderAll();
});

function getSelectedCourt() {
    const hiddenId = Number(document.getElementById('slotCourtId')?.value);
    if (hiddenId) {
        const found = myCourts.find(c => Number(c.id) === hiddenId);
        if (found) return found;
    }
    const val = (document.getElementById('slotCourtInput')?.value || '').trim().toLowerCase();
    if (!val) return null;
    return myCourts.find(c => c.name.toLowerCase() === val) ||
           myCourts.find(c => c.name.toLowerCase().includes(val)) || null;
}

function updateSuggestedPrice() {
    const court = getSelectedCourt();
    if (!court) return;
    const dur = Number(document.getElementById('genDuration').value) || 60;
    document.getElementById('genPrice').value = Math.round((Number(court.price_per_hour) || 80000) * (dur / 60));
}

function renderSubCourtChecks() {
    const court = getSelectedCourt();
    const genWrap = document.getElementById('genSubCourtChecks');
    const delWrap = document.getElementById('delSubCourtChecks');
    if (!court) {
        if (genWrap) genWrap.innerHTML = '';
        if (delWrap) delWrap.innerHTML = '';
        return;
    }

    const count = Number(court.total_sub_courts) || 4;
    const names = Array.from({ length: count }, (_, i) => `Thảm A${i + 1}`);

    if (genWrap) {
        genWrap.innerHTML = names.map((n, i) => `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="${n}" id="sub_${i}" checked>
                <label class="form-check-label small" for="sub_${i}">${n}</label>
            </div>`).join('');
    }

    if (delWrap) {
        delWrap.innerHTML = names.map((n, i) => `
            <div class="form-check">
                <input class="form-check-input" type="checkbox" value="${n}" id="del_sub_${i}" checked>
                <label class="form-check-label small" for="del_sub_${i}">${n}</label>
            </div>`).join('');
    }

    const startVal = court.opening_time ? court.opening_time.substring(0, 5) : '06:00';
    const endVal = court.closing_time ? court.closing_time.substring(0, 5) : '22:00';

    const genStartEl = document.getElementById('genStart');
    const genEndEl = document.getElementById('genEnd');
    const delStartEl = document.getElementById('delStart');
    const delEndEl = document.getElementById('delEnd');

    if (genStartEl) genStartEl.value = startVal;
    if (genEndEl) genEndEl.value = endVal;
    if (delStartEl) delStartEl.value = startVal;
    if (delEndEl) delEndEl.value = endVal;

    updateSuggestedPrice();
}

function toggleAllDelSubCourts(checked) {
    document.querySelectorAll('#delSubCourtChecks input[type="checkbox"]').forEach(cb => {
        cb.checked = checked;
    });
}

function populateModalSubCourtSelects() {
    const court = getSelectedCourt();
    const maintSelect = document.getElementById('bulkMaintSubCourt');
    const priceSelect = document.getElementById('bulkPriceSubCourt');
    if (!court || !maintSelect || !priceSelect) return;

    const count = Number(court.total_sub_courts) || 4;
    const names = Array.from({ length: count }, (_, i) => `Thảm A${i + 1}`);

    const optionsHtml = '<option value="all">Tất cả các thảm</option>' +
        names.map(n => `<option value="${n}">${n}</option>`).join('');

    maintSelect.innerHTML = optionsHtml;
    priceSelect.innerHTML = optionsHtml;
}

function setBulkMaintNote(note) {
    document.getElementById('bulkMaintNote').value = note;
}

/* ==================== LOAD & RENDER DATA (MA TRẬN THẢM) ==================== */
async function renderAll() {
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    const empty = document.getElementById('slotEmpty');
    const emptyMsg = document.getElementById('slotEmptyMsg');
    const summaryDate = document.getElementById('slotSummaryDate');

    if (summaryDate && dateStr) {
        const parts = dateStr.split('-');
        summaryDate.textContent = `Ngày ${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    if (!court || !dateStr) {
        currentSlots = [];
        updateKpis([]);
        document.getElementById('matrixTable').innerHTML = '';
        if (empty) {
            emptyMsg.textContent = myCourts.length === 0 ? 'Bạn chưa có sân nào để quản lý.' : 'Vui lòng nhập tên sân hợp lệ để hiển thị khung giờ.';
            empty.style.display = 'block';
        }
        return;
    }

    try {
        currentSlots = await AdminApp.fetchSlots(court.id, dateStr);
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không tải được danh sách khung giờ.');
        currentSlots = [];
    }

    updateKpis(currentSlots);

    if (currentSlots.length === 0) {
        empty.style.display = 'block';
        emptyMsg.textContent = 'Chưa có khung giờ nào cho ngày này. Nhấn "Tạo khung giờ hàng loạt" để thiết lập.';
        document.getElementById('matrixTable').innerHTML = '';
        return;
    }

    empty.style.display = 'none';
    renderMatrixView();
}

function updateKpis(slots) {
    const avail = slots.filter(s => s.status === 'available').length;
    const hold  = slots.filter(s => s.status === 'holding').length;
    const book  = slots.filter(s => s.status === 'booked').length;
    const maint = slots.filter(s => s.status === 'maintenance').length;
    const block = slots.filter(s => s.status === 'blocked').length;

    document.getElementById('kpiAvailable').textContent = avail;
    document.getElementById('kpiHolding').textContent = hold;
    document.getElementById('kpiBooked').textContent = book;
    document.getElementById('kpiMaintenance').textContent = maint;
    document.getElementById('kpiBlocked').textContent = block;
    document.getElementById('kpiTotal').textContent = slots.length;
}

/* ==================== VIEW 2: MA TRẬN BẢNG LỊCH ==================== */
function renderMatrixView() {
    const table = document.getElementById('matrixTable');
    if (!table) return;

    const subNames = Array.from(new Set(currentSlots.map(s => s.sub_court_name))).sort();
    const timeSlotsSet = Array.from(new Set(currentSlots.map(s => `${s.start_time}-${s.end_time}`))).sort();

    if (subNames.length === 0 || timeSlotsSet.length === 0) {
        table.innerHTML = '<tr><td class="text-center py-4 text-muted">Chưa có khung giờ cho sân và ngày này.</td></tr>';
        return;
    }

    // Map dữ liệu theo `${sub_court_name}|${start_time}-${end_time}`
    const map = new Map();
    currentSlots.forEach(s => {
        map.set(`${s.sub_court_name}|${s.start_time}-${s.end_time}`, s);
    });

    let headerHtml = '<thead><tr>';
    headerHtml += '<th class="matrix-time-header-corner"><i class="fa-regular fa-clock me-1 text-teal"></i>Khung giờ</th>';
    subNames.forEach(name => {
        headerHtml += `<th class="matrix-court-header"><div class="fw-bold text-teal">${AdminApp.escapeHTML(name)}</div></th>`;
    });
    headerHtml += '</tr></thead>';

    let bodyHtml = '<tbody>';
    timeSlotsSet.forEach(tr => {
        bodyHtml += '<tr>';
        bodyHtml += `<td class="matrix-time-cell">${tr}</td>`;

        subNames.forEach(sub => {
            const slot = map.get(`${sub}|${tr}`);
            if (slot) {
                const statusClass = slot.status === 'available' ? 'is-available'
                    : slot.status === 'holding' ? 'is-holding'
                    : slot.status === 'booked' ? 'is-booked'
                    : slot.status === 'maintenance' ? 'is-maintenance'
                    : 'is-blocked';

                const icon = slot.status === 'available' ? '🟢'
                    : slot.status === 'holding' ? '🟡'
                    : slot.status === 'booked' ? '🔴'
                    : slot.status === 'maintenance' ? '🔧'
                    : '⚪';

                const noteText = slot.note ? `<div class="slot-chip-note">${AdminApp.escapeHTML(slot.note)}</div>` : '';
                const bookingText = slot.booking_code ? `<div class="slot-chip-note text-truncate">${AdminApp.escapeHTML(slot.booking_code)}</div>` : '';

                bodyHtml += `
                    <td>
                        <div class="matrix-cell-content ${statusClass}" onclick="openSlotActionModal(${slot.id})" title="${AdminApp.escapeHTML(slot.sub_court_name)} (${slot.start_time}-${slot.end_time}): ${slot.status}">
                            <div class="d-flex align-items-center justify-content-between">
                                <span style="font-size:0.75rem;">${icon}</span>
                                <span class="fw-bold" style="font-size:0.75rem;">${AdminApp.formatVND(slot.price)}</span>
                            </div>
                            ${noteText || bookingText}
                        </div>
                    </td>
                `;
            } else {
                bodyHtml += '<td><div class="matrix-cell-empty">-</div></td>';
            }
        });

        bodyHtml += '</tr>';
    });
    bodyHtml += '</tbody>';

    table.innerHTML = headerHtml + bodyHtml;
}

/* ==================== MODAL CHI TIẾT & THAO TÁC KHUNG GIỜ ==================== */
function openSlotActionModal(slotId) {
    const slot = currentSlots.find(s => s.id === Number(slotId));
    if (!slot) return;

    document.getElementById('modalSlotId').value = slot.id;
    document.getElementById('slotModalTitle').textContent = `${slot.sub_court_name} · ${slot.start_time} - ${slot.end_time}`;
    document.getElementById('slotModalSubtitle').textContent = `Ngày: ${slot.slot_date} · Sân: ${slot.court_name}`;

    const bookedBox = document.getElementById('slotBookedInfo');
    const maintBox = document.getElementById('slotMaintenanceInfo');
    const blockedBox = document.getElementById('slotBlockedInfo');
    const holdingBox = document.getElementById('slotHoldingInfo');
    const availBox = document.getElementById('slotAvailableInfo');

    const editToggleWrap = document.getElementById('slotEditToggleWrap');
    const editFields = document.getElementById('slotEditFields');
    const statusSelect = document.getElementById('modalSlotStatus');
    const noteInput = document.getElementById('modalSlotNote');

    const closeBtn = document.getElementById('btnModalCloseSlot');
    const reopenBtn = document.getElementById('btnModalReopenSlot');
    const saveBtn = document.getElementById('btnModalSaveSlot');

    // Ẩn toàn bộ các box thông tin và điều khiển về mặc định
    bookedBox.style.display = 'none';
    maintBox.style.display = 'none';
    blockedBox.style.display = 'none';
    if (holdingBox) holdingBox.style.display = 'none';
    availBox.style.display = 'none';
    editToggleWrap.style.display = 'none';
    editFields.style.display = 'none';
    reopenBtn.style.display = 'none';
    saveBtn.style.display = 'none';

    noteInput.value = slot.note || '';

    if (slot.status === 'booked') {
        // 1. ĐÃ CÓ KHÁCH ĐẶT: Bỏ nhập lý do, xóa nút lưu ghi chú, chỉ hiện thông tin đặt sân & nút Đóng
        bookedBox.style.display = 'block';

        const statusBadge = document.getElementById('modalBookingStatusBadge');
        if (slot.booking_status === 'completed') {
            statusBadge.className = 'badge bg-success text-white rounded-pill';
            statusBadge.textContent = 'Hoàn thành';
        } else {
            statusBadge.className = 'badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill';
            statusBadge.textContent = 'Đã cọc';
        }

        let detailsHtml = `<div><strong>Mã đơn:</strong> <span class="badge bg-light text-dark border">#${AdminApp.escapeHTML(slot.booking_code || slot.booking_id)}</span></div>`;
        if (slot.customer_name) {
            detailsHtml += `<div><strong>Khách đặt:</strong> ${AdminApp.escapeHTML(slot.customer_name)} ${slot.customer_phone ? `· SĐT: ${AdminApp.escapeHTML(slot.customer_phone)}` : ''}</div>`;
        }
        document.getElementById('modalBookingDetails').innerHTML = detailsHtml;

        closeBtn.className = 'btn btn-outline-secondary rounded-pill py-2 fw-semibold w-100';

    } else if (slot.status === 'holding') {
        // ĐANG GIỮ CHỖ TẠM THỜI
        if (holdingBox) holdingBox.style.display = 'block';
        closeBtn.className = 'btn btn-outline-secondary rounded-pill py-2 fw-semibold w-100';

    } else if (slot.status === 'maintenance') {
        // 2. BẢO TRÌ: Hiện thông tin bảo trì (lý do bảo trì), bỏ phần nhập lý do, nút Đóng & Mở lại nhận khách
        maintBox.style.display = 'block';
        document.getElementById('modalMaintenanceNote').textContent = slot.note || 'Bảo dưỡng định kỳ / Chưa có ghi chú cụ thể';

        editToggleWrap.style.display = 'block';
        statusSelect.value = 'maintenance';

        reopenBtn.style.display = 'block';
        reopenBtn.textContent = 'Mở lại nhận khách';

        closeBtn.className = 'btn btn-outline-secondary rounded-pill py-2 fw-semibold flex-fill';
        reopenBtn.className = 'btn btn-teal rounded-pill py-2 fw-bold flex-fill';

    } else if (slot.status === 'blocked') {
        // 3. KHÓA SÂN: Hiện thông tin sân đóng (lý do đóng sân), bỏ phần nhập lý do, nút Đóng & Mở lại nhận khách
        blockedBox.style.display = 'block';
        document.getElementById('modalBlockedNote').textContent = slot.note || 'Tạm ngừng nhận khách';

        editToggleWrap.style.display = 'block';
        statusSelect.value = 'blocked';

        reopenBtn.style.display = 'block';
        reopenBtn.textContent = 'Mở lại nhận khách';

        closeBtn.className = 'btn btn-outline-secondary rounded-pill py-2 fw-semibold flex-fill';
        reopenBtn.className = 'btn btn-teal rounded-pill py-2 fw-bold flex-fill';

    } else {
        // 4. TRỐNG (Available): Cho phép nhân viên đóng sân hoặc bảo trì khung này
        availBox.style.display = 'block';
        editFields.style.display = 'block';
        statusSelect.value = 'maintenance';

        saveBtn.style.display = 'block';
        saveBtn.textContent = 'Lưu thay đổi';

        closeBtn.className = 'btn btn-outline-secondary rounded-pill py-2 fw-semibold flex-fill';
        saveBtn.className = 'btn btn-teal rounded-pill py-2 fw-bold flex-fill';
    }

    slotActionModal.show();
}

function handleToggleEditSlot() {
    const editFields = document.getElementById('slotEditFields');
    const editToggleWrap = document.getElementById('slotEditToggleWrap');
    const reopenBtn = document.getElementById('btnModalReopenSlot');
    const saveBtn = document.getElementById('btnModalSaveSlot');
    const closeBtn = document.getElementById('btnModalCloseSlot');

    editFields.style.display = 'block';
    editToggleWrap.style.display = 'none';
    reopenBtn.style.display = 'none';
    saveBtn.style.display = 'block';
    saveBtn.textContent = 'Lưu thay đổi';

    closeBtn.className = 'btn btn-outline-secondary rounded-pill py-2 fw-semibold flex-fill';
    saveBtn.className = 'btn btn-teal rounded-pill py-2 fw-bold flex-fill';
}

async function handleModalReopenSlot() {
    const slotId = Number(document.getElementById('modalSlotId').value);
    const slot = currentSlots.find(s => s.id === slotId);
    if (!slot) return;

    const btn = document.getElementById('btnModalReopenSlot');
    btn.disabled = true;

    try {
        await AdminApp.updateSlotApi(slotId, {
            status: 'available',
            note: ''
        });
        slotActionModal.hide();
        AdminApp.toast('success', `Đã mở lại khung giờ ${slot.sub_court_name} (${slot.start_time}-${slot.end_time}) cho khách đặt.`);
        await renderAll();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể mở lại khung giờ.');
    } finally {
        btn.disabled = false;
    }
}

async function handleSaveSlotAction(e) {
    e.preventDefault();
    const slotId = Number(document.getElementById('modalSlotId').value);
    const slot = currentSlots.find(s => s.id === slotId);
    if (!slot) return;

    const note = document.getElementById('modalSlotNote').value.trim();
    const newStatus = document.getElementById('modalSlotStatus')?.value || 'available';

    const btn = document.getElementById('btnModalSaveSlot');
    btn.disabled = true;

    try {
        await AdminApp.updateSlotApi(slotId, {
            status: newStatus,
            note: newStatus === 'available' ? '' : note
        });
        AdminApp.toast('success', 'Đã cập nhật khung giờ thành công');
        slotActionModal.hide();
        await renderAll();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể cập nhật khung giờ.');
    } finally {
        btn.disabled = false;
    }
}

async function handleModalDeleteSlot() {
    const slotId = Number(document.getElementById('modalSlotId').value);
    const slot = currentSlots.find(s => s.id === slotId);
    if (!slot) return;

    const ok = await AdminApp.confirmAction(
        'Xóa khung giờ này?',
        `Khung giờ ${slot.sub_court_name} (${slot.start_time}-${slot.end_time}) sẽ bị xóa khỏi lịch ngày này.`
    );
    if (!ok) return;

    try {
        await AdminApp.deleteSlotApi(slotId);
        slotActionModal.hide();
        AdminApp.toast('success', 'Đã xóa khung giờ thành công');
        await renderAll();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể xóa khung giờ.');
    }
}

async function handleDeleteSlotClick(e, slotId, subName, timeRange) {
    e.stopPropagation();
    const ok = await AdminApp.confirmAction(
        'Xóa khung giờ này?',
        `Khung giờ ${subName} (${timeRange}) sẽ bị xóa khỏi lịch ngày đang chọn.`
    );
    if (!ok) return;

    try {
        await AdminApp.deleteSlotApi(slotId);
        AdminApp.toast('success', 'Đã xóa khung giờ');
        await renderAll();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể xóa khung giờ.');
    }
}

/* ==================== BULK ACTIONS ==================== */
async function handleBulkMaintenanceSubmit(e) {
    e.preventDefault();
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    if (!court || !dateStr) return;

    const subCourt = document.getElementById('bulkMaintSubCourt').value;
    const scope = document.querySelector('input[name="bulkMaintScope"]:checked').value;
    const status = document.getElementById('bulkMaintStatus').value;
    const note = document.getElementById('bulkMaintNote').value.trim();

    let startTime = '';
    let endTime = '';
    if (scope === 'time') {
        startTime = document.getElementById('bulkMaintStart').value;
        endTime = document.getElementById('bulkMaintEnd').value;
        if (startTime >= endTime) {
            AdminApp.toast('warning', 'Giờ kết thúc phải lớn hơn giờ bắt đầu');
            return;
        }
    }

    const btn = document.getElementById('btnSubmitBulkMaint');
    btn.disabled = true;

    try {
        const res = await AdminApp.bulkMaintenanceSlotsApi(court.id, dateStr, {
            subCourtName: subCourt,
            startTime,
            endTime,
            status,
            note
        });
        bulkMaintModal.hide();
        AdminApp.toast('success', res.message || 'Đã áp dụng bảo trì thành công.');
        await renderAll();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể thực hiện bảo trì hàng loạt.');
    } finally {
        btn.disabled = false;
    }
}

async function handleBulkPriceSubmit(e) {
    e.preventDefault();
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    if (!court || !dateStr) return;

    const subCourt = document.getElementById('bulkPriceSubCourt').value;
    const price = Number(document.getElementById('bulkPriceValue').value);

    if (isNaN(price) || price < 0) {
        AdminApp.toast('warning', 'Vui lòng nhập mức giá hợp lệ');
        return;
    }

    const btn = document.getElementById('btnSubmitBulkPrice');
    btn.disabled = true;

    try {
        const res = await AdminApp.bulkPriceSlotsApi(court.id, dateStr, {
            subCourtName: subCourt,
            price
        });
        bulkPriceModal.hide();
        AdminApp.toast('success', res.message || 'Đã cập nhật giá vé thành công.');
        await renderAll();
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể điều chỉnh giá vé hàng loạt.');
    } finally {
        btn.disabled = false;
    }
}

async function handleGenerate(e) {
    e.preventDefault();
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    if (!court) {
        AdminApp.toast('warning', 'Vui lòng nhập tên sân hợp lệ trước khi tạo khung giờ.');
        return;
    }
    if (!dateStr) return;

    const subCourts = Array.from(document.querySelectorAll('#genSubCourtChecks input:checked')).map(c => c.value);
    if (subCourts.length === 0) {
        AdminApp.toast('warning', 'Vui lòng chọn ít nhất 1 sân con');
        return;
    }

    const opts = {
        startTime: document.getElementById('genStart').value,
        endTime: document.getElementById('genEnd').value,
        durationMin: Number(document.getElementById('genDuration').value),
        price: Number(document.getElementById('genPrice').value),
        subCourts
    };

    if (opts.startTime >= opts.endTime) {
        AdminApp.toast('warning', 'Giờ kết thúc phải sau giờ bắt đầu');
        return;
    }

    const btn = document.getElementById('btnSubmitGenerate');
    btn.disabled = true;
    try {
        const res = await AdminApp.generateSlotsApi(court.id, dateStr, opts);
        genModal.hide();
        await renderAll();
        AdminApp.toast('success', res.message || `Đã tạo thêm ${res.data?.createdCount ?? 0} khung giờ mới`);
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể tạo khung giờ hàng loạt.');
    } finally {
        btn.disabled = false;
    }
}

async function handleBulkDelete(e) {
    e.preventDefault();
    const court = getSelectedCourt();
    const dateStr = document.getElementById('slotDateInput').value;
    if (!court) {
        AdminApp.toast('warning', 'Vui lòng chọn sân hợp lệ.');
        return;
    }
    if (!dateStr) return;

    const subCourts = Array.from(document.querySelectorAll('#delSubCourtChecks input:checked')).map(c => c.value);
    if (subCourts.length === 0) {
        AdminApp.toast('warning', 'Vui lòng chọn ít nhất 1 thảm con cần xóa.');
        return;
    }

    const startTime = document.getElementById('delStart').value;
    const endTime = document.getElementById('delEnd').value;
    if (startTime >= endTime) {
        AdminApp.toast('warning', 'Giờ kết thúc phải sau giờ bắt đầu.');
        return;
    }

    const ok = await AdminApp.confirmAction(
        'Xác nhận xóa khung giờ?',
        `Hệ thống sẽ xóa các khung giờ chưa có khách đặt từ ${startTime} đến ${endTime} của các thảm: ${subCourts.join(', ')} (ngày ${dateStr}). Khung giờ đã có khách đặt sẽ được giữ nguyên an toàn.`
    );
    if (!ok) return;

    const btn = document.getElementById('btnSubmitBulkDelete');
    btn.disabled = true;
    try {
        const res = await AdminApp.bulkDeleteSlotsApi(court.id, dateStr, {
            startTime,
            endTime,
            subCourts
        });
        bulkDelModal.hide();
        await renderAll();
        AdminApp.toast('success', res.message || 'Đã xóa các khung giờ thành công.');
    } catch (err) {
        AdminApp.toast('error', err.message || 'Không thể xóa khung giờ.');
    } finally {
        btn.disabled = false;
    }
}
</script>
</body>
</html>
