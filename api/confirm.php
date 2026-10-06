<?php
/**
 * api/confirm.php
 * POST xác nhận đặt sân, tạo bookings + booking_slots.
 *
 * Người phụ trách: Minh (Tuần 2)
 */
require_once __DIR__ . '/../config/db.php'; // sửa số lượng '../' nếu đổi vị trí file
header('Content-Type: application/json; charset=utf-8');

// TODO: triển khai logic thật, dùng $pdo->prepare(...) — không nối chuỗi SQL trực tiếp.
echo json_encode(['status' => 'not_implemented']);
