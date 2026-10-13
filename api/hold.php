<?php
/**
 * api/hold.php
 * POST giữ chỗ 5 phút — transaction + SELECT ... FOR UPDATE ★★★ (Task 7)
 *
 * Người phụ trách: Huy (Tuần 2)
 * Hợp đồng API: booking_api_contract.md, mục 4.
 *
 * Request  (JSON): { "slot_id": 142 }
 * Response (JSON): { status, code, message, data }
 *   200  giữ chỗ thành công   → data: { slot_id, hold_expires_at (ISO 8601 +07:00), expires_in_seconds }
 *   400  thiếu/sai slot_id (hoặc khung giờ đã qua)
 *   401  chưa đăng nhập
 *   404  không tìm thấy khung giờ (hoặc sân đã ngưng hoạt động)
 *   405  sai phương thức (chỉ POST)
 *   409  slot đang được người khác giữ / đã được đặt
 *   500  lỗi hệ thống
 *
 * Chống tranh chấp: 2 người bấm giữ cùng 1 slot cùng lúc → SELECT ... FOR UPDATE khoá dòng,
 * người đến sau phải chờ người trước COMMIT rồi mới đọc lại trạng thái → chỉ 1 người thành công.
 */
require_once __DIR__ . '/../config/db.php'; // cung cấp $pdo (đã set múi giờ +07:00)

header('Content-Type: application/json; charset=utf-8');

const HOLD_MINUTES = 5;

function respond(int $code, string $message, array $data = []): void
{
    http_response_code($code);
    $body = [
        'status'  => $code === 200 ? 'success' : 'error',
        'code'    => $code,
        'message' => $message,
    ];
    if ($data) {
        $body['data'] = $data;
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 1. Phương thức + đăng nhập -------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    respond(405, 'Chỉ chấp nhận phương thức POST.');
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
session_write_close(); // nhả khoá session sớm để không chặn các request song song của cùng user
if ($userId <= 0) {
    respond(401, 'Vui lòng đăng nhập để thực hiện.');
}

// --- 2. Đọc + kiểm tra đầu vào ---------------------------------------------------
$input  = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST; // cho phép form-urlencoded khi test nhanh bằng Postman
}
$rawId = $input['slot_id'] ?? null;
if ($rawId === null || $rawId === '' || filter_var($rawId, FILTER_VALIDATE_INT) === false || (int)$rawId <= 0) {
    respond(400, 'Thiếu tham số slot_id.');
}
$slotId = (int)$rawId;

// --- 3. Transaction giữ chỗ ------------------------------------------------------
try {
    $pdo->beginTransaction();

    // Khoá dòng slot. Mọi so sánh thời gian tính bằng NOW() của MySQL (một nguồn thời gian duy nhất).
    $stmt = $pdo->prepare(
        "SELECT ts.id, ts.status, ts.held_by,
                DATE_FORMAT(ts.start_time, '%H:%i') AS start_hm,
                DATE_FORMAT(ts.end_time,   '%H:%i') AS end_hm,
                (ts.status = 'holding' AND ts.hold_expires_at IS NOT NULL
                    AND ts.hold_expires_at < NOW())                          AS hold_expired,
                (TIMESTAMP(ts.slot_date, ts.start_time) < NOW())             AS is_past,
                c.status AS court_status
           FROM time_slots ts
           JOIN courts c ON c.id = ts.court_id
          WHERE ts.id = ?
          FOR UPDATE"
    );
    $stmt->execute([$slotId]);
    $slot = $stmt->fetch();

    if (!$slot || $slot['court_status'] !== 'active') {
        $pdo->rollBack();
        respond(404, 'Không tìm thấy khung giờ.');
    }

    $range = $slot['start_hm'] . '-' . $slot['end_hm'];

    if ($slot['status'] === 'booked') {
        $pdo->rollBack();
        respond(409, "Khung giờ {$range} đã được đặt. Vui lòng chọn khung giờ khác!");
    }

    if ($slot['is_past']) {
        $pdo->rollBack();
        respond(400, "Khung giờ {$range} đã qua, không thể giữ chỗ.");
    }

    // Đang 'holding', còn hạn, của người khác → từ chối.
    // (Hết hạn rồi thì coi như 'available' → cho phép giữ tiếp. Chính mình giữ lại thì gia hạn 5 phút.)
    if ($slot['status'] === 'holding' && !$slot['hold_expired'] && (int)$slot['held_by'] !== $userId) {
        $pdo->rollBack();
        respond(409, "Khung giờ {$range} đã được người khác giữ chỗ. Vui lòng chọn khung giờ khác!");
    }

    $upd = $pdo->prepare(
        "UPDATE time_slots
            SET status = 'holding',
                held_by = ?,
                hold_expires_at = NOW() + INTERVAL " . HOLD_MINUTES . " MINUTE
          WHERE id = ?"
    );
    $upd->execute([$userId, $slotId]);

    // Đọc lại hạn giữ chỗ do chính MySQL tính, trong cùng transaction.
    $chk = $pdo->prepare(
        "SELECT hold_expires_at, TIMESTAMPDIFF(SECOND, NOW(), hold_expires_at) AS secs
           FROM time_slots WHERE id = ?"
    );
    $chk->execute([$slotId]);
    $row = $chk->fetch();

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[hold.php] ' . $e->getMessage());
    respond(500, 'Lỗi hệ thống, vui lòng thử lại.');
}

$expiresIso = (new DateTime($row['hold_expires_at'], new DateTimeZone('Asia/Ho_Chi_Minh')))->format('c');

respond(200, 'Giữ chỗ thành công! Bạn có ' . HOLD_MINUTES . ' phút để thanh toán.', [
    'slot_id'            => $slotId,
    'hold_expires_at'    => $expiresIso,
    'expires_in_seconds' => (int)$row['secs'],
]);
