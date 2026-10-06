<?php
/**
 * config/db.php — Kết nối PDO dùng chung cho toàn bộ dự án.
 * Mọi file trong api/ include file này để lấy biến $pdo.
 *
 * Người phụ trách: Huy (Tuần 1, công việc #1-2 trong timeline).
 */

// Đọc cấu hình từ biến môi trường nếu có, nếu không dùng giá trị mặc định
// cho XAMPP local (KHÔNG commit mật khẩu thật lên GitHub — xem .env.example).
$DB_HOST = getenv('DB_HOST') ?: '127.0.0.1';
$DB_NAME = getenv('DB_NAME') ?: 'badminton_booking';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: ''; // XAMPP mặc định user root không có mật khẩu

try {
    $pdo = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // bắt buộc dùng prepared statement thật
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die('Lỗi kết nối cơ sở dữ liệu: ' . $e->getMessage());
}
