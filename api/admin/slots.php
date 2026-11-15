<?php
require_once __DIR__.'/../../includes/auth-session.php';
/**
 * api/admin/slots.php
 * Quản lý khung giờ (time_slots) trên MySQL và phân quyền chủ sân / admin:
 *   - GET    /api/admin/slots.php?court_id=1&date=YYYY-MM-DD : Lấy danh sách khung giờ theo sân & ngày
 *   - POST   /api/admin/slots.php                            : Tạo khung giờ hàng loạt (Bulk Generate)
 *   - PATCH  /api/admin/slots.php (hoặc PUT / POST action=status) : Đổi trạng thái từng slot (available ↔ blocked)
 *   - DELETE /api/admin/slots.php?id=123 (hoặc POST action=delete): Xóa từng slot (hoặc xóa các slot trống trong ngày)
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../cleanup-expired.php';
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
    jsonOut(401, ['status' => 'error', 'message' => 'Vui lòng đăng nhập để quản lý khung giờ.']);
}
if (!in_array($currentRole, ['admin', 'owner'], true)) {
    jsonOut(403, ['status' => 'error', 'message' => 'Bạn không có quyền truy cập chức năng quản lý khung giờ.']);
}

/**
 * Đảm bảo bảng time_slots hỗ trợ trạng thái 'blocked' (Ngừng nhận đặt) và cột price.
 */
function ensureSlotsSchema(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;
    $checked = true;

    try {
        $colStatus = $pdo->query("SHOW COLUMNS FROM time_slots LIKE 'status'")->fetch();
        if ($colStatus && isset($colStatus['Type']) && (strpos($colStatus['Type'], "'blocked'") === false || strpos($colStatus['Type'], "'maintenance'") === false)) {
            $pdo->exec("
                ALTER TABLE time_slots
                MODIFY COLUMN status ENUM('available', 'holding', 'booked', 'blocked', 'maintenance') NOT NULL DEFAULT 'available'
            ");
        }
        $colPrice = $pdo->query("SHOW COLUMNS FROM time_slots LIKE 'price'")->fetch();
        if (!$colPrice) {
            $pdo->exec("ALTER TABLE time_slots ADD COLUMN price DECIMAL(10,0) NULL AFTER end_time");
        }
        $colNote = $pdo->query("SHOW COLUMNS FROM time_slots LIKE 'note'")->fetch();
        if (!$colNote) {
            $pdo->exec("ALTER TABLE time_slots ADD COLUMN note VARCHAR(255) NULL AFTER status");
        }
    } catch (Throwable $e) {
        error_log('[admin/slots.php ensureSlotsSchema] ' . $e->getMessage());
    }
}

/**
 * Kiểm tra sân tồn tại và quyền sở hữu của chủ sân / admin.
 */
function assertCourtAccess(PDO $pdo, int $courtId, int $userId, string $role): array {
    $stmt = $pdo->prepare("SELECT id, owner_id, name, price_per_hour, status FROM courts WHERE id = ?");
    $stmt->execute([$courtId]);
    $court = $stmt->fetch();
    if (!$court) {
        jsonOut(404, ['status' => 'error', 'message' => "Không tìm thấy sân cầu lông ID #{$courtId}."]);
    }
    if ($role !== 'admin' && (int)$court['owner_id'] !== $userId) {
        jsonOut(403, ['status' => 'error', 'message' => 'Bạn chỉ có quyền quản lý khung giờ cho sân do mình sở hữu.']);
    }
    return $court;
}

/**
 * Chuyển chuỗi giờ HH:MM[:SS] sang tổng số phút trong ngày.
 */
function timeToMinutes(string $timeStr): int {
    $parts = explode(':', trim($timeStr));
    $h = isset($parts[0]) ? (int)$parts[0] : 0;
    $m = isset($parts[1]) ? (int)$parts[1] : 0;
    return $h * 60 + $m;
}

/**
 * Chuyển số phút trong ngày sang chuỗi HH:MM:SS.
 */
function minutesToSqlTime(int $totalMinutes): string {
    $h = intdiv($totalMinutes, 60);
    $m = $totalMinutes % 60;
    return sprintf('%02d:%02d:00', $h, $m);
}

/**
 * Lấy danh sách khung giờ của 1 sân trong 1 ngày và format chuẩn cho frontend.
 */
function fetchFormattedSlots(PDO $pdo, array $court, string $dateStr): array {
    $stmt = $pdo->prepare("
        SELECT ts.id, ts.court_id, ts.sub_court_name, ts.slot_date, ts.start_time, ts.end_time, ts.price, ts.status, ts.note,
               ts.held_by, ts.hold_expires_at,
               b.id AS booking_id, b.booking_code, b.status AS booking_status,
               u.name AS customer_name, u.phone AS customer_phone
        FROM time_slots ts
        LEFT JOIN booking_slots bs ON bs.slot_id = ts.id
        LEFT JOIN bookings b ON b.id = bs.booking_id AND b.status != 'cancelled'
        LEFT JOIN users u ON u.id = b.user_id
        WHERE ts.court_id = ? AND ts.slot_date = ?
        ORDER BY ts.sub_court_name ASC, ts.start_time ASC
    ");
    $stmt->execute([(int)$court['id'], $dateStr]);
    $rows = $stmt->fetchAll();

    $pricePerHour = (float)$court['price_per_hour'];
    $slots = [];

    foreach ($rows as $r) {
        $startHm = substr($r['start_time'], 0, 5);
        $endHm   = substr($r['end_time'], 0, 5);
        $durMin  = max(30, timeToMinutes($endHm) - timeToMinutes($startHm));

        if ($r['price'] !== null && (float)$r['price'] > 0) {
            $slotPrice = (int)round((float)$r['price']);
        } else {
            $slotPrice = (int)round($pricePerHour * ($durMin / 60));
            $startHour = (int)substr($startHm, 0, 2);
            if ($startHour >= 17 && $startHour <= 21) {
                $slotPrice += (int)round(7500 * ($durMin / 30));
            }
        }

        $slots[] = [
            'id'              => (int)$r['id'],
            'court_id'        => (int)$r['court_id'],
            'court_name'      => $court['name'],
            'sub_court_name'  => $r['sub_court_name'],
            'slot_date'       => $r['slot_date'],
            'start_time'      => $startHm,
            'end_time'        => $endHm,
            'time_range'      => "{$startHm} - {$endHm}",
            'price'           => $slotPrice,
            'price_formatted' => number_format($slotPrice, 0, '', '.') . ' đ',
            'status'          => $r['status'],
            'note'            => $r['note'] ?? null,
            'held_by'         => $r['held_by'] ? (int)$r['held_by'] : null,
            'holding_user_id' => $r['held_by'] ? (int)$r['held_by'] : null,
            'hold_expires_at' => $r['hold_expires_at'],
            'booking_id'      => $r['booking_id'] ? (int)$r['booking_id'] : null,
            'booking_code'    => $r['booking_code'] ?? null,
            'booking_status'  => $r['booking_status'] ?? null,
            'customer_name'   => $r['customer_name'] ?? null,
            'customer_phone'  => $r['customer_phone'] ?? null,
        ];
    }

    return $slots;
}

ensureSlotsSchema($pdo);
cleanup_expired_holds($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

// Hỗ trợ truyền action qua POST cho các môi trường chặn PATCH/DELETE
$action = strtolower(trim((string)($input['action'] ?? '')));
if ($method === 'POST' && !empty($action)) {
    if (in_array($action, ['status', 'set_status', 'toggle_status', 'update'], true)) {
        $method = 'PATCH';
    } elseif (in_array($action, ['delete', 'remove', 'clear_day'], true)) {
        $method = 'DELETE';
    }
}

try {
    require_once __DIR__.'/../../includes/owner-workflow.php';$workflowActor=wf_user();
    if($method!=='GET'){
        booking_account_csrf();$pdo->beginTransaction();wf_lock_actor($workflowActor);
        $workflowCourtId=(int)($input['court_id']??$_GET['court_id']??0);
        if(!$workflowCourtId){$workflowSlotId=(int)($input['slot_id']??$input['id']??$_GET['slot_id']??$_GET['id']??0);$q=$pdo->prepare('SELECT court_id FROM time_slots WHERE id=?');$q->execute([$workflowSlotId]);$workflowCourtId=(int)$q->fetchColumn();}
        $q=$pdo->prepare('SELECT owner_id FROM courts WHERE id=? FOR UPDATE');$q->execute([$workflowCourtId]);$workflowOwner=$q->fetchColumn();
        if($workflowOwner===false || ($workflowActor['role']!=='admin' && (int)$workflowOwner!==$workflowActor['id']))wf_fail(403,'Bạn không còn quyền quản lý khung giờ của sân này.');
    }
    // =====================================================================
    // 1. GET — Danh sách khung giờ theo sân & ngày
    // =====================================================================
    if ($method === 'GET') {
        $courtId = isset($_GET['court_id']) ? (int)$_GET['court_id'] : 0;
        $dateStr = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])
            ? $_GET['date']
            : date('Y-m-d');

        if ($courtId <= 0) {
            jsonOut(422, ['status' => 'error', 'message' => 'Thiếu tham số court_id.']);
        }

        $court = assertCourtAccess($pdo, $courtId, $currentUserId, $currentRole);
        $slots = fetchFormattedSlots($pdo, $court, $dateStr);

        jsonOut(200, [
            'status' => 'success',
            'court'  => [
                'id'             => (int)$court['id'],
                'name'           => $court['name'],
                'price_per_hour' => (int)$court['price_per_hour'],
            ],
            'date'   => $dateStr,
            'data'   => $slots,
            'total'  => count($slots),
        ]);
    }

    // =====================================================================
    // 2. POST — Xử lý các nghiệp vụ hàng loạt (Tạo khung, Bảo trì, Mở lại, Đổi giá)
    // =====================================================================
    if ($method === 'POST') {
        $courtId = (int)($input['court_id'] ?? 0);
        $dateStr = trim((string)($input['slot_date'] ?? $input['date'] ?? ''));

        // --- A. BẢO TRÌ HÀNG LOẠT (BULK MAINTENANCE) ---
        if ($action === 'bulk_maintenance') {
            if ($courtId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                jsonOut(422, ['status' => 'error', 'message' => 'Vui lòng chọn sân và ngày hợp lệ.']);
            }
            $court = assertCourtAccess($pdo, $courtId, $currentUserId, $currentRole);
            $subCourt  = trim((string)($input['sub_court_name'] ?? 'all'));
            $startTime = trim((string)($input['start_time'] ?? ''));
            $endTime   = trim((string)($input['end_time'] ?? ''));
            $statusReq = strtolower(trim((string)($input['status'] ?? 'maintenance')));
            $status    = in_array($statusReq, ['maintenance', 'blocked'], true) ? $statusReq : 'maintenance';
            $note      = trim((string)($input['note'] ?? 'Bảo trì hệ thống'));

            if (!$pdo->inTransaction()) $pdo->beginTransaction();

            $sql = "UPDATE time_slots 
                       SET status = ?, note = ?, held_by = NULL, hold_expires_at = NULL 
                     WHERE court_id = ? AND slot_date = ? AND status IN ('available', 'blocked', 'maintenance')";
            $params = [$status, $note, $courtId, $dateStr];

            if ($subCourt !== '' && $subCourt !== 'all') {
                $sql .= " AND sub_court_name = ?";
                $params[] = $subCourt;
            }
            if ($startTime !== '') {
                $sql .= " AND start_time >= ?";
                $params[] = strlen($startTime) === 5 ? "{$startTime}:00" : $startTime;
            }
            if ($endTime !== '') {
                $sql .= " AND end_time <= ?";
                $params[] = strlen($endTime) === 5 ? "{$endTime}:00" : $endTime;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            $pdo->commit();

            $allSlots = fetchFormattedSlots($pdo, $court, $dateStr);
            jsonOut(200, [
                'status'  => 'success',
                'message' => "Đã chuyển {$affected} khung giờ sang chế độ {$status} thành công.",
                'data'    => [
                    'affected' => $affected,
                    'slots'    => $allSlots
                ]
            ]);
        }

        // --- B. MỞ LẠI NHẬN KHÁCH HÀNG LOẠT (BULK REOPEN) ---
        if ($action === 'bulk_reopen') {
            if ($courtId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                jsonOut(422, ['status' => 'error', 'message' => 'Vui lòng chọn sân và ngày hợp lệ.']);
            }
            $court = assertCourtAccess($pdo, $courtId, $currentUserId, $currentRole);
            $subCourt  = trim((string)($input['sub_court_name'] ?? 'all'));
            $startTime = trim((string)($input['start_time'] ?? ''));
            $endTime   = trim((string)($input['end_time'] ?? ''));

            if (!$pdo->inTransaction()) $pdo->beginTransaction();

            $sql = "UPDATE time_slots 
                       SET status = 'available', note = NULL, held_by = NULL, hold_expires_at = NULL 
                     WHERE court_id = ? AND slot_date = ? AND status IN ('blocked', 'maintenance')";
            $params = [$courtId, $dateStr];

            if ($subCourt !== '' && $subCourt !== 'all') {
                $sql .= " AND sub_court_name = ?";
                $params[] = $subCourt;
            }
            if ($startTime !== '') {
                $sql .= " AND start_time >= ?";
                $params[] = strlen($startTime) === 5 ? "{$startTime}:00" : $startTime;
            }
            if ($endTime !== '') {
                $sql .= " AND end_time <= ?";
                $params[] = strlen($endTime) === 5 ? "{$endTime}:00" : $endTime;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            $pdo->commit();

            $allSlots = fetchFormattedSlots($pdo, $court, $dateStr);
            jsonOut(200, [
                'status'  => 'success',
                'message' => "Đã mở lại {$affected} khung giờ cho khách đặt.",
                'data'    => [
                    'affected' => $affected,
                    'slots'    => $allSlots
                ]
            ]);
        }

        // --- C. ĐIỀU CHỈNH GIÁ HÀNG LOẠT (BULK PRICE) ---
        if ($action === 'bulk_price') {
            if ($courtId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                jsonOut(422, ['status' => 'error', 'message' => 'Vui lòng chọn sân và ngày hợp lệ.']);
            }
            $court = assertCourtAccess($pdo, $courtId, $currentUserId, $currentRole);
            $subCourt = trim((string)($input['sub_court_name'] ?? 'all'));
            $newPrice = isset($input['price']) && is_numeric($input['price']) ? max(0, (int)$input['price']) : null;

            if ($newPrice === null) {
                jsonOut(422, ['status' => 'error', 'message' => 'Vui lòng cung cấp mức giá hợp lệ.']);
            }

            if (!$pdo->inTransaction()) $pdo->beginTransaction();

            $sql = "UPDATE time_slots SET price = ? WHERE court_id = ? AND slot_date = ? AND status != 'booked'";
            $params = [$newPrice, $courtId, $dateStr];

            if ($subCourt !== '' && $subCourt !== 'all') {
                $sql .= " AND sub_court_name = ?";
                $params[] = $subCourt;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $affected = $stmt->rowCount();
            $pdo->commit();

            $allSlots = fetchFormattedSlots($pdo, $court, $dateStr);
            jsonOut(200, [
                'status'  => 'success',
                'message' => "Đã cập nhật giá cho {$affected} khung giờ thành " . number_format($newPrice, 0, '', '.') . ' đ.',
                'data'    => [
                    'affected' => $affected,
                    'slots'    => $allSlots
                ]
            ]);
        }

        // --- D. TẠO KHUNG GIỜ HÀNG LOẠT (BULK GENERATE) ---
        $startTime   = trim((string)($input['start_time'] ?? $input['startTime'] ?? '06:00'));
        $endTime     = trim((string)($input['end_time'] ?? $input['endTime'] ?? '22:00'));
        $durationMin = (int)($input['duration_min'] ?? $input['durationMin'] ?? 60);
        $customPrice = isset($input['price']) && is_numeric($input['price']) ? (int)$input['price'] : null;
        $subCourts   = $input['sub_courts'] ?? $input['subCourts'] ?? [];

        if (is_string($subCourts)) {
            $subCourts = array_filter(array_map('trim', explode(',', $subCourts)));
        }
        if (!is_array($subCourts)) {
            $subCourts = [];
        }
        $subCourts = array_values(array_unique(array_filter(array_map('trim', $subCourts))));

        if ($courtId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            jsonOut(422, ['status' => 'error', 'message' => 'Vui lòng chọn sân và ngày hợp lệ (YYYY-MM-DD).']);
        }
        if (empty($subCourts)) {
            jsonOut(422, ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 sân con để tạo khung giờ.']);
        }
        if ($durationMin < 15 || $durationMin > 360) {
            jsonOut(422, ['status' => 'error', 'message' => 'Thời lượng mỗi khung giờ phải từ 15 đến 360 phút.']);
        }

        $startMin = timeToMinutes($startTime);
        $endMin   = timeToMinutes($endTime);
        if ($startMin >= $endMin || ($startMin + $durationMin) > $endMin) {
            jsonOut(422, ['status' => 'error', 'message' => 'Giờ kết thúc phải lớn hơn giờ bắt đầu ít nhất bằng thời lượng 1 khung giờ.']);
        }

        $court = assertCourtAccess($pdo, $courtId, $currentUserId, $currentRole);
        $slotPrice = ($customPrice !== null && $customPrice >= 0)
            ? $customPrice
            : (int)round((float)$court['price_per_hour'] * ($durationMin / 60));

        if(!$pdo->inTransaction())$pdo->beginTransaction();

        // Lấy các khung giờ đã tồn tại trong ngày của sân để kiểm tra trùng
        $existStmt = $pdo->prepare("
            SELECT sub_court_name, start_time, end_time
            FROM time_slots
            WHERE court_id = ? AND slot_date = ?
            FOR UPDATE
        ");
        $existStmt->execute([$courtId, $dateStr]);
        $existingRows = $existStmt->fetchAll();

        $existingBySub = [];
        foreach ($existingRows as $er) {
            $sub = $er['sub_court_name'];
            $existingBySub[$sub][] = [
                'start' => timeToMinutes($er['start_time']),
                'end'   => timeToMinutes($er['end_time']),
            ];
        }

        // 1. Kiểm tra toàn bộ xem có khung giờ nào của thảm nào bị trùng/đã tồn tại trước không
        $clashes = [];
        $cursor = $startMin;
        while ($cursor + $durationMin <= $endMin) {
            $slotStartMin = $cursor;
            $slotEndMin   = $cursor + $durationMin;
            $previewStart = minutesToSqlTime($slotStartMin);
            $previewEnd   = minutesToSqlTime($slotEndMin);
            $startDisplay = substr($previewStart, 0, 5);
            $endDisplay   = substr($previewEnd, 0, 5);

            foreach ($subCourts as $subName) {
                foreach ($existingBySub[$subName] ?? [] as $ex) {
                    // Kiểm tra giao nhau: startA < endB && endA > startB
                    if ($slotStartMin < $ex['end'] && $slotEndMin > $ex['start']) {
                        $clashes[] = "{$subName} ({$startDisplay}-{$endDisplay})";
                        break;
                    }
                }
            }
            $cursor += $durationMin;
        }

        if (!empty($clashes)) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $clashCount = count($clashes);
            $preview = array_slice($clashes, 0, 4);
            $msg = "Khung giờ hoặc thảm sau đã được tạo trước đó: " . implode(', ', $preview);
            if ($clashCount > 4) {
                $msg .= " và " . ($clashCount - 4) . " khung giờ khác.";
            } else {
                $msg .= ".";
            }
            $msg .= " Vui lòng kiểm tra lại hoặc xóa khung giờ cũ trước khi tạo!";
            jsonOut(409, [
                'status'  => 'error',
                'message' => $msg,
                'clashes' => $clashes,
            ]);
        }

        // 2. Nếu không có khung giờ nào trùng, tiến hành tạo mới toàn bộ
        $insStmt = $pdo->prepare("
            INSERT INTO time_slots (court_id, sub_court_name, slot_date, start_time, end_time, price, status)
            VALUES (?, ?, ?, ?, ?, ?, 'available')
        ");

        $createdCount = 0;
        $cursor = $startMin;
        while ($cursor + $durationMin <= $endMin) {
            $slotStartMin = $cursor;
            $slotEndMin   = $cursor + $durationMin;
            $sqlStart     = minutesToSqlTime($slotStartMin);
            $sqlEnd       = minutesToSqlTime($slotEndMin);

            foreach ($subCourts as $subName) {
                $insStmt->execute([$courtId, $subName, $dateStr, $sqlStart, $sqlEnd, $slotPrice]);
                $createdCount++;
            }
            $cursor += $durationMin;
        }

        $pdo->commit();

        $allSlots = fetchFormattedSlots($pdo, $court, $dateStr);
        jsonOut(200, [
            'status'  => 'success',
            'message' => "Đã tạo thành công {$createdCount} khung giờ mới.",
            'data'    => [
                'createdCount' => $createdCount,
                'total'        => count($allSlots),
                'slots'        => $allSlots,
            ],
        ]);
    }

    // =====================================================================
    // 3. PATCH / PUT — Đổi trạng thái, lý do bảo trì, giá vé hoặc ghi chú nội bộ
    // =====================================================================
    if ($method === 'PATCH' || $method === 'PUT') {
        $slotId    = (int)($input['slot_id'] ?? $input['id'] ?? $_GET['id'] ?? 0);
        $newStatus = isset($input['status']) ? strtolower(trim((string)$input['status'])) : null;
        $hasNote   = array_key_exists('note', $input);
        $newNote   = $hasNote ? trim((string)$input['note']) : null;
        $hasPrice  = array_key_exists('price', $input) && is_numeric($input['price']);
        $newPrice  = $hasPrice ? max(0, (int)$input['price']) : null;

        if ($slotId <= 0) {
            jsonOut(422, ['status' => 'error', 'message' => 'Thiếu tham số slot_id hợp lệ.']);
        }
        if ($newStatus !== null && !in_array($newStatus, ['available', 'blocked', 'maintenance'], true)) {
            jsonOut(422, ['status' => 'error', 'message' => 'Trạng thái không hợp lệ (hỗ trợ: available, blocked, maintenance).']);
        }

        $stmt = $pdo->prepare("
            SELECT s.*, c.owner_id, c.name AS court_name
            FROM time_slots s
            JOIN courts c ON c.id = s.court_id
            WHERE s.id = ?
        ");
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();

        if (!$slot) {
            jsonOut(404, ['status' => 'error', 'message' => "Không tìm thấy khung giờ ID #{$slotId}."]);
        }
        if ($currentRole !== 'admin' && (int)$slot['owner_id'] !== $currentUserId) {
            jsonOut(403, ['status' => 'error', 'message' => 'Bạn không có quyền thay đổi khung giờ của sân này.']);
        }

        // Nếu slot đã được đặt (booked):
        if ($slot['status'] === 'booked') {
            if ($newStatus !== null && $newStatus !== 'booked') {
                jsonOut(409, ['status' => 'error', 'message' => 'Khung giờ đã có khách đặt — không thể thay đổi trạng thái tại đây. Vui lòng xử lý ở mục "Quản lý đơn đặt".']);
            }
            // Cho phép cập nhật ghi chú nội bộ cho khung giờ booked
            if ($hasNote) {
                $upd = $pdo->prepare("UPDATE time_slots SET note = ? WHERE id = ?");
                $upd->execute([$newNote !== '' ? $newNote : null, $slotId]);
                $pdo->commit();
                jsonOut(200, [
                    'status'  => 'success',
                    'message' => 'Đã lưu ghi chú nội bộ cho khung giờ.',
                    'data'    => ['id' => $slotId, 'note' => $newNote]
                ]);
            }
            jsonOut(409, ['status' => 'error', 'message' => 'Khung giờ đã có khách đặt.']);
        }

        if ($slot['status'] === 'holding' && $newStatus !== null) {
            jsonOut(409, ['status' => 'error', 'message' => 'Khung giờ đang được khách giữ chỗ tạm thời, chưa thể đổi trạng thái.']);
        }

        // Cập nhật slot bình thường
        $finalStatus = $newStatus !== null ? $newStatus : $slot['status'];
        $finalNote = $hasNote ? ($newNote !== '' ? $newNote : null) : ($finalStatus === 'available' ? null : $slot['note']);
        $finalPrice = $hasPrice ? $newPrice : $slot['price'];

        $upd = $pdo->prepare("
            UPDATE time_slots
               SET status = ?, note = ?, price = ?, held_by = NULL, hold_expires_at = NULL
             WHERE id = ?
        ");
        $upd->execute([$finalStatus, $finalNote, $finalPrice, $slotId]);
        $pdo->commit();

        $msg = 'Đã cập nhật thông tin khung giờ thành công.';
        if ($newStatus === 'maintenance') {
            $msg = 'Đã chuyển khung giờ sang chế độ bảo trì.';
        } elseif ($newStatus === 'blocked') {
            $msg = 'Đã tạm ngừng nhận đặt khung giờ.';
        } elseif ($newStatus === 'available') {
            $msg = 'Đã mở lại khung giờ cho khách đặt.';
        }

        jsonOut(200, [
            'status'  => 'success',
            'message' => $msg,
            'data'    => [
                'id'     => $slotId,
                'status' => $finalStatus,
                'note'   => $finalNote,
                'price'  => $finalPrice,
            ],
        ]);
    }

    // =====================================================================
    // 4. DELETE — Xóa từng slot hoặc xóa toàn bộ slot trống/khóa trong ngày
    // =====================================================================
    if ($method === 'DELETE') {
        $slotId   = (int)($_GET['id'] ?? $_GET['slot_id'] ?? $input['slot_id'] ?? $input['id'] ?? 0);
        $isBulk = !empty($_GET['bulk_delete']) || !empty($input['bulk_delete']) || !empty($_GET['clear_day']) || !empty($input['clear_day']) || ($slotId <= 0 && (!empty($_GET['court_id']) || !empty($input['court_id'])));

        // Xóa hàng loạt các slot chưa đặt (available / maintenance / blocked) theo thảm và khoảng thời gian
        if ($isBulk) {
            $courtId   = (int)($_GET['court_id'] ?? $input['court_id'] ?? 0);
            $dateStr   = trim((string)($_GET['date'] ?? $input['slot_date'] ?? $input['date'] ?? ''));
            $startTime = trim((string)($_GET['start_time'] ?? $input['start_time'] ?? ''));
            $endTime   = trim((string)($_GET['end_time'] ?? $input['end_time'] ?? ''));
            $subCourts = $_GET['sub_courts'] ?? $input['sub_courts'] ?? $input['subCourts'] ?? [];

            if (is_string($subCourts)) {
                $subCourts = array_filter(array_map('trim', explode(',', $subCourts)));
            }
            if (!is_array($subCourts)) {
                $subCourts = [];
            }
            $subCourts = array_values(array_unique(array_filter(array_map('trim', $subCourts))));

            if ($courtId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                jsonOut(422, ['status' => 'error', 'message' => 'Thiếu court_id hoặc ngày cần xóa khung giờ (YYYY-MM-DD).']);
            }
            assertCourtAccess($pdo, $courtId, $currentUserId, $currentRole);

            $where = [
                "court_id = ?",
                "slot_date = ?",
                "status IN ('available', 'maintenance', 'blocked')"
            ];
            $params = [$courtId, $dateStr];

            if ($startTime !== '') {
                $where[] = "start_time >= ?";
                $params[] = strlen($startTime) === 5 ? "{$startTime}:00" : $startTime;
            }
            if ($endTime !== '') {
                $where[] = "end_time <= ?";
                $params[] = strlen($endTime) === 5 ? "{$endTime}:00" : $endTime;
            }
            if (!empty($subCourts)) {
                $placeholders = implode(',', array_fill(0, count($subCourts), '?'));
                $where[] = "sub_court_name IN ({$placeholders})";
                foreach ($subCourts as $sc) {
                    $params[] = $sc;
                }
            }

            // Bảo vệ khung giờ đã có khách đặt
            $where[] = "NOT EXISTS (SELECT 1 FROM booking_slots bs JOIN bookings b ON b.id = bs.booking_id WHERE bs.slot_id = time_slots.id AND b.status IN ('pending', 'confirmed', 'checked_in', 'completed'))";

            $whereSql = implode(' AND ', $where);

            if(!$pdo->inTransaction())$pdo->beginTransaction();

            $findStmt = $pdo->prepare("SELECT id FROM time_slots WHERE {$whereSql} FOR UPDATE");
            $findStmt->execute($params);
            $targetSlotIds = $findStmt->fetchAll(PDO::FETCH_COLUMN, 0);

            $deletedCount = 0;
            if (!empty($targetSlotIds)) {
                $idPlaceholders = implode(',', array_fill(0, count($targetSlotIds), '?'));
                $pdo->prepare("DELETE FROM booking_slots WHERE slot_id IN ({$idPlaceholders})")->execute($targetSlotIds);
                $del = $pdo->prepare("DELETE FROM time_slots WHERE id IN ({$idPlaceholders})");
                $del->execute($targetSlotIds);
                $deletedCount = $del->rowCount();
            }

            $pdo->commit();

            jsonOut(200, [
                'status'  => 'success',
                'message' => $deletedCount > 0
                    ? "Đã xóa {$deletedCount} khung giờ chưa có khách đặt thành công."
                    : "Không tìm thấy khung giờ trống nào trong phạm vi đã chọn để xóa.",
                'data'    => ['deletedCount' => $deletedCount],
            ]);
        }

        if ($slotId <= 0) {
            jsonOut(422, ['status' => 'error', 'message' => 'Thiếu ID khung giờ cần xóa.']);
        }

        $stmt = $pdo->prepare("
            SELECT s.*, c.owner_id
            FROM time_slots s
            JOIN courts c ON c.id = s.court_id
            WHERE s.id = ?
        ");
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();

        if (!$slot) {
            jsonOut(404, ['status' => 'error', 'message' => "Không tìm thấy khung giờ ID #{$slotId}."]);
        }
        if ($currentRole !== 'admin' && (int)$slot['owner_id'] !== $currentUserId) {
            jsonOut(403, ['status' => 'error', 'message' => 'Bạn không có quyền xóa khung giờ của sân này.']);
        }
        if ($slot['status'] === 'booked') {
            jsonOut(409, ['status' => 'error', 'message' => 'Không thể xóa khung giờ đã có khách đặt.']);
        }
        if ($slot['status'] === 'holding') {
            jsonOut(409, ['status' => 'error', 'message' => 'Khung giờ đang được khách giữ chỗ tạm thời, không thể xóa lúc này.']);
        }

        if(!$pdo->inTransaction())$pdo->beginTransaction();
        $pdo->prepare("DELETE FROM booking_slots WHERE slot_id = ?")->execute([$slotId]);
        $pdo->prepare("DELETE FROM time_slots WHERE id = ?")->execute([$slotId]);
        $pdo->commit();

        jsonOut(200, [
            'status'  => 'success',
            'message' => 'Đã xóa khung giờ thành công.',
            'data'    => ['id' => $slotId],
        ]);
    }

    jsonOut(405, ['status' => 'error', 'message' => 'Phương thức HTTP không được hỗ trợ.']);

} catch (Throwable $e) {
 if($e instanceof BookingError){if($pdo->inTransaction())$pdo->rollBack();jsonOut($e->httpCode,['status'=>'error','message'=>$e->getMessage()]);}
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[admin/slots.php] ' . $e->getMessage());
    jsonOut(500, ['status' => 'error', 'message' => 'Lỗi máy chủ khi xử lý khung giờ: ' . $e->getMessage()]);
}
