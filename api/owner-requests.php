<?php

require_once __DIR__ . '/../includes/owner-workflow.php';
try {
    $user = wf_user();
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $r = wf_request(booking_account_id($_GET['id']));
            if ((int) $r['user_id'] !== $user['id'])
                wf_fail(403, 'Bạn chỉ được xem hồ sơ của mình.');
            booking_account_out(200, wf_request_detail($r));
        }

        $s = $pdo->prepare('SELECT id,kind,court_id,status,review_note,revision,submitted_at,updated_at,payload FROM owner_requests WHERE user_id=? ORDER BY id DESC LIMIT 100');
        $s->execute([$user['id']]);
        $rows = $s->fetchAll();
        foreach ($rows as &$r)
            $r['payload'] = json_decode($r['payload'], true);
        unset($r);
        booking_account_out(200, ['requests' => $rows, 'wards' => $pdo->query('SELECT * FROM wards ORDER BY name')->fetchAll(), 'amenities' => $pdo->query('SELECT code,label FROM amenities ORDER BY id')->fetchAll(), 'notifications' => []]);
    }

    if ($user['role'] === 'admin')
        wf_fail(403, 'Admin xét duyệt hồ sơ, không tự đăng ký quyền chủ sân cho mình.');
    $in = wf_post();
    $action = wf_text($in, 'action', 30);
    if (!in_array($action, ['save', 'submit', 'withdraw'], true))
        wf_fail(422, 'Thao tác không hợp lệ.');
    if (!god_auth_limit($pdo, 'owner-application', (string) $user['id'], 40, 3600))
        wf_fail(429, 'Quá nhiều yêu cầu. Vui lòng thử lại sau.');
    $pdo->beginTransaction();
    wf_lock_actor($user);
    $id = isset($in['id']) && $in['id'] !== '' ? booking_account_id($in['id']) : null;
    $old = $id ? wf_request($id, true) : null;
    if ($old && (int) $old['user_id'] !== $user['id'])
        wf_fail(403, 'Hồ sơ không thuộc tài khoản của bạn.');
    if ($old && (int) ($in['revision'] ?? 0) !== (int) $old['revision'])
        wf_fail(409, 'Hồ sơ đã thay đổi. Tải lại trước khi lưu.');
    if ($action === 'withdraw') {
        if (!$old || !in_array($old['status'], ['draft', 'pending', 'needs_info', 'rejected'], true))
            wf_fail(409, 'Không thể rút hồ sơ này.');
        $pdo->prepare("UPDATE owner_requests SET status='withdrawn',revision=revision+1 WHERE id=?")->execute([$id]);
        wf_event($id, $user['id'], 'withdraw', 'Người đăng ký rút hồ sơ.');
        $pdo->commit();
        booking_account_out(200, ['id' => $id], 'Đã rút hồ sơ.');
    }

    if ($old && !in_array($old['status'], ['draft', 'needs_info', 'rejected'], true))
        wf_fail(409, 'Chỉ chỉnh sửa hồ sơ nháp hoặc hồ sơ cần bổ sung/bị từ chối.');
    $kind = wf_text($in, 'kind', 10);
    if (!in_array($kind, ['new', 'claim'], true) || ($old && $old['kind'] === 'update'))
        wf_fail(422, 'Chọn đăng ký sân mới hoặc nhận quản lý sân có sẵn.');
    $courtId = $kind === 'claim' && !empty($in['court_id']) ? booking_account_id($in['court_id']) : null;
    $rep = wf_text($in, 'representative_name', 150);
    $phone = wf_text($in, 'representative_phone', 30);
    $note = wf_text($in, 'evidence_note', 5000);
    $url = wf_text($in, 'evidence_url', 2000);
    if ($url !== '' && !wf_url($url))
        wf_fail(422, 'Liên kết chứng minh phải dùng HTTPS.');
    $submit = $action === 'submit';
    if ($submit && (mb_strlen($rep) < 2 || !preg_match('/^\+?[0-9 ()-]{9,20}$/', $phone) || mb_strlen($note) < 20))
        wf_fail(422, 'Điền người đại diện, số liên hệ và mô tả quyền quản lý ít nhất 20 ký tự.');
    $payload = $in['payload'] ?? [];
    if (!is_array($payload))
        wf_fail(422, 'Thông tin sân không hợp lệ.');
    if ($kind === 'claim') {
        if ($submit && !$courtId)
            wf_fail(422, 'Chọn sân đã có để yêu cầu nhận quản lý.');
        if ($courtId) {
            $q = $pdo->prepare('SELECT id,name,address,owner_id FROM courts WHERE id=? FOR UPDATE');
            $q->execute([$courtId]);
            $c = $q->fetch();
            if (!$c)
                wf_fail(404, 'Sân không tồn tại.');
            if ((int) $c['owner_id'] === $user['id'])
                wf_fail(409, 'Bạn đã quản lý sân này.');
            $payload = ['name' => $c['name'], 'address' => $c['address']];
        }
    } else {
        $courtId = null;
        $payload = wf_payload($payload, $submit);
    }

    if ($submit) {
        $q = $pdo->prepare("SELECT id FROM owner_requests WHERE user_id=? AND kind=? AND status='pending' AND id<>? AND (court_id <=> ?) FOR UPDATE");
        $q->execute([$user['id'], $kind, $id ?? 0, $courtId]);
        // One new-court application at a time, or one claim per venue; drafts remain editable.
        if ($q->fetchColumn())
            wf_fail(409, 'Bạn đã có hồ sơ cùng loại/sân đang chờ duyệt. Vui lòng chờ kết quả.');
    }

    $status = $submit ? 'pending' : 'draft';
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($old) {
        $pdo->prepare('UPDATE owner_requests SET kind=?,court_id=?,status=?,representative_name=?,representative_phone=?,evidence_note=?,evidence_url=?,payload=?,revision=revision+1,submitted_at=IF(?=1,NOW(),submitted_at) WHERE id=?')->execute([$kind, $courtId, $status, $rep, $phone, $note, $url, $json, $submit ? 1 : 0, $id]);
    } else {
        $pdo->prepare('INSERT INTO owner_requests(user_id,kind,court_id,status,representative_name,representative_phone,evidence_note,evidence_url,payload,submitted_at) VALUES(?,?,?,?,?,?,?,?,?,IF(?=1,NOW(),NULL))')->execute([$user['id'], $kind, $courtId, $status, $rep, $phone, $note, $url, $json, $submit ? 1 : 0]);
        $id = (int) $pdo->lastInsertId();
    }

    wf_event($id, $user['id'], $submit ? 'submit' : 'save', $submit ? 'Đã gửi hồ sơ xét duyệt.' : 'Đã lưu nháp.');
    $pdo->commit();
    booking_account_out(200, ['id' => $id, 'duplicates' => $kind === 'new' ? wf_duplicates($payload) : []], $submit ? 'Đã gửi hồ sơ. Sân chưa được công khai trước khi duyệt.' : 'Đã lưu nháp.');
} catch (Throwable $e) {
    wf_error($e);
}
