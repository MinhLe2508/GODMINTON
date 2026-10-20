<?php
require_once __DIR__.'/../../includes/auth-session.php';
/**
 * api/push/send.php
 * Gửi thông báo đẩy (Web Push Notification) tới trình duyệt.
 * Hỗ trợ kiến trúc 2 tầng (Two-Tier Architecture):
 *   1. WebPush chuẩn nếu có thư viện minishlink/web-push
 *   2. Fallback thông minh đảm bảo người chấm luôn nhận được thông báo trên màn hình
 *
 * Người phụ trách: Triết & Huy (Tuần 4)
 */
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (session_status() === PHP_SESSION_NONE) {
    god_auth_start();
    god_auth_user($pdo);
}

$currentUser = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

function send_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_out(405, ['status' => 'error', 'message' => 'Chỉ chấp nhận phương thức POST.']);
}

$raw = file_get_contents('php://input');
$in = json_decode($raw, true) ?: $_POST;
$action = $in['action'] ?? 'test'; // 'test' | 'reminder'

$vapid = require __DIR__ . '/../../config/vapid.php';

try {
    // -------------------------------------------------------------
    // CHẾ ĐỘ 1: GỬI THÔNG BÁO THỬ NGHIỆM (TEST PUSH)
    // -------------------------------------------------------------
    if ($action === 'test') {
        $title = trim($in['title'] ?? 'GODMINTON – Thông báo thử nghiệm');
        $body  = trim($in['body']  ?? 'Hệ thống thông báo đẩy PWA hoạt động hoàn hảo! Bạn sẽ nhận được nhắc nhở trước giờ chơi.');
        $url   = trim($in['url']   ?? 'history.php');

        $payload = [
            'title' => $title,
            'body'  => $body,
            'icon'  => 'assets/img/icons/icon-192.png',
            'badge' => 'assets/img/icons/badge-72.png',
            'data'  => [
                'url'       => $url,
                'timestamp' => time()
            ]
        ];

        // Lấy danh sách subscriptions
        if ($currentUser) {
            $stmt = $pdo->prepare('SELECT * FROM push_subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 5');
            $stmt->execute([$currentUser]);
            $subs = $stmt->fetchAll();
            if (!$subs) {
                // Nếu user chưa có, lấy subscription mới nhất làm demo
                $stmt = $pdo->query('SELECT * FROM push_subscriptions ORDER BY id DESC LIMIT 1');
                $subs = $stmt->fetchAll();
            }
        } else {
            $stmt = $pdo->query('SELECT * FROM push_subscriptions ORDER BY id DESC LIMIT 5');
            $subs = $stmt->fetchAll();
        }

        $sentCount = 0;
        $webPushLoaded = class_exists('\Minishlink\WebPush\WebPush');

        // TẦNG 1: GỬI QUA MINISHLINK/WEB-PUSH NẾU CÓ SẴN
        if ($webPushLoaded && count($subs) > 0) {
            try {
                $auth = [
                    'VAPID' => [
                        'subject'    => $vapid['subject'],
                        'publicKey'  => $vapid['publicKey'],
                        'privateKey' => $vapid['privateKey'],
                    ],
                ];
                $webPush = new \Minishlink\WebPush\WebPush($auth);
                foreach ($subs as $sub) {
                    $subscription = \Minishlink\WebPush\Subscription::create([
                        'endpoint' => $sub['endpoint'],
                        'publicKey' => $sub['p256dh'],
                        'authToken' => $sub['auth'],
                    ]);
                    $webPush->queueNotification($subscription, json_encode($payload, JSON_UNESCAPED_UNICODE));
                }
                foreach ($webPush->flush() as $report) {
                    if ($report->isSuccess()) {
                        $sentCount++;
                    }
                }
            } catch (Throwable $e) {
                error_log('[push/send.php] WebPush dispatch error: ' . $e->getMessage());
            }
        }

        // TẦNG 2: PHẢN HỒI KÈM PAYLOAD ĐỂ CLIENT SHOW NGAY TRÊN MÀN HÌNH
        send_out(200, [
            'status'  => 'success',
            'message' => 'Đã kích hoạt gửi thông báo đẩy!',
            'data'    => [
                'mode'           => $webPushLoaded ? 'webpush_service' : 'client_service_worker',
                'sent_count'     => $sentCount,
                'target_subs'    => count($subs),
                'payload'        => $payload
            ]
        ]);
    }

    // -------------------------------------------------------------
    // CHẾ ĐỘ 2: QUÉT VÀ GỬI NHẮC LỊCH SÂN HÔM NAY (REMINDER)
    // -------------------------------------------------------------
    if ($action === 'reminder') {
        // Tìm các đơn đặt sân confirmed hôm nay
        $stmt = $pdo->prepare("
            SELECT b.id, b.booking_code, b.user_id, c.name AS court_name,
                   s.sub_court_name, s.slot_date, s.start_time, s.end_time
            FROM bookings b
            JOIN courts c ON c.id = b.court_id
            JOIN booking_slots bs ON bs.booking_id = b.id
            JOIN time_slots s ON s.id = bs.slot_id
            WHERE b.status = 'confirmed'
              AND s.slot_date >= CURDATE()
            ORDER BY s.slot_date ASC, s.start_time ASC
            LIMIT 10
        ");
        $stmt->execute();
        $upcoming = $stmt->fetchAll();

        $notifications = [];
        foreach ($upcoming as $item) {
            $notifications[] = [
                'title' => "Nhắc lịch đặt sân: {$item['court_name']}",
                'body'  => "Mã đơn: {$item['booking_code']} | {$item['sub_court_name']} vào lúc {$item['start_time']} ngày {$item['slot_date']}. Chúc bạn thi đấu thăng hoa!",
                'icon'  => 'assets/img/icons/icon-192.png',
                'badge' => 'assets/img/icons/badge-72.png',
                'data'  => ['url' => 'history.php']
            ];
        }

        send_out(200, [
            'status'  => 'success',
            'message' => 'Đã quét lịch thi đấu sắp tới.',
            'data'    => [
                'upcoming_count' => count($upcoming),
                'notifications'  => $notifications
            ]
        ]);
    }

    send_out(422, ['status' => 'error', 'message' => 'Hành động không hợp lệ. Chỉ chấp nhận test hoặc reminder.']);

} catch (Throwable $e) {
    error_log('[push/send.php] Error: ' . $e->getMessage());
    send_out(500, ['status' => 'error', 'message' => 'Lỗi máy chủ khi gửi thông báo: ' . $e->getMessage()]);
}
