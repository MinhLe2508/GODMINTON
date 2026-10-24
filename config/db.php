<?php
/**
 * config/db.php — Kết nối PDO dùng chung cho toàn bộ dự án.
 * Mọi file trong api/ include file này để lấy biến $pdo.
 * Hỗ trợ tự động dò cổng MySQL XAMPP (3306 / 3307) và tự khởi tạo DB nếu chưa có.
 */

require_once __DIR__ . '/load-env.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');

$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_PORT = getenv('DB_PORT') ?: null;
$DB_NAME = getenv('DB_NAME') ?: 'badminton_booking';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';

// Luôn thử cổng trong .env trước, sau đó tự động fallback sang 3306 và 3307
// để tránh lỗi khi .env để 3307 nhưng XAMPP chạy 3306 (hoặc ngược lại).
$portsToTry = array_values(array_unique(array_filter([
    $DB_PORT ? (int)$DB_PORT : null,
    3306,
    3307,
    3308,
])));

$hostsToTry = array_values(array_unique(array_filter([
    $DB_HOST ?: null,
    '127.0.0.1',
    'localhost',
])));

$passesToTry = array_values(array_unique([
    (string)$DB_PASS,
    '',
]));

if (!function_exists('godminton_exec_sql_file')) {
    function godminton_exec_sql_file(PDO $pdo, string $filePath): void {
        if (!is_readable($filePath)) {
            return;
        }
        $raw = file_get_contents($filePath);
        if ($raw === false || trim($raw) === '') {
            return;
        }
        $raw = str_replace("\r\n", "\n", $raw);
        $lines = explode("\n", $raw);
        $cleanLines = [];
        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if ($trimmed === '' || strpos($trimmed, '--') === 0) {
                continue;
            }
            $cleanLines[] = $line;
        }
        $cleaned = implode("\n", $cleanLines);
        $statements = explode(";\n", $cleaned);
        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }
            if (substr($stmt, -1) === ';') {
                $stmt = trim(substr($stmt, 0, -1));
            }
            if ($stmt !== '') {
                $pdo->exec($stmt);
            }
        }
    }
}

if (!function_exists('godminton_ensure_schema_ready')) {
    function godminton_ensure_schema_ready(PDO $pdo, string $dbName): void {
        $projectRoot = dirname(__DIR__);
        try {
            $stmt = $pdo->query("SHOW TABLES LIKE 'courts'");
            $hasCourtsTable = (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            $hasCourtsTable = false;
        }

        if (!$hasCourtsTable) {
            // Database mới tinh -> Tự động nạp schema.sql, seed.sql, seed_vouchers.sql, seed_reviews_demo.sql
            $sqlFiles = [
                $projectRoot . '/database/schema.sql',
                $projectRoot . '/database/seed.sql',
                $projectRoot . '/database/seed_vouchers.sql',
                $projectRoot . '/database/seed_reviews_demo.sql',
            ];
            foreach ($sqlFiles as $sqlFile) {
                try {
                    godminton_exec_sql_file($pdo, $sqlFile);
                } catch (Throwable $e) {
                    error_log('[GODMINTON DB INIT] ' . basename($sqlFile) . ': ' . $e->getMessage());
                }
            }
            return;
        }

        // Nếu bảng courts đã có nhưng trống dữ liệu -> nạp seed.sql
        try {
            $courtCount = (int)$pdo->query("SELECT COUNT(*) FROM courts")->fetchColumn();
            if ($courtCount === 0) {
                godminton_exec_sql_file($pdo, $projectRoot . '/database/seed.sql');
                godminton_exec_sql_file($pdo, $projectRoot . '/database/seed_vouchers.sql');
                godminton_exec_sql_file($pdo, $projectRoot . '/database/seed_reviews_demo.sql');
            }
        } catch (Throwable $e) {
            error_log('[GODMINTON DB SEED CHECK] ' . $e->getMessage());
        }
    }
}

$pdo = null;
$lastException = null;
$pdoOptions = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::ATTR_TIMEOUT            => 3,
];

foreach ($hostsToTry as $host) {
    foreach ($portsToTry as $port) {
        foreach ($passesToTry as $pass) {
            try {
                $pdo = new PDO(
                    "mysql:host={$host};port={$port};dbname={$DB_NAME};charset=utf8mb4",
                    $DB_USER,
                    $pass,
                    $pdoOptions
                );
                $pdo->exec("SET time_zone = '+07:00'");
                godminton_ensure_schema_ready($pdo, $DB_NAME);
                break 3;
            } catch (PDOException $e) {
                $lastException = $e;
                $errCode = (int)($e->errorInfo[1] ?? 0);
                // Mã 1049: Unknown database -> Tự tạo database badminton_booking rồi kết nối lại
                if ($errCode === 1049 || strpos($e->getMessage(), '1049') !== false || stripos($e->getMessage(), 'Unknown database') !== false) {
                    try {
                        $serverPdo = new PDO(
                            "mysql:host={$host};port={$port};charset=utf8mb4",
                            $DB_USER,
                            $pass,
                            $pdoOptions
                        );
                        $safeDbName = str_replace('`', '``', $DB_NAME);
                        $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $serverPdo = null;

                        $pdo = new PDO(
                            "mysql:host={$host};port={$port};dbname={$DB_NAME};charset=utf8mb4",
                            $DB_USER,
                            $pass,
                            $pdoOptions
                        );
                        $pdo->exec("SET time_zone = '+07:00'");
                        godminton_ensure_schema_ready($pdo, $DB_NAME);
                        break 3;
                    } catch (Throwable $createErr) {
                        $lastException = $createErr;
                    }
                }
            }
        }
    }
}

if (!$pdo) {
    error_log('[GODMINTON DB] ' . ($lastException ? $lastException->getMessage() : 'No connection'));
    if (defined('GODMINTON_DB_OPTIONAL') && GODMINTON_DB_OPTIONAL === true) {
        return;
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'Không kết nối được MySQL. Vui lòng bật MySQL trong XAMPP Control Panel.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
