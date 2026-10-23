<?php
/**
 * database/migrate_posts.php
 * Script CLI chạy migration và seed cho bảng `posts` (Khám phá: Sự kiện, Gói hội viên, Thông báo).
 * Tương thích: MariaDB 10.4 & MySQL 8.0.
 * Idempotent: chạy nhiều lần an toàn, không xóa dữ liệu cũ.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Chỉ chạy qua dòng lệnh (CLI).\n");
}

require_once __DIR__ . '/../config/db.php';

echo "=== Bắt đầu Migration bảng `posts` (Khám phá) ===\n";

try {
    // 1. Chạy migration tạo bảng posts
    $migrationSql = file_get_contents(__DIR__ . '/migration_posts.sql');
    $pdo->exec($migrationSql);
    echo "✓ Đã kiểm tra / tạo bảng `posts` thành công.\n";

    // 2. Kiểm tra xem đã có dữ liệu trong bảng posts chưa
    $count = (int)$pdo->query("SELECT COUNT(*) FROM `posts`")->fetchColumn();
    if ($count === 0) {
        $seedSql = file_get_contents(__DIR__ . '/seed_posts.sql');
        $pdo->exec($seedSql);
        $newCount = (int)$pdo->query("SELECT COUNT(*) FROM `posts`")->fetchColumn();
        echo "✓ Đã nạp thành công {$newCount} bài viết mẫu vào bảng `posts`.\n";
    } else {
        echo "i Bảng `posts` đã có {$count} bài viết, bỏ qua bước nạp seed để tránh trùng lặp.\n";
    }

    echo "=== HOÀN TẤT MIGRATION BÀI VIẾT KHÁM PHÁ ===\n";
} catch (Throwable $e) {
    echo "✗ Lỗi migration: " . $e->getMessage() . "\n";
    exit(1);
}
