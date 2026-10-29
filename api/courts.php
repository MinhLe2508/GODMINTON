<?php
/**
 * api/courts.php
 * GET danh sách sân, hỗ trợ lọc theo phường/giá/tiện ích/tìm kiếm.
 *
 * Query parameters:
 *   - search    : tìm theo tên sân hoặc địa chỉ (LIKE)
 *   - ward_id   : lọc theo ID phường (INT)
 *   - max_price : giá tối đa (INT, đơn vị đồng)
 *   - amenities : mã tiện ích, phân cách bằng dấu phẩy (vd: aircon,parking,shower)
 *
 * Response JSON:
 *   { "status": "success", "data": [ {...court}, ... ] }
 *
 * Người phụ trách: Minh (Tuần 1)
 */
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');

// Chỉ chấp nhận GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

try {
    // ---- Xây dựng câu truy vấn cơ sở ----
    $sql = "
        SELECT
            c.id,
            c.name,
            c.address,
            c.latitude,
            c.longitude,
            c.price_per_hour,
            c.opening_time,
            c.closing_time,
            c.description,
            c.status,
            w.id   AS ward_id,
            w.name AS ward_name,
            w.old_area_label,
            -- Lấy ảnh đầu tiên (sort_order nhỏ nhất)
            (SELECT ci.image_url
             FROM court_images ci
             WHERE ci.court_id = c.id
             ORDER BY ci.sort_order ASC
             LIMIT 1) AS image_url,
            -- Gom các mã tiện ích thành chuỗi phân cách bởi dấu phẩy
            (SELECT GROUP_CONCAT(a.code ORDER BY a.id SEPARATOR ',')
             FROM court_amenities ca
             JOIN amenities a ON a.id = ca.amenity_id
             WHERE ca.court_id = c.id) AS amenity_codes,
            -- Gom label tiện ích
            (SELECT GROUP_CONCAT(a.label ORDER BY a.id SEPARATOR ',')
             FROM court_amenities ca
             JOIN amenities a ON a.id = ca.amenity_id
             WHERE ca.court_id = c.id) AS amenity_labels
        FROM courts c
        JOIN wards w ON w.id = c.ward_id
        WHERE c.status = 'active' AND c.approval_status='approved' AND c.booking_open=1 AND EXISTS(SELECT 1 FROM users venue_owner WHERE venue_owner.id=c.owner_id AND venue_owner.account_status='active' AND venue_owner.deleted_at IS NULL AND venue_owner.is_verified=1)
    ";
    $params = [];

    // ---- Lọc theo từ khóa tìm kiếm ----
    if (!empty($_GET['search'])) {
        $search = '%' . trim($_GET['search']) . '%';
        $sql .= " AND (c.name LIKE :search OR c.address LIKE :search2)";
        $params[':search']  = $search;
        $params[':search2'] = $search;
    }

    // ---- Lọc theo ward_id ----
    if (!empty($_GET['ward_id'])) {
        $sql .= " AND c.ward_id = :ward_id";
        $params[':ward_id'] = (int) $_GET['ward_id'];
    }

    // ---- Lọc theo mức giá ----
    $priceRange = trim($_GET['price_range'] ?? '');
    if (empty($priceRange) && !empty($_GET['max_price']) && strpos((string)$_GET['max_price'], '-') !== false) {
        $priceRange = trim((string)$_GET['max_price']);
    }

    if (!empty($priceRange)) {
        if ($priceRange === '0-80000') {
            $sql .= " AND c.price_per_hour <= 80000";
        } elseif ($priceRange === '80000-100000') {
            $sql .= " AND c.price_per_hour >= 80000 AND c.price_per_hour <= 100000";
        } elseif ($priceRange === '100000-150000') {
            $sql .= " AND c.price_per_hour >= 100000 AND c.price_per_hour <= 150000";
        } elseif ($priceRange === '150000-up' || $priceRange === '150000+') {
            $sql .= " AND c.price_per_hour >= 150000";
        } elseif (preg_match('/^(\d+)-(\d+)$/', $priceRange, $m)) {
            $minP = (int) $m[1];
            $maxP = (int) $m[2];
            if ($minP > 0) {
                $sql .= " AND c.price_per_hour >= :min_range_price";
                $params[':min_range_price'] = $minP;
            }
            if ($maxP > 0) {
                $sql .= " AND c.price_per_hour <= :max_range_price";
                $params[':max_range_price'] = $maxP;
            }
        }
    } else {
        if (!empty($_GET['min_price'])) {
            $sql .= " AND c.price_per_hour >= :min_price";
            $params[':min_price'] = (int) $_GET['min_price'];
        }
        if (!empty($_GET['max_price'])) {
            $sql .= " AND c.price_per_hour <= :max_price";
            $params[':max_price'] = (int) $_GET['max_price'];
        }
    }
    // ---- Lọc theo tiện ích (phải có TẤT CẢ các tiện ích được chọn) ----
    if (!empty($_GET['amenities'])) {
        $amenityCodes = array_filter(explode(',', $_GET['amenities']));
        if (count($amenityCodes) > 0) {
            // Sử dụng subquery đếm — sân phải có đủ N tiện ích yêu cầu
            $amenityPlaceholders = [];
            foreach ($amenityCodes as $i => $code) {
                $key = ':amen_' . $i;
                $amenityPlaceholders[] = $key;
                $params[$key] = trim($code);
            }
            $inList = implode(',', $amenityPlaceholders);
            $count  = count($amenityCodes);
            $sql .= "
                AND (
                    SELECT COUNT(*)
                    FROM court_amenities ca2
                    JOIN amenities a2 ON a2.id = ca2.amenity_id
                    WHERE ca2.court_id = c.id
                      AND a2.code IN ({$inList})
                ) = {$count}
            ";
        }
    }

    $sql .= " ORDER BY c.id ASC";

    // ---- Thực thi ----
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // ---- Format dữ liệu đầu ra ----
    $courts = [];
    foreach ($rows as $row) {
        $amenityCodes  = $row['amenity_codes']  ? explode(',', $row['amenity_codes'])  : [];
        $amenityLabels = $row['amenity_labels'] ? explode(',', $row['amenity_labels']) : [];

        $amenities = [];
        for ($i = 0; $i < count($amenityCodes); $i++) {
            $amenities[] = [
                'code'  => $amenityCodes[$i],
                'label' => $amenityLabels[$i] ?? $amenityCodes[$i]
            ];
        }

        $courts[] = [
            'id'              => (int) $row['id'],
            'name'            => $row['name'],
            'address'         => $row['address'],
            'latitude'        => (float) $row['latitude'],
            'longitude'       => (float) $row['longitude'],
            'price_per_hour'  => (int) $row['price_per_hour'],
            'price_formatted' => number_format($row['price_per_hour'], 0, '', '.') . ' đ',
            'description'     => $row['description'],
            'status'          => $row['status'],
            'opening_time'    => $row['opening_time'],
            'closing_time'    => $row['closing_time'],
            'ward_id'         => (int) $row['ward_id'],
            'ward_name'       => $row['ward_name'],
            'old_area_label'  => $row['old_area_label'],
            'image_url'       => $row['image_url'] ?: 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop',
            'amenities'       => $amenities,
            'amenity_codes'   => $amenityCodes,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $courts,
        'total'  => count($courts),
        'server_time' => (int) round(microtime(true) * 1000)
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
