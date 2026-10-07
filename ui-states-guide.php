<?php
/**
 * ui-states-guide.php
 * Trang trình diễn (Showcase) và kiểm thử 5 mẫu trạng thái giao diện chuẩn GODMINTON.
 * Dành cho kiểm tra nghiệm thu Task #6 và hỗ trợ Huy, Minh tái sử dụng.
 */
$pageTitle = 'Trình Diễn Mẫu Trạng Thái UI — GODMINTON';
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/ui-states.css">
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/partials/navbar.php'; ?>

<main class="container py-5">
    <div class="mb-4">
        <span class="badge bg-teal px-3 py-2 fs-6 rounded-pill">Task #6 — UI States Component</span>
        <h1 class="fw-extrabold mt-2 mb-2">Trình Diễn & Hướng Dẫn 5 Mẫu Trạng Thái UI</h1>
        <p class="text-muted">Các mẫu giao diện dùng chung được thiết kế tách biệt trong <code>ui-states.css</code> và <code>ui-states.js</code>, sẵn sàng cho Huy & Minh ghép API.</p>
    </div>

    <!-- 1. TOAST NOTIFICATIONS -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-bell text-teal me-2"></i>1. Toast Notifications</h4>
            <p class="text-muted small">Bấm thử các nút bên dưới để xem toast notification xuất hiện:</p>
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-success rounded-pill px-4" onclick="GodmintonUI.toast('success', 'Thành công!', 'Đã giữ slot sân thành công trong 5 phút.')">
                    <i class="fa-solid fa-circle-check me-1"></i> Success Toast
                </button>
                <button class="btn btn-danger rounded-pill px-4" onclick="GodmintonUI.toast('error', 'Lỗi kết nối', 'Không thể nạp dữ liệu từ máy chủ. Thử lại sau.')">
                    <i class="fa-solid fa-circle-xmark me-1"></i> Error Toast
                </button>
                <button class="btn btn-warning text-dark rounded-pill px-4" onclick="GodmintonUI.toast('warning', 'Thời gian sắp hết', 'Slot chỉ còn 60 giây trước khi mở lại cho người khác.')">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> Warning Toast
                </button>
                <button class="btn btn-info text-white rounded-pill px-4" onclick="GodmintonUI.toast('info', 'Thông báo', 'Hệ thống đã cập nhật biểu phí mới.')">
                    <i class="fa-solid fa-circle-info me-1"></i> Info Toast
                </button>
            </div>
        </div>
    </div>

    <!-- 2. BUTTON DISABLED / SUBMITTING -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-hand text-teal me-2"></i>2. Nút Disabled & Loading khi đang gửi</h4>
            <p class="text-muted small">Ngăn người dùng bấm nhiều lần gây trùng lặp giao dịch (double submit):</p>
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-teal rounded-pill px-4 fw-semibold" id="demoSubmitBtn" onclick="testButtonLoading()">
                    <i class="fa-solid fa-paper-plane me-2"></i> Bấm để thử gửi form
                </button>
                <span class="small text-muted" id="demoSubmitNote">(Nút sẽ bị khóa trong 2.5s rồi mở lại)</span>
            </div>
        </div>
    </div>

    <!-- 3. EMPTY STATE -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-inbox text-teal me-2"></i>3. Trạng Thái Rỗng (Empty State)</h4>
            <div id="demoEmptyContainer"></div>
        </div>
    </div>

    <!-- 4. LOADING STATE -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-spinner text-teal me-2"></i>4. Trạng Thái Đang Tải (Spinner & Skeleton)</h4>
            <div class="row g-4">
                <div class="col-md-5">
                    <h6 class="fw-bold text-muted small mb-2">A. SPINNER TRÒN TEAL</h6>
                    <div id="demoLoadingContainer" class="border rounded-4 bg-white p-3"></div>
                </div>
                <div class="col-md-7">
                    <h6 class="fw-bold text-muted small mb-2">B. SKELETON CARDS SHIMMER (3 CỘT)</h6>
                    <div id="demoSkeletonContainer"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- 5. ERROR STATE -->
    <div class="card shadow-sm border-0 rounded-4 mb-4">
        <div class="card-body p-4">
            <h4 class="fw-bold mb-3"><i class="fa-solid fa-triangle-exclamation text-danger me-2"></i>5. Trạng Thái Lỗi (Error State & Field Error)</h4>
            <div id="demoErrorContainer"></div>
            
            <hr class="my-4">
            
            <h6 class="fw-bold text-muted small mb-2">INLINE FIELD ERROR (TRÊN FORM ĐĂNG KÝ / ĐĂNG NHẬP)</h6>
            <div class="p-3 bg-white border rounded-4" style="max-width: 400px;">
                <label class="form-label small fw-bold">Địa chỉ Email</label>
                <input type="text" class="form-control is-invalid-field" value="email_khong_hop_le">
                <div class="auth-field-error">
                    <i class="fa-solid fa-circle-exclamation"></i> Vui lòng nhập địa chỉ email hợp lệ (vd: ten@example.com).
                </div>
            </div>
        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/partials/footer.php'; ?>
<?php include __DIR__ . '/includes/partials/bottom-nav.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/ui-states.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Render demo Empty State
    GodmintonUI.renderEmpty('demoEmptyContainer', {
        icon: 'fa-magnifying-glass',
        title: 'Không tìm thấy sân cầu lông phù hợp',
        desc: 'Không có sân nào phù hợp với bộ lọc khu vực và mức giá đã chọn. Hãy thử nới lỏng bộ lọc.',
        actionText: 'Đặt lại bộ lọc',
        actionClick: () => GodmintonUI.toast('info', 'Đã đặt lại', 'Đang thiết lập lại bộ lọc mặc định.')
    });

    // 2. Render demo Loading
    GodmintonUI.renderLoading('demoLoadingContainer', 'Đang kết nối API thời gian thực...');

    // 3. Render demo Skeleton
    GodmintonUI.renderSkeletons('demoSkeletonContainer', 2);

    // 4. Render demo Error State
    GodmintonUI.renderError('demoErrorContainer', {
        title: 'Mất kết nối máy chủ',
        desc: 'Hệ thống không thể tải dữ liệu slot thời gian thực. Vui lòng thử lại sau vài giây.',
        retryText: 'Thử lại ngay',
        onRetry: () => GodmintonUI.toast('info', 'Thử lại', 'Đang kết nối lại tới máy chủ...')
    });
});

function testButtonLoading() {
    const btn = document.getElementById('demoSubmitBtn');
    GodmintonUI.setButtonLoading(btn, 'Đang gửi dữ liệu...');
    setTimeout(() => {
        GodmintonUI.resetButton(btn);
        GodmintonUI.toast('success', 'Hoàn tất', 'Dữ liệu đã được xử lý xong!');
    }, 2500);
}
</script>
</body>
</html>
