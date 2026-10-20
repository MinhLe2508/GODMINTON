-- database/migration_push_subscriptions.sql
-- Tuần 4 (Triết & Huy — STT 22): CSDL cho Web Push Notification
-- Chạy 1 lần trên MySQL để lưu trữ các subscription từ trình duyệt người dùng.

-- Select the target database before running this migration.

CREATE TABLE IF NOT EXISTS push_subscriptions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NULL,                           -- NULL nếu người dùng chưa đăng nhập (khách vãng lai bật thông báo)
    endpoint    TEXT NOT NULL,                               -- Web push endpoint URL từ push service (FCM, Mozilla, Apple, etc.)
    p256dh      VARCHAR(255) NOT NULL,                       -- Public key của client
    auth        VARCHAR(255) NOT NULL,                       -- Auth secret token của client
    user_agent  VARCHAR(255) NULL,                           -- Thông tin thiết bị / trình duyệt
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_endpoint (endpoint(191)),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
