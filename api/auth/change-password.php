<?php
require_once __DIR__ . '/../../includes/auth-security.php';
try {
    $user = god_auth_user($pdo);
    if (!$user) {
        god_auth_out(401, 'Vui lòng đăng nhập.');
    }
    $in = god_auth_input();
    $old = god_auth_text($in, 'old_password');
    $next = god_auth_text($in, 'new_password');
    if (!god_auth_password_valid($next) || $old === $next) {
        god_auth_out(422, 'Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường, chữ số và ký tự đặc biệt. Mật khẩu mới phải khác mật khẩu cũ.');
    }
    if (!god_auth_limit($pdo, 'change-password', (string)$user['id'], 10, 900)) {
        god_auth_out(429, 'Vui lòng thử lại sau 15 phút.');
    }
    $pdo->beginTransaction();
    $s = $pdo->prepare('SELECT password_hash,session_version,account_status,deleted_at FROM users WHERE id=? FOR UPDATE');
    $s->execute([$user['id']]);
    $stored = $s->fetch();
    if ($stored['account_status'] !== 'active' || $stored['deleted_at'] !== null || (int)$stored['session_version'] !== (int)$_SESSION['auth_version'] || !password_verify($old, $stored['password_hash'])) {
        $pdo->rollBack();
        god_auth_out(403, 'Mật khẩu cũ hoặc phiên đăng nhập không hợp lệ.');
    }
    $newSessionVersion = (int)$stored['session_version'] + 1;
    $pdo->prepare('UPDATE users SET password_hash=?,pending_email=NULL,session_version=? WHERE id=?')->execute([god_auth_password_hash($next), $newSessionVersion, $user['id']]);
    $pdo->prepare('UPDATE auth_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$user['id']]);
    $pdo->commit();

    // Thu hồi toàn bộ cookie Ghi nhớ đăng nhập trên các thiết bị khác
    if (god_auth_ensure_remember_schema($pdo)) {
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id=?')->execute([$user['id']]);
    }
    $wasRemembered = !empty($_SESSION['remember_me']);
    $_SESSION['auth_version'] = $newSessionVersion;
    session_regenerate_id(true);
    if ($wasRemembered) {
        $user['session_version'] = $newSessionVersion;
        god_auth_issue_remember_token($pdo, $user, GOD_REMEMBER_DAYS);
    }
    if (god_auth_get_saved_credentials() !== null && !empty($user['email'])) {
        god_auth_set_saved_credentials((string)$user['email'], $next, GOD_REMEMBER_DAYS);
    }

    god_auth_out(200, 'Đổi mật khẩu thành công. Các phiên trên thiết bị khác đã được đăng xuất.');
} catch (Throwable $e) {
    god_auth_failure($e);
}
