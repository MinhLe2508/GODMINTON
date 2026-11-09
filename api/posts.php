<?php
/**
 * api/posts.php
 * API công khai lấy bài viết phần Khám phá (Sự kiện, Gói hội viên, Thông báo).
 * Chỉ phục vụ phương thức GET cho khách hàng.
 */

require_once __DIR__ . '/../includes/booking-account-api.php';
require_once __DIR__ . '/../includes/post-service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    booking_account_out(405, null, 'Phương thức không được hỗ trợ.');
}

try {
    // 0. Nếu có tham số latest_check -> Trả về 5 bài viết xuất bản mới nhất cho hệ thống thông báo
    if (!empty($_GET['latest_check'])) {
        $limit = min(10, max(1, (int)($_GET['limit'] ?? 5)));
        $items = post_get_latest_notifications($pdo, $limit);
        booking_account_out(200, [
            'items' => $items,
            'server_ts' => time()
        ], 'Thành công.');
    }

    // 1. Nếu có tham số slug -> Lấy chi tiết bài viết
    if (isset($_GET['slug']) && trim((string)$_GET['slug']) !== '') {
        $slug = trim((string)$_GET['slug']);
        $post = post_get_public_by_slug($pdo, $slug);

        if (!$post) {
            booking_account_out(404, null, 'Bài viết không tồn tại hoặc đã hết hạn.');
        }

        // Render nội dung an toàn chống XSS
        $post['rendered_content'] = post_render_content($post['content']);
        booking_account_out(200, $post, 'Thành công.');
    }

    // 2. Lấy danh sách bài viết theo bộ lọc
    $type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
    $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 9)));

    $result = post_get_public_list($pdo, [
        'type' => $type,
        'search' => $search,
        'page' => $page,
        'limit' => $limit
    ]);

    booking_account_out(200, $result, 'Thành công.');
} catch (PostError $e) {
    booking_account_out($e->httpCode, null, $e->getMessage());
} catch (Throwable $e) {
    error_log('[GODMINTON api/posts.php] ' . $e->getMessage());
    booking_account_out(500, null, 'Không thể tải bài viết. Vui lòng thử lại sau.');
}
