<?php
/**
 * api/favorites.php
 * Quản lý danh sách sân cầu lông yêu thích của người dùng.
 * - Hỗ trợ đồng bộ hai chiều giữa MySQL (bảng court_favorites khi đã đăng nhập) và trình duyệt.
 * - GET  /api/favorites.php : Lấy danh sách ID các sân yêu thích của tài khoản hiện tại.
 * - POST /api/favorites.php : Thêm / bỏ yêu thích 1 sân hoặc đồng bộ danh sách từ client.
 */
require_once __DIR__ . '/../includes/auth-session.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');

function fav_json_out(int $code, array $payload): never {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function ensure_court_favorites_schema(PDO $pdo): bool {
    static $ensured = false;
    if ($ensured) {
        return true;
    }
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `court_favorites` (
                `user_id` INT UNSIGNED NOT NULL,
                `court_id` INT UNSIGNED NOT NULL,
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`user_id`, `court_id`),
                KEY `idx_fav_court` (`court_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        $ensured = true;
        return true;
    } catch (Throwable $e) {
        error_log('[GODMINTON court_favorites] ' . $e->getMessage());
        return false;
    }
}

function get_user_favorite_court_ids(PDO $pdo, int $userId): array {
    if ($userId <= 0 || !ensure_court_favorites_schema($pdo)) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT cf.court_id
        FROM court_favorites cf
        JOIN courts c ON c.id = cf.court_id
        WHERE cf.user_id = ? AND c.status = 'active' AND c.approval_status = 'approved'
        ORDER BY cf.created_at DESC
    ");
    $stmt->execute([$userId]);
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

try {
    god_auth_start();
    $user = god_auth_user($pdo);
    $userId = (int)($user['id'] ?? 0);
    session_write_close();

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        $courtIds = $userId > 0 ? get_user_favorite_court_ids($pdo, $userId) : [];
        fav_json_out(200, [
            'status' => 'success',
            'data'   => [
                'logged_in' => $userId > 0,
                'user_id'   => $userId,
                'court_ids' => $courtIds,
            ],
        ]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');
        fav_json_out(405, ['status' => 'error', 'message' => 'Phương thức không được hỗ trợ.']);
    }

    $raw = file_get_contents('php://input');
    $input = $raw !== '' ? json_decode($raw, true) : $_POST;
    if (!is_array($input)) {
        fav_json_out(422, ['status' => 'error', 'message' => 'Dữ liệu JSON không hợp lệ.']);
    }

    $action = strtolower(trim((string)($input['action'] ?? 'toggle')));

    if ($userId <= 0) {
        fav_json_out(200, [
            'status'  => 'success',
            'message' => 'Đã lưu trên trình duyệt.',
            'data'    => [
                'logged_in' => false,
                'user_id'   => 0,
                'court_ids' => [],
            ],
        ]);
    }

    if (!ensure_court_favorites_schema($pdo)) {
        fav_json_out(500, ['status' => 'error', 'message' => 'Không thể khởi tạo bảng sân yêu thích.']);
    }

    if ($action === 'sync') {
        $clientIds = isset($input['court_ids']) && is_array($input['court_ids']) ? $input['court_ids'] : [];
        if (!empty($clientIds)) {
            $ins = $pdo->prepare('INSERT IGNORE INTO court_favorites (user_id, court_id) SELECT ?, id FROM courts WHERE id = ? AND status = "active"');
            foreach ($clientIds as $cid) {
                $cid = (int)$cid;
                if ($cid > 0) {
                    $ins->execute([$userId, $cid]);
                }
            }
        }
        $courtIds = get_user_favorite_court_ids($pdo, $userId);
        fav_json_out(200, [
            'status' => 'success',
            'data'   => [
                'logged_in' => true,
                'user_id'   => $userId,
                'court_ids' => $courtIds,
            ],
        ]);
    }

    $courtId = (int)($input['court_id'] ?? 0);
    if ($courtId <= 0) {
        fav_json_out(422, ['status' => 'error', 'message' => 'ID sân không hợp lệ.']);
    }

    $checkStmt = $pdo->prepare('SELECT 1 FROM court_favorites WHERE user_id = ? AND court_id = ? LIMIT 1');
    $checkStmt->execute([$userId, $courtId]);
    $exists = (bool)$checkStmt->fetchColumn();

    if ($action === 'add' || ($action === 'toggle' && !$exists)) {
        $ins = $pdo->prepare('INSERT IGNORE INTO court_favorites (user_id, court_id) VALUES (?, ?)');
        $ins->execute([$userId, $courtId]);
        $isFavorite = true;
    } else {
        $del = $pdo->prepare('DELETE FROM court_favorites WHERE user_id = ? AND court_id = ?');
        $del->execute([$userId, $courtId]);
        $isFavorite = false;
    }

    $courtIds = get_user_favorite_court_ids($pdo, $userId);
    fav_json_out(200, [
        'status'  => 'success',
        'message' => $isFavorite ? 'Đã thêm vào danh sách sân yêu thích.' : 'Đã bỏ khỏi danh sách sân yêu thích.',
        'data'    => [
            'logged_in'   => true,
            'user_id'     => $userId,
            'court_id'    => $courtId,
            'is_favorite' => $isFavorite,
            'court_ids'   => $courtIds,
        ],
    ]);
} catch (Throwable $e) {
    error_log('[GODMINTON api/favorites] ' . $e->getMessage());
    fav_json_out(500, ['status' => 'error', 'message' => 'Lỗi hệ thống khi xử lý sân yêu thích.']);
}
