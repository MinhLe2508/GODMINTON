<?php
/**
 * explore.php
 * Trang Khám Phá — Sự kiện, Gói hội viên ưu đãi, Thông báo (GODMINTON).
 * Dành cho khách hàng xem và cập nhật thông tin cộng đồng.
 */
header('Cache-Control: no-store, max-age=0');
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khám Phá — Sự Kiện, Gói Hội Viên & Thông Báo | GODMINTON</title>
    <meta name="description" content="Khám phá các giải đấu cầu lông sôi động, gói hội viên ưu đãi đặc quyền và thông báo cập nhật mới nhất từ GODMINTON.">
<?php include_once __DIR__ . '/includes/partials/pwa-head.php'; ?>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <!-- CSS -->
    <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime(__DIR__ . '/assets/css/style.css') ?>">
    <link rel="stylesheet" href="assets/css/index_explore_tabs_style.css?v=<?= filemtime(__DIR__ . '/assets/css/index_explore_tabs_style.css') ?>">

    <style>
        .explore-hero {
            background: linear-gradient(135deg, rgba(13,148,136,0.1) 0%, rgba(15,23,42,0.03) 100%);
            border-bottom: 1px solid var(--border-color);
            padding: 2.5rem 0 1.5rem;
        }
        [data-bs-theme="dark"] .explore-hero {
            background: linear-gradient(135deg, rgba(13,148,136,0.15) 0%, rgba(17,24,39,0.8) 100%);
        }
        .post-card {
            border: 1.5px solid var(--border-color);
            border-radius: 1.25rem;
            background: var(--bg-card, #fff);
            transition: transform 0.22s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.22s, border-color 0.22s;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            position: relative;
        }
        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(13,148,136,0.14);
            border-color: rgba(13,148,136,0.45);
        }
        .post-card-img-wrap {
            height: 190px;
            width: 100%;
            background: var(--bg-surface, #f8fafc);
            position: relative;
            overflow: hidden;
        }
        .post-card-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .post-card:hover .post-card-img {
            transform: scale(1.05);
        }
        .post-pinned-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 2;
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(4px);
            color: #fbbf24;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(251, 191, 36, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .post-type-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 0.7rem;
            font-weight: 800;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .chip-event { background: rgba(139, 92, 246, 0.12); color: #8b5cf6; }
        .chip-membership { background: rgba(245, 158, 11, 0.12); color: #d97706; }
        .chip-announcement { background: rgba(244, 63, 94, 0.12); color: #e11d48; }
        
        [data-bs-theme="dark"] .chip-event { color: #c4b5fd; }
        [data-bs-theme="dark"] .chip-membership { color: #fcd34d; }
        [data-bs-theme="dark"] .chip-announcement { color: #fda4af; }

        .post-card-body {
            padding: 1.25rem;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }
        .post-card-title {
            font-size: 1.05rem;
            font-weight: 800;
            line-height: 1.45;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .post-card-summary {
            font-size: 0.85rem;
            color: var(--text-secondary);
            line-height: 1.55;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .post-card-footer {
            margin-top: auto;
            padding-top: 0.85rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .post-meta-text {
            font-size: 0.75rem;
            color: var(--text-secondary);
        }
        .pagination .page-link {
            border-radius: 999px;
            margin: 0 3px;
            color: var(--brand-teal);
            border-color: var(--border-color);
            font-weight: 600;
        }
        .pagination .page-item.active .page-link {
            background-color: var(--brand-teal);
            border-color: var(--brand-teal);
            color: #fff;
        }

        @media (max-width: 575.98px) {
            .explore-hero {
                padding: 1.5rem 0 1rem;
            }
            .explore-hero h1 {
                font-size: 1.35rem !important;
            }
            .post-card-img-wrap {
                height: 165px;
            }
            .post-card-body {
                padding: 1rem;
            }
            .post-card-title {
                font-size: 0.96rem;
                margin-bottom: 0.35rem;
            }
            .post-card-summary {
                font-size: 0.82rem;
                margin-bottom: 0.75rem;
                -webkit-line-clamp: 2;
            }
            .post-card-footer .btn {
                padding: 0.3rem 0.75rem;
                font-size: 0.76rem;
            }
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

    <main class="min-vh-100">

        <!-- Hero Section -->
        <section class="explore-hero mb-4">
            <div class="container">
                <div class="row align-items-center justify-content-between g-3">
                    <div class="col-lg-7">
                        <span class="badge bg-teal-subtle text-teal fw-bold text-uppercase px-3 py-1 rounded-pill mb-2">
                            <i class="fa-solid fa-compass me-1"></i> Khám phá GODMINTON
                        </span>
                        <h1 class="fw-extrabold mb-2 display-6">Sự Kiện, Gói Hội Viên & Bản Tin</h1>
                        <p class="text-muted mb-0 small">Cập nhật lịch thi đấu giao lưu, quyền lợi hội viên và thông báo chính thức từ ban quản trị</p>
                    </div>
                    <div class="col-lg-5">
                        <div class="input-group">
                            <span class="input-group-text bg-surface border-end-0 rounded-start-pill ps-3 text-muted">
                                <i class="fa-solid fa-magnifying-glass"></i>
                            </span>
                            <input type="search" id="exploreSearchInput" class="form-control rounded-end-pill border-start-0 py-2" placeholder="Tìm theo tiêu đề bài viết...">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Container Chính -->
        <div class="container pb-5">

            <!-- Tab Điều Hướng (3 Loại: Sự kiện, Gói hội viên, Thông báo) -->
            <div class="explore-tabs-wrap mb-4" data-aos="fade-up">
                <div class="explore-tabs" id="exploreTabGroup">
                    <button class="explore-tab active" data-type="" aria-pressed="true">
                        <i class="fa-solid fa-border-all"></i> Tất cả
                    </button>
                    <button class="explore-tab" data-type="event" aria-pressed="false">
                        <i class="fa-solid fa-trophy"></i> Sự kiện
                    </button>
                    <button class="explore-tab" data-type="membership" aria-pressed="false">
                        <i class="fa-solid fa-id-card"></i> Hội viên ưu đãi
                    </button>
                    <button class="explore-tab" data-type="announcement" aria-pressed="false">
                        <i class="fa-solid fa-bell"></i> Thông báo
                    </button>
                </div>
            </div>

            <!-- Loading State -->
            <div id="exploreLoading" class="text-center py-5">
                <div class="spinner-border text-teal mb-3" role="status" style="width: 2.5rem; height: 2.5rem;"></div>
                <div class="text-muted small fw-semibold">Đang tải danh sách bài viết...</div>
            </div>

            <!-- Grid Thẻ Bài Viết -->
            <div class="row g-4" id="exploreGrid" style="display: none;">
                <!-- Render bằng JavaScript -->
            </div>

            <!-- Empty State -->
            <div id="exploreEmpty" class="explore-empty my-4" style="display: none;">
                <i class="fa-regular fa-folder-open text-teal fs-1 mb-3"></i>
                <h5 class="fw-bold mb-1">Chưa có bài viết trong mục này</h5>
                <p class="text-muted small mb-0">Các sự kiện và thông báo mới sẽ được ban quản trị cập nhật sớm nhất.</p>
            </div>

            <!-- Phân Trang -->
            <nav id="explorePaginationNav" class="mt-5" aria-label="Phân trang bài viết" style="display: none;">
                <ul class="pagination justify-content-center" id="explorePagination">
                    <!-- Render bằng JavaScript -->
                </ul>
            </nav>

        </div>

    </main>

    <!-- Footer -->
    <footer class="footer-custom mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="mb-3"><img src="assets/img/logo.svg" alt="GODMINTON" style="height:38px; width:auto;"></div>
                    <p class="text-muted-light small">Nền tảng đặt sân cầu lông trực tuyến thông minh, giữ chỗ realtime 5 phút và thông báo đẩy PWA.</p>
                    <div class="d-flex gap-3">
                        <a href="#" class="social-icon" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="social-icon" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
                    </div>
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
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Hotline</h6>
                    <div class="d-flex align-items-center gap-3">
                        <div class="icon-circle bg-teal text-white"><i class="fa-solid fa-phone"></i></div>
                        <div>
                            <div class="small text-muted-light">Tổng đài 24/7</div>
                            <div class="fs-5 fw-bold text-white">1900 8888</div>
                        </div>
                    </div>
                </div>
            </div>
            <hr class="border-secondary opacity-25 my-4">
            <div class="small text-muted-light">&copy; 2026 GODMINTON — Đồ án Lập trình Web.</div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <script src="assets/js/content-client.js?v=<?= filemtime(__DIR__ . '/assets/js/content-client.js') ?>"></script>
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
    <script src="assets/js/explore.js?v=<?= filemtime(__DIR__ . '/assets/js/explore.js') ?>"></script>
</body>
</html>
