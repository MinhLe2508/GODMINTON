<?php
/**
 * tests/audit_logic.php — Tự động kiểm tra sâu toàn bộ logic nghiệp vụ dự án GODMINTON
 * Chạy trên PHP CLI: php tests/audit_logic.php
 */
require_once __DIR__ . '/../config/db.php';

$baseUrl = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8098', '/');
$passCount = 0;
$failCount = 0;

function test_assert(string $label, bool $condition, string $details = '') {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$label}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$label}" . ($details ? " -> {$details}" : "") . "\n";
    }
}

function http_call(string $url, string $method = 'GET', $body = null, string $cookieFile = '', array $extraHeaders = []): array {
    $ch = curl_init($url);
    $headers = array_merge(['Accept: application/json'], $extraHeaders);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CUSTOMREQUEST  => $method,
    ];
    if ($cookieFile) {
        $opts[CURLOPT_COOKIEJAR]  = $cookieFile;
        $opts[CURLOPT_COOKIEFILE] = $cookieFile;
    }
    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        $opts[CURLOPT_POSTFIELDS] = is_string($body) ? $body : json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    $opts[CURLOPT_HTTPHEADER] = $headers;
    curl_setopt_array($ch, $opts);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $json = json_decode((string)$res, true);
    return ['status' => $code, 'data' => $json, 'raw' => (string)$res];
}

echo "====================================================================\n";
echo "   KIỂM TRA LOGIC TOÀN DIỆN DỰ ÁN GODMINTON (CHUẨN BỊ TUẦN 4)\n";
echo "====================================================================\n\n";

// -------------------------------------------------------------
// 1. KIỂM TRA CẤU TRÚC DATABASE VÀ RÀNG BUỘC
// -------------------------------------------------------------
echo "1. KIỂM TRA CẤU TRÚC DATABASE & RÀNG BUỘC:\n";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$expectedTables = ['users', 'wards', 'courts', 'court_images', 'amenities', 'court_amenities', 'time_slots', 'bookings', 'booking_slots', 'contacts', 'news'];
foreach ($expectedTables as $t) {
    test_assert("Bảng '{$t}' tồn tại trong database", in_array($t, $tables, true));
}

// Kiểm tra các cột tuần 3 trong bảng bookings
$bookingCols = $pdo->query("SHOW COLUMNS FROM bookings")->fetchAll(PDO::FETCH_COLUMN);
test_assert("Bảng bookings có cột 'booking_code'", in_array('booking_code', $bookingCols, true));
test_assert("Bảng bookings có cột 'payment_method'", in_array('payment_method', $bookingCols, true));
test_assert("Bảng bookings có cột 'notes'", in_array('notes', $bookingCols, true));

// Kiểm tra múi giờ MySQL
$dbTz = $pdo->query("SELECT @@session.time_zone")->fetchColumn();
test_assert("Múi giờ MySQL đã đồng bộ +07:00", $dbTz === '+07:00', "Múi giờ hiện tại: {$dbTz}");

// -------------------------------------------------------------
// 2. CHUẨN BỊ DỮ LIỆU TEST & AUTH
// -------------------------------------------------------------
echo "\n2. KIỂM TRA XÁC THỰC (AUTH) & PHÂN QUYỀN:\n";
$cookieCustomer1 = tempnam(sys_get_temp_dir(), 'gdm_c1_');
$cookieCustomer2 = tempnam(sys_get_temp_dir(), 'gdm_c2_');
$cookieOwner1     = tempnam(sys_get_temp_dir(), 'gdm_ow1_');
$cookieAdmin      = tempnam(sys_get_temp_dir(), 'gdm_adm_');
$cookieContact    = tempnam(sys_get_temp_dir(), 'gdm_cnt_');

// Seed test users (role: customer, owner, admin)
$hash = password_hash('Pass@123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, is_verified) 
               VALUES ('Khách 1', 'test_user1@godminton.vn', '0901000001', ?, 'customer', 1)
               ON DUPLICATE KEY UPDATE password_hash=?, role='customer'")->execute([$hash, $hash]);

$pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, is_verified) 
               VALUES ('Khách 2', 'test_user2@godminton.vn', '0901000002', ?, 'customer', 1)
               ON DUPLICATE KEY UPDATE password_hash=?, role='customer'")->execute([$hash, $hash]);

$pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, is_verified) 
               VALUES ('Chủ Sân 1', 'test_owner1@godminton.vn', '0902000001', ?, 'owner', 1)
               ON DUPLICATE KEY UPDATE password_hash=?, role='owner'")->execute([$hash, $hash]);

$pdo->prepare("INSERT INTO users (name, email, phone, password_hash, role, is_verified) 
               VALUES ('Quản Trị Viên', 'test_admin@godminton.vn', '0903000001', ?, 'admin', 1)
               ON DUPLICATE KEY UPDATE password_hash=?, role='admin'")->execute([$hash, $hash]);

// Test login Customer 1
$r = http_call("$baseUrl/api/auth/login.php", 'POST', ['email' => 'test_user1@godminton.vn', 'password' => 'Pass@123'], $cookieCustomer1);
test_assert("Đăng nhập Khách 1 thành công (HTTP 200)", $r['status'] === 200 && ($r['data']['status'] ?? '') === 'success');

// Test me.php Customer 1
$r = http_call("$baseUrl/api/auth/me.php", 'GET', null, $cookieCustomer1);
test_assert("api/auth/me.php trả thông tin Khách 1 và csrf_token", $r['status'] === 200 && !empty($r['data']['data']['csrf_token']) && $r['data']['data']['user']['email'] === 'test_user1@godminton.vn');
$csrfC1 = $r['data']['data']['csrf_token'] ?? '';

// Test login Customer 2
$r = http_call("$baseUrl/api/auth/login.php", 'POST', ['email' => 'test_user2@godminton.vn', 'password' => 'Pass@123'], $cookieCustomer2);
test_assert("Đăng nhập Khách 2 thành công", $r['status'] === 200);

// Test login Owner 1
$r = http_call("$baseUrl/api/auth/login.php", 'POST', ['email' => 'test_owner1@godminton.vn', 'password' => 'Pass@123'], $cookieOwner1);
test_assert("Đăng nhập Chủ sân 1 thành công", $r['status'] === 200);

// Test login Admin
$r = http_call("$baseUrl/api/auth/login.php", 'POST', ['email' => 'test_admin@godminton.vn', 'password' => 'Pass@123'], $cookieAdmin);
test_assert("Đăng nhập Quản trị viên thành công", $r['status'] === 200);

// -------------------------------------------------------------
// 3. KIỂM TRA LOGIC GIỮ CHỖ (HOLD) & OVERSELL TRONG API/HOLD.PHP
// -------------------------------------------------------------
echo "\n3. KIỂM TRA LOGIC GIỮ CHỖ (HOLD & CONCURRENCY):\n";
$testDate = '2026-12-01';
$r = http_call("$baseUrl/api/slots.php?court_id=1&date=$testDate", 'GET');
test_assert("api/slots.php tự động tạo slots nếu ngày chưa có", $r['status'] === 200 && count($r['data']['data'] ?? []) > 0);

$slotsData = $r['data']['data'] ?? [];
$slotsA1 = array_values(array_filter($slotsData, fn($s) => $s['sub_court_name'] === 'Thảm A1'));
test_assert("Có ít nhất 2 slot Thảm A1 cho test", count($slotsA1) >= 2);
$slot1 = $slotsA1[0];
$slot2 = $slotsA1[1];
$slot1Id = $slot1['id'];
$slot2Id = $slot2['id'];

// Đặt trạng thái slot về available trước khi test
$pdo->prepare("UPDATE time_slots SET status='available', held_by=NULL, hold_expires_at=NULL WHERE id IN (?, ?)")->execute([$slot1Id, $slot2Id]);

// Customer 1 giữ slot1 và slot2
$r = http_call("$baseUrl/api/hold.php", 'POST', ['slot_ids' => [$slot1Id, $slot2Id]], $cookieCustomer1);
test_assert("Khách 1 giữ 2 slot thành công (200, status=holding)", $r['status'] === 200 && ($r['data']['data']['status'] ?? '') === 'holding');

// Customer 2 cố tình giữ trùng slot1 -> PHẢI BỊ CHẶN 409
$r = http_call("$baseUrl/api/hold.php", 'POST', ['slot_ids' => [$slot1Id]], $cookieCustomer2);
test_assert("Khách 2 giữ trùng slot của Khách 1 -> Bị từ chối 409", $r['status'] === 409, "HTTP code: {$r['status']}");

// Customer 1 giữ lại slot1 (tự gia hạn chính mình) -> Hợp lệ
$r = http_call("$baseUrl/api/hold.php", 'POST', ['slot_ids' => [$slot1Id]], $cookieCustomer1);
test_assert("Khách 1 tự giữ lại slot của mình -> Cho phép gia hạn (200)", $r['status'] === 200);

// Giả lập slot1 hết hạn (hold_expires_at < NOW())
$pdo->prepare("UPDATE time_slots SET hold_expires_at = DATE_SUB(NOW(), INTERVAL 1 MINUTE) WHERE id = ?")->execute([$slot1Id]);

// Customer 2 giữ slot1 vừa hết hạn -> Cho phép thành công
$r = http_call("$baseUrl/api/hold.php", 'POST', ['slot_ids' => [$slot1Id]], $cookieCustomer2);
test_assert("Slot hết hạn: Khách 2 giữ thành công (coi như trống)", $r['status'] === 200);

// Customer 2 nhả slot (release)
$r = http_call("$baseUrl/api/hold.php", 'POST', ['action' => 'release', 'slot_ids' => [$slot1Id]], $cookieCustomer2);
test_assert("Khách 2 nhả giữ chỗ (release) thành công", $r['status'] === 200);

// -------------------------------------------------------------
// 4. KIỂM TRA LOGIC TẠO ĐƠN ĐẶT SÂN (BOOKING CREATION)
// -------------------------------------------------------------
echo "\n4. KIỂM TRA LOGIC TẠO ĐƠN & TÍNH TIỀN (API/BOOKINGS.PHP):\n";
// Customer 1 giữ lại 2 slot liên tiếp: slot1 và slot2
$pdo->prepare("UPDATE time_slots SET status='available', held_by=NULL, hold_expires_at=NULL WHERE id IN (?, ?)")->execute([$slot1Id, $slot2Id]);
$r = http_call("$baseUrl/api/hold.php", 'POST', ['slot_ids' => [$slot1Id, $slot2Id]], $cookieCustomer1);
test_assert("Khách 1 giữ 2 slot liên tiếp trước khi đặt", $r['status'] === 200);

$originHeader = 'Origin: ' . parse_url($baseUrl, PHP_URL_SCHEME) . '://' . parse_url($baseUrl, PHP_URL_HOST) . ':' . (parse_url($baseUrl, PHP_URL_PORT) ?: 80);

// Customer 2 cố tình thanh toán/xác nhận 2 slot mà Khách 1 đang giữ -> PHẢI BỊ CHẶN 403
$r = http_call("$baseUrl/api/bookings.php", 'POST', [
    'court_id' => 1,
    'slot_ids' => [$slot1Id, $slot2Id],
    'payment_method' => 'Chuyển khoản VietQR',
    'notes' => 'Test hack'
], $cookieCustomer2, [$originHeader]);
test_assert("Khách khác cố đặt slot không phải của mình -> Bị chặn 403", $r['status'] === 403);

// Customer 1 gửi slot không liên tiếp (slot 1 và slot 4)
$slot4 = $slotsA1[3] ?? null;
if ($slot4) {
    $c1Id = (int)$pdo->query("SELECT id FROM users WHERE email='test_user1@godminton.vn'")->fetchColumn();
    $pdo->prepare("UPDATE time_slots SET status='holding', held_by=?, hold_expires_at=DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id=?")->execute([$c1Id, $slot4['id']]);
    $r = http_call("$baseUrl/api/bookings.php", 'POST', [
        'court_id' => 1,
        'slot_ids' => [$slot1Id, $slot4['id']],
        'payment_method' => 'Chuyển khoản VietQR'
    ], $cookieCustomer1, [$originHeader]);
    test_assert("Khung giờ không liên tiếp -> Bị từ chối 422", $r['status'] === 422);
}

// Customer 1 đặt đơn hợp lệ
$r = http_call("$baseUrl/api/bookings.php", 'POST', [
    'court_id' => 1,
    'slot_ids' => [$slot1Id, $slot2Id],
    'payment_method' => 'Chuyển khoản VietQR',
    'notes' => 'Đơn test tự động'
], $cookieCustomer1, [$originHeader]);

test_assert("Khách 1 tạo đơn thành công (201 Created)", $r['status'] === 201 && ($r['data']['status'] ?? '') === 'success');
$newBookingId = $r['data']['data']['booking_id'] ?? 0;
$newBookingCode = $r['data']['data']['booking_code'] ?? '';
test_assert("Có mã đơn booking_code định dạng GDM-YYYYMMDD-XXXX", (bool)preg_match('/^GDM-\d{8}-\d{4}$/', $newBookingCode), "Code: {$newBookingCode}");

// Kiểm tra 2 slot trong time_slots đã đổi sang 'booked'
$chkSlots = $pdo->prepare("SELECT status FROM time_slots WHERE id IN (?, ?)");
$chkSlots->execute([$slot1Id, $slot2Id]);
$statuses = $chkSlots->fetchAll(PDO::FETCH_COLUMN);
test_assert("Trạng thái cả 2 slot đổi thành 'booked' trong DB", count($statuses) === 2 && $statuses[0] === 'booked' && $statuses[1] === 'booked');

// -------------------------------------------------------------
// 5. KIỂM TRA LỊCH SỬ & HUỶ ĐƠN (CANCELLATION)
// -------------------------------------------------------------
echo "\n5. KIỂM TRA LỊCH SỬ ĐƠN & HUỶ ĐƠN (API/CANCEL.PHP):\n";
// Lấy danh sách lịch sử của Khách 1
$r = http_call("$baseUrl/api/bookings.php", 'GET', null, $cookieCustomer1);
$myBookings = $r['data']['data'] ?? [];
$hasCreated = false;
foreach ($myBookings as $b) {
    if ($b['id'] === $newBookingId) {
        $hasCreated = true;
        test_assert("Trường can_cancel = true vì ngày 2026-12-01 ở tương lai", $b['can_cancel'] === true);
        break;
    }
}
test_assert("Khách 1 xem thấy đơn vừa tạo trong lịch sử", $hasCreated);

// Lấy CSRF của Khách 2
$r2 = http_call("$baseUrl/api/auth/me.php", 'GET', null, $cookieCustomer2);
$csrfC2 = $r2['data']['data']['csrf_token'] ?? '';

// Khách 2 cố tình hủy đơn của Khách 1 -> Bị từ chối 404
$r = http_call("$baseUrl/api/cancel.php", 'POST', ['booking_id' => $newBookingId], $cookieCustomer2, ["X-GODMINTON-CSRF: {$csrfC2}"]);
test_assert("Khách khác không thể huỷ đơn của Khách 1 (404 Not Found)", $r['status'] === 404);

// Khách 1 huỷ đơn hợp lệ
$r = http_call("$baseUrl/api/cancel.php", 'POST', ['booking_id' => $newBookingId], $cookieCustomer1, ["X-GODMINTON-CSRF: {$csrfC1}"]);
test_assert("Khách 1 huỷ đơn trước giờ chơi thành công (200)", $r['status'] === 200);

// Kiểm tra sau khi huỷ: đơn đổi sang 'cancelled' và các slot đổi về 'available'
$bStatus = $pdo->query("SELECT status FROM bookings WHERE id = {$newBookingId}")->fetchColumn();
test_assert("Trạng thái đơn bookings đổi thành 'cancelled'", $bStatus === 'cancelled');

$chkSlots->execute([$slot1Id, $slot2Id]);
$statuses = $chkSlots->fetchAll(PDO::FETCH_COLUMN);
test_assert("Các slot đổi lại thành 'available' sau khi huỷ đơn", count($statuses) === 2 && $statuses[0] === 'available' && $statuses[1] === 'available');

// -------------------------------------------------------------
// 6. KIỂM TRA THÔNG TIN HỒ SƠ & BẢO MẬT PROFILE
// -------------------------------------------------------------
echo "\n6. KIỂM TRA HỒ SƠ TÀI KHOẢN (API/PROFILE.PHP):\n";
$r = http_call("$baseUrl/api/profile.php", 'POST', [
    'name'  => 'Khách 1 Đã Đổi Tên',
    'email' => 'test_user1@godminton.vn',
    'phone' => '0901999999'
], $cookieCustomer1, ["X-GODMINTON-CSRF: {$csrfC1}"]);
test_assert("Cập nhật tên & SĐT có CSRF hợp lệ -> 200", $r['status'] === 200);

// Cập nhật không có CSRF -> Phải bị 403
$r = http_call("$baseUrl/api/profile.php", 'POST', [
    'name' => 'Hacker'
], $cookieCustomer1);
test_assert("Cập nhật thiếu CSRF -> Bị chặn 403", $r['status'] === 403);

// -------------------------------------------------------------
// 7. KIỂM TRA NỘI DUNG LIÊN HỆ & TIN TỨC
// -------------------------------------------------------------
echo "\n7. KIỂM TRA TIN TỨC & LIÊN HỆ (API/CONTACT.PHP, API/NEWS.PHP):\n";
$r = http_call("$baseUrl/api/news.php", 'GET');
test_assert("api/news.php trả danh sách tin tức từ MySQL", $r['status'] === 200 && is_array($r['data']['data'] ?? null));

// Lấy CSRF từ contact.php bằng cùng cookie session
$rContactCsrf = http_call("$baseUrl/api/contact.php", 'GET', null, $cookieContact);
$contactCsrf = $rContactCsrf['data']['data']['csrf_token'] ?? '';
test_assert("api/contact.php GET sinh csrf_token thành công", !empty($contactCsrf));

$r = http_call("$baseUrl/api/contact.php", 'POST', [
    'name' => 'Người Hỏi',
    'email' => 'hoi@gmail.com',
    'phone' => '0912000000',
    'subject' => 'Hỏi giá sân',
    'message' => 'Sân có chỗ để xe hơi không?'
], $cookieContact, ["X-GODMINTON-CSRF: {$contactCsrf}"]);
test_assert("Gửi tin nhắn liên hệ qua api/contact.php thành công (201)", $r['status'] === 201);

// Dọn dẹp test
$pdo->prepare("DELETE FROM bookings WHERE id = ?")->execute([$newBookingId]);
$pdo->prepare("DELETE FROM booking_slots WHERE booking_id = ?")->execute([$newBookingId]);
$pdo->prepare("DELETE FROM users WHERE email LIKE 'test_%@godminton.vn'")->execute();
@unlink($cookieCustomer1);
@unlink($cookieCustomer2);
@unlink($cookieOwner1);
@unlink($cookieAdmin);
@unlink($cookieContact);

echo "\n====================================================================\n";
echo "TỔNG KẾT KIỂM TRA LOGIC: {$passCount} ĐẠT, {$failCount} LỖI.\n";
echo "====================================================================\n";
exit($failCount > 0 ? 1 : 0);
