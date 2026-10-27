<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth-session.php';
require_once __DIR__ . '/../../includes/web-push.php';
god_auth_start();
god_push_ensure_schema($pdo);
$user = god_auth_user($pdo);
$userId = $user ? (int)$user['id'] : null;
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function push_subscribe_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $count = 0;
        if ($userId !== null) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?');
            $stmt->execute([$userId]);
            $count = (int)$stmt->fetchColumn();
        }
        // Tự động quét nhắc lịch cho các đơn sắp đến giờ chơi
        $reminderSummary = god_push_dispatch_due_reminders($pdo, $userId);
        push_subscribe_out(200, ['status' => 'success', 'data' => [
            'subscribed' => $count > 0,
            'count' => $count,
            'csrf_token' => $_SESSION['csrf_token'],
            'reminders' => $reminderSummary,
        ]]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') push_subscribe_out(405, ['status' => 'error', 'message' => 'Chỉ hỗ trợ GET/POST.']);

    $csrf = $_SERVER['HTTP_X_GODMINTON_CSRF'] ?? '';
    if ($csrf !== '' && (!is_string($csrf) || !hash_equals($_SESSION['csrf_token'], $csrf))) {
        push_subscribe_out(403, ['status' => 'error', 'message' => 'Phiên thao tác đã hết hạn. Hãy tải lại trang.']);
    }
    $in = json_decode(file_get_contents('php://input'), true);
    if (!is_array($in)) push_subscribe_out(400, ['status' => 'error', 'message' => 'Dữ liệu gửi lên không hợp lệ.']);
    $action = (string)($in['action'] ?? 'subscribe');
    $endpoint = trim((string)($in['endpoint'] ?? ''));
    if (!god_push_endpoint_is_allowed($endpoint)) push_subscribe_out(422, ['status' => 'error', 'message' => 'Địa chỉ Push Service không hợp lệ.']);

    if ($action === 'unsubscribe') {
        if ($userId === null) {
            $stmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?');
            $stmt->execute([$endpoint]);
        } else {
            $stmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ? AND (user_id = ? OR user_id IS NULL)');
            $stmt->execute([$endpoint, $userId]);
        }
        push_subscribe_out(200, ['status' => 'success', 'data' => ['unsubscribed' => true]]);
    }
    if ($action !== 'subscribe') push_subscribe_out(422, ['status' => 'error', 'message' => 'Hành động không hợp lệ.']);

    $keys = is_array($in['keys'] ?? null) ? $in['keys'] : [];
    $p256dh = trim((string)($keys['p256dh'] ?? ''));
    $auth = trim((string)($keys['auth'] ?? ''));
    if (!preg_match('/^[A-Za-z0-9_=-]{40,140}$/', $p256dh) || !preg_match('/^[A-Za-z0-9_=-]{16,80}$/', $auth)) {
        push_subscribe_out(422, ['status' => 'error', 'message' => 'Khóa mã hóa subscription không hợp lệ.']);
    }
    $userAgent = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
    $stmt = $pdo->prepare('INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, user_agent)
        VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE
        user_id = COALESCE(VALUES(user_id), user_id),
        p256dh = VALUES(p256dh),
        auth = VALUES(auth),
        user_agent = VALUES(user_agent),
        updated_at = NOW()');
    $stmt->execute([$userId, $endpoint, $p256dh, $auth, $userAgent]);

    // Quét nhắc lịch ngay khi thiết bị vừa đồng bộ subscription
    $reminderSummary = god_push_dispatch_due_reminders($pdo, $userId);

    push_subscribe_out(200, [
        'status' => 'success',
        'message' => 'Đã bật nhận thông báo trên thiết bị này.',
        'data' => ['reminders' => $reminderSummary],
    ]);
} catch (Throwable $e) {
    error_log('[GODMINTON Push] Subscription error: ' . $e->getMessage());
    push_subscribe_out(500, ['status' => 'error', 'message' => 'Không thể cập nhật thiết lập thông báo.']);
}
