<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/web-push.php';

$dryRun = (PHP_SAPI === 'cli') && in_array('--dry-run', $argv ?? [], true);
$result = god_push_dispatch_due_reminders($pdo, null, $dryRun, 1800);

if (PHP_SAPI === 'cli') {
    if (!empty($result['skipped_lock'])) {
        fwrite(STDOUT, "Reminder worker already running; skipped.\n");
        exit(0);
    }
    fwrite(
        STDOUT,
        sprintf(
            "Push reminders sent: %d; failed: %d; upcoming bookings checked: %d%s\n",
            (int)$result['sent'],
            (int)$result['failed'],
            (int)$result['upcoming_count'],
            $dryRun ? ' (dry run)' : ''
        )
    );
    exit(((int)$result['failed']) > 0 ? 1 : 0);
}

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status'  => 'success',
    'message' => sprintf('Đã gửi %d thông báo nhắc lịch (kiểm tra %d đơn sắp tới).', (int)$result['sent'], (int)$result['upcoming_count']),
    'data'    => $result,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
