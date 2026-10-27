<?php
/**
 * api/cleanup-expired.php
 * Task 8 & SePay — Dọn các slot giữ chỗ đã hết hạn và xử lý huỷ các đơn thanh toán pending quá hạn.
 *
 * 3 cách dùng:
 *   A. Cron: php /path/to/GODMINTON/api/cleanup-expired.php
 *   B. HTTP: GET /api/cleanup-expired.php
 *   C. Inline: cleanup_expired_holds($pdo);
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/payment-service.php';

/**
 * Trả các slot 'holding' đã hết hạn về 'available' và huỷ các giao dịch/booking quá hạn.
 *
 * @return array{cleaned_slots: int, expired_payments: int}
 */
function cleanup_expired_holds(PDO $pdo): array
{
    $expiredPayments = payment_expire_overdue($pdo, 100);

    $stmt = $pdo->prepare(
        "UPDATE time_slots
            SET status = 'available', held_by = NULL, hold_expires_at = NULL
          WHERE status = 'holding' AND hold_expires_at IS NOT NULL AND hold_expires_at < NOW()"
    );
    $stmt->execute();
    $cleanedSlots = $stmt->rowCount();

    return [
        'cleaned_slots'    => $cleanedSlots,
        'expired_payments' => $expiredPayments,
    ];
}

$__isDirect = isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);

if ($__isDirect) {
    try {
        $result = cleanup_expired_holds($pdo);
        $msg = "Đã dọn {$result['cleaned_slots']} slot và huỷ {$result['expired_payments']} đơn quá hạn.";
        if (PHP_SAPI === 'cli') {
            echo '[' . date('Y-m-d H:i:s') . "] {$msg}\n";
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'success',
                'code'    => 200,
                'message' => $msg,
                'data'    => $result,
            ], JSON_UNESCAPED_UNICODE);
        }
    } catch (Throwable $e) {
        error_log('[cleanup-expired] ' . $e->getMessage());
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "Lỗi: " . $e->getMessage() . "\n");
            exit(1);
        }
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'code' => 500,
            'message' => 'Lỗi hệ thống, vui lòng thử lại.'], JSON_UNESCAPED_UNICODE);
    }
}
