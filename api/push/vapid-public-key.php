<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $vapid = require __DIR__ . '/../../config/vapid.php';
    echo json_encode(['status' => 'success', 'publicKey' => $vapid['publicKey']], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('[GODMINTON Push] VAPID configuration error: ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Thông báo đẩy chưa được cấu hình trên máy chủ.'], JSON_UNESCAPED_UNICODE);
}
