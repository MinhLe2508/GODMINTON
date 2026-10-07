<?php
/**
 * api/admin/courts.php
 * CRUD sân (phân quyền theo owner_id / role).
 *
 * Người phụ trách: Huy (Tuần 3)
 */
require_once __DIR__ . '/../../config/db.php'; // file nằm trong api/<thư mục con>/ nên cần 2 lần '../'
header('Content-Type: application/json; charset=utf-8');

// TODO: triển khai logic thật, dùng $pdo->prepare(...) — không nối chuỗi SQL trực tiếp.
echo json_encode(['status' => 'not_implemented']);
