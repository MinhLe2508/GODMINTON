<?php
/**
 * api/push/subscribe.php
 * Lưu hoặc hủy push subscription của trình duyệt vào MySQL.
 * Hỗ trợ POST (lưu/hủy), GET (kiểm tra trạng thái)
 */
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$method = $_SERVER['REQUEST_METHOD'];

function push_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    // 1. GET: Kiểm tra xem user hiện tại đã có subscription chưa
    if ($method === 'GET') {
        if (!$userId) {
            push_out(200, ['status' => 'success', 'data' => ['subscribed' => false, 'count' => 0]]);
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM push_subscriptions WHERE user_id = ?');
        $stmt->execute([$userId]);
        $count = (int)$stmt->fetchColumn();
        push_out(200, ['status' => 'success', 'data' => ['subscribed' => $count > 0, 'count' => $count]]);
    }

    // 2. POST: Đăng ký (hoặc Hủy đăng ký) subscription
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $in = json_decode($raw, true) ?: $_POST;

        $action = $in['action'] ?? 'subscribe';
        $endpoint = trim($in['endpoint'] ?? '');

        if ($endpoint === '') {
            push_out(422, ['status' => 'error', 'message' => 'Thiếu thông tin endpoint.']);
        }

        // HỦY ĐĂNG KÝ
        if ($action === 'unsubscribe') {
            $stmt = $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?');
            $stmt->execute([$endpoint]);
            push_out(200, [
                'status'  => 'success',
                'message' => 'Đã hủy nhận thông báo đẩy thành công.',
                'data'    => ['unsubscribed' => true]
            ]);
        }

        // ĐĂNG KÝ MỚI / CẬP NHẬT
        $keys = $in['keys'] ?? [];
        $p256dh = trim($keys['p256dh'] ?? ($in['p256dh'] ?? ''));
        $auth   = trim($keys['auth']   ?? ($in['auth']   ?? ''));

        if ($p256dh === '' || $auth === '') {
            push_out(422, ['status' => 'error', 'message' => 'Thiếu khóa mã hóa p256dh hoặc auth secret.']);
        }

        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        // Lưu vào MySQL bằng prepared statement chống SQL Injection
        $stmt = $pdo->prepare("
            INSERT INTO push_subscriptions (user_id, endpoint, p256dh, auth, user_agent)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                user_id    = VALUES(user_id),
                p256dh     = VALUES(p256dh),
                auth       = VALUES(auth),
                user_agent = VALUES(user_agent),
                updated_at = NOW()
        ");
        $stmt->execute([$userId, $endpoint, $p256dh, $auth, $userAgent]);

        push_out(200, [
            'status'  => 'success',
            'message' => 'Đăng ký thông báo đẩy thành công!',
            'data'    => [
                'id'       => (int)$pdo->lastInsertId(),
                'user_id'  => $userId,
                'endpoint' => $endpoint
            ]
        ]);
    }

    push_out(405, ['status' => 'error', 'message' => 'Phương thức không được hỗ trợ.']);

} catch (PDOException $e) {
    error_log('[push/subscribe.php] DB Error: ' . $e->getMessage());
    push_out(500, ['status' => 'error', 'message' => 'Lỗi lưu trữ subscription vào CSDL.']);
} catch (Throwable $e) {
    error_log('[push/subscribe.php] Error: ' . $e->getMessage());
    push_out(500, ['status' => 'error', 'message' => 'Lỗi máy chủ khi xử lý thông báo đẩy.']);
}
