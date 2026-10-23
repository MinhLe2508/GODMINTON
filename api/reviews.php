<?php
/**
 * api/reviews.php — Đánh giá sân.
 *
 * GET  ?court_id=N : danh sách đánh giá, điểm trung bình, và quyền của người xem
 *                    (đã đăng nhập? đã từng đặt sân? đánh giá của chính mình).
 * POST {court_id, rating(1-5), comment} + header X-GODMINTON-CSRF :
 *      tạo hoặc sửa đánh giá của chính mình (mỗi người 1 đánh giá / sân).
 *      Chỉ người có đơn đặt sân đã xác nhận/hoàn thành tại sân đó mới được đánh giá.
 */
require_once __DIR__ . '/../includes/booking-account-api.php';

const REVIEW_BOOKING_STATUSES = "('confirmed','completed')";

function review_court_exists(PDO $pdo, int $courtId): bool {
    $s = $pdo->prepare("SELECT 1 FROM courts WHERE id=? AND approval_status='approved' LIMIT 1");
    $s->execute([$courtId]);
    return (bool) $s->fetchColumn();
}
function review_has_booked(PDO $pdo, int $userId, int $courtId): bool {
    $s = $pdo->prepare("SELECT 1 FROM bookings WHERE user_id=? AND court_id=? AND status IN " . REVIEW_BOOKING_STATUSES . " LIMIT 1");
    $s->execute([$userId, $courtId]);
    return (bool) $s->fetchColumn();
}
function review_payload(PDO $pdo, int $courtId, ?array $user): array {
    $sum = $pdo->prepare("SELECT COUNT(*) AS total, COALESCE(AVG(r.rating),0) AS average
        FROM reviews r JOIN users u ON u.id=r.user_id AND u.deleted_at IS NULL
        WHERE r.court_id=?");
    $sum->execute([$courtId]);
    $row = $sum->fetch();
    $dist = array_fill(1, 5, 0);
    $d = $pdo->prepare("SELECT r.rating, COUNT(*) c FROM reviews r JOIN users u ON u.id=r.user_id AND u.deleted_at IS NULL WHERE r.court_id=? GROUP BY r.rating");
    $d->execute([$courtId]);
    foreach ($d->fetchAll() as $x) $dist[(int) $x['rating']] = (int) $x['c'];

    $list = $pdo->prepare("SELECT r.id, r.user_id, r.rating, r.comment, r.created_at, r.updated_at, u.name
        FROM reviews r JOIN users u ON u.id=r.user_id AND u.deleted_at IS NULL
        WHERE r.court_id=? ORDER BY r.updated_at DESC, r.id DESC LIMIT 100");
    $list->execute([$courtId]);
    $uid = $user['id'] ?? 0;
    $mine = null;
    $items = [];
    foreach ($list->fetchAll() as $r) {
        $item = [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'rating' => (int) $r['rating'],
            'comment' => $r['comment'],
            'created_at' => $r['created_at'],
            'edited' => $r['updated_at'] !== $r['created_at'],
            'is_mine' => $uid && (int) $r['user_id'] === $uid,
        ];
        if ($item['is_mine']) $mine = $item;
        $items[] = $item;
    }
    return [
        'summary' => ['average' => round((float) $row['average'], 1), 'total' => (int) $row['total'], 'distribution' => $dist],
        'reviews' => $items,
        'viewer' => [
            'logged_in' => (bool) $user,
            'can_review' => $user ? review_has_booked($pdo, $uid, $courtId) : false,
            'my_review' => $mine,
        ],
    ];
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        $courtId = booking_account_id($_GET['court_id'] ?? 0);
        if (!review_court_exists($pdo, $courtId)) booking_account_out(404, null, 'Không tìm thấy sân.');
        $data = review_payload($pdo, $courtId, booking_account_user(true));
        $data['csrf_token'] = $_SESSION['csrf_token'];
        booking_account_out(200, $data);
    }
    if ($method !== 'POST') { header('Allow: GET, POST'); booking_account_out(405, null, 'Chỉ hỗ trợ GET/POST.'); }

    $user = booking_account_user();
    booking_account_csrf();
    $in = booking_account_input();
    $courtId = booking_account_id($in['court_id'] ?? 0);
    $rating = $in['rating'] ?? null;
    if (!is_scalar($rating) || !preg_match('/^[1-5]$/', (string) $rating)) booking_account_out(422, null, 'Vui lòng chọn số sao từ 1 đến 5.');
    $comment = is_string($in['comment'] ?? null) ? trim(preg_replace("/\r\n?/", "\n", $in['comment'])) : '';
    $len = mb_strlen($comment, 'UTF-8');
    if ($len < 5) booking_account_out(422, null, 'Bình luận cần ít nhất 5 ký tự.');
    if ($len > 1000) booking_account_out(422, null, 'Bình luận tối đa 1000 ký tự.');
    if (!review_court_exists($pdo, $courtId)) booking_account_out(404, null, 'Không tìm thấy sân.');
    if (!review_has_booked($pdo, $user['id'], $courtId)) booking_account_out(403, null, 'Chỉ khách đã từng đặt sân này mới được đánh giá.');

    // UNIQUE(court_id,user_id): lần đầu thì thêm, lần sau thì cập nhật đánh giá cũ.
    $pdo->prepare("INSERT INTO reviews (court_id,user_id,rating,comment) VALUES (?,?,?,?)
        ON DUPLICATE KEY UPDATE rating=VALUES(rating), comment=VALUES(comment), updated_at=CURRENT_TIMESTAMP")
        ->execute([$courtId, $user['id'], (int) $rating, $comment]);

    $data = review_payload($pdo, $courtId, $user);
    $data['csrf_token'] = $_SESSION['csrf_token'];
    booking_account_out(200, $data, 'Đã lưu đánh giá của bạn.');
} catch (PDOException $e) {
    if ((int) ($e->errorInfo[1] ?? 0) === 1146) { error_log('[GODMINTON reviews] ' . $e->getMessage()); booking_account_out(500, null, 'Chưa có bảng reviews. Hãy import database/migration_reviews.sql.'); }
    booking_account_error($e);
} catch (Throwable $e) { booking_account_error($e); }
