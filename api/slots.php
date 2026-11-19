<?php

require_once __DIR__ . '/../includes/auth-session.php';
/**
 * api/slots.php
 * GET danh sách khung giờ theo sân và ngày từ MySQL.
 * Hỗ trợ:
 *   - GET ?court_id=1&date=2026-10-14 : Lấy danh sách slots của sân trong ngày
 *   - GET ?id=101                      : Lấy chi tiết 1 slot
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/payment-service.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không được hỗ trợ. Chỉ chấp nhận GET.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    god_auth_start();
    god_auth_user($pdo);
}

$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

try {
    // 1. Tự động dọn dẹp các slot 'holding' đã hết hạn và đơn quá hạn
    payment_expire_overdue($pdo, 10);
    $cleanupStmt = $pdo->prepare("
        UPDATE time_slots 
        SET status = 'available', held_by = NULL, hold_expires_at = NULL 
        WHERE status = 'holding' 
          AND hold_expires_at IS NOT NULL 
          AND hold_expires_at < NOW()
    ");
    $cleanupStmt->execute();

    // 2. Trường hợp lấy 1 slot cụ thể theo id (dùng trong confirm.js)
    if (!empty($_GET['id'])) {
        $slotId = (int) $_GET['id'];
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   c.name AS court_name, 
                   c.address AS court_address, 
                   c.price_per_hour,
                   TIMESTAMPDIFF(SECOND, NOW(), s.hold_expires_at) AS sec_left
            FROM time_slots s
            JOIN courts c ON c.id = s.court_id
            WHERE s.id = ?
        ");
        $stmt->execute([$slotId]);
        $slot = $stmt->fetch();

        if (!$slot) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy khung giờ.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $secondsLeft = ($slot['status'] === 'holding' && !empty($slot['hold_expires_at']))
            ? max(0, (int) ($slot['sec_left'] ?? 0))
            : 0;

        $startShort = substr($slot['start_time'], 0, 5);
        $endShort = substr($slot['end_time'], 0, 5);

        // Tính giá tiền: Mỗi ô 30 phút = price_per_hour / 2, Giờ cao điểm (17h-21h) + 7.500đ
        $startHour = (int) substr($startShort, 0, 2);
        $slotPrice = round((float) $slot['price_per_hour'] / 2);
        if ($startHour >= 17 && $startHour <= 21) {
            $slotPrice += 7500;
        }

        $nowDt = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $todayStr = $nowDt->format('Y-m-d');
        $nowTimeStr = $nowDt->format('H:i:s');
        $isPast = ($slot['slot_date'] < $todayStr) || ($slot['slot_date'] === $todayStr && $slot['start_time'] <= $nowTimeStr);

        $isMyHold = ($currentUserId && $slot['status'] === 'holding' && (int) $slot['held_by'] === $currentUserId && $secondsLeft > 0);

        echo json_encode([
            'status' => 'success',
            'data' => [
                'id' => (int) $slot['id'],
                'court_id' => (int) $slot['court_id'],
                'court_name' => $slot['court_name'],
                'court_address' => $slot['court_address'],
                'sub_court_name' => $slot['sub_court_name'],
                'slot_date' => $slot['slot_date'],
                'start_time' => $startShort,
                'end_time' => $endShort,
                'time_range' => "{$startShort} - {$endShort}",
                'price' => $slotPrice,
                'status' => $slot['status'],
                'note' => null,
                'is_past' => $isPast,
                'held_by' => $slot['held_by'] ? (int) $slot['held_by'] : null,
                'holding_user_id' => $slot['held_by'] ? (int) $slot['held_by'] : null,
                'hold_expires_at' => $slot['hold_expires_at'],
                'is_my_hold' => $isMyHold,
                'hold_seconds_left' => $secondsLeft
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 3. Trường hợp lấy danh sách slots của 1 sân theo ngày
    $courtId = isset($_GET['court_id']) ? (int) $_GET['court_id'] : 1;
    $dateStr = $_GET['date'] ?? date('Y-m-d');
    if (!is_string($dateStr) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Ngày không hợp lệ.']);
        exit;
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $dateStr);
    if (!$parsed || $parsed->format('Y-m-d') !== $dateStr) {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Ngày không hợp lệ.']);
        exit;
    }

    // Lấy thông tin sân
    $courtStmt = $pdo->prepare("SELECT id, name, address, price_per_hour FROM courts WHERE id = ? AND status='active' AND booking_open=1");
    $courtStmt->execute([$courtId]);
    $court = $courtStmt->fetch();

    if (!$court) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Sân không hoạt động hoặc đang tạm ngưng nhận đặt.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Kiểm tra số lượng slots hiện có cho ngày này
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM time_slots WHERE court_id = ? AND slot_date = ?");
    $countStmt->execute([$courtId, $dateStr]);
    $slotCount = (int) $countStmt->fetchColumn();

    // Nếu ngày đó chưa có slots trong DB -> Tự động khởi tạo (auto-seed) cho 4 thảm theo block 30 phút
    if ($slotCount === 0) {
        $subCourts = ['Thảm A1', 'Thảm A2', 'Thảm A3', 'Thảm A4'];
        $insertStmt = $pdo->prepare("
            INSERT IGNORE INTO time_slots (court_id, sub_court_name, slot_date, start_time, end_time, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $curMin = 5 * 60 + 30;  // 05:30
        $endMin = 23 * 60 + 30; // 23:30
        while ($curMin < $endMin) {
            $nextMin = $curMin + 30;
            $startStr = sprintf('%02d:%02d:00', floor($curMin / 60), $curMin % 60);
            $endStr = sprintf('%02d:%02d:00', floor($nextMin / 60), $nextMin % 60);

            foreach ($subCourts as $sub) {
                $initStatus = 'available';

                $insertStmt->execute([
                    $courtId,
                    $sub,
                    $dateStr,
                    $startStr,
                    $endStr,
                    $initStatus
                ]);
            }

            $curMin = $nextMin;
        }
    }

    // Truy vấn tất cả slots của ngày này (tính trực tiếp số giây còn lại bằng MySQL engine)
    $slotsStmt = $pdo->prepare("
        SELECT id, court_id, sub_court_name, slot_date, start_time, end_time, status, note, price, held_by, hold_expires_at,
               TIMESTAMPDIFF(SECOND, NOW(), hold_expires_at) AS sec_left
        FROM time_slots
        WHERE court_id = ? AND slot_date = ?
        ORDER BY sub_court_name ASC, start_time ASC
    ");
    $slotsStmt->execute([$courtId, $dateStr]);
    $rawSlots = $slotsStmt->fetchAll();

    $data = [];
    $baseSlotPrice = round((float) $court['price_per_hour'] / 2);

    $nowDt = new DateTimeImmutable('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
    $todayStr = $nowDt->format('Y-m-d');
    $nowTimeStr = $nowDt->format('H:i:s');

    foreach ($rawSlots as $s) {
        $secondsLeft = ($s['status'] === 'holding' && !empty($s['hold_expires_at']))
            ? max(0, (int) ($s['sec_left'] ?? 0))
            : 0;

        $startShort = substr($s['start_time'], 0, 5);
        $endShort = substr($s['end_time'], 0, 5);
        $startHour = (int) substr($startShort, 0, 2);

        $price = (!empty($s['price']) && (float) $s['price'] > 0) ? (float) $s['price'] : $baseSlotPrice;
        if ((empty($s['price']) || (float) $s['price'] <= 0) && ($startHour >= 17 && $startHour <= 21)) {
            $price += 7500; // Phụ thu giờ cao điểm nếu không có giá riêng
        }

        $isMyHold = ($currentUserId && $s['status'] === 'holding' && (int) $s['held_by'] === $currentUserId && $secondsLeft > 0);
        $isPast = ($s['slot_date'] < $todayStr) || ($s['slot_date'] === $todayStr && $s['start_time'] <= $nowTimeStr);

        $data[] = [
            'id' => (int) $s['id'],
            'court_id' => (int) $s['court_id'],
            'sub_court_name' => $s['sub_court_name'],
            'slot_date' => $s['slot_date'],
            'start_time' => $startShort,
            'end_time' => $endShort,
            'time_range' => "{$startShort} - {$endShort}",
            'price' => (float) $price,
            'status' => $s['status'],
            'note' => null,
            'is_past' => $isPast,
            'held_by' => $s['held_by'] ? (int) $s['held_by'] : null,
            'holding_user_id' => $s['held_by'] ? (int) $s['held_by'] : null,
            'hold_expires_at' => $s['hold_expires_at'],
            'is_my_hold' => $isMyHold,
            'hold_seconds_left' => $secondsLeft
        ];
    }

    echo json_encode([
        'status' => 'success',
        'court' => [
            'id' => (int) $court['id'],
            'name' => $court['name'],
            'address' => $court['address'],
            'price_per_hour' => (float) $court['price_per_hour']
        ],
        'date' => $dateStr,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi máy chủ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
