<?php
/**
 * api/admin/slots.php
 * Task 14 — Quản lý khung giờ (time_slots) trên MySQL thật + phân quyền chủ sân / admin:
 *   - GET    /api/admin/slots.php?court_id=1&date=YYYY-MM-DD : Lấy danh sách khung giờ theo sân & ngày
 *   - POST   /api/admin/slots.php                            : Tạo khung giờ hàng loạt (Bulk Generate)
 *   - PATCH  /api/admin/slots.php (hoặc PUT / POST action=status) : Đổi trạng thái từng slot (available ↔ blocked)
 *   - DELETE /api/admin/slots.php?id=123 (hoặc POST action=delete): Xóa từng slot (hoặc xóa các slot trống trong ngày)
 *
 * Người phụ trách: Huy (Tuần 3 — Task 14)
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
    session_start();
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
        if ($colStatus && isset($colStatus['Type']) && strpos($colStatus['Type'], "'blocked'") === false) {
            $pdo->exec("
                ALTER TABLE time_slots
                MODIFY COLUMN status ENUM('available', 'holding', 'booked', 'blocked') NOT NULL DEFAULT 'available'
            ");
        }
        $colPrice = $pdo->query("SHOW COLUMNS FROM time_slots LIKE 'price'")->fetch();
        if (!$colPrice) {
            $pdo->exec("ALTER TABLE time_slots ADD COLUMN price DECIMAL(10,0) NULL AFTER end_time");
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
        SELECT id, court_id, sub_court_name, slot_date, start_time, end_time, price, status, held_by, hold_expires_at
        FROM time_slots
        WHERE court_id = ? AND slot_date = ?
        ORDER BY sub_court_name ASC, start_time ASC
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
            'held_by'         => $r['held_by'] ? (int)$r['held_by'] : null,
            'holding_user_id' => $r['held_by'] ? (int)$r['held_by'] : null,
            'hold_expires_at' => $r['hold_expires_at'],
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
if ($method === 'POST' && !empty($input['action'])) {
    $act = strtolower(trim((string)$input['action']));
    if (in_array($act, ['status', 'set_status', 'toggle_status'], true)) {
        $method = 'PATCH';
    } elseif (in_array($act, ['delete', 'remove', 'clear_day'], true)) {
        $method = 'DELETE';
    }
}

try {
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
    // 2. POST — Tạo khung giờ hàng loạt (Bulk Generate)
    // =====================================================================
    if ($method === 'POST') {
        $courtId     = (int)($input['court_id'] ?? 0);
        $dateStr     = trim((string)($input['slot_date'] ?? $input['date'] ?? ''));
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

        $pdo->beginTransaction();

        // Lấy các khung giờ đã tồn tại trong ngày của sân để tránh tạo trùng hoặc chồng lấn giờ
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

        $insStmt = $pdo->prepare("
            INSERT IGNORE INTO time_slots (court_id, sub_court_name, slot_date, start_time, end_time, price, status)
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
                $clash = false;
                foreach ($existingBySub[$subName] ?? [] as $ex) {
                    // Kiểm tra giao nhau: startA < endB && endA > startB
                    if ($slotStartMin < $ex['end'] && $slotEndMin > $ex['start']) {
                        $clash = true;
                        break;
                    }
                }
                if (!$clash) {
                    $insStmt->execute([$courtId, $subName, $dateStr, $sqlStart, $sqlEnd, $slotPrice]);
                    if ($insStmt->rowCount() > 0) {
                        $createdCount++;
                        $existingBySub[$subName][] = ['start' => $slotStartMin, 'end' => $slotEndMin];
                    }
                }
            }
            $cursor += $durationMin;
        }

        $pdo->commit();

        $allSlots = fetchFormattedSlots($pdo, $court, $dateStr);
        jsonOut(200, [
            'status'  => 'success',
            'message' => $createdCount > 0
                ? "Đã tạo thêm {$createdCount} khung giờ mới."
                : 'Tất cả khung giờ trong khoảng đã chọn đều đã tồn tại.',
            'data'    => [
                'createdCount' => $createdCount,
                'total'        => count($allSlots),
                'slots'        => $allSlots,
            ],
        ]);
    }

    // =====================================================================
    // 3. PATCH / PUT — Đổi trạng thái từng khung giờ (available ↔ blocked)
    // =====================================================================
    if ($method === 'PATCH' || $method === 'PUT') {
        $slotId    = (int)($input['slot_id'] ?? $input['id'] ?? $_GET['id'] ?? 0);
        $newStatus = strtolower(trim((string)($input['status'] ?? '')));

        if ($slotId <= 0 || !in_array($newStatus, ['available', 'blocked'], true)) {
            jsonOut(422, ['status' => 'error', 'message' => 'Tham số slot_id hoặc trạng thái không hợp lệ (chỉ hỗ trợ available / blocked).']);
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

        if ($slot['status'] === 'booked') {
            jsonOut(409, ['status' => 'error', 'message' => 'Khung giờ đã có khách đặt — vui lòng xử lý ở mục "Quản lý đơn đặt".']);
        }
        if ($slot['status'] === 'holding') {
            jsonOut(409, ['status' => 'error', 'message' => 'Khung giờ đang được khách giữ chỗ tạm thời, chưa thể đổi trạng thái.']);
        }

        $upd = $pdo->prepare("
            UPDATE time_slots
               SET status = ?, held_by = NULL, hold_expires_at = NULL
             WHERE id = ?
        ");
        $upd->execute([$newStatus, $slotId]);

        jsonOut(200, [
            'status'  => 'success',
            'message' => $newStatus === 'blocked'
                ? 'Đã tạm ngừng nhận đặt khung giờ.'
                : 'Đã mở lại khung giờ cho khách đặt.',
            'data'    => [
                'id'     => $slotId,
                'status' => $newStatus,
            ],
        ]);
    }

    // =====================================================================
    // 4. DELETE — Xóa từng slot hoặc xóa toàn bộ slot trống/khóa trong ngày
    // =====================================================================
    if ($method === 'DELETE') {
        $slotId   = (int)($_GET['id'] ?? $_GET['slot_id'] ?? $input['slot_id'] ?? $input['id'] ?? 0);
        $clearDay = !empty($_GET['clear_day']) || !empty($input['clear_day']);

        // Xóa hàng loạt các slot chưa đặt (available / blocked) của 1 sân trong 1 ngày
        if ($clearDay && $slotId <= 0) {
            $courtId = (int)($_GET['court_id'] ?? $input['court_id'] ?? 0);
            $dateStr = trim((string)($_GET['date'] ?? $input['slot_date'] ?? $input['date'] ?? ''));
            if ($courtId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                jsonOut(422, ['status' => 'error', 'message' => 'Thiếu court_id hoặc ngày cần xóa khung giờ.']);
            }
            assertCourtAccess($pdo, $courtId, $currentUserId, $currentRole);

            $pdo->beginTransaction();
            $pdo->prepare("
                DELETE bs FROM booking_slots bs
                JOIN time_slots ts ON ts.id = bs.slot_id
                WHERE ts.court_id = ? AND ts.slot_date = ? AND ts.status IN ('available', 'blocked')
            ")->execute([$courtId, $dateStr]);

            $del = $pdo->prepare("
                DELETE FROM time_slots
                WHERE court_id = ? AND slot_date = ? AND status IN ('available', 'blocked')
            ");
            $del->execute([$courtId, $dateStr]);
            $deletedCount = $del->rowCount();
            $pdo->commit();

            jsonOut(200, [
                'status'  => 'success',
                'message' => "Đã xóa {$deletedCount} khung giờ chưa có khách đặt.",
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

        $pdo->beginTransaction();
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
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[admin/slots.php] ' . $e->getMessage());
    jsonOut(500, ['status' => 'error', 'message' => 'Lỗi máy chủ khi xử lý khung giờ: ' . $e->getMessage()]);
}
