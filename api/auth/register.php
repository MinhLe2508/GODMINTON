<?php
/**
 * api/auth/register.php
 * Tạo tài khoản mới (bcrypt qua password_hash()).
 * Nhận JSON: { name, email, phone, password, role }  (role: customer | owner)
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

$name     = trim($in['name'] ?? '');
$email    = strtolower(trim($in['email'] ?? ''));
$phone    = trim($in['phone'] ?? '');
$password = $in['password'] ?? '';
$role     = $in['role'] ?? 'customer';

if ($name === '' || $email === '' || $phone === '' || $password === '') {
    out(422, ['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin.']);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    out(422, ['status' => 'error', 'message' => 'Email không hợp lệ.']);
}
if (!preg_match('/^[0-9 +]{9,15}$/', $phone)) {
    out(422, ['status' => 'error', 'message' => 'Số điện thoại không hợp lệ.']);
}
if (strlen($password) < 6) {
    out(422, ['status' => 'error', 'message' => 'Mật khẩu tối thiểu 6 ký tự.']);
}
// Không bao giờ cho client tự chọn 'admin'
if (!in_array($role, ['customer', 'owner'], true)) {
    $role = 'customer';
}

try {
    $chk = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $chk->execute([$email]);
    if ($chk->fetch()) {
        out(409, ['status' => 'error', 'message' => 'Email này đã được đăng ký.']);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = $pdo->prepare(
        'INSERT INTO users (name, email, phone, password_hash, role, is_verified)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $ins->execute([$name, $email, $phone, $hash, $role]);
    $id = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    out(500, ['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại.']);
}

// Đăng ký xong thì đăng nhập luôn
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
session_regenerate_id(true);
$_SESSION['user_id'] = $id;
$_SESSION['role']    = $role;
$_SESSION['name']    = $name;
$_SESSION['email']   = $email;
$_SESSION['phone']   = $phone;

out(201, ['status' => 'success', 'data' => ['user' => [
    'id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone, 'role' => $role,
]]]);
