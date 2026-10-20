<?php
require_once __DIR__.'/../../includes/auth-session.php';
/**
 * api/admin/courts.php
 * Task 13 — CRUD sân cầu lông trên MySQL thật + phân quyền chủ sân / admin.
 *   - Chủ sân (role = 'owner'): chỉ thấy, thêm, sửa, xóa sân có owner_id = $_SESSION['user_id'].
 *   - Quản trị viên (role = 'admin'): thấy và quản lý toàn bộ sân trong hệ thống.
 *
 * Hỗ trợ:
 *   - GET    /api/admin/courts.php[?id=1&search=...] : Danh sách sân (hoặc chi tiết 1 sân) + danh mục wards
 *   - POST   /api/admin/courts.php                   : Thêm sân mới (hoặc action='update'|'delete')
 *   - PUT    /api/admin/courts.php                   : Cập nhật thông tin sân
 *   - PATCH  /api/admin/courts.php                   : Cập nhật thông tin sân / đổi trạng thái
 *   - DELETE /api/admin/courts.php?id=1              : Xóa sân
 *
 * Người phụ trách: Huy (Tuần 3 — Task 13)
 */
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function jsonOut(int $code, array $body): void {
    http_response_code($code);
    if (!isset($body['code'])) {
        $body = ['status' => $body['status'] ?? ($code < 400 ? 'success' : 'error'), 'code' => $code] + $body;
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) {
    god_auth_start();
    god_auth_user($pdo);
}
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$currentRole   = $_SESSION['role'] ?? '';
session_write_close();

if ($currentUserId <= 0) {
    jsonOut(401, ['status' => 'error', 'message' => 'Vui lòng đăng nhập để truy cập kênh quản trị.']);
}
if (!in_array($currentRole, ['admin', 'owner'], true)) {
    jsonOut(403, ['status' => 'error', 'message' => 'Bạn không có quyền truy cập chức năng quản lý sân.']);
}

/**
 * Tự động kiểm tra và bổ sung các cột mở rộng của Tuần 3 (total_sub_courts, opening_time, closing_time)
 * vào bảng courts nếu người dùng chưa chạy database/migration_admin_management.sql.
 */
function ensureCourtsSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $cols = $pdo->query("SHOW COLUMNS FROM courts")->fetchAll(PDO::FETCH_COLUMN, 0);
        $altered = false;
        if (!in_array('total_sub_courts', $cols, true)) {
            $pdo->exec("ALTER TABLE courts ADD COLUMN total_sub_courts INT UNSIGNED NOT NULL DEFAULT 6 AFTER price_per_hour");
            $altered = true;
        }
        if (!in_array('opening_time', $cols, true)) {
            $pdo->exec("ALTER TABLE courts ADD COLUMN opening_time TIME NOT NULL DEFAULT '06:00:00' AFTER total_sub_courts");
            $altered = true;
        }
        if (!in_array('closing_time', $cols, true)) {
            $pdo->exec("ALTER TABLE courts ADD COLUMN closing_time TIME NOT NULL DEFAULT '22:00:00' AFTER opening_time");
            $altered = true;
        }
        if ($altered) {
            $defaults = [
                1 => [6,  '06:00:00', '23:00:00'],
                2 => [8,  '05:30:00', '23:00:00'],
                3 => [10, '05:00:00', '23:30:00'],
                4 => [5,  '06:00:00', '22:30:00'],
                5 => [4,  '06:00:00', '22:00:00'],
                6 => [7,  '05:00:00', '23:00:00'],
                7 => [12, '05:30:00', '23:00:00'],
            ];
            $upd = $pdo->prepare("UPDATE courts SET total_sub_courts = ?, opening_time = ?, closing_time = ? WHERE id = ?");
            foreach ($defaults as $cid => [$sub, $open, $close]) {
                $upd->execute([$sub, $open, $close, $cid]);
            }
        }
    } catch (Throwable $e) {
        error_log('[admin/courts.php ensureCourtsSchema] ' . $e->getMessage());
    }
}

/**
 * Tìm hoặc tạo ward_id từ input (hỗ trợ cả ward_id số nguyên hoặc chuỗi tên khu vực/quận/phường).
 */
function resolveWardId(PDO $pdo, array $input, ?int $fallbackWardId = null): int {
    if (!empty($input['ward_id']) && (int)$input['ward_id'] > 0) {
        $wid = (int)$input['ward_id'];
        $chk = $pdo->prepare("SELECT id FROM wards WHERE id = ?");
        $chk->execute([$wid]);
        if ($chk->fetchColumn()) {
            return $wid;
        }
    }

    $district = trim((string)($input['district'] ?? $input['ward_name'] ?? ''));
    if ($district !== '') {
        // Tìm chính xác hoặc gần đúng theo name hoặc old_area_label
        $stmt = $pdo->prepare("
            SELECT id FROM wards
            WHERE LOWER(name) = LOWER(:exact)
               OR LOWER(old_area_label) = LOWER(:exact2)
               OR name LIKE :like1
               OR old_area_label LIKE :like2
            ORDER BY id ASC
            LIMIT 1
        ");
        $like = '%' . $district . '%';
        $stmt->execute([
            ':exact'  => $district,
            ':exact2' => $district,
            ':like1'  => $like,
            ':like2'  => $like,
        ]);
        $found = $stmt->fetchColumn();
        if ($found) {
            return (int)$found;
        }

        // Nếu người dùng nhập một khu vực mới chưa có trong bảng wards → thêm mới
        $ins = $pdo->prepare("INSERT INTO wards (name, old_area_label) VALUES (?, ?)");
        $ins->execute([$district, $district]);
        return (int)$pdo->lastInsertId();
    }

    if ($fallbackWardId !== null && $fallbackWardId > 0) {
        return $fallbackWardId;
    }

    $first = $pdo->query("SELECT id FROM wards ORDER BY id ASC LIMIT 1")->fetchColumn();
    return $first ? (int)$first : 1;
}

/**
 * Chuẩn hóa danh sách mã tiện ích (chuyển alias 'canteen' của bản tĩnh sang 'cafe' trong DB).
 */
function normalizeAmenityCodes(mixed $raw): array {
    if (is_string($raw)) {
        $raw = array_filter(array_map('trim', explode(',', $raw)));
    }
    if (!is_array($raw)) {
        return [];
    }
    $codes = [];
    foreach ($raw as $item) {
        $c = is_array($item) ? strtolower(trim((string)($item['code'] ?? ''))) : strtolower(trim((string)$item));
        if ($c === 'canteen') {
            $c = 'cafe';
        }
        if ($c !== '') {
            $codes[] = $c;
        }
    }
    return array_values(array_unique($codes));
}

/**
 * Đồng bộ bảng court_amenities cho 1 sân.
 */
function syncCourtAmenities(PDO $pdo, int $courtId, array $codes): void {
    $pdo->prepare("DELETE FROM court_amenities WHERE court_id = ?")->execute([$courtId]);
    if (empty($codes)) {
        return;
    }
    $in = implode(',', array_fill(0, count($codes), '?'));
    $stmt = $pdo->prepare("SELECT id FROM amenities WHERE code IN ($in)");
    $stmt->execute($codes);
    $amenityIds = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    $ins = $pdo->prepare("INSERT IGNORE INTO court_amenities (court_id, amenity_id) VALUES (?, ?)");
    foreach ($amenityIds as $aid) {
        $ins->execute([$courtId, (int)$aid]);
    }
}

/**
 * Đồng bộ ảnh đại diện chính (sort_order = 0) trong bảng court_images.
 */
function syncCourtPrimaryImage(PDO $pdo, int $courtId, string $imageUrl): void {
    $defaultImg = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop';
    $url = trim($imageUrl) !== '' ? trim($imageUrl) : $defaultImg;

    $chk = $pdo->prepare("SELECT id FROM court_images WHERE court_id = ? ORDER BY sort_order ASC, id ASC LIMIT 1");
    $chk->execute([$courtId]);
    $imgId = $chk->fetchColumn();

    if ($imgId) {
        $pdo->prepare("UPDATE court_images SET image_url = ? WHERE id = ?")->execute([$url, (int)$imgId]);
    } else {
        $pdo->prepare("INSERT INTO court_images (court_id, image_url, sort_order) VALUES (?, ?, 0)")->execute([$courtId, $url]);
    }
}

/**
 * Truy vấn và format 1 hoặc nhiều sân từ MySQL.
 */
function fetchFormattedCourts(PDO $pdo, string $role, int $userId, ?int $onlyCourtId = null, string $search = ''): array {
    $sql = "
        SELECT
            c.id,
            c.owner_id,
            c.ward_id,
            c.name,
            c.address,
            c.latitude,
            c.longitude,
            c.price_per_hour,
            c.total_sub_courts,
            c.opening_time,
            c.closing_time,
            c.description,
            c.status,
            c.created_at,
            w.name           AS ward_name,
            w.old_area_label AS old_area_label,
            u.name           AS owner_name,
            u.phone          AS owner_phone,
            u.email          AS owner_email,
            (SELECT ci.image_url
             FROM court_images ci
             WHERE ci.court_id = c.id
             ORDER BY ci.sort_order ASC, ci.id ASC
             LIMIT 1) AS image_url,
            (SELECT GROUP_CONCAT(a.code ORDER BY a.id SEPARATOR ',')
             FROM court_amenities ca
             JOIN amenities a ON a.id = ca.amenity_id
             WHERE ca.court_id = c.id) AS amenity_codes,
            (SELECT GROUP_CONCAT(a.label ORDER BY a.id SEPARATOR ',')
             FROM court_amenities ca
             JOIN amenities a ON a.id = ca.amenity_id
             WHERE ca.court_id = c.id) AS amenity_labels
        FROM courts c
        JOIN wards w ON w.id = c.ward_id
        JOIN users u ON u.id = c.owner_id
        WHERE 1=1
    ";
    $params = [];

    // Phân quyền: Chủ sân chỉ thấy sân có owner_id = mình; Admin thấy toàn bộ
    if ($role !== 'admin') {
        $sql .= " AND c.owner_id = :owner_id";
        $params[':owner_id'] = $userId;
    }

    if ($onlyCourtId !== null) {
        $sql .= " AND c.id = :court_id";
        $params[':court_id'] = $onlyCourtId;
    }

    if ($search !== '') {
        $like = '%' . $search . '%';
        $sql .= " AND (c.name LIKE :s1 OR c.address LIKE :s2 OR w.name LIKE :s3 OR w.old_area_label LIKE :s4)";
        $params[':s1'] = $like;
        $params[':s2'] = $like;
        $params[':s3'] = $like;
        $params[':s4'] = $like;
    }

    $sql .= " ORDER BY c.id ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $defaultImg = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop';
    $result = [];

    foreach ($rows as $row) {
        $codes  = $row['amenity_codes']  ? explode(',', $row['amenity_codes'])  : [];
        $labels = $row['amenity_labels'] ? explode(',', $row['amenity_labels']) : [];
        $price  = (int)$row['price_per_hour'];

        $districtLabel = $row['old_area_label']
            ? preg_replace('/\s*\(cũ\)\s*$/u', '', $row['old_area_label'])
            : $row['ward_name'];

        $result[] = [
            'id'               => (int)$row['id'],
            'owner_id'         => (int)$row['owner_id'],
            'owner_name'       => $row['owner_name'],
            'owner_phone'      => $row['owner_phone'] ?? '',
            'owner_email'      => $row['owner_email'] ?? '',
            'ward_id'          => (int)$row['ward_id'],
            'ward_name'        => $row['ward_name'],
            'old_area_label'   => $row['old_area_label'] ?? '',
            'district'         => $districtLabel,
            'city'             => 'TP. Hồ Chí Minh',
            'name'             => $row['name'],
            'address'          => $row['address'],
            'latitude'         => (float)$row['latitude'],
            'longitude'        => (float)$row['longitude'],
            'price_per_hour'   => $price,
            'min_price'        => $price,
            'price_formatted'  => number_format($price, 0, '', '.') . ' đ',
            'total_sub_courts' => (int)($row['total_sub_courts'] ?? 6),
            'opening_time'     => substr((string)($row['opening_time'] ?? '06:00:00'), 0, 5),
            'closing_time'     => substr((string)($row['closing_time'] ?? '22:00:00'), 0, 5),
            'description'      => $row['description'] ?? '',
            'status'           => $row['status'],
            'image_url'        => $row['image_url'] ?: $defaultImg,
            'amenities'        => $codes,
            'amenity_codes'    => $codes,
            'amenity_labels'   => $labels,
            'created_at'       => $row['created_at'],
        ];
    }

    return $result;
}

ensureCourtsSchema($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// Hỗ trợ truyền _method hoặc action qua POST cho các môi trường chặn PUT/DELETE
if ($method === 'POST' && !empty($input['action'])) {
    $act = strtolower(trim((string)$input['action']));
    if ($act === 'update' || $act === 'edit') {
        $method = 'PUT';
    } elseif ($act === 'delete' || $act === 'remove') {
        $method = 'DELETE';
    }
}

try {
    // =====================================================================
    // 1. GET — Lấy danh sách sân (hoặc 1 sân theo ?id=) theo phân quyền
    // =====================================================================
    if ($method === 'GET') {
        $onlyId = !empty($_GET['id']) ? (int)$_GET['id'] : null;
        $search = trim((string)($_GET['search'] ?? ''));

        if ($onlyId !== null) {
            // Kiểm tra sân tồn tại và quyền sở hữu
            $chk = $pdo->prepare("SELECT id, owner_id FROM courts WHERE id = ?");
            $chk->execute([$onlyId]);
            $row = $chk->fetch();
            if (!$row) {
                jsonOut(404, ['status' => 'error', 'message' => 'Không tìm thấy sân cầu lông.']);
            }
            if ($currentRole !== 'admin' && (int)$row['owner_id'] !== $currentUserId) {
                jsonOut(403, ['status' => 'error', 'message' => 'Bạn không có quyền xem/chỉnh sửa sân của chủ sân khác.']);
            }
            $courts = fetchFormattedCourts($pdo, $currentRole, $currentUserId, $onlyId);
            jsonOut(200, ['status' => 'success', 'data' => $courts[0] ?? null]);
        }

        $courts = fetchFormattedCourts($pdo, $currentRole, $currentUserId, null, $search);
        $wards  = $pdo->query("SELECT id, name, old_area_label FROM wards ORDER BY id ASC")->fetchAll();
        foreach ($wards as &$w) {
            $w['id'] = (int)$w['id'];
        }
        unset($w);

        jsonOut(200, [
            'status' => 'success',
            'data'   => $courts,
            'wards'  => $wards,
            'total'  => count($courts),
        ]);
    }

    // =====================================================================
    // 2. POST — Thêm sân mới
    // =====================================================================
    if ($method === 'POST') {
        $name        = trim((string)($input['name'] ?? ''));
        $address     = trim((string)($input['address'] ?? ''));
        $price       = isset($input['price_per_hour']) ? (int)$input['price_per_hour'] : -1;
        $subCount    = isset($input['total_sub_courts']) ? max(1, (int)$input['total_sub_courts']) : 6;
        $status      = in_array($input['status'] ?? 'active', ['active', 'inactive'], true) ? $input['status'] : 'active';
        $openTime    = trim((string)($input['opening_time'] ?? '06:00')) ?: '06:00';
        $closeTime   = trim((string)($input['closing_time'] ?? '22:00')) ?: '22:00';
        $latitude    = isset($input['latitude']) && is_numeric($input['latitude']) ? (float)$input['latitude'] : 10.7769000;
        $longitude   = isset($input['longitude']) && is_numeric($input['longitude']) ? (float)$input['longitude'] : 106.7009000;
        $description = trim((string)($input['description'] ?? ''));
        $imageUrl    = trim((string)($input['image_url'] ?? ''));
        $amenities   = normalizeAmenityCodes($input['amenities'] ?? []);

        if ($name === '' || $address === '' || $price < 0) {
            jsonOut(422, ['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ tên sân, địa chỉ và giá thuê hợp lệ.']);
        }

        // Chủ sân tạo sân thì owner_id luôn là chính mình; Admin có thể chỉ định owner_id hoặc mặc định là mình
        $ownerId = ($currentRole === 'admin' && !empty($input['owner_id']))
            ? (int)$input['owner_id']
            : $currentUserId;

        $pdo->beginTransaction();
        $wardId = resolveWardId($pdo, $input);

        $ins = $pdo->prepare("
            INSERT INTO courts (
                owner_id, ward_id, name, address, latitude, longitude,
                price_per_hour, total_sub_courts, opening_time, closing_time,
                description, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([
            $ownerId,
            $wardId,
            $name,
            $address,
            $latitude,
            $longitude,
            $price,
            $subCount,
            strlen($openTime) === 5 ? $openTime . ':00' : $openTime,
            strlen($closeTime) === 5 ? $closeTime . ':00' : $closeTime,
            $description,
            $status,
        ]);
        $newCourtId = (int)$pdo->lastInsertId();

        syncCourtPrimaryImage($pdo, $newCourtId, $imageUrl);
        syncCourtAmenities($pdo, $newCourtId, $amenities);

        $pdo->commit();

        $created = fetchFormattedCourts($pdo, 'admin', $currentUserId, $newCourtId);
        jsonOut(201, [
            'status'  => 'success',
            'message' => 'Đã thêm sân mới thành công.',
            'data'    => $created[0] ?? ['id' => $newCourtId],
        ]);
    }

    // =====================================================================
    // 3. PUT / PATCH — Cập nhật thông tin sân
    // =====================================================================
    if ($method === 'PUT' || $method === 'PATCH') {
        $courtId = !empty($input['id']) ? (int)$input['id'] : (!empty($_GET['id']) ? (int)$_GET['id'] : 0);
        if ($courtId <= 0) {
            jsonOut(422, ['status' => 'error', 'message' => 'Thiếu ID sân cần cập nhật.']);
        }

        $chk = $pdo->prepare("SELECT * FROM courts WHERE id = ?");
        $chk->execute([$courtId]);
        $existing = $chk->fetch();
        if (!$existing) {
            jsonOut(404, ['status' => 'error', 'message' => "Không tìm thấy sân ID #{$courtId}."]);
        }

        // Phân quyền: Chủ sân chỉ được sửa sân của chính mình
        if ($currentRole !== 'admin' && (int)$existing['owner_id'] !== $currentUserId) {
            jsonOut(403, ['status' => 'error', 'message' => 'Bạn chỉ có quyền chỉnh sửa sân do mình sở hữu.']);
        }

        $name        = isset($input['name']) ? trim((string)$input['name']) : $existing['name'];
        $address     = isset($input['address']) ? trim((string)$input['address']) : $existing['address'];
        $price       = isset($input['price_per_hour']) ? (int)$input['price_per_hour'] : (int)$existing['price_per_hour'];
        $subCount    = isset($input['total_sub_courts']) ? max(1, (int)$input['total_sub_courts']) : (int)($existing['total_sub_courts'] ?? 6);
        $status      = isset($input['status']) && in_array($input['status'], ['active', 'inactive'], true)
                       ? $input['status'] : $existing['status'];
        $openTime    = isset($input['opening_time']) && trim((string)$input['opening_time']) !== ''
                       ? trim((string)$input['opening_time']) : substr((string)($existing['opening_time'] ?? '06:00:00'), 0, 5);
        $closeTime   = isset($input['closing_time']) && trim((string)$input['closing_time']) !== ''
                       ? trim((string)$input['closing_time']) : substr((string)($existing['closing_time'] ?? '22:00:00'), 0, 5);
        $latitude    = isset($input['latitude']) && is_numeric($input['latitude']) ? (float)$input['latitude'] : (float)$existing['latitude'];
        $longitude   = isset($input['longitude']) && is_numeric($input['longitude']) ? (float)$input['longitude'] : (float)$existing['longitude'];
        $description = array_key_exists('description', $input) ? trim((string)$input['description']) : ($existing['description'] ?? '');

        if ($name === '' || $address === '' || $price < 0) {
            jsonOut(422, ['status' => 'error', 'message' => 'Tên sân, địa chỉ và giá thuê không được để trống.']);
        }

        $pdo->beginTransaction();
        $wardId = resolveWardId($pdo, $input, (int)$existing['ward_id']);

        $upd = $pdo->prepare("
            UPDATE courts
               SET ward_id          = ?,
                   name             = ?,
                   address          = ?,
                   latitude         = ?,
                   longitude        = ?,
                   price_per_hour   = ?,
                   total_sub_courts = ?,
                   opening_time     = ?,
                   closing_time     = ?,
                   description      = ?,
                   status           = ?
             WHERE id = ?
        ");
        $upd->execute([
            $wardId,
            $name,
            $address,
            $latitude,
            $longitude,
            $price,
            $subCount,
            strlen($openTime) === 5 ? $openTime . ':00' : $openTime,
            strlen($closeTime) === 5 ? $closeTime . ':00' : $closeTime,
            $description,
            $status,
            $courtId,
        ]);

        if (array_key_exists('image_url', $input)) {
            syncCourtPrimaryImage($pdo, $courtId, (string)$input['image_url']);
        }
        if (array_key_exists('amenities', $input)) {
            syncCourtAmenities($pdo, $courtId, normalizeAmenityCodes($input['amenities']));
        }

        $pdo->commit();

        $updated = fetchFormattedCourts($pdo, 'admin', $currentUserId, $courtId);
        jsonOut(200, [
            'status'  => 'success',
            'message' => 'Đã cập nhật thông tin sân thành công.',
            'data'    => $updated[0] ?? ['id' => $courtId],
        ]);
    }

    // =====================================================================
    // 4. DELETE — Xóa sân
    // =====================================================================
    if ($method === 'DELETE') {
        $courtId = !empty($_GET['id']) ? (int)$_GET['id'] : (!empty($input['id']) ? (int)$input['id'] : 0);
        if ($courtId <= 0) {
            jsonOut(422, ['status' => 'error', 'message' => 'Thiếu ID sân cần xóa.']);
        }

        $chk = $pdo->prepare("SELECT id, name, owner_id FROM courts WHERE id = ?");
        $chk->execute([$courtId]);
        $existing = $chk->fetch();
        if (!$existing) {
            jsonOut(404, ['status' => 'error', 'message' => "Không tìm thấy sân ID #{$courtId}."]);
        }

        // Phân quyền: Chủ sân chỉ được xóa sân của chính mình
        if ($currentRole !== 'admin' && (int)$existing['owner_id'] !== $currentUserId) {
            jsonOut(403, ['status' => 'error', 'message' => 'Bạn chỉ có quyền xóa sân do mình sở hữu.']);
        }

        $pdo->beginTransaction();
        // Xóa các bản ghi phụ thuộc trong booking_slots & bookings trước khi xóa sân
        // để tránh lỗi khóa ngoại ON DELETE RESTRICT
        $pdo->prepare("
            DELETE bs FROM booking_slots bs
            JOIN time_slots ts ON ts.id = bs.slot_id
            WHERE ts.court_id = ?
        ")->execute([$courtId]);
        $pdo->prepare("
            DELETE bs FROM booking_slots bs
            JOIN bookings b ON b.id = bs.booking_id
            WHERE b.court_id = ?
        ")->execute([$courtId]);
        $pdo->prepare("DELETE FROM bookings WHERE court_id = ?")->execute([$courtId]);
        $pdo->prepare("DELETE FROM courts WHERE id = ?")->execute([$courtId]);
        $pdo->commit();

        jsonOut(200, [
            'status'  => 'success',
            'message' => 'Đã xóa sân thành công.',
            'data'    => ['id' => $courtId],
        ]);
    }

    jsonOut(405, ['status' => 'error', 'message' => 'Phương thức HTTP không được hỗ trợ.']);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[admin/courts.php] ' . $e->getMessage());
    jsonOut(500, ['status' => 'error', 'message' => 'Lỗi máy chủ khi xử lý dữ liệu sân: ' . $e->getMessage()]);
}
