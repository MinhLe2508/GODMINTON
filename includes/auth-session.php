<?php
/**
 * includes/auth-session.php
 * Quản lý phiên đăng nhập (PHP Session) & Ghi nhớ đăng nhập (Remember Me) bảo mật cao:
 * - Cookie HttpOnly, Secure (tự động bật trên HTTPS + tiền tố __Host-), SameSite=Lax
 * - Cơ chế Split-Token (Selector + Validator băm SHA-256) chống lộ token trong DB & chống timing attack
 * - Tự động xoay vòng khóa (Token Rotation) mỗi lần khôi phục phiên và thu hồi toàn bộ nếu phát hiện đánh cắp cookie
 * - Ràng buộc chặt với users.session_version (đổi mật khẩu / đặt lại mật khẩu tự động vô hiệu hóa mọi thiết bị)
 */

const GOD_REMEMBER_DAYS = 30;

function god_auth_is_https(): bool {
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    if (!empty($_SERVER['REQUEST_SCHEME']) && strtolower((string)$_SERVER['REQUEST_SCHEME']) === 'https') {
        return true;
    }
    if ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    return false;
}

function god_auth_remember_cookie_name(): string {
    return god_auth_is_https() ? '__Host-godminton_remember' : 'godminton_remember';
}

function god_auth_saved_login_cookie_name(): string {
    return god_auth_is_https() ? '__Host-godminton_saved_login' : 'godminton_saved_login';
}

function god_auth_cipher_key(): string {
    $secret = (string)(getenv('APP_KEY') ?: getenv('SEPAY_WEBHOOK_API_KEY') ?: getenv('SMTP_PASS') ?: 'godminton-default-secret-key-2026');
    return hash_hmac('sha256', 'godminton-aes-256-gcm-login-v1|' . __DIR__, $secret, true);
}

/**
 * ĐÃ LOẠI BỎ: Không lưu mật khẩu/email vào Cookie theo tiêu chuẩn bảo mật OWASP.
 * Cơ chế xác thực duy trì băm Argon2id trong Database và Remember Token an toàn (Selector-Validator).
 * Hàm này chủ động xóa bỏ cookie cũ (nếu còn sót trên trình duyệt).
 */
function god_auth_set_saved_credentials(string $email, string $password, int $days = GOD_REMEMBER_DAYS): void {
    god_auth_clear_saved_credentials();
}

function god_auth_clear_saved_credentials(): void {
    $isHttps = god_auth_is_https();
    foreach (['__Host-godminton_saved_login', 'godminton_saved_login'] as $name) {
        if (isset($_COOKIE[$name]) || $name === god_auth_saved_login_cookie_name()) {
            setcookie($name, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $name === '__Host-godminton_saved_login' ? true : $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            unset($_COOKIE[$name]);
        }
    }
}

function god_auth_get_saved_credentials(): ?array {
    god_auth_clear_saved_credentials();
    return null;
}

function god_auth_set_remember_cookie(string $value, int $expiresAt): void {
    $isHttps = god_auth_is_https();
    $name = god_auth_remember_cookie_name();
    setcookie($name, $value, [
        'expires'  => $expiresAt,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[$name] = $value;
}

function god_auth_clear_remember_cookie(): void {
    $isHttps = god_auth_is_https();
    foreach (['__Host-godminton_remember', 'godminton_remember'] as $name) {
        if (isset($_COOKIE[$name]) || $name === god_auth_remember_cookie_name()) {
            setcookie($name, '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $name === '__Host-godminton_remember' ? true : $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            unset($_COOKIE[$name]);
        }
    }
}

function god_auth_get_remember_cookie(): string {
    foreach (['__Host-godminton_remember', 'godminton_remember'] as $name) {
        if (!empty($_COOKIE[$name]) && is_string($_COOKIE[$name])) {
            return trim($_COOKIE[$name]);
        }
    }
    return '';
}

function god_auth_ensure_remember_schema(PDO $pdo): bool {
    static $ensured = false;
    if ($ensured) {
        return true;
    }
    try {
        $pdo->exec("
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $ensured = true;
        return true;
    } catch (Throwable $e) {
        error_log('[GODMINTON remember_tokens] ' . $e->getMessage());
        return false;
    }
}

function god_auth_issue_remember_token(PDO $pdo, array $user, int $days = GOD_REMEMBER_DAYS): void {
    if (!god_auth_ensure_remember_schema($pdo)) {
        return;
    }
    $userId = (int)($user['id'] ?? 0);
    if ($userId <= 0) {
        return;
    }

    try {
        // Dọn các token đã hết hạn và giới hạn tối đa 5 thiết bị ghi nhớ trên mỗi tài khoản
        $pdo->prepare('DELETE FROM remember_tokens WHERE expires_at <= NOW()')->execute();
        $cleanupStmt = $pdo->prepare('
            SELECT id FROM remember_tokens
            WHERE user_id = ?
            ORDER BY COALESCE(last_used_at, created_at) DESC
            LIMIT 100 OFFSET 4
        ');
        $cleanupStmt->execute([$userId]);
        $oldIds = $cleanupStmt->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($oldIds)) {
            $inClause = implode(',', array_map('intval', $oldIds));
            $pdo->exec("DELETE FROM remember_tokens WHERE id IN ($inClause)");
        }

        $selector = bin2hex(random_bytes(12));   // 24 ký tự hex
        $validator = bin2hex(random_bytes(32));  // 64 ký tự hex
        $validatorHash = hash('sha256', $validator);
        $sessionVersion = (int)($user['session_version'] ?? 0);
        $uaHash = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ttlSeconds = max(86400, $days * 86400);
        $expiresAt = time() + $ttlSeconds;
        $expiresSql = date('Y-m-d H:i:s', $expiresAt);

        $ins = $pdo->prepare('
            INSERT INTO remember_tokens (user_id, selector, validator_hash, session_version, user_agent_hash, ip_prefix, expires_at, last_used_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ');
        $ins->execute([$userId, $selector, $validatorHash, $sessionVersion, $uaHash, substr($ip, 0, 45), $expiresSql]);

        god_auth_set_remember_cookie($selector . ':' . $validator, $expiresAt);
        $_SESSION['remember_me'] = true;
    } catch (Throwable $e) {
        error_log('[GODMINTON remember_issue] ' . $e->getMessage());
    }
}

function god_auth_revoke_remember(?PDO $pdo = null, bool $allDevices = false): void {
    $raw = god_auth_get_remember_cookie();
    if ($pdo && god_auth_ensure_remember_schema($pdo)) {
        try {
            if ($allDevices && !empty($_SESSION['user_id'])) {
                $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([(int)$_SESSION['user_id']]);
            } elseif ($raw !== '' && preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $raw, $m)) {
                $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$m[1]]);
            }
        } catch (Throwable $e) {
            error_log('[GODMINTON remember_revoke] ' . $e->getMessage());
        }
    }
    god_auth_clear_remember_cookie();
}

function god_auth_try_remember(PDO $pdo): ?array {
    $raw = god_auth_get_remember_cookie();
    if ($raw === '' || !preg_match('/^([a-f0-9]{24}):([a-f0-9]{64})$/', $raw, $m)) {
        if ($raw !== '') {
            god_auth_clear_remember_cookie();
        }
        return null;
    }

    if (!god_auth_ensure_remember_schema($pdo)) {
        return null;
    }

    [$full, $selector, $validator] = $m;

    try {
        $stmt = $pdo->prepare('
            SELECT rt.id AS token_id, rt.user_id, rt.selector, rt.validator_hash,
                   rt.session_version AS token_session_version, rt.expires_at,
                   u.id, u.name, u.email, u.phone, u.role, u.avatar_url, u.created_at,
                   u.is_verified, u.session_version, u.account_status, u.deleted_at, u.owner_intent
            FROM remember_tokens rt
            JOIN users u ON u.id = rt.user_id
            WHERE rt.selector = ?
            LIMIT 1
        ');
        $stmt->execute([$selector]);
        $row = $stmt->fetch();

        if (!$row) {
            god_auth_clear_remember_cookie();
            return null;
        }

        $expiresTs = strtotime((string)$row['expires_at']);
        if ($expiresTs === false || $expiresTs <= time()) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([(int)$row['token_id']]);
            god_auth_clear_remember_cookie();
            return null;
        }

        // So sánh băm thời gian hằng số (Constant-time comparison)
        $givenHash = hash('sha256', $validator);
        if (!hash_equals((string)$row['validator_hash'], $givenHash)) {
            // Phát hiện selector hợp lệ nhưng validator sai -> nguy cơ đánh cắp/replay cookie -> thu hồi toàn bộ token của user!
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([(int)$row['user_id']]);
            god_auth_clear_remember_cookie();
            return null;
        }

        // Kiểm tra trạng thái tài khoản & session_version
        if (
            !$row['is_verified']
            || $row['account_status'] !== 'active'
            || $row['deleted_at'] !== null
            || (int)$row['token_session_version'] !== (int)$row['session_version']
        ) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([(int)$row['token_id']]);
            god_auth_clear_remember_cookie();
            return null;
        }

        // Xoay vòng validator (Token Rotation) ngay khi khôi phục phiên để mỗi validator chỉ dùng 1 lần
        $newValidator = bin2hex(random_bytes(32));
        $newHash = hash('sha256', $newValidator);
        $uaHash = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? ''));
        $pdo->prepare('UPDATE remember_tokens SET validator_hash = ?, user_agent_hash = ?, last_used_at = NOW() WHERE id = ?')
            ->execute([$newHash, $uaHash, (int)$row['token_id']]);

        god_auth_set_remember_cookie($selector . ':' . $newValidator, $expiresTs);

        // Thiết lập lại PHP Session an toàn
        $now = time();
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$row['id'];
        $_SESSION['auth_version'] = (int)$row['session_version'];
        $_SESSION['authenticated_at'] = $now;
        $_SESSION['last_activity'] = $now;
        $_SESSION['remember_me'] = true;
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        foreach (['name', 'email', 'phone', 'role'] as $key) {
            $_SESSION[$key] = $row[$key] ?? '';
        }

        return [
            'id'             => (int)$row['id'],
            'name'           => $row['name'],
            'email'          => $row['email'],
            'phone'          => $row['phone'],
            'role'           => $row['role'],
            'avatar_url'     => $row['avatar_url'],
            'created_at'     => $row['created_at'],
            'account_status' => $row['account_status'],
            'owner_intent'   => $row['owner_intent'],
        ];
    } catch (Throwable $e) {
        error_log('[GODMINTON remember_restore] ' . $e->getMessage());
        return null;
    }
}

function god_auth_start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        $isHttps = god_auth_is_https();
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid', '0');
        ini_set('session.cookie_httponly', '1');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    // Tự động khôi phục phiên từ cookie Ghi nhớ đăng nhập nếu trang đã nạp $pdo
    if (empty($_SESSION['user_id']) && god_auth_get_remember_cookie() !== '') {
        global $pdo;
        if (isset($pdo) && $pdo instanceof PDO) {
            god_auth_try_remember($pdo);
        }
    }
}

function god_auth_clear(bool $clearRememberCookie = true): void {
    if ($clearRememberCookie) {
        global $pdo;
        god_auth_revoke_remember((isset($pdo) && $pdo instanceof PDO) ? $pdo : null, false);
    }
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function god_auth_user(PDO $pdo): ?array {
    god_auth_start();
    $id = (int)($_SESSION['user_id'] ?? 0);
    if (!$id) {
        return god_auth_try_remember($pdo);
    }

    $s = $pdo->prepare('SELECT id,name,email,phone,role,avatar_url,created_at,is_verified,session_version,account_status,deleted_at,owner_intent FROM users WHERE id=?');
    $s->execute([$id]);
    $u = $s->fetch();
    $now = time();

    // Tài khoản bị khóa, xóa hoặc đã đổi mật khẩu (session_version thay đổi) -> hủy toàn bộ cả session lẫn cookie remember
    if (
        !$u
        || !$u['is_verified']
        || $u['account_status'] !== 'active'
        || $u['deleted_at'] !== null
        || (int)$u['session_version'] !== (int)($_SESSION['auth_version'] ?? 0)
    ) {
        god_auth_clear(true);
        return null;
    }

    // Kiểm tra timeout phiên làm việc (30 phút không tương tác hoặc 12 giờ phiên ngắn hạn)
    $idleExpired = ($now - (int)($_SESSION['last_activity'] ?? $now)) > 1800;
    $absExpired  = ($now - (int)($_SESSION['authenticated_at'] ?? $now)) > 43200;
    if ($idleExpired || $absExpired) {
        // Nếu người dùng có cookie Ghi nhớ đăng nhập hợp lệ -> xoay vòng khóa và gia hạn phiên tự động
        if (god_auth_get_remember_cookie() !== '') {
            $restored = god_auth_try_remember($pdo);
            if ($restored) {
                return $restored;
            }
        }
        god_auth_clear(true);
        return null;
    }

    $_SESSION['auth_version'] = (int)($_SESSION['auth_version'] ?? 0);
    $_SESSION['last_activity'] = $now;
    $_SESSION['authenticated_at'] ??= $now;
    foreach (['name', 'email', 'phone', 'role', 'avatar_url'] as $key) {
        $_SESSION[$key] = $u[$key] ?? '';
    }
    unset($u['session_version'], $u['is_verified'], $u['deleted_at']);
    $u['id'] = (int)$u['id'];
    return $u;
}

function god_auth_login(array $user, ?PDO $pdo = null, bool $remember = false): void {
    god_auth_clear(true);
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['auth_version'] = (int)$user['session_version'];
    $_SESSION['authenticated_at'] = $_SESSION['last_activity'] = time();
    $_SESSION['remember_me'] = $remember;
    foreach (['name', 'email', 'phone', 'role', 'avatar_url'] as $key) {
        $_SESSION[$key] = $user[$key] ?? '';
    }
    if ($remember && $pdo instanceof PDO) {
        god_auth_issue_remember_token($pdo, $user, GOD_REMEMBER_DAYS);
    }
}
