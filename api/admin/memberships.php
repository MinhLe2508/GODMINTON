<?php

/**
 * api/admin/memberships.php
 * API Quản lý Gói Hội Viên dành riêng cho Quản trị viên (Admin).
 * Hỗ trợ:
 * - GET: Xem danh sách, tìm kiếm, lọc theo hạng/trạng thái, thống kê KPI (doanh thu, số lượng, sắp hết hạn).
 * - POST (action=grant): Cấp/tặng gói hội viên cho khách hàng.
 * - POST (action=extend): Gia hạn thêm ngày cho hội viên hiện tại.
 * - POST (action=update_status): Bật/tắt/hủy trạng thái gói.
 * - POST (action=delete): Xóa bản ghi đăng ký.
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/membership-service.php';

try {
    $user = booking_account_user();
    if (($user['role'] ?? '') !== 'admin') {
        booking_account_out(403, null, 'Chỉ Quản trị viên (Admin) mới có quyền truy cập trang quản lý hội viên.');
    }

    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    // Tự động chuyển các gói hội viên đã qua 23h59p ngày kết thúc sang trạng thái 'expired'
    // & hủy các phiên chờ thanh toán VietQR đã quá hạn 20 phút mà khách chưa chuyển tiền
    payment_ensure_schema($pdo);
    payment_expire_overdue($pdo, 20);
    membership_sync_expired($pdo);

    // ==========================================
    // GET: Thống kê & Danh sách hội viên
    // ==========================================
    if ($method === 'GET') {
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        $tier = isset($_GET['tier']) ? trim((string) $_GET['tier']) : '';
        $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(5, min(100, (int) ($_GET['limit'] ?? 15)));
        $offset = ($page - 1) * $limit;

        // 1. Thống kê tổng quan (KPIs)
        $statPendingStmt = $pdo->query("SELECT COUNT(*) FROM user_memberships WHERE status = 'pending'");
        $totalPending = (int) $statPendingStmt->fetchColumn();

        $statActiveStmt = $pdo->query("SELECT COUNT(*) FROM user_memberships WHERE status = 'active' AND end_date >= NOW()");
        $totalActive = (int) $statActiveStmt->fetchColumn();

        $tierStatsStmt = $pdo->query("
            SELECT tier, COUNT(*) as cnt 
            FROM user_memberships 
            WHERE status = 'active' AND end_date >= NOW()
            GROUP BY tier
        ");
        $tierCounts = ['silver' => 0, 'gold' => 0, 'platinum' => 0];
        while ($row = $tierStatsStmt->fetch()) {
            if (isset($tierCounts[$row['tier']])) {
                $tierCounts[$row['tier']] = (int) $row['cnt'];
            }
        }

        $revenueStmt = $pdo->query("SELECT COALESCE(SUM(price_paid), 0) FROM user_memberships WHERE status = 'active'");
        $totalRevenue = (float) $revenueStmt->fetchColumn();

        $expiringStmt = $pdo->query("
            SELECT COUNT(*) 
            FROM user_memberships 
            WHERE status = 'active' 
              AND end_date >= NOW() 
              AND end_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)
        ");
        $expiringSoon = (int) $expiringStmt->fetchColumn();

        // 2. Lọc danh sách
        $where = ['1=1'];
        $params = [];

        if ($tier !== '' && in_array($tier, ['silver', 'gold', 'platinum'], true)) {
            $where[] = 'um.tier = ?';
            $params[] = $tier;
        }

        if ($status !== '' && in_array($status, ['pending', 'active', 'expired', 'cancelled'], true)) {
            if ($status === 'pending') {
                $where[] = "um.status = 'pending'";
            } elseif ($status === 'active') {
                $where[] = "(um.status = 'active' AND um.end_date >= NOW())";
            } elseif ($status === 'expired') {
                $where[] = "(um.status = 'expired' OR (um.status = 'active' AND um.end_date < NOW()))";
            } else {
                $where[] = "um.status IN ('cancelled', 'rejected')";
            }
        }

        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR p.title LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        // Đếm tổng số bản ghi
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM user_memberships um
            JOIN users u ON u.id = um.user_id
            JOIN posts p ON p.id = um.post_id
            WHERE {$whereSql}
        ");
        $countStmt->execute($params);
        $totalRecords = (int) $countStmt->fetchColumn();

        // Lấy danh sách chi tiết kèm trạng thái thanh toán SePay thực tế
        $listStmt = $pdo->prepare("
            SELECT um.*,
                   u.name AS user_name,
                   u.email AS user_email,
                   u.phone AS user_phone,
                   p.title AS package_title,
                   p.slug AS package_slug,
                   pay.status AS payment_status,
                   pay.paid_amount AS payment_paid_amount,
                   pay.payment_code AS payment_code,
                   pay.paid_at AS payment_paid_at
            FROM user_memberships um
            JOIN users u ON u.id = um.user_id
            JOIN posts p ON p.id = um.post_id
            LEFT JOIN payments pay ON pay.id = (
                SELECT p2.id FROM payments p2 WHERE p2.membership_id = um.id ORDER BY p2.id DESC LIMIT 1
            )
            WHERE {$whereSql}
            ORDER BY um.id DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $listStmt->execute($params);
        $rawItems = $listStmt->fetchAll();

        $now = time();
        $items = array_map(function ($item) use ($now) {
            $end = strtotime($item['end_date']);
            $daysLeft = max(0, (int) ceil(($end - $now) / 86400));
            $isExpired = $end < $now;

            $effectiveStatus = $item['status'];
            if ($effectiveStatus === 'active' && $isExpired) {
                $effectiveStatus = 'expired';
            }

            $payStatus = $item['payment_status'] ?? null;
            // Một gói chỉ được coi là ĐÃ THANH TOÁN nếu cổng SePay xác nhận 'paid' HOẶC gói do Admin cấp trực tiếp (không qua bảng payments và đã active/expired)
            $isPaid = ($payStatus === 'paid') || ($payStatus === null && in_array($item['status'], ['active', 'expired'], true));
            // Chỉ cho phép kích hoạt lại nếu gói ĐÃ THANH TOÁN, bị Admin tạm hủy ('cancelled') và vẫn còn hạn sử dụng
            $canReactivate = ($item['status'] === 'cancelled' && $isPaid && !$isExpired);
            // Chỉ cho phép gia hạn nếu gói ĐÃ THANH TOÁN và đang ở trạng thái active hoặc expired
            $canExtend = ($isPaid && in_array($item['status'], ['active', 'expired'], true));

            return [
                'id' => (int) $item['id'],
                'user_id' => (int) $item['user_id'],
                'user_name' => $item['user_name'] ?? 'Khách hàng',
                'user_email' => $item['user_email'] ?? '',
                'user_phone' => $item['user_phone'] ?? '',
                'user_avatar' => '',
                'post_id' => (int) $item['post_id'],
                'package_title' => $item['package_title'] ?? 'Gói Hội Viên',
                'package_slug' => $item['package_slug'] ?? '',
                'tier' => $item['tier'],
                'tier_label' => match ($item['tier']) {
                    'platinum' => 'Bạch Kim',
                    'gold' => 'Vàng',
                    'silver' => 'Bạc',
                    default => 'Hội Viên'
                },
                'discount_percent' => (int) $item['discount_percent'],
                'price_paid' => (float) $item['price_paid'],
                'duration_days' => (int) $item['duration_days'],
                'start_date' => $item['start_date'],
                'end_date' => $item['end_date'],
                'days_left' => $daysLeft,
                'is_expired' => $isExpired,
                'status' => $item['status'],
                'effective_status' => $effectiveStatus,
                'payment_method' => $item['payment_method'] ?? 'VietQR',
                'payment_status' => $payStatus,
                'payment_code' => $item['payment_code'] ?? null,
                'is_paid' => $isPaid,
                'can_reactivate' => $canReactivate,
                'can_extend' => $canExtend,
                'created_at' => $item['created_at']
            ];
        }, $rawItems);

        // 3. Danh sách các gói hội viên có sẵn trong hệ thống (để admin cấp gói)
        $pkgStmt = $pdo->query("
            SELECT id, title, slug, price, duration_days, discount_percent 
            FROM posts 
            WHERE type = 'membership' AND status = 'published'
            ORDER BY discount_percent ASC, price ASC
        ");
        $availablePackages = $pkgStmt->fetchAll();

        // 4. Danh sách tài khoản khách hàng để admin chọn nhanh khi cấp gói
        $userSearch = isset($_GET['user_search']) ? trim((string) $_GET['user_search']) : '';
        if ($userSearch !== '') {
            $uStmt = $pdo->prepare("
                SELECT id, name, email, phone 
                FROM users 
                WHERE account_status = 'active' 
                  AND deleted_at IS NULL
                  AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)
                ORDER BY name ASC 
                LIMIT 30
            ");
            $uStmt->execute(["%$userSearch%", "%$userSearch%", "%$userSearch%"]);
        } else {
            $uStmt = $pdo->query("
                SELECT id, name, email, phone 
                FROM users 
                WHERE account_status = 'active' 
                  AND deleted_at IS NULL
                ORDER BY name ASC 
                LIMIT 50
            ");
        }

        $customers = $uStmt->fetchAll();

        booking_account_out(200, [
            'stats' => [
                'total_pending' => $totalPending,
                'total_active' => $totalActive,
                'silver_count' => $tierCounts['silver'],
                'gold_count' => $tierCounts['gold'],
                'platinum_count' => $tierCounts['platinum'],
                'total_revenue' => $totalRevenue,
                'expiring_soon' => $expiringSoon
            ],
            'items' => $items,
            'pagination' => [
                'total' => $totalRecords,
                'page' => $page,
                'limit' => $limit,
                'pages' => max(1, (int) ceil($totalRecords / $limit))
            ],
            'available_packages' => $availablePackages,
            'customers' => $customers
        ]);
    }

    // ==========================================
    // POST: Cấp gói, gia hạn, đổi trạng thái, xóa
    // ==========================================
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $input = json_decode($raw, true);
        if (!is_array($input)) {
            $input = !empty($_POST) ? $_POST : [];
            if (empty($input) && !empty($raw)) {
                parse_str($raw, $parsedRaw);
                if (is_array($parsedRaw))
                    $input = $parsedRaw;
            }
        }

        $action = trim((string) ($input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? ''));

        // ==========================================
        // 1. ACTION: GRANT (Cấp / Tặng gói cho user)
        // ==========================================
        if ($action === 'grant') {
            $targetUserId = (int) ($input['user_id'] ?? 0);
            $postId = (int) ($input['post_id'] ?? 0);
            $paymentMethod = trim((string) ($input['payment_method'] ?? 'Admin cấp / Tiền mặt'));
            $customDays = isset($input['duration_days']) && (int) $input['duration_days'] > 0 ? (int) $input['duration_days'] : null;
            $customPrice = isset($input['price_paid']) ? (float) $input['price_paid'] : null;

            if ($targetUserId <= 0) {
                booking_account_out(422, null, 'Vui lòng chọn tài khoản người dùng.');
            }

            if ($postId <= 0) {
                booking_account_out(422, null, 'Vui lòng chọn gói hội viên.');
            }

            // Kiểm tra user
            $uStmt = $pdo->prepare("SELECT id, name, email, account_status, deleted_at FROM users WHERE id = ?");
            $uStmt->execute([$targetUserId]);
            $targetUser = $uStmt->fetch();
            if (!$targetUser || $targetUser['account_status'] !== 'active' || $targetUser['deleted_at'] !== null) {
                booking_account_out(422, null, 'Tài khoản người dùng không hợp lệ hoặc đã bị khóa.');
            }

            // Kiểm tra gói
            $pkg = membership_get_package_by_id($pdo, $postId);
            if (!$pkg) {
                booking_account_out(404, null, 'Gói hội viên không tồn tại trong hệ thống.');
            }

            $durationDays = $customDays !== null ? $customDays : (int) ($pkg['duration_days'] ?? 30);
            if ($durationDays <= 0)
                $durationDays = 30;

            $pricePaid = $customPrice !== null ? max(0, $customPrice) : (float) ($pkg['price'] ?? 0);

            $discountPercent = (int) ($pkg['discount_percent'] ?? 0);
            if ($discountPercent <= 0) {
                $slug = $pkg['slug'] ?? '';
                if (str_contains($slug, 'platinum'))
                    $discountPercent = 15;
                elseif (str_contains($slug, 'gold') || str_contains($slug, 'golden'))
                    $discountPercent = 10;
                else $discountPercent = 5;
            }

            $tier = $discountPercent >= 15 ? 'platinum' : ($discountPercent >= 10 ? 'gold' : 'silver');

            // Kiểm tra gói hiện tại của user
            $active = membership_get_active_for_user($pdo, $targetUserId);
            $now = date('Y-m-d H:i:s');
            $startDate = $now;

            if ($active) {
                if ($active['tier'] === $tier) {
                    // Cộng dồn thời hạn
                    $curEnd = strtotime($active['end_date']);
                    $baseTime = ($curEnd > time()) ? $curEnd : time();
                    $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", $baseTime));
                    $endDate = $targetDate . ' 23:59:59';
                } else {
                    $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", time()));
                    $endDate = $targetDate . ' 23:59:59';
                    $pdo->prepare("UPDATE user_memberships SET status = 'cancelled' WHERE id = ?")->execute([$active['id']]);
                }
            } else {
                $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", time()));
                $endDate = $targetDate . ' 23:59:59';
            }

            $insStmt = $pdo->prepare("
                INSERT INTO user_memberships (
                    user_id, post_id, tier, discount_percent, price_paid, duration_days,
                    start_date, end_date, status, payment_method, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())
            ");
            $insStmt->execute([
                $targetUserId,
                $pkg['id'],
                $tier,
                $discountPercent,
                $pricePaid,
                $durationDays,
                $startDate,
                $endDate,
                $paymentMethod
            ]);

            booking_account_out(200, [
                'id' => (int) $pdo->lastInsertId(),
                'user_name' => $targetUser['name'],
                'package_title' => $pkg['title'],
                'tier' => $tier,
                'discount_percent' => $discountPercent,
                'end_date' => $endDate
            ], "Đã cấp thành công gói {$pkg['title']} cho thành viên {$targetUser['name']}!");
        }

        // ==========================================
        // 2. ACTION: EXTEND (Gia hạn thêm số ngày)
        // ==========================================
        if ($action === 'extend') {
            $membershipId = (int) ($input['id'] ?? 0);
            $addDays = (int) ($input['days'] ?? 30);

            if ($membershipId <= 0 || $addDays <= 0) {
                booking_account_out(422, null, 'Thông tin gia hạn không hợp lệ.');
            }

            $memStmt = $pdo->prepare("SELECT * FROM user_memberships WHERE id = ?");
            $memStmt->execute([$membershipId]);
            $mem = $memStmt->fetch();
            if (!$mem) {
                booking_account_out(404, null, 'Không tìm thấy gói hội viên.');
            }

            $payRow = payment_fetch_by_membership($pdo, $membershipId);
            $isPaid = ($payRow && $payRow['status'] === 'paid') || (!$payRow && in_array($mem['status'], ['active', 'expired'], true));
            if (!$isPaid || in_array($mem['status'], ['pending', 'rejected'], true)) {
                booking_account_out(422, null, 'Không thể gia hạn cho đơn đăng ký chưa thanh toán hoặc đã bị từ chối.');
            }

            $curEnd = strtotime($mem['end_date']);
            $baseTime = ($curEnd > time()) ? $curEnd : time();
            $targetDate = date('Y-m-d', strtotime("+{$addDays} days", $baseTime));
            $newEndDate = $targetDate . ' 23:59:59';
            $newDuration = (int) $mem['duration_days'] + $addDays;

            $upd = $pdo->prepare("
                UPDATE user_memberships 
                SET end_date = ?, duration_days = ?, status = 'active', updated_at = NOW() 
                WHERE id = ?
            ");
            $upd->execute([$newEndDate, $newDuration, $membershipId]);

            booking_account_out(200, [
                'id' => $membershipId,
                'new_end_date' => $newEndDate,
                'added_days' => $addDays
            ], "Đã gia hạn thành công thêm {$addDays} ngày (Hiệu lực đến " . date('d/m/Y', strtotime($newEndDate)) . ")!");
        }

        // ==========================================
        // 3. ACTION: UPDATE_STATUS (Đổi trạng thái)
        // ==========================================
        if ($action === 'update_status') {
            $membershipId = (int) ($input['id'] ?? 0);
            $newStatus = trim((string) ($input['status'] ?? ''));

            if ($membershipId <= 0 || !in_array($newStatus, ['active', 'expired', 'cancelled'], true)) {
                booking_account_out(422, null, 'Trạng thái không hợp lệ.');
            }

            $memStmt = $pdo->prepare("SELECT * FROM user_memberships WHERE id = ?");
            $memStmt->execute([$membershipId]);
            $mem = $memStmt->fetch();
            if (!$mem) {
                booking_account_out(404, null, 'Không tìm thấy gói hội viên.');
            }

            if ($newStatus === 'active') {
                $payRow = payment_fetch_by_membership($pdo, $membershipId);
                $isPaid = ($payRow && $payRow['status'] === 'paid') || (!$payRow && in_array($mem['status'], ['active', 'expired'], true));
                if ($mem['status'] === 'rejected' || !$isPaid) {
                    booking_account_out(422, null, 'Không thể kích hoạt lại gói chưa thanh toán hoặc đã bị từ chối. Vui lòng yêu cầu khách tạo mã QR mới hoặc dùng chức năng Cấp gói hội viên.');
                }

                if (strtotime((string) $mem['end_date']) < time()) {
                    booking_account_out(422, null, 'Gói hội viên này đã hết hạn thời gian. Vui lòng sử dụng chức năng Gia hạn.');
                }
            }

            $upd = $pdo->prepare("UPDATE user_memberships SET status = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newStatus, $membershipId]);

            $statusLabels = [
                'active' => 'Đang kích hoạt',
                'expired' => 'Hết hạn',
                'cancelled' => 'Đã hủy / chuyển gói'
            ];

            booking_account_out(200, ['status' => $newStatus], "Đã cập nhật trạng thái gói thành: " . ($statusLabels[$newStatus] ?? $newStatus));
        }

        // ==========================================
        // 4. ACTION: DELETE (Xóa bản ghi)
        // ==========================================
        if ($action === 'delete') {
            $membershipId = (int) ($input['id'] ?? 0);
            if ($membershipId <= 0) {
                booking_account_out(422, null, 'ID bản ghi không hợp lệ.');
            }

            $del = $pdo->prepare("DELETE FROM user_memberships WHERE id = ?");
            $del->execute([$membershipId]);

            booking_account_out(200, null, 'Đã xóa bản ghi gói hội viên thành công.');
        }

        // ==========================================
        // 5. ACTION: APPROVE (Duyệt & Kích hoạt gói)
        // ==========================================
        if ($action === 'approve') {
            $membershipId = (int) ($input['id'] ?? 0);
            if ($membershipId <= 0) {
                booking_account_out(422, null, 'ID bản ghi không hợp lệ.');
            }

            try {
                $res = membership_approve($pdo, $membershipId);
                booking_account_out(200, $res, $res['message']);
            } catch (MembershipError $me) {
                booking_account_out($me->httpCode, null, $me->getMessage());
            }
        }

        // ==========================================
        // 6. ACTION: REJECT (Từ chối yêu cầu đăng ký)
        // ==========================================
        if ($action === 'reject') {
            $membershipId = (int) ($input['id'] ?? 0);
            $reason = trim((string) ($input['reason'] ?? ''));
            if ($membershipId <= 0) {
                booking_account_out(422, null, 'ID bản ghi không hợp lệ.');
            }

            try {
                $res = membership_reject($pdo, $membershipId, $reason);
                booking_account_out(200, $res, $res['message']);
            } catch (MembershipError $me) {
                booking_account_out($me->httpCode, null, $me->getMessage());
            }
        }

        booking_account_out(422, null, 'Thao tác không được hỗ trợ.');
    }

    booking_account_out(405, null, 'Phương thức không được hỗ trợ.');
} catch (Throwable $e) {
    booking_account_out(500, null, 'Lỗi xử lý quản lý hội viên: ' . $e->getMessage());
}
