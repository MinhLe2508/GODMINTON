<?php

require_once __DIR__ . '/../../includes/owner-workflow.php';
try {
    $actor = wf_user(true);
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['id'])) {
            $id = booking_account_id($_GET['id']);
            $s = $pdo->prepare('SELECT id,name,email,phone,role,is_verified,account_status,locked_reason,locked_at,created_at,deleted_at,pending_email FROM users WHERE id=?');
            $s->execute([$id]);
            $u = $s->fetch();
            if (!$u)
                wf_fail(404, 'Không tìm thấy tài khoản.');
            $s = $pdo->prepare('SELECT id,name,address,status,booking_open FROM courts WHERE owner_id=? ORDER BY id');
            $s->execute([$id]);
            $u['courts'] = $s->fetchAll();
            $s = $pdo->prepare('SELECT b.id,b.booking_code,b.status,b.total_price,b.deposit_amount,b.created_at,c.name AS court_name FROM bookings b JOIN courts c ON c.id=b.court_id WHERE b.user_id=? ORDER BY b.id DESC LIMIT 30');
            $s->execute([$id]);
            $u['bookings'] = $s->fetchAll();
            $s = $pdo->prepare('SELECT a.id,a.action,a.reason,a.created_at,a.court_id,u.name AS actor_name FROM account_audit a LEFT JOIN users u ON u.id=a.actor_id WHERE target_user_id=? ORDER BY a.id DESC LIMIT 50');
            $s->execute([$id]);
            $u['audit'] = $s->fetchAll();
            booking_account_out(200, $u);
        }

        $where = '1=1';
        $args = [];
        $search = trim((string) ($_GET['search'] ?? ''));
        $role = (string) ($_GET['role'] ?? '');
        $state = (string) ($_GET['state'] ?? '');
        $verified = (string) ($_GET['verified'] ?? '');
        $page = max(1, min(100000, (int) ($_GET['page'] ?? 1)));
        if (mb_strlen($search) > 150)
            wf_fail(422, 'Từ khóa quá dài.');
        if ($search !== '') {
            $where .= ' AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            array_push($args, '%' . $search . '%', '%' . $search . '%', '%' . $search . '%');
        }

        if ($role !== '') {
            if (!in_array($role, ['customer', 'owner', 'admin'], true))
                wf_fail(422, 'Vai trò không hợp lệ.');
            $where .= ' AND u.role=?';
            $args[] = $role;
        }

        if ($state === 'deleted')
            $where .= ' AND u.deleted_at IS NOT NULL';
        elseif ($state !== '') {
            if (!in_array($state, ['active', 'locked'], true))
                wf_fail(422, 'Trạng thái không hợp lệ.');
            $where .= ' AND u.deleted_at IS NULL AND u.account_status=?';
            $args[] = $state;
        }

        if ($verified !== '') {
            if (!in_array($verified, ['0', '1'], true))
                wf_fail(422, 'Bộ lọc xác minh không hợp lệ.');
            $where .= ' AND u.is_verified=?';
            $args[] = (int) $verified;
        }

        $s = $pdo->prepare('SELECT COUNT(*) FROM users u WHERE ' . $where);
        $s->execute($args);
        $total = (int) $s->fetchColumn();
        $s = $pdo->prepare("SELECT u.id,u.name,u.email,u.phone,u.role,u.is_verified,u.account_status,u.created_at,u.deleted_at,(SELECT COUNT(*) FROM owner_requests r WHERE r.user_id=u.id AND r.status='pending') AS pending_requests FROM users u WHERE " . $where . ' ORDER BY u.id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20));
        $s->execute($args);
        booking_account_out(200, ['items' => $s->fetchAll(), 'total' => $total, 'page' => $page]);
    }

    $in = wf_post();
    wf_admin_password($actor, $in);
    $target = booking_account_id($in['id'] ?? null);
    $action = wf_text($in, 'action', 30);
    $reason = wf_text($in, 'reason', 1000);
    if (!in_array($action, ['lock', 'unlock', 'edit', 'send_reset', 'revoke_owner', 'request_email_change'], true) || mb_strlen($reason) < 5)
        wf_fail(422, 'Chọn thao tác và nhập lý do ít nhất 5 ký tự.');
    if (array_key_exists('role', $in) || array_key_exists('is_verified', $in) || array_key_exists('email', $in))
        wf_fail(422, 'Không được cấp quyền hoặc thay email/xác minh trực tiếp tại đây.');
    $pdo->beginTransaction();
    $admins = wf_lock_admins($actor);
    $u = wf_lock_user($target);
    if ($u['deleted_at'] !== null)
        wf_fail(409, 'Tài khoản đã xóa không thể sửa hoặc mở khóa.');
    if ($action === 'lock') {
        if ($u['role'] === 'admin' && $u['account_status'] === 'active' && count($admins) <= 1)
            wf_fail(409, 'Không được khóa quản trị viên hoạt động cuối cùng.');
        if ($target === $actor['id'])
            wf_fail(409, 'Không được tự khóa tài khoản đang sử dụng.');
        if ($u['account_status'] === 'locked')
            wf_fail(409, 'Tài khoản đã bị khóa.');
        $pdo->prepare("UPDATE users SET account_status='locked',locked_reason=?,locked_by=?,locked_at=NOW(),session_version=session_version+1 WHERE id=?")->execute([$reason, $actor['id'], $target]);
        $pdo->prepare("UPDATE courts SET status='inactive',booking_open=0 WHERE owner_id=?")->execute([$target]);
        $pdo->prepare("UPDATE time_slots SET status='available',held_by=NULL,hold_expires_at=NULL WHERE held_by=? AND status='holding'")->execute([$target]);
    } elseif ($action === 'unlock') {
        if ($u['account_status'] !== 'locked')
            wf_fail(409, 'Tài khoản không bị khóa.');
        $pdo->prepare("UPDATE users SET account_status='active',locked_reason=NULL,locked_by=NULL,locked_at=NULL,session_version=session_version+1 WHERE id=?")->execute([$target]);
    } elseif ($action === 'edit') {
        $name = wf_text($in, 'name', 150);
        $phone = wf_text($in, 'phone', 30);
        if (mb_strlen($name) < 2 || !preg_match('/^\+?[0-9 ()-]{9,20}$/', $phone))
            wf_fail(422, 'Họ tên hoặc số điện thoại không hợp lệ.');
        $pdo->prepare('UPDATE users SET name=?,phone=? WHERE id=?')->execute([$name, $phone, $target]);
    } elseif ($action === 'revoke_owner') {
        if ($u['role'] !== 'owner')
            wf_fail(409, 'Tài khoản không có vai trò chủ sân.');
        $s = $pdo->prepare('SELECT id FROM courts WHERE owner_id=? FOR UPDATE');
        $s->execute([$target]);
        if ($s->fetchColumn())
            wf_fail(409, 'Bàn giao các sân trước khi thu hồi quyền chủ sân.');
        $s = $pdo->prepare("SELECT id FROM bookings WHERE user_id=? AND status IN ('pending','confirmed') FOR UPDATE");
        $s->execute([$target]);
        if ($s->fetchColumn())
            wf_fail(409, 'Cần giải quyết các đơn đang hoạt động trước.');
        $pdo->prepare("UPDATE users SET role='customer',session_version=session_version+1 WHERE id=?")->execute([$target]);
    } elseif ($action === 'request_email_change') {
        if (!wf_active($u))
            wf_fail(409, 'Tài khoản phải hoạt động và đã xác minh.');
        $email = strtolower(wf_text($in, 'new_email', 150));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $email === strtolower($u['email']))
            wf_fail(422, 'Nhập email mới hợp lệ và khác email hiện tại.');
        $q = $pdo->prepare('SELECT id FROM users WHERE email=?');
        $q->execute([$email]);
        if ($q->fetchColumn())
            wf_fail(409, 'Email mới đã được sử dụng.');
        if (!god_auth_limit($pdo, 'admin-email-change', (string) $target, 3, 3600))
            wf_fail(429, 'Đã đạt giới hạn yêu cầu đổi email.');
        $pdo->prepare('UPDATE users SET pending_email=? WHERE id=?')->execute([$email, $target]);
        $mail = god_auth_mailer();
        $token = god_auth_token($pdo, $target, 'email_change');
        god_auth_send($mail, ['name' => $u['name'], 'email' => $email], 'email_change', $token);
        $notice = wf_notice($target, 'Yêu cầu đổi email GODMINTON', 'Admin đã tạo yêu cầu đổi email. Địa chỉ hiện tại chỉ thay đổi sau khi xác minh hộp thư mới. Nếu không phải yêu cầu của bạn, hãy liên hệ quản trị viên.');
    } else {
        if (!wf_active($u))
            wf_fail(409, 'Chỉ gửi đặt lại mật khẩu cho tài khoản hoạt động, đã xác minh.');
        if (!god_auth_limit($pdo, 'admin-reset', (string) $target, 3, 3600))
            wf_fail(429, 'Đã đạt giới hạn gửi email cho tài khoản này.');
        $mail = god_auth_mailer();
        $token = god_auth_token($pdo, $target, 'reset');
        god_auth_send($mail, $u, 'reset', $token);
    }

    wf_audit($actor['id'], $target, 'account_' . $action, $reason, null, null, $action === 'edit' ? ['changed_fields' => ['name', 'phone']] : []);
    if ($action === 'lock')
        $notice = wf_notice($target, 'Tài khoản GODMINTON đã bị khóa', 'Tài khoản bị khóa: ' . $reason . '. Các đơn hiện có được giữ để xử lý.');
    if ($action === 'unlock')
        $notice = wf_notice($target, 'Tài khoản GODMINTON đã được mở khóa', 'Tài khoản đã được mở khóa. Các sân vẫn tạm dừng nhận đặt cho đến khi được kiểm tra và mở lại.');
    $pdo->commit();
    $email = isset($notice) ? wf_deliver($notice) : null;
    booking_account_out(200, ['email_sent' => $email], 'Đã cập nhật tài khoản và lưu nhật ký.' . ($email === false ? ' Thông báo đã lưu; email chưa gửi được.' : ''));
} catch (Throwable $e) {
    wf_error($e);
}
