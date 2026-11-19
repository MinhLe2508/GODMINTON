<?php

/**
 * api/admin/vouchers.php
 * API quản lý mã giảm giá dành cho Quản trị viên (admin) và Chủ sân (owner).
 * 
 * Quyền hạn:
 * - Admin: Xem tất cả, tạo mã toàn sàn (funded_by='platform'), bật/tắt mọi mã.
 * - Owner: Xem mã của mình, tạo mã cho sân của mình (funded_by='owner', tối đa 50%).
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/voucher-service.php';

try {
    $user = booking_account_user();
    if (!in_array($user['role'], ['admin', 'owner'], true)) {
        booking_account_out(403, null, 'Bạn không có quyền truy cập trang quản lý mã giảm giá.');
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // ==========================================
    // GET: Danh sách mã giảm giá
    // ==========================================
    if ($method === 'GET') {
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';

        $where = [];
        $args = [];

        // Phân quyền theo vai trò
        if ($user['role'] === 'owner') {
            $where[] = 'v.owner_id = ?';
            $args[] = (int) $user['id'];
        }

        if ($status !== '' && in_array($status, ['active', 'disabled'], true)) {
            $where[] = 'v.status = ?';
            $args[] = $status;
        }

        if ($q !== '') {
            $where[] = '(v.code LIKE ? OR c.name LIKE ?)';
            $args[] = '%' . $q . '%';
            $args[] = '%' . $q . '%';
        }

        $sql = "
            SELECT v.*,
                   u.name AS creator_name,
                   c.name AS court_name
            FROM vouchers v
            LEFT JOIN users u ON u.id = v.created_by
            LEFT JOIN courts c ON c.id = v.court_id
        ";
        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY v.created_at DESC, v.id DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($args);
        $vouchers = $stmt->fetchAll();

        // Định dạng dữ liệu trả về cho client
        $data = array_map(function ($v) {
            return [
                'id' => (int) $v['id'],
                'code' => $v['code'],
                'created_by' => (int) $v['created_by'],
                'creator_name' => $v['creator_name'] ?? 'Hệ thống',
                'owner_id' => $v['owner_id'] !== null ? (int) $v['owner_id'] : null,
                'court_id' => $v['court_id'] !== null ? (int) $v['court_id'] : null,
                'court_name' => $v['court_name'] ?? 'Tất cả sân',
                'discount_percent' => (int) $v['discount_percent'],
                'max_discount' => $v['max_discount'] !== null ? (int) $v['max_discount'] : null,
                'min_order' => (int) $v['min_order'],
                'start_at' => $v['start_at'],
                'end_at' => $v['end_at'],
                'usage_limit' => $v['usage_limit'] !== null ? (int) $v['usage_limit'] : null,
                'used_count' => (int) $v['used_count'],
                'per_user_limit' => (int) $v['per_user_limit'],
                'first_booking_only' => (bool) $v['first_booking_only'],
                'funded_by' => $v['funded_by'],
                'status' => $v['status'],
                'created_at' => $v['created_at'],
            ];
        }, $vouchers);

        booking_account_out(200, $data, 'Lấy danh sách mã thành công.');
    }

    // Kiểm tra CSRF cho các thao tác ghi dữ liệu (POST, PATCH, DELETE)
    booking_account_csrf();
    $in = booking_account_input();

    // ==========================================
    // POST: Tạo mã giảm giá mới
    // ==========================================
    if ($method === 'POST') {
        $code = strtoupper(trim((string) ($in['code'] ?? '')));
        if ($code === '' || !preg_match('/^[A-Z0-9_-]{3,50}$/', $code)) {
            booking_account_out(422, null, 'Mã giảm giá phải từ 3-50 ký tự chữ cái, số, gạch nối.');
        }

        // Kiểm tra trùng mã
        $chk = $pdo->prepare('SELECT id FROM vouchers WHERE UPPER(code) = ?');
        $chk->execute([$code]);
        if ($chk->fetch()) {
            booking_account_out(422, null, 'Mã giảm giá này đã tồn tại trong hệ thống.');
        }

        $percent = (int) ($in['discount_percent'] ?? 0);
        if ($percent < 1 || $percent > 100) {
            booking_account_out(422, null, 'Phần trăm giảm giá phải từ 1% đến 100%.');
        }

        // Trần % giảm cho chủ sân: tối đa 50%
        if ($user['role'] === 'owner' && $percent > 50) {
            booking_account_out(422, null, 'Chủ sân chỉ được tạo mã giảm giá tối đa 50%.');
        }

        $maxDiscount = isset($in['max_discount']) && $in['max_discount'] !== ''
            ? max(0, (int) $in['max_discount'])
            : null;

        // Mã do Admin tạo bắt buộc phải có max_discount
        if ($user['role'] === 'admin') {
            if ($maxDiscount === null || $maxDiscount <= 0) {
                booking_account_out(422, null, 'Mã giảm giá toàn sàn của quản trị viên bắt buộc phải có mức giảm tối đa.');
            }
        }

        $minOrder = max(0, (int) ($in['min_order'] ?? 0));

        $startAt = trim((string) ($in['start_at'] ?? ''));
        $endAt = trim((string) ($in['end_at'] ?? ''));
        if (!$startAt || !$endAt || strtotime($endAt) <= strtotime($startAt)) {
            booking_account_out(422, null, 'Thời gian kết thúc phải sau thời gian bắt đầu.');
        }

        $usageLimit = isset($in['usage_limit']) && $in['usage_limit'] !== ''
            ? max(1, (int) $in['usage_limit'])
            : null;

        $perUserLimit = max(1, (int) ($in['per_user_limit'] ?? 1));

        // first_booking_only: chỉ admin được bật
        $firstBookingOnly = ($user['role'] === 'admin' && !empty($in['first_booking_only'])) ? 1 : 0;

        // Xác định bên chịu và phạm vi sân (server tự gán theo vai trò)
        $fundedBy = ($user['role'] === 'admin') ? 'platform' : 'owner';
        $ownerId = ($user['role'] === 'admin') ? null : (int) $user['id'];
        $courtId = isset($in['court_id']) && $in['court_id'] !== '' ? (int) $in['court_id'] : null;

        // Nếu là chủ sân và có chọn court_id: bắt buộc phải thuộc quyền sở hữu
        if ($user['role'] === 'owner') {
            if ($courtId !== null) {
                $cChk = $pdo->prepare('SELECT id FROM courts WHERE id = ? AND owner_id = ?');
                $cChk->execute([$courtId, $user['id']]);
                if (!$cChk->fetch()) {
                    booking_account_out(403, null, 'Bạn không thể tạo mã cho sân không thuộc quyền sở hữu của mình.');
                }
            }
        }

        $ins = $pdo->prepare('
            INSERT INTO vouchers (
                code, created_by, owner_id, court_id, discount_percent,
                max_discount, min_order, start_at, end_at, usage_limit,
                used_count, per_user_limit, first_booking_only, funded_by, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, "active")
        ');
        $ins->execute([
            $code,
            $user['id'],
            $ownerId,
            $courtId,
            $percent,
            $maxDiscount,
            $minOrder,
            $startAt,
            $endAt,
            $usageLimit,
            $perUserLimit,
            $firstBookingOnly,
            $fundedBy
        ]);

        $newId = (int) $pdo->lastInsertId();
        booking_account_out(201, ['id' => $newId, 'code' => $code], 'Tạo mã giảm giá thành công.');
    }

    // ==========================================
    // PATCH: Cập nhật hoặc Bật / Tắt trạng thái
    // ==========================================
    if ($method === 'PATCH') {
        $id = booking_account_id($in['voucher_id'] ?? null);

        $stmt = $pdo->prepare('SELECT * FROM vouchers WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $v = $stmt->fetch();

        if (!$v) {
            booking_account_out(404, null, 'Không tìm thấy mã giảm giá.');
        }

        // Quyền truy cập
        if ($user['role'] === 'owner' && (int) $v['owner_id'] !== (int) $user['id']) {
            booking_account_out(403, null, 'Bạn không có quyền chỉnh sửa mã này.');
        }

        // Trường hợp 1: Chỉ bật / tắt trạng thái
        if (isset($in['status'])) {
            $status = in_array($in['status'], ['active', 'disabled'], true) ? $in['status'] : 'active';
            $pdo->prepare('UPDATE vouchers SET status = ? WHERE id = ?')->execute([$status, $id]);
            booking_account_out(200, ['id' => $id, 'status' => $status], 'Cập nhật trạng thái mã thành công.');
        }

        // Trường hợp 2: Chỉnh sửa thông tin
        $usedCount = (int) $v['used_count'];

        // Nếu admin chỉnh sửa mã của chủ sân: Admin được tắt/bật nhưng không được sửa mức giảm
        if ($user['role'] === 'admin' && $v['funded_by'] === 'owner' && (int) $v['created_by'] !== (int) $user['id']) {
            booking_account_out(403, null, 'Quản trị viên chỉ được tắt/bật mã của chủ sân, không được sửa mức giảm.');
        }

        // Mã đã có lượt dùng: KHÔNG được sửa %, max_discount, phạm vi sân; chỉ được kéo dài hạn hoặc chỉnh lượt
        if ($usedCount > 0) {
            if (isset($in['discount_percent']) && (int) $in['discount_percent'] !== (int) $v['discount_percent']) {
                booking_account_out(422, null, 'Mã đã có lượt sử dụng, không thể sửa phần trăm giảm giá.');
            }

            if (isset($in['max_discount']) && (int) $in['max_discount'] !== (int) $v['max_discount']) {
                booking_account_out(422, null, 'Mã đã có lượt sử dụng, không thể sửa mức giảm tối đa.');
            }

            if (isset($in['court_id']) && (int) ($in['court_id'] ?: 0) !== (int) ($v['court_id'] ?: 0)) {
                booking_account_out(422, null, 'Mã đã có lượt sử dụng, không thể đổi phạm vi sân áp dụng.');
            }

            // Kéo dài hạn
            $endAt = trim((string) ($in['end_at'] ?? $v['end_at']));
            if (strtotime($endAt) < strtotime($v['end_at'])) {
                booking_account_out(422, null, 'Mã đã có lượt sử dụng, chỉ được kéo dài hạn dùng chứ không thể rút ngắn.');
            }

            $pdo->prepare('UPDATE vouchers SET end_at = ? WHERE id = ?')->execute([$endAt, $id]);
            booking_account_out(200, ['id' => $id], 'Đã gia hạn mã giảm giá thành công.');
        }

        // Mã chưa có lượt dùng: cho phép cập nhật đầy đủ
        $percent = isset($in['discount_percent']) ? (int) $in['discount_percent'] : (int) $v['discount_percent'];
        if ($user['role'] === 'owner' && $percent > 50) {
            booking_account_out(422, null, 'Chủ sân chỉ được đặt mức giảm tối đa 50%.');
        }

        $maxDiscount = isset($in['max_discount']) && $in['max_discount'] !== ''
            ? max(0, (int) $in['max_discount'])
            : null;

        if ($v['funded_by'] === 'platform' && ($maxDiscount === null || $maxDiscount <= 0)) {
            booking_account_out(422, null, 'Mã nền tảng bắt buộc phải có mức giảm tối đa.');
        }

        $minOrder = max(0, (int) ($in['min_order'] ?? $v['min_order']));
        $startAt = trim((string) ($in['start_at'] ?? $v['start_at']));
        $endAt = trim((string) ($in['end_at'] ?? $v['end_at']));
        if (strtotime($endAt) <= strtotime($startAt)) {
            booking_account_out(422, null, 'Thời gian kết thúc phải sau thời gian bắt đầu.');
        }

        $usageLimit = isset($in['usage_limit']) && $in['usage_limit'] !== ''
            ? max(1, (int) $in['usage_limit'])
            : null;
        $perUserLimit = max(1, (int) ($in['per_user_limit'] ?? $v['per_user_limit']));

        $courtId = isset($in['court_id']) && $in['court_id'] !== '' ? (int) $in['court_id'] : null;
        if ($user['role'] === 'owner' && $courtId !== null) {
            $cChk = $pdo->prepare('SELECT id FROM courts WHERE id = ? AND owner_id = ?');
            $cChk->execute([$courtId, $user['id']]);
            if (!$cChk->fetch()) {
                booking_account_out(403, null, 'Sân không thuộc quyền sở hữu của bạn.');
            }
        }

        $pdo->prepare('
            UPDATE vouchers
            SET discount_percent = ?, max_discount = ?, min_order = ?,
                start_at = ?, end_at = ?, usage_limit = ?, per_user_limit = ?,
                court_id = ?
            WHERE id = ?
        ')->execute([
            $percent,
            $maxDiscount,
            $minOrder,
            $startAt,
            $endAt,
            $usageLimit,
            $perUserLimit,
            $courtId,
            $id
        ]);

        booking_account_out(200, ['id' => $id], 'Cập nhật mã giảm giá thành công.');
    }

    // ==========================================
    // DELETE: Xóa mã chưa có lượt sử dụng
    // ==========================================
    if ($method === 'DELETE') {
        $id = booking_account_id($in['voucher_id'] ?? ($_GET['id'] ?? null));

        $stmt = $pdo->prepare('SELECT * FROM vouchers WHERE id = ? FOR UPDATE');
        $stmt->execute([$id]);
        $v = $stmt->fetch();

        if (!$v) {
            booking_account_out(404, null, 'Không tìm thấy mã giảm giá.');
        }

        if ($user['role'] === 'owner' && (int) $v['owner_id'] !== (int) $user['id']) {
            booking_account_out(403, null, 'Bạn không có quyền xóa mã này.');
        }

        if ((int) $v['used_count'] > 0) {
            booking_account_out(422, null, 'Mã này đã có lượt sử dụng trong lịch sử đặt sân, không thể xóa. Bạn có thể chọn Tắt mã.');
        }

        $pdo->prepare('DELETE FROM vouchers WHERE id = ?')->execute([$id]);
        booking_account_out(200, ['id' => $id], 'Đã xóa mã giảm giá thành công.');
    }

    booking_account_out(405, null, 'Phương thức không được hỗ trợ.');
} catch (Throwable $e) {
    booking_account_error($e);
}
