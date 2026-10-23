<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/web-push.php';

$lock = $pdo->query("SELECT GET_LOCK('godminton_push_reminders', 0)")->fetchColumn();
if ((int)$lock !== 1) {
    fwrite(STDOUT, "Reminder worker already running; skipped.\n");
    exit(0);
}

$sentBookings = 0;
$failedBookings = 0;
$dryRun = in_array('--dry-run', $argv, true);
try {
    $bookings = $pdo->query("SELECT b.id, b.user_id, b.booking_code, c.name AS court_name,
            MIN(CONCAT(s.slot_date, ' ', s.start_time)) AS starts_at,
            GROUP_CONCAT(DISTINCT s.sub_court_name ORDER BY s.start_time SEPARATOR ', ') AS court_names
        FROM bookings b
        JOIN courts c ON c.id = b.court_id
        JOIN booking_slots bs ON bs.booking_id = b.id
        JOIN time_slots s ON s.id = bs.slot_id
        WHERE b.status = 'confirmed'
        GROUP BY b.id, b.user_id, b.booking_code, c.name
        HAVING TIMESTAMPDIFF(SECOND, NOW(), MIN(CONCAT(s.slot_date, ' ', s.start_time))) BETWEEN 0 AND 2100
        ORDER BY starts_at ASC")->fetchAll();

    $findDispatch = $pdo->prepare("SELECT id,status,attempts FROM push_reminder_dispatches
        WHERE booking_id=? AND reminder_type='start_30_minutes' LIMIT 1");
    $createDispatch = $pdo->prepare("INSERT IGNORE INTO push_reminder_dispatches (booking_id,reminder_type,status)
        VALUES (?,'start_30_minutes','pending')");
    $beginAttempt = $pdo->prepare("UPDATE push_reminder_dispatches SET status='pending',attempts=attempts+1,last_error=NULL
        WHERE id=? AND status<>'sent' AND attempts<3");
    $markSent = $pdo->prepare("UPDATE push_reminder_dispatches SET status='sent',sent_at=NOW(),last_error=NULL WHERE id=?");
    $markFailed = $pdo->prepare("UPDATE push_reminder_dispatches SET status='failed',last_error=? WHERE id=?");

    foreach ($bookings as $booking) {
        $subscriptionsQuery = $pdo->prepare('SELECT endpoint,p256dh,auth FROM push_subscriptions WHERE user_id=? ORDER BY id DESC');
        $subscriptionsQuery->execute([(int)$booking['user_id']]);
        $subscriptions = $subscriptionsQuery->fetchAll();
        if (!$subscriptions) continue;
        if ($dryRun) {
            fwrite(STDOUT, sprintf("Would send reminder for booking #%d to %d device(s).\n", (int)$booking['id'], count($subscriptions)));
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
            $payload = [
                'title' => 'GODMINTON · Sắp đến giờ đặt sân',
                'body' => sprintf('%s · %s · %s. Mã đặt: %s',
                    $booking['court_name'], $booking['court_names'], $startsAt->format('H:i d/m'), $booking['booking_code'] ?: ('#' . $booking['id'])),
                'icon' => 'assets/img/icons/icon-192.png',
                'badge' => 'assets/img/icons/badge-72.png',
                'data' => ['url' => 'history.php', 'timestamp' => time()],
            ];
            $delivery = god_push_dispatch($pdo, $subscriptions, $payload);
            if ($delivery['accepted'] > 0) {
                $markSent->execute([(int)$dispatch['id']]);
                $sentBookings++;
            } else {
                $markFailed->execute(['Push Service không nhận thông báo ở lần gửi này.', (int)$dispatch['id']]);
                $failedBookings++;
            }
        } catch (Throwable $e) {
            error_log('[GODMINTON Push] Reminder failed for booking #' . (int)$booking['id'] . ': ' . $e->getMessage());
            $markFailed->execute(['Lỗi cấu hình hoặc gửi Push Service.', (int)$dispatch['id']]);
            $failedBookings++;
        }
    }
} finally {
    $pdo->query("SELECT RELEASE_LOCK('godminton_push_reminders')");
}

fwrite(STDOUT, sprintf("Push reminders sent: %d; failed: %d; upcoming bookings checked: %d%s\n", $sentBookings, $failedBookings, count($bookings ?? []), $dryRun ? ' (dry run)' : ''));
exit($failedBookings > 0 ? 1 : 0);
