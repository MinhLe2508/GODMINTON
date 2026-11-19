-- database/migration_posts.sql
-- GODMINTON - Migration tính năng Khám phá: Bài viết (Sự kiện, Gói hội viên ưu đãi, Thông báo)
-- Tương thích: MariaDB 10.4 (XAMPP) & MySQL 8.0
-- Không DROP / TRUNCATE dữ liệu hiện có.
--
-- Cách hoàn tác khi cần thiết:
--   DROP TABLE IF EXISTS `posts`;

CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` enum('event','membership','announcement') NOT NULL COMMENT 'Loại bài: event (Sự kiện), membership (Gói hội viên), announcement (Thông báo)',
  `title` varchar(255) NOT NULL COMMENT 'Tiêu đề bài viết',
  `slug` varchar(255) NOT NULL COMMENT 'Đường dẫn tĩnh thân thiện (duy nhất)',
  `summary` text DEFAULT NULL COMMENT 'Tóm tắt bài viết ngắn gọn',
  `content` mediumtext NOT NULL COMMENT 'Nội dung chi tiết (Markdown giới hạn / văn bản an toàn)',
  `cover_image` varchar(2048) DEFAULT NULL COMMENT 'Đường dẫn ảnh bìa bài viết',
  `status` enum('draft','published','archived') NOT NULL DEFAULT 'draft' COMMENT 'Trạng thái: draft (Nháp), published (Xuất bản), archived (Lưu trữ/Ẩn)',
  `publish_at` datetime DEFAULT NULL COMMENT 'Thời điểm bài bắt đầu hiển thị (NULL = ngay lập tức)',
  `expire_at` datetime DEFAULT NULL COMMENT 'Thời điểm bài tự động ẩn (NULL = không giới hạn)',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1: Ghim bài lên đầu danh sách, 0: Bình thường',
  `created_by` int(10) unsigned NOT NULL COMMENT 'ID quản trị viên tạo bài (users.id)',
  
  -- Trường đặc thù cho Sự kiện (type = 'event')
  `event_start` datetime DEFAULT NULL COMMENT 'Thời gian bắt đầu sự kiện',
  `event_end` datetime DEFAULT NULL COMMENT 'Thời gian kết thúc sự kiện',
  `event_location` varchar(255) DEFAULT NULL COMMENT 'Địa điểm tổ chức sự kiện',
  
  -- Trường đặc thù cho Gói hội viên ưu đãi (type = 'membership')
  `price` decimal(12,0) DEFAULT NULL COMMENT 'Giá niêm yết gói hội viên (VNĐ)',
  `duration_days` int(10) unsigned DEFAULT NULL COMMENT 'Thời hạn hiệu lực của gói (ngày)',
  `discount_percent` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Phần trăm giảm giá khi đặt sân (5% / 10% / 15%)',
  `benefits` text DEFAULT NULL COMMENT 'Danh sách quyền lợi của gói (mỗi dòng một ý)',
  
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug` (`slug`),
  KEY `idx_posts_type_status_dates` (`type`,`status`,`publish_at`,`expire_at`),
  KEY `idx_posts_pinned` (`is_pinned`),
  KEY `idx_posts_created_by` (`created_by`),
  CONSTRAINT `fk_posts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
