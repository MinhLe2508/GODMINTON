-- database/migration_vouchers.sql
-- GODMINTON - Migration tính năng Mã giảm giá (Vouchers)
-- Tương thích: MariaDB 10.4 (XAMPP) & MySQL 8.0
-- Không xóa hay thay đổi dữ liệu hiện có.

-- 1. Bảng lưu danh mục voucher
CREATE TABLE IF NOT EXISTS `vouchers` (
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
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_code` (`code`),
  KEY `idx_vouchers_owner` (`owner_id`),
  KEY `idx_vouchers_court` (`court_id`),
  KEY `idx_vouchers_status_dates` (`status`,`start_at`,`end_at`),
  CONSTRAINT `fk_vouchers_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_vouchers_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_vouchers_court` FOREIGN KEY (`court_id`) REFERENCES `courts` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Bảng lưu lịch sử sử dụng voucher theo đơn đặt sân
CREATE TABLE IF NOT EXISTS `voucher_usages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `voucher_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned NOT NULL,
  `booking_id` int(10) unsigned NOT NULL,
  `discount_amount` decimal(12,0) NOT NULL,
  `used_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `released_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_voucher_usage_booking` (`booking_id`),
  KEY `idx_vu_voucher_user` (`voucher_id`,`user_id`),
  KEY `idx_vu_user` (`user_id`),
  CONSTRAINT `fk_vu_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`),
  CONSTRAINT `fk_vu_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_vu_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bổ sung các cột chiết tính tiền và voucher vào bảng bookings
ALTER TABLE `bookings`
  ADD COLUMN IF NOT EXISTS `voucher_id` int(10) unsigned DEFAULT NULL AFTER `court_id`,
  ADD COLUMN IF NOT EXISTS `court_amount` decimal(12,0) NOT NULL DEFAULT 0 AFTER `booking_code`,
  ADD COLUMN IF NOT EXISTS `discount_amount` decimal(12,0) NOT NULL DEFAULT 0 AFTER `court_amount`,
  ADD COLUMN IF NOT EXISTS `discount_funded_by` enum('platform','owner') DEFAULT NULL AFTER `discount_amount`,
  ADD COLUMN IF NOT EXISTS `platform_fee` decimal(12,0) NOT NULL DEFAULT 0 AFTER `discount_funded_by`,
  ADD COLUMN IF NOT EXISTS `owner_amount` decimal(12,0) NOT NULL DEFAULT 0 AFTER `platform_fee`;

-- Thêm khóa ngoại cho bookings.voucher_id (an toàn không gây lỗi nếu đã tồn tại)
SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND CONSTRAINT_NAME = 'fk_bookings_voucher'
);
SET @sql = IF(@fk_exists = 0,
    'ALTER TABLE `bookings` ADD CONSTRAINT `fk_bookings_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL',
    'SELECT "fk_bookings_voucher already exists"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

/*
-- =======================================================================
-- PHẦN HOÀN TÁC (ROLLBACK SCRIPT)
-- Lưu ý: Chỉ chạy khi bạn muốn gỡ bỏ hoàn toàn tính năng voucher.
-- =======================================================================

SET @fk_exists = (
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND CONSTRAINT_NAME = 'fk_bookings_voucher'
);
SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `bookings` DROP FOREIGN KEY `fk_bookings_voucher`',
    'SELECT "No foreign key to drop"'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

ALTER TABLE `bookings`
  DROP COLUMN IF EXISTS `owner_amount`,
  DROP COLUMN IF EXISTS `platform_fee`,
  DROP COLUMN IF EXISTS `discount_funded_by`,
  DROP COLUMN IF EXISTS `discount_amount`,
  DROP COLUMN IF EXISTS `court_amount`,
  DROP COLUMN IF EXISTS `voucher_id`;

DROP TABLE IF EXISTS `voucher_usages`;
DROP TABLE IF EXISTS `vouchers`;
-- =======================================================================
*/
