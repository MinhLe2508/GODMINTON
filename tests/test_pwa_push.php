<?php
/**
 * tests/test_pwa_push.php — Kiểm thử tự động tính năng PWA & Web Push Notification Tuần 4
 */
require_once __DIR__ . '/../config/db.php';

$baseUrl = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8098', '/');
$pass = 0;
$fail = 0;

function check(string $name, bool $ok, string $detail = '') {
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? "  [PASS] " : "  [FAIL] ") . $name . ($ok || $detail === '' ? '' : " -> $detail") . "\n";
}

function req(string $url, string $method = 'GET', $body = null, array $headers = []): array {
    $ch = curl_init($url);
    $allHeaders = array_merge(['Accept: application/json'], $headers);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CUSTOMREQUEST  => $method,
    ];
    if ($body !== null) {
        $allHeaders[] = 'Content-Type: application/json';
        $opts[CURLOPT_POSTFIELDS] = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    $opts[CURLOPT_HTTPHEADER] = $allHeaders;
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    $json = json_decode((string)$raw, true);
    return ['status' => $code, 'contentType' => $contentType, 'data' => $json, 'raw' => (string)$raw];
}

echo "====================================================================\n";
echo "   KIỂM THỬ TỰ ĐỘNG TUẦN 4: PWA VÀ WEB PUSH NOTIFICATION\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------
// 1. KIỂM TRA PWA MANIFEST & ICONS
// -------------------------------------------------------------
echo "1. KIỂM TRA MANIFEST & TÀI NGUYÊN PWA:\n";
$r = req("$baseUrl/manifest.json");
check("manifest.json phản hồi HTTP 200", $r['status'] === 200);
$manifest = $r['data'];
check("manifest.json là JSON hợp lệ", is_array($manifest));
check("manifest có name và short_name GODMINTON", ($manifest['short_name'] ?? '') === 'GODMINTON');
check("manifest có display là 'standalone'", ($manifest['display'] ?? '') === 'standalone');
check("manifest có theme_color thương hiệu '#0d9488'", ($manifest['theme_color'] ?? '') === '#0d9488');
check("manifest có danh sách icons", !empty($manifest['icons']) && is_array($manifest['icons']));

foreach ($manifest['icons'] ?? [] as $icon) {
    $iconPath = dirname(__DIR__) . '/' . ltrim($icon['src'], './');
    check("File icon tồn tại: {$icon['src']}", file_exists($iconPath), "Không tìm thấy file: $iconPath");
}

// -------------------------------------------------------------
// 2. KIỂM TRA SERVICE WORKER & OFFLINE PAGE
// -------------------------------------------------------------
echo "\n2. KIỂM TRA SERVICE WORKER (SW.JS) & TRANG NGOẠI TUYẾN:\n";
$r = req("$baseUrl/sw.js");
check("sw.js phản hồi HTTP 200", $r['status'] === 200);
check("sw.js chứa bộ lắng nghe sự kiện 'install'", str_contains($r['raw'], "addEventListener('install'"));
check("sw.js chứa bộ lắng nghe sự kiện 'fetch'", str_contains($r['raw'], "addEventListener('fetch'"));
check("sw.js chứa quy tắc Network-Only cho /api/", str_contains($r['raw'], "/api/"));
check("sw.js chứa bộ lắng nghe sự kiện 'push'", str_contains($r['raw'], "addEventListener('push'"));
check("sw.js chứa bộ lắng nghe sự kiện 'notificationclick'", str_contains($r['raw'], "addEventListener('notificationclick'"));

$r = req("$baseUrl/offline.php");
check("offline.php phản hồi HTTP 200", $r['status'] === 200);
check("offline.php có tiêu đề 'Bạn Đang Ngoại Tuyến'", str_contains($r['raw'], 'Bạn Đang Ngoại Tuyến'));
check("offline.php có nút thử kết nối lại", str_contains($r['raw'], 'retryConnection()'));

// -------------------------------------------------------------
// 3. KIỂM TRA VAPID KEYS & WEB PUSH APIS
// -------------------------------------------------------------
echo "\n3. KIỂM TRA VAPID KEYS & WEB PUSH APIS:\n";
$r = req("$baseUrl/api/push/vapid-public-key.php");
check("api/push/vapid-public-key.php phản hồi 200", $r['status'] === 200);
$pubKey = $r['data']['publicKey'] ?? '';
check("VAPID public key hợp lệ (chuỗi Base64URL dài > 80 ký tự)", strlen($pubKey) >= 80, "Key: $pubKey");

// Kiểm tra bảng push_subscriptions trong DB
$colCount = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='push_subscriptions'")->fetchColumn();
check("Bảng 'push_subscriptions' có đầy đủ cấu trúc cột", $colCount >= 6);

// Test đăng ký subscription giả lập (POST api/push/subscribe.php)
$testEndpoint = 'https://fcm.googleapis.com/fcm/send/test_device_' . bin2hex(random_bytes(8));
$testP256dh = 'BNcRdreALRFXTkOOUHK1EtK2wtaz5Ry4YfYCA_0QT9t0GLNu3q2';
$testAuth   = 'tBHItJI5svbpez7KI4CCXg';

$r = req("$baseUrl/api/push/subscribe.php", 'POST', [
    'endpoint' => $testEndpoint,
    'keys'     => [
        'p256dh' => $testP256dh,
        'auth'   => $testAuth
    ]
]);
check("Đăng ký push subscription mới thành công (HTTP 200)", $r['status'] === 200 && ($r['data']['status'] ?? '') === 'success');

// Kiểm tra trong DB
$stmt = $pdo->prepare('SELECT COUNT(*) FROM push_subscriptions WHERE endpoint = ?');
$stmt->execute([$testEndpoint]);
check("Subscription đã lưu thành công vào MySQL", (int)$stmt->fetchColumn() === 1);

// Test gửi thông báo thử nghiệm (POST api/push/send.php action=test)
$r = req("$baseUrl/api/push/send.php", 'POST', [
    'action' => 'test',
    'title'  => 'Kiểm tra thông báo PWA',
    'body'   => 'Trận đấu của bạn sắp diễn ra!'
]);
check("api/push/send.php gửi thông báo thử nghiệm thành công (HTTP 200)", $r['status'] === 200 && ($r['data']['status'] ?? '') === 'success');
check("api/push/send.php trả payload chuẩn để client hiển thị", !empty($r['data']['data']['payload']['title']));

// Test quét nhắc lịch (POST api/push/send.php action=reminder)
$r = req("$baseUrl/api/push/send.php", 'POST', ['action' => 'reminder']);
check("api/push/send.php quét nhắc lịch thành công (HTTP 200)", $r['status'] === 200 && isset($r['data']['data']['upcoming_count']));

// Test hủy subscription (POST api/push/subscribe.php action=unsubscribe)
$r = req("$baseUrl/api/push/subscribe.php", 'POST', [
    'action'   => 'unsubscribe',
    'endpoint' => $testEndpoint
]);
check("Hủy subscription thành công (HTTP 200)", $r['status'] === 200);

$stmt->execute([$testEndpoint]);
check("Subscription đã xóa khỏi MySQL sau khi hủy", (int)$stmt->fetchColumn() === 0);

echo "\n====================================================================\n";
echo "KẾT QUẢ KIỂM THỬ TUẦN 4: {$pass} ĐẠT, {$fail} LỖI.\n";
echo "====================================================================\n";
exit($fail > 0 ? 1 : 0);
