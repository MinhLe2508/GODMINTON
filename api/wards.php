<?php
/**
 * api/wards.php
 * GET danh sách phường (để populate dropdown lọc khu vực).
 *
 * Response JSON:
 *   { "status": "success", "data": [ { "id": 8, "name": "Bến Nghé", "old_area_label": "Quận 1 (cũ)" }, ... ] }
 *
 * Người phụ trách: Minh (Tuần 1)
 */
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

try {
    // Chỉ trả về phường có ít nhất 1 sân active
    $stmt = $pdo->query("
        SELECT DISTINCT w.id, w.name, w.old_area_label
        FROM wards w
        JOIN courts c ON c.ward_id = w.id AND c.status = 'active'
        ORDER BY w.old_area_label ASC, w.name ASC
    ");
    $wards = $stmt->fetchAll();

    // Cast id to int
    $wards = array_map(function ($w) {
        return [
            'id'             => (int) $w['id'],
            'name'           => $w['name'],
            'old_area_label' => $w['old_area_label']
        ];
    }, $wards);

    echo json_encode([
        'status' => 'success',
        'data'   => $wards
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Lỗi truy vấn: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
