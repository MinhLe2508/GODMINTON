<?php
require_once __DIR__ . '/../../includes/auth-security.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    god_auth_out(405, 'Chỉ hỗ trợ POST.');
}
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$expected = (god_auth_is_https() ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? '');
if (($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '') === 'cross-site' || ($origin !== '' && $origin !== $expected)) {
    god_auth_out(403, 'Nguồn yêu cầu không hợp lệ.');
}
god_auth_revoke_remember($pdo, false);
god_auth_clear(true);
god_auth_out(200, 'Đã đăng xuất.');
