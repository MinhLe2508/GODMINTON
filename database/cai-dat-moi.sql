-- GODMINTON - CHI IMPORT VAO DATABASE MOI TRONG.
-- Khong chua du lieu giao dich, tai khoan ca nhan hoac khoa dich vu.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;


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
  `payment_status` enum('unpaid','deposit_paid','refunded') NOT NULL DEFAULT 'unpaid',
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
  `voucher_id` int(10) unsigned DEFAULT NULL COMMENT 'ID mã giảm giá đính kèm bài viết',
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
  KEY `fk_posts_voucher` (`voucher_id`),
  CONSTRAINT `fk_posts_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_posts_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `booking_slots` (
  `booking_id` int(10) unsigned NOT NULL,
  `slot_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`booking_id`,`slot_id`),
  KEY `fk_bs_slot` (`slot_id`),
  CONSTRAINT `fk_bs_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_bs_slot` FOREIGN KEY (`slot_id`) REFERENCES `time_slots` (`id`)
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

CREATE TABLE `user_vouchers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL COMMENT 'ID người dùng đã nhận mã',
  `voucher_id` int(10) unsigned NOT NULL COMMENT 'ID mã giảm giá',
  `post_id` int(10) unsigned DEFAULT NULL COMMENT 'ID bài viết nguồn mà người dùng bấm nhận mã',
  `collected_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_voucher` (`user_id`, `voucher_id`),
  KEY `idx_uv_user` (`user_id`),
  KEY `idx_uv_voucher` (`voucher_id`),
  KEY `idx_uv_post` (`post_id`),
  CONSTRAINT `fk_uv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uv_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uv_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL
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

CREATE TABLE `payments` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(10) unsigned NOT NULL,
  `payment_code` varchar(20) NOT NULL,
  `amount` decimal(12,0) NOT NULL,
  `paid_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `status` enum('pending','paid','expired','cancelled','late_paid','refunded') NOT NULL DEFAULT 'pending',
  `expires_at` datetime NOT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_code` (`payment_code`),
  UNIQUE KEY `uq_payment_booking` (`booking_id`),
  KEY `idx_pay_status_exp` (`status`,`expires_at`),
  CONSTRAINT `fk_pay_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sepay_transactions` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sepay_id` bigint(20) unsigned NOT NULL,
  `payment_id` int(10) unsigned DEFAULT NULL,
  `gateway` varchar(100) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `transfer_type` varchar(5) DEFAULT NULL,
  `transfer_amount` decimal(14,0) NOT NULL DEFAULT 0,
  `content` varchar(500) DEFAULT NULL,
  `code` varchar(100) DEFAULT NULL,
  `reference_code` varchar(100) DEFAULT NULL,
  `transaction_date` datetime DEFAULT NULL,
  `raw_payload` text NOT NULL,
  `result` varchar(30) NOT NULL DEFAULT 'received',
  `note` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sepay_id` (`sepay_id`),
  KEY `idx_st_payment` (`payment_id`),
  KEY `idx_st_result` (`result`),
  CONSTRAINT `fk_st_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS=1;


-- Source: migration_sepay_payments.sql
-- Migration: Tích hợp thanh toán tiền cọc tự động qua SePay
-- Trong phpMyAdmin: CHỌN DATABASE của website rồi import file này.
-- Có thể import lại nhiều lần mà không gây lỗi trùng bảng/cột.

SET NAMES utf8mb4;

-- 1. Mở rộng trạng thái đơn đặt và thêm các cột quản lý cọc / check-in vào bảng bookings
ALTER TABLE `bookings`
  MODIFY COLUMN `status` ENUM('pending','confirmed','checked_in','completed','no_show','cancelled') NOT NULL DEFAULT 'pending';

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME='payment_status'),
    'DO 0',
    'ALTER TABLE `bookings` ADD COLUMN `payment_status` ENUM(\'unpaid\',\'deposit_paid\',\'refunded\') NOT NULL DEFAULT \'unpaid\' AFTER `status`');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME='is_deposit_refunded'),
    'DO 0',
    'ALTER TABLE `bookings` ADD COLUMN `is_deposit_refunded` TINYINT(1) NOT NULL DEFAULT 0 AFTER `deposit_amount`');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME='refund_note'),
    'DO 0',
    'ALTER TABLE `bookings` ADD COLUMN `refund_note` VARCHAR(500) NULL DEFAULT NULL AFTER `is_deposit_refunded`');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME='remaining_paid'),
    'DO 0',
    'ALTER TABLE `bookings` ADD COLUMN `remaining_paid` DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `deposit_amount`');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME='checked_in_at'),
    'DO 0',
    'ALTER TABLE `bookings` ADD COLUMN `checked_in_at` DATETIME NULL DEFAULT NULL AFTER `status`');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME='completed_at'),
    'DO 0',
    'ALTER TABLE `bookings` ADD COLUMN `completed_at` DATETIME NULL DEFAULT NULL AFTER `checked_in_at`');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='time_slots' AND COLUMN_NAME='note'),
    'DO 0',
    'ALTER TABLE `time_slots` ADD COLUMN `note` VARCHAR(255) NULL DEFAULT NULL AFTER `status`');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

-- Bảng payments: Lưu yêu cầu thanh toán tiền cọc đặt sân & thanh toán gói hội viên qua SePay
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED DEFAULT NULL,
  `membership_id` INT UNSIGNED DEFAULT NULL,
  `payment_code` VARCHAR(20) NOT NULL,
  `amount` DECIMAL(12,0) NOT NULL,
  `paid_amount` DECIMAL(12,0) NOT NULL DEFAULT 0,
  `status` ENUM('pending','paid','expired','cancelled','late_paid','refunded') NOT NULL DEFAULT 'pending',
  `expires_at` DATETIME NOT NULL,
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payment_code` (`payment_code`),
  UNIQUE KEY `uq_payment_booking` (`booking_id`),
  UNIQUE KEY `uq_payment_membership` (`membership_id`),
  KEY `idx_pay_status_exp` (`status`, `expires_at`),
  CONSTRAINT `fk_pay_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Đảm bảo bảng payments đã tồn tại từ trước cũng hỗ trợ membership_id và booking_id DEFAULT NULL
ALTER TABLE `payments` MODIFY COLUMN `booking_id` INT UNSIGNED DEFAULT NULL;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='payments' AND COLUMN_NAME='membership_id'),
    'DO 0',
    'ALTER TABLE `payments` ADD COLUMN `membership_id` INT UNSIGNED DEFAULT NULL AFTER `booking_id`, ADD UNIQUE KEY `uq_payment_membership` (`membership_id`)');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

-- Bảng sepay_transactions: Lưu nguyên văn mọi webhook biến động số dư từ SePay
CREATE TABLE IF NOT EXISTS `sepay_transactions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sepay_id` BIGINT UNSIGNED NOT NULL,
  `payment_id` INT UNSIGNED DEFAULT NULL,
  `gateway` VARCHAR(100) DEFAULT NULL,
  `account_number` VARCHAR(50) DEFAULT NULL,
  `transfer_type` VARCHAR(5) DEFAULT NULL,
  `transfer_amount` DECIMAL(14,0) NOT NULL DEFAULT 0,
  `content` VARCHAR(500) DEFAULT NULL,
  `code` VARCHAR(100) DEFAULT NULL,
  `reference_code` VARCHAR(100) DEFAULT NULL,
  `transaction_date` DATETIME DEFAULT NULL,
  `raw_payload` TEXT NOT NULL,
  `result` VARCHAR(30) NOT NULL DEFAULT 'received',
  `note` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sepay_id` (`sepay_id`),
  KEY `idx_st_payment` (`payment_id`),
  KEY `idx_st_result` (`result`),
  CONSTRAINT `fk_st_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Bổ sung cột voucher_id cho bảng posts & bảng user_vouchers (từ bản cập nhật Kho Voucher & Hội Viên)
SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='posts' AND COLUMN_NAME='voucher_id'),
    'DO 0',
    'ALTER TABLE `posts` ADD COLUMN `voucher_id` INT UNSIGNED NULL DEFAULT NULL AFTER `type`');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

CREATE TABLE IF NOT EXISTS `user_vouchers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `voucher_id` INT UNSIGNED NOT NULL,
  `post_id` INT UNSIGNED DEFAULT NULL,
  `collected_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_voucher` (`user_id`, `voucher_id`),
  KEY `idx_uv_user` (`user_id`),
  KEY `idx_uv_voucher` (`voucher_id`),
  KEY `idx_uv_post` (`post_id`),
  CONSTRAINT `fk_uv_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uv_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_uv_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Bảng Web Push Subscriptions & Nhắc lịch thi đấu tự động (push_reminder_dispatches)
CREATE TABLE IF NOT EXISTS `push_subscriptions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `endpoint` VARCHAR(500) NOT NULL,
  `p256dh` VARCHAR(255) NOT NULL,
  `auth` VARCHAR(255) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_push_endpoint` (`endpoint`),
  KEY `idx_push_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `push_reminder_dispatches` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT(10) UNSIGNED NOT NULL,
  `reminder_type` VARCHAR(40) NOT NULL DEFAULT 'start_30_minutes',
  `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `attempts` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `last_error` VARCHAR(500) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `sent_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_push_reminder_booking_type` (`booking_id`, `reminder_type`),
  KEY `idx_push_reminder_status` (`status`, `created_at`),
  CONSTRAINT `fk_push_reminder_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Bảng remember_tokens: Lưu token Ghi nhớ đăng nhập (Split-Token: Selector + Validator băm SHA-256)
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `selector` CHAR(24) NOT NULL,
  `validator_hash` CHAR(64) NOT NULL,
  `session_version` INT UNSIGNED NOT NULL DEFAULT 0,
  `user_agent_hash` CHAR(64) DEFAULT NULL,
  `ip_prefix` VARCHAR(45) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `last_used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_remember_selector` (`selector`),
  KEY `idx_remember_user` (`user_id`),
  KEY `idx_remember_expires` (`expires_at`),
  CONSTRAINT `fk_remember_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;





-- Source: seed.sql
-- GODMINTON installation reference data, exported 2026-10-22 10:37:08 +07:00
-- Import AFTER schema.sql into a new database only.
-- Development accounts are generated, not copied from live users.
-- No bookings, slots, contacts, device subscriptions or private accounts are exported.
SET NAMES utf8mb4;
START TRANSACTION;

INSERT INTO `users` (`id`,`name`,`email`,`phone`,`password_hash`,`role`,`is_verified`) VALUES
('1','Khách phát triển','khach1@gmail.com','0900000001','$2y$10$mYQb8pqZF/BBgoc8A7fQV.MjciS9GjEHxNV2k3xvWcUrAP/bLN6M2','customer','1'),
('2','Quản trị phát triển','admin@godminton.vn','0900000002','$2y$10$Z7puGyRHZ/aUAEPLpa1aueb93KWyLEwIRY/o3WWQXhtlbQ0pmaREq','admin','1'),
('3','Chủ sân phát triển','owner.q1@godminton.vn','0900000003','$2y$10$7g0yIFZOZjnG7KnclBTqK.tbSkDQxYw3RH.BZSXngj.yYlh9NJXAe','owner','1');

INSERT INTO `wards` (`id`,`name`,`old_area_label`) VALUES
('1','Sài Gòn','Quận 1 (cũ)'),
('2','Chợ Lớn','Quận 5 (cũ)'),
('3','Bình Tây','Quận 6 (cũ)'),
('4','Bình Tiên','Quận 6 (cũ)'),
('5','Bình Phú','Quận 6 (cũ)'),
('6','Phú Lâm','Quận 6 (cũ)'),
('7','Minh Phụng','Quận 11 (cũ)'),
('8','Bến Nghé','Quận 1 (cũ)'),
('9','Phường 26','Bình Thạnh (cũ)'),
('10','Phường 4','Tân Bình (cũ)'),
('11','Phường 3','Gò Vấp (cũ)'),
('12','Tân Phong','Quận 7 (cũ)'),
('13','Bình Thọ','TP. Thủ Đức (cũ)'),
('14','Bình Chuẩn','Thuận An, Bình Dương'),
('15','Bến Thành','Quận 1 (cũ)'),
('16','Cầu Ông Lãnh','Quận 1 (cũ)'),
('17','An Khánh','Quận 2 (cũ)'),
('18','Nhiêu Lộc','Quận 3 (cũ)'),
('19','Xuân Hòa','Quận 3 (cũ)'),
('20','Khánh Hội','Quận 4 (cũ)'),
('21','Xóm Chiếu','Quận 4 (cũ)'),
('22','Chợ Quán','Quận 5 (cũ)'),
('23','An Đông','Quận 5 (cũ)'),
('24','Tân Thuận','Quận 7 (cũ)'),
('25','Tân Hưng','Quận 7 (cũ)'),
('26','Bình Phú','Quận 8 (cũ)'),
('27','Phú Định','Quận 8 (cũ)'),
('28','Phước Long','Quận 9 (cũ)'),
('29','Long Trường','Quận 9 (cũ)'),
('30','Vườn Lài','Quận 10 (cũ)'),
('31','Hòa Hưng','Quận 10 (cũ)'),
('32','Phú Thọ','Quận 11 (cũ)'),
('33','Tân Thới Hiệp','Quận 12 (cũ)'),
('34','Bình Quới','Bình Thạnh (cũ)'),
('35','Bình Thạnh','Bình Thạnh (cũ)'),
('36','Tân Sơn Nhất','Tân Bình (cũ)'),
('37','Tân Sơn Hòa','Tân Bình (cũ)'),
('38','Tân Bình','Tân Bình (cũ)'),
('39','An Hội Tây','Gò Vấp (cũ)'),
('40','Đức Nhuận','Phú Nhuận (cũ)'),
('41','Phú Nhuận','Phú Nhuận (cũ)'),
('42','Linh Xuân','Thủ Đức (cũ)'),
('43','Thủ Đức','Thủ Đức (cũ)'),
('44','Tam Bình','Thủ Đức (cũ)'),
('45','Bình Trị Đông','Bình Tân (cũ)'),
('46','Tân Phú','Tân Phú (cũ)'),
('47','Tân Sơn Nhì','Tân Phú (cũ)');

INSERT INTO `amenities` (`id`,`code`,`label`) VALUES
('1','aircon','Điều hòa'),
('2','parking','Bãi đỗ xe'),
('3','shower','Phòng tắm'),
('4','rental','Cho thuê vợt'),
('5','cafe','Canteen/Cafe'),
('6','locker','Tủ khóa'),
('7','wifi','Wi-Fi'),
('8','indoor','Sân trong nhà');

INSERT INTO `courts` (`id`,`owner_id`,`ward_id`,`name`,`address`,`latitude`,`longitude`,`price_per_hour`,`total_sub_courts`,`opening_time`,`closing_time`,`description`,`status`,`created_at`,`approval_status`,`booking_open`) VALUES
('1','3','8','Sân Cầu Lông Tao Đàn','Số 01 Huyền Trân Công Chúa, Phường Bến Thành, Quận 1, TP.HCM','10.7747674','106.6947053','105000','6','06:00:00','23:00:00','Tổ hợp sân cầu lông hiện đại ngay trung tâm Quận 1, 6 sân thảm tiêu chuẩn BWF, hệ thống chiếu sáng LED chống lóa. Phù hợp cho người đi làm muốn luyện tập sau giờ hành chính.','inactive','2026-10-13 21:35:57','approved','1'),
('2','3','35','Sân Cầu Lông Nguyễn Xí','161 Đ. Nguyễn Xí, Phường Bình Thạnh, Thành phố Hồ Chí Minh','10.8141754','106.7086153','80000','8','05:30:00','23:00:00','[catalog:ff9be98fee680c462289]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-thanh\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-13 21:35:57','approved','1'),
('3','3','10','Sân Cầu Lông 18B Cộng Hoà','18B Cộng Hoà, Phường 4, Tân Bình, TP.HCM','10.8051712','106.6556922','80000','10','05:00:00','23:30:00','Nằm gần sân bay Tân Sơn Nhất, 10 sân thảm tiêu chuẩn thi đấu, phù hợp cho câu lạc bộ và giải phong trào. Có khu vực ngồi chờ rộng rãi và quầy đồ uống thể thao.','inactive','2026-10-13 21:35:57','approved','1'),
('4','3','11','Sân Cầu Lông Đại Phát','313/17 Đ. Phan Huy Ích, Phường 3, Gò Vấp, TP.HCM','10.8387338','106.6341503','75000','5','06:00:00','22:30:00','Sân cầu lông gia đình thân thiện, 5 sân thảm Victor cao su tự nhiên, thích hợp cho mọi lứa tuổi. Có khu vực vui chơi trẻ em và phòng nghỉ ngơi sau khi chơi.','inactive','2026-10-13 21:35:57','approved','1'),
('5','3','12','Sân Cầu Lông Victory Nam Sài Gòn','157 Nguyễn Văn Quỳ, Phường Tân Phong, Quận 7, TP.HCM','10.7421688','106.7381973','90000','4','06:00:00','22:00:00','Sân cầu lông cao cấp trong khu đô thị Phú Mỹ Hưng, 4 sân thảm Li-Ning nhập khẩu, có đội ngũ HLV chuyên nghiệp từ đội tuyển quốc gia. Dịch vụ 5 sao, phòng tắm xông hơi sau tập.','inactive','2026-10-13 21:35:57','approved','1'),
('6','3','13','Sân Cầu Lông Aurora Sport','158 Đường số 17, Phường Linh Xuân, TP. Thủ Đức, TP.HCM','10.8662804','106.7875994','80000','7','05:00:00','23:00:00','Nằm gần ĐH Bách Khoa và ĐH Quốc Gia, là địa điểm yêu thích của sinh viên và giảng viên. 7 sân thảm giá sinh viên, mở cửa sớm từ 5 giờ sáng phục vụ buổi tập sáng.','inactive','2026-10-13 21:35:57','approved','1'),
('7','3','14','Sân Cầu Lông Vĩnh Lộc','342 Quách Điêu, Xã Vĩnh Lộc, TP.HCM','10.8269257','106.5728652','65000','12','05:30:00','23:00:00','Sân cầu lông lớn nhất Bình Dương với 12 sân thảm tiêu chuẩn, sân gần khu công nghiệp VSIP tiện cho công nhân viên sau giờ làm. Có phòng gym và bể bơi đi kèm.','active','2026-10-13 21:35:57','approved','1'),
('8','3','8','Sân Cầu Lông Cung Văn Hóa Lao Động','55B Nguyễn Thị Minh Khai, Phường Bến Nghé, Quận 1, TP.HCM','10.7763541','106.6931260','110000','6','06:00:00','22:00:00','Cụm sân cầu lông truyền thống trung tâm Quận 1, sàn gỗ chuyên dụng, đèn LED chống chói.','inactive','2026-10-15 23:18:01','approved','1'),
('9','3','1','CLB Cầu Lông Tao Đàn','1 Huyền Trân Công Chúa, Phường Bến Thành, Quận 1, TP.HCM','10.7747674','106.6947053','105000','6','06:00:00','22:00:00','Sân cầu lông trong khuôn viên công viên Tao Đàn xanh mát, 4 sân thảm Yonex cao cấp.','inactive','2026-10-15 23:18:02','approved','1'),
('10','3','1','Sân Cầu Lông Cầu Kho Q1','Số 2 Nguyễn Văn Cừ, Phường Cầu Kho, Quận 1, TP.HCM','10.7547701','106.6865755','90000','6','06:00:00','22:00:00','Sân cầu lông khu vực Cầu Kho, mặt thảm êm, không gian tập luyện câu lạc bộ thân thiện.','inactive','2026-10-15 23:18:06','approved','1'),
('11','3','1','CLB Cầu Lông Nguyễn Cư Trinh','Hẻm 189 Nguyễn Trãi, Phường Nguyễn Cư Trinh, Quận 1, TP.HCM','10.7605820','106.6861450','95000','6','06:00:00','22:00:00','Sân cầu lông chất lượng cao, trần cao thoáng đãng, phục vụ người chơi thể thao phong trào.','inactive','2026-10-15 23:18:08','approved','1'),
('12','3','1','CLB Cầu Lông Hồ Xuân Hương','2 Hồ Xuân Hương, Phường Võ Thị Sáu, Quận 3, TP.HCM','10.7778000','106.6885000','100000','6','06:00:00','22:00:00','Trung tâm thể dục thể thao Quận 3, nhiều sân thảm đạt chuẩn thi đấu thành phố.','inactive','2026-10-15 23:18:09','approved','1'),
('13','3','1','Sân Cầu Lông Rạng Đông Q3','269 Rạch Bùng Binh, Phường Võ Thị Sáu, Quận 3, TP.HCM','10.7821652','106.6801638','85000','6','06:00:00','22:00:00','Sân cầu lông thảm chất lượng tốt, căng vợt lấy ngay, giá hợp lý cho sinh viên.','inactive','2026-10-15 23:18:11','approved','1'),
('14','3','1','CLB Cầu Lông Ga Sài Gòn','01 Nguyễn Thông, Phường 9, Quận 3, TP.HCM','10.7825189','106.6768555','80000','6','06:00:00','22:00:00','Sân cầu lông gần Ga Sài Gòn, bãi đỗ xe máy rộng, hoạt động liên tục các ngày trong tuần.','inactive','2026-10-15 23:18:13','approved','1'),
('15','3','1','Sân Cầu Lông Nam Kỳ Sport','Nam Kỳ Khởi Nghĩa, Phường Võ Thị Sáu, Quận 3, TP.HCM','10.7845000','106.6905000','95000','6','06:00:00','22:00:00','Cụm sân trong nhà sạch đẹp, hệ thống thông gió mát mẻ, phòng tắm nóng lạnh.','inactive','2026-10-15 23:18:15','approved','1'),
('16','3','1','Sân Cầu Lông Trương Định','26 Lê Quý Đôn, Phường Võ Thị Sáu, Quận 3, TP.HCM','10.7819577','106.6902981','90000','6','06:00:00','22:00:00','Sân chơi giao lưu sôi động, có lớp dạy cầu lông cơ bản và nâng cao.','inactive','2026-10-15 23:18:16','approved','1'),
('17','3','2','CLB Cầu Lông Tinh Võ','Số 8 Nguyễn Trãi, Phường 11, Quận 5, TP.HCM','10.7590400','106.6818537','85000','6','06:00:00','22:00:00','Trung tâm Tinh Võ Chợ Lớn lịch sử, sàn thi đấu đạt chuẩn, không khí rèn luyện chuyên nghiệp.','inactive','2026-10-15 23:18:20','approved','1'),
('18','3','2','Sân Cầu Lông ĐH Sư Phạm TP.HCM','280 An Dương Vương, Phường 4, Quận 5, TP.HCM','10.7614399','106.6821537','75000','6','06:00:00','22:00:00','Nhà thi đấu trường ĐH Sư Phạm, trần cao, sàn gỗ lót thảm cao su chống sốc tốt.','inactive','2026-10-15 23:18:22','approved','1'),
('19','3','2','Sân Cầu Lông Hùng Vương Chợ Lớn','Hùng Vương, Phường 12, Quận 5, TP.HCM','10.7557757','106.6629025','80000','6','06:00:00','22:00:00','Khu vực trung tâm thương mại Chợ Lớn, thuận tiện đi lại, mở cửa 06:00 - 22:00.','inactive','2026-10-15 23:18:24','approved','1'),
('20','3','2','Sân Cầu Lông TT Thể Thao Quận 5','78 An Dương Vương, Phường 7, Quận 5, TP.HCM','10.7578899','106.6708473','80000','6','06:00:00','22:00:00','Cơ sở vật chất hiện đại, có khán đài mini xem thi đấu giao hữu phong trào.','inactive','2026-10-15 23:18:25','approved','1'),
('21','3','2','Sân Cầu Lông Lê Hồng Phong Q5','Lê Hồng Phong, Phường 2, Quận 5, TP.HCM','10.7577011','106.6844527','85000','6','06:00:00','22:00:00','Sân thảm chất lượng ổn định, có căn-tin nước giải khát và quầy bán quả cầu lông.','inactive','2026-10-15 23:18:27','approved','1'),
('22','3','5','Sân Cầu Lông Thảo Trang','24 Đ. Lý Chiêu Hoàng, Phường Bình Phú, Thành phố Hồ Chí Minh','10.7377355','106.6318526','70000','6','06:00:00','22:00:00','[catalog:560c61cacbd1786c071b]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-6\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-15 23:18:28','approved','1'),
('23','3','3','Sân Cầu Lông Phong Sơn Bình Phú','102 Đ. Trần Văn Kiểu, Phường 5, Quận 6, TP.HCM','10.7357749','106.6270888','75000','6','06:00:00','22:00:00','Sân cầu lông gần chợ Bình Tây, hệ thống đèn chiếu sáng đều không lóa mắt.','inactive','2026-10-15 23:18:30','approved','1'),
('24','3','5','Sân Cầu Lông Nhựt Minh','3-5 Đ. Lý Chiêu Hoàng, Khu II, Phường Bình Phú, Thành phố Hồ Chí Minh','10.7358356','106.6335628','70000','6','06:00:00','22:00:00','[catalog:3d660769c37fcc2121d3]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-6\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-15 23:18:32','approved','1'),
('25','3','6','CLB Cầu Lông Hoàng Vy','218/6 An Dương Vương, Phường 13, Quận 6, TP.HCM','10.7309514','106.6213914','70000','6','06:00:00','22:00:00','Sân cầu lông gần vòng xoay Phú Lâm, phục vụ hội viên rèn luyện sức khỏe mỗi ngày.','inactive','2026-10-15 23:18:35','approved','1'),
('26','3','3','CLB Cầu Lông Xóm Củi','63 Đ. Tùng Thiện Vương, Phường 9, Quận 6, TP.HCM','10.7484872','106.6662164','75000','6','06:00:00','22:00:00','Cụm sân cầu lông nhộn nhịp, tổ chức nhiều giải phong trào các lứa tuổi.','inactive','2026-10-15 23:18:36','approved','1'),
('27','3','12','Sân Cầu Lông Long Viên Q7','Huỳnh Tấn Phát, Phường Tân Phú, Quận 7, TP.HCM','10.7121896','106.7363046','85000','6','06:00:00','22:00:00','Quy mô 8 sân thảm xanh đẹp mắt, dịch vụ cho thuê đồ tập và phụ kiện.','inactive','2026-10-15 23:18:40','approved','1'),
('28','3','12','Sân Cầu Lông Nhà Thi Đấu Đa Năng Q7','504 Huỳnh Tấn Phát, Phường Tân Phong, Quận 7, TP.HCM','10.7415332','106.7295191','90000','6','06:00:00','22:00:00','Sân tập hiện đại gần trường ĐH Tôn Đức Thắng, không khí trẻ trung sôi nổi.','inactive','2026-10-15 23:18:41','approved','1'),
('29','3','12','Sân Cầu Lông Nam Sài Gòn Q7','Nguyễn Hữu Thọ, Phường Tân Hưng, Quận 7, TP.HCM','10.7310236','106.6998250','95000','6','06:00:00','22:00:00','Mặt sàn gỗ phủ thảm Victor tiêu chuẩn, có điều hòa thông gió công suất lớn.','inactive','2026-10-15 23:18:43','approved','1'),
('30','3','24','Sân Cầu Lông 79 Pro','300/13 Nguyễn Văn Linh, Phường Tân Thuận, Thành phố Hồ Chí Minh','10.7420631','106.7206122','85000','6','06:00:00','22:00:00','[catalog:249a8f89d83fecab2e58]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-7\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-15 23:18:45','approved','1'),
('31','3','2','Sân Cầu Lông Phong Sơn Cao Đẳng Kinh Tế','253 Trần Nhân Tôn, Phường 12, Quận 10, TP.HCM','10.7648250','106.6741632','85000','6','06:00:00','22:00:00','Sân cầu lông Kỳ Hòa nổi tiếng Sài Gòn, 10 sân thảm, dịch vụ căng vợt chuyên nghiệp.','inactive','2026-10-15 23:18:47','approved','1'),
('32','3','2','Sân Cầu Lông 291 Cách Mạng Tháng 8','291 Hẻm 285 Cách Mạng Tháng Tám, Phường 12, Quận 10, TP.HCM','10.7758833','106.6734950','95000','6','06:00:00','22:00:00','Khu thể thao Lan Anh cao cấp, tiện ích 5 sao, quầy cafe và bãi đỗ xe ô tô.','inactive','2026-10-15 23:18:49','approved','1'),
('33','3','2','CLB Cầu Lông Kỳ Hòa Q10','238 Đường 3/2, Phường 12, Quận 10, TP.HCM','10.7712450','106.6738520','80000','6','06:00:00','22:00:00','Sân chơi thoáng mát, giá vé giờ vàng ưu đãi cho hội viên thuê cố định tháng.','inactive','2026-10-15 23:18:50','approved','1'),
('34','3','2','Sân Cầu Lông Bắc Hải Sport','LL1 Bis, Đ. Hương Giang, Cư xá Bắc Hải, Phường 15, Quận 10, TP.HCM','10.7806129','106.6638385','80000','6','06:00:00','22:00:00','Nằm trong khu cư xá Bắc Hải yên tĩnh, sàn thảm êm ái, ánh sáng tiêu chuẩn.','inactive','2026-10-15 23:18:51','approved','1'),
('35','3','2','Sân Cầu Lông Nguyễn Tri Phương','Thành Thái, Phường 14, Quận 10, TP.HCM','10.7692365','106.6648526','80000','6','06:00:00','22:00:00','Địa điểm tập luyện quen thuộc của các CLB sinh viên và người đi làm.','inactive','2026-10-15 23:18:54','approved','1'),
('36','3','7','Sân Cầu Lông Phú Thọ','219 Lý Thường Kiệt, Phường 15, Quận 11, TP.HCM','10.7675886','106.6584777','90000','6','06:00:00','22:00:00','Tổ hợp nhà thi đấu Phú Thọ quy mô lớn nhất TP.HCM, thường đăng cai giải quốc gia.','inactive','2026-10-15 23:18:56','approved','1'),
('37','3','7','CLB Cầu Lông Lãnh Binh Thăng','283 Lãnh Binh Thăng, Phường 8, Quận 11, TP.HCM','10.7632054','106.6498650','75000','6','06:00:00','22:00:00','Sân sạch sẽ, phục vụ chu đáo, có quầy nước và tủ đồ cá nhân an toàn.','inactive','2026-10-15 23:18:57','approved','1'),
('38','3','7','Sân Cầu Lông Bình Thới Q11','Bình Thới, Phường 10, Quận 11, TP.HCM','10.7595092','106.6425366','70000','6','06:00:00','22:00:00','Sân thảm mới, độ bám tốt, thích hợp thi đấu đôi và đơn cường độ cao.','inactive','2026-10-15 23:18:59','approved','1'),
('39','3','7','Sân Cầu Lông Toàn Võ','185 Đường Lê Thị Bạch Cát, Phường 3, Quận 11, TP.HCM','10.7650614','106.6492458','75000','6','06:00:00','22:00:00','Gần công viên Đầm Sen, vị trí thuận tiện giao lưu cho các quận lân cận.','inactive','2026-10-15 23:19:03','approved','1'),
('40','3','7','Sân Cầu Lông Nhà Thi Đấu Phú Thọ','Số 1 Lữ Gia, Phường 9, Quận 11, TP.HCM','10.7690285','106.6577739','70000','6','06:00:00','22:00:00','Sân chơi thân thiện cho gia đình, có bảo hiểm sân bãi và sơ cứu y tế.','inactive','2026-10-15 23:19:04','approved','1'),
('41','3','9','Sân Cầu Lông Chu Văn An','Chu Văn An, Phường 12, Bình Thạnh, TP.HCM','10.8116638','106.6982832','75000','6','06:00:00','22:00:00','Sân cầu lông khu dân cư Chu Văn An, không gian yên tĩnh, chỗ để xe rộng.','inactive','2026-10-15 23:19:08','approved','1'),
('42','3','34','Sân Cầu Lông Như Nhiên','353/3 Bình Quới, Phường Bình Quới, Thành phố Hồ Chí Minh','10.8263405','106.7355239','70000','6','06:00:00','22:00:00','[catalog:4cbb16b8cfd196b26e80]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-thanh\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-15 23:19:09','approved','1'),
('43','3','9','Sân Cầu Lông Phan Đăng Lưu Sport','Phan Đăng Lưu, Phường 1, Bình Thạnh, TP.HCM','10.8027227','106.6985310','80000','6','06:00:00','22:00:00','Trung tâm Bình Thạnh giáp Phú Nhuận, giao thông thuận lợi, dịch vụ tận tâm.','inactive','2026-10-15 23:19:11','approved','1'),
('44','3','9','CLB Cầu Lông Nơ Trang Long','Nơ Trang Long, Phường 13, Bình Thạnh, TP.HCM','10.8142207','106.6977134','75000','6','06:00:00','22:00:00','Sân thảm chống mỏi chân, mở cửa sớm đón khách chơi cầu thể dục buổi sáng.','inactive','2026-10-15 23:19:13','approved','1'),
('45','3','10','CLB Cầu Lông Ga Trực Thăng','Đường Bạch Đằng, Phường 2, Tân Bình, TP.HCM','10.8123450','106.6662150','80000','6','06:00:00','22:00:00','Quy mô 12 sân rộng rãi cạnh sân bay Tân Sơn Nhất, trần cao thoáng mát.','inactive','2026-10-15 23:19:15','approved','1'),
('46','3','10','Sân Cầu Lông 98','98 Đ. Trần Thị Trọng, Phường 12, Tân Bình, TP.HCM','10.8255865','106.6359921','75000','6','06:00:00','22:00:00','Cơ sở hiện đại, mặt sân đạt chuẩn quốc tế, có quầy nước và phụ kiện cầu lông.','inactive','2026-10-15 23:19:18','approved','1'),
('47','3','10','Sân Cầu Lông Bùi Thị Xuân','191 Bùi Thị Xuân, Phường 14, Tân Bình, TP.HCM','10.7949933','106.6643541','70000','6','06:00:00','22:00:00','Khu dân cư Bàu Cát đông đúc, phong trào cầu lông sôi nổi bậc nhất Tân Bình.','inactive','2026-10-15 23:19:21','approved','1'),
('48','3','10','Sân Cầu Lông Cộng Hòa Arena','Cộng Hòa, Phường 13, Tân Bình, TP.HCM','10.8024385','106.6419696','80000','6','06:00:00','22:00:00','Sân chơi quy mô, ánh sáng trắng chống lóa, huấn luyện viên nhiệt tình chỉ dẫn.','inactive','2026-10-15 23:19:24','approved','1'),
('49','3','11','Sân Cầu Lông Bến Cát Gò Vấp','145 Dương Quảng Hàm, Phường 5, Gò Vấp, TP.HCM','10.8415000','106.6905000','75000','6','06:00:00','22:00:00','Sân thảm chất lượng tốt, giá thành hợp lý cho học sinh, sinh viên.','inactive','2026-10-15 23:19:26','approved','1'),
('50','3','11','Sân Cầu Lông Khang An Gò Vấp','Phan Văn Trị, Phường 10, Gò Vấp, TP.HCM','10.8354396','106.6647905','75000','6','06:00:00','22:00:00','Sân trong hẻm lớn yên tĩnh, thông gió tự nhiên mát mẻ, dịch vụ chuyên nghiệp.','inactive','2026-10-15 23:19:33','approved','1'),
('51','3','11','Sân Cầu Lông An Hội','256/110 Đ. Phan Huy Ích, Phường 16, Gò Vấp, TP.HCM','10.8380834','106.6402183','70000','6','06:00:00','22:00:00','Sân cầu lông uy tín lâu năm tại Gò Vấp, nhiều huấn luyện viên giỏi trực tiếp hướng dẫn.','inactive','2026-10-15 23:19:34','approved','1'),
('52','3','11','Sân Cầu Lông Quang Trung Sport','Quang Trung, Phường 8, Gò Vấp, TP.HCM','10.8265054','106.6799467','70000','6','06:00:00','22:00:00','Mặt sân chuẩn thi đấu, chỗ nghỉ ngơi rộng rãi, wifi tốc độ cao miễn phí.','inactive','2026-10-15 23:19:36','approved','1'),
('53','3','1','Sân Cầu Lông Rạch Miễu','1 Hoa Phượng, Phường 2, Phú Nhuận, TP.HCM','10.7961774','106.6872310','90000','6','06:00:00','22:00:00','Trung tâm thể dục thể thao Rạch Miễu danh tiếng, cơ sở vật chất đồng bộ hiện đại.','inactive','2026-10-15 23:19:37','approved','1'),
('54','3','1','Sân Cầu Lông PooC','202B Hoàng Văn Thụ, Phường 9, Phú Nhuận, TP.HCM','10.8009171','106.6699606','85000','6','06:00:00','22:00:00','Nhà thi đấu Quân Khu 7 rộng lớn, trần cao tiêu chuẩn quốc tế, không bị cản cầu.','inactive','2026-10-15 23:19:39','approved','1'),
('55','3','1','CLB Cầu Lông Đào Duy Anh','21 Đào Duy Anh, Phường 9, Phú Nhuận, TP.HCM','10.8051688','106.6758775','80000','6','06:00:00','22:00:00','Gần công viên Gia Định, không khí thoáng đãng trong lành, chỗ đỗ xe thuận tiện.','inactive','2026-10-15 23:19:41','approved','1'),
('56','3','1','Sân Cầu Lông Gia Định Sport','Hoàng Minh Giám, Phường 9, Phú Nhuận, TP.HCM','10.8084727','106.6728204','80000','6','06:00:00','22:00:00','Hệ thống sân thảm Yonex mới 100%, có phòng thay đồ và tắm nóng lạnh sạch sẽ.','inactive','2026-10-15 23:19:43','approved','1'),
('57','3','1','Sân Cầu Lông Phan Xích Long','Phan Xích Long, Phường 7, Phú Nhuận, TP.HCM','10.7991290','106.6873381','85000','6','06:00:00','22:00:00','Tuyến phố ẩm thực sầm uất, thuận tiện tụ họp thể thao và ăn uống cùng bạn bè.','inactive','2026-10-15 23:19:45','approved','1'),
('58','3','13','Sân Cầu Lông Lavie','40 Lương Định Của, Phường Thảo Điền, TP. Thủ Đức, TP.HCM','10.7858758','106.7312755','110000','6','06:00:00','22:00:00','Khu vực Thảo Điền cao cấp, dịch vụ chuẩn quốc tế, có huấn luyện viên nước ngoài.','inactive','2026-10-15 23:19:48','approved','1'),
('59','3','13','Sân Cầu Lông Trần Não','22 Đường số 24, Phường An Phú, TP. Thủ Đức, TP.HCM','10.7944184','106.7298283','95000','6','06:00:00','22:00:00','Sân cầu lông dọc tuyến Metro số 1, sàn thi đấu êm, quầy đồ uống phong phú.','inactive','2026-10-15 23:19:50','approved','1'),
('60','3','13','Sân Cầu Lông Linh Đông Thủ Đức','Đường Linh Đông, Phường Linh Đông, TP. Thủ Đức, TP.HCM','10.8533044','106.7602290','70000','6','06:00:00','22:00:00','Sân tập đông vui, giá cả vừa túi tiền sinh viên và công nhân viên chức.','inactive','2026-10-15 23:19:51','approved','1'),
('61','3','13','Sân Cầu Lông Bình An','11B Đường số 29, Phường Tam Phú, TP. Thủ Đức, TP.HCM','10.7922769','106.7293126','70000','6','06:00:00','22:00:00','Sân cầu lông đạt chuẩn, chỗ gửi xe rộng rãi, nhân viên hỗ trợ nhiệt tình.','inactive','2026-10-15 23:19:53','approved','1'),
('65','3','15','Sân cầu lông Tao Đàn','Số 01 đường Huyền Trân Công Chúa, Phường Bến Thành, Thành phố Hồ Chí Minh','10.7747674','106.6947053','105000','6','06:00:00','22:00:00','[catalog:aac5d0c08209624cbcf3]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-1\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('66','3','15','Sân cầu lông Cung Văn hóa Lao động TP.HCM','55B, Nguyễn Thị Minh Khai, Phường Bến Thành, Thành phố Hồ Chí Minh','10.7763541','106.6931260','105000','6','06:00:00','22:00:00','[catalog:da0df53f633c822745f6]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-1\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('67','3','16','Sân cầu lông Cầu Kho','Số 2, Đường Nguyễn Văn Cừ, Phường Cầu Ông Lãnh, Thành phố Hồ Chí Minh','10.7547701','106.6865755','105000','6','06:00:00','22:00:00','[catalog:07111cefa4586c7b0019]\nTra cứu: 2026-10-16\nLiên kết của cả Tuấn Việt và VNB trỏ đến số 2 Nguyễn Văn Cừ, không phải POI riêng của sân. Cần chủ sân xác nhận vị trí lối vào; không coi đây là tọa độ sân đã xác minh.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-1\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('68','3','17','Sân cầu lông Lavie','40 Lương Định Của, Phường An Khánh, TP.HCM','10.7858758','106.7312755','95000','6','06:00:00','22:00:00','[catalog:fb5a38391255940ac4ba]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://gosensports.com/san-cau-long-quan-2\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('69','3','17','Sân cầu lông Trần Não','22 Đường số 24, Phường An Khánh, TP.HCM','10.7944184','106.7298283','95000','6','06:00:00','22:00:00','[catalog:83d548b9eb65658565ae]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://gosensports.com/san-cau-long-quan-2\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('70','3','17','Sân cầu lông Bình An','11B Đường số 29, Phường An Khánh, TP.HCM','10.7922769','106.7293126','95000','6','06:00:00','22:00:00','[catalog:e662528d6fed3d119339]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://gosensports.com/san-cau-long-thu-duc\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('71','3','18','Sân Cầu Lông NCUT','269 Rạch Bùng Binh, Phường Nhiêu Lộc, Thành phố Hồ Chí Minh','10.7821652','106.6801638','90000','6','06:00:00','22:00:00','[catalog:5a9866d28b839ee5add6]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-3\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('72','3','18','Sân cầu lông Trung Tâm TDTT Ga Sài Gòn','01 Nguyễn Thông, Phường Nhiêu Lộc, Thành phố Hồ Chí Minh','10.7825189','106.6768555','90000','6','06:00:00','22:00:00','[catalog:1c8f74eaf576e49520bb]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-3\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('73','3','19','Sân cầu lông Marie Curie','26 Lê Quý Đôn, Phường Xuân Hòa, Thành phố Hồ Chí Minh','10.7819577','106.6902981','90000','6','06:00:00','22:00:00','[catalog:ec3573310f4a505135c0]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-3\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('74','3','20','Sân cầu lông Tân Cảng','122 Đ. Khánh Hội, Phường Khánh Hội, Thành phố Hồ Chí Minh','10.7563925','106.7011714','85000','6','06:00:00','22:00:00','[catalog:daed1a38d25cbe90971f]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-4\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('75','3','21','Học viện cầu lông TPro','1k Ngô Văn Sở, Xóm Chiếu, Hồ Chí Minh, Việt Nam','10.7622910','106.7104750','85000','6','06:00:00','22:00:00','[catalog:5e25d61b35926d2a284c]\nTra cứu: 2026-10-16\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-4\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.\nTên, địa chỉ và tọa độ được người dùng đính chính ngày 19/10/2026 theo ảnh Google Maps cung cấp; chưa kiểm tra thực địa. TPro và T12 là hai địa điểm riêng.','active','2026-10-18 23:04:11','approved','1'),
('76','3','21','Sân cầu lông T12','1B Hoàng Diệu, Phường Xóm Chiếu, TP.HCM','10.7638079','106.7091101','85000','6','06:00:00','22:00:00','[catalog:a8db4013bc913764ec19]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://shopvnb.com/san-cau-long-t12.html\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('77','3','22','Sân Cầu Lông Đại Học Sài Gòn','Số 8, Đ. Nguyễn Trãi, Phường Chợ Quán, Thành phố Hồ Chí Minh','10.7590400','106.6818537','80000','6','06:00:00','22:00:00','[catalog:d9d9547ca0990e3e52fb]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-5\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('78','3','22','Sân Cầu Lông Lam Sơn','Số 302/1, Đường Trần Bình Trọng, Phường Chợ Quán, Thành phố Hồ Chí Minh','10.7623271','106.6798241','80000','6','06:00:00','22:00:00','[catalog:e8ca72cc32bc47cd0738]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-5\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('79','3','23','Sân Cầu Lông Lý Phong','Số 78, Đường An Dương Vương, Phường An Đông, Thành phố Hồ Chí Minh','10.7578899','106.6708473','80000','6','06:00:00','22:00:00','[catalog:75097cf1d038a375e707]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-5\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('80','3','5','Sân Cầu Lông Thể Thao Phong Sơn Bình Phú','102 Đ. Trần Văn Kiểu, Phường Bình Phú, Thành phố Hồ Chí Minh','10.7357749','106.6270888','75000','6','06:00:00','22:00:00','[catalog:64fd5b9c69f39bd06850]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-6\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('81','3','24','Sân Cầu Lông Trung Tâm Thể Thao Victory','Nhà thi đấu Nam Sài Gòn, 157 Nguyễn Văn Quỳ, Phường Tân Thuận, Thành phố Hồ Chí Minh','10.7421688','106.7381973','90000','6','06:00:00','22:00:00','[catalog:5b562187b560a769773e]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-7\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('82','3','24','Sân Cầu Lông Nhà thi đấu đa năng Quận 7','504 Huỳnh Tấn Phát, Phường Tân Thuận, Thành phố Hồ Chí Minh','10.7415332','106.7295191','90000','6','06:00:00','22:00:00','[catalog:a50ac4a9101151d9742c]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-7\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('83','3','25','Sân cầu lông Đại học Cảnh sát','Cổng 4 Trường Đại học Cảnh sát nhân dân, đường Hoàng Trọng Mậu, Phường Tân Hưng, TP.HCM','10.7344990','106.6952815','90000','6','06:00:00','22:00:00','[catalog:dai-hoc-canh-sat-q7]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm lấy từ liên kết Google Maps do người dùng cung cấp ngày 16/10/2026. Địa chỉ lối vào theo LOOL; chưa xác minh thực địa.\nNguồn: https://lool.vn/san/san-cau-long-dai-hoc-canh-sat-quan-7-df67\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('84','3','26','Sân cầu lông Hoàng Vy','218/6 An Dương Vương, Phường Bình Phú, Thành phố Hồ Chí Minh','10.7309514','106.6213914','70000','6','06:00:00','22:00:00','[catalog:69ffac0f1ced7443ab87]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-8\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('85','3','27','CLB cầu lông Xóm Củi','63 Đ. Tùng Thiện Vương, Phường Phú Định, Thành phố Hồ Chí Minh','10.7484872','106.6662164','70000','6','06:00:00','22:00:00','[catalog:ada87b6023fa727983a3]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-8\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('86','3','27','Sân cầu lông Aquaminton','1458 Đ. Hoài Thanh, Phường Phú Định, Thành phố Hồ Chí Minh','10.7359953','106.6448645','70000','6','06:00:00','22:00:00','[catalog:78dbb73978d47bd098a9]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-8\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('87','3','28','Sân cầu lông Hải Sơn','167 Đường số 14, Phường Phước Long, TP.HCM','10.8110270','106.7681039','75000','6','06:00:00','22:00:00','[catalog:7ac440be001141483241]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://gosensports.com/san-cau-long-quan-9\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('88','3','29','Sân cầu lông Long Sen','39P Gò Cát, Phường Long Trường, TP.HCM','10.7860298','106.8013464','75000','6','06:00:00','22:00:00','[catalog:8767df6273ee2e389575]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://gosensports.com/san-cau-long-quan-9\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('89','3','28','Sân cầu lông Công Thương','103 Tăng Nhơn Phú, Phường Phước Long, TP.HCM','10.8295874','106.7732033','75000','6','06:00:00','22:00:00','[catalog:862234d0d924a41f8be3]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://shopvnb.com/san-cau-long-cong-thuong.html\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('90','3','30','Sân cầu lông Phong Sơn Cao Đẳng Kinh Tế','253 Trần Nhân Tôn, Phường Vườn Lài, Thành phố Hồ Chí Minh','10.7648250','106.6741632','85000','6','06:00:00','22:00:00','[catalog:ba55e2fa24ee5cb6a23c]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-10\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('91','3','31','Sân cầu lông 291','291 Hẻm 285 Cách Mạng Tháng Tám, Phường Hòa Hưng, Thành phố Hồ Chí Minh','10.7758833','106.6734950','85000','6','06:00:00','22:00:00','[catalog:1e4bf63a9f333e655024]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-10\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('92','3','31','Sân Cầu Lông Bắc Hải','LL1 Bis, Đ. Hương Giang, Cư xá Bắc Hải, Phường Hòa Hưng, Thành phố Hồ Chí Minh','10.7806129','106.6638385','85000','6','06:00:00','22:00:00','[catalog:5789da7ccad6afe05e64]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-10\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('93','3','32','Sân cầu lông Toàn Võ (Châu Dương cũ)','Số 185 Đường Lê Thị Bạch Cát, Phường Phú Thọ, Thành phố Hồ Chí Minh','10.7650614','106.6492458','75000','6','06:00:00','22:00:00','[catalog:1ed9a1c034655e38892a]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-11\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('94','3','32','Sân cầu lông Nhà thi đấu Phú Thọ','Lầu 1, Số 1 Lữ Gia, Phường Phú Thọ, Thành phố Hồ Chí Minh','10.7690285','106.6577739','75000','6','06:00:00','22:00:00','[catalog:9a7bd549f5d7723addf3]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-11\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('95','3','32','Sân cầu lông Phú Thọ','219 Lý Thường Kiệt, Phường Phú Thọ, Thành phố Hồ Chí Minh','10.7675886','106.6584777','75000','6','06:00:00','22:00:00','[catalog:aadb878f3f9172c80a79]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-11\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('96','3','33','Sân cầu lông Nhà thi đấu đa năng Quận 12','số 493, đường Đường Thị Mười, Phường Tân Thới Hiệp, Thành phố Hồ Chí Minh','10.8737375','106.6385800','70000','6','06:00:00','22:00:00','[catalog:e27b181fa3cf3faac19f]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-12\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('97','3','33','Sân cầu lông Võ Trường Toản','Số 482, Đường Nguyễn Thị Đặng, Phường Tân Thới Hiệp, Thành phố Hồ Chí Minh','10.8725841','106.6458344','70000','6','06:00:00','22:00:00','[catalog:ec1d2d37845c3b223849]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-12\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('98','3','33','Sân cầu lông Hiệp Thành','163/50, Đường Bùi Văn Ngữ, khu phố 7, Phường Tân Thới Hiệp, Thành phố Hồ Chí Minh','10.8864202','106.6295177','70000','6','06:00:00','22:00:00','[catalog:fd4f62bf1db4a04263d9]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-12\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('99','3','34','Sân Cầu Lông Kim Minh','206/8A Bình Quới, Phường Bình Quới, Thành phố Hồ Chí Minh','10.8188471','106.7260652','80000','6','06:00:00','22:00:00','[catalog:18dc63a676595c92ce05]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-thanh\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('100','3','36','Sân cầu lông 18B Cộng Hoà','18B Cộng Hoà, Phường Tân Sơn Nhất, Thành phố Hồ Chí Minh','10.8051712','106.6556922','80000','6','06:00:00','22:00:00','[catalog:fde8112c6ff08ecdeff5]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-tan-binh\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('101','3','37','Sân cầu lông Bùi Thị Xuân','191 Bùi Thị Xuân, Phường Tân Sơn Hòa, Thành phố Hồ Chí Minh','10.7949933','106.6643541','80000','6','06:00:00','22:00:00','[catalog:6694233bef24d12b5fe4]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-tan-binh\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('102','3','38','Sân cầu lông 98','98/ Đ. Trần Thị Trọng, Phường Tân Bình, Thành phố Hồ Chí Minh','10.8255865','106.6359921','80000','6','06:00:00','22:00:00','[catalog:42eb6896182b2fb48145]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-tan-binh\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('103','3','39','Sân cầu lông Đại Phát','313/17 Đ. Phan Huy Ích, Phường An Hội Tây, Thành phố Hồ Chí Minh','10.8387338','106.6341503','75000','6','06:00:00','22:00:00','[catalog:71f65ac046bcb6f4afa1]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-go-vap\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1');

INSERT INTO `courts` (`id`,`owner_id`,`ward_id`,`name`,`address`,`latitude`,`longitude`,`price_per_hour`,`total_sub_courts`,`opening_time`,`closing_time`,`description`,`status`,`created_at`,`approval_status`,`booking_open`) VALUES
('104','3','39','Sân cầu lông Đức Lợi','611 Đ. Lê Văn Thọ, Phường An Hội Tây, Thành phố Hồ Chí Minh','10.8548593','106.6551045','75000','6','06:00:00','22:00:00','[catalog:7012269cc77ab67e68ee]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-go-vap\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('105','3','39','Sân cầu lông An Hội','256/110 Đ. Phan Huy Ích, Phường An Hội Tây, Thành phố Hồ Chí Minh','10.8380834','106.6402183','75000','6','06:00:00','22:00:00','[catalog:b81c1c079ec158e9c4a9]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-go-vap\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('106','3','40','Sân cầu lông PooC','202B Hoàng Văn Thụ, Phường Đức Nhuận, Thành phố Hồ Chí Minh','10.8009171','106.6699606','85000','6','06:00:00','22:00:00','[catalog:7303148a94e7f606ac14]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-phu-nhuan\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('107','3','41','Sân cầu lông Sky Phú Nhuận','84 Mai Văn Ngọc, Phường Phú Nhuận, Thành phố Hồ Chí Minh','10.7926856','106.6725338','85000','6','06:00:00','22:00:00','[catalog:9a69939960bf2b672a1b]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-phu-nhuan\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('108','3','40','Sân cầu lông Đào Duy Anh','21 Đào Duy Anh, Phường Đức Nhuận, TP.HCM','10.8051688','106.6758775','85000','6','06:00:00','22:00:00','[catalog:04ad561c4829d1e260ff]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-phu-nhuan\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('109','3','42','Sân cầu lông Aurora Sport','158 Đường số 17, Phường Linh Xuân, TP.HCM','10.8662804','106.7875994','100000','6','06:00:00','22:00:00','[catalog:9f3ff658b45348f3333b]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://shopvnb.com/san-cau-long-aurora-sport.html\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('110','3','43','Sân cầu lông Tường Anh','56/1 Đường số 2, Phường Thủ Đức, TP.HCM','10.8349018','106.7546823','100000','6','06:00:00','22:00:00','[catalog:ba58c265552e9ba6a444]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://gosensports.com/san-cau-long-thu-duc\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('111','3','44','Sân cầu lông Quốc Lộ 1','400 Quốc lộ 1A, Phường Tam Bình, TP.HCM','10.8721689','106.7417341','100000','6','06:00:00','22:00:00','[catalog:b1582618732f74ead88e]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://shopvnb.com/san-cau-long-quoc-lo-1.html\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('112','3','45','Sân cầu lông Aka Badminton Center','730/4 Hương Lộ 2, Phường Bình Trị Đông, Thành phố Hồ Chí Minh','10.7672571','106.6051067','70000','6','06:00:00','22:00:00','[catalog:e844241dc18eaa643cbe]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-tan\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('113','3','45','CLB cầu lông Sinh Đôi','327 Đ. Bình Trị Đông, Phường Bình Trị Đông, Thành phố Hồ Chí Minh','10.7700147','106.6093781','70000','6','06:00:00','22:00:00','[catalog:bf73ca193cb759550ace]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-tan\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('114','3','45','Sân cầu lông Hưng Phú','15 Liên Khu 2-5, Phường Bình Trị Đông Thành phố Hồ Chí Minh','10.7675902','106.6168394','70000','6','06:00:00','22:00:00','[catalog:45dfcb509515520c9206]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-tan\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','inactive','2026-10-18 23:04:11','approved','1'),
('115','3','46','Câu lạc bộ Cầu Lông TTC','17/5/15, Lương Minh Nguyệt, P.Tân Phú, TP.Hồ Chí Minh','10.7602202','106.6310087','75000','6','06:00:00','22:00:00','[catalog:e6676ee250f09453f0cf]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-tan-phu\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('116','3','47','Sân cầu lông Ecosport','15 Cách Mạng, P.Tân Sơn Nhì, TP.Hồ Chí Minh','10.7890963','106.6364317','75000','6','06:00:00','22:00:00','[catalog:6fbda9fb669891fa4236]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-tan-phu\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1'),
('117','3','46','Sân cầu lông Tấn Phúc','452 Lũy Bán Bích, P.Tân Phú, TP.Hồ Chí Minh','10.7787125','106.6362366','75000','6','06:00:00','22:00:00','[catalog:79b17c02f0e74343031a]\nTra cứu: 2026-10-16\nTọa độ ghim địa điểm từ liên kết bản đồ của nguồn; chưa đo đạc thực địa hoặc xác nhận hoạt động qua điện thoại.\nNguồn: https://tuanviet.vn/danh-sach-san-cau-long-quan-tan-phu\nGiá sân mới 100.000 đ/giờ là giá DEMO cho đồ án, không phải báo giá thực tế. Liên hệ sân xác nhận hoạt động và giá.','active','2026-10-18 23:04:11','approved','1');

INSERT INTO `court_images` (`id`,`court_id`,`image_url`,`sort_order`) VALUES
('1','1','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('2','2','https://lh3.googleusercontent.com/grass-cs/AABkmLesmKvtv_9VtgUuwRjHSXbllKtGgR1woaz-DLMabNhqC5wq8lqRgvlOFh-UuJZnwzEfJ2QZRl2UHkvXs4vgREyzDKJxwvmzkYKYacfREcNwTy1Oct6yPbnr_4JkOarHUAcT_IsW=w408-h306-k-no','0'),
('3','3','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('4','4','https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop','0'),
('5','5','https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&auto=format&fit=crop','0'),
('6','6','https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop','0'),
('7','7','https://lh3.googleusercontent.com/grass-cs/AABkmLd_UP5KTy4EMpx1VJtV-2RdZjUvhut-atAiIFO-sYY-4xCj4m9VJdDN0_r3G-LGbcdHpuOXxkIV_-iiXgdGZRqtAGDrCjC5-b6LyBcB_jG2KQCD7SnIUqY6Me82QgFEy0PH07Kp=w408-h306-k-no','0'),
('8','8','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('9','9','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('10','10','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('11','11','https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop','0'),
('12','12','https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&auto=format&fit=crop','0'),
('13','13','https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop','0'),
('14','14','https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop','0'),
('15','15','https://images.unsplash.com/photo-1599474924187-334a4ae5bd3c?w=800&auto=format&fit=crop','0'),
('16','16','https://images.unsplash.com/photo-1511067007798-44670806348f?w=800&auto=format&fit=crop','0'),
('17','17','https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?w=800&auto=format&fit=crop','0'),
('18','18','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('19','19','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('20','20','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('21','21','https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop','0'),
('22','22','https://lh3.googleusercontent.com/grass-cs/AABkmLdXotWJAXyzFqXPGe_xqrzkXL4YxmbxZoav7Lb4jQD-pbMoStYvy1Kf0bw0OOwQf8c00XiO5_G_FRschJmLF606bU91s55NWOAjqK1KZCLDEp81mCALk_0g71c5nGlrOjYVQ-p5dA=w408-h306-k-no','0'),
('23','23','https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop','0'),
('24','24','https://lh3.googleusercontent.com/grass-cs/AABkmLeIosFLDSyqSMb2Jbr3Ej9ejGw0-sLRwT2B_-8dobsS8exCQTdpIgmw_7_EHJe29bhw4LZzdazfoL48z46epQCi8z_Wz1Yg5I2KLDqqHOE9hx9FriXMwyj4y6qMFDPs35-Qrtj6=w408-h306-k-no','0'),
('25','25','https://images.unsplash.com/photo-1599474924187-334a4ae5bd3c?w=800&auto=format&fit=crop','0'),
('26','26','https://images.unsplash.com/photo-1511067007798-44670806348f?w=800&auto=format&fit=crop','0'),
('27','27','https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?w=800&auto=format&fit=crop','0'),
('28','28','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('29','29','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('30','30','https://lh3.googleusercontent.com/grass-cs/AABkmLf6EBEPYLp9e4oB9vcYSgNHWAu5BWFdctYV8p6H887pZew1vGTVSpcCdiNhqZwXj-wvRBaJI5M5e-KGCHOi6PTba0bjqkbab2Wnp4KKOYA4da64DbYFycGQ3WBG7aUbULuCMk3uCA=w408-h306-k-no','0'),
('31','31','https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop','0'),
('32','32','https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&auto=format&fit=crop','0'),
('33','33','https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop','0'),
('34','34','https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop','0'),
('35','35','https://images.unsplash.com/photo-1599474924187-334a4ae5bd3c?w=800&auto=format&fit=crop','0'),
('36','36','https://images.unsplash.com/photo-1511067007798-44670806348f?w=800&auto=format&fit=crop','0'),
('37','37','https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?w=800&auto=format&fit=crop','0'),
('38','38','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('39','39','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('40','40','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('41','41','https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop','0'),
('42','42','https://lh3.googleusercontent.com/grass-cs/AABkmLdzU672IZhFRpHzEzL6F3kVfdTHEVwZS0xearIpWHgl37uZcdIqv1eEtRqcaEwKr8PFom4MjRLdZxPVaRdcNI8p2YWPWh6D5l5suEvx6ZwBWFMJAwy_aQEZ0QNp4mvWw7AOadym=w408-h306-k-no','0'),
('43','43','https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop','0'),
('44','44','https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop','0'),
('45','45','https://images.unsplash.com/photo-1599474924187-334a4ae5bd3c?w=800&auto=format&fit=crop','0'),
('46','46','https://images.unsplash.com/photo-1511067007798-44670806348f?w=800&auto=format&fit=crop','0'),
('47','47','https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?w=800&auto=format&fit=crop','0'),
('48','48','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('49','49','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('50','50','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('51','51','https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop','0'),
('52','52','https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&auto=format&fit=crop','0'),
('53','53','https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop','0'),
('54','54','https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop','0'),
('55','55','https://images.unsplash.com/photo-1599474924187-334a4ae5bd3c?w=800&auto=format&fit=crop','0'),
('56','56','https://images.unsplash.com/photo-1511067007798-44670806348f?w=800&auto=format&fit=crop','0'),
('57','57','https://images.unsplash.com/photo-1612872087720-bb876e2e67d1?w=800&auto=format&fit=crop','0'),
('58','58','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('59','59','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('60','60','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('61','61','https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop','0'),
('65','65','https://lh3.googleusercontent.com/grass-cs/AABkmLeFf-eScjlTcaoV3zXBtYhXuhSgMlpMwOI9ajGQWONgemxPAZTw-En5RzdFtuRCjazH-yzGAzxr3iAoQxnYlcbAUpghDMd0F8ZQfsyBc4BBkCv3ZfGi_DRFMgUpKRJSylTU8rq-=w408-h408-k-no','0'),
('66','66','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('67','67','https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop','0'),
('68','68','https://lh3.googleusercontent.com/grass-cs/AABkmLf8NiT93_tz408vpZ9UMu3J3SavLHCsCQjpeki_HfBhghMmdrSBqrdE5tbyVnA4gbz-aNXqqWwq1W3mJCK-zifN0LynIp-keoGTtSTYO17KeOFnFBZf7yL4PV7OJhhI5VxlUmwB=w426-h240-k-no','0'),
('69','69','https://lh3.googleusercontent.com/grass-cs/AABkmLd5RozmTHdE0FPn1ggt8zx5-KSY--uWWYk5XdW8OE7ZauV4CuMiIiM7ti1BEFKr8h_5-ZIdZnKQRdHTj4dCEJSV5SECKCiB5xwKEbMnOv2r5ry1dDcOBuPq1XEoUCR4h8ZEPSND=w408-h306-k-no','0'),
('70','70','https://lh3.googleusercontent.com/grass-cs/AABkmLdKYU0bJH4YG-UTyxBU3Lqvsp15eMhwP0X7EN967EKaN5GLokGW1iAp3v0ietKc8Ywnbh8coUs6X7OFQfBtTR3l2cvbGVGalpLYexZiQLBbK4oQRPo4nI3gyfrH3V76hQGREujz7oq_l1Q=w408-h306-k-no','0'),
('71','71','https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=800&auto=format&fit=crop','0'),
('72','72','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('73','73','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('74','74','https://streetviewpixels-pa.googleapis.com/v1/thumbnail?panoid=Pht_2l_IY0-YXeJPMg3n0g&cb_client=search.gws-prod.grass&w=408&h=240&yaw=45.720703&pitch=0&thumbfov=100','0'),
('75','75','https://lh3.googleusercontent.com/grass-cs/AABkmLfmiqIwPUbqq_5VeBqmtMW9DfnxizO-o79JNbXjd2GMKu7pVvUXLuzZ2xV2_FHOCxZM5Corw-4xtMQ_nSIicag-aCbkgmV93YmZylQvYVKgU-0ypujS6uhh0lc1aLOfXuNvD2wYEA=w408-h544-k-no','0'),
('76','76','https://lh3.googleusercontent.com/grass-cs/AABkmLfxFDerl0KLKI637HRCwrikOvffGM4U3-RxDHrebrJZO4Qd7KetMv7eyaXAXpzNfPDbJ-0HWVkbnszNQ3KJwMvQQiSzhhvkAhz0ESfP5eJ78aQE2s--zDHit-f848BKy4E5dL3L7w=w408-h306-k-no','0'),
('77','77','https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop','0'),
('78','78','https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop','0'),
('79','79','https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=800&auto=format&fit=crop','0'),
('80','80','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('81','81','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('82','82','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('83','83','https://lh3.googleusercontent.com/grass-cs/AABkmLet13HizPleqOWHlHbycsKzgKR3oowG2Fw6eSIinB39YIc-GljdD82a3Oilc-D1tv8AXF6JAVd51DjuOfuknr328OMBHnuvylx4Ps0IgHQieOl6JVkWk-n21Afusui8vQy8irKI2lVbA_Mk=w408-h306-k-no','0'),
('84','84','https://lh3.googleusercontent.com/grass-cs/AABkmLeNuDiVw5-dg-VSpORZ3GoTPukudsifJe48Ou787iPEpLB8Z9YkjfbhBaVMHWJZ7CH-2I5bVavKKahvQMIy598zHKtSyTGSdu7Sei0fwa6QMTjyBVELBpsgnv_nIC1DqtAY9DY=w408-h306-k-no','0'),
('85','85','https://lh3.googleusercontent.com/grass-cs/AABkmLfua6syLVXlth0Z92DqsEHLuJBbE-09aDHExgNKrwfPfe9Unfk7YWob-2QMz8y-MYpzFU-vl766UggVd5TojhZUr6mxY61UkEo0W-FCVmiY-1p9dBrG6EuBcZPWbxE8KiaaD2AV=w408-h544-k-no','0'),
('86','86','https://lh3.googleusercontent.com/grass-cs/AABkmLd5Eq3oncyQoicDX4R7KKj4TRmPO5fbjQF3GdGr5wP4eS-xItrvHZk5gC8y-__QtJn12QrsMcpliwxa1V7Q1TmkmpxyRFMSiyf6Yx-Ss5E-daldTjFpwSHxW9VgvwtcsshBllAwZwuP-hHX=w408-h725-k-no','0'),
('87','87','https://lh3.googleusercontent.com/grass-cs/AABkmLcl5K-HOQ_RoJa51hqtBNo21I0pcVL7COUmDEHFvBkTh7_HV1LViUgPZphFl6BZtuvUMRoUZzVTVCS3tQWlSOQ4CDxowEnbNsLmkwPX5IcSsZoG1hpw-6c_j_ySWpm4CGjKJigc=w408-h306-k-no','0'),
('88','88','https://lh3.googleusercontent.com/grass-cs/AABkmLc7hZEYdcXyYlRvQM4Orix90vM_IY8rO_lU-G68pJrMaAg9BnxX1fjoBnitRyP_cfHHflG1-4Dcale88dOgQX8x--DfkAAe5-C9U2O4eBLZJXz2IzbpeWSD0cn6NQNbr3G7YjJ1=w408-h544-k-no','0'),
('89','89','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('90','90','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('91','91','https://lh3.googleusercontent.com/grass-cs/AABkmLfXiF9rrxNxJ_g452HTwzzqdTZ9AQreN3-xkuDoin-pUGadEzpEzsaZWUHWtUmwE2ddqCAzrDQTolIiXnZCviei_LVofM4KwEPULMT4htqjzRmvWUa-ZgZ68F3bTvaKn8w9kn4SiAzlK0bS=w628-h240-k-no','0'),
('92','92','https://lh3.googleusercontent.com/grass-cs/AABkmLfXiF9rrxNxJ_g452HTwzzqdTZ9AQreN3-xkuDoin-pUGadEzpEzsaZWUHWtUmwE2ddqCAzrDQTolIiXnZCviei_LVofM4KwEPULMT4htqjzRmvWUa-ZgZ68F3bTvaKn8w9kn4SiAzlK0bS=w628-h240-k-no','0'),
('93','93','https://lh3.googleusercontent.com/grass-cs/AABkmLf2OAG0bWmM88tit7WGn1pmb7T3bnP6o7Ljs7oKIV-qSaZs-mRcVVdKfXxkU9zT5Bonwfa4s_KxegpwCUYFAqL1kjt031mIo9XvY6kj7wr5GW4PqBombxLkg6V5aO7W4fKHgIeZ7stOif3Z=w408-h544-k-no','0'),
('94','94','https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop','0'),
('95','95','https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=800&auto=format&fit=crop','0'),
('96','96','https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop','0'),
('97','97','https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop','0'),
('98','98','https://lh3.googleusercontent.com/grass-cs/AABkmLeAEOcdAN-eNLgnkowxMzoJ6QPyBDbz2gz7Ctg7SrzGmyFzlT3vsFedqZYDh6ZL1rCvaCU4cnpp2krnZbLSfHDl3fKm5NYx8zlZeBPYK9bC-2j9DymXQd1KEVckSXdIQ3Ca3uI=w408-h306-k-no','0'),
('99','99','https://lh3.googleusercontent.com/grass-cs/AABkmLfgYy1KEdVlXimi29iK3cbZ1RJfhOhxTiqKPgLLYp-U7ECeSJWDJxmOw5rrOhg-5wAMJk7F5PDcbdIlTtCmmqCti4XcW0VUxp7_Rm-95CKU1AThSu6Lq1g8cxmxoP0CKGfdT8eh3w67VMZ4=w408-h544-k-no','0'),
('100','100','https://lh3.googleusercontent.com/grass-cs/AABkmLfdupSwVa4Djp1NJ_ThfhX0dM8KG0-1hzPvsVdgrJp3UizYbM1UwvuqpJl28_HHRyipuH2UzxHpJjK-OUXbwzsuXZkvds7PUOWr9Vve3yLTYW66jUah9ThjrjDmFpuUB9qYe7k=w408-h305-k-no','0'),
('101','101','https://lh3.googleusercontent.com/grass-cs/AABkmLdz81r3wBF1o14Oe8Jo_xzmJW919vn1wvQc0N0gXBlzNVyJFbK1K8LAigbbF1iIMY29wMblr4AeUEsy8geuFfoBh_ymtKl0ty3uJAGXpEWj4pCUJm-SjRBWj4GdrGgbRMZZw1373w=w408-h306-k-no','0'),
('102','102','https://lh3.googleusercontent.com/grass-cs/AABkmLdXoa24MHQl-884zkIZtvkJAEwmyYuoCL4RABKIy9XVlQfyMEHGA76e3T4jCdwikEhFQLy4EYwRfo0iac4ieJ47uZ7T5pBh93vy1yS7BUguUJZPOdpS7S3m8xgS6RUXqp-dNnXzOiWibFSk=w426-h240-k-no','0'),
('103','103','https://lh3.googleusercontent.com/grass-cs/AABkmLfC6x1Fp_n1d2olsTRpZXBkTaVA-1aVGB5qrEShGgc_67PDfYSISfqwBg6nDWVQbXs0xj7yllh7KBoEL48gC-fmL1-kMOzlWZc6eicVCXtmckdeftwyWoaIh8gAvy9f5A_12jUm=w408-h544-k-no','0');

INSERT INTO `court_images` (`id`,`court_id`,`image_url`,`sort_order`) VALUES
('104','104','https://lh3.googleusercontent.com/grass-cs/AABkmLd9veQQRVg1nZoq8IX_xV0ZWhmwhuR--wBzHI5NgitovhhJwZn8s1VsJXzrvpztYbMJSqFAeR9OmK4QNa0Lc-FW8an-kRjxBvQVkEJX9UxUFjGg-zO5D94dSN638INeZ3A8oLc8=w408-h544-k-no','0'),
('105','105','https://lh3.googleusercontent.com/grass-cs/AABkmLc9jzPoiYvVFROBqTdN2ikLUkUCal67YJ_Yza1LMKClRHrEYvFblfY5CEXwZbVs6Fex0c7U1ejHwEpPqTa-e1Skc9IK-APBtZcwcyvujKag2hdEABx4pMPFSbCmRordsaAxZqU4=w408-h306-k-no','0'),
('106','106','https://lh3.googleusercontent.com/grass-cs/AABkmLcaC9vLcOW0YcobZmIDM1bLgi3WW2GxZpAKhDMekDA6OB55XJ3mcz8iSxSycNiKU_hCcXh9qrbnh2K3xFXHb2BQvf9xgkPqGVfbZB1NX_-EZQNQGCKk1GP5bI5mjK85ae5crl4=w426-h240-k-no','0'),
('107','107','https://lh3.googleusercontent.com/grass-cs/AABkmLfzULxTaxRsB2sJ0de3c3JFP-r_5WzW69_DpMb_lw-j2rfDE6WJoWJSuFo6PLS9SmOF7VxbL6YZoAyM5QlllhNdSGD_iRS97cITpBAEhgBjcbDK2S0UTBCrKd7f997JxmOU_9AV=w408-h544-k-no','0'),
('108','108','https://lh3.googleusercontent.com/grass-cs/AABkmLdhjjB-YcD7ehBRGPAm2tZ6MneoMXA99PIZlXQYCRyB95An7hhNOmIY8FLWoGOb88gQz0BifSz2PfGlMxcOz4J7RGywjzhxcZhuV2cY13KhwpVbyIgGgzL8Zf6-fu9zLm-7s2wMz6ljoZqC=w408-h306-k-no','0'),
('109','109','https://lh3.googleusercontent.com/grass-cs/AABkmLeQeAXUqf0Pe1uuZsG5tTaTAhqcN9QZz8S7alKj-EqiiWTWFpbmbHwvEEcHqw5e7xCNMoyizfGq7uJwJyf_5vH_ekrkfwijMPHA4rO-abJBh64zxWedfHFOCXKxjBwOwoH5pkOG=w408-h408-k-no','0'),
('110','110','https://lh3.googleusercontent.com/grass-cs/AABkmLe7QnQ0ElgLcXoXCfeO5hA3PtIb_00sUBaa4PX8hAqxVEJFmnoC5eX6wZJnzaVpE6x-c8s3sL60TfWH_kaiKZ684yf4Mws-Iwh3_Tg9AkwDYm9LHPl4uC0BzmAu9YDZt17IQSkBzHoAsvM1=w426-h240-k-no','0'),
('111','111','https://lh3.googleusercontent.com/grass-cs/AABkmLfZuM49be3EMnUBnBgfEYhJ_5kRn6ObTEmIx7VbM67Fs7lOaQ1vFlPVb81JmQfLzCixbnvum0vaWTpW0GiHyQJWkaVJPA-BNT_N1HjNdv2Z_8RWbWg7Yv230JsQBzEqVPZys2Njq8gh2tEQ=w408-h306-k-no','0'),
('112','112','https://lh3.googleusercontent.com/grass-cs/AABkmLe01YdRWl0b754XOpnZXcV22ZcsMTIaw5DM3BppeQ3suCp9ithTY9-pDGcLwBYYKbv-yJhYQoRXXa9TnD1kHZcAYu5ff1pOka9aKvm-lMXQlN0xUn_5pOD3Muw16cLlxP4bx4F4rw=w408-h544-k-no','0'),
('113','113','https://lh3.googleusercontent.com/grass-cs/AABkmLdDNLLx2KCbgdzhZXcFDKUFbQITaPWOIPvTm-dcBPCQDNxoGThzkDW6eXBrzZLOVR96mfwdKsJgv2fVAMOR0qgNNV3MUAdUtWlkrmDzWYbLPxr2TXNCVT5Yf0a5Fiq1GiLBGOyQ=w408-h306-k-no','0'),
('114','114','https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop','0'),
('115','115','https://lh3.googleusercontent.com/grass-cs/AABkmLd58TymIOdhJpxxNw_ILszliiJvsLQnrzDaMF5UzeeYh5fCOG386D3nmo4OH87sCY8Uel03OKFsgF4QOvd2y3wj_8CAqs2yacOm31_Z1HXfe7FEremvZMjPjPHqQlMd-fEatWYLl2j0vuPQ=w408-h306-k-no','0'),
('116','116','https://lh3.googleusercontent.com/grass-cs/AABkmLfBRNY7nOKYJZjuHjQHthZid40BsPlQTvIgNAjjS_Y7UXY2EMEOtmYf1P4BNYA6w6IpIcTJsJpiBFd2j-AX_qocVORBj8ogT6U_MupjTh7qY35S9N_4jrF1TQZephY8mD76KgZ4EA=w408-h306-k-no','0'),
('117','117','https://lh3.googleusercontent.com/grass-cs/AABkmLd7yTG4xWZGZHmFZKncqY3-Ux_iCgBPNpT24RHXcDTrjyPLU9yguj1o32dwdvfYWgp60Buz6NdaDL3qe7mHwuzoqtnbCOh6PUY4kYbcZ5VU9drTjRuwHXMSG0dZzGbfed_3Stb1li9FB6et=w408-h408-k-no','0');

INSERT INTO `court_amenities` (`court_id`,`amenity_id`) VALUES
('1','1'),
('1','2'),
('1','3'),
('1','4'),
('1','5'),
('1','7'),
('2','1'),
('2','2'),
('2','3'),
('2','4'),
('2','6'),
('2','7'),
('3','2'),
('3','3'),
('3','4'),
('3','5'),
('3','7'),
('4','1'),
('4','2'),
('4','3'),
('4','5'),
('4','7'),
('5','1'),
('5','2'),
('5','3'),
('5','4'),
('5','5'),
('5','6'),
('5','7'),
('6','2'),
('6','3'),
('6','4'),
('6','5'),
('6','7'),
('7','1'),
('7','2'),
('7','3'),
('7','4'),
('7','5'),
('7','6'),
('7','7'),
('8','1'),
('8','2'),
('8','5'),
('8','6'),
('8','7'),
('8','8'),
('9','1'),
('9','2'),
('9','5'),
('9','6'),
('9','7'),
('9','8'),
('10','1'),
('10','2'),
('10','4'),
('10','6'),
('10','7'),
('10','8'),
('11','2'),
('11','3'),
('11','4'),
('11','6'),
('11','7'),
('11','8'),
('12','1'),
('12','2'),
('12','4'),
('12','5'),
('12','7'),
('12','8'),
('13','2'),
('13','3'),
('13','5'),
('13','6'),
('13','7'),
('13','8'),
('14','1'),
('14','2'),
('14','5'),
('14','6'),
('14','7'),
('14','8'),
('15','1'),
('15','2'),
('15','3'),
('15','5'),
('15','7'),
('15','8'),
('16','1'),
('16','2'),
('16','3'),
('16','6'),
('16','7'),
('16','8'),
('17','1'),
('17','2'),
('17','3'),
('17','5'),
('17','7');

INSERT INTO `court_amenities` (`court_id`,`amenity_id`) VALUES
('17','8'),
('18','1'),
('18','2'),
('18','4'),
('18','6'),
('18','7'),
('18','8'),
('19','2'),
('19','3'),
('19','4'),
('19','6'),
('19','7'),
('19','8'),
('20','2'),
('20','4'),
('20','5'),
('20','6'),
('20','7'),
('20','8'),
('21','1'),
('21','2'),
('21','4'),
('21','5'),
('21','7'),
('21','8'),
('22','2'),
('22','3'),
('22','4'),
('22','5'),
('22','7'),
('22','8'),
('23','1'),
('23','2'),
('23','3'),
('23','5'),
('23','7'),
('23','8'),
('24','1'),
('24','2'),
('24','3'),
('24','6'),
('24','7'),
('24','8'),
('25','1'),
('25','2'),
('25','4'),
('25','5'),
('25','7'),
('25','8'),
('26','1'),
('26','2'),
('26','5'),
('26','6'),
('26','7'),
('26','8'),
('27','1'),
('27','2'),
('27','3'),
('27','4'),
('27','7'),
('27','8'),
('28','1'),
('28','2'),
('28','5'),
('28','6'),
('28','7'),
('28','8'),
('29','2'),
('29','3'),
('29','4'),
('29','5'),
('29','7'),
('29','8'),
('30','2'),
('30','3'),
('30','4'),
('30','5'),
('30','7'),
('30','8'),
('31','2'),
('31','3'),
('31','4'),
('31','6'),
('31','7'),
('31','8'),
('32','1'),
('32','2'),
('32','4'),
('32','5'),
('32','7'),
('32','8'),
('33','1'),
('33','2'),
('33','5'),
('33','6'),
('33','7'),
('33','8'),
('34','2'),
('34','3'),
('34','5');

INSERT INTO `court_amenities` (`court_id`,`amenity_id`) VALUES
('34','6'),
('34','7'),
('34','8'),
('35','1'),
('35','2'),
('35','3'),
('35','4'),
('35','7'),
('35','8'),
('36','1'),
('36','2'),
('36','5'),
('36','6'),
('36','7'),
('36','8'),
('37','2'),
('37','4'),
('37','5'),
('37','6'),
('37','7'),
('37','8'),
('38','1'),
('38','2'),
('38','4'),
('38','5'),
('38','7'),
('38','8'),
('39','1'),
('39','2'),
('39','3'),
('39','6'),
('39','7'),
('39','8'),
('40','1'),
('40','2'),
('40','3'),
('40','5'),
('40','7'),
('40','8'),
('41','2'),
('41','4'),
('41','5'),
('41','6'),
('41','7'),
('41','8'),
('42','1'),
('42','2'),
('42','3'),
('42','5'),
('42','7'),
('42','8'),
('43','1'),
('43','2'),
('43','5'),
('43','6'),
('43','7'),
('43','8'),
('44','2'),
('44','3'),
('44','4'),
('44','6'),
('44','7'),
('44','8'),
('45','2'),
('45','4'),
('45','5'),
('45','6'),
('45','7'),
('45','8'),
('46','2'),
('46','3'),
('46','5'),
('46','6'),
('46','7'),
('46','8'),
('47','1'),
('47','2'),
('47','3'),
('47','6'),
('47','7'),
('47','8'),
('48','1'),
('48','2'),
('48','4'),
('48','6'),
('48','7'),
('48','8'),
('49','2'),
('49','4'),
('49','5'),
('49','6'),
('49','7'),
('49','8'),
('50','2'),
('50','3'),
('50','4'),
('50','5'),
('50','7'),
('50','8'),
('51','1');

INSERT INTO `court_amenities` (`court_id`,`amenity_id`) VALUES
('51','2'),
('51','3'),
('51','5'),
('51','7'),
('51','8'),
('52','1'),
('52','2'),
('52','3'),
('52','4'),
('52','7'),
('52','8'),
('53','2'),
('53','4'),
('53','5'),
('53','6'),
('53','7'),
('53','8'),
('54','1'),
('54','2'),
('54','4'),
('54','6'),
('54','7'),
('54','8'),
('55','1'),
('55','2'),
('55','3'),
('55','6'),
('55','7'),
('55','8'),
('56','1'),
('56','2'),
('56','3'),
('56','6'),
('56','7'),
('56','8'),
('57','1'),
('57','2'),
('57','5'),
('57','6'),
('57','7'),
('57','8'),
('58','1'),
('58','2'),
('58','3'),
('58','5'),
('58','7'),
('58','8'),
('59','1'),
('59','2'),
('59','3'),
('59','4'),
('59','7'),
('59','8'),
('60','1'),
('60','2'),
('60','3'),
('60','6'),
('60','7'),
('60','8'),
('61','1'),
('61','2'),
('61','4'),
('61','6'),
('61','7'),
('61','8'),
('65','2'),
('65','7'),
('65','8'),
('66','1'),
('66','2'),
('66','3'),
('66','7'),
('66','8'),
('67','2'),
('67','7'),
('67','8'),
('68','1'),
('68','2'),
('68','3'),
('68','7'),
('68','8'),
('69','2'),
('69','7'),
('69','8'),
('70','1'),
('70','2'),
('70','3'),
('70','7'),
('70','8'),
('71','2'),
('71','7'),
('71','8'),
('72','1'),
('72','2'),
('72','3'),
('72','7'),
('72','8'),
('73','2'),
('73','7'),
('73','8');

INSERT INTO `court_amenities` (`court_id`,`amenity_id`) VALUES
('74','1'),
('74','2'),
('74','3'),
('74','7'),
('74','8'),
('75','2'),
('75','7'),
('75','8'),
('76','1'),
('76','2'),
('76','3'),
('76','7'),
('76','8'),
('77','2'),
('77','7'),
('77','8'),
('78','1'),
('78','2'),
('78','3'),
('78','7'),
('78','8'),
('79','2'),
('79','7'),
('79','8'),
('80','1'),
('80','2'),
('80','3'),
('80','7'),
('80','8'),
('81','2'),
('81','7'),
('81','8'),
('82','1'),
('82','2'),
('82','3'),
('82','7'),
('82','8'),
('83','2'),
('83','7'),
('83','8'),
('84','1'),
('84','2'),
('84','3'),
('84','7'),
('84','8'),
('85','2'),
('85','7'),
('85','8'),
('86','1'),
('86','2'),
('86','3'),
('86','7'),
('86','8'),
('87','2'),
('87','7'),
('87','8'),
('88','1'),
('88','2'),
('88','3'),
('88','7'),
('88','8'),
('89','2'),
('89','7'),
('89','8'),
('90','1'),
('90','2'),
('90','3'),
('90','7'),
('90','8'),
('91','2'),
('91','7'),
('91','8'),
('92','1'),
('92','2'),
('92','3'),
('92','7'),
('92','8'),
('93','2'),
('93','7'),
('93','8'),
('94','1'),
('94','2'),
('94','3'),
('94','7'),
('94','8'),
('95','2'),
('95','7'),
('95','8'),
('96','1'),
('96','2'),
('96','3'),
('96','7'),
('96','8'),
('97','2'),
('97','7'),
('97','8'),
('98','1'),
('98','2'),
('98','3'),
('98','7');

INSERT INTO `court_amenities` (`court_id`,`amenity_id`) VALUES
('98','8'),
('99','2'),
('99','7'),
('99','8'),
('100','1'),
('100','2'),
('100','3'),
('100','7'),
('100','8'),
('101','2'),
('101','7'),
('101','8'),
('102','1'),
('102','2'),
('102','3'),
('102','7'),
('102','8'),
('103','2'),
('103','7'),
('103','8'),
('104','1'),
('104','2'),
('104','3'),
('104','7'),
('104','8'),
('105','2'),
('105','7'),
('105','8'),
('106','1'),
('106','2'),
('106','3'),
('106','7'),
('106','8'),
('107','2'),
('107','7'),
('107','8'),
('108','1'),
('108','2'),
('108','3'),
('108','7'),
('108','8'),
('109','2'),
('109','7'),
('109','8'),
('110','1'),
('110','2'),
('110','3'),
('110','7'),
('110','8'),
('111','2'),
('111','7'),
('111','8'),
('112','1'),
('112','2'),
('112','3'),
('112','7'),
('112','8'),
('113','2'),
('113','7'),
('113','8'),
('114','1'),
('114','2'),
('114','3'),
('114','7'),
('114','8'),
('115','2'),
('115','7'),
('115','8'),
('116','1'),
('116','2'),
('116','3'),
('116','7'),
('116','8'),
('117','2'),
('117','7'),
('117','8');

-- news: empty at export time.

COMMIT;



-- Source: seed_posts.sql
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
  'Gói hội viên 1 tháng linh hoạt dành cho người chơi thường xuyên, tự động giảm ngay 5% cho mọi lượt đặt sân trên toàn hệ thống GODMINTON.',
  '### Thông tin chi tiết gói Bạc\nGói **Silver Feather** là sự lựa chọn hoàn hảo cho những ai muốn duy trì thói quen tập luyện thể thao 2-3 buổi mỗi tuần mà vẫn tối ưu được ngân sách đặt sân.\n\n### Đặc quyền khi kích hoạt\n- Tự động trừ 5% trực tiếp trên tổng hóa đơn đặt sân.\n- Được áp dụng cộng dồn hoặc ưu tiên cùng mã giảm giá nếu hợp lệ.\n- Thời hạn áp dụng: 1 tháng (30 ngày) kể từ thời điểm thanh toán thành công.',
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
  'Gói hội viên 1 tháng tối ưu chi phí cho các câu lạc bộ và nhóm cố định hàng tuần với mức giảm giá 10% toàn sàn.',
  '### Đặc quyền dành cho câu lạc bộ\nĐược thiết kế cho các nhóm chơi thường xuyên, gói **Golden Racket** giúp thành viên an tâm về chỗ đánh mỗi tuần mà không lo cảnh giành sân giờ cao điểm.\n\n### Chính sách hỗ trợ\n- Giữ khung giờ vàng trước 7 ngày không lo hết chỗ.\n- Miễn phí hủy đơn trước 6 tiếng không bị mất tiền cọc.\n- Tặng kèm 01 ống cầu lông thi đấu chuẩn khi kích hoạt.\n- Thời hạn sử dụng: 1 tháng (30 ngày).',
  'assets/img/memberships/membership-gold.jpg',
  'published',
  NOW() - INTERVAL 2 DAY,
  NULL,
  1,
  2,
  NULL, NULL, NULL,
  349000,
  30,
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
  'Gói hội viên 1 tháng cao cấp nhất dành cho vận động viên, hội nhóm và doanh nghiệp với mức chiết khấu tối đa 15% toàn hệ thống.',
  '### Trải nghiệm VIP không giới hạn\nGói hội viên cao cấp nhất mang lại sự thoải mái tuyệt đối cho các tổ chức, doanh nghiệp hoặc người chơi chuyên nghiệp với đặc quyền tối đa trên mạng lưới đối tác sân cầu lông GODMINTON.\n\n### Đặc quyền VIP\n- Giảm 15% trực tiếp trên tổng hóa đơn đặt sân toàn sàn.\n- Ưu tiên giữ lịch cố định tháng và phục vụ VIP.\n- Miễn phí hủy đơn trước 4 tiếng.\n- Thời hạn sử dụng: 1 tháng (30 ngày).',
  'assets/img/memberships/membership-platinum.jpg',
  'published',
  NOW() - INTERVAL 1 DAY,
  NULL,
  0,
  2,
  NULL, NULL, NULL,
  499000,
  30,
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



-- Source: seed_vouchers.sql
-- database/seed_vouchers.sql
-- Seed dữ liệu mã giảm giá khởi tạo WELCOME15
-- Nền tảng chịu (funded_by = 'platform'), 15%, tối đa 50.000đ, chỉ áp dụng cho đơn đầu tiên.

INSERT INTO `vouchers` (
  `code`,
  `created_by`,
  `owner_id`,
  `court_id`,
  `discount_percent`,
  `max_discount`,
  `min_order`,
  `start_at`,
  `end_at`,
  `usage_limit`,
  `used_count`,
  `per_user_limit`,
  `first_booking_only`,
  `funded_by`,
  `status`
)
SELECT
  'WELCOME15',
  COALESCE((SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1), 1),
  NULL,
  NULL,
  15,
  50000,
  0,
  '2026-01-01 00:00:00',
  '2027-12-31 23:59:59',
  NULL,
  0,
  1,
  1,
  'platform',
  'active'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `vouchers` WHERE `code` = 'WELCOME15'
);



-- Source: seed_reviews_demo.sql
-- database/seed_reviews_demo.sql
-- Dữ liệu MẪU cho "Đánh Giá Từ Vợt Thủ": 125 đánh giá của 118 tài khoản demo trên 41 sân.
-- Tự tạo bảng `reviews` nếu chưa có nên import một mình file này là đủ. Chọn database badminton_booking trước khi import.
-- Chạy lại nhiều lần an toàn (không tạo trùng). Không động tới tài khoản/đánh giá thật.
-- Tài khoản demo có email dạng demo.*@demo.godminton.local, trạng thái 'locked' nên KHÔNG đăng nhập được.
-- Muốn xóa toàn bộ dữ liệu demo (đánh giá tự xóa theo):
--   DELETE FROM users WHERE email LIKE 'demo.%@demo.godminton.local';

-- 0. Bảng đánh giá (bỏ qua nếu đã có)
CREATE TABLE IF NOT EXISTS `reviews` (
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

-- 1. Tài khoản demo (bị khóa, mật khẩu không hợp lệ)
INSERT IGNORE INTO `users` (`name`,`email`,`password_hash`,`role`,`is_verified`,`account_status`,`locked_reason`) VALUES
('Anh Tú G.','demo.anh.tu.g@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Anh Tú P.','demo.anh.tu.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bình Minh T.','demo.binh.minh.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bích Phượng D.','demo.bich.phuong.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bích Phượng R.','demo.bich.phuong.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bích Phượng S.','demo.bich.phuong.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bích Phượng V.','demo.bich.phuong.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Bảo Trân C.','demo.bao.tran.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Chí Công E.','demo.chi.cong.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Chí Công S.','demo.chi.cong.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Chí Công T.','demo.chi.cong.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Chí Công X.','demo.chi.cong.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Công Danh H.','demo.cong.danh.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Công Danh K.','demo.cong.danh.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Diễm My H.','demo.diem.my.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh B.','demo.duy.anh.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh E.','demo.duy.anh.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh H.','demo.duy.anh.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh Q.','demo.duy.anh.q@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Duy Anh S.','demo.duy.anh.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Gia Huy A.','demo.gia.huy.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Gia Huy B.','demo.gia.huy.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Gia Huy C.','demo.gia.huy.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Gia Huy N.','demo.gia.huy.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hoàng Long R.','demo.hoang.long.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hoàng Long X.','demo.hoang.long.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My D.','demo.ha.my.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My G.','demo.ha.my.g@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My H.','demo.ha.my.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My L.','demo.ha.my.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hà My M.','demo.ha.my.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hải Yến M.','demo.hai.yen.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hải Yến T.','demo.hai.yen.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hồng Nhung N.','demo.hong.nhung.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hồng Nhung T.','demo.hong.nhung.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hồng Nhung V.','demo.hong.nhung.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước B.','demo.huu.phuoc.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước L.','demo.huu.phuoc.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước P.','demo.huu.phuoc.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước S.','demo.huu.phuoc.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Hữu Phước T.','demo.huu.phuoc.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Khánh Vy A.','demo.khanh.vy.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân C.','demo.kim.ngan.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân E.','demo.kim.ngan.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân R.','demo.kim.ngan.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân S.','demo.kim.ngan.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Kim Ngân T.','demo.kim.ngan.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Lan Chi N.','demo.lan.chi.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mai Anh X.','demo.mai.anh.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Minh Khoa R.','demo.minh.khoa.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mỹ Duyên E.','demo.my.duyen.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mỹ Duyên M.','demo.my.duyen.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mỹ Duyên S.','demo.my.duyen.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Mỹ Duyên T.','demo.my.duyen.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ngọc Anh A.','demo.ngoc.anh.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ngọc Anh Q.','demo.ngoc.anh.q@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ngọc Anh R.','demo.ngoc.anh.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ngọc Anh V.','demo.ngoc.anh.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Như Quỳnh H.','demo.nhu.quynh.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Như Quỳnh N.','demo.nhu.quynh.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Như Quỳnh R.','demo.nhu.quynh.r@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Nhật Nam H.','demo.nhat.nam.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Nhật Nam L.','demo.nhat.nam.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Nhật Nam N.','demo.nhat.nam.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Phương Linh A.','demo.phuong.linh.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Phương Linh B.','demo.phuong.linh.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Phương Linh C.','demo.phuong.linh.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quang Vinh C.','demo.quang.vinh.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo D.','demo.quoc.bao.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo K.','demo.quoc.bao.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo L.','demo.quoc.bao.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo M.','demo.quoc.bao.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Quốc Bảo V.','demo.quoc.bao.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thanh Tâm K.','demo.thanh.tam.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thanh Tâm M.','demo.thanh.tam.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thanh Tâm T.','demo.thanh.tam.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thanh Tâm X.','demo.thanh.tam.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thu Hà C.','demo.thu.ha.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thu Hà L.','demo.thu.ha.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thu Hà T.','demo.thu.ha.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thùy Dương C.','demo.thuy.duong.c@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thùy Dương Q.','demo.thuy.duong.q@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thùy Dương V.','demo.thuy.duong.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thảo Nguyên B.','demo.thao.nguyen.b@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thế Vinh A.','demo.the.vinh.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thế Vinh K.','demo.the.vinh.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Thế Vinh N.','demo.the.vinh.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên D.','demo.trung.kien.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên E.','demo.trung.kien.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên N.','demo.trung.kien.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên S.','demo.trung.kien.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trung Kiên V.','demo.trung.kien.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trọng Nghĩa K.','demo.trong.nghia.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trọng Nghĩa N.','demo.trong.nghia.n@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Trọng Nghĩa T.','demo.trong.nghia.t@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tuấn Kiệt P.','demo.tuan.kiet.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát K.','demo.tan.phat.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát M.','demo.tan.phat.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát P.','demo.tan.phat.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát S.','demo.tan.phat.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tấn Phát V.','demo.tan.phat.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tố Uyên D.','demo.to.uyen.d@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Tố Uyên M.','demo.to.uyen.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Văn Hậu A.','demo.van.hau.a@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Văn Hậu M.','demo.van.hau.m@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Văn Hậu S.','demo.van.hau.s@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Yến Nhi H.','demo.yen.nhi.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Yến Nhi V.','demo.yen.nhi.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ái Vy E.','demo.ai.vy.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Ái Vy X.','demo.ai.vy.x@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đình Khôi H.','demo.dinh.khoi.h@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đình Khôi L.','demo.dinh.khoi.l@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đình Khôi P.','demo.dinh.khoi.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đăng Khoa G.','demo.dang.khoa.g@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đăng Khoa K.','demo.dang.khoa.k@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đăng Khoa P.','demo.dang.khoa.p@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đăng Khoa V.','demo.dang.khoa.v@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu'),
('Đức Thịnh E.','demo.duc.thinh.e@demo.godminton.local','!','customer',1,'locked','Tài khoản demo cho đánh giá mẫu');

-- 2. Đánh giá mẫu. Bỏ qua sân không tồn tại trong DB và đánh giá đã có.
INSERT IGNORE INTO `reviews` (`court_id`,`user_id`,`rating`,`comment`,`created_at`,`updated_at`)
SELECT c.id,u.id,v.rating,v.comment,v.ts,v.ts FROM (
SELECT 2 AS court_id,'demo.van.hau.m@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Quên mang vợt vẫn có chỗ cho thuê, tiện khi rủ bạn mới chơi.' AS comment,'2026-08-11 21:38:00' AS ts
UNION ALL
SELECT 2 AS court_id,'demo.my.duyen.t@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Có tủ khóa nên để đồ yên tâm hơn. Phòng tắm tiện cho ai đi làm về ghé đánh.' AS comment,'2026-08-24 22:00:00' AS ts
UNION ALL
SELECT 2 AS court_id,'demo.gia.huy.a@demo.godminton.local' AS email,3 AS rating,'Trải nghiệm ở mức bình thường. Vị trí ở Bình Thạnh (cũ), đi lại thuận tiện. Giờ tối và cuối tuần khá đông, nên đặt trước để có khung giờ đẹp.' AS comment,'2026-09-11 19:38:00' AS ts
UNION ALL
SELECT 2 AS court_id,'demo.hai.yen.m@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt.' AS comment,'2026-08-24 10:45:00' AS ts
UNION ALL
SELECT 7 AS court_id,'demo.chi.cong.s@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Có canteen/cafe để nghỉ giữa các set và uống nước. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-10-06 07:00:00' AS ts
UNION ALL
SELECT 7 AS court_id,'demo.khanh.vy.a@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Phòng tắm tiện cho ai đi làm về ghé đánh.' AS comment,'2026-10-09 17:15:00' AS ts
UNION ALL
SELECT 7 AS court_id,'demo.kim.ngan.s@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Vị trí ở Thuận An, Bình Dương, đi lại thuận tiện. Điều hòa giúp đánh lâu mà không bị mệt vì nóng.' AS comment,'2026-08-19 19:15:00' AS ts
UNION ALL
SELECT 7 AS court_id,'demo.van.hau.s@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Có cho thuê vợt nên dẫn người mới đi cùng rất dễ. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-09-17 16:00:00' AS ts
UNION ALL
SELECT 22 AS court_id,'demo.huu.phuoc.t@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Quên mang vợt vẫn có chỗ cho thuê, tiện khi rủ bạn mới chơi.' AS comment,'2026-09-07 11:45:00' AS ts
UNION ALL
SELECT 22 AS court_id,'demo.chi.cong.e@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Phòng tắm tiện cho ai đi làm về ghé đánh. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-20 12:15:00' AS ts
UNION ALL
SELECT 22 AS court_id,'demo.bich.phuong.r@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Có canteen/cafe để nghỉ giữa các set và uống nước.' AS comment,'2026-09-11 07:45:00' AS ts
UNION ALL
SELECT 24 AS court_id,'demo.thu.ha.c@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Sân có điều hòa, đánh liền mấy set vẫn dễ chịu. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-10-06 09:45:00' AS ts
UNION ALL
SELECT 24 AS court_id,'demo.duy.anh.e@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Có tủ khóa nên để đồ yên tâm hơn. Wi-Fi ổn, tiện cho lúc chờ đến lượt.' AS comment,'2026-10-14 07:07:00' AS ts
UNION ALL
SELECT 24 AS court_id,'demo.dinh.khoi.p@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Vị trí ở Quận 6 (cũ), đi lại thuận tiện.' AS comment,'2026-08-26 09:53:00' AS ts
UNION ALL
SELECT 30 AS court_id,'demo.thuy.duong.q@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Vị trí ở Quận 7 (cũ), đi lại thuận tiện. Chắc chắn sẽ quay lại.' AS comment,'2026-10-11 11:07:00' AS ts
UNION ALL
SELECT 30 AS court_id,'demo.chi.cong.t@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Phòng tắm tiện cho ai đi làm về ghé đánh. Có cho thuê vợt nên dẫn người mới đi cùng rất dễ.' AS comment,'2026-10-17 15:45:00' AS ts
UNION ALL
SELECT 30 AS court_id,'demo.tuan.kiet.p@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-08-25 15:22:00' AS ts
UNION ALL
SELECT 42 AS court_id,'demo.my.duyen.s@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giải lao ở canteen sau trận rất thoải mái.' AS comment,'2026-08-12 13:07:00' AS ts
UNION ALL
SELECT 42 AS court_id,'demo.the.vinh.n@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Phòng tắm tiện cho ai đi làm về ghé đánh.' AS comment,'2026-10-11 13:38:00' AS ts
UNION ALL
SELECT 42 AS court_id,'demo.ngoc.anh.q@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-09-10 18:38:00' AS ts
UNION ALL
SELECT 65 AS court_id,'demo.bao.tran.c@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-09-11 15:00:00' AS ts
UNION ALL
SELECT 65 AS court_id,'demo.bich.phuong.v@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết. Giá hơi cao một chút, bù lại sân đánh thoải mái. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-10-13 08:38:00' AS ts
UNION ALL
SELECT 65 AS court_id,'demo.anh.tu.g@demo.godminton.local' AS email,3 AS rating,'Đánh được, nhưng có vài điểm mình thấy cần lưu ý. Vị trí ở Quận 1 (cũ), đi lại thuận tiện. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-08-31 18:22:00' AS ts
UNION ALL
SELECT 68 AS court_id,'demo.hong.nhung.t@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá nhỉnh hơn mặt bằng chung nhưng đáng tiền. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-08-31 14:45:00' AS ts
UNION ALL
SELECT 68 AS court_id,'demo.thanh.tam.x@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-08-28 15:38:00' AS ts
UNION ALL
SELECT 68 AS court_id,'demo.thuy.duong.v@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Vị trí ở Quận 2 (cũ), đi lại thuận tiện. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện. Chắc chắn sẽ quay lại.' AS comment,'2026-10-04 15:15:00' AS ts
UNION ALL
SELECT 69 AS court_id,'demo.my.duyen.e@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Nằm ở khu vực Quận 2 (cũ), dễ tìm. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Chắc chắn sẽ quay lại.' AS comment,'2026-08-13 20:07:00' AS ts
UNION ALL
SELECT 69 AS court_id,'demo.to.uyen.m@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-03 12:53:00' AS ts
UNION ALL
SELECT 69 AS court_id,'demo.tan.phat.s@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-08-06 19:53:00' AS ts
UNION ALL
SELECT 70 AS court_id,'demo.phuong.linh.c@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Giá nhỉnh hơn mặt bằng chung nhưng đáng tiền. Nằm ở khu vực Quận 2 (cũ), dễ tìm. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-08-29 22:45:00' AS ts
UNION ALL
SELECT 70 AS court_id,'demo.duy.anh.s@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Điều hòa giúp đánh lâu mà không bị mệt vì nóng. Chắc chắn sẽ quay lại.' AS comment,'2026-10-03 20:15:00' AS ts
UNION ALL
SELECT 70 AS court_id,'demo.minh.khoa.r@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-10-07 18:45:00' AS ts
UNION ALL
SELECT 74 AS court_id,'demo.dang.khoa.g@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Có điều hòa nên đánh buổi tối không bị ngột ngạt. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-08-17 10:53:00' AS ts
UNION ALL
SELECT 74 AS court_id,'demo.dinh.khoi.l@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Vị trí ở Quận 4 (cũ), đi lại thuận tiện.' AS comment,'2026-10-04 17:38:00' AS ts
UNION ALL
SELECT 74 AS court_id,'demo.nhat.nam.h@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện.' AS comment,'2026-10-17 12:15:00' AS ts
UNION ALL
SELECT 75 AS court_id,'demo.quoc.bao.k@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết.' AS comment,'2026-10-13 17:15:00' AS ts
UNION ALL
SELECT 75 AS court_id,'demo.thanh.tam.m@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-09-20 08:45:00' AS ts
UNION ALL
SELECT 75 AS court_id,'demo.tan.phat.p@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-08-06 08:22:00' AS ts
UNION ALL
SELECT 76 AS court_id,'demo.my.duyen.m@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Chắc chắn sẽ quay lại.' AS comment,'2026-09-02 07:07:00' AS ts
UNION ALL
SELECT 76 AS court_id,'demo.quoc.bao.v@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Vị trí ở Quận 4 (cũ), đi lại thuận tiện. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-08-07 17:07:00' AS ts
UNION ALL
SELECT 76 AS court_id,'demo.hong.nhung.v@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá hợp lý cho chất lượng nhận được. Đánh xong có chỗ tắm nên đỡ phải về nhà mới thay đồ.' AS comment,'2026-10-09 19:15:00' AS ts
UNION ALL
SELECT 83 AS court_id,'demo.cong.danh.k@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-08-31 21:53:00' AS ts
UNION ALL
SELECT 83 AS court_id,'demo.duy.anh.b@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Giá hơi cao một chút, bù lại sân đánh thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-08-24 15:22:00' AS ts
UNION ALL
SELECT 83 AS court_id,'demo.the.vinh.k@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-09-24 08:15:00' AS ts
UNION ALL
SELECT 84 AS court_id,'demo.quoc.bao.l@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-08-24 21:07:00' AS ts
UNION ALL
SELECT 84 AS court_id,'demo.huu.phuoc.s@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Mức giá mềm so với chất lượng sân. Chắc chắn sẽ quay lại.' AS comment,'2026-08-15 08:45:00' AS ts
UNION ALL
SELECT 84 AS court_id,'demo.thuy.duong.c@demo.godminton.local' AS email,3 AS rating,'Đánh được, nhưng có vài điểm mình thấy cần lưu ý. Nằm ở khu vực Quận 8 (cũ), dễ tìm. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-10-03 13:22:00' AS ts
UNION ALL
SELECT 85 AS court_id,'demo.trung.kien.d@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát.' AS comment,'2026-08-21 14:07:00' AS ts
UNION ALL
SELECT 85 AS court_id,'demo.ha.my.h@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Nằm ở khu vực Quận 8 (cũ), dễ tìm. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-26 19:53:00' AS ts
UNION ALL
SELECT 85 AS court_id,'demo.nhu.quynh.n@demo.godminton.local' AS email,3 AS rating,'Sân tạm ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-09-18 16:53:00' AS ts
UNION ALL
SELECT 86 AS court_id,'demo.to.uyen.d@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Nằm ở khu vực Quận 8 (cũ), dễ tìm. Phòng tắm tiện cho ai đi làm về ghé đánh. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-08-22 12:22:00' AS ts
UNION ALL
SELECT 86 AS court_id,'demo.trung.kien.v@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Mức giá mềm so với chất lượng sân. Có chỗ để xe, không phải loay hoay tìm chỗ gửi.' AS comment,'2026-08-15 08:38:00' AS ts
UNION ALL
SELECT 86 AS court_id,'demo.dinh.khoi.h@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-08-16 20:07:00' AS ts
UNION ALL
SELECT 87 AS court_id,'demo.thu.ha.l@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-09-29 21:45:00' AS ts
UNION ALL
SELECT 87 AS court_id,'demo.kim.ngan.t@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn.' AS comment,'2026-10-15 14:38:00' AS ts
UNION ALL
SELECT 87 AS court_id,'demo.dinh.khoi.l@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết. Vị trí ở Quận 9 (cũ), đi lại thuận tiện.' AS comment,'2026-09-20 17:53:00' AS ts
UNION ALL
SELECT 88 AS court_id,'demo.trung.kien.v@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Nằm ở khu vực Quận 9 (cũ), dễ tìm. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-24 22:22:00' AS ts
UNION ALL
SELECT 88 AS court_id,'demo.tan.phat.v@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-09-06 16:45:00' AS ts
UNION ALL
SELECT 88 AS court_id,'demo.hong.nhung.n@demo.godminton.local' AS email,3 AS rating,'Sân tạm ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giờ tối và cuối tuần khá đông, nên đặt trước để có khung giờ đẹp.' AS comment,'2026-09-14 09:53:00' AS ts
UNION ALL
SELECT 91 AS court_id,'demo.quoc.bao.m@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Nằm ở khu vực Quận 10 (cũ), dễ tìm.' AS comment,'2026-08-12 17:53:00' AS ts
UNION ALL
SELECT 91 AS court_id,'demo.ha.my.d@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-09-29 18:22:00' AS ts
UNION ALL
SELECT 91 AS court_id,'demo.hai.yen.m@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng.' AS comment,'2026-08-16 13:22:00' AS ts
UNION ALL
SELECT 92 AS court_id,'demo.tan.phat.m@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Vị trí ở Quận 10 (cũ), đi lại thuận tiện.' AS comment,'2026-08-30 12:45:00' AS ts
UNION ALL
SELECT 92 AS court_id,'demo.duy.anh.h@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-08-23 16:53:00' AS ts
UNION ALL
SELECT 92 AS court_id,'demo.trong.nghia.t@demo.godminton.local' AS email,3 AS rating,'Trải nghiệm ở mức bình thường. Điều hòa giúp đánh lâu mà không bị mệt vì nóng. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-09-24 13:00:00' AS ts
UNION ALL
SELECT 93 AS court_id,'demo.trung.kien.n@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Nằm ở khu vực Quận 11 (cũ), dễ tìm. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-08-06 15:07:00' AS ts
UNION ALL
SELECT 93 AS court_id,'demo.chi.cong.x@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân.' AS comment,'2026-09-04 18:38:00' AS ts
UNION ALL
SELECT 93 AS court_id,'demo.lan.chi.n@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-10-09 13:38:00' AS ts
UNION ALL
SELECT 98 AS court_id,'demo.trong.nghia.k@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn.' AS comment,'2026-09-11 19:38:00' AS ts
UNION ALL
SELECT 98 AS court_id,'demo.ai.vy.e@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Điều hòa giúp đánh lâu mà không bị mệt vì nóng.' AS comment,'2026-08-11 07:38:00' AS ts
UNION ALL
SELECT 98 AS court_id,'demo.kim.ngan.c@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Nằm ở khu vực Quận 12 (cũ), dễ tìm.' AS comment,'2026-09-22 22:15:00' AS ts
UNION ALL
SELECT 99 AS court_id,'demo.diem.my.h@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Vị trí ở Bình Thạnh (cũ), đi lại thuận tiện. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-09-27 18:38:00' AS ts
UNION ALL
SELECT 99 AS court_id,'demo.trung.kien.e@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-09-26 17:38:00' AS ts
UNION ALL
SELECT 99 AS court_id,'demo.ha.my.g@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-09-23 22:53:00' AS ts
UNION ALL
SELECT 100 AS court_id,'demo.van.hau.a@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Vị trí ở Tân Bình (cũ), đi lại thuận tiện. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-08-06 20:07:00' AS ts
UNION ALL
SELECT 100 AS court_id,'demo.tan.phat.k@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Điều hòa giúp đánh lâu mà không bị mệt vì nóng.' AS comment,'2026-08-23 18:15:00' AS ts
UNION ALL
SELECT 100 AS court_id,'demo.gia.huy.n@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Đánh xong có chỗ tắm nên đỡ phải về nhà mới thay đồ. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-08-09 21:38:00' AS ts
UNION ALL
SELECT 101 AS court_id,'demo.huu.phuoc.p@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Vị trí ở Tân Bình (cũ), đi lại thuận tiện.' AS comment,'2026-10-10 08:38:00' AS ts
UNION ALL
SELECT 101 AS court_id,'demo.bich.phuong.s@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng.' AS comment,'2026-08-21 17:22:00' AS ts
UNION ALL
SELECT 101 AS court_id,'demo.thanh.tam.t@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn.' AS comment,'2026-09-29 21:45:00' AS ts
UNION ALL
SELECT 102 AS court_id,'demo.bich.phuong.d@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-09-11 13:00:00' AS ts
UNION ALL
SELECT 102 AS court_id,'demo.gia.huy.b@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Đánh xong có chỗ tắm nên đỡ phải về nhà mới thay đồ. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Chắc chắn sẽ quay lại.' AS comment,'2026-08-10 10:38:00' AS ts
UNION ALL
SELECT 102 AS court_id,'demo.nhu.quynh.h@demo.godminton.local' AS email,3 AS rating,'Trải nghiệm ở mức bình thường. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Giờ tối và cuối tuần khá đông, nên đặt trước để có khung giờ đẹp.' AS comment,'2026-08-08 12:15:00' AS ts
UNION ALL
SELECT 103 AS court_id,'demo.trung.kien.s@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Vị trí ở Gò Vấp (cũ), đi lại thuận tiện. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-08-22 17:22:00' AS ts
UNION ALL
SELECT 103 AS court_id,'demo.duy.anh.h@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-08-15 07:45:00' AS ts
UNION ALL
SELECT 103 AS court_id,'demo.ha.my.m@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Chắc chắn sẽ quay lại.' AS comment,'2026-08-27 12:53:00' AS ts
UNION ALL
SELECT 104 AS court_id,'demo.kim.ngan.e@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-08-18 19:15:00' AS ts
UNION ALL
SELECT 104 AS court_id,'demo.yen.nhi.v@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Giá hợp lý cho chất lượng nhận được. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-08-23 17:07:00' AS ts
UNION ALL
SELECT 104 AS court_id,'demo.duc.thinh.e@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Vị trí ở Gò Vấp (cũ), đi lại thuận tiện. Sân có điều hòa, đánh liền mấy set vẫn dễ chịu.' AS comment,'2026-10-06 21:07:00' AS ts
UNION ALL
SELECT 105 AS court_id,'demo.thu.ha.t@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Sân trong nhà nên không lo mưa gió, cầu bay ổn định. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-09-23 08:22:00' AS ts
UNION ALL
SELECT 105 AS court_id,'demo.phuong.linh.b@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Vị trí ở Gò Vấp (cũ), đi lại thuận tiện. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn.' AS comment,'2026-09-06 09:00:00' AS ts
UNION ALL
SELECT 105 AS court_id,'demo.hai.yen.t@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Có chỗ để xe, không phải loay hoay tìm chỗ gửi.' AS comment,'2026-08-14 07:22:00' AS ts
UNION ALL
SELECT 106 AS court_id,'demo.huu.phuoc.b@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ. Mở từ 6h sáng nên đánh sớm trước giờ làm được.' AS comment,'2026-08-25 13:00:00' AS ts
UNION ALL
SELECT 106 AS court_id,'demo.yen.nhi.h@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Sân trong nhà nên không lo mưa gió, cầu bay ổn định.' AS comment,'2026-08-19 10:53:00' AS ts
UNION ALL
SELECT 106 AS court_id,'demo.ngoc.anh.v@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Có điều hòa nên đánh buổi tối không bị ngột ngạt. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện.' AS comment,'2026-08-26 17:45:00' AS ts
UNION ALL
SELECT 107 AS court_id,'demo.gia.huy.c@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Nằm ở khu vực Phú Nhuận (cũ), dễ tìm. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-09-25 10:15:00' AS ts
UNION ALL
SELECT 107 AS court_id,'demo.huu.phuoc.l@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Có chỗ để xe, không phải loay hoay tìm chỗ gửi. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn.' AS comment,'2026-09-25 15:45:00' AS ts
UNION ALL
SELECT 107 AS court_id,'demo.nhat.nam.l@demo.godminton.local' AS email,3 AS rating,'Đánh được, nhưng có vài điểm mình thấy cần lưu ý. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Giờ cao điểm hơi đông, bạn nào muốn giờ đẹp nên đặt sớm.' AS comment,'2026-08-24 17:45:00' AS ts
UNION ALL
SELECT 108 AS court_id,'demo.dang.khoa.p@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Vị trí ở Phú Nhuận (cũ), đi lại thuận tiện. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết.' AS comment,'2026-09-06 15:45:00' AS ts
UNION ALL
SELECT 108 AS court_id,'demo.thao.nguyen.b@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Giá hợp lý cho chất lượng nhận được. Có phòng tắm nên đánh xong tắm rửa rồi về luôn, rất tiện. Chắc chắn sẽ quay lại.' AS comment,'2026-10-06 18:15:00' AS ts
UNION ALL
SELECT 108 AS court_id,'demo.cong.danh.h@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-09-02 09:38:00' AS ts
UNION ALL
SELECT 109 AS court_id,'demo.phuong.linh.a@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được.' AS comment,'2026-09-03 22:38:00' AS ts
UNION ALL
SELECT 109 AS court_id,'demo.duy.anh.q@demo.godminton.local' AS email,4 AS rating,'Nhìn chung mình hài lòng. Vị trí ở Thủ Đức (cũ), đi lại thuận tiện. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-10-06 07:38:00' AS ts
UNION ALL
SELECT 109 AS court_id,'demo.dang.khoa.k@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Giá nhỉnh hơn mặt bằng chung nhưng đáng tiền. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Chắc chắn sẽ quay lại.' AS comment,'2026-10-10 19:07:00' AS ts
UNION ALL
SELECT 110 AS court_id,'demo.thanh.tam.t@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Có chỗ để xe, không phải loay hoay tìm chỗ gửi.' AS comment,'2026-10-05 17:07:00' AS ts
UNION ALL
SELECT 110 AS court_id,'demo.dang.khoa.v@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Phòng tắm tiện cho ai đi làm về ghé đánh. Vị trí ở Thủ Đức (cũ), đi lại thuận tiện.' AS comment,'2026-09-03 11:15:00' AS ts
UNION ALL
SELECT 110 AS court_id,'demo.mai.anh.x@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Giá nhỉnh hơn mặt bằng chung nhưng đáng tiền. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát.' AS comment,'2026-08-12 11:00:00' AS ts
UNION ALL
SELECT 111 AS court_id,'demo.anh.tu.p@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết.' AS comment,'2026-10-12 22:15:00' AS ts
UNION ALL
SELECT 111 AS court_id,'demo.the.vinh.a@demo.godminton.local' AS email,5 AS rating,'Sân đánh rất đã, cả nhóm đều ưng. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Chắc chắn sẽ quay lại.' AS comment,'2026-08-21 08:45:00' AS ts
UNION ALL
SELECT 111 AS court_id,'demo.nhu.quynh.r@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Giá hơi cao một chút, bù lại sân đánh thoải mái. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-09-28 21:22:00' AS ts
UNION ALL
SELECT 112 AS court_id,'demo.hoang.long.x@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Mở từ 6h sáng nên đánh sớm trước giờ làm được. Mức giá mềm so với chất lượng sân.' AS comment,'2026-09-25 13:00:00' AS ts
UNION ALL
SELECT 112 AS court_id,'demo.trong.nghia.n@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Nằm ở khu vực Bình Tân (cũ), dễ tìm. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-10-17 14:22:00' AS ts
UNION ALL
SELECT 112 AS court_id,'demo.ai.vy.x@demo.godminton.local' AS email,4 AS rating,'Sân đánh được, nhóm mình thấy ổn. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng.' AS comment,'2026-08-23 19:38:00' AS ts
UNION ALL
SELECT 113 AS court_id,'demo.ngoc.anh.a@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Vị trí ở Bình Tân (cũ), đi lại thuận tiện.' AS comment,'2026-08-07 15:45:00' AS ts
UNION ALL
SELECT 113 AS court_id,'demo.thanh.tam.k@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-10-02 14:22:00' AS ts
UNION ALL
SELECT 113 AS court_id,'demo.hoang.long.r@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Giá thuê dễ chịu, hợp với sinh viên và nhóm bạn. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-10-04 10:53:00' AS ts
UNION ALL
SELECT 115 AS court_id,'demo.nhat.nam.n@demo.godminton.local' AS email,5 AS rating,'Trải nghiệm tuyệt vời, rất đáng để quay lại. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Giá ở mức vừa phải, đánh thường xuyên vẫn ổn. Sẽ rủ thêm bạn bè đến đánh.' AS comment,'2026-10-09 22:15:00' AS ts
UNION ALL
SELECT 115 AS court_id,'demo.quoc.bao.d@demo.godminton.local' AS email,5 AS rating,'Mình chơi ở đây mấy buổi rồi, buổi nào cũng ổn. Wi-Fi ổn, tiện cho lúc chờ đến lượt. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-09-04 14:22:00' AS ts
UNION ALL
SELECT 115 AS court_id,'demo.thanh.tam.t@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Đặt sân qua GODMINTON nhanh, chọn khung giờ rõ ràng. Nằm ở khu vực Tân Phú (cũ), dễ tìm.' AS comment,'2026-08-21 20:53:00' AS ts
UNION ALL
SELECT 116 AS court_id,'demo.binh.minh.t@demo.godminton.local' AS email,5 AS rating,'Rất hài lòng với sân này. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Bãi để xe dễ vào, cả nhóm đi xe máy đều ổn.' AS comment,'2026-08-15 08:53:00' AS ts
UNION ALL
SELECT 116 AS court_id,'demo.thuy.duong.q@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Nằm ở khu vực Tân Phú (cũ), dễ tìm. Giá hợp lý cho chất lượng nhận được.' AS comment,'2026-08-10 09:22:00' AS ts
UNION ALL
SELECT 116 AS court_id,'demo.kim.ngan.r@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Không gian trong nhà, cầu bay thẳng và dễ kiểm soát. Mở cửa tới 22h nên đi làm về vẫn kịp đánh.' AS comment,'2026-10-13 07:38:00' AS ts
UNION ALL
SELECT 117 AS court_id,'demo.quang.vinh.c@demo.godminton.local' AS email,4 AS rating,'Chơi ở đây khá ổn, đáng để quay lại. Chỗ để xe thuận tiện, đến sát giờ vẫn kịp vào sân. Đặt giờ qua web tiện, không phải gọi điện hỏi từng chỗ.' AS comment,'2026-09-20 11:38:00' AS ts
UNION ALL
SELECT 117 AS court_id,'demo.ngoc.anh.r@demo.godminton.local' AS email,4 AS rating,'Sân ổn, đánh khá thoải mái. Giá hợp lý cho chất lượng nhận được. Đánh trong nhà nên ít bị ảnh hưởng bởi thời tiết.' AS comment,'2026-09-05 17:07:00' AS ts
UNION ALL
SELECT 117 AS court_id,'demo.ha.my.l@demo.godminton.local' AS email,5 AS rating,'Đánh ở đây rất thoải mái. Mở cửa tới 22h nên đi làm về vẫn kịp đánh. Có Wi-Fi nên lúc chờ lượt vẫn xem tin nhắn được. Giới thiệu cho anh em chơi cầu lông.' AS comment,'2026-08-21 21:38:00' AS ts
) v JOIN `courts` c ON c.id=v.court_id JOIN `users` u ON u.email=v.email;



-- Cap nhat noi dung cac goi hoi vien mau
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

