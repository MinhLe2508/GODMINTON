<?php
/**
 * post-detail.php
 * Trang chi tiết bài viết (Sự kiện, Gói hội viên ưu đãi, Thông báo).
 * Tuân thủ quy tắc hiển thị Mục 2.4: Nếu không đủ điều kiện công khai -> Trả về 404.
 */

require_once __DIR__ . '/includes/auth-session.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/post-service.php';
require_once __DIR__ . '/includes/membership-service.php';

god_auth_start();
$currentUserId = (int)($_SESSION['user_id'] ?? 0);

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$post = null;

if ($slug !== '') {
    $post = post_get_public_by_slug($pdo, $slug);
}

// Nếu không tìm thấy hoặc bài chưa được xuất bản / đã hết hạn -> Trả về 404
if (!$post) {
    http_response_code(404);
}

$userActiveMembership = ($currentUserId > 0 && $post && $post['type'] === 'membership') 
    ? membership_get_active_for_user($pdo, $currentUserId) 
    : null;
$isCurrentPackageActive = ($post && $userActiveMembership && (int)$userActiveMembership['post_id'] === (int)$post['id']);

$userPendingMembership = ($currentUserId > 0 && $post && $post['type'] === 'membership') 
    ? membership_get_pending_for_user($pdo, $currentUserId) 
    : null;
$isCurrentPackagePending = ($post && $userPendingMembership && (int)$userPendingMembership['post_id'] === (int)$post['id']);

$typeLabels = [
    'event'        => ['label' => 'Sự kiện', 'icon' => 'fa-trophy', 'chip_class' => 'chip-event'],
    'membership'   => ['label' => 'Hội viên ưu đãi', 'icon' => 'fa-id-card', 'chip_class' => 'chip-membership'],
    'announcement' => ['label' => 'Thông báo', 'icon' => 'fa-bell', 'chip_class' => 'chip-announcement'],
];

$typeMeta = $post && isset($typeLabels[$post['type']]) ? $typeLabels[$post['type']] : ['label' => 'Bài viết', 'icon' => 'fa-newspaper', 'chip_class' => 'chip-announcement'];

$cleanPackageTitle = '';
if ($post && $post['type'] === 'membership') {
    $cleanPackageTitle = match(true) {
        str_contains(strtolower(($post['slug'] ?? '') . ' ' . ($post['title'] ?? '')), 'platinum') || (int)($post['discount_percent'] ?? 0) >= 15 => 'Gói Hội Viên Bạch Kim',
        str_contains(strtolower(($post['slug'] ?? '') . ' ' . ($post['title'] ?? '')), 'gold') || (int)($post['discount_percent'] ?? 0) >= 10 => 'Gói Hội Viên Vàng',
        str_contains(strtolower(($post['slug'] ?? '') . ' ' . ($post['title'] ?? '')), 'silver') || (int)($post['discount_percent'] ?? 0) >= 5 => 'Gói Hội Viên Bạc',
        default => 'Gói Hội Viên'
    };
}
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? htmlspecialchars($post['title']) . ' | GODMINTON' : '404 - Không tìm thấy bài viết | GODMINTON' ?></title>
    <meta name="description" content="<?= $post ? htmlspecialchars($post['summary'] ?? $post['title']) : 'Bài viết không tồn tại.' ?>">
<?php include_once __DIR__ . '/includes/partials/pwa-head.php'; ?>

    <!-- Fonts & Bootstrap -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(__DIR__ . '/assets/css/swal-godminton.css') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">

    <style>
        .post-detail-cover {
            width: 100%;
            max-height: 440px;
            object-fit: cover;
            border-radius: 1.5rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        .highlight-meta-box {
            background: linear-gradient(135deg, rgba(13,148,136,0.08) 0%, rgba(20,184,166,0.02) 100%);
            border: 1.5px solid rgba(13,148,136,0.25);
            border-radius: 1.25rem;
            padding: 1.5rem;
        }
        [data-bs-theme="dark"] .highlight-meta-box {
            background: linear-gradient(135deg, rgba(13,148,136,0.15) 0%, rgba(15,23,42,0.6) 100%);
            border-color: rgba(45,212,191,0.3);
        }
        .benefit-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            margin-bottom: 0.65rem;
            font-size: 0.95rem;
        }
        .benefit-icon {
            color: var(--brand-teal);
            font-size: 1.1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }
        .post-content-body {
            font-size: 1.02rem;
            line-height: 1.8;
            color: var(--text-primary);
        }
        .chip-event { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
        .chip-membership { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .chip-announcement { background: rgba(244, 63, 94, 0.12); color: #e11d48; }
        [data-bs-theme="dark"] .chip-event { color: #c4b5fd; }
        [data-bs-theme="dark"] .chip-membership { color: #fcd34d; }
        [data-bs-theme="dark"] .chip-announcement { color: #fda4af; }
    </style>
</head>
<body>

    <!-- Header Cố Định -->
    <header class="app-brand-head">
        <div class="container text-center">
            <a href="index.php" class="brand-logo-link" aria-label="GODMINTON Trang chủ">
                <img src="assets/img/logo-teal.svg" alt="GODMINTON" class="brand-logo-img">
            </a>
        </div>
    </header>

    <main class="min-vh-100 py-4">
        <div class="container" style="max-width: 880px;">

            <?php if (!$post): ?>
            <!-- 404: Không tìm thấy bài viết hoặc bài đã hết hạn -->
            <div class="text-center py-5 my-5">
                <div class="mb-4">
                    <i class="fa-solid fa-file-circle-xmark text-danger" style="font-size: 4rem;"></i>
                </div>
                <h1 class="fw-extrabold display-6 mb-2">404 — Không tìm thấy bài viết</h1>
                <p class="text-muted mb-4">Bài viết bạn đang tìm không tồn tại, đã hết hạn hiển thị hoặc đã được chuyển vào lưu trữ.</p>
                <a href="explore.php" class="btn btn-teal rounded-pill px-4 py-2 fw-bold">
                    <i class="fa-solid fa-arrow-left me-1"></i> Quay lại Khám phá
                </a>
            </div>

            <?php else: ?>
            <!-- Chi Tiết Bài Viết -->
            
            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="index.php" class="text-decoration-none text-muted">Trang chủ</a></li>
                    <li class="breadcrumb-item"><a href="explore.php" class="text-decoration-none text-muted">Khám phá</a></li>
                    <li class="breadcrumb-item"><a href="explore.php?tab=<?= urlencode($post['type']) ?>" class="text-decoration-none text-teal fw-semibold"><?= htmlspecialchars($typeMeta['label']) ?></a></li>
                    <li class="breadcrumb-item active text-truncate" style="max-width: 250px;" aria-current="page"><?= htmlspecialchars($post['title']) ?></li>
                </ol>
            </nav>

            <!-- Tiêu đề & Metadata -->
            <div class="mb-4">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <span class="badge rounded-pill px-3 py-1 fw-bold text-uppercase <?= $typeMeta['chip_class'] ?>">
                        <i class="fa-solid <?= $typeMeta['icon'] ?> me-1"></i> <?= htmlspecialchars($typeMeta['label']) ?>
                    </span>
                    <?php if (!empty($post['is_pinned'])): ?>
                    <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 rounded-pill px-3 py-1 fw-bold">
                        <i class="fa-solid fa-thumbtack me-1"></i> Ghim đầu
                    </span>
                    <?php endif; ?>
                    <span class="text-muted small ms-auto">
                        <i class="fa-regular fa-calendar me-1"></i> <?= date('d/m/Y', strtotime($post['created_at'])) ?>
                    </span>
                </div>

                <h1 class="fw-extrabold mb-3 text-break" style="font-size: 2rem; line-height: 1.35;">
                    <?= htmlspecialchars($post['title']) ?>
                </h1>

                <?php if (!empty($post['summary'])): ?>
                <div class="lead text-muted fs-6 mb-4" style="line-height: 1.7;">
                    <?= htmlspecialchars($post['summary']) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Ảnh bìa bài viết nếu có -->
            <?php if (!empty($post['cover_image'])): ?>
            <div class="mb-4 text-center">
                <img src="<?= htmlspecialchars($post['cover_image']) ?>" alt="<?= htmlspecialchars($post['title']) ?>" class="post-detail-cover">
            </div>
            <?php endif; ?>

            <!-- ==================== KHỐI ĐẶC THÙ THEO LOẠI BÀI ==================== -->

            <?php if ($post['type'] === 'event'): ?>
            <!-- Khối thông tin Sự kiện -->
            <div class="highlight-meta-box mb-4">
                <h5 class="fw-bold mb-3 text-teal"><i class="fa-solid fa-calendar-days me-2"></i>Thông tin sự kiện</h5>
                <div class="row g-3">
                    <?php if (!empty($post['event_start'])): ?>
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1"><i class="fa-regular fa-clock me-1 text-teal"></i>Khởi tranh</div>
                        <div class="fw-bold fs-6"><?= date('H:i, \n\gà\y d/m/Y', strtotime($post['event_start'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($post['event_end'])): ?>
                    <div class="col-sm-6">
                        <div class="small text-muted mb-1"><i class="fa-solid fa-flag-checkered me-1 text-teal"></i>Bế mạc</div>
                        <div class="fw-bold fs-6"><?= date('H:i, \n\gà\y d/m/Y', strtotime($post['event_end'])) ?></div>
                    </div>
                    <?php endif; ?>

                    <?php if (!empty($post['event_location'])): ?>
                    <div class="col-12 pt-2 border-top border-teal border-opacity-25">
                        <div class="small text-muted mb-1"><i class="fa-solid fa-location-dot me-1 text-danger"></i>Địa điểm tổ chức</div>
                        <div class="fw-semibold"><?= htmlspecialchars($post['event_location']) ?></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif ($post['type'] === 'membership'): ?>
            <!-- Khối thông tin Gói hội viên ưu đãi: Silver 5%, Gold 10%, Platinum 15% -->
            <div class="highlight-meta-box mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                            <span class="badge bg-warning bg-opacity-25 text-warning fw-bold px-3 py-1 rounded-pill">
                                <i class="fa-solid fa-crown me-1"></i> Gói đặc quyền
                            </span>
                            <?php if (!empty($post['discount_percent'])): ?>
                            <span class="badge bg-danger text-white fw-bold px-3 py-1 rounded-pill">
                                <i class="fa-solid fa-percent me-1"></i> GIẢM <?= (int)$post['discount_percent'] ?>% TIỀN SÂN
                            </span>
                            <?php endif; ?>
                        </div>
                        <h4 class="fw-extrabold mb-0"><?= htmlspecialchars($cleanPackageTitle ?: $post['title']) ?></h4>
                    </div>
                    <?php if ($post['price'] !== null): ?>
                    <div class="text-sm-end">
                        <div class="fs-3 fw-extrabold text-teal"><?= post_format_vnd($post['price']) ?></div>
                        <div class="small text-muted"><?= !empty($post['duration_days']) ? 'Thời hạn ' . (int)$post['duration_days'] . ' ngày' : 'Gói hội viên' ?></div>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($post['benefits'])): ?>
                <div class="mt-3 pt-3 border-top border-teal border-opacity-25">
                    <h6 class="fw-bold mb-3"><i class="fa-solid fa-gift me-2 text-warning"></i>Danh sách quyền lợi thành viên:</h6>
                    <div class="benefits-list">
                        <?php 
                        $benefitsLines = preg_split('/\r\n|\r|\n/', trim($post['benefits']));
                        foreach ($benefitsLines as $bLine): 
                            $bLine = trim($bLine);
                            if ($bLine === '') continue;
                        ?>
                        <div class="benefit-item">
                            <i class="fa-solid fa-circle-check benefit-icon"></i>
                            <div><?= htmlspecialchars($bLine) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Trạng thái kích hoạt & Nút Đăng Ký / Gia Hạn -->
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <?php if (!$currentUserId): ?>
                        <div class="small text-muted" style="max-width: 520px;">
                            <i class="fa-solid fa-circle-info me-1 text-teal"></i>
                            Đăng nhập tài khoản GODMINTON để đăng ký gói hội viên và tự động hưởng chiết khấu <strong><?= (int)$post['discount_percent'] ?>%</strong> khi đặt sân.
                        </div>
                        <a href="login.php?redirect=<?= urlencode('post-detail.php?slug=' . $post['slug']) ?>" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm">
                            <i class="fa-solid fa-right-to-bracket me-1"></i> Đăng nhập để đăng ký gói
                        </a>
                    <?php elseif ($isCurrentPackageActive): ?>
                        <div>
                            <div class="badge bg-success bg-opacity-10 text-success border border-success p-2 rounded-3 d-inline-flex align-items-center gap-2">
                                <i class="fa-solid fa-circle-check fs-5"></i>
                                <div class="text-start">
                                    <strong>Bạn đang kích hoạt gói này!</strong>
                                    <div class="small opacity-75">Còn <?= (int)$userActiveMembership['days_left'] ?> ngày (hạn đến <?= date('d/m/Y', strtotime($userActiveMembership['end_date'])) ?>)</div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <a href="courts.php" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fa-solid fa-table-tennis-paddle-ball me-1"></i> Đặt sân ngay (Giảm <?= (int)$post['discount_percent'] ?>%)
                            </a>
                            <a href="profile.php?tab=membership" class="btn btn-outline-teal rounded-pill px-3 fw-bold">
                                <i class="fa-solid fa-crown text-warning me-1"></i> Quản lý & Gia hạn tại Trang cá nhân
                            </a>
                        </div>
                    <?php elseif ($isCurrentPackagePending): ?>
                        <div class="small text-muted" style="max-width: 520px;">
                            <span class="badge bg-warning text-dark px-2 py-1 rounded-pill fw-bold mb-1">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i>Đang chờ admin xác nhận
                            </span>
                            <div>Bạn đã gửi yêu cầu đăng ký gói này. Ban Quản Trị đang duyệt và sẽ kích hoạt ngay cho bạn.</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <a href="profile.php?tab=membership" class="btn btn-warning text-dark rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fa-solid fa-clock-rotate-left me-1"></i> Theo dõi tại Trang cá nhân
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="small text-muted" style="max-width: 520px;">
                            <i class="fa-solid fa-bolt me-1 text-warning"></i>
                            <?php if ($userActiveMembership): ?>
                                Bạn đang dùng gói <strong><?= htmlspecialchars($userActiveMembership['package_title']) ?></strong>. Đăng ký gói này sẽ nâng cấp tài khoản lên mức giảm <strong><?= (int)$post['discount_percent'] ?>%</strong>.
                            <?php else: ?>
                                Kích hoạt ngay để được tự động giảm <strong><?= (int)$post['discount_percent'] ?>%</strong> toàn bộ tiền sân trong <strong><?= (int)$post['duration_days'] ?> ngày</strong>.
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-teal rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="openSubscribeModal()">
                                <i class="fa-solid fa-crown me-1"></i> Đăng ký gói ngay (<?= post_format_vnd($post['price']) ?>)
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif ($post['type'] === 'announcement'): ?>
            <!-- Khối biểu ngữ Thông báo chính thức -->
            <div class="p-3 rounded-4 mb-4 bg-surface border border-secondary border-opacity-25 d-flex align-items-center gap-3">
                <div class="icon-circle bg-danger bg-opacity-10 text-danger flex-shrink-0" style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>
                <div class="small text-muted">
                    <strong class="text-body d-block">Thông báo chính thức từ Ban Quản Trị GODMINTON</strong>
                    Vui lòng theo dõi và tuân thủ các quy định để có trải nghiệm đặt sân tốt nhất.
                </div>
            </div>
            <?php endif; ?>

            <!-- Nội dung chi tiết bài viết (Render an toàn chống XSS) -->
            <div class="post-content-body mb-5 p-3 p-md-4 rounded-4 bg-surface border">
                <?= post_render_content($post['content']) ?>
            </div>

            <!-- Nút điều hướng chân trang -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top">
                <a href="explore.php?tab=<?= urlencode($post['type']) ?>" class="btn btn-outline-secondary rounded-pill px-4 fw-semibold">
                    <i class="fa-solid fa-arrow-left me-1"></i> Quay lại <?= htmlspecialchars($typeMeta['label']) ?>
                </a>
                <a href="explore.php" class="btn btn-teal rounded-pill px-4 fw-semibold">
                    Xem tất cả bài viết <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>

            <?php endif; ?>

        </div>
    </main>

    <!-- Footer -->
    <footer class="footer-custom mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="mb-3"><img src="assets/img/logo.svg" alt="GODMINTON" style="height:38px; width:auto;"></div>
                    <p class="text-muted-light small">Nền tảng đặt sân cầu lông trực tuyến thông minh.</p>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Liên Kết Nhanh</h6>
                    <ul class="list-unstyled footer-links small">
                        <li><a href="index.php">Trang chủ</a></li>
                        <li><a href="courts.php">Tìm kiếm sân</a></li>
                        <li><a href="explore.php">Khám phá</a></li>
                        <li><a href="contact.php">Hỗ trợ</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary opacity-25 my-4">
            <div class="small text-muted-light">&copy; 2026 GODMINTON — Đồ án Lập trình Web.</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>

    <?php if ($post && $post['type'] === 'membership'): ?>
    <!-- Modal Đăng Ký / Thanh Toán Gói Hội Viên -->
    <div class="modal fade" id="modalSubscribeMembership" tabindex="-1" aria-labelledby="modalSubLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="modalSubLabel">
                        <i class="fa-solid fa-crown text-warning me-2"></i>Đăng Ký Gói Hội Viên
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body pt-3">
                    <!-- Tóm tắt gói -->
                    <div class="p-3 rounded-3 mb-3 bg-light border border-secondary border-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-1">
                            <span class="badge bg-teal text-white fw-bold px-3 py-1 rounded-pill"><?= htmlspecialchars($cleanPackageTitle ?: $post['title']) ?></span>
                            <span class="badge bg-danger text-white fw-bold px-2 py-1 rounded-pill">-<?= (int)$post['discount_percent'] ?>% Tiền Sân</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="text-muted small">Thời hạn kích hoạt:</span>
                            <span class="fw-bold"><?= (int)$post['duration_days'] ?> ngày</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted small">Tổng thanh toán:</span>
                            <span class="fs-4 fw-extrabold text-teal"><?= post_format_vnd($post['price']) ?></span>
                        </div>
                    </div>

                    <!-- Phương thức thanh toán -->
                    <label class="form-label fw-bold small text-muted text-uppercase mb-2">Chọn phương thức thanh toán</label>
                    <div class="d-flex flex-column gap-2 mb-3">
                        <label class="p-2 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-surface">
                            <input type="radio" name="subPaymentMethod" value="VietQR" checked class="form-check-input mt-0">
                            <i class="fa-solid fa-qrcode text-teal fs-4"></i>
                            <div>
                                <div class="fw-bold small">Chuyển khoản VietQR (Mô phỏng tức thì)</div>
                                <div class="text-muted" style="font-size:0.75rem;">Hỗ trợ quét qua mọi App ngân hàng Việt Nam</div>
                            </div>
                        </label>
                        <label class="p-2 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-surface">
                            <input type="radio" name="subPaymentMethod" value="MoMo" class="form-check-input mt-0">
                            <i class="fa-solid fa-wallet text-danger fs-4"></i>
                            <div>
                                <div class="fw-bold small">Ví điện tử MoMo</div>
                                <div class="text-muted" style="font-size:0.75rem;">Thanh toán nhanh chóng</div>
                            </div>
                        </label>
                        <label class="p-2 border rounded-3 d-flex align-items-center gap-3 cursor-pointer bg-surface">
                            <input type="radio" name="subPaymentMethod" value="Card" class="form-check-input mt-0">
                            <i class="fa-solid fa-credit-card text-primary fs-4"></i>
                            <div>
                                <div class="fw-bold small">Thẻ Visa / Mastercard</div>
                                <div class="text-muted" style="font-size:0.75rem;">Thẻ ghi nợ hoặc tín dụng</div>
                            </div>
                        </label>
                    </div>

                    <!-- Ghi chú duyệt kích hoạt -->
                    <div class="p-2 rounded-3 border bg-light text-center small text-muted">
                        <i class="fa-solid fa-clock-rotate-left text-warning me-1"></i>
                        Yêu cầu đăng ký sẽ được gửi tới Ban Quản Trị và tự động kích hoạt sau khi Admin duyệt xác nhận.
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                    <button type="button" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm" id="btnConfirmSubscribe" onclick="submitSubscribe(<?= (int)$post['id'] ?>)">
                        <span id="btnSubText"><i class="fa-solid fa-paper-plane me-1"></i> Gửi yêu cầu đăng ký</span>
                        <span id="btnSubSpinner" class="spinner-border spinner-border-sm ms-1" style="display:none;" role="status"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let subModalInstance = null;
    function openSubscribeModal() {
        const modalEl = document.getElementById('modalSubscribeMembership');
        if (!modalEl) return;
        subModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        subModalInstance.show();
    }

    async function submitSubscribe(postId) {
        const btn = document.getElementById('btnConfirmSubscribe');
        const text = document.getElementById('btnSubText');
        const spinner = document.getElementById('btnSubSpinner');
        const methodEl = document.querySelector('input[name="subPaymentMethod"]:checked');
        const method = methodEl ? methodEl.value : 'VietQR';

        btn.disabled = true;
        text.style.display = 'none';
        spinner.style.display = 'inline-block';

        try {
            const res = await fetch('api/memberships/subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId, payment_method: method })
            });

            const json = await res.json();
            if (res.ok && json.status === 'success') {
                if (subModalInstance) subModalInstance.hide();
                await Swal.fire({
                    icon: 'info',
                    title: 'Đã Gửi Yêu Cầu Đăng Ký!',
                    html: `Yêu cầu đăng ký <strong>${json.data.package_title}</strong> của bạn đã được gửi thành công và <strong>đang chờ Quản trị viên (Admin) xác nhận</strong>.<br>Hệ thống sẽ kích hoạt tự động ngay sau khi Admin duyệt. Bạn có thể theo dõi trong Trang cá nhân.`,
                    confirmButtonText: 'Đã hiểu',
                    confirmButtonColor: '#0d9488'
                });
                window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Đăng ký thất bại',
                    text: json.message || 'Không thể đăng ký gói lúc này. Vui lòng thử lại sau.',
                    confirmButtonColor: '#0d9488'
                });
            }
        } catch (e) {
            console.error('Lỗi đăng ký hội viên:', e);
            Swal.fire({
                icon: 'error',
                title: 'Lỗi kết nối',
                text: 'Không thể kết nối tới máy chủ. Vui lòng kiểm tra lại đường truyền.',
                confirmButtonColor: '#0d9488'
            });
        } finally {
            btn.disabled = false;
            text.style.display = 'inline';
            spinner.style.display = 'none';
        }
    }
    </script>
    <?php endif; ?>
</body>
</html>
