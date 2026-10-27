-- GODMINTON schema, exported 2026-10-23 22:17:45 +07:00
-- Select an EMPTY database before importing. No DROP/USE/CREATE DATABASE statements.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

-- Tables ordered by foreign-key dependencies for MariaDB/phpMyAdmin imports.

CREATE TABLE `account_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `actor_id` int(10) unsigned NOT NULL,
  `target_user_id` int(10) unsigned DEFAULT NULL,
  `request_id` bigint(20) unsigned DEFAULT NULL,
  `court_id` int(10) unsigned DEFAULT NULL,
  `action` varchar(60) NOT NULL,
  `reason` varchar(1000) NOT NULL,
  `details` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_target` (`target_user_id`,`id`),
  KEY `idx_audit_actor` (`actor_id`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `amenities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(30) NOT NULL,
  `label` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auth_rate_limits` (
  `key_hash` char(64) NOT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `window_started_at` datetime NOT NULL,
  PRIMARY KEY (`key_hash`),
  KEY `idx_auth_rate_window` (`window_started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `contacts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `email` varchar(254) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `news` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `summary` text NOT NULL,
  `content` mediumtext NOT NULL,
  `image_url` varchar(2048) DEFAULT NULL,
  `category` varchar(30) NOT NULL DEFAULT 'notice',
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_news_publication` (`status`,`published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `push_subscriptions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `endpoint` text NOT NULL,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_endpoint` (`endpoint`(191)),
  KEY `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('customer','owner','admin') NOT NULL DEFAULT 'customer',
  `avatar_url` varchar(255) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `session_version` int(10) unsigned NOT NULL DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL,
  `account_status` enum('active','locked') NOT NULL DEFAULT 'active',
  `owner_intent` tinyint(1) NOT NULL DEFAULT 0,
  `locked_reason` varchar(1000) DEFAULT NULL,
  `locked_at` datetime DEFAULT NULL,
  `locked_by` int(10) unsigned DEFAULT NULL,
  `pending_email` varchar(150) DEFAULT NULL,
  `area_label` varchar(100) DEFAULT NULL,
  `skill_level` varchar(20) DEFAULT NULL,
  `preferred_time` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `wards` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `old_area_label` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `courts` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` int(10) unsigned NOT NULL,
  `ward_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `address` varchar(255) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `price_per_hour` decimal(10,0) NOT NULL,
  `total_sub_courts` int(10) unsigned NOT NULL DEFAULT 6,
  `opening_time` time NOT NULL DEFAULT '06:00:00',
  `closing_time` time NOT NULL DEFAULT '22:00:00',
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approval_status` enum('approved','suspended') NOT NULL DEFAULT 'approved',
  `booking_open` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `fk_courts_owner` (`owner_id`),
  KEY `idx_courts_ward` (`ward_id`),
  KEY `idx_courts_location` (`latitude`,`longitude`),
  CONSTRAINT `fk_courts_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_courts_ward` FOREIGN KEY (`ward_id`) REFERENCES `wards` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `time_slots` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `court_id` int(10) unsigned NOT NULL,
  `sub_court_name` varchar(50) NOT NULL DEFAULT 'Thảm A1',
  `slot_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `price` decimal(10,0) DEFAULT NULL,
  `status` enum('available','holding','booked','blocked') NOT NULL DEFAULT 'available',
  `held_by` int(10) unsigned DEFAULT NULL,
  `hold_expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_slot_per_court` (`court_id`,`slot_date`,`start_time`,`sub_court_name`),
  KEY `fk_slots_held_by` (`held_by`),
  KEY `idx_slots_lookup` (`court_id`,`slot_date`,`status`),
  CONSTRAINT `fk_slots_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_slots_held_by` FOREIGN KEY (`held_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `vouchers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `created_by` int(10) unsigned NOT NULL,
  `owner_id` int(10) unsigned DEFAULT NULL,
  `court_id` int(10) unsigned DEFAULT NULL,
  `discount_percent` tinyint(3) unsigned NOT NULL,
  `max_discount` decimal(12,0) DEFAULT NULL,
  `min_order` decimal(12,0) NOT NULL DEFAULT 0,
  `start_at` datetime NOT NULL,
  `end_at` datetime NOT NULL,
  `usage_limit` int(10) unsigned DEFAULT NULL,
  `used_count` int(10) unsigned NOT NULL DEFAULT 0,
  `per_user_limit` int(10) unsigned NOT NULL DEFAULT 1,
  `first_booking_only` tinyint(1) NOT NULL DEFAULT 0,
  `funded_by` enum('platform','owner') NOT NULL,
  `status` enum('active','disabled') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_code` (`code`),
  KEY `idx_vouchers_owner` (`owner_id`),
  KEY `idx_vouchers_court` (`court_id`),
  KEY `idx_vouchers_status_dates` (`status`,`start_at`,`end_at`),
  KEY `fk_vouchers_created_by` (`created_by`),
  CONSTRAINT `fk_vouchers_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`),
  CONSTRAINT `fk_vouchers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_vouchers_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `bookings` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `court_id` int(10) unsigned NOT NULL,
  `voucher_id` int(10) unsigned DEFAULT NULL,
  `user_membership_id` int(10) unsigned DEFAULT NULL,
  `booking_code` varchar(30) DEFAULT NULL,
  `court_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `discount_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `membership_discount_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `discount_funded_by` enum('platform','owner') DEFAULT NULL,
  `platform_fee` decimal(12,0) NOT NULL DEFAULT 0,
  `owner_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `total_price` decimal(12,0) NOT NULL,
  `deposit_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `payment_method` varchar(30) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_booking_code` (`booking_code`),
  KEY `idx_bookings_user` (`user_id`),
  KEY `idx_bookings_court` (`court_id`),
  KEY `fk_bookings_voucher` (`voucher_id`),
  CONSTRAINT `fk_bookings_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`),
  CONSTRAINT `fk_bookings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_bookings_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `account_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `request_id` bigint(20) unsigned DEFAULT NULL,
  `email_sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_account_notices` (`user_id`,`id`),
  CONSTRAINT `fk_account_notice_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `auth_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `purpose` enum('verify','reset','delete','email_change') NOT NULL,
  `token_hash` char(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_auth_user` (`user_id`,`purpose`),
  KEY `idx_auth_expiry` (`expires_at`),
  CONSTRAINT `fk_auth_token_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `booking_slots` (
  `booking_id` int(10) unsigned NOT NULL,
  `slot_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`booking_id`,`slot_id`),
  KEY `fk_bs_slot` (`slot_id`),
  CONSTRAINT `fk_bs_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bs_slot` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `court_amenities` (
  `court_id` int(10) unsigned NOT NULL,
  `amenity_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`court_id`,`amenity_id`),
  KEY `fk_ca_amenity` (`amenity_id`),
  CONSTRAINT `fk_ca_amenity` FOREIGN KEY (`amenity_id`) REFERENCES `amenities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ca_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `court_images` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `court_id` int(10) unsigned NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_images_court` (`court_id`),
  CONSTRAINT `fk_images_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `owner_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `kind` enum('new','claim','update') NOT NULL,
  `court_id` int(10) unsigned DEFAULT NULL,
  `status` enum('draft','pending','needs_info','approved','rejected','withdrawn') NOT NULL DEFAULT 'draft',
  `representative_name` varchar(150) NOT NULL DEFAULT '',
  `representative_phone` varchar(30) NOT NULL DEFAULT '',
  `evidence_note` text DEFAULT NULL,
  `evidence_url` varchar(2000) DEFAULT NULL,
  `payload` longtext NOT NULL,
  `review_note` text DEFAULT NULL,
  `reviewed_by` int(10) unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `revision` int(10) unsigned NOT NULL DEFAULT 1,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_owner_queue` (`status`,`updated_at`),
  KEY `idx_owner_applicant` (`user_id`,`status`),
  KEY `idx_owner_court` (`court_id`),
  CONSTRAINT `fk_owner_request_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`),
  CONSTRAINT `fk_owner_request_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `owner_request_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned NOT NULL,
  `actor_id` int(10) unsigned NOT NULL,
  `action` varchar(40) NOT NULL,
  `note` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_owner_events` (`request_id`,`id`),
  CONSTRAINT `fk_owner_event_request` FOREIGN KEY (`request_id`) REFERENCES `owner_requests` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `posts` (
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
  `event_start` datetime DEFAULT NULL COMMENT 'Thời gian bắt đầu sự kiện',
  `event_end` datetime DEFAULT NULL COMMENT 'Thời gian kết thúc sự kiện',
  `event_location` varchar(255) DEFAULT NULL COMMENT 'Địa điểm tổ chức sự kiện',
  `price` decimal(12,0) DEFAULT NULL COMMENT 'Giá niêm yết gói hội viên (VNĐ)',
  `duration_days` int(10) unsigned DEFAULT NULL COMMENT 'Thời hạn hiệu lực của gói (ngày)',
  `discount_percent` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Phần trăm giảm giá khi đặt sân',
  `benefits` text DEFAULT NULL COMMENT 'Danh sách quyền lợi của gói (mỗi dòng một ý)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug` (`slug`),
  KEY `idx_posts_type_status_dates` (`type`,`status`,`publish_at`,`expire_at`),
  KEY `idx_posts_pinned` (`is_pinned`),
  KEY `idx_posts_created_by` (`created_by`),
  CONSTRAINT `fk_posts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `push_reminder_dispatches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL,
  `reminder_type` varchar(40) NOT NULL DEFAULT 'start_30_minutes',
  `status` enum('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `last_error` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_push_reminder_booking_type` (`booking_id`,`reminder_type`),
  KEY `idx_push_reminder_status` (`status`,`created_at`),
  CONSTRAINT `fk_push_reminder_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `court_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `rating` tinyint(3) unsigned NOT NULL,
  `comment` varchar(1000) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_review_court_user` (`court_id`,`user_id`),
  KEY `idx_reviews_user` (`user_id`),
  CONSTRAINT `fk_godm_review_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_godm_review_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_memberships` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'ID người dùng (users.id)',
  `post_id` int(10) unsigned NOT NULL COMMENT 'ID bài viết gói trong posts',
  `tier` enum('silver','gold','platinum') NOT NULL COMMENT 'Hạng hội viên: silver, gold, platinum',
  `discount_percent` tinyint(3) unsigned NOT NULL DEFAULT 0 COMMENT 'Phần trăm giảm giá thực tế được áp dụng',
  `price_paid` decimal(12,0) NOT NULL DEFAULT 0 COMMENT 'Số tiền đã thanh toán',
  `duration_days` int(10) unsigned NOT NULL DEFAULT 30 COMMENT 'Thời hạn của gói (ngày)',
  `start_date` datetime NOT NULL COMMENT 'Ngày bắt đầu kích hoạt',
  `end_date` datetime NOT NULL COMMENT 'Ngày hết hạn gói',
  `status` enum('pending','active','expired','cancelled','rejected') NOT NULL DEFAULT 'pending' COMMENT 'Trạng thái gói: pending (chờ admin duyệt), active, expired, cancelled, rejected',
  `payment_method` varchar(50) NOT NULL DEFAULT 'VietQR' COMMENT 'Phương thức thanh toán',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_um_user_status_end` (`user_id`,`status`,`end_date`),
  KEY `idx_um_post` (`post_id`),
  CONSTRAINT `fk_um_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_um_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `voucher_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `booking_id` int(10) unsigned NOT NULL,
  `discount_amount` decimal(12,0) NOT NULL,
  `used_at` datetime NOT NULL DEFAULT current_timestamp(),
  `released_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_usage_booking` (`booking_id`),
  KEY `idx_vu_voucher_user` (`voucher_id`,`user_id`),
  KEY `idx_vu_user` (`user_id`),
  CONSTRAINT `fk_vu_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`),
  CONSTRAINT `fk_vu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_vu_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;
