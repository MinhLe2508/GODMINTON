-- database/seed_posts.sql
-- GODMINTON - Dữ liệu mẫu bài viết phần Khám phá (Sự kiện, Gói hội viên ưu đãi, Thông báo)
-- Sử dụng sau khi đã chạy migration_posts.sql
-- Tác giả: ID = 2 (Quản trị viên hệ thống)

START TRANSACTION;

INSERT INTO `posts` (
  `id`, `type`, `title`, `slug`, `summary`, `content`, `cover_image`,
  `status`, `publish_at`, `expire_at`, `is_pinned`, `created_by`,
  `event_start`, `event_end`, `event_location`,
  `price`, `duration_days`, `discount_percent`, `benefits`,
  `created_at`, `updated_at`
) VALUES
-- =======================================================================
-- 1. SỰ KIỆN (type = 'event')
-- =======================================================================
(
  1,
  'event',
  'Giải Cầu Lông Mở Rộng Toàn Thành GODMINTON Autumn Cup 2026',
  'giai-cau-long-mo-rong-toan-thanh-godminton-autumn-cup-2026',
  'Giải đấu thường niên quy tụ hơn 64 cặp vận động viên phong trào với tổng giải thưởng lên đến 50.000.000đ và nhiều quà tặng hấp dẫn.',
  '### Giới thiệu giải đấu\nNhằm tạo sân chơi lành mạnh, nâng cao tinh thần rèn luyện thể thao và gắn kết cộng đồng người chơi cầu lông tại TP.HCM, GODMINTON chính thức khởi tranh giải đấu **GODMINTON Autumn Cup 2026**.\n\n### Đối tượng & Thể thức thi đấu\n- **Đối tượng:** Tất cả người chơi phong trào (không bao gồm vận động viên chuyên nghiệp thuộc đội tuyển quốc gia).\n- **Nội dung thi đấu:** Đôi nam phong trào, Đôi nữ phong trào và Đôi nam nữ hỗn hợp.\n- **Thể thức:** Đấu loại trực tiếp 3 hiệp thắng 2, mỗi hiệp 21 điểm theo luật cầu lông BWF hiện hành.\n\n### Cơ cấu giải thưởng\n- **Giải Nhất:** Cúp vô địch + 15.000.000đ tiền mặt + 02 vợt cầu lông cao cấp.\n- **Giải Nhì:** 8.000.000đ + Giấy khen + Quà tài trợ.\n- **Đồng Giải Ba:** 4.000.000đ + Giấy khen.\n\n### Đăng ký thi đấu\nHạn chót đăng ký: 10/11/2026. Lệ phí tham dự: 200.000đ/cặp vận động viên.',
  'assets/img/posts/event-autumn-cup.jpg',
  'published',
  NOW() - INTERVAL 2 DAY,
  NULL,
  1,
  2,
  '2026-11-15 08:00:00',
  '2026-11-16 18:00:00',
  'Cụm sân Cầu Lông Kỳ Hòa, 238 Ba Tháng Hai, Phường 12, Quận 10, TP.HCM',
  NULL,
  NULL,
  0,
  NULL,
  NOW() - INTERVAL 2 DAY,
  NOW() - INTERVAL 2 DAY
),
(
  2,
  'event',
  'Ngày Hội Giao Lưu Cầu Lông Cuối Tuần - Badminton Sunday Match',
  'ngay-hoi-giao-luu-cau-long-cuoi-tuan-badminton-sunday-match',
  'Buổi giao lưu kết nối các tay vợt từ trình độ cơ bản đến trung bình, kiểm tra thể lực và nhận quà lưu niệm từ ban quản trị.',
  '### Không gian kết nối đam mê\nBạn đang tìm kiếm bạn đánh cuối tuần? Muốn cọ xát kỹ năng trong một bầu không khí vui vẻ và thân thiện? Hãy tham gia ngay buổi sinh hoạt cộng đồng **Badminton Sunday Match** cùng các thành viên GODMINTON.\n\n### Lịch trình chi tiết\n- **14:00 - 14:30:** Tập trung, bốc thăm chia nhóm theo trình độ (Cơ bản / Trung bình / Khá).\n- **14:30 - 17:00:** Thi đấu giao lưu luân phiên các cặp đấu ngắn (15 điểm).\n- **17:00 - 18:00:** Bốc thăm trúng thưởng phụ kiện (quấn cán, tất thi đấu, voucher giảm giá sân).\n\n### Lưu ý chuẩn bị\n- Mang giày đế chuyên dụng (không để lại vết đen trên thảm sân).\n- Ban tổ chức đã chuẩn bị sẵn cầu thi đấu và nước uống miễn phí cho toàn bộ người tham gia.',
  'assets/img/posts/event-sunday-match.jpg',
  'published',
  NOW() - INTERVAL 1 DAY,
  NULL,
  0,
  2,
  '2026-11-08 14:00:00',
  '2026-11-08 18:00:00',
  'Sân Cầu Lông Lan Anh, 291 Cách Mạng Tháng 8, Phường 12, Quận 10, TP.HCM',
  NULL,
  NULL,
  0,
  NULL,
  NOW() - INTERVAL 1 DAY,
  NOW() - INTERVAL 1 DAY
),
(
  3,
  'event',
  'Workshop Kỹ Thuật Đập Cầu & Phòng Thủ Sân Đôi Cùng HLV Chuyên Nghiệp',
  'workshop-ky-thuat-dap-cau-va-phong-thu-san-doi-cung-hlv-chuyen-nghiep',
  'Chuyên đề nâng cao kỹ năng xử lý các tình huống cầu nhanh, kỹ thuật smash cắm và bộ chân di chuyển chiến thuật đôi.',
  '### Nội dung đào tạo chuyên sâu\nWorkshop được thiết kế đặc biệt nhằm gỡ bỏ các sai sót phổ biến trong phối hợp đôi:\n- **Phần 1: Tư thế & Góc phát lực:** Bí quyết tạo điểm rơi chuẩn xác khi đập cầu smash mà không tốn quá nhiều sức.\n- **Phần 2: Bộ chân phòng thủ phản xạ:** Kỹ thuật di chuyển ngang, lùi bước đón quả đập của đối phương.\n- **Phần 3: Chiến thuật kiểm soát lưới:** Cách xử lý các quả cài lưới, đẩy cầu sâu tạo thế chủ động.\n\n### Chuyên gia đồng hành\nHuấn luyện viên có chứng chỉ BWF Level 2, cựu vận động viên với hơn 10 năm kinh nghiệm thi đấu và đào tạo phong trào.',
  'assets/img/posts/event-smash-workshop.jpg',
  'published',
  NOW(),
  NULL,
  0,
  2,
  '2026-11-22 09:00:00',
  '2026-11-22 11:30:00',
  'Sân Cầu Lông Bến Thành, 50 Huyền Trân Công Chúa, Phường Bến Thành, Quận 1, TP.HCM',
  NULL,
  NULL,
  0,
  NULL,
  NOW(),
  NOW()
),

-- =======================================================================
-- 2. GÓI HỘI VIÊN ƯU ĐÃI (type = 'membership')
-- =======================================================================
(
  4,
  'membership',
  'Gói Hội Viên Bạc (Silver Feather) - Ưu Đãi 5% Tiền Sân',
  'goi-hoi-vien-bac-silver-feather',
  'Gói hội viên 30 ngày linh hoạt dành cho người chơi thường xuyên, tự động giảm ngay 5% cho mọi lượt đặt sân trên toàn hệ thống GODMINTON.',
  '### Thông tin chi tiết gói Bạc\nGói **Silver Feather** là sự lựa chọn hoàn hảo cho những ai muốn duy trì thói quen tập luyện thể thao 2-3 buổi mỗi tuần mà vẫn tối ưu được ngân sách đặt sân.\n\n### Đặc quyền khi kích hoạt\n- Tự động trừ 5% trực tiếp trên tổng hóa đơn đặt sân.\n- Được áp dụng cộng dồn hoặc ưu tiên cùng mã giảm giá nếu hợp lệ.\n- Thời gian áp dụng: 30 ngày kể từ thời điểm thanh toán thành công.',
  'assets/img/memberships/membership-silver.jpg',
  'published',
  NOW() - INTERVAL 3 DAY,
  NULL,
  0,
  2,
  NULL, NULL, NULL,
  199000,
  30,
  5,
  'Giảm 5% trực tiếp trên tổng tiền sân cho mọi lượt đặt sân\nƯu tiên giữ slot trước 3 ngày so với khách thông thường\nMiễn phí đổi lịch trước 12 tiếng không tính phí\nHỗ trợ tư vấn đặt sân và kiểm tra lịch nhanh qua hotline',
  NOW() - INTERVAL 3 DAY,
  NOW() - INTERVAL 3 DAY
),
(
  5,
  'membership',
  'Gói Hội Viên Vàng (Golden Racket) - Ưu Đãi 10% Tiền Sân',
  'goi-hoi-vien-vang-golden-racket',
  'Gói hội viên 90 ngày tối ưu chi phí cho các câu lạc bộ và nhóm cố định hàng tuần với mức giảm giá 10% toàn sàn.',
  '### Đặc quyền dành cho câu lạc bộ\nĐược thiết kế cho các nhóm chơi lâu năm, gói **Golden Racket** giúp trưởng nhóm an tâm về chỗ đánh mỗi tuần mà không lo cảnh giành sân giờ cao điểm.\n\n### Chính sách hỗ trợ\n- Giữ khung giờ cố định cả tháng không lo hết chỗ.\n- Miễn phí hủy đơn trước 6 tiếng không bị mất tiền cọc.\n- Tặng kèm phụ kiện thi đấu chính hãng khi gia hạn.',
  'assets/img/memberships/membership-gold.jpg',
  'published',
  NOW() - INTERVAL 2 DAY,
  NULL,
  1,
  2,
  NULL, NULL, NULL,
  499000,
  90,
  10,
  'Giảm 10% trực tiếp trên tổng tiền sân cho mọi lượt đặt sân\nƯu tiên giữ khung giờ vàng trước 7 ngày\nTặng 01 ống cầu lông thi đấu chuẩn khi kích hoạt\nMiễn phí đổi lịch hoặc hủy trước 6 tiếng',
  NOW() - INTERVAL 2 DAY,
  NOW() - INTERVAL 2 DAY
),
(
  6,
  'membership',
  'Gói Hội Viên Bạch Kim (Platinum Ace) - Ưu Đãi 15% Tiền Sân',
  'goi-hoi-vien-bach-kim-platinum-ace',
  'Gói hội viên cao cấp nhất 365 ngày dành cho vận động viên, hội nhóm và doanh nghiệp với mức chiết khấu tối đa 15% toàn hệ thống.',
  '### Trải nghiệm VIP không giới hạn\nGói hội viên cao cấp nhất mang lại sự thoải mái tuyệt đối cho các tổ chức, doanh nghiệp hoặc người chơi chuyên nghiệp với đặc quyền tối đa trên mạng lưới đối tác sân cầu lông GODMINTON.',
  'assets/img/memberships/membership-platinum.jpg',
  'published',
  NOW() - INTERVAL 1 DAY,
  NULL,
  0,
  2,
  NULL, NULL, NULL,
  1490000,
  365,
  15,
  'Giảm 15% tối đa trên tổng hóa đơn đặt sân tại tất cả các cụm sân đối tác\nƯu tiên giữ lịch cố định tháng và đặt trước 14 ngày\nMiễn phí hủy đơn trước 4 tiếng\nVé VIP tham dự các giải đấu mở rộng do GODMINTON tổ chức\nĐược tặng áo đấu độc quyền và quà tri ân sinh nhật',
  NOW() - INTERVAL 1 DAY,
  NOW() - INTERVAL 1 DAY
),

-- =======================================================================
-- 3. THÔNG BÁO (type = 'announcement')
-- =======================================================================
(
  7,
  'announcement',
  'Thông Báo Nâng Cấp Hệ Thống Đồng Bộ Đặt Sân Theo Thời Gian Thực',
  'thong-bao-nang-cap-he-thong-dong-bo-dat-san-theo-thoi-gian-thuc',
  'GODMINTON hoàn tất nâng cấp cơ chế khóa giữ chỗ ACID (5 phút) và mạng lưới thông báo đẩy Web Push nhắc lịch thi đấu.',
  '### Nâng tầm trải nghiệm người dùng\nKính gửi toàn thể quý khách hàng và các chủ sân đối tác,\n\nNhằm khắc phục triệt để tình trạng trùng lịch và hỗ trợ người chơi chủ động sắp xếp thời gian, ban quản trị GODMINTON đã triển khai thành công đợt nâng cấp kỹ thuật lớn:\n\n1. **Giao dịch giữ chỗ 5 phút (ACID):** Khi bạn chọn khung giờ, hệ thống sẽ tự động khóa slot của bạn trong 5 phút để bảo đảm không ai khác có thể can thiệp trong lúc bạn thanh toán.\n2. **Thông báo đẩy Web Push (PWA):** Tự động gửi thông báo trực tiếp đến điện thoại hoặc máy tính trước giờ thi đấu 30 phút mà không cần mở ứng dụng.\n3. **Tối ưu hiển thị di động:** Tải nhanh hơn 40%, mượt mà trên tất cả các dòng smartphone.\n\nCảm ơn quý khách đã luôn đồng hành cùng GODMINTON!',
  'assets/img/posts/announcement-system-upgrade.jpg',
  'published',
  NOW() - INTERVAL 3 DAY,
  NULL,
  1,
  2,
  NULL, NULL, NULL,
  NULL, NULL, 0, NULL,
  NOW() - INTERVAL 3 DAY,
  NOW() - INTERVAL 3 DAY
),
(
  8,
  'announcement',
  'Quy Định Về Giữ Gìn Vệ Sinh Và Bảo Quản Mặt Thảm Sân Cầu Lông',
  'quy-dinh-ve-giu-gin-ve-sinh-va-bao-quan-mat-tham-san-cau-long',
  'Nhắc nhở quý khách hàng mang đúng giày đế kếp (non-marking) chuyên dụng để bảo vệ mặt sân tiêu chuẩn thi đấu.',
  '### Giới thiệu mặt sân chung\nĐể đảm bảo độ bám sân, độ đàn hồi của thảm cao su tiêu chuẩn và hạn chế chấn thương lật cổ chân cho người chơi, ban quản lý các cụm sân xin lưu ý các quy định sau:\n\n- **Trang phục:** Vui lòng chỉ sử dụng giày cầu lông chuyên dụng có đế kếp màu cao su hoặc đế không để lại vệt đen (non-marking).\n- **Vệ sinh:** Không mang đồ ăn, thức uống có gas, kẹo cao su vào khu vực thảm thi đấu.\n- **Hành vi:** Tuyệt đối không đập vợt xuống sàn hoặc kéo lê các vật cứng trên mặt thảm.\n\nCác trường hợp vi phạm có thể bị từ chối phục vụ và bồi thường thiệt hại theo quy định sân.',
  'assets/img/posts/announcement-court-rules.jpg',
  'published',
  NOW() - INTERVAL 2 DAY,
  NULL,
  0,
  2,
  NULL, NULL, NULL,
  NULL, NULL, 0, NULL,
  NOW() - INTERVAL 2 DAY,
  NOW() - INTERVAL 2 DAY
),
(
  9,
  'announcement',
  'Cảnh Báo Giả Mạo Nhân Viên GODMINTON Yêu Cầu Chuyển Tiền Cọc Ngoài Ứng Dụng',
  'canh-bao-gia-mao-nhan-vien-godminton-yeu-cau-chuyen-tien-coc-ngoai-ung-dung',
  'Khuyến cáo khách hàng chỉ thực hiện giao dịch quét mã VietQR tự động trực tiếp trên hệ thống chính thức.',
  '### Khuyến cáo an toàn thanh toán\nThời gian gần đây, ban quản trị phát hiện một số đối tượng giả mạo fanpage hoặc nhân viên tư vấn GODMINTON, liên hệ qua Zalo/Facebook cá nhân để yêu cầu khách hàng chuyển khoản đặt cọc vào tài khoản lạ.\n\n### Các nguyên tắc an toàn bắt buộc:\n1. **Chỉ thanh toán qua hệ thống:** GODMINTON chỉ xử lý thanh toán qua mã VietQR động được tạo trực tiếp tại trang [payment.php].\n2. **Nội dung chuyển khoản tự động:** Mã đơn hàng và số tiền được mã hóa chính xác trong mã QR, hệ thống tự động xác nhận trong vòng 30 giây.\n3. **Không chuyển tiền riêng:** Nhân viên hỗ trợ không bao giờ yêu cầu chuyển cọc vào số tài khoản cá nhân ngoài hệ thống.\n\nNếu gặp trường hợp khả nghi, vui lòng báo ngay qua trang Hỗ trợ của chúng tôi.',
  'assets/img/posts/announcement-payment-security.jpg',
  'published',
  NOW() - INTERVAL 1 DAY,
  NULL,
  0,
  2,
  NULL, NULL, NULL,
  NULL, NULL, 0, NULL,
  NOW() - INTERVAL 1 DAY,
  NOW() - INTERVAL 1 DAY
);

COMMIT;
