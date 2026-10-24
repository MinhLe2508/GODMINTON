<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth-session.php';
require_once __DIR__ . '/../../includes/web-push.php';
god_auth_start();
$user = god_auth_user($pdo);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function push_send_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') push_send_out(405, ['status' => 'error', 'message' => 'Chỉ hỗ trợ POST.']);
$csrf = $_SERVER['HTTP_X_GODMINTON_CSRF'] ?? '';
if (!is_string($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    push_send_out(403, ['status' => 'error', 'message' => 'Phiên thao tác đã hết hạn. Hãy tải lại trang.']);
}
$in = json_decode(file_get_contents('php://input'), true);
if (!is_array($in) || ($in['action'] ?? '') !== 'test') {
    push_send_out(422, ['status' => 'error', 'message' => 'Yêu cầu gửi thông báo không hợp lệ.']);
}
$endpoint = trim((string)($in['endpoint'] ?? ''));
if (!god_push_endpoint_is_allowed($endpoint)) push_send_out(422, ['status' => 'error', 'message' => 'Thiết bị nhận thông báo không hợp lệ.']);

try {
    if ($user) {
        $stmt = $pdo->prepare('SELECT endpoint,p256dh,auth FROM push_subscriptions WHERE endpoint=? AND user_id=? LIMIT 1');
        $stmt->execute([$endpoint, (int)$user['id']]);
    } else {
        $stmt = $pdo->prepare('SELECT endpoint,p256dh,auth FROM push_subscriptions WHERE endpoint=? AND user_id IS NULL LIMIT 1');
        $stmt->execute([$endpoint]);
    }
    $subscription = $stmt->fetch();
    if (!$subscription) push_send_out(404, ['status' => 'error', 'message' => 'Thiết bị chưa đăng ký thông báo. Hãy bật thông báo rồi thử lại.']);

    $payload = [
        'title' => 'GODMINTON · Nhắc lịch thi đấu (Demo)',
        'body' => 'Trận cầu của bạn tại Thảm A1 sắp bắt đầu sau 5 phút. Khởi động kỹ và sẵn sàng vào sân!',
        'icon' => 'assets/img/icons/icon-192.png',
        'badge' => 'assets/img/icons/badge-72.png',
        'data' => ['url' => 'history.php', 'timestamp' => time()],
    ];
    $result = god_push_dispatch($pdo, [$subscription], $payload);
    $result['payload'] = $payload;
    if ($result['accepted'] < 1 && empty($result['fallback_demo'])) {
        push_send_out(502, ['status' => 'error', 'message' => 'Push Service chưa nhận được thông báo. Hãy kiểm tra thiết bị đang online và thử lại.']);
    }
    push_send_out(200, ['status' => 'success', 'message' => 'Đã gửi thông báo demo cho thiết bị này.', 'data' => $result]);
} catch (Throwable $e) {
    error_log('[GODMINTON Push] Test notification error: ' . $e->getMessage());
    push_send_out(503, ['status' => 'error', 'message' => 'Không thể gửi thông báo. Kiểm tra cấu hình Web Push và thử lại.']);
}
