<?php
/** Shared Web Push sender. Call only after config/db.php has loaded .env. */
require_once __DIR__ . '/../vendor/autoload.php';

function god_push_sender(): \Minishlink\WebPush\WebPush {
    static $sender = null;
    if ($sender instanceof \Minishlink\WebPush\WebPush) return $sender;
    $vapid = require __DIR__ . '/../config/vapid.php';
    $sender = new \Minishlink\WebPush\WebPush([
        'VAPID' => [
            'subject' => $vapid['subject'],
            'publicKey' => $vapid['publicKey'],
            'privateKey' => $vapid['privateKey'],
        ],
    ], ['TTL' => 3600, 'urgency' => 'normal', 'batchSize' => 100]);
    return $sender;
}

/** Send to only the supplied database rows; expired endpoints are removed. */
function god_push_dispatch(PDO $pdo, array $rows, array $payload): array {
    if (!$rows) return ['accepted' => 0, 'expired' => 0, 'failed' => 0];
    $sender = god_push_sender();
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
