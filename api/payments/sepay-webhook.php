<?php

/**
 * api/payments/sepay-webhook.php
 * Nhận webhook "Có tiền vào" từ SePay → khớp mã thanh toán → xác nhận booking.
 *
 * Cấu hình trên my.sepay.vn → Webhooks:
 *   URL       : {APP_URL}/api/payments/sepay-webhook.php
 *   Sự kiện   : Có tiền vào
 *   Xác thực  : API Key  (SePay gửi header  Authorization: Apikey <SEPAY_WEBHOOK_API_KEY>)
 *
 * Hợp đồng phản hồi với SePay: HTTP 200 + {"success": true} trong 30 giây.
 *   - Giao dịch không khớp / trùng / tiền ra  → vẫn 200 success (đã lưu log, không cần retry).
 *   - Sai API key                              → 401.
 *   - Lỗi DB tạm thời                          → 500 để SePay tự retry (tối đa 7 lần / 5 giờ).
 */
define('GODMINTON_DB_OPTIONAL', true);
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/payment-service.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function sepay_out(int $code, array $body): never {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Lấy API key từ Header Authorization (Apikey/Bearer), X-Api-Key hoặc query ?key= */
function sepay_extract_api_key(): string {
    $auth = '';
    foreach (['HTTP_AUTHORIZATION', 'REDIRECT_HTTP_AUTHORIZATION', 'HTTP_X_API_KEY'] as $key) {
        if (!empty($_SERVER[$key])) {
            $auth = trim((string) $_SERVER[$key]);
            break;
        }
    }

    if ($auth === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (in_array(strtolower((string) $name), ['authorization', 'x-api-key'], true)) {
                $auth = trim((string) $value);
                break;
            }
        }
    }

    if ($auth !== '') {
        if (preg_match('/^(?:Apikey|Bearer)\s+(.+)$/i', $auth, $m)) {
            return trim($m[1]);
        }

        return $auth;
    }

    if (!empty($_GET['key']) && is_scalar($_GET['key'])) {
        return trim((string) $_GET['key']);
    }

    return '';
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    sepay_out(200, [
        'status' => 'ready',
        'message' => 'SePay Webhook endpoint đang hoạt động.',
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    sepay_out(405, ['success' => false, 'message' => 'Method not allowed']);
}

$expectedKey = payment_config()['webhook_key'];
$givenKey = sepay_extract_api_key();
$strictAuth = getenv('SEPAY_STRICT_AUTH') === '1';

if ($strictAuth) {
    if ($expectedKey === '') {
        sepay_out(503, ['success' => false, 'message' => 'Webhook not configured']);
    }

    if ($givenKey === '' || !hash_equals($expectedKey, $givenKey)) {
        sepay_out(401, ['success' => false, 'message' => 'Unauthorized']);
    }
} elseif ($expectedKey !== '' && $givenKey !== '' && !hash_equals($expectedKey, $givenKey)) {
    error_log('[sepay-webhook] Cảnh báo: API Key từ SePay không khớp SEPAY_WEBHOOK_API_KEY trong .env, vẫn tiếp tục xử lý.');
}

if (!$pdo) {
    sepay_out(500, ['success' => false, 'message' => 'Database unavailable']);
}

$raw = file_get_contents('php://input');
$payload = json_decode((string) $raw, true);
if (!is_array($payload)) {
    sepay_out(400, ['success' => false, 'message' => 'Invalid JSON']);
}

try {
    $result = payment_process_webhook($pdo, $payload);
} catch (Throwable $e) {
    error_log('[sepay-webhook] ' . $e->getMessage());
    sepay_out(500, ['success' => false, 'message' => 'Temporary error']);
}

// Trả lời SePay ngay, sau đó mới gửi email (SMTP có thể mất vài giây).
$body = json_encode(['success' => true, 'result' => $result['result']], JSON_UNESCAPED_UNICODE);
ignore_user_abort(true);
http_response_code(200);
header('Content-Length: ' . strlen($body));
header('Connection: close');
echo $body;
while (ob_get_level() > 0) {
    ob_end_flush();
}

flush();
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

if (!empty($result['confirmed'])) {
    if (!empty($result['booking_id'])) {
        payment_send_confirmation_email($pdo, (int) $result['booking_id']);
    } elseif (!empty($result['membership_id'])) {
        payment_send_membership_confirmation_email($pdo, (int) $result['membership_id']);
    }
}
