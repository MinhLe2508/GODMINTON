<?php
/**
 * api/auth/register.php
 * Tạo tài khoản mới (bcrypt qua password_hash()).
 *
 * POST JSON: { name, email, phone, password, role? ("customer"|"owner") }
 * Thành công: 201 + data.user, đồng thời đăng nhập luôn (giống luồng giao diện cũ).
 *
 * Người phụ trách: Huy (Tuần 1)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/api-helpers.php';
api_start();
api_require_method('POST');

$in       = api_input();
$name     = trim((string)($in['name'] ?? ''));
$email    = strtolower(trim((string)($in['email'] ?? '')));
$phone    = preg_replace('/\s+/', '', (string)($in['phone'] ?? ''));
$password = (string)($in['password'] ?? '');
$role     = (string)($in['role'] ?? 'customer');

if ($name === '' || $email === '' || $phone === '' || $password === '') {
    api_fail('Vui lòng điền đầy đủ thông tin.', 422);
}
// Đếm ký tự UTF-8 bằng regex (không phụ thuộc extension mbstring)
if (preg_match_all('/./us', $name) > 150) {
    api_fail('Họ tên quá dài (tối đa 150 ký tự).', 422);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
    api_fail('Email không hợp lệ.', 422);
}
if (!preg_match('/^(0|\+84)\d{9}$/', $phone)) {
    api_fail('Số điện thoại không hợp lệ (ví dụ 0912345678).', 422);
}
if (strlen($password) < 6) {
    api_fail('Mật khẩu phải có tối thiểu 6 ký tự.', 422);
}
// BẢO MẬT: chỉ cho phép tự đăng ký customer/owner — KHÔNG BAO GIỜ cho client tự chọn admin.
if (!in_array($role, ['customer', 'owner'], true)) {
    $role = 'customer';
}

try {
    $stmt = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetchColumn()) {
        api_fail('Email này đã được đăng ký.', 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    // is_verified = 1: giao diện thông báo "tài khoản đã được kích hoạt", chưa có bước xác thực email.
    $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, phone, password_hash, role, is_verified)
         VALUES (?, ?, ?, ?, ?, 1)'
    );
    $stmt->execute([$name, $email, $phone, $hash, $role]);
    $id = (int)$pdo->lastInsertId();
} catch (PDOException $e) {
    if ($e->getCode() === '23000') { // trùng email do 2 request đồng thời (UNIQUE)
        api_fail('Email này đã được đăng ký.', 409);
    }
    error_log('[GODMINTON] register: ' . $e->getMessage());
    api_fail('Lỗi hệ thống, vui lòng thử lại sau.', 500);
}

$row = ['id' => $id, 'name' => $name, 'email' => $email, 'phone' => $phone, 'role' => $role];
api_login_session($row);
api_ok(['user' => $row], 'Đăng ký thành công!', 201);
