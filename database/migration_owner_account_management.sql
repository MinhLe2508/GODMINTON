-- GODMINTON: owner applications and administrator account management.
-- Select the existing website DB in phpMyAdmin, then import.
-- Import migration_customer_accounts.sql FIRST on older installations.
-- Repeatable; preserves existing users, roles, courts, bookings and prices.
SET NAMES utf8mb4;

SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='account_status'),'DO 0','ALTER TABLE `users` ADD COLUMN `account_status` ENUM(''active'',''locked'') NOT NULL DEFAULT ''active''');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;

SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='owner_intent'),'DO 0','ALTER TABLE `users` ADD COLUMN `owner_intent` TINYINT(1) NOT NULL DEFAULT 0');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;

SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='locked_reason'),'DO 0','ALTER TABLE `users` ADD COLUMN `locked_reason` VARCHAR(1000) NULL');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;

SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='locked_at'),'DO 0','ALTER TABLE `users` ADD COLUMN `locked_at` DATETIME NULL');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;

SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='locked_by'),'DO 0','ALTER TABLE `users` ADD COLUMN `locked_by` INT UNSIGNED NULL');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;

SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courts' AND COLUMN_NAME='approval_status'),'DO 0','ALTER TABLE `courts` ADD COLUMN `approval_status` ENUM(''approved'',''suspended'') NOT NULL DEFAULT ''approved''');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;

SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='courts' AND COLUMN_NAME='booking_open'),'DO 0','ALTER TABLE `courts` ADD COLUMN `booking_open` TINYINT(1) NOT NULL DEFAULT 1');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;

CREATE TABLE IF NOT EXISTS owner_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 user_id INT UNSIGNED NOT NULL,
 kind ENUM('new','claim','update') NOT NULL,
 court_id INT UNSIGNED NULL,
 status ENUM('draft','pending','needs_info','approved','rejected','withdrawn') NOT NULL DEFAULT 'draft',
 representative_name VARCHAR(150) NOT NULL DEFAULT '',
 representative_phone VARCHAR(30) NOT NULL DEFAULT '',
 evidence_note TEXT NULL,
 evidence_url VARCHAR(2000) NULL,
 payload LONGTEXT NOT NULL,
 review_note TEXT NULL,
 reviewed_by INT UNSIGNED NULL,
 reviewed_at DATETIME NULL,
 revision INT UNSIGNED NOT NULL DEFAULT 1,
 submitted_at DATETIME NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_owner_queue(status,updated_at), KEY idx_owner_applicant(user_id,status), KEY idx_owner_court(court_id),
 CONSTRAINT fk_owner_request_user FOREIGN KEY(user_id) REFERENCES users(id),
 CONSTRAINT fk_owner_request_court FOREIGN KEY(court_id) REFERENCES courts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS owner_request_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, request_id BIGINT UNSIGNED NOT NULL,
 actor_id INT UNSIGNED NOT NULL, action VARCHAR(40) NOT NULL, note TEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_owner_events(request_id,id),
 CONSTRAINT fk_owner_event_request FOREIGN KEY(request_id) REFERENCES owner_requests(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS account_audit (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, actor_id INT UNSIGNED NOT NULL,
 target_user_id INT UNSIGNED NULL, request_id BIGINT UNSIGNED NULL, court_id INT UNSIGNED NULL,
 action VARCHAR(60) NOT NULL, reason VARCHAR(1000) NOT NULL,
 details TEXT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_audit_target(target_user_id,id), KEY idx_audit_actor(actor_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS account_notifications (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
 title VARCHAR(200) NOT NULL, message TEXT NOT NULL, request_id BIGINT UNSIGNED NULL,
 email_sent_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_account_notices(user_id,id),
 CONSTRAINT fk_account_notice_user FOREIGN KEY(user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
SET @god_workflow_ddl=NULL;

-- Email address changes are activated only after proof of the new mailbox.
SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='pending_email'),'DO 0','ALTER TABLE users ADD COLUMN pending_email VARCHAR(150) NULL DEFAULT NULL');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;
SET @god_workflow_ddl=IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='auth_tokens' AND COLUMN_NAME='purpose' AND COLUMN_TYPE LIKE '%''email_change''%'),'DO 0','ALTER TABLE auth_tokens MODIFY COLUMN purpose ENUM(''verify'',''reset'',''delete'',''email_change'') NOT NULL');
PREPARE god_workflow_stmt FROM @god_workflow_ddl;
EXECUTE god_workflow_stmt;
DEALLOCATE PREPARE god_workflow_stmt;
SET @god_workflow_ddl=NULL;
