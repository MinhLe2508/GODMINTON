<?php
/**
 * admin-posts.php
 * Kênh Quản Trị — Quản lý bài viết khám phá (Sự kiện, Gói hội viên ưu đãi, Thông báo).
 * Chỉ Quản trị viên (role = 'admin') mới được truy cập.
 */

require_once dirname(__DIR__) . '/includes/auth-check.php';
header('Cache-Control: no-store, max-age=0');

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('403 — Chỉ Quản trị viên (Admin) mới có quyền truy cập trang quản lý bài viết khám phá.');
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
    <title>Quản Lý Bài Viết Khám Phá | GODMINTON Admin</title>
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

    <style>
        .post-thumb-img {
            width: 58px;
            height: 42px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .thumb-placeholder {
            width: 58px;
            height: 42px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: var(--bg-surface);
            color: var(--brand-teal);
            font-size: 1.1rem;
            border: 1px dashed var(--border-color);
        }
        .preview-box {
            max-height: 180px;
            border-radius: 12px;
            overflow: hidden;
            border: 1.5px dashed var(--border-color);
            background: var(--bg-surface);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .preview-box img {
            max-height: 180px;
            width: 100%;
            object-fit: cover;
        }
        .bg-purple-subtle {
            background-color: rgba(139, 92, 246, 0.12) !important;
        }
        .text-purple {
            color: #7c3aed !important;
        }
        .border-purple-subtle {
            border-color: rgba(139, 92, 246, 0.28) !important;
        }
        [data-bs-theme="dark"] .bg-purple-subtle {
            background-color: rgba(139, 92, 246, 0.2) !important;
        }
        [data-bs-theme="dark"] .text-purple {
            color: #c4b5fd !important;
        }
        [data-bs-theme="dark"] .border-purple-subtle {
            border-color: rgba(139, 92, 246, 0.4) !important;
        }
    </style>

    <script>
        window.__GODMINTON_USER__ = <?php echo json_encode($currentUser, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    </script>
</head>
<body class="admin-body">

<div class="admin-shell">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="admin-sidebar-brand">
            <img src="assets/img/logo.svg" alt="GODMINTON">
            <div>
                <div class="fw-bold text-white" style="font-size:.9rem;">GODMINTON</div>
                <div class="brand-sub">Kênh Quản Trị</div>
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

    <!-- Main Content -->
    <div class="admin-main">
        <header class="admin-topbar">
            <button class="btn btn-outline-secondary admin-sidebar-toggle" id="adminSidebarToggle"><i class="fa-solid fa-bars"></i></button>
            <div class="admin-topbar-title flex-grow-1">
                <div class="admin-breadcrumb" id="adminBreadcrumb">Quản trị hệ thống</div>
                <h1 id="adminPageTitle">Quản lý bài viết khám phá</h1>
            </div>
            <div class="admin-topbar-actions">
                <button id="themeToggle" type="button" class="admin-icon-btn" aria-label="Chuyển giao diện sáng/tối" title="Chuyển giao diện sáng/tối">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button class="btn btn-teal rounded-pill px-3 fw-semibold ms-1" id="btnAddPost" title="Viết bài mới">
                    <i class="fa-solid fa-plus me-1"></i><span class="btn-label-text">Viết bài mới</span>
                </button>
            </div>
        </header>

        <main class="admin-content">
            <div class="admin-panel">
                <div class="admin-panel-head">
                    <h2><i class="fa-solid fa-newspaper text-teal me-2"></i>Danh sách bài viết</h2>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <input type="search" id="searchPost" class="form-control form-control-sm rounded-pill" placeholder="Tìm theo tiêu đề..." style="min-width: 200px;">
                        <select id="filterType" class="form-select form-select-sm rounded-pill" style="min-width: 150px;">
                            <option value="">Tất cả loại bài</option>
                            <option value="event">Sự kiện</option>
                            <option value="membership">Gói hội viên</option>
                            <option value="announcement">Thông báo</option>
                        </select>
                        <select id="filterStatus" class="form-select form-select-sm rounded-pill" style="min-width: 150px;">
                            <option value="">Tất cả trạng thái</option>
                            <option value="published">Đã xuất bản</option>
                            <option value="draft">Bản nháp</option>
                            <option value="archived">Lưu trữ / Ẩn</option>
                        </select>
                    </div>
                </div>

                <div class="admin-panel-body p-0">
                    <div class="table-responsive">
                        <table class="table admin-table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 75px;">Ảnh</th>
                                    <th>Tiêu đề & Slug</th>
                                    <th>Loại bài</th>
                                    <th>Ghim</th>
                                    <th>Trạng thái</th>
                                    <th>Thời hạn hiển thị</th>
                                    <th class="text-end" style="width: 155px;">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody id="postsTableBody">
                                <!-- Rendered by JS -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Empty State -->
                    <div id="postsEmpty" class="text-center text-muted py-5" style="display: none;">
                        <i class="fa-regular fa-folder-open fs-2 d-block mb-2 text-teal"></i>
                        Không có bài viết nào phù hợp với bộ lọc.
                    </div>

                    <!-- Pagination -->
                    <div class="p-3 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" id="postsPaginationWrap" style="display: none;">
                        <span class="small text-muted" id="postsCountText">Hiển thị bài viết</span>
                        <nav aria-label="Phân trang">
                            <ul class="pagination pagination-sm mb-0" id="postsPagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- ==================== MODAL: THÊM / SỬA BÀI VIẾT ==================== -->
<div class="modal fade" id="postModal" tabindex="-1" aria-labelledby="postModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold" id="postModalTitle">Viết bài mới</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <form id="postForm">
                <input type="hidden" id="formPostId">
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Loại bài viết -->
                        <div class="col-md-5">
                            <label for="formPostType" class="form-label fw-semibold small">Loại bài viết <span class="text-danger">*</span></label>
                            <select class="form-select" id="formPostType" required>
                                <option value="event">Sự kiện (Event)</option>
                                <option value="membership">Gói hội viên ưu đãi (Membership)</option>
                                <option value="announcement">Thông báo (Announcement)</option>
                            </select>
                        </div>

                        <!-- Trạng thái bài -->
                        <div class="col-md-4">
                            <label for="formPostStatus" class="form-label fw-semibold small">Trạng thái đăng <span class="text-danger">*</span></label>
                            <select class="form-select" id="formPostStatus" required>
                                <option value="published">Xuất bản ngay (Published)</option>
                                <option value="draft">Lưu bản nháp (Draft)</option>
                                <option value="archived">Lưu trữ / Ẩn (Archived)</option>
                            </select>
                        </div>

                        <!-- Ghim bài viết -->
                        <div class="col-md-3 d-flex align-items-center pt-3">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="formPostPinned">
                                <label class="form-check-label fw-semibold small" for="formPostPinned">Ghim đầu trang</label>
                            </div>
                        </div>

                        <!-- Tiêu đề -->
                        <div class="col-12">
                            <label for="formPostTitle" class="form-label fw-semibold small">Tiêu đề bài viết <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="formPostTitle" required placeholder="Nhập tiêu đề thu hút người đọc..." maxlength="255">
                            <div class="invalid-feedback">Vui lòng nhập tiêu đề bài viết (từ 3 đến 255 ký tự).</div>
                        </div>

                        <!-- Tóm tắt -->
                        <div class="col-12">
                            <label for="formPostSummary" class="form-label fw-semibold small">Tóm tắt ngắn</label>
                            <textarea class="form-control" id="formPostSummary" rows="2" placeholder="Tóm tắt 1-2 câu hiển thị ở danh sách bài viết..."></textarea>
                        </div>

                        <!-- Mã giảm giá đính kèm (Tùy chọn) -->
                        <div class="col-12">
                            <label for="formPostVoucher" class="form-label fw-semibold small">
                                <i class="fa-solid fa-ticket text-teal me-1"></i> Mã giảm giá đính kèm bài viết (Tùy chọn)
                            </label>
                            <select class="form-select" id="formPostVoucher">
                                <option value="">-- Không đính kèm mã giảm giá --</option>
                            </select>
                        </div>

                        <!-- ================= TRƯỜNG ĐẶC THÙ CHO SỰ KIỆN ================= -->
                        <div id="sectionEventFields" class="col-12 p-3 rounded-4 bg-surface border">
                            <h6 class="fw-bold text-teal mb-3"><i class="fa-solid fa-trophy me-1"></i> Thông tin Sự kiện</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="formEventStart" class="form-label fw-semibold small">Thời gian bắt đầu</label>
                                    <input type="datetime-local" class="form-control" id="formEventStart">
                                </div>
                                <div class="col-md-6">
                                    <label for="formEventEnd" class="form-label fw-semibold small">Thời gian kết thúc</label>
                                    <input type="datetime-local" class="form-control" id="formEventEnd">
                                </div>
                                <div class="col-12">
                                    <label for="formEventLocation" class="form-label fw-semibold small">Địa điểm tổ chức</label>
                                    <input type="text" class="form-control" id="formEventLocation" placeholder="VD: Sân Cầu Lông Kỳ Hòa, Quận 10, TP.HCM">
                                </div>
                            </div>
                        </div>

                        <!-- ================= TRƯỜNG ĐẶC THÙ CHO GÓI HỘI VIÊN ================= -->
                        <div id="sectionMembershipFields" class="col-12 p-3 rounded-4 bg-surface border" style="display: none;">
                            <h6 class="fw-bold text-warning mb-3"><i class="fa-solid fa-id-card me-1"></i> Thông tin Gói hội viên ưu đãi (Silver 5%, Gold 10%, Platinum 15%)</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="formMemPrice" class="form-label fw-semibold small">Giá niêm yết (VNĐ)</label>
                                    <input type="number" min="0" step="1000" class="form-control" id="formMemPrice" placeholder="VD: 199000">
                                </div>
                                <div class="col-md-4">
                                    <label for="formMemDuration" class="form-label fw-semibold small">Thời hạn gói (Số ngày)</label>
                                    <input type="number" min="1" class="form-control" id="formMemDuration" placeholder="VD: 30">
                                </div>
                                <div class="col-md-4">
                                    <label for="formMemDiscountPercent" class="form-label fw-semibold small">Giảm giá tiền sân (%) <span class="text-danger">*</span></label>
                                    <input type="number" min="0" max="100" class="form-control" id="formMemDiscountPercent" placeholder="VD: 5, 10 hoặc 15">
                                    <div class="form-text" style="font-size: 0.72rem;">Tự động giảm % này cho đơn đặt sân của hội viên.</div>
                                </div>
                                <div class="col-12">
                                    <label for="formMemBenefits" class="form-label fw-semibold small">Danh sách quyền lợi thành viên (mỗi dòng 1 ý)</label>
                                    <textarea class="form-control" id="formMemBenefits" rows="4" placeholder="Giảm 10% giờ thường từ T2 đến T6&#10;Tặng 2 giờ chơi miễn phí&#10;Miễn phí nước suối và quấn cán"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Nội dung chi tiết -->
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label for="formPostContent" class="form-label fw-semibold small mb-0">Nội dung chi tiết <span class="text-danger">*</span></label>
                                <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.7rem;">Hỗ trợ Markdown cơ bản (### tiêu đề, **đậm**, *nghiêng*, - gạch đầu dòng)</span>
                            </div>
                            <textarea class="form-control" id="formPostContent" rows="6" required placeholder="Nhập nội dung đầy đủ của bài viết..."></textarea>
                            <div class="invalid-feedback">Nội dung chi tiết bài viết không được để trống.</div>
                        </div>

                        <!-- Quản lý Ảnh bìa (Upload file hoặc Nhập URL) -->
                        <div class="col-12">
                            <label class="form-label fw-semibold small mb-1">Ảnh bìa bài viết</label>
                            <div class="row g-2 align-items-start">
                                <div class="col-md-6">
                                    <input type="file" class="form-control" id="formCoverFile" accept="image/jpeg,image/png,image/webp">
                                    <div class="form-text" style="font-size: 0.72rem;">Chấp nhận JPG, PNG, WEBP tối đa 5 MiB.</div>
                                </div>
                                <div class="col-md-6">
                                    <input type="url" class="form-control" id="formCoverUrl" placeholder="Hoặc dán URL ảnh: https://...">
                                    <div class="form-text" style="font-size: 0.72rem;">Nhập đường dẫn trực tiếp nếu dùng ảnh online.</div>
                                </div>
                            </div>
                            <!-- Khung xem trước ảnh -->
                            <div class="preview-box mt-2" id="coverPreviewWrap" style="display: none;">
                                <img src="" id="coverPreviewImg" alt="Xem trước ảnh bìa">
                            </div>
                        </div>

                        <!-- Lịch xuất bản tự động (Tùy chọn) -->
                        <div class="col-md-6">
                            <label for="formPublishAt" class="form-label fw-semibold small">Thời điểm xuất bản (Publish At)</label>
                            <input type="datetime-local" class="form-control" id="formPublishAt">
                            <div class="form-text" style="font-size: 0.72rem;">Để trống để bài hiển thị ngay lập tức khi trạng thái là Xuất bản.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="formExpireAt" class="form-label fw-semibold small">Thời điểm hết hạn tự ẩn (Expire At)</label>
                            <input type="datetime-local" class="form-control" id="formExpireAt">
                            <div class="form-text" style="font-size: 0.72rem;"><span class="text-warning"><i class="fa-solid fa-triangle-exclamation"></i> Chú ý:</span> Bài viết sẽ <strong>tự động ẩn</strong> sau thời điểm này. <strong>Để trống</strong> nếu muốn bài luôn hiển thị.</div>
                        </div>
                        <div class="col-12">
                            <div class="p-3 rounded-4 border bg-teal-subtle d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <div class="fw-bold small text-teal"><i class="fa-solid fa-bell me-1"></i> Gửi thông báo nhắc bài viết mới đến người dùng</div>
                                    <div class="text-muted" style="font-size: 0.75rem;">Tự động đẩy thông báo Web Push, bật chấm đỏ trên mục <strong>Khám phá</strong> và hiện thẻ nhắc bài mới trên trang khách khi bài được xuất bản.</div>
                                </div>
                                <div class="form-check form-switch fs-5 mb-0">
                                    <input class="form-check-input" type="checkbox" role="switch" id="formPostNotify" checked style="cursor:pointer;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" class="btn btn-teal rounded-pill px-4 fw-bold" id="btnSavePost">
                        <span class="spinner-border spinner-border-sm me-1" id="saveSpinner" style="display: none;" role="status"></span>
                        Lưu bài viết
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/swal-godminton.js?v=<?= filemtime(dirname(__DIR__) . '/assets/css/swal-godminton.css') ?>"></script>
<script src="assets/js/booking-account-client.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/booking-account-client.js') ?>"></script>
<script src="assets/js/app.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/app.js') ?>"></script>
<script src="assets/js/admin.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/admin.js') ?>"></script>
<script src="assets/js/admin-posts.js?v=<?= time() ?>"></script>

</body>
</html>
