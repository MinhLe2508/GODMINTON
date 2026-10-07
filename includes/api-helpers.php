<?php
/**
 * includes/api-helpers.php — Hàm dùng chung cho các API trả JSON.
 *
 * Người phụ trách: Huy (Tuần 1). Mọi API nên dùng để thống nhất định dạng:
 *   thành công: {"status":"success","message":"...","data":{...}}
 *   thất bại  : {"status":"error","message":"..."}   (kèm HTTP status phù hợp)
 *
 * Cách dùng:
 *   require_once __DIR__ . '/../../includes/api-helpers.php';
 *   api_start();                       // header JSON + bật session
 *   api_require_method('POST');
 *   $in = api_input();                 // đọc JSON body hoặc form
 *   $user = api_require_login();       // 401 nếu chưa đăng nhập
 *   $user = api_require_role(['admin','owner']); // 403 nếu sai vai trò
 *   api_ok($data, 'Thông báo');  /  api_fail('Lỗi gì đó', 422);
 */

function api_start(bool $remember = false): void
{
    header('Content-Type: application/json; charset=utf-8');
    api_session_start($remember);
}

function api_session_start(bool $remember = false): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    session_set_cookie_params([
        'lifetime' => $remember ? 60 * 60 * 24 * 30 : 0, // 30 ngày nếu "Ghi nhớ đăng nhập"
        'path'     => '/',
        'httponly' => true,                               // JS không đọc được cookie session
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function api_ok($data = null, string $message = '', int $code = 200): void
{
    http_response_code($code);
    $out = ['status' => 'success'];
    if ($message !== '') $out['message'] = $message;
    if ($data !== null)  $out['data']    = $data;
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

function api_fail(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['status' => 'error', 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function api_require_method(string $method): void
{
    if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
        header('Allow: ' . strtoupper($method));
        api_fail('Phương thức không được hỗ trợ (cần ' . strtoupper($method) . ').', 405);
    }
}

/** Đọc body JSON (fetch với Content-Type: application/json) hoặc form-data thường. */
function api_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw !== '' && $raw !== false) {
        $json = json_decode($raw, true);
        if (is_array($json)) {
            return $json;
        }
    }
    return $_POST;
}

/** Thông tin user hiện tại từ session (không có mật khẩu), hoặc null nếu chưa đăng nhập. */
function api_current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'    => (int) $_SESSION['user_id'],
        'name'  => $_SESSION['name']  ?? '',
        'email' => $_SESSION['email'] ?? '',
        'phone' => $_SESSION['phone'] ?? '',
        'role'  => $_SESSION['role']  ?? 'customer',
    ];
}

function api_require_login(): array
{
    $user = api_current_user();
    if (!$user) {
        api_fail('Bạn cần đăng nhập để thực hiện thao tác này.', 401);
    }
    return $user;
}

function api_require_role(array $roles): array
{
    $user = api_require_login();
    if (!in_array($user['role'], $roles, true)) {
        api_fail('Bạn không có quyền thực hiện thao tác này.', 403);
    }
    return $user;
}

/** Ghi thông tin đăng nhập vào session (gọi sau khi xác thực thành công). */
function api_login_session(array $row): void
{
    session_regenerate_id(true); // chống session fixation
    $_SESSION['user_id'] = (int) $row['id'];
    $_SESSION['name']    = $row['name'];
    $_SESSION['email']   = $row['email'];
    $_SESSION['phone']   = $row['phone'] ?? '';
    $_SESSION['role']    = $row['role'];
}
