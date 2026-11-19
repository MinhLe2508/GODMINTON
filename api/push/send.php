<?php

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth-session.php';
require_once __DIR__ . '/../../includes/web-push.php';
god_auth_start();
god_push_ensure_schema($pdo);
$user = god_auth_user($pdo);
$userId = $user ? (int) $user['id'] : null;
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function push_send_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
    push_send_out(405, ['status' => 'error', 'message' => 'Chỉ hỗ trợ POST.']);
$csrf = $_SERVER['HTTP_X_GODMINTON_CSRF'] ?? '';
if (!is_string($csrf) || $csrf === '' || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    push_send_out(403, ['status' => 'error', 'message' => 'Phiên thao tác đã hết hạn. Hãy tải lại trang.']);
}

$in = json_decode(file_get_contents('php://input'), true);
$action = is_array($in) ? (string) ($in['action'] ?? '') : '';
if (!in_array($action, ['test', 'reminder'], true)) {
    push_send_out(422, ['status' => 'error', 'message' => 'Yêu cầu gửi thông báo không hợp lệ.']);
}

$endpoint = trim((string) ($in['endpoint'] ?? ''));

try {
    // Nếu thiết bị đã đăng ký trước khi đăng nhập (user_id IS NULL), tự động gắn vào user hiện tại
    if ($endpoint !== '' && $userId !== null && god_push_endpoint_is_allowed($endpoint)) {
        $bindStmt = $pdo->prepare('UPDATE push_subscriptions SET user_id = ?, updated_at = NOW() WHERE endpoint = ? AND (user_id IS NULL OR user_id <> ?)');
        $bindStmt->execute([$userId, $endpoint, $userId]);
    }

    if ($action === 'reminder') {
        $summary = god_push_dispatch_due_reminders($pdo, $userId);
        push_send_out(200, [
            'status' => 'success',
            'message' => 'Đã quét nhắc lịch thi đấu tự động.',
            'data' => $summary,
        ]);
    }

    // action === 'test'
    $subscription = null;
    if ($endpoint !== '') {
        if (!god_push_endpoint_is_allowed($endpoint)) {
            push_send_out(422, ['status' => 'error', 'message' => 'Thiết bị nhận thông báo không hợp lệ.']);
        }

        $stmt = $pdo->prepare('SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE endpoint = ? LIMIT 1');
        $stmt->execute([$endpoint]);
        $subscription = $stmt->fetch();
    } elseif ($userId !== null) {
        $stmt = $pdo->prepare('SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$userId]);
        $subscription = $stmt->fetch();
    } else {
        $stmt = $pdo->query('SELECT endpoint, p256dh, auth FROM push_subscriptions ORDER BY id DESC LIMIT 1');
        $subscription = $stmt->fetch();
    }

    if (!$subscription) {
        push_send_out(404, ['status' => 'error', 'message' => 'Thiết bị chưa đăng ký thông báo. Hãy bật thông báo rồi thử lại.']);
    }

    // Kiểm tra xem người dùng hiện tại có đơn đặt sân nào sắp diễn ra trong hôm nay không để cá nhân hóa nội dung nhắc lịch
    $customTitle = trim((string) ($in['title'] ?? ''));
    $customBody = trim((string) ($in['body'] ?? ''));
    $upcomingBooking = null;
    if ($userId !== null) {
        $upStmt = $pdo->prepare(
            "SELECT b.id, b.booking_code, c.name AS court_name,
                    MIN(CONCAT(s.slot_date, ' ', s.start_time)) AS starts_at,
                    TIMESTAMPDIFF(MINUTE, NOW(), MIN(CONCAT(s.slot_date, ' ', s.start_time))) AS minutes_left,
                    GROUP_CONCAT(DISTINCT s.sub_court_name ORDER BY s.start_time SEPARATOR ', ') AS court_names
               FROM bookings b
               JOIN courts c ON c.id = b.court_id
               JOIN booking_slots bs ON bs.booking_id = b.id
               JOIN time_slots s ON s.id = bs.slot_id
              WHERE b.user_id = ?
                AND b.status IN ('confirmed', 'checked_in')
                AND CONCAT(s.slot_date, ' ', s.start_time) >= NOW()
              GROUP BY b.id, b.booking_code, c.name
              ORDER BY starts_at ASC
              LIMIT 1"
        );
        $upStmt->execute([$userId]);
        $upcomingBooking = $upStmt->fetch() ?: null;
    }

    if ($upcomingBooking) {
        $startsAt = new DateTimeImmutable((string) $upcomingBooking['starts_at']);
        $minsLeft = max(1, (int) $upcomingBooking['minutes_left']);
        $timeHint = $minsLeft <= 60 ? "còn {$minsLeft} phút" : ('lúc ' . $startsAt->format('H:i d/m'));
        $payload = [
            'title' => "GODMINTON · Nhắc lịch thi đấu ({$timeHint})",
            'body' => sprintf(
                '%s · %s · Bắt đầu lúc %s. Mã đặt: %s. Hãy khởi động kỹ và sẵn sàng vào sân!',
                $upcomingBooking['court_name'],
                $upcomingBooking['court_names'],
                $startsAt->format('H:i d/m/Y'),
                $upcomingBooking['booking_code'] ?: ('#' . $upcomingBooking['id'])
            ),
            'icon' => 'assets/img/icons/icon-192.png',
            'badge' => 'assets/img/icons/badge-72.png',
            'data' => ['url' => 'history.php', 'timestamp' => time()],
        ];
    } else {
        $payload = [
            'title' => $customTitle !== '' ? $customTitle : 'GODMINTON · Nhắc lịch thi đấu (Demo)',
            'body' => $customBody !== '' ? $customBody : 'Trận cầu của bạn tại Thảm A1 sắp bắt đầu sau 5 phút. Khởi động kỹ và sẵn sàng vào sân!',
            'icon' => 'assets/img/icons/icon-192.png',
            'badge' => 'assets/img/icons/badge-72.png',
            'data' => ['url' => 'history.php', 'timestamp' => time()],
        ];
    }

    $result = god_push_dispatch($pdo, [$subscription], $payload);
    $result['payload'] = $payload;
    push_send_out(200, ['status' => 'success', 'message' => 'Đã gửi thông báo nhắc lịch cho thiết bị này.', 'data' => $result]);
} catch (Throwable $e) {
    error_log('[GODMINTON Push] Test notification error: ' . $e->getMessage());
    push_send_out(503, ['status' => 'error', 'message' => 'Không thể gửi thông báo. Kiểm tra cấu hình Web Push và thử lại.']);
}
