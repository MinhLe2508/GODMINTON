<?php
/**
 * api/admin/posts.php
 * API quản lý bài viết phần Khám phá dành riêng cho Quản trị viên (Admin).
 * Chỉ Admin mới có quyền truy cập và thao tác (Khách và Chủ sân đều bị chặn 403).
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/post-service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    // 1. Kiểm tra xác thực và phân quyền Admin ở cấp độ Server
    $user = booking_account_user();
    if (($user['role'] ?? '') !== 'admin') {
        booking_account_out(403, null, 'Chỉ Quản trị viên hệ thống (Admin) mới có quyền quản lý bài viết.');
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // ==========================================
    // GET: Danh sách bài viết hoặc chi tiết 1 bài
    // ==========================================
    if ($method === 'GET') {
        if (isset($_GET['id']) && (int)$_GET['id'] > 0) {
            $id = (int)$_GET['id'];
            $post = post_get_by_id($pdo, $id);
            if (!$post) {
                booking_account_out(404, null, 'Không tìm thấy bài viết yêu cầu.');
            }
            booking_account_out(200, $post, 'Thành công.');
        }

        $type = isset($_GET['type']) ? trim((string)$_GET['type']) : '';
        $status = isset($_GET['status']) ? trim((string)$_GET['status']) : '';
        $search = isset($_GET['search']) ? trim((string)$_GET['search']) : '';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(50, max(1, (int)($_GET['limit'] ?? 15)));

        $result = post_get_admin_list($pdo, [
            'type' => $type,
            'status' => $status,
            'search' => $search,
            'page' => $page,
            'limit' => $limit
        ]);

        booking_account_out(200, $result, 'Thành công.');
    }

    // Các thao tác ghi bắt buộc kiểm tra CSRF token
    booking_account_csrf();
    $input = booking_account_input();

    // ==========================================
    // POST: Tạo bài viết mới
    // ==========================================
    if ($method === 'POST') {
        $newId = post_create($pdo, (int)$user['id'], $input);
        booking_account_out(201, ['id' => $newId], 'Tạo bài viết thành công.');
    }

    // ==========================================
    // PUT: Cập nhật bài viết
    // ==========================================
    if ($method === 'PUT') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            booking_account_out(422, null, 'ID bài viết không hợp lệ.');
        }
        post_update($pdo, $id, $input);
        booking_account_out(200, ['id' => $id], 'Cập nhật bài viết thành công.');
    }

    // ==========================================
    // PATCH: Đổi nhanh trạng thái hoặc ghim bài
    // ==========================================
    if ($method === 'PATCH') {
        $id = (int)($input['id'] ?? 0);
        if ($id <= 0) {
            booking_account_out(422, null, 'ID bài viết không hợp lệ.');
        }

        if (isset($input['status'])) {
            post_toggle_status($pdo, $id, trim((string)$input['status']));
            booking_account_out(200, null, 'Đã cập nhật trạng thái bài viết.');
        }

        if (isset($input['is_pinned'])) {
            post_toggle_pin($pdo, $id, !empty($input['is_pinned']));
            booking_account_out(200, null, 'Đã cập nhật trạng thái ghim.');
        }

        booking_account_out(422, null, 'Không có thao tác nào được chỉ định.');
    }

    // ==========================================
    // DELETE: Xóa bài viết
    // ==========================================
    if ($method === 'DELETE') {
        $id = (int)($input['id'] ?? ($_GET['id'] ?? 0));
        if ($id <= 0) {
            booking_account_out(422, null, 'ID bài viết cần xóa không hợp lệ.');
        }
        post_delete($pdo, $id);
        booking_account_out(200, null, 'Đã xóa bài viết thành công.');
    }

    header('Allow: GET, POST, PUT, PATCH, DELETE');
    booking_account_out(405, null, 'Phương thức không được hỗ trợ.');
} catch (PostError $e) {
    booking_account_out($e->httpCode, null, $e->getMessage());
} catch (Throwable $e) {
    error_log('[GODMINTON api/admin/posts.php] ' . $e->getMessage());
    booking_account_out(500, null, 'Lỗi xử lý yêu cầu quản trị bài viết.');
}
