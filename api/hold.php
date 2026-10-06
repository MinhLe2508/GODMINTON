<?php
/**
 * api/hold.php
 * POST giữ chỗ 5 phút — transaction + SELECT...FOR UPDATE ★★★
 *
 * Người phụ trách: Huy (Tuần 2)
 */
require_once __DIR__ . '/../config/db.php'; // sửa số lượng '../' nếu đổi vị trí file
header('Content-Type: application/json; charset=utf-8');

// TODO: triển khai logic thật, dùng $pdo->prepare(...) — không nối chuỗi SQL trực tiếp.
echo json_encode(['status' => 'not_implemented']);
