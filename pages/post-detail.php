<?php
/**
 * post-detail.php
 * Trang chi tiết bài viết (Sự kiện, Gói hội viên ưu đãi, Thông báo).
 * Tuân thủ quy tắc hiển thị Mục 2.4: Nếu không đủ điều kiện công khai -> Trả về 404.
 */

require_once dirname(__DIR__) . '/includes/auth-session.php';
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/post-service.php';
require_once dirname(__DIR__) . '/includes/membership-service.php';

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

// Thông tin mã giảm giá tặng kèm bài viết (nếu có)
$postVoucher = null;
$userHasCollectedVoucher = false;
if ($post && !empty($post['voucher_id']) && !empty($post['voucher_code']) && ($post['voucher_status'] ?? '') === 'active') {
    $postVoucher = [
        'id'               => (int)$post['voucher_id'],
        'code'             => $post['voucher_code'],
        'discount_percent' => (int)$post['voucher_discount_percent'],
        'max_discount'     => $post['voucher_max_discount'] !== null ? (int)$post['voucher_max_discount'] : null,
        'min_order'        => (int)$post['voucher_min_order'],
        'end_at'           => $post['voucher_end_at'],
        'court_name'       => $post['voucher_court_name'] ?: 'Áp dụng cho mọi sân'
    ];

    if ($currentUserId > 0) {
        $chkStmt = $pdo->prepare('SELECT 1 FROM user_vouchers WHERE user_id = ? AND voucher_id = ?');
        $chkStmt->execute([$currentUserId, (int)$post['voucher_id']]);
        $userHasCollectedVoucher = (bool)$chkStmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? htmlspecialchars($post['title']) . ' | GODMINTON' : '404 - Không tìm thấy bài viết | GODMINTON' ?></title>
    <meta name="description" content="<?= $post ? htmlspecialchars($post['summary'] ?? $post['title']) : 'Bài viết không tồn tại.' ?>">
<?php include_once dirname(__DIR__) . '/includes/partials/pwa-head.php'; ?>

    <!-- Fonts & Bootstrap -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/swal-godminton.css') ?>">
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(dirname(__DIR__) . '/assets/css/style.css') ?>">

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
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.4; transform: scale(0.85); }
        }
        .sepay-wait-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #0d9488;
            margin-right: 6px;
            animation: pulseDot 1.4s infinite ease-in-out;
        }
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
                        <h4 class="fw-extrabold mb-0"><?= htmlspecialchars($post['title']) ?></h4>
                    </div>
                    <?php if ($post['price'] !== null): ?>
                    <div class="text-sm-end">
                        <div class="fs-3 fw-extrabold text-teal"><?= post_format_vnd($post['price']) ?></div>
                        <div class="small text-muted"><?= (!empty($post['duration_days']) && (int)$post['duration_days'] === 30) ? 'Thời hạn 1 tháng' : (!empty($post['duration_days']) ? 'Thời hạn ' . (int)$post['duration_days'] . ' ngày' : 'Gói hội viên') ?></div>
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
                        <a href="auth.php?redirect=<?= urlencode('post-detail.php?slug=' . $post['slug']) ?>" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm">
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
                                <i class="fa-solid fa-qrcode me-1"></i>Đang chờ thanh toán VietQR (SePay 24/7)
                            </span>
                            <div>Bạn đang có đơn đăng ký gói này. Quét mã VietQR để hệ thống tự động kích hoạt gói ngay lập tức!</div>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-teal rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="openSubscribeModal(true)">
                                <i class="fa-solid fa-qrcode me-1"></i> Tiếp tục thanh toán QR (<?= post_format_vnd($post['price']) ?>)
                            </button>
                            <a href="profile.php?tab=membership" class="btn btn-outline-secondary rounded-pill px-3 fw-semibold">
                                <i class="fa-solid fa-user me-1"></i> Trang cá nhân
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="small text-muted" style="max-width: 520px;">
                            <i class="fa-solid fa-bolt me-1 text-warning"></i>
                            <?php if ($userActiveMembership): ?>
                                Bạn đang dùng gói <strong><?= htmlspecialchars($userActiveMembership['package_title']) ?></strong>. Đăng ký gói này sẽ nâng cấp tài khoản lên mức giảm <strong><?= (int)$post['discount_percent'] ?>%</strong>.
                            <?php else: ?>
                                Kích hoạt ngay để được tự động giảm <strong><?= (int)$post['discount_percent'] ?>%</strong> toàn bộ tiền sân trong <strong><?= (int)$post['duration_days'] === 30 ? '1 tháng' : (int)$post['duration_days'] . ' ngày' ?></strong>.
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <button type="button" class="btn btn-teal rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="openSubscribeModal(false)">
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

            <?php if ($postVoucher): ?>
            <!-- ==================== KHỐI MÃ GIẢM GIÁ ĐÍNH KÈM BÀI VIẾT ==================== -->
            <div class="voucher-reward-box p-4 mb-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, rgba(13,148,136,0.08) 0%, rgba(20,184,166,0.18) 100%); border: 2px dashed var(--brand-teal, #0d9488);">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-teal text-white rounded-pill px-3 py-1 fw-bold">
                            <i class="fa-solid fa-gift me-1"></i> MÃ GIẢM GIÁ TẶNG KÈM
                        </span>
                        <span class="badge bg-danger text-white rounded-pill px-3 py-1 fw-bold">
                            <i class="fa-solid fa-bolt me-1"></i> GIẢM <?= (int)$postVoucher['discount_percent'] ?>%
                        </span>
                    </div>
                    <div class="small text-muted">
                        <i class="fa-regular fa-clock me-1 text-teal"></i> Hạn dùng: <?= date('d/m/Y', strtotime($postVoucher['end_at'])) ?>
                    </div>
                </div>

                <div class="row align-items-center g-3">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <div class="fs-4 fw-extrabold text-teal font-monospace tracking-wide text-uppercase" id="displayVoucherCode"><?= htmlspecialchars($postVoucher['code']) ?></div>
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-2 py-0 copy-code-btn" data-code="<?= htmlspecialchars($postVoucher['code']) ?>" title="Sao chép mã">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        </div>
                        <div class="small text-muted">
                            <div><i class="fa-solid fa-shield-halved text-teal me-1"></i> <?= $postVoucher['max_discount'] ? 'Tối đa ' . number_format($postVoucher['max_discount'], 0, ',', '.') . 'đ' : 'Không giới hạn mức giảm' ?> · <?= $postVoucher['min_order'] > 0 ? 'Đơn từ ' . number_format($postVoucher['min_order'], 0, ',', '.') . 'đ' : 'Mọi giá trị đơn' ?></div>
                            <div><i class="fa-solid fa-location-dot text-teal me-1"></i> <?= htmlspecialchars($postVoucher['court_name']) ?></div>
                        </div>
                    </div>

                    <div class="col-md-5 text-md-end text-start">
                        <div id="voucherCollectActionWrap">
                            <?php if (!$currentUserId): ?>
                                <a href="auth.php?redirect=<?= urlencode('post-detail.php?slug=' . $post['slug']) ?>" class="btn btn-teal rounded-pill px-4 py-2 fw-bold shadow-sm">
                                    <i class="fa-solid fa-right-to-bracket me-1"></i> Đăng nhập để nhận mã
                                </a>
                            <?php elseif ($userHasCollectedVoucher): ?>
                                <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm" disabled>
                                    <i class="fa-solid fa-circle-check me-1"></i> Đã lưu vào kho mã
                                </button>
                                <div class="mt-2">
                                    <a href="profile.php?tab=vouchers" class="small text-teal text-decoration-none fw-semibold">
                                        Xem trong kho của bạn <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php else: ?>
                                <button type="button" class="btn btn-teal rounded-pill px-4 py-2 fw-bold shadow-sm btn-collect-voucher" data-voucher-id="<?= (int)$postVoucher['id'] ?>" data-post-id="<?= (int)$post['id'] ?>">
                                    <span class="spinner-border spinner-border-sm me-1 collect-spinner" style="display:none;"></span>
                                    <i class="fa-solid fa-gift me-1 collect-icon"></i> <span class="collect-text">Bấm nhận mã vào kho</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
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
    <script src="assets/js/app.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/app.js') ?>"></script>
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(dirname(__DIR__) . '/assets/js/bottom-nav.js') ?>"></script>

    <?php if ($post && $post['type'] === 'membership'): 
        $pkgShortTitle = 'Gói Hội Viên';
        $disc = (int)($post['discount_percent'] ?? 0);
        $slug = (string)($post['slug'] ?? '');
        if ($disc >= 15 || str_contains($slug, 'platinum')) {
            $pkgShortTitle = 'Gói Hội Viên Bạch Kim';
        } elseif ($disc >= 10 || str_contains($slug, 'gold') || str_contains($slug, 'golden')) {
            $pkgShortTitle = 'Gói Hội Viên Vàng';
        } elseif ($disc >= 5 || str_contains($slug, 'silver')) {
            $pkgShortTitle = 'Gói Hội Viên Bạc';
        } else {
            $raw = trim(explode('–', explode('-', explode('(', (string)$post['title'])[0])[0])[0]);
            $pkgShortTitle = !str_starts_with(mb_strtolower($raw), 'gói') ? ('Gói ' . $raw) : $raw;
        }
        $bankInfo = payment_public_bank();
    ?>
    <!-- Modal Đăng Ký / Thanh Toán Gói Hội Viên (Tích hợp SePay VietQR 24/7 tự động) -->
    <div class="modal fade" id="modalSubscribeMembership" tabindex="-1" aria-labelledby="modalSubLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <!-- BƯỚC 1: XÁC NHẬN ĐĂNG KÝ & TẠO MÃ VIETQR SEPAY -->
                <div id="subStep1">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold" id="modalSubLabel">
                            <i class="fa-solid fa-crown text-warning me-2"></i>Đăng Ký Gói Hội Viên
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                    </div>
                    <div class="modal-body pt-3">
                        <!-- Tóm tắt gói -->
                        <div class="p-3 rounded-3 mb-3 bg-body-tertiary border border-secondary border-opacity-10">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge bg-teal text-white fw-bold px-3 py-1 rounded-pill"><?= htmlspecialchars($pkgShortTitle) ?></span>
                                <span class="badge bg-danger text-white fw-bold px-2 py-1 rounded-pill">-<?= (int)$post['discount_percent'] ?>% Tiền Sân</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="text-muted small">Thời hạn kích hoạt:</span>
                                <span class="fw-bold"><?= (int)$post['duration_days'] === 30 ? '1 tháng (30 ngày)' : (int)$post['duration_days'] . ' ngày' ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted small">Tổng thanh toán:</span>
                                <span class="fs-4 fw-extrabold text-teal"><?= post_format_vnd($post['price']) ?></span>
                            </div>
                        </div>

                        <!-- Phương thức thanh toán: Chuyển khoản VietQR SePay 24/7 -->
                        <label class="form-label fw-bold small text-muted text-uppercase mb-2">Phương thức thanh toán</label>
                        <div class="p-3 border rounded-3 d-flex align-items-center gap-3 bg-surface border-teal shadow-2xs mb-3" style="border-width: 1.5px !important;">
                            <div class="icon-circle bg-teal text-white flex-shrink-0" style="width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;">
                                <i class="fa-solid fa-qrcode"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold small text-teal d-flex align-items-center justify-content-between">
                                    <span>Chuyển khoản VietQR 24/7 (SePay)</span>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-0" style="font-size:0.7rem;">Kích hoạt tự động</span>
                                </div>
                                <div class="text-muted" style="font-size:0.75rem;">Tự động kích hoạt gói & gửi hóa đơn sau 2–10 giây</div>
                            </div>
                        </div>

                        <div class="p-2 rounded-3 border bg-body-tertiary text-center small text-muted">
                            <i class="fa-solid fa-bolt text-warning me-1"></i>
                            Sau khi quét mã chuyển khoản, hệ thống sẽ <strong>tự động kích hoạt đặc quyền hội viên ngay lập tức</strong>.
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Hủy</button>
                        <button type="button" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm" id="btnStartSepaySub" onclick="startSepayMembershipPayment(<?= (int)$post['id'] ?>)">
                            <span id="btnStartSubText"><i class="fa-solid fa-qrcode me-1"></i> Tạo mã QR thanh toán</span>
                            <span id="btnStartSubSpinner" class="spinner-border spinner-border-sm ms-1" style="display:none;" role="status"></span>
                        </button>
                    </div>
                </div>

                <!-- BƯỚC 2: HIỂN THỊ MÃ VIETQR SEPAY & TỰ ĐỘNG XÁC NHẬN -->
                <div id="subStep2" style="display: none;">
                    <div style="background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%); padding: 14px 20px; color: #fff;" class="d-flex align-items-center justify-content-between">
                        <div>
                            <div style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.85; font-weight: 700;">Thanh Toán Gói Hội Viên</div>
                            <div class="fw-extrabold fs-6"><?= htmlspecialchars($pkgShortTitle) ?> (-<?= (int)$post['discount_percent'] ?>%)</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-teal fw-extrabold px-2 py-1 rounded-pill font-monospace" id="subQrTimerBadge" style="font-size: 0.82rem;">15:00</span>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Đóng"></button>
                        </div>
                    </div>

                    <div class="modal-body p-3">
                        <!-- Khung ảnh QR -->
                        <div class="text-center mb-3">
                            <div class="p-3 d-inline-block bg-white rounded-4 border shadow-sm">
                                <img id="subQrImg"
                                     src=""
                                     alt="VietQR SePay GODMINTON"
                                     class="img-fluid rounded-2"
                                     style="max-height: 215px; width: auto; object-fit: contain;">
                            </div>
                        </div>

                        <!-- Bảng thông tin chuyển khoản chuẩn SePay -->
                        <div class="p-3 rounded-4 bg-body-tertiary border border-secondary border-opacity-15 small mb-3">
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary border-opacity-10">
                                <span class="text-muted">Số tiền cần chuyển:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-body fw-extrabold fs-5" id="subPayAmountText"><?= post_format_vnd($post['price']) ?></strong>
                                    <button type="button" class="btn btn-sm btn-outline-teal py-0 px-2 rounded-2" id="btnCopySubAmount" title="Sao chép số tiền">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-secondary border-opacity-10">
                                <span class="text-muted">Nội dung chuyển:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-teal font-monospace fs-6" id="subPayCodeText">GDM800000</strong>
                                    <button type="button" class="btn btn-sm btn-outline-teal py-0 px-2 rounded-2" id="btnCopySubCode" title="Sao chép nội dung">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary border-opacity-10">
                                <span class="text-muted">Ngân hàng thụ hưởng:</span>
                                <strong class="text-body text-end" id="subPayBankName"><?= htmlspecialchars($bankInfo['name']) ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom border-secondary border-opacity-10">
                                <span class="text-muted">Số tài khoản:</span>
                                <div class="d-flex align-items-center gap-2">
                                    <strong class="text-body font-monospace" id="subPayAccNum"><?= htmlspecialchars($bankInfo['account_number']) ?></strong>
                                    <button type="button" class="btn btn-sm btn-outline-teal py-0 px-2 rounded-2" id="btnCopySubAcc" title="Sao chép STK">
                                        <i class="fa-regular fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-1">
                                <span class="text-muted">Chủ tài khoản:</span>
                                <strong class="text-body" id="subPayAccName"><?= htmlspecialchars($bankInfo['account_name']) ?></strong>
                            </div>
                        </div>

                        <!-- Trạng thái chờ ngân hàng xác nhận tự động -->
                        <div class="p-3 rounded-3 text-center mb-2" style="background: rgba(13, 148, 136, 0.08); border: 1px solid rgba(13, 148, 136, 0.25);" id="subPayStatusBox">
                            <div class="fw-bold text-teal small mb-1" id="subPayStatusTitle">
                                <span class="sepay-wait-dot"></span>Đang chờ ngân hàng xác nhận giao dịch...
                            </div>
                            <div class="text-muted" style="font-size: 0.74rem;" id="subPayStatusDesc">
                                Quét mã bằng app ngân hàng / ví điện tử. Hệ thống tự động kích hoạt gói hội viên ngay khi tiền vào (thường từ 2–10 giây).
                            </div>
                        </div>

                        <!-- Nút giả lập dành cho môi trường DEV TEST -->
                        <button type="button" id="btnSimulateSubPay" class="btn btn-sm btn-outline-warning text-dark w-100 rounded-pill fw-bold mb-2" style="display: none;">
                            <i class="fa-solid fa-flask me-1"></i> [DEV TEST] Giả lập SePay nhận tiền thành công
                        </button>

                        <div class="d-flex justify-content-between align-items-center pt-1 small">
                            <a href="#" id="subDownloadQrLink" download class="text-teal text-decoration-none fw-semibold">
                                <i class="fa-solid fa-download me-1"></i> Tải ảnh QR
                            </a>
                            <button type="button" class="btn btn-sm btn-link text-muted text-decoration-none p-0" onclick="showStep1()">
                                <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let subModalInstance = null;
    let subPollTimer = null;
    let subCountdownTimer = null;
    let currentSubPaymentCode = '';

    function stopSubTimers() {
        if (subPollTimer) { clearInterval(subPollTimer); subPollTimer = null; }
        if (subCountdownTimer) { clearInterval(subCountdownTimer); subCountdownTimer = null; }
    }

    function showStep1() {
        stopSubTimers();
        const s1 = document.getElementById('subStep1');
        const s2 = document.getElementById('subStep2');
        if (s1) s1.style.display = 'block';
        if (s2) s2.style.display = 'none';
    }

    function openSubscribeModal(autoStartQr = false) {
        const modalEl = document.getElementById('modalSubscribeMembership');
        if (!modalEl) return;
        showStep1();
        subModalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
        subModalInstance.show();

        modalEl.addEventListener('hidden.bs.modal', () => {
            stopSubTimers();
        }, { once: true });

        if (autoStartQr) {
            startSepayMembershipPayment(<?= (int)$post['id'] ?>);
        }
    }

    function copySubText(text, label) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(String(text)).then(() => {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: `Đã sao chép ${label}: ${text}`,
                        showConfirmButton: false,
                        timer: 1500
                    });
                }
            }).catch(() => {});
        }
    }

    function formatSubVnd(n) {
        return Number(n || 0).toLocaleString('vi-VN') + ' đ';
    }

    function startSubCountdown(seconds) {
        if (subCountdownTimer) clearInterval(subCountdownTimer);
        let remain = Math.max(0, Number(seconds) || 900);
        const badge = document.getElementById('subQrTimerBadge');
        const tick = () => {
            const m = String(Math.floor(remain / 60)).padStart(2, '0');
            const s = String(remain % 60).padStart(2, '0');
            if (badge) badge.textContent = `${m}:${s}`;
            if (remain <= 0) {
                clearInterval(subCountdownTimer);
            }
            remain--;
        };
        tick();
        subCountdownTimer = setInterval(tick, 1000);
    }

    async function startSepayMembershipPayment(postId) {
        const btn = document.getElementById('btnStartSepaySub');
        const text = document.getElementById('btnStartSubText');
        const spinner = document.getElementById('btnStartSubSpinner');

        if (btn) btn.disabled = true;
        if (text) text.style.display = 'none';
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const res = await fetch('api/memberships/subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId, payment_method: 'VietQR' })
            });

            const json = await res.json();
            if (!res.ok || json.status !== 'success' || !json.data || !json.data.payment) {
                throw new Error(json.message || 'Không thể khởi tạo phiên thanh toán VietQR lúc này.');
            }

            const p = json.data.payment;
            currentSubPaymentCode = p.payment_code;

            // Đổ dữ liệu lên Step 2
            const qrImg = document.getElementById('subQrImg');
            if (qrImg) qrImg.src = p.qr_url;
            const dlLink = document.getElementById('subDownloadQrLink');
            if (dlLink) {
                dlLink.href = p.qr_url;
                dlLink.download = `VietQR-${p.payment_code}.png`;
            }

            const amountEl = document.getElementById('subPayAmountText');
            if (amountEl) amountEl.textContent = formatSubVnd(p.amount);
            const codeEl = document.getElementById('subPayCodeText');
            if (codeEl) codeEl.textContent = p.payment_code;
            const bankNameEl = document.getElementById('subPayBankName');
            if (bankNameEl && p.bank) bankNameEl.textContent = p.bank.name;
            const accNumEl = document.getElementById('subPayAccNum');
            if (accNumEl && p.bank) accNumEl.textContent = p.bank.account_number;
            const accNameEl = document.getElementById('subPayAccName');
            if (accNameEl && p.bank) accNameEl.textContent = p.bank.account_name;

            const btnCopyAmount = document.getElementById('btnCopySubAmount');
            if (btnCopyAmount) btnCopyAmount.onclick = () => copySubText(p.amount, 'Số tiền');
            const btnCopyCode = document.getElementById('btnCopySubCode');
            if (btnCopyCode) btnCopyCode.onclick = () => copySubText(p.payment_code, 'Nội dung chuyển khoản');
            const btnCopyAcc = document.getElementById('btnCopySubAcc');
            if (btnCopyAcc && p.bank) btnCopyAcc.onclick = () => copySubText(p.bank.account_number, 'Số tài khoản');

            const simBtn = document.getElementById('btnSimulateSubPay');
            if (simBtn) {
                if (p.simulate_available) {
                    simBtn.style.display = 'block';
                    simBtn.disabled = false;
                    simBtn.innerHTML = '<i class="fa-solid fa-flask me-1"></i> [DEV TEST] Giả lập SePay nhận tiền thành công';
                    simBtn.onclick = async () => {
                        simBtn.disabled = true;
                        simBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang giả lập webhook SePay...';
                        try {
                            await fetch('api/payments/simulate.php', {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json' },
                                body: JSON.stringify({ code: p.payment_code })
                            });
                            await pollSubPaymentStatus(p.payment_code, json.data.package_title);
                        } catch (err) {
                            simBtn.disabled = false;
                        }
                    };
                } else {
                    simBtn.style.display = 'none';
                }
            }

            // Chuyển sang Step 2
            document.getElementById('subStep1').style.display = 'none';
            document.getElementById('subStep2').style.display = 'block';

            startSubCountdown(p.expires_in_seconds || 900);
            if (subPollTimer) clearInterval(subPollTimer);
            subPollTimer = setInterval(() => {
                pollSubPaymentStatus(p.payment_code, json.data.package_title);
            }, 3000);

        } catch (e) {
            Swal.fire({
                icon: 'error',
                title: 'Không thể tạo mã thanh toán',
                text: e.message || 'Vui lòng kiểm tra kết nối và thử lại.',
                confirmButtonColor: '#0d9488'
            });
        } finally {
            if (btn) btn.disabled = false;
            if (text) text.style.display = 'inline';
            if (spinner) spinner.style.display = 'none';
        }
    }

    async function pollSubPaymentStatus(paymentCode, packageTitle) {
        try {
            const res = await fetch(`api/payments/status.php?code=${encodeURIComponent(paymentCode)}`, { cache: 'no-store' });
            const json = await res.json();
            if (!res.ok || !json.data) return;

            const d = json.data;
            if (d.status === 'paid') {
                stopSubTimers();
                if (subModalInstance) subModalInstance.hide();
                await Swal.fire({
                    icon: 'success',
                    title: 'Kích Hoạt Gói Hội Viên Thành Công!',
                    html: `Hệ thống đã nhận đủ thanh toán và <strong>kích hoạt ngay lập tức ${packageTitle || 'Gói Hội Viên'}</strong> cho tài khoản của bạn.<br><br>Hóa đơn điện tử &amp; Chứng nhận Thẻ Hội Viên VIP cũng đã được gửi tới email của bạn!`,
                    confirmButtonText: 'Tuyệt vời!',
                    confirmButtonColor: '#0d9488'
                });
                window.location.reload();
            } else if (d.status === 'expired' || d.status === 'cancelled') {
                stopSubTimers();
                const titleEl = document.getElementById('subPayStatusTitle');
                const descEl = document.getElementById('subPayStatusDesc');
                if (titleEl) titleEl.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-danger me-1"></i> Mã QR đã hết hạn';
                if (descEl) descEl.textContent = 'Vui lòng bấm Quay lại và tạo mã QR mới để tiếp tục thanh toán.';
            }
        } catch (err) {
            // Bỏ qua lỗi mạng tạm thời trong lúc poll
        }
    }
    </script>
    <?php endif; ?>

    <?php if ($postVoucher): ?>
    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Sao chép mã giảm giá
        document.querySelectorAll('.copy-code-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const code = btn.dataset.code;
                if (!code) return;
                try {
                    await navigator.clipboard.writeText(code);
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã sao chép mã!',
                        text: `Mã "${code}" đã được lưu vào bộ nhớ tạm.`,
                        timer: 1500,
                        showConfirmButton: false
                    });
                } catch(e) {
                    prompt('Mã giảm giá của bạn:', code);
                }
            });
        });

        // Bấm nhận mã vào kho
        document.querySelectorAll('.btn-collect-voucher').forEach(btn => {
            btn.addEventListener('click', async () => {
                const voucherId = btn.dataset.voucherId;
                const postId = btn.dataset.postId;
                const spinner = btn.querySelector('.collect-spinner');
                const icon = btn.querySelector('.collect-icon');
                const text = btn.querySelector('.collect-text');

                btn.disabled = true;
                if (spinner) spinner.style.display = 'inline-block';
                if (icon) icon.style.display = 'none';
                if (text) text.textContent = 'Đang nhận mã...';

                try {
                    const res = await fetch('api/vouchers/collect.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ voucher_id: Number(voucherId), post_id: Number(postId) })
                    });
                    const json = await res.json();

                    if (res.ok && json.status === 'success') {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Nhận Mã Thành Công!',
                            html: `Mã ưu đãi <strong>${json.data.voucher_code}</strong> đã được lưu thành công vào <strong>Kho Mã Giảm Giá &amp; Ưu Đãi</strong> trong trang cá nhân của bạn.<br><br>Bạn có thể dùng mã này khi thanh toán đặt sân!`,
                            confirmButtonText: 'Đã hiểu',
                            confirmButtonColor: '#0d9488'
                        });

                        const wrap = document.getElementById('voucherCollectActionWrap');
                        if (wrap) {
                            wrap.innerHTML = `
                                <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm" disabled>
                                    <i class="fa-solid fa-circle-check me-1"></i> Đã lưu vào kho mã
                                </button>
                                <div class="mt-2">
                                    <a href="profile.php?tab=vouchers" class="small text-teal text-decoration-none fw-semibold">
                                        Xem trong kho của bạn <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            `;
                        }
                    } else if (res.status === 409) {
                        Swal.fire({
                            icon: 'info',
                            title: 'Mã đã có trong kho',
                            text: json.message || 'Bạn đã lưu mã này vào kho của bạn rồi!',
                            confirmButtonColor: '#0d9488'
                        });
                        const wrap = document.getElementById('voucherCollectActionWrap');
                        if (wrap) {
                            wrap.innerHTML = `
                                <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold shadow-sm" disabled>
                                    <i class="fa-solid fa-circle-check me-1"></i> Đã lưu vào kho mã
                                </button>
                                <div class="mt-2">
                                    <a href="profile.php?tab=vouchers" class="small text-teal text-decoration-none fw-semibold">
                                        Xem trong kho của bạn <i class="fa-solid fa-arrow-right"></i>
                                    </a>
                                </div>
                            `;
                        }
                    } else {
                        throw new Error(json.message || 'Không thể nhận mã lúc này.');
                    }
                } catch(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Chưa thể nhận mã',
                        text: err.message,
                        confirmButtonColor: '#0d9488'
                    });
                    btn.disabled = false;
                    if (spinner) spinner.style.display = 'none';
                    if (icon) icon.style.display = 'inline-block';
                    if (text) text.textContent = 'Bấm nhận mã vào kho';
                }
            });
        });
    });
    </script>
    <?php endif; ?>
</body>
</html>
