<?php
/**
 * api/cleanup-expired.php
 * Task 8 — Dọn các slot giữ chỗ đã hết hạn: status='holding' AND hold_expires_at < NOW()
 * → trả về 'available' (xóa held_by, hold_expires_at).
 *
 * Người phụ trách: Huy (Tuần 2)
 *
 * 3 cách dùng (cùng một hàm, không trùng lặp code):
 *   A. Cron (khuyến nghị, chạy mỗi phút):
 *        * * * * * php /đường/dẫn/GODMINTON/api/cleanup-expired.php
 *      Windows/XAMPP: dùng Task Scheduler chạy  C:\xampp\php\php.exe  <đường dẫn file này>
 *   B. Gọi qua HTTP:  GET /api/cleanup-expired.php  → trả JSON số slot đã dọn.
 *   C. Inline (Minh dùng trong api/slots.php, trước câu SELECT):
 *        require_once __DIR__ . '/cleanup-expired.php';
 *        cleanup_expired_holds($pdo);
 *      Khi được require từ file khác, file này CHỈ định nghĩa hàm, không in gì ra.
 *
 * An toàn khi chạy song song với hold.php/confirm.php: UPDATE chỉ đụng các dòng
 * 'holding' đã quá hạn; InnoDB khoá từng dòng nên không ảnh hưởng slot đang được giữ hợp lệ.
 */
require_once __DIR__ . '/../config/db.php';

/**
 * Trả các slot 'holding' đã hết hạn về 'available'.
 *
 * @return int số slot đã dọn
 */
function cleanup_expired_holds(PDO $pdo): int
{
    $stmt = $pdo->prepare(
        "UPDATE time_slots
            SET status = 'available', held_by = NULL, hold_expires_at = NULL
          WHERE status = 'holding' AND hold_expires_at < NOW()"
    );
    $stmt->execute();
    return $stmt->rowCount();
}

// Chỉ chạy "chế độ script" khi file này được gọi trực tiếp (cron / trình duyệt),
// không chạy khi bị require_once từ file khác.
$__isDirect = isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === realpath(__FILE__);

if ($__isDirect) {
    try {
        $cleaned = cleanup_expired_holds($pdo);
        $msg = "Đã dọn {$cleaned} slot hết hạn.";
        if (PHP_SAPI === 'cli') {
            echo '[' . date('Y-m-d H:i:s') . "] {$msg}\n";
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'status'  => 'success',
                'code'    => 200,
                'message' => $msg,
                'data'    => ['cleaned' => $cleaned],
            ], JSON_UNESCAPED_UNICODE);
        }
    } catch (PDOException $e) {
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
