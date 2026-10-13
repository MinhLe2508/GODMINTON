<?php
/**
 * api/bookings.php
 * POST: Tạo booking thật — Kiểm tra slot holding bởi đúng user, tạo bản ghi bookings + booking_slots,
 *       chuyển slot holding → booked trong cùng Transaction ★★★
 * GET : Lấy danh sách booking của user đang đăng nhập (lịch sử đặt sân).
 *
 * Người phụ trách: Minh (Tuần 2 & Tuần 3)
 */
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

function jsonOut(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

// =========================================================================
// 1. XỬ LÝ PHƯƠNG THỨC GET: Lấy danh sách lịch sử đặt sân của user
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$currentUserId) {
        jsonOut(401, ['status' => 'error', 'message' => 'Vui lòng đăng nhập để xem lịch sử đặt sân.']);
    }

    try {
        $stmt = $pdo->prepare("
            SELECT b.id AS booking_id,
                   b.court_id,
                   c.name AS court_name,
                   c.address AS court_address,
                   b.total_price,
                   b.deposit_amount,
                   b.status AS booking_status,
                   b.created_at,
                   -- Gom thông tin slots
                   GROUP_CONCAT(DISTINCT s.sub_court_name SEPARATOR ', ') AS sub_courts,
                   MIN(s.slot_date) AS booking_date,
                   MIN(s.start_time) AS start_time,
                   MAX(s.end_time) AS end_time
            FROM bookings b
            JOIN courts c ON c.id = b.court_id
            JOIN booking_slots bs ON bs.booking_id = b.id
            JOIN time_slots s ON s.id = bs.slot_id
            WHERE b.user_id = ?
            GROUP BY b.id
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$currentUserId]);
        $rows = $stmt->fetchAll();

        $bookings = [];
        foreach ($rows as $r) {
            $dateCode = str_replace('-', '', $r['booking_date'] ?? date('Ymd'));
            $startShort = substr($r['start_time'], 0, 5);
            $endShort   = substr($r['end_time'], 0, 5);

            $bookings[] = [
                'id' => (int)$r['booking_id'],
                'booking_code' => 'GDM-' . $dateCode . '-' . str_pad($r['booking_id'], 4, '0', STR_PAD_LEFT),
                'court_id' => (int)$r['court_id'],
                'court_name' => $r['court_name'],
                'court_address' => $r['court_address'],
                'sub_court_name' => $r['sub_courts'],
                'booking_date' => $r['booking_date'],
                'time_range' => "{$startShort} - {$endShort}",
                'total_amount' => (float)$r['total_price'],
                'deposit_amount' => (float)$r['deposit_amount'],
                'booking_status' => $r['booking_status'],
                'created_at' => $r['created_at']
            ];
        }

        jsonOut(200, ['status' => 'success', 'data' => $bookings]);
    } catch (Exception $e) {
        jsonOut(500, ['status' => 'error', 'message' => 'Lỗi máy chủ: ' . $e->getMessage()]);
    }
}

// =========================================================================
// 2. XỬ LÝ PHƯƠNG THỨC POST: Tạo booking thật từ slot đang giữ chỗ (holding)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(405, ['status' => 'error', 'message' => 'Phương thức không được hỗ trợ.']);
}

if (!$currentUserId) {
    jsonOut(401, [
        'status' => 'error',
        'code' => 401,
        'message' => 'Vui lòng đăng nhập để hoàn tất đặt sân.'
    ]);
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$courtId = isset($input['court_id']) ? (int)$input['court_id'] : 0;
$paymentMethod = !empty($input['payment_method']) ? trim($input['payment_method']) : 'VietQR';
$notes = isset($input['notes']) ? trim($input['notes']) : '';

// Hỗ trợ cả `slot_id` đơn lẻ hoặc mảng `slot_ids`
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

if (empty($slotIds)) {
    jsonOut(422, ['status' => 'error', 'message' => 'Thiếu thông tin khung giờ (slot_id) cần đặt.']);
}

try {
    // Bắt đầu Database Transaction
    $pdo->beginTransaction();

    $totalPrice = 0;
    $firstSlot = null;
    $subCourtNames = [];
    $lockedSlots = [];

    // Duyệt qua từng slot để khóa dòng và kiểm tra điều kiện
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
            jsonOut(404, ['status' => 'error', 'message' => "Không tìm thấy khung giờ ID #{$sid}."]);
        }

        if ($firstSlot === null) {
            $firstSlot = $slot;
            if ($courtId === 0) {
                $courtId = (int)$slot['court_id'];
            }
        }

        // KIỂM TRA QUAN TRỌNG:
        // 1. Slot phải đang ở trạng thái 'holding'
        if ($slot['status'] !== 'holding') {
            $pdo->rollBack();
            jsonOut(409, [
                'status' => 'error',
                'message' => "Khung giờ {$slot['sub_court_name']} ({$slot['start_time']}) chưa được giữ chỗ hoặc đã được đặt trước."
            ]);
        }

        // 2. Slot phải được giữ bởi chính user này
        if ((int)$slot['held_by'] !== $currentUserId) {
            $pdo->rollBack();
            jsonOut(403, [
                'status' => 'error',
                'message' => "Khung giờ {$slot['sub_court_name']} ({$slot['start_time']}) đang được giữ bởi tài khoản khác."
            ]);
        }

        // 3. Thời gian giữ chỗ phải còn hiệu lực (chưa quá 5 phút)
        $expireTs = !empty($slot['hold_expires_at']) ? strtotime($slot['hold_expires_at']) : 0;
        if ($expireTs < time()) {
            $pdo->rollBack();
            jsonOut(409, [
                'status' => 'error',
                'message' => "Phiên giữ chỗ của khung giờ {$slot['sub_court_name']} ({$slot['start_time']}) đã hết hạn 5 phút. Vui lòng chọn lại!"
            ]);
        }

        // Tính tiền slot này: Mỗi ô 30 phút = price_per_hour / 2, Giờ cao điểm (17h-21h) + 7.500đ
        $startShort = substr($slot['start_time'], 0, 5);
        $startHour  = (int)substr($startShort, 0, 2);
        $p = round((float)$slot['price_per_hour'] / 2);
        if ($startHour >= 17 && $startHour <= 21) {
            $p += 7500;
        }
        $totalPrice += $p;

        $subCourtNames[] = $slot['sub_court_name'];
        $lockedSlots[] = $slot;
    }

    // Tiền cọc tối thiểu 50% tổng tiền
    $depositAmount = round($totalPrice * 0.5);

    // 1. Tạo bản ghi trong bảng bookings
    $insertBooking = $pdo->prepare("
        INSERT INTO bookings (user_id, court_id, total_price, deposit_amount, status, created_at)
        VALUES (?, ?, ?, ?, 'confirmed', NOW())
    ");
    $insertBooking->execute([
        $currentUserId,
        $courtId,
        $totalPrice,
        $depositAmount
    ]);
    $bookingId = (int)$pdo->lastInsertId();

    // 2. Tạo các bản ghi trong booking_slots và cập nhật time_slots sang 'booked'
    $insertSlot = $pdo->prepare("INSERT INTO booking_slots (booking_id, slot_id) VALUES (?, ?)");
    $updateSlot = $pdo->prepare("
        UPDATE time_slots 
        SET status = 'booked', held_by = NULL, hold_expires_at = NULL 
        WHERE id = ?
    ");

    foreach ($slotIds as $sid) {
        $insertSlot->execute([$bookingId, $sid]);
        $updateSlot->execute([$sid]);
    }

    // Commit Transaction an toàn
    $pdo->commit();

    // Sinh mã đơn hàng
    $datePart = str_replace('-', '', $firstSlot['slot_date'] ?? date('Ymd'));
    $bookingCode = 'GDM-' . $datePart . '-' . str_pad($bookingId, 4, '0', STR_PAD_LEFT);

    $timeRange = substr($firstSlot['start_time'], 0, 5) . ' – ' . substr(end($lockedSlots)['end_time'], 0, 5);
    $subCourtStr = implode(', ', array_unique($subCourtNames));

    jsonOut(201, [
        'status' => 'success',
        'message' => 'Đặt sân và thanh toán cọc thành công!',
        'data' => [
            'booking_id' => $bookingId,
            'booking_code' => $bookingCode,
            'court_id' => $courtId,
            'court_name' => $firstSlot['court_name'],
            'sub_court_name' => $subCourtStr,
            'booking_date' => $firstSlot['slot_date'],
            'time_range' => $timeRange,
            'start_time' => substr($firstSlot['start_time'], 0, 5),
            'end_time' => substr(end($lockedSlots)['end_time'], 0, 5),
            'total_price' => $totalPrice,
            'deposit_amount' => $depositAmount,
            'deposit_paid' => $depositAmount,
            'remain_amount' => $totalPrice - $depositAmount,
            'payment_method' => $paymentMethod,
            'booking_status' => 'confirmed',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonOut(500, [
        'status' => 'error',
        'message' => 'Lỗi máy chủ khi tạo đơn đặt sân: ' . $e->getMessage()
    ]);
}
