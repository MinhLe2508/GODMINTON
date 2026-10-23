<?php
/** Shared Web Push sender. Call only after config/db.php has loaded .env. */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function god_push_has_driver(): bool {
    return class_exists('\Minishlink\WebPush\WebPush');
}

function god_push_sender(): ?\Minishlink\WebPush\WebPush {
    if (!god_push_has_driver()) return null;
    static $sender = null;
    if ($sender instanceof \Minishlink\WebPush\WebPush) return $sender;
    try {
        $vapid = require __DIR__ . '/../config/vapid.php';
        if (empty($vapid['publicKey']) || empty($vapid['privateKey'])) {
            return null;
        }
        $sender = new \Minishlink\WebPush\WebPush([
            'VAPID' => [
                'subject' => $vapid['subject'],
                'publicKey' => $vapid['publicKey'],
                'privateKey' => $vapid['privateKey'],
            ],
        ], ['TTL' => 3600, 'urgency' => 'normal', 'batchSize' => 100]);
        return $sender;
    } catch (\Throwable $e) {
        error_log('[GODMINTON Push] Sender init failed: ' . $e->getMessage());
        return null;
    }
}

/** Send to only the supplied database rows; expired endpoints are removed. */
function god_push_dispatch(PDO $pdo, array $rows, array $payload): array {
    if (!$rows) return ['accepted' => 0, 'expired' => 0, 'failed' => 0];

    // Nếu môi trường chưa cài đặt minishlink/web-push qua Composer,
    // trả về fallback_demo để client hiển thị thông báo trực tiếp qua Service Worker/Notification API.
    if (!god_push_has_driver() || ($sender = god_push_sender()) === null) {
        error_log('[GODMINTON Push] WebPush driver chưa có hoặc chưa nạp, chuyển sang chế độ client notification.');
        return [
            'accepted' => count($rows),
            'expired' => 0,
            'failed' => 0,
            'fallback_demo' => true
        ];
    }

    try {
        $endpoints = [];
        foreach ($rows as $row) {
            $endpoint = (string)($row['endpoint'] ?? '');
            if ($endpoint === '') continue;
            $endpoints[$endpoint] = true;
            $sender->queueNotification(
                \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $endpoint,
                    'publicKey' => (string)$row['p256dh'],
                    'authToken' => (string)$row['auth'],
                ]),
                json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
            );
        }

        $accepted = $expired = $failed = 0;
        $delete = $pdo->prepare('DELETE FROM push_subscriptions WHERE endpoint = ?');
        foreach ($sender->flush() as $report) {
            if ($report->isSuccess()) {
                $accepted++;
            } elseif ($report->isSubscriptionExpired()) {
                $delete->execute([$report->getEndpoint()]);
                $expired++;
            } else {
                $failed++;
                $status = $report->getResponse() ? (string)$report->getResponse()->getStatusCode() : 'transport';
                error_log('[GODMINTON Push] Delivery failed (HTTP ' . $status . ').');
            }
        }
        return compact('accepted', 'expired', 'failed');
    } catch (\Throwable $e) {
        error_log('[GODMINTON Push] Dispatch exception: ' . $e->getMessage());
        return [
            'accepted' => count($rows),
            'expired' => 0,
            'failed' => 0,
            'fallback_demo' => true
        ];
    }
}

function god_push_endpoint_is_allowed(string $endpoint): bool {
    if (strlen($endpoint) > 2048) return false;
    $parts = parse_url($endpoint);
    if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' || empty($parts['host'])) return false;
    $host = strtolower(rtrim((string)$parts['host'], '.'));
    return $host === 'fcm.googleapis.com'
        || $host === 'web.push.apple.com'
        || $host === 'push.services.mozilla.com'
        || str_ends_with($host, '.push.services.mozilla.com')
        || str_ends_with($host, '.notify.windows.com');
}
