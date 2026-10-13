<?php
/**
 * api/hold.php
 * POST giữ chỗ 5 phút — Transaction + SELECT...FOR UPDATE ★★★
 * Hỗ trợ giữ 1 slot hoặc nhiều slot liên tiếp (1 tiếng, 1.5 tiếng, 2 tiếng...)
 *
 * Request JSON:
 *   { "slot_id": 101, "action": "hold" }
 *   hoặc
 *   { "slot_ids": [101, 102, 103], "action": "hold" }
 *   hoặc
 *   { "action": "release", "slot_ids": [101, 102] }
 *
 * Người phụ trách: Huy (Tuần 2) & Minh (Tuần 2)
 */
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

function jsonResponse(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(405, ['status' => 'error', 'message' => 'Chỉ chấp nhận phương thức POST.']);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (!$currentUserId) {
    jsonResponse(401, [
        'status' => 'error',
        'code' => 401,
        'message' => 'Vui lòng đăng nhập để thực hiện giữ chỗ sân.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = isset($input['action']) ? strtolower(trim($input['action'])) : 'hold';

// Gom slot_id hoặc slot_ids thành mảng
$slotIds = [];
if (!empty($input['slot_id'])) {
    $slotIds[] = (int)$input['slot_id'];
}
if (!empty($input['slot_ids']) && is_array($input['slot_ids'])) {
    foreach ($input['slot_ids'] as $sid) {
        $slotIds[] = (int)$sid;
    }
}
$slotIds = array_values(array_unique(array_filter($slotIds)));

try {
    $pdo->beginTransaction();

    // 1. Xử lý hành động HỦY GIỮ CHỖ (release)
    if ($action === 'release') {
        if (!empty($slotIds)) {
            $inClause = implode(',', array_fill(0, count($slotIds), '?'));
            $params = array_merge([$currentUserId], $slotIds);
            $relStmt = $pdo->prepare("
                UPDATE time_slots 
                SET status = 'available', held_by = NULL, hold_expires_at = NULL 
                WHERE status = 'holding' AND held_by = ? AND id IN ($inClause)
            ");
            $relStmt->execute($params);
        } else {
            // Nếu không truyền slot cụ thể, giải phóng tất cả slot đang hold của user này
            $relStmt = $pdo->prepare("
                UPDATE time_slots 
                SET status = 'available', held_by = NULL, hold_expires_at = NULL 
                WHERE status = 'holding' AND held_by = ?
            ");
            $relStmt->execute([$currentUserId]);
        }
        $pdo->commit();
        jsonResponse(200, [
            'status' => 'success',
            'message' => 'Đã hủy giữ chỗ thành công.',
            'data' => ['status' => 'available']
        ]);
    }

    // 2. Xử lý hành động GIỮ CHỖ (hold)
    if (empty($slotIds)) {
        $pdo->rollBack();
        jsonResponse(422, ['status' => 'error', 'message' => 'Vui lòng chọn ít nhất 1 khung giờ cần giữ chỗ.']);
    }

    // Nếu trước đó user đang giữ các slot khác, tự động giải phóng trước khi nhận slot mới
    $clearPrev = $pdo->prepare("
        UPDATE time_slots 
        SET status = 'available', held_by = NULL, hold_expires_at = NULL 
        WHERE status = 'holding' AND held_by = ? AND id NOT IN (" . implode(',', array_fill(0, count($slotIds), '?')) . ")
    ");
    $clearPrev->execute(array_merge([$currentUserId], $slotIds));

    $lockedSlots = [];
    $totalPrice = 0;
    $firstSlot = null;

    // Khóa dòng và kiểm tra từng slot bằng SELECT ... FOR UPDATE
    foreach ($slotIds as $sid) {
        $stmt = $pdo->prepare("
            SELECT s.*, c.name AS court_name, c.price_per_hour
            FROM time_slots s
            JOIN courts c ON c.id = s.court_id
            WHERE s.id = ? 
            FOR UPDATE
        ");
        $stmt->execute([$sid]);
        $slot = $stmt->fetch();

        if (!$slot) {
            $pdo->rollBack();
            jsonResponse(404, ['status' => 'error', 'message' => "Không tìm thấy khung giờ ID #{$sid}."]);
        }

        if ($firstSlot === null) $firstSlot = $slot;

        if ($slot['status'] === 'booked') {
            $pdo->rollBack();
            jsonResponse(409, [
                'status' => 'error',
                'code' => 409,
                'message' => "Khung giờ {$slot['start_time']}–{$slot['end_time']} đã có khách hàng khác đặt thành công."
            ]);
        }

        $now = time();
        $expireTs = !empty($slot['hold_expires_at']) ? strtotime($slot['hold_expires_at']) : 0;
        $isHoldingValid = ($slot['status'] === 'holding' && $expireTs >= $now);

        if ($isHoldingValid && (int)$slot['held_by'] !== $currentUserId) {
            $pdo->rollBack();
            jsonResponse(409, [
                'status' => 'error',
                'code' => 409,
                'message' => "Khung giờ {$slot['start_time']}–{$slot['end_time']} đang được khách khác giữ chỗ. Vui lòng chọn khung giờ khác!"
            ]);
        }

        // Tính tiền slot 30 phút
        $startShort = substr($slot['start_time'], 0, 5);
        $startHour  = (int)substr($startShort, 0, 2);
        $p = round((float)$slot['price_per_hour'] / 2);
        if ($startHour >= 17 && $startHour <= 21) {
            $p += 7500;
        }
        $totalPrice += $p;

        $lockedSlots[] = $slot;
    }

    // Cập nhật tất cả các slot sang status = 'holding'
    $holdStmt = $pdo->prepare("
        UPDATE time_slots 
        SET status = 'holding',
            held_by = ?,
            hold_expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE)
        WHERE id = ?
    ");
    foreach ($slotIds as $sid) {
        $holdStmt->execute([$currentUserId, $sid]);
    }

    $pdo->commit();

    $timeRange = substr($firstSlot['start_time'], 0, 5) . ' – ' . substr(end($lockedSlots)['end_time'], 0, 5);
    $durationHours = count($slotIds) * 0.5;

    jsonResponse(200, [
        'status' => 'success',
        'code' => 200,
        'message' => 'Giữ chỗ thành công! Bạn có 5 phút để hoàn tất đặt sân & thanh toán cọc.',
        'data' => [
            'slot_id' => (int)$firstSlot['id'],
            'slot_ids' => $slotIds,
            'court_id' => (int)$firstSlot['court_id'],
            'court_name' => $firstSlot['court_name'],
            'sub_court_name' => $firstSlot['sub_court_name'],
            'slot_date' => $firstSlot['slot_date'],
            'start_time' => substr($firstSlot['start_time'], 0, 5),
            'end_time' => substr(end($lockedSlots)['end_time'], 0, 5),
            'time_range' => $timeRange,
            'duration' => $durationHours,
            'duration_label' => "{$durationHours} giờ (" . count($slotIds) . " ô)",
            'total_price' => $totalPrice,
            'deposit_amount' => round($totalPrice * 0.5),
            'status' => 'holding',
            'expires_in' => 300
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(500, [
        'status' => 'error',
        'message' => 'Lỗi máy chủ khi giữ chỗ: ' . $e->getMessage()
    ]);
}
