-- GODMINTON: thêm hồ sơ người chơi (khu vực sinh sống theo địa chỉ cũ, trình độ, thời gian chơi yêu thích).
-- Trong phpMyAdmin: CHỌN DATABASE của website rồi import file này.
-- Có thể import lại nhiều lần. Không DROP/DELETE, không đổi dữ liệu hiện có.
-- DDL tự commit: nên sao lưu database trước khi nâng cấp.

SET NAMES utf8mb4;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='area_label'),
    'DO 0',
    'ALTER TABLE users ADD COLUMN area_label VARCHAR(100) NULL DEFAULT NULL');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='skill_level'),
    'DO 0',
    'ALTER TABLE users ADD COLUMN skill_level VARCHAR(20) NULL DEFAULT NULL');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;

SET @g_ddl = IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='preferred_time'),
    'DO 0',
    'ALTER TABLE users ADD COLUMN preferred_time VARCHAR(20) NULL DEFAULT NULL');
PREPARE g_stmt FROM @g_ddl; EXECUTE g_stmt; DEALLOCATE PREPARE g_stmt;
