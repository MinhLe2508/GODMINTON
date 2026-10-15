<?php
/**
 * tests/admin_week3_test.php — Kiểm thử tự động nhiệm vụ Tuần 3 của Huy (Task 13, 14 & Dashboard).
 * Người phụ trách: Huy (Tuần 3).
 *
 * Cách chạy:
 *   php tests/admin_week3_test.php                                        # tự bật PHP built-in server cổng 8098
 *   BASE_URL=http://localhost/GODMINTON php tests/admin_week3_test.php    # test trên XAMPP/Apache thật
 */
require_once __DIR__ . '/../config/db.php';

$base   = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8098', '/');
$server = null;
if (!getenv('BASE_URL')) {
    $cmd = sprintf('PHP_CLI_SERVER_WORKERS=4 php -S 127.0.0.1:8098 -t %s', escapeshellarg(dirname(__DIR__)));
    $server = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    usleep(800000);
    register_shutdown_function(function () use ($server) { if ($server) proc_terminate($server); });
}

$pass = 0;
$fail = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? "  [PASS] " : "  [FAIL] ") . $name . ($ok || $detail === '' ? '' : "  → $detail") . "\n";
}

function req(string $url, ?array $body = null, string $jar = '', string $method = 'GET'): array {
    $ch = curl_init($url);
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CUSTOMREQUEST  => $method,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ];
    if ($body !== null && $method !== 'GET') {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE);
    }
    if ($jar !== '') {
        $opts[CURLOPT_COOKIEJAR]  = $jar;
        $opts[CURLOPT_COOKIEFILE] = $jar;
    }
    curl_setopt_array($ch, $opts);
    $raw  = (string)curl_exec($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['http' => $http, 'json' => json_decode($raw, true), 'raw' => $raw];
}

// ---------------------------------------------------------------- Chuẩn bị tài khoản test
$hashCustomer = password_hash('Khach@123', PASSWORD_BCRYPT);
$hashOwner    = password_hash('Owner@123', PASSWORD_BCRYPT);
$hashAdmin    = password_hash('Admin@123', PASSWORD_BCRYPT);

$pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, is_verified)
               VALUES ('Khách Hàng Test', 'khach1@gmail.com', '0912345678', ?, 'customer', 1)")->execute([$hashCustomer]);
$pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, is_verified)
               VALUES ('Chủ Sân Quận 1', 'owner.q1@godminton.vn', '0911111111', ?, 'owner', 1)")->execute([$hashOwner]);
$pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, is_verified)
               VALUES ('Chủ Sân Thứ 2', 'owner2.test@godminton.vn', '0922222222', ?, 'owner', 1)")->execute([$hashOwner]);
$pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, is_verified)
               VALUES ('Quản Trị Viên', 'admin@godminton.vn', '0900000000', ?, 'admin', 1)")->execute([$hashAdmin]);

$jarGuest    = tempnam(sys_get_temp_dir(), 'ckG');
$jarCustomer = tempnam(sys_get_temp_dir(), 'ckC');
$jarOwner1   = tempnam(sys_get_temp_dir(), 'ckO1');
$jarOwner2   = tempnam(sys_get_temp_dir(), 'ckO2');
$jarAdmin    = tempnam(sys_get_temp_dir(), 'ckAd');

req("$base/api/auth/login.php", ['email' => 'khach1@gmail.com',         'password' => 'Khach@123'], $jarCustomer, 'POST');
req("$base/api/auth/login.php", ['email' => 'owner.q1@godminton.vn',    'password' => 'Owner@123'], $jarOwner1,   'POST');
req("$base/api/auth/login.php", ['email' => 'owner2.test@godminton.vn', 'password' => 'Owner@123'], $jarOwner2,   'POST');
req("$base/api/auth/login.php", ['email' => 'admin@godminton.vn',       'password' => 'Admin@123'], $jarAdmin,    'POST');

// ---------------------------------------------------------------- 1. Phân quyền truy cập Admin API
echo "\n== 1. Kiểm tra bảo mật & phân quyền cơ bản trên các endpoint /api/admin/* ==\n";
check("Chưa đăng nhập gọi GET /api/admin/courts.php → 401",    req("$base/api/admin/courts.php", null, $jarGuest, 'GET')['http'] === 401);
check("Chưa đăng nhập gọi GET /api/admin/slots.php → 401",     req("$base/api/admin/slots.php?court_id=1", null, $jarGuest, 'GET')['http'] === 401);
check("Chưa đăng nhập gọi GET /api/admin/dashboard.php → 401", req("$base/api/admin/dashboard.php", null, $jarGuest, 'GET')['http'] === 401);

check("Khách hàng (customer) gọi GET /api/admin/courts.php → 403",    req("$base/api/admin/courts.php", null, $jarCustomer, 'GET')['http'] === 403);
check("Khách hàng (customer) gọi GET /api/admin/slots.php → 403",     req("$base/api/admin/slots.php?court_id=1", null, $jarCustomer, 'GET')['http'] === 403);
check("Khách hàng (customer) gọi GET /api/admin/dashboard.php → 403", req("$base/api/admin/dashboard.php", null, $jarCustomer, 'GET')['http'] === 403);

// ---------------------------------------------------------------- 2. Task 13: CRUD Sân & Phân quyền Chủ sân / Admin
echo "\n== 2. Task 13: CRUD sân (api/admin/courts.php) & cách ly quyền theo owner_id ==\n";
$rCreate = req("$base/api/admin/courts.php", [
    'name'             => 'Sân Test Tuần 3 - Owner 2',
    'district'         => 'Quận 1',
    'address'          => '99 Nguyễn Huệ, Phường Bến Nghé, Quận 1',
    'price_per_hour'   => 95000,
    'total_sub_courts' => 4,
    'opening_time'     => '06:00',
    'closing_time'     => '22:30',
    'status'           => 'active',
    'amenities'        => ['wifi', 'parking', 'aircon', 'canteen'],
], $jarOwner2, 'POST');

$newCourtId = (int)($rCreate['json']['data']['id'] ?? 0);
check("Owner 2 thêm sân mới thành công (HTTP 201)", $rCreate['http'] === 201 && $newCourtId > 0, $rCreate['raw']);
check("Tiện ích 'canteen' tự chuẩn hóa thành 'cafe' trong DB", in_array('cafe', $rCreate['json']['data']['amenities'] ?? [], true), json_encode($rCreate['json']['data']['amenities'] ?? []));

// Kiểm tra cách ly danh sách sân giữa Owner 1, Owner 2 và Admin
$listO1 = req("$base/api/admin/courts.php", null, $jarOwner1, 'GET');
$idsO1  = array_column($listO1['json']['data'] ?? [], 'id');
check("Owner 1 KHÔNG thấy sân của Owner 2 trong danh sách của mình", !in_array($newCourtId, $idsO1, true));

$listAd = req("$base/api/admin/courts.php", null, $jarAdmin, 'GET');
$idsAd  = array_column($listAd['json']['data'] ?? [], 'id');
check("Admin thấy TOÀN BỘ sân (gồm cả sân của Owner 1 và Owner 2)", in_array(1, $idsAd, true) && in_array($newCourtId, $idsAd, true));

// Owner 1 cố sửa/xóa sân của Owner 2 → bị chặn 403
$rHackEdit = req("$base/api/admin/courts.php", ['id' => $newCourtId, 'name' => 'Hack Name', 'address' => 'A', 'price_per_hour' => 50000], $jarOwner1, 'PUT');
check("Owner 1 cố tình sửa sân của Owner 2 → bị chặn 403", $rHackEdit['http'] === 403, $rHackEdit['raw']);

$rHackDel = req("$base/api/admin/courts.php?id=$newCourtId", null, $jarOwner1, 'DELETE');
check("Owner 1 cố tình xóa sân của Owner 2 → bị chặn 403", $rHackDel['http'] === 403, $rHackDel['raw']);

// Owner 2 tự cập nhật sân của mình → 200
$rUpdate = req("$base/api/admin/courts.php", [
    'id'               => $newCourtId,
    'name'             => 'Sân Test Tuần 3 - Đã Cập Nhật',
    'address'          => '99 Nguyễn Huệ, Quận 1',
    'price_per_hour'   => 110000,
    'total_sub_courts' => 6,
    'status'           => 'inactive',
], $jarOwner2, 'PUT');
check("Owner 2 cập nhật sân của chính mình → 200 & lưu đúng giá/trạng thái",
    $rUpdate['http'] === 200
    && ($rUpdate['json']['data']['price_per_hour'] ?? 0) === 110000
    && ($rUpdate['json']['data']['status'] ?? '') === 'inactive',
    $rUpdate['raw']
);

// Kích hoạt lại sân để test tạo slot và giữ chỗ
req("$base/api/admin/courts.php", ['id' => $newCourtId, 'status' => 'active'], $jarOwner2, 'PUT');

// ---------------------------------------------------------------- 3. Task 14: Quản lý khung giờ (api/admin/slots.php)
echo "\n== 3. Task 14: Tạo khung giờ hàng loạt, đổi trạng thái & xóa khung giờ (api/admin/slots.php) ==\n";
$testDate = (new DateTime('+5 days'))->format('Y-m-d');

// Owner 1 cố tạo khung giờ cho sân của Owner 2 → 403
$rHackSlot = req("$base/api/admin/slots.php", [
    'court_id'     => $newCourtId,
    'slot_date'    => $testDate,
    'start_time'   => '06:00',
    'end_time'     => '09:00',
    'duration_min' => 60,
    'price'        => 110000,
    'sub_courts'   => ['Thảm A1'],
], $jarOwner1, 'POST');
check("Owner 1 tạo khung giờ trên sân của Owner 2 → bị chặn 403", $rHackSlot['http'] === 403, $rHackSlot['raw']);

// Owner 2 tạo khung giờ hàng loạt: 06:00 -> 09:00, mỗi khung 60 phút, 2 sân con (Thảm A1, Thảm A2) => 3 x 2 = 6 khung giờ
$rGen = req("$base/api/admin/slots.php", [
    'court_id'     => $newCourtId,
    'slot_date'    => $testDate,
    'start_time'   => '06:00',
    'end_time'     => '09:00',
    'duration_min' => 60,
    'price'        => 110000,
    'sub_courts'   => ['Thảm A1', 'Thảm A2'],
], $jarOwner2, 'POST');
check("Tạo khung giờ hàng loạt (06:00-09:00, 60p, 2 thảm) → tạo đúng 6 khung giờ",
    $rGen['http'] === 200 && ($rGen['json']['data']['createdCount'] ?? 0) === 6,
    $rGen['raw']
);

// Bấm tạo lại cùng khoảng giờ → không tạo trùng (createdCount = 0)
$rGenDup = req("$base/api/admin/slots.php", [
    'court_id'     => $newCourtId,
    'slot_date'    => $testDate,
    'start_time'   => '06:00',
    'end_time'     => '09:00',
    'duration_min' => 60,
    'price'        => 110000,
    'sub_courts'   => ['Thảm A1', 'Thảm A2'],
], $jarOwner2, 'POST');
check("Tạo lặp lại cùng khoảng giờ → chống trùng lặp (createdCount = 0)",
    $rGenDup['http'] === 200 && ($rGenDup['json']['data']['createdCount'] ?? -1) === 0,
    $rGenDup['raw']
);

$slots = $rGen['json']['data']['slots'] ?? [];
$firstSlotId  = (int)($slots[0]['id'] ?? 0);
$secondSlotId = (int)($slots[1]['id'] ?? 0);

// Đổi trạng thái available → blocked
$rBlock = req("$base/api/admin/slots.php", ['slot_id' => $firstSlotId, 'status' => 'blocked'], $jarOwner2, 'PATCH');
check("Đổi trạng thái khung giờ sang 'blocked' (Ngừng nhận đặt) → 200",
    $rBlock['http'] === 200 && ($rBlock['json']['data']['status'] ?? '') === 'blocked',
    $rBlock['raw']
);

// Khách hàng cố giữ chỗ ô đã bị chủ sân khóa ('blocked') → bị chặn 409
$rHoldBlocked = req("$base/api/hold.php", ['slot_ids' => [$firstSlotId], 'action' => 'hold'], $jarCustomer, 'POST');
check("Khách hàng giữ chỗ khung giờ đang 'blocked' → bị từ chối 409", $rHoldBlocked['http'] === 409, $rHoldBlocked['raw']);

// Mở lại khung giờ blocked → available
$rUnblock = req("$base/api/admin/slots.php", ['slot_id' => $firstSlotId, 'status' => 'available'], $jarOwner2, 'PATCH');
check("Mở lại khung giờ từ 'blocked' sang 'available' → 200",
    $rUnblock['http'] === 200 && ($rUnblock['json']['data']['status'] ?? '') === 'available',
    $rUnblock['raw']
);

// Xóa 1 khung giờ
$rDelSlot = req("$base/api/admin/slots.php?id=$secondSlotId", null, $jarOwner2, 'DELETE');
check("Xóa từng khung giờ trống → 200", $rDelSlot['http'] === 200, $rDelSlot['raw']);

$rGetSlots = req("$base/api/admin/slots.php?court_id=$newCourtId&date=$testDate", null, $jarOwner2, 'GET');
check("Tổng số khung giờ sau khi xóa 1 ô còn lại 5", ($rGetSlots['json']['total'] ?? 0) === 5, $rGetSlots['raw']);

// ---------------------------------------------------------------- 4. Dashboard (api/admin/dashboard.php)
echo "\n== 4. Thống kê tổng quan Dashboard (api/admin/dashboard.php) ==\n";
$rDashO1 = req("$base/api/admin/dashboard.php", null, $jarOwner1, 'GET');
check("Owner 1 lấy thống kê dashboard → 200 & có đủ stats/recentBookings/courts",
    $rDashO1['http'] === 200
    && isset($rDashO1['json']['data']['stats']['totalCourts'])
    && isset($rDashO1['json']['data']['recentBookings'])
    && isset($rDashO1['json']['data']['courts']),
    $rDashO1['raw']
);

// Dọn dẹp sân test của Owner 2
$rCleanupCourt = req("$base/api/admin/courts.php?id=$newCourtId", null, $jarOwner2, 'DELETE');
check("Xóa sân test sau khi kiểm thử → 200", $rCleanupCourt['http'] === 200, $rCleanupCourt['raw']);

@unlink($jarGuest);
@unlink($jarCustomer);
@unlink($jarOwner1);
@unlink($jarOwner2);
@unlink($jarAdmin);

echo "\n==== KẾT QUẢ TUẦN 3 (HUY): $pass đạt, $fail lỗi ====\n";
exit($fail ? 1 : 0);
