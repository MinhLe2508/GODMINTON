<?php

require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['status' => 'error', 'message' => 'Chỉ hỗ trợ GET.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT id,title,summary,content,image_url,category,published_at AS created_at FROM news WHERE status='published' AND published_at IS NOT NULL AND published_at<=NOW() ORDER BY published_at DESC,id DESC LIMIT 100");
    $stmt->execute();
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $error) {
    error_log('[news] ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Không tải được tin tức. Kiểm tra migration dữ liệu.'], JSON_UNESCAPED_UNICODE);
}
