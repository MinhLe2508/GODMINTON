<?php
/**
 * post-detail.php
 * Trang chi tiết bài viết (Sự kiện, Gói hội viên ưu đãi, Thông báo).
 * Tuân thủ quy tắc hiển thị Mục 2.4: Nếu không đủ điều kiện công khai -> Trả về 404.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/post-service.php';

$slug = isset($_GET['slug']) ? trim((string)$_GET['slug']) : '';
$post = null;

if ($slug !== '') {
    $post = post_get_public_by_slug($pdo, $slug);
}

// Nếu không tìm thấy hoặc bài chưa được xuất bản / đã hết hạn -> Trả về 404
if (!$post) {
    http_response_code(404);
}

$typeLabels = [
    'event'        => ['label' => 'Sự kiện', 'icon' => 'fa-trophy', 'chip_class' => 'chip-event'],
    'membership'   => ['label' => 'Hội viên ưu đãi', 'icon' => 'fa-id-card', 'chip_class' => 'chip-membership'],
    'announcement' => ['label' => 'Thông báo', 'icon' => 'fa-bell', 'chip_class' => 'chip-announcement'],
];

$typeMeta = $post && isset($typeLabels[$post['type']]) ? $typeLabels[$post['type']] : ['label' => 'Bài viết', 'icon' => 'fa-newspaper', 'chip_class' => 'chip-announcement'];
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
            <!-- Khối thông tin Gói hội viên ưu đãi (Mức 1: Giới thiệu quyền lợi, KHÔNG có nút mua) -->
            <div class="highlight-meta-box mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <span class="badge bg-warning bg-opacity-25 text-warning fw-bold px-3 py-1 rounded-pill mb-1">Gói đặc quyền</span>
                        <h4 class="fw-extrabold mb-0"><?= htmlspecialchars($post['title']) ?></h4>
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

                <!-- Lưu ý & Nút phụ xem sân (Tuyệt đối không có luồng mua / thanh toán) -->
                <div class="mt-4 pt-3 border-top border-secondary border-opacity-25 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="small text-muted" style="max-width: 520px;">
                        <i class="fa-solid fa-circle-info me-1 text-teal"></i>
                        <em>Gói hội viên chỉ mang tính chất thông tin chính sách ưu đãi của hệ thống. Bạn có thể xem ngay các cụm sân cầu lông đối tác đang hoạt động.</em>
                    </div>
                    <a href="courts.php" class="btn btn-teal rounded-pill px-4 fw-bold">
                        <i class="fa-solid fa-layer-group me-1"></i> Xem danh sách sân
                    </a>
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
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
</body>
</html>
