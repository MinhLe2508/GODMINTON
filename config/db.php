<?php
/**
 * config/db.php — Kết nối PDO dùng chung cho toàn bộ dự án.
 * Mọi file trong api/ include file này để lấy biến $pdo.
 *
 * Người phụ trách: Huy (Tuần 1, công việc #1-2 trong timeline).
 *
 * Cách dùng trong API:   require_once __DIR__ . '/../config/db.php';      (file nằm trong api/)
 *                        require_once __DIR__ . '/../../config/db.php';   (file nằm trong api/auth, api/admin, api/push)
 */

// ---- Nạp file .env (PHP/XAMPP KHÔNG tự đọc .env, nên phải tự đọc) ----
// Chỉ nạp biến chưa tồn tại trong môi trường, để biến hệ thống luôn được ưu tiên.
(function () {
    $envFile = __DIR__ . '/../.env';
    if (!is_readable($envFile)) {
        return;
    }
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
        }
    }
})();

// Đọc cấu hình; nếu không có thì dùng mặc định cho XAMPP local
// (KHÔNG commit mật khẩu thật lên GitHub — xem .env.example).
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'badminton_booking';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''; // XAMPP mặc định root không có mật khẩu
$DB_PORT = getenv('DB_PORT') ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // bắt buộc dùng prepared statement thật
        ]
    );
} catch (PDOException $e) {
    error_log('[GODMINTON] DB connect failed: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    // Không lộ chi tiết lỗi (host/user) ra trình duyệt; xem chi tiết trong log của Apache.
    die(json_encode([
        'status'  => 'error',
        'message' => 'Không kết nối được cơ sở dữ liệu. Kiểm tra MySQL đã bật, database badminton_booking đã import và file .env.',
    ], JSON_UNESCAPED_UNICODE));
}
