-- GODMINTON: upgrade account registration/login, email verification,
-- password reset, session revocation and account deletion.
-- In phpMyAdmin, SELECT THE EXISTING WEBSITE DATABASE, then import this file.
-- Requires the existing users table (id INT UNSIGNED).
-- Compatible with MySQL 5.7+/8.x and MariaDB 10.4+.
-- Repeatable. No database selection, seed, DROP, DELETE or account changes.
-- DDL commits implicitly: back up the database before any schema upgrade.

SET NAMES utf8mb4;

SET @godminton_account_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='session_version'),
    'DO 0',
    'ALTER TABLE users ADD COLUMN session_version INT UNSIGNED NOT NULL DEFAULT 0'
);
PREPARE godminton_account_stmt FROM @godminton_account_ddl;
EXECUTE godminton_account_stmt;
DEALLOCATE PREPARE godminton_account_stmt;

SET @godminton_account_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='deleted_at'),
    'DO 0',
    'ALTER TABLE users ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL'
);
PREPARE godminton_account_stmt FROM @godminton_account_ddl;
EXECUTE godminton_account_stmt;
DEALLOCATE PREPARE godminton_account_stmt;

CREATE TABLE IF NOT EXISTS auth_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    purpose ENUM('verify','reset','delete') NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auth_user (user_id,purpose),
    KEY idx_auth_expiry (expires_at),
    CONSTRAINT fk_auth_token_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Upgrade auth_tokens created by the earlier verification/reset migration.
SET @godminton_account_ddl = IF(
    EXISTS(SELECT 1 FROM information_schema.COLUMNS
           WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='auth_tokens'
             AND COLUMN_NAME='purpose' AND COLUMN_TYPE LIKE '%''delete''%'),
    'DO 0',
    'ALTER TABLE auth_tokens MODIFY COLUMN purpose ENUM(''verify'',''reset'',''delete'') NOT NULL'
);
PREPARE godminton_account_stmt FROM @godminton_account_ddl;
EXECUTE godminton_account_stmt;
DEALLOCATE PREPARE godminton_account_stmt;

CREATE TABLE IF NOT EXISTS auth_rate_limits (
    key_hash CHAR(64) PRIMARY KEY,
    attempts INT UNSIGNED NOT NULL DEFAULT 0,
    window_started_at DATETIME NOT NULL,
    KEY idx_auth_rate_window(window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @godminton_account_ddl = NULL;
