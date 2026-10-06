<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên Hệ & Hỗ Trợ Khách Hàng | GODMINTON</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- App Brand Header (Khung Cố Định) -->
    <header class="app-brand-head">
        <div class="container text-center">
            <a href="index.html" class="brand-logo-link" aria-label="GODMINTON Trang chủ">
                <img src="assets/img/logo-teal.svg" alt="GODMINTON" class="brand-logo-img">
            </a>
        </div>
    </header>

    <main class="min-vh-100">
    <div class="container py-5">
        <div class="row g-4 justify-content-center">
            <!-- Thông tin liên hệ -->
            <div class="col-lg-5" data-aos="fade-right">
                <div class="card border-0 text-white rounded-4 p-4 p-sm-5 shadow-lg h-100" style="background-color: var(--brand-teal);">
                    <h3 class="fw-extrabold mb-3">Liên Hệ Với Chúng Tôi</h3>
                    <p class="opacity-90 mb-4">
                        Đội ngũ hỗ trợ GODMINTON luôn sẵn sàng giải đáp thắc mắc về lịch đặt sân, hợp tác mở cụm sân mới và dịch vụ thể thao.
                    </p>

                    <div class="d-flex flex-column gap-3 mb-5">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-white text-teal fs-5 shadow-sm">
                                <i class="fa-solid fa-phone"></i>
                            </div>
                            <div>
                                <div class="small opacity-75">Hotline hỗ trợ 24/7</div>
                                <div class="fs-5 fw-bold">1900 8888 / 0988 888 888</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-white text-teal fs-5 shadow-sm">
                                <i class="fa-solid fa-envelope"></i>
                            </div>
                            <div>
                                <div class="small opacity-75">Email phản hồi</div>
                                <div class="fs-6 fw-bold">contact@datsancaulong.vn</div>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-white text-teal fs-5 shadow-sm">
                                <i class="fa-solid fa-location-dot"></i>
                            </div>
                            <div>
                                <div class="small opacity-75">Trụ sở chính</div>
                                <div class="fs-6 fw-bold">Nguyễn Hữu Thọ, Tân Hưng, Hồ Chí Minh</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-auto pt-3 border-top border-white border-opacity-25 small opacity-90">
                        Cam kết phản hồi trong vòng 15 phút làm việc.
                    </div>
                </div>
            </div>

            <!-- Form gửi thư liên hệ -->
            <div class="col-lg-6" data-aos="fade-left">
                <div class="card border rounded-4 p-4 p-sm-5 shadow-sm">
                    <h4 class="fw-extrabold mb-3">Gửi Thắc Mắc Hoặc Góp Ý</h4>

                    <div id="alertSuccess" class="alert alert-success border-0 rounded-3 d-none">
                        <i class="fa-solid fa-circle-check me-2"></i>Cảm ơn bạn đã liên hệ! Ban quản trị sẽ liên lạc với bạn sớm nhất.
                    </div>

                    <form id="contactForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Họ và Tên *</label>
                                <input type="text" name="name" id="contactName" class="form-control rounded-3" required placeholder="Nguyễn Văn A">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Số Điện Thoại *</label>
                                <input type="tel" name="phone" id="contactPhone" class="form-control rounded-3" required placeholder="0912345678">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Email *</label>
                                <input type="email" name="email" id="contactEmail" class="form-control rounded-3" required placeholder="name@gmail.com">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Chủ Đề</label>
                                <input type="text" name="subject" id="contactSubject" class="form-control rounded-3" placeholder="Hỏi về đặt sân theo tháng, giải đấu...">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Nội Dung Chi Tiết *</label>
                                <textarea name="message" id="contactMessage" class="form-control rounded-3" rows="4" required placeholder="Nhập nội dung cần hỗ trợ..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" id="btnContactSubmit" class="btn btn-teal btn-lg rounded-pill px-4 fw-bold shadow">
                                    <i class="fa-solid fa-paper-plane me-1"></i> Gửi Tin Nhắn
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
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
                        <li><a href="index.html">Trang chủ</a></li>
                        <li><a href="courts.html">Tìm kiếm sân</a></li>
                        <li><a href="news.html">Tin tức</a></li>
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
    <script src="assets/js/mock-data.js"></script>
    <script src="assets/js/app.js"></script>
    <script src="assets/js/contact.js"></script>
    <!-- Bottom Nav -->
    <script src="assets/js/bottom-nav.js"></script>
</body>
</html>





