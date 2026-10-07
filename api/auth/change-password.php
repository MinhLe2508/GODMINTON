<?php
/**
 * api/auth/change-password.php
 * Đổi mật khẩu, yêu cầu xác thực mật khẩu cũ.
 *
 * POST JSON: { old_password, new_password }  — phải đang đăng nhập.
 *
 * Người phụ trách: Huy (Tuần 1)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/api-helpers.php';
api_start();
api_require_method('POST');
$user = api_require_login();

$in  = api_input();
$old = (string)($in['old_password'] ?? '');
$new = (string)($in['new_password'] ?? '');

if ($old === '' || $new === '') {
    api_fail('Vui lòng nhập mật khẩu cũ và mật khẩu mới.', 422);
}
if (strlen($new) < 6) {
    api_fail('Mật khẩu mới phải có tối thiểu 6 ký tự.', 422);
}
if ($old === $new) {
    api_fail('Mật khẩu mới phải khác mật khẩu cũ.', 422);
}

try {
    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $hash = $stmt->fetchColumn();

    if (!$hash || !password_verify($old, $hash)) {
        api_fail('Mật khẩu cũ không chính xác.', 403);
    }

    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($new, PASSWORD_BCRYPT), $user['id']]);
} catch (PDOException $e) {
    error_log('[GODMINTON] change-password: ' . $e->getMessage());
    api_fail('Lỗi hệ thống, vui lòng thử lại sau.', 500);
}

session_regenerate_id(true);
api_ok(null, 'Đổi mật khẩu thành công.');
