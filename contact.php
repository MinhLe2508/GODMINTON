<?php header('Cache-Control: no-store, max-age=0'); ?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên Hệ & Hỗ Trợ Khách Hàng | GODMINTON</title>
<?php include_once __DIR__ . '/includes/partials/pwa-head.php'; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?= filemtime(__DIR__ . '/assets/css/swal-godminton.css') ?>">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/contact.css?v=<?= filemtime(__DIR__ . '/assets/css/contact.css') ?>">
</head>
<body>

    <!-- App Brand Header (Khung Cố Định) -->
    <header class="app-brand-head">
        <div class="container text-center">
            <a href="index.php" class="brand-logo-link" aria-label="GODMINTON Trang chủ">
                <img src="assets/img/logo-teal.svg" alt="GODMINTON" class="brand-logo-img">
            </a>
        </div>
    </header>

    <main class="contact-page">
    <div class="container contact-shell">
        <div class="row g-4 justify-content-center align-items-stretch">
            <!-- Thông tin liên hệ -->
            <div class="col-lg-5" data-aos="fade-right">
                <section class="contact-info-card h-100" aria-labelledby="contactInfoTitle">
                    <div class="contact-kicker"><span class="contact-kicker-icon"><i class="fa-solid fa-headset"></i></span> GODMINTON SUPPORT</div>
                    <h1 id="contactInfoTitle">Kết nối với đội ngũ hỗ trợ</h1>
                    <p class="contact-intro">Bạn cần trợ giúp về đặt sân, tài khoản hoặc muốn hợp tác cùng GODMINTON? Hãy gửi lời nhắn, đội ngũ sẽ tiếp nhận và phản hồi qua email của bạn.</p>

                    <div class="contact-topics" aria-label="Chủ đề hỗ trợ">
                        <span><i class="fa-regular fa-calendar-check"></i> Đặt sân</span>
                        <span><i class="fa-solid fa-building"></i> Hợp tác sân</span>
                        <span><i class="fa-solid fa-circle-question"></i> Tài khoản</span>
                    </div>

                    <div class="contact-channel">
                        <span class="contact-channel-icon"><i class="fa-solid fa-envelope"></i></span>
                        <div>
                            <span class="contact-channel-label">Email tiếp nhận</span>
                            <a href="mailto:admgodminton@gmail.com">admgodminton@gmail.com</a>
                        </div>
                    </div>

                    <div class="contact-privacy-note">
                        <i class="fa-solid fa-shield-halved"></i>
                        <span>Thông tin bạn gửi chỉ được dùng để xử lý yêu cầu hỗ trợ.</span>
                    </div>
                </section>
            </div>

            <!-- Form gửi thư liên hệ -->
            <div class="col-lg-6" data-aos="fade-left">
                <section class="contact-form-card h-100" aria-labelledby="contactFormTitle">
                    <div class="contact-form-heading">
                        <span class="contact-form-icon"><i class="fa-regular fa-paper-plane"></i></span>
                        <div>
                            <h2 id="contactFormTitle">Gửi lời nhắn cho chúng tôi</h2>
                            <p>Các trường có dấu <span aria-hidden="true">*</span> là bắt buộc.</p>
                        </div>
                    </div>

                    <div id="alertSuccess" class="contact-feedback contact-feedback-success d-none" role="status" aria-live="polite">
                        <i class="fa-solid fa-circle-check"></i><span>Cảm ơn bạn đã liên hệ! Tin nhắn đã được gửi đến đội ngũ GODMINTON.</span>
                    </div>

                    <form id="contactForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="contactName" class="form-label">Họ và tên <span>*</span></label>
                                <input type="text" name="name" id="contactName" class="form-control" required maxlength="120" autocomplete="name" placeholder="Nguyễn Văn A">
                            </div>
                            <div class="col-md-6">
                                <label for="contactPhone" class="form-label">Số điện thoại <span>*</span></label>
                                <input type="tel" name="phone" id="contactPhone" class="form-control" required maxlength="20" autocomplete="tel" inputmode="tel" placeholder="0912 345 678">
                            </div>
                            <div class="col-12">
                                <label for="contactEmail" class="form-label">Email phản hồi <span>*</span></label>
                                <input type="email" name="email" id="contactEmail" class="form-control" required maxlength="150" autocomplete="email" placeholder="ten@email.com">
                            </div>
                            <div class="col-12">
                                <label for="contactSubject" class="form-label">Chủ đề</label>
                                <input type="text" name="subject" id="contactSubject" class="form-control" maxlength="200" placeholder="Ví dụ: Hỗ trợ đặt sân, góp ý dịch vụ">
                            </div>
                            <div class="col-12">
                                <label for="contactMessage" class="form-label">Nội dung chi tiết <span>*</span></label>
                                <textarea name="message" id="contactMessage" class="form-control" rows="5" required maxlength="5000" placeholder="Mô tả vấn đề hoặc điều bạn muốn chia sẻ..."></textarea>
                            </div>
                            <div class="col-12 d-flex flex-wrap align-items-center justify-content-between gap-3 pt-1">
                                <small class="contact-form-hint"><i class="fa-solid fa-lock"></i> Thông tin được gửi an toàn qua hệ thống.</small>
                                <button type="submit" id="btnContactSubmit" class="btn btn-teal contact-submit-btn">
                                    <i class="fa-solid fa-paper-plane"></i> Gửi tin nhắn
                                </button>
                            </div>
                        </div>
                    </form>
                </section>
            </div>
        </div>
    </div>
    </main>

    <footer class="footer-custom mt-5 pt-5 pb-4">
        <div class="container">
            <div class="row g-4 mb-4">
                <div class="col-lg-4 col-md-6">
                    <div class="mb-3"><img src="assets/img/logo.svg" alt="GODMINTON" style="height:38px; width:auto;"></div>
                    <p class="text-muted-light">Nền tảng đặt sân cầu lông trực tuyến thông minh.</p>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3">Liên Kết Nhanh</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="index.php">Trang chủ</a></li>
                        <li><a href="courts.php">Tìm kiếm sân</a></li>
                        <li><a href="news.php">Tin tức</a></li>
                    </ul>
                </div>
            </div>
            <hr class="border-secondary opacity-25 my-4">
            <div class="small text-muted-light">&copy; 2026 GODMINTON - Đồ án Lập trình Web.</div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/swal-godminton.js?v=<?= filemtime(__DIR__ . '/assets/js/swal-godminton.js') ?>"></script>
    <script src="assets/js/app.js?v=<?= filemtime(__DIR__ . '/assets/js/app.js') ?>"></script>
    <script src="assets/js/content-client.js?v=<?= filemtime(__DIR__ . '/assets/js/content-client.js') ?>"></script>
    <script src="assets/js/contact.js?v=<?= filemtime(__DIR__ . '/assets/js/contact.js') ?>"></script>
    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js?v=<?= filemtime(__DIR__ . '/assets/js/bottom-nav.js') ?>"></script>
</body>
</html>





