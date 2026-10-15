<?php
/**
 * api/auth/login.php
 * Xác thực đăng nhập, tạo session.
 * Nhận JSON: { email, password }
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

$in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$email    = strtolower(trim($in['email'] ?? ''));
$password = $in['password'] ?? '';

if ($email === '' || $password === '') {
    out(422, ['status' => 'error', 'message' => 'Vui lòng nhập email và mật khẩu.']);
}

$stmt = $pdo->prepare('SELECT id, name, email, phone, password_hash, role FROM users WHERE email = ?');
$stmt->execute([$email]);
$u = $stmt->fetch();

// Cùng một thông báo cho "sai email" và "sai mật khẩu" để không lộ email nào tồn tại
if (!$u || !password_verify($password, $u['password_hash'])) {
    out(401, ['status' => 'error', 'message' => 'Email hoặc mật khẩu không chính xác.']);
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
session_regenerate_id(true);
$_SESSION['user_id'] = (int)$u['id'];
$_SESSION['role']    = $u['role'];
$_SESSION['name']    = $u['name'];
$_SESSION['email']   = $u['email'];
$_SESSION['phone']   = $u['phone'] ?? '';

unset($u['password_hash']);
$u['id'] = (int)$u['id'];
out(200, ['status' => 'success', 'data' => ['user' => $u]]);
