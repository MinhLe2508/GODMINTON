-- database/migration_memberships.sql
-- GODMINTON - Migration tính năng Gói hội viên ưu đãi: Silver (5%), Gold (10%), Platinum (15%)
-- Tương thích: MariaDB 10.4 (XAMPP) & MySQL 8.0

-- 1. Bổ sung trường tỷ lệ giảm giá vào bảng `posts` (nếu chưa có)
-- Lưu ý: Nếu chạy trực tiếp trên MySQL không hỗ trợ IF NOT EXISTS ở ALTER TABLE, dùng migrate_memberships.php
ALTER TABLE `posts` ADD COLUMN IF NOT EXISTS `discount_percent` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Phần trăm giảm giá khi đặt sân' AFTER `duration_days`;

-- 2. Tạo bảng lưu trữ thông tin đăng ký gói hội viên của người dùng
CREATE TABLE IF NOT EXISTS `user_memberships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'ID người dùng (users.id)',
  `post_id` int(10) unsigned NOT NULL COMMENT 'ID bài viết gói trong posts',
  `tier` enum('silver','gold','platinum') NOT NULL COMMENT 'Hạng hội viên: silver (5%), gold (10%), platinum (15%)',
  `discount_percent` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Phần trăm giảm giá thực tế được áp dụng',
  `price_paid` decimal(12,0) NOT NULL DEFAULT 0 COMMENT 'Số tiền đã thanh toán',
  `duration_days` int(10) unsigned NOT NULL DEFAULT 30 COMMENT 'Thời hạn của gói (ngày)',
  `start_date` datetime NOT NULL COMMENT 'Ngày bắt đầu kích hoạt',
  `end_date` datetime NOT NULL COMMENT 'Ngày hết hạn gói',
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active' COMMENT 'Trạng thái gói',
  `payment_method` varchar(50) NOT NULL DEFAULT 'VietQR' COMMENT 'Phương thức thanh toán',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_um_user_status_end` (`user_id`,`status`,`end_date`),
  KEY `idx_um_post` (`post_id`),
  CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_um_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bổ sung liên kết gói hội viên vào bảng `bookings`
ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `user_membership_id` int(10) unsigned DEFAULT NULL AFTER `voucher_id`;
ALTER TABLE `bookings` ADD COLUMN IF NOT EXISTS `membership_discount_amount` decimal(12,0) NOT NULL DEFAULT 0 AFTER `discount_amount`;

-- 4. Cập nhật dữ liệu 3 gói hội viên chuẩn: Silver (5%), Gold (10%), Platinum (15%)
UPDATE `posts` SET
  `title` = 'Gói Hội Viên Bạc (Silver Feather) - Ưu Đãi 5% Tiền Sân',
  `slug` = 'goi-hoi-vien-bac-silver-feather',
  `summary` = 'Gói hội viên 30 ngày linh hoạt dành cho người chơi thường xuyên, tự động giảm ngay 5% cho mọi lượt đặt sân trên toàn hệ thống GODMINTON.',
  `price` = 199000,
  `duration_days` = 30,
  `discount_percent` = 5,
  `benefits` = 'Giảm 5% trực tiếp trên tổng tiền sân cho mọi lượt đặt sân\nƯu tiên giữ slot trước 3 ngày so với khách thông thường\nMiễn phí đổi lịch trước 12 tiếng không tính phí\nHỗ trợ tư vấn đặt sân và kiểm tra lịch nhanh qua hotline'
WHERE `id` = 4 OR `slug` LIKE '%silver%';

UPDATE `posts` SET
  `title` = 'Gói Hội Viên Vàng (Golden Racket) - Ưu Đãi 10% Tiền Sân',
  `slug` = 'goi-hoi-vien-vang-golden-racket',
  `summary` = 'Gói hội viên 90 ngày tối ưu chi phí cho các câu lạc bộ và nhóm cố định hàng tuần với mức giảm giá 10% toàn sàn.',
  `price` = 499000,
  `duration_days` = 90,
  `discount_percent` = 10,
  `benefits` = 'Giảm 10% trực tiếp trên tổng tiền sân cho mọi lượt đặt sân\nƯu tiên giữ khung giờ vàng trước 7 ngày\nTặng 01 ống cầu lông thi đấu chuẩn khi kích hoạt\nMiễn phí đổi lịch hoặc hủy trước 6 tiếng'
WHERE `id` = 5 OR `slug` LIKE '%golden%';

UPDATE `posts` SET
  `title` = 'Gói Hội Viên Bạch Kim (Platinum Ace) - Ưu Đãi 15% Tiền Sân',
  `slug` = 'goi-hoi-vien-bach-kim-platinum-ace',
  `summary` = 'Gói hội viên cao cấp nhất 365 ngày dành cho vận động viên, hội nhóm và doanh nghiệp với mức chiết khấu tối đa 15% toàn hệ thống.',
  `price` = 1490000,
  `duration_days` = 365,
  `discount_percent` = 15,
  `benefits` = 'Giảm 15% tối đa trên tổng hóa đơn đặt sân tại tất cả các cụm sân đối tác\nƯu tiên giữ lịch cố định tháng và đặt trước 14 ngày\nMiễn phí hủy đơn trước 4 tiếng\nVé VIP tham dự các giải đấu mở rộng do GODMINTON tổ chức\nĐược tặng áo đấu độc quyền và quà tri ân sinh nhật'
WHERE `id` = 6 OR `slug` LIKE '%diamond%' OR `slug` LIKE '%platinum%';
