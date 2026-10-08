<?php
/**
 * api/auth/change-password.php
 * Đổi mật khẩu, yêu cầu xác thực mật khẩu cũ.
 * Nhận JSON: { old_password, new_password }
 */
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    out(405, ['status' => 'error', 'message' => 'Chỉ chấp nhận phương thức POST.']);
}

session_start();
if (empty($_SESSION['user_id'])) {
    out(401, ['status' => 'error', 'message' => 'Bạn chưa đăng nhập.']);
}

$in  = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$old = $in['old_password'] ?? '';
$new = $in['new_password'] ?? '';

if ($old === '' || $new === '') {
    out(422, ['status' => 'error', 'message' => 'Vui lòng nhập đủ mật khẩu cũ và mới.']);
}
if (strlen($new) < 6) {
    out(422, ['status' => 'error', 'message' => 'Mật khẩu mới tối thiểu 6 ký tự.']);
}

$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$row = $stmt->fetch();

if (!$row || !password_verify($old, $row['password_hash'])) {
    out(403, ['status' => 'error', 'message' => 'Mật khẩu cũ không đúng.']);
}

$upd = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
$upd->execute([password_hash($new, PASSWORD_BCRYPT), $_SESSION['user_id']]);

out(200, ['status' => 'success', 'message' => 'Đổi mật khẩu thành công.']);
