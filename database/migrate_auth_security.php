<?php
// Additive, repeatable migration. Existing users and bookings are preserved.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__.'/../config/db.php';
$columns=$pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('session_version',$columns,true)) $pdo->exec('ALTER TABLE users ADD session_version INT UNSIGNED NOT NULL DEFAULT 0');
$pdo->exec("CREATE TABLE IF NOT EXISTS auth_tokens (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NOT NULL,
 purpose ENUM('verify','reset') NOT NULL, token_hash CHAR(64) NOT NULL UNIQUE,
 expires_at DATETIME NOT NULL, used_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_auth_user (user_id,purpose), KEY idx_auth_expiry (expires_at),
 CONSTRAINT fk_auth_token_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS auth_rate_limits (
 key_hash CHAR(64) PRIMARY KEY, attempts INT UNSIGNED NOT NULL DEFAULT 0,
 window_started_at DATETIME NOT NULL, KEY idx_auth_rate_window(window_started_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "Auth security migration OK.\n";
