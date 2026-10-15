<?php
/**
 * api/auth/me.php
 * Trả thông tin người dùng đang đăng nhập từ PHP session / MySQL.
 * Người phụ trách: Huy
 */
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'code'    => 401,
        'message' => 'Chưa đăng nhập.',
        'data'    => ['user' => null]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $pdo->prepare('SELECT id, name, email, phone, role, avatar_url FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION = [];
    session_destroy();
    http_response_code(401);
    echo json_encode([
        'status'  => 'error',
        'code'    => 401,
        'message' => 'Phiên đăng nhập không còn hợp lệ.',
        'data'    => ['user' => null]
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$user['id'] = (int)$user['id'];
$_SESSION['name']  = $user['name'];
$_SESSION['email'] = $user['email'];
$_SESSION['phone'] = $user['phone'] ?? '';
$_SESSION['role']  = $user['role'];

echo json_encode([
    'status' => 'success',
    'code'   => 200,
    'data'   => ['user' => $user]
], JSON_UNESCAPED_UNICODE);
