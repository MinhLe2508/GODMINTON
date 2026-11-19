<?php

require_once __DIR__ . '/../includes/owner-workflow.php';
try {
    wf_user();
    if ($_SERVER['REQUEST_METHOD'] !== 'GET')
        wf_fail(405, 'Chỉ hỗ trợ GET.');
    $search = trim((string) ($_GET['search'] ?? ''));
    if (mb_strlen($search) < 2 || mb_strlen($search) > 150)
        wf_fail(422, 'Nhập ít nhất 2 ký tự tên hoặc địa chỉ sân.');
    $s = $pdo->prepare('SELECT id,name,address,latitude,longitude FROM courts WHERE (name LIKE ? OR address LIKE ?) ORDER BY name LIMIT 25');
    $s->execute(['%' . $search . '%', '%' . $search . '%']);
    booking_account_out(200, $s->fetchAll());
} catch (Throwable $e) {
    wf_error($e);
}
