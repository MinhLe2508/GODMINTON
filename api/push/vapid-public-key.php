<?php
/**
 * api/push/vapid-public-key.php
 * Trả về VAPID Public Key để trình duyệt đăng ký Web Push
 */
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=86400');

$vapid = require __DIR__ . '/../../config/vapid.php';

echo json_encode([
    'status'    => 'success',
    'publicKey' => $vapid['publicKey']
], JSON_UNESCAPED_UNICODE);
