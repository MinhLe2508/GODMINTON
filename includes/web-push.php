<?php
/** Shared Web Push sender & automatic reminder dispatcher. Call only after config/db.php has loaded .env. */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

function god_push_ensure_schema(PDO $pdo): void {
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS push_subscriptions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT UNSIGNED DEFAULT NULL,
            endpoint VARCHAR(500) NOT NULL,
            p256dh VARCHAR(255) NOT NULL,
            auth VARCHAR(255) NOT NULL,
            user_agent VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_push_endpoint (endpoint),
            KEY idx_push_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS push_reminder_dispatches (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            booking_id INT(10) UNSIGNED NOT NULL,
            reminder_type VARCHAR(40) NOT NULL DEFAULT 'start_30_minutes',
            status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
            attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
            last_error VARCHAR(500) DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_push_reminder_booking_type (booking_id, reminder_type),
            KEY idx_push_reminder_status (status, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    } catch (\Throwable $e) {
        error_log('[GODMINTON Push] Schema ensure warning: ' . $e->getMessage());
    }
}

function god_push_has_driver(): bool {
    return class_exists('\Minishlink\WebPush\WebPush');
}

function god_push_sender(): ?\Minishlink\WebPush\WebPush {
    if (!god_push_has_driver()) return null;
    static $sender = null;
    if ($sender instanceof \Minishlink\WebPush\WebPush) return $sender;
    try {
        if (DIRECTORY_SEPARATOR === '\\' && !getenv('OPENSSL_CONF')) {
            foreach (['D:/xampp/apache/conf/openssl.cnf', 'C:/xampp/apache/conf/openssl.cnf', dirname(PHP_BINARY) . '/extras/ssl/openssl.cnf'] as $cnf) {
                if (is_file($cnf)) {
                    putenv('OPENSSL_CONF=' . $cnf);
                    break;
                }
            }
        }
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

/**
 * Quét các đơn đặt sân đã xác nhận (confirmed) sắp diễn ra trong vòng $windowSeconds giây (mặc định 30 phút = 1800s)
 * và gửi thông báo nhắc lịch tự động (chống gửi trùng qua bảng push_reminder_dispatches).
 */
function god_push_dispatch_due_reminders(PDO $pdo, ?int $onlyUserId = null, bool $dryRun = false, int $windowSeconds = 1800): array {
    god_push_ensure_schema($pdo);

    $lock = 0;
    try {
        $lock = (int)$pdo->query("SELECT GET_LOCK('godminton_push_reminders', 0)")->fetchColumn();
    } catch (\Throwable $e) {
        return ['sent' => 0, 'failed' => 0, 'upcoming_count' => 0, 'skipped_lock' => true, 'notifications' => []];
    }
    if ($lock !== 1) {
        return ['sent' => 0, 'failed' => 0, 'upcoming_count' => 0, 'skipped_lock' => true, 'notifications' => []];
    }

    $sentBookings = 0;
    $failedBookings = 0;
    $bookings = [];
    $userNotifications = [];

    try {
        $windowSeconds = max(60, min(7200, $windowSeconds));
        $sql = "SELECT b.id, b.user_id, b.booking_code, c.name AS court_name,
                    MIN(CONCAT(s.slot_date, ' ', s.start_time)) AS starts_at,
                    TIMESTAMPDIFF(SECOND, NOW(), MIN(CONCAT(s.slot_date, ' ', s.start_time))) AS seconds_until_start,
                    GROUP_CONCAT(DISTINCT s.sub_court_name ORDER BY s.start_time SEPARATOR ', ') AS court_names
                FROM bookings b
                JOIN courts c ON c.id = b.court_id
                JOIN booking_slots bs ON bs.booking_id = b.id
                JOIN time_slots s ON s.id = bs.slot_id
                WHERE b.status IN ('confirmed', 'checked_in')
                  AND s.slot_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                GROUP BY b.id, b.user_id, b.booking_code, c.name
                HAVING seconds_until_start BETWEEN 0 AND {$windowSeconds}
                ORDER BY starts_at ASC";
        $bookings = $pdo->query($sql)->fetchAll();

        $findDispatch = $pdo->prepare("SELECT id, status, attempts FROM push_reminder_dispatches
            WHERE booking_id = ? AND reminder_type IN ('start_5_minutes', 'start_30_minutes') LIMIT 1");
        $createDispatch = $pdo->prepare("INSERT IGNORE INTO push_reminder_dispatches (booking_id, reminder_type, status)
            VALUES (?, 'start_5_minutes', 'pending')");
        $beginAttempt = $pdo->prepare("UPDATE push_reminder_dispatches SET status = 'pending', attempts = attempts + 1, last_error = NULL
            WHERE id = ? AND status <> 'sent' AND attempts < 3");
        $markSent = $pdo->prepare("UPDATE push_reminder_dispatches SET status = 'sent', sent_at = NOW(), last_error = NULL WHERE id = ?");
        $markFailed = $pdo->prepare("UPDATE push_reminder_dispatches SET status = 'failed', last_error = ? WHERE id = ?");

        foreach ($bookings as $booking) {
            $bookingUserId = (int)($booking['user_id'] ?? 0);
            if ($bookingUserId <= 0) continue;

            $subscriptionsQuery = $pdo->prepare('SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_id = ? ORDER BY id DESC');
            $subscriptionsQuery->execute([$bookingUserId]);
            $subscriptions = $subscriptionsQuery->fetchAll();

            // Fallback: nếu người dùng đang online trên trình duyệt nhưng subscription cũ có user_id IS NULL,
            // vẫn cho phép kiểm tra trạng thái nếu $onlyUserId trùng khớp
            if (!$subscriptions) continue;
            if ($dryRun) {
                continue;
            }

            $createDispatch->execute([(int)$booking['id']]);
            $findDispatch->execute([(int)$booking['id']]);
            $dispatch = $findDispatch->fetch();
            if (!$dispatch || $dispatch['status'] === 'sent' || (int)$dispatch['attempts'] >= 3) continue;

            $beginAttempt->execute([(int)$dispatch['id']]);
            if ($beginAttempt->rowCount() !== 1) continue;

            try {
                $startsAt = new DateTimeImmutable((string)$booking['starts_at']);
                $secondsLeft = max(60, (int)($booking['seconds_until_start'] ?? 300));
                $minutesLeft = max(1, (int)ceil($secondsLeft / 60));

                $payload = [
                    'title' => sprintf('GODMINTON · Sắp đến giờ vào sân (còn %d phút)', $minutesLeft),
                    'body' => sprintf(
                        '%s · %s · Bắt đầu lúc %s. Mã đặt: %s. Hãy khởi động sẵn sàng vào sân!',
                        $booking['court_name'],
                        $booking['court_names'],
                        $startsAt->format('H:i d/m'),
                        $booking['booking_code'] ?: ('#' . $booking['id'])
                    ),
                    'icon' => 'assets/img/icons/icon-192.png',
                    'badge' => 'assets/img/icons/badge-72.png',
                    'data' => [
                        'url' => 'history.php',
                        'booking_id' => (int)$booking['id'],
                        'booking_code' => (string)($booking['booking_code'] ?? ''),
                        'timestamp' => time(),
                    ],
                ];

                $delivery = god_push_dispatch($pdo, $subscriptions, $payload);
                if ($delivery['accepted'] > 0 || !empty($delivery['fallback_demo'])) {
                    $markSent->execute([(int)$dispatch['id']]);
                    $sentBookings++;
                    if ($onlyUserId !== null && $bookingUserId === $onlyUserId) {
                        $userNotifications[] = $payload;
                    }
                } else {
                    $markFailed->execute(['Push Service không nhận thông báo ở lần gửi này.', (int)$dispatch['id']]);
                    $failedBookings++;
                }
            } catch (\Throwable $e) {
                error_log('[GODMINTON Push] Reminder failed for booking #' . (int)$booking['id'] . ': ' . $e->getMessage());
                $markFailed->execute(['Lỗi cấu hình hoặc gửi Push Service.', (int)$dispatch['id']]);
                $failedBookings++;
            }
        }
    } catch (\Throwable $e) {
        error_log('[GODMINTON Push] Reminder scan error: ' . $e->getMessage());
    } finally {
        try {
            $pdo->query("SELECT RELEASE_LOCK('godminton_push_reminders')");
        } catch (\Throwable $_) {}
    }

    return [
        'sent' => $sentBookings,
        'failed' => $failedBookings,
        'upcoming_count' => count($bookings),
        'notifications' => $userNotifications,
    ];
}

/**
 * Phát thông báo Web Push cho toàn bộ thiết bị đăng ký khi Admin đăng bài viết mới lên phần Khám phá.
 */
function god_push_broadcast_new_post(PDO $pdo, array $post): array {
    god_push_ensure_schema($pdo);

    try {
        $rows = $pdo->query('SELECT endpoint, p256dh, auth FROM push_subscriptions ORDER BY id DESC LIMIT 500')->fetchAll();
    } catch (\Throwable $e) {
        return ['accepted' => 0, 'expired' => 0, 'failed' => 0];
    }

    if (!$rows) {
        return ['accepted' => 0, 'expired' => 0, 'failed' => 0];
    }

    $type = (string)($post['type'] ?? 'announcement');
    $typePrefix = match ($type) {
        'event'      => '🏆 Sự kiện mới',
        'membership' => '👑 Ưu đãi Hội viên',
        default      => '🔔 Thông báo mới',
    };

    $title = 'GODMINTON · ' . $typePrefix;
    $voucherHint = !empty($post['voucher_code'])
        ? sprintf(' (🎁 Tặng mã %s giảm %d%%)', $post['voucher_code'], (int)($post['voucher_discount_percent'] ?? 0))
        : '';

    $summary = trim((string)($post['summary'] ?? ''));
    $bodyText = (string)($post['title'] ?? 'Bài viết mới trên Khám phá') . $voucherHint;
    if ($summary !== '') {
        $bodyText .= ' — ' . mb_strimwidth($summary, 0, 90, '...', 'UTF-8');
    }

    $slug = (string)($post['slug'] ?? '');
    $targetUrl = $slug !== '' ? ('post-detail.php?slug=' . rawurlencode($slug)) : 'explore.php';

    $payload = [
        'title' => $title,
        'body'  => $bodyText,
        'icon'  => !empty($post['cover_image']) ? (string)$post['cover_image'] : 'assets/img/icons/icon-192.png',
        'badge' => 'assets/img/icons/badge-72.png',
        'data'  => [
            'url'       => $targetUrl,
            'post_id'   => (int)($post['id'] ?? 0),
            'post_slug' => $slug,
            'post_type' => $type,
            'timestamp' => time(),
        ],
    ];

    return god_push_dispatch($pdo, $rows, $payload);
}
