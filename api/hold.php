<?php
/**
 * api/hold.php
 * POST giữ chỗ 5 phút / huỷ giữ chỗ — Transaction + SELECT ... FOR UPDATE ★★★
 * Hỗ trợ giữ 1 slot hoặc nhiều slot liên tiếp (1 tiếng, 1.5 tiếng, 2 tiếng...).
 *
 * Request JSON (giữ nguyên giao diện mà calendar_booking.js của Minh đang gọi):
 *   { "slot_id": 101, "action": "hold" }
 *   { "slot_ids": [101, 102, 103], "action": "hold" }      (action mặc định là "hold")
 *   { "action": "release", "slot_ids": [101, 102] }         (không có slot_ids → huỷ mọi slot đang giữ)
 *
 * Phản hồi: { status, code, message, data } theo booking_api_contract.md.
 *   200 thành công · 401 chưa đăng nhập · 404 không thấy slot · 405 sai phương thức
 *   409 slot đã bị người khác giữ / đã đặt / bị khoá · 422 thiếu/sai tham số · 500 lỗi hệ thống
 *
 * Người phụ trách: Huy (task 7) — hợp nhất với bản của Minh (nhiều slot + release).
 */
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');

const HOLD_MINUTES = 5;
const MAX_RETRY    = 3;

function jsonResponse(int $code, array $body): void {
    http_response_code($code);
    if (!isset($body['code'])) {
        $body = ['status' => $body['status'] ?? ($code < 400 ? 'success' : 'error'), 'code' => $code] + $body;
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Ngoại lệ nghiệp vụ: bị ném trong transaction để rollback rồi trả lỗi cho client. */
class HoldError extends Exception {
    public function __construct(public int $httpCode, string $message) { parent::__construct($message); }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    jsonResponse(405, ['message' => 'Chỉ chấp nhận phương thức POST.']);
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
session_write_close(); // nhả khoá session để không chặn các request song song của cùng user

if ($currentUserId <= 0) {
    jsonResponse(401, ['message' => 'Vui lòng đăng nhập để thực hiện giữ chỗ sân.']);
}

$input  = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$action = isset($input['action']) ? strtolower(trim((string)$input['action'])) : 'hold';
if (!in_array($action, ['hold', 'release'], true)) {
    jsonResponse(422, ['message' => 'Hành động không hợp lệ (chỉ hỗ trợ hold hoặc release).']);
}

// Gom slot_id / slot_ids thành mảng số nguyên dương, bỏ trùng, sắp tăng dần (thứ tự khoá cố định)
$slotIds = [];
if (!empty($input['slot_id'])) {
    $slotIds[] = (int)$input['slot_id'];
}
if (!empty($input['slot_ids']) && is_array($input['slot_ids'])) {
    foreach ($input['slot_ids'] as $sid) {
        $slotIds[] = (int)$sid;
    }
}
$slotIds = array_values(array_unique(array_filter($slotIds, fn($v) => $v > 0)));
sort($slotIds);

/** Giá 1 ô 30 phút: price_per_hour / 2, giờ cao điểm 17h–21h phụ thu 7.500đ (khớp slots.php / bookings.php). */
function slotPrice(array $slot): int {
    $p = (int)round((float)$slot['price_per_hour'] / 2);
    $h = (int)substr($slot['start_time'], 0, 2);
    return ($h >= 17 && $h <= 21) ? $p + 7500 : $p;
}

/** Thực hiện 1 lần giữ chỗ trong transaction. Trả mảng response data; ném HoldError nếu nghiệp vụ từ chối. */
function doHold(PDO $pdo, int $userId, array $slotIds): array {
    $pdo->beginTransaction();
    try {
        $in = implode(',', array_fill(0, count($slotIds), '?'));

        // Khoá toàn bộ slot theo thứ tự id. hold_expired do MySQL tính (NOW()) — một nguồn thời gian duy nhất.
        $stmt = $pdo->prepare("
            SELECT s.*, c.name AS court_name, c.price_per_hour, c.status AS court_status,
                   (s.status = 'holding' AND s.hold_expires_at IS NOT NULL AND s.hold_expires_at < NOW()) AS hold_expired
            FROM time_slots s
            JOIN courts c ON c.id = s.court_id
            WHERE s.id IN ($in)
            ORDER BY s.id
            FOR UPDATE
        ");
        $stmt->execute($slotIds);
        $rows = $stmt->fetchAll();

        $byId = [];
        foreach ($rows as $r) { $byId[(int)$r['id']] = $r; }
        foreach ($slotIds as $sid) {
            if (!isset($byId[$sid]) || $byId[$sid]['court_status'] !== 'active') {
                throw new HoldError(404, "Không tìm thấy khung giờ ID #{$sid}.");
            }
        }

        $first = $byId[$slotIds[0]];
        foreach ($slotIds as $sid) {
            $s = $byId[$sid];
            if ((int)$s['court_id'] !== (int)$first['court_id'] || $s['slot_date'] !== $first['slot_date']) {
                throw new HoldError(422, 'Các khung giờ phải thuộc cùng một sân và cùng một ngày.');
            }
        }

        $total = 0;
        foreach ($slotIds as $sid) {
            $s     = $byId[$sid];
            $range = substr($s['start_time'], 0, 5) . '–' . substr($s['end_time'], 0, 5);

            if ($s['status'] === 'booked') {
                throw new HoldError(409, "Khung giờ {$s['sub_court_name']} {$range} đã có khách hàng khác đặt thành công.");
            }
            if ($s['status'] === 'blocked') {
                throw new HoldError(409, "Khung giờ {$s['sub_court_name']} {$range} đang tạm ngừng nhận đặt.");
            }
            // Đang holding, còn hạn, của người khác → từ chối. (Hết hạn = coi như trống; của chính mình = gia hạn.)
            if ($s['status'] === 'holding' && !$s['hold_expired'] && (int)$s['held_by'] !== $userId) {
                throw new HoldError(409, "Khung giờ {$s['sub_court_name']} {$range} đang được khách khác giữ chỗ. Vui lòng chọn khung giờ khác!");
            }
            $total += slotPrice($s);
        }

        // Người dùng chọn lại vùng khác → trả các slot cũ họ đang giữ (không thuộc lần chọn này) về available.
        $pdo->prepare("
            UPDATE time_slots SET status = 'available', held_by = NULL, hold_expires_at = NULL
            WHERE status = 'holding' AND held_by = ? AND id NOT IN ($in)
        ")->execute(array_merge([$userId], $slotIds));

        $pdo->prepare("
            UPDATE time_slots
            SET status = 'holding', held_by = ?, hold_expires_at = DATE_ADD(NOW(), INTERVAL " . HOLD_MINUTES . " MINUTE)
            WHERE id IN ($in)
        ")->execute(array_merge([$userId], $slotIds));

        $chk = $pdo->prepare("SELECT hold_expires_at, TIMESTAMPDIFF(SECOND, NOW(), hold_expires_at) AS secs FROM time_slots WHERE id = ?");
        $chk->execute([$slotIds[0]]);
        $exp = $chk->fetch();

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }

    $last = $byId[end($slotIds)];
    $n    = count($slotIds);
    $hrs  = $n * 0.5;
    $startHm = substr($first['start_time'], 0, 5);
    $endHm   = substr($last['end_time'], 0, 5);

    return [
        'slot_id'            => (int)$first['id'],
        'slot_ids'           => $slotIds,
        'court_id'           => (int)$first['court_id'],
        'court_name'         => $first['court_name'],
        'sub_court_name'     => $first['sub_court_name'],
        'slot_date'          => $first['slot_date'],
        'start_time'         => $startHm,
        'end_time'           => $endHm,
        'time_range'         => "{$startHm} – {$endHm}",
        'duration'           => $hrs,
        'duration_label'     => "{$hrs} giờ ({$n} ô)",
        'total_price'        => $total,
        'deposit_amount'     => (int)round($total * 0.5),
        'status'             => 'holding',
        'expires_in'         => (int)$exp['secs'],                 // giữ tên cũ mà frontend đang dùng
        'expires_in_seconds' => (int)$exp['secs'],                 // tên theo hợp đồng API
        'hold_expires_at'    => (new DateTime($exp['hold_expires_at'], new DateTimeZone('Asia/Ho_Chi_Minh')))->format('c'),
    ];
}

try {
    // ---- HUỶ GIỮ CHỖ ----
    if ($action === 'release') {
        if ($slotIds) {
            $in = implode(',', array_fill(0, count($slotIds), '?'));
            $st = $pdo->prepare("UPDATE time_slots SET status='available', held_by=NULL, hold_expires_at=NULL
                                 WHERE status='holding' AND held_by = ? AND id IN ($in)");
            $st->execute(array_merge([$currentUserId], $slotIds));
        } else {
            $st = $pdo->prepare("UPDATE time_slots SET status='available', held_by=NULL, hold_expires_at=NULL
                                 WHERE status='holding' AND held_by = ?");
            $st->execute([$currentUserId]);
        }
        jsonResponse(200, ['message' => 'Đã hủy giữ chỗ thành công.', 'data' => ['status' => 'available']]);
    }

    // ---- GIỮ CHỖ ----
    if (!$slotIds) {
        jsonResponse(422, ['message' => 'Vui lòng chọn ít nhất 1 khung giờ cần giữ chỗ (slot_id hoặc slot_ids).']);
    }

    $data = null;
    for ($attempt = 1; $attempt <= MAX_RETRY; $attempt++) {
        try {
            $data = doHold($pdo, $currentUserId, $slotIds);
            break;
        } catch (PDOException $e) {
            $mysqlCode = (int)($e->errorInfo[1] ?? 0);
            if (in_array($mysqlCode, [1213, 1205], true) && $attempt < MAX_RETRY) {
                usleep(random_int(20000, 80000)); // deadlock / lock timeout → thử lại
                continue;
            }
            throw $e;
        }
    }

    jsonResponse(200, [
        'message' => 'Giữ chỗ thành công! Bạn có ' . HOLD_MINUTES . ' phút để hoàn tất đặt sân & thanh toán cọc.',
        'data'    => $data,
    ]);

} catch (HoldError $e) {
    jsonResponse($e->httpCode, ['message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('[hold.php] ' . $e->getMessage());
    jsonResponse(500, ['message' => 'Lỗi hệ thống, vui lòng thử lại.']);
}
