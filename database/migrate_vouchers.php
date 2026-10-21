<?php
/**
 * database/migrate_vouchers.php
 * Script CLI chạy migration và seed tính năng Mã giảm giá (Vouchers).
 * Idempotent: chạy nhiều lần an toàn, không làm mất dữ liệu hiện có.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("CLI only\n");
}

require_once __DIR__ . '/../config/db.php';

echo "Bắt đầu migration tính năng Voucher...\n";

// 1. Tạo bảng vouchers
$pdo->exec("CREATE TABLE IF NOT EXISTS `vouchers` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "- Đã kiểm tra/tạo bảng `vouchers`.\n";

// 2. Tạo bảng voucher_usages
$pdo->exec("CREATE TABLE IF NOT EXISTS `voucher_usages` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "- Đã kiểm tra/tạo bảng `voucher_usages`.\n";

// 3. Thêm các cột vào bookings
$columns = $pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(PDO::FETCH_COLUMN, 0);

$colsToAdd = [
    'voucher_id'         => "INT(10) UNSIGNED DEFAULT NULL AFTER `court_id`",
    'court_amount'       => "DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `booking_code`",
    'discount_amount'    => "DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `court_amount`",
    'discount_funded_by' => "ENUM('platform','owner') DEFAULT NULL AFTER `discount_amount`",
    'platform_fee'       => "DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `discount_funded_by`",
    'owner_amount'       => "DECIMAL(12,0) NOT NULL DEFAULT 0 AFTER `platform_fee`",
];

foreach ($colsToAdd as $col => $definition) {
    if (!in_array($col, $columns, true)) {
        $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `{$col}` {$definition}");
        echo "  + Đã thêm cột `{$col}` vào `bookings`.\n";
    }
}

// Kiểm tra khóa ngoại fk_bookings_voucher
$fkCheck = $pdo->query("
    SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'bookings'
      AND CONSTRAINT_NAME = 'fk_bookings_voucher'
")->fetchColumn();

if ((int)$fkCheck === 0) {
    try {
        $pdo->exec("ALTER TABLE `bookings` ADD CONSTRAINT `fk_bookings_voucher` FOREIGN KEY (`voucher_id`) REFERENCES `vouchers` (`id`) ON DELETE SET NULL");
        echo "- Đã thêm khóa ngoại `fk_bookings_voucher` cho `bookings`.\n";
    } catch (PDOException $e) {
        echo "  ! Cảnh báo khi tạo FK `fk_bookings_voucher`: " . $e->getMessage() . "\n";
    }
}

// 4. Seed mã WELCOME15
$seedStmt = $pdo->prepare("
    INSERT INTO `vouchers` (
      `code`, `created_by`, `owner_id`, `court_id`, `discount_percent`,
      `max_discount`, `min_order`, `start_at`, `end_at`, `usage_limit`,
      `used_count`, `per_user_limit`, `first_booking_only`, `funded_by`, `status`
    )
    SELECT
      'WELCOME15',
      COALESCE((SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1), 1),
      NULL, NULL, 15, 50000, 0,
      '2026-01-01 00:00:00', '2027-12-31 23:59:59', NULL, 0, 1, 1, 'platform', 'active'
    FROM DUAL
    WHERE NOT EXISTS (
      SELECT 1 FROM `vouchers` WHERE `code` = 'WELCOME15'
    )
");
$seedStmt->execute();
echo "- Đã kiểm tra/seed mã `WELCOME15`.\n";

echo "Hoàn thành migration Voucher thành công!\n";
