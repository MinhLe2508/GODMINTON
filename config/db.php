<?php
/**
 * config/db.php — Kết nối PDO dùng chung cho toàn bộ dự án.
 * Mọi file trong api/ include file này để lấy biến $pdo.
 *
 * Người phụ trách: Huy (Tuần 1, công việc #1-2 trong timeline).
 */

require_once __DIR__ . '/load-env.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: null;
$DB_NAME = getenv('DB_NAME') ?: 'badminton_booking';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: ''; // XAMPP mặc định user root không có mật khẩu

// Cố gắng kết nối: nếu có cấu hình DB_PORT thì dùng, nếu không thì thử 3306 rồi fallback sang 3307
$portsToTry = $DB_PORT ? [(int)$DB_PORT] : [3306, 3307];
$pdo = null;
$lastException = null;

foreach ($portsToTry as $port) {
    try {
        $pdo = new PDO(
            "mysql:host={$DB_HOST};port={$port};dbname={$DB_NAME};charset=utf8mb4",
            $DB_USER,
            $DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false, // bắt buộc dùng prepared statement thật
            ]
        );
        // Đồng bộ múi giờ PHP và MySQL (giờ Việt Nam) để NOW() / hold_expires_at
        // luôn khớp nhau, bất kể múi giờ mặc định của máy chủ MySQL.
        $pdo->exec("SET time_zone = '+07:00'");
        break;
    } catch (PDOException $e) {
        $lastException = $e;
    }
}

if (!$pdo) {
    error_log('[GODMINTON DB] ' . ($lastException ? $lastException->getMessage() : 'No connection'));
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'error',
        'message' => 'Không kết nối được DB. Kiểm tra .env và log PHP.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
