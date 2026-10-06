<?php
/**
 * api/push/subscribe.php
 * Lưu push subscription của trình duyệt.
 *
 * Người phụ trách: Triết (Tuần 4)
 */
require_once __DIR__ . '/../config/db.php'; // sửa số lượng '../' nếu đổi vị trí file
header('Content-Type: application/json; charset=utf-8');

// TODO: triển khai logic thật, dùng $pdo->prepare(...) — không nối chuỗi SQL trực tiếp.
echo json_encode(['status' => 'not_implemented']);
