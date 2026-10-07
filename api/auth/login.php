<?php
/**
 * api/auth/login.php
 * Xác thực đăng nhập, tạo session.
 *
 * POST JSON: { email, password, remember? (bool) }
 * Thành công: data.user = { id, name, email, phone, role }
 *
 * Người phụ trách: Huy (Tuần 1)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/api-helpers.php';

$in = api_input();
api_start(!empty($in['remember']));
api_require_method('POST');

$email    = strtolower(trim((string)($in['email'] ?? '')));
$password = (string)($in['password'] ?? '');

if ($email === '' || $password === '') {
    api_fail('Vui lòng nhập email và mật khẩu.', 422);
}

try {
    $stmt = $pdo->prepare('SELECT id, name, email, phone, password_hash, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $row = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[GODMINTON] login: ' . $e->getMessage());
    api_fail('Lỗi hệ thống, vui lòng thử lại sau.', 500);
}

// Cùng một thông báo cho "sai email" và "sai mật khẩu" để không lộ email nào đã tồn tại.
if (!$row || !password_verify($password, $row['password_hash'])) {
    api_fail('Email hoặc mật khẩu không chính xác.', 401);
}

// Nâng cost nếu hash cũ yếu hơn mặc định hiện tại
if (password_needs_rehash($row['password_hash'], PASSWORD_BCRYPT)) {
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
        ->execute([password_hash($password, PASSWORD_BCRYPT), $row['id']]);
}

api_login_session($row);
unset($row['password_hash']);
$row['id'] = (int)$row['id'];
api_ok(['user' => $row], 'Đăng nhập thành công!');
