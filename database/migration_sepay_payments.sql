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

-- Bảng payments: Lưu yêu cầu thanh toán tiền cọc gắn với đơn đặt sân
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_id` INT UNSIGNED NOT NULL,
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
  KEY `idx_pay_status_exp` (`status`, `expires_at`),
  CONSTRAINT `fk_pay_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

