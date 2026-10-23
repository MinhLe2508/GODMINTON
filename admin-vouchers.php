<?php
require_once __DIR__ . '/includes/auth-check.php';
header('Cache-Control: no-store, max-age=0');
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
    <title>Quản Lý Mã Giảm Giá | GODMINTON</title>
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
                <div class="brand-sub"><?= $currentUser['role'] === 'admin' ? 'Kênh Quản Trị Viên' : 'Kênh Chủ Sân' ?></div>
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
                <div class="admin-breadcrumb" id="adminBreadcrumb"><?= $currentUser['role'] === 'admin' ? 'Quản trị hệ thống' : 'Kênh chủ sân' ?></div>
                <h1 id="adminPageTitle">Quản lý mã giảm giá</h1>
            </div>
            <div class="admin-topbar-actions">
                <button id="themeToggle" type="button" class="admin-icon-btn" aria-label="Chuyển giao diện sáng/tối" title="Chuyển giao diện sáng/tối">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button class="btn btn-teal rounded-pill px-3 fw-semibold ms-1" id="btnAddVoucher">
                    <i class="fa-solid fa-plus me-1"></i> Tạo mã giảm giá
                </button>
            </div>
        </header>

        <main class="admin-content">
            <div class="admin-panel">
                <div class="admin-panel-head">
                    <h2><i class="fa-solid fa-ticket text-teal me-2"></i>Danh sách mã giảm giá</h2>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="search" id="searchVoucher" class="form-control form-control-sm rounded-pill" placeholder="Tìm theo mã..." style="min-width:200px;">
                        <select id="filterStatus" class="form-select form-select-sm rounded-pill" style="min-width:160px;">
                            <option value="">Tất cả trạng thái</option>
                            <option value="active">Đang kích hoạt</option>
                            <option value="disabled">Đã tạm tắt</option>
                        </select>
                    </div>
                </div>
                <div class="admin-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Mã giảm giá</th>
                                    <th>Mức giảm</th>
                                    <th>Giảm tối đa</th>
                                    <th>Đơn tối thiểu</th>
                                    <th>Hiệu lực</th>
                                    <th>Lượt dùng</th>
                                    <th>Phạm vi áp dụng</th>
                                    <?php if ($currentUser['role'] === 'admin'): ?>
                                    <th>Người tạo</th>
                                    <th>Bên chịu</th>
                                    <?php endif; ?>
                                    <th>Trạng thái</th>
                                    <th class="text-end">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="vouchersTableBody"></tbody>
                        </table>
                    </div>
                    <div id="vouchersEmpty" class="text-center text-muted py-5" style="display:none;">
                        <i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i> Không có mã giảm giá nào phù hợp.
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Modal Tạo / Sửa Mã Giảm Giá -->
<div class="modal fade" id="voucherModal" tabindex="-1" aria-labelledby="voucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="voucherModalLabel">Tạo mã giảm giá mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="voucherForm" novalidate>
                <div class="modal-body">
                    <input type="hidden" id="voucherId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="formCode" class="form-label small fw-semibold">Mã giảm giá <span class="text-danger">*</span></label>
                            <input type="text" class="form-control text-uppercase" id="formCode" placeholder="VD: TET2026, SUMMER10" required maxlength="50">
                            <div class="invalid-feedback">Vui lòng nhập mã từ 3-50 ký tự (chữ cái, số).</div>
                        </div>
                        <div class="col-md-6">
                            <label for="formPercent" class="form-label small fw-semibold">Phần trăm giảm (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="formPercent" min="1" max="<?= $currentUser['role'] === 'owner' ? 50 : 100 ?>" placeholder="VD: 15" required>
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="form-text small text-muted">
                                <?= $currentUser['role'] === 'owner' ? 'Chủ sân được tạo tối đa 50%.' : 'Quản trị viên có thể tạo từ 1% đến 100%.' ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="formMaxDiscount" class="form-label small fw-semibold">
                                Giảm tối đa (VNĐ) <?= $currentUser['role'] === 'admin' ? '<span class="text-danger">*</span>' : '<span class="text-muted fw-normal">(tùy chọn)</span>' ?>
                            </label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="formMaxDiscount" min="1000" step="1000" placeholder="VD: 50000" <?= $currentUser['role'] === 'admin' ? 'required' : '' ?>>
                                <span class="input-group-text">đ</span>
                            </div>
                            <div class="form-text small text-muted">Mã của Quản trị viên bắt buộc có mức giảm tối đa.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="formMinOrder" class="form-label small fw-semibold">Đơn tối thiểu (VNĐ)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="formMinOrder" min="0" step="10000" placeholder="0">
                                <span class="input-group-text">đ</span>
                            </div>
                            <div class="form-text small text-muted">Giá trị đơn tối thiểu để mã có hiệu lực.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="formStartAt" class="form-label small fw-semibold">Bắt đầu từ <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="formStartAt" required>
                        </div>
                        <div class="col-md-6">
                            <label for="formEndAt" class="form-label small fw-semibold">Hết hạn vào <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="formEndAt" required>
                        </div>

                        <div class="col-md-6">
                            <label for="formUsageLimit" class="form-label small fw-semibold">Tổng lượt dùng tối đa <span class="text-muted fw-normal">(tùy chọn)</span></label>
                            <input type="number" class="form-control" id="formUsageLimit" min="1" placeholder="Để trống = Không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label for="formPerUserLimit" class="form-label small fw-semibold">Lượt dùng tối đa / khách</label>
                            <input type="number" class="form-control" id="formPerUserLimit" min="1" value="1">
                        </div>

                        <div class="col-md-12">
                            <label for="formCourtId" class="form-label small fw-semibold">Phạm vi áp dụng</label>
                            <select class="form-select" id="formCourtId">
                                <option value="">Tất cả sân <?= $currentUser['role'] === 'owner' ? 'do tôi quản lý' : 'trên hệ thống' ?></option>
                            </select>
                        </div>

                        <?php if ($currentUser['role'] === 'admin'): ?>
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="formFirstBookingOnly">
                                <label class="form-check-label small fw-semibold" for="formFirstBookingOnly">
                                    Chỉ áp dụng cho đơn đặt sân đầu tiên của khách (First booking only)
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Hủy bỏ</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4" id="btnSaveVoucher">
                        <span id="btnSaveVoucherText">Lưu mã</span>
                        <span id="btnSaveVoucherSpinner" class="spinner-border spinner-border-sm ms-1" role="status" aria-hidden="true" style="display:none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/swal-godminton.js?v=<?= filemtime(__DIR__ . '/assets/js/swal-godminton.js') ?>"></script>
<script src="assets/js/booking-account-client.js"></script>
<script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
<script src="assets/js/admin.js?v=<?= filemtime(__DIR__ . '/assets/js/admin.js') ?>"></script>
<script src="assets/js/admin-vouchers.js"></script>
</body>
</html>
