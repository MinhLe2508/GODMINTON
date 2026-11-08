<?php
/**
 * api/court-detail.php
 * GET chi tiết 1 sân theo id.
 *
 * Query parameter:
 *   - id : ID sân (INT, bắt buộc)
 *
 * Response JSON:
 *   { "status": "success", "data": {...court} }
 *
 * Người phụ trách: Minh (Tuần 1)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/image-url.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('Access-Control-Allow-Origin: *');

// Chỉ chấp nhận GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Validate ID
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Thiếu hoặc sai tham số id.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // ---- Lấy thông tin sân ----
    $stmt = $pdo->prepare("
        SELECT
            c.id,
            c.name,
            c.address,
            c.latitude,
            c.longitude,
            c.price_per_hour,
            c.description,
            c.status,
            w.id   AS ward_id,
            w.name AS ward_name,
            w.old_area_label,
            u.name  AS owner_name,
            u.phone AS owner_phone
        FROM courts c
        JOIN wards w ON w.id = c.ward_id
        JOIN users u ON u.id = c.owner_id
        WHERE c.id = :id AND c.approval_status='approved' AND EXISTS(SELECT 1 FROM users venue_owner WHERE venue_owner.id=c.owner_id AND venue_owner.account_status='active' AND venue_owner.deleted_at IS NULL)
        LIMIT 1
    ");
    $stmt->execute([':id' => $id]);
    $court = $stmt->fetch();

    if (!$court) {
        http_response_code(404);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Không tìm thấy sân với id = ' . $id
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ---- Lấy danh sách ảnh ----
    $imgStmt = $pdo->prepare("
        SELECT image_url, sort_order
        FROM court_images
        WHERE court_id = :id
        ORDER BY sort_order ASC
    ");
    $imgStmt->execute([':id' => $id]);
    $images = $imgStmt->fetchAll();

    // ---- Lấy tiện ích ----
    $amenStmt = $pdo->prepare("
        SELECT a.code, a.label
        FROM court_amenities ca
        JOIN amenities a ON a.id = ca.amenity_id
        WHERE ca.court_id = :id
        ORDER BY a.id ASC
    ");
    $amenStmt->execute([':id' => $id]);
    $amenities = $amenStmt->fetchAll();

    // ---- Format dữ liệu ----
    $result = [
        'id'              => (int) $court['id'],
        'name'            => $court['name'],
        'address'         => $court['address'],
        'latitude'        => (float) $court['latitude'],
        'longitude'       => (float) $court['longitude'],
        'price_per_hour'  => (int) $court['price_per_hour'],
        'price_formatted' => number_format($court['price_per_hour'], 0, '', '.') . ' đ',
        'description'     => $court['description'],
        'status'          => $court['status'],
        'ward_id'         => (int) $court['ward_id'],
        'ward_name'       => $court['ward_name'],
        'old_area_label'  => $court['old_area_label'],
        'owner_name'      => $court['owner_name'],
        'owner_phone'     => $court['owner_phone'],
        'images'          => array_map(function ($img) {
            return [
                'url'        => godminton_hires_image($img['image_url'], 1600),
                'sort_order' => (int) $img['sort_order']
            ];
        }, $images),
        'image_url'       => godminton_hires_image(!empty($images) ? $images[0]['image_url']
                             : 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=1600&auto=format&fit=crop', 1600),
        'amenities'       => array_map(function ($a) {
            return [
                'code'  => $a['code'],
                'label' => $a['label']
            ];
        }, $amenities),
        'amenity_codes'   => array_column($amenities, 'code'),
    ];

    echo json_encode([
        'status' => 'success',
        'data'   => $result
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
