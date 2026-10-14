<?php
/**
 * tests/oversell_test.php — Task 11: kiểm thử nhiều người cùng giữ 1 khung giờ → không oversell.
 * Người phụ trách: Huy (Tuần 2). Dùng giao diện hold.php hiện tại: { slot_ids:[...], action:'hold'|'release' }.
 *
 * Yêu cầu: đã import schema.sql + seed.sql (không cần seed slot — test tự gọi api/slots.php để sinh slot ngày mai).
 *   php tests/oversell_test.php                                        # tự bật PHP built-in server cổng 8099
 *   BASE_URL=http://localhost/GODMINTON php tests/oversell_test.php    # test trên XAMPP/Apache thật
 * Tài khoản: khach1@gmail.com và khach2@gmail.com (mật khẩu Khach@123; khach2 tự tạo nếu chưa có).
 * Test chỉ đụng slot "Thảm A3" của sân 1 ngày mai, xoá đơn test tạo ra và trả slot về 'available' khi xong.
 */
require_once __DIR__ . '/../config/db.php';

$ROUNDS = (int)(getenv('ROUNDS') ?: 20);
$base   = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8099', '/');
$server = null;
if (!getenv('BASE_URL')) {
    $cmd = sprintf('PHP_CLI_SERVER_WORKERS=8 php -S 127.0.0.1:8099 -t %s', escapeshellarg(dirname(__DIR__)));
    $server = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    usleep(800000);
    register_shutdown_function(function () use ($server) { if ($server) proc_terminate($server); });
}

$pass = 0; $fail = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? "  [PASS] " : "  [FAIL] ") . $name . ($ok || $detail === '' ? '' : "  → $detail") . "\n";
}
function req(string $url, ?array $body, string $jar = '', string $method = 'POST'): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_HTTPHEADER => ['Content-Type: application/json']]);
    if ($method === 'POST') { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? new stdClass)); }
    if ($jar) { curl_setopt($ch, CURLOPT_COOKIEJAR, $jar); curl_setopt($ch, CURLOPT_COOKIEFILE, $jar); }
    $raw = curl_exec($ch);
    $r = ['http' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'json' => json_decode((string)$raw, true), 'raw' => (string)$raw];
    curl_close($ch);
    return $r;
}
function holdH(string $base, array $ids, string $jar, string $action = 'hold') {
    $ch = curl_init("$base/api/hold.php");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['slot_ids' => $ids, 'action' => $action]),
        CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar]);
    return $ch;
}
function hold(string $base, array $ids, string $jar, string $action = 'hold'): array {
    return parallel([holdH($base, $ids, $jar, $action)])[0];
}
function parallel(array $handles): array {
    $mh = curl_multi_init();
    foreach ($handles as $h) curl_multi_add_handle($mh, $h);
    do { curl_multi_exec($mh, $running); curl_multi_select($mh, 0.05); } while ($running > 0);
    $out = [];
    foreach ($handles as $h) {
        $raw = (string)curl_multi_getcontent($h);
        $out[] = ['http' => curl_getinfo($h, CURLINFO_HTTP_CODE), 'json' => json_decode($raw, true), 'raw' => $raw];
        curl_multi_remove_handle($mh, $h); curl_close($h);
    }
    curl_multi_close($mh);
    return $out;
}
function row(PDO $pdo, int $id): array {
    $s = $pdo->prepare('SELECT status, held_by, hold_expires_at FROM time_slots WHERE id = ?'); $s->execute([$id]);
    return $s->fetch() ?: [];
}

// ---------------------------------------------------------------- chuẩn bị
$hash = password_hash('Khach@123', PASSWORD_BCRYPT);
$pdo->prepare("INSERT IGNORE INTO users (name, email, phone, password_hash, role, is_verified)
               VALUES ('Khách Test 2', 'khach2@gmail.com', '0900000002', ?, 'customer', 1)")->execute([$hash]);
$uid = fn(string $e) => (int)$pdo->query("SELECT id FROM users WHERE email = " . $pdo->quote($e))->fetchColumn();
$A = ['id' => $uid('khach1@gmail.com'), 'jar' => tempnam(sys_get_temp_dir(), 'ckA')];
$B = ['id' => $uid('khach2@gmail.com'), 'jar' => tempnam(sys_get_temp_dir(), 'ckB')];
foreach ([[$A, 'khach1@gmail.com'], [$B, 'khach2@gmail.com']] as [$u, $email]) {
    $r = req("$base/api/auth/login.php", ['email' => $email, 'password' => 'Khach@123'], $u['jar']);
    if ($r['http'] !== 200) { fwrite(STDERR, "Không đăng nhập được $email (HTTP {$r['http']}): {$r['raw']}\n"); exit(2); }
}

$date = (new DateTime('tomorrow'))->format('Y-m-d');
req("$base/api/slots.php?court_id=1&date=$date", null, $A['jar'], 'GET');      // slots.php tự sinh slot nếu ngày chưa có
$q = $pdo->prepare("SELECT id FROM time_slots WHERE court_id = 1 AND slot_date = ? AND sub_court_name = 'Thảm A3' ORDER BY start_time");
$q->execute([$date]);
$ids = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
if (count($ids) < $ROUNDS + 12) { fwrite(STDERR, "Thiếu slot cho test (có " . count($ids) . ")\n"); exit(2); }
$cleanup = function () use ($pdo, $ids) {
    $in = implode(',', $ids);
    $pdo->exec("DELETE FROM bookings WHERE id IN (SELECT booking_id FROM booking_slots WHERE slot_id IN ($in))");
    $pdo->exec("UPDATE time_slots SET status='available', held_by=NULL, hold_expires_at=NULL WHERE id IN ($in)");
};
$cleanup();
$raceIds = array_slice($ids, 0, $ROUNDS);
$x = array_slice($ids, $ROUNDS);

// ---------------------------------------------------------------- 1
echo "\n== 1. Cuộc đua: 2 người cùng giữ 1 khung giờ ($ROUNDS lượt, request song song) ==\n";
$bad = []; $aw = 0; $bw = 0;
foreach ($raceIds as $sid) {
    [$ra, $rb] = parallel([holdH($base, [$sid], $A['jar']), holdH($base, [$sid], $B['jar'])]);
    $codes = [$ra['http'], $rb['http']]; sort($codes);
    $r = row($pdo, $sid);
    $win = $ra['http'] === 200 ? $A['id'] : ($rb['http'] === 200 ? $B['id'] : null);
    if ($codes === [200, 409] && $r['status'] === 'holding' && (int)$r['held_by'] === $win) { $ra['http'] === 200 ? $aw++ : $bw++; }
    else $bad[] = "slot $sid: HTTP " . json_encode([$ra['http'], $rb['http']]) . " DB " . json_encode($r);
}
check("Mọi lượt: đúng 1 người 200, 1 người 409, DB held_by = người thắng", !$bad, implode(' | ', array_slice($bad, 0, 3)));
echo "      (A thắng $aw lượt, B thắng $bw lượt)\n";
$cleanup();

// ---------------------------------------------------------------- 2
echo "\n== 2. Giữ NHIỀU ô giao nhau, thứ tự ngược nhau (kiểm tra không deadlock / không giữ nửa vời) ==\n";
$bad = []; $n500 = 0;
for ($i = 0; $i < 10; $i++) {
    $s1 = $x[0]; $s2 = $x[1]; $s3 = $x[2];
    [$ra, $rb] = parallel([holdH($base, [$s1, $s2, $s3], $A['jar']), holdH($base, [$s3, $s2, $s1], $B['jar'])]);
    $codes = [$ra['http'], $rb['http']]; sort($codes);
    if (in_array(500, $codes, true)) $n500++;
    $holders = array_unique(array_map(fn($s) => (int)row($pdo, $s)['held_by'], [$s1, $s2, $s3]));
    if ($codes !== [200, 409] || count($holders) !== 1) $bad[] = "lượt $i: " . json_encode($codes) . " holders=" . json_encode($holders);
    $cleanup();
}
check("10 lượt: đúng 1 bên thắng cả 3 ô, bên kia 409, cả 3 ô thuộc cùng 1 người", !$bad, implode(' | ', array_slice($bad, 0, 3)));
check("Không có lỗi 500 / deadlock", $n500 === 0, "số 500: $n500");

// ---------------------------------------------------------------- 3
echo "\n== 3. Người thua nhận lỗi rõ ràng & không bị khoá treo ==\n";
$s = $x[3];
hold($base, [$s], $A['jar']);
$r = hold($base, [$s], $B['jar']);
check("Người thua: HTTP 409 + status 'error' + message tiếng Việt", $r['http'] === 409 && ($r['json']['status'] ?? '') === 'error' && !empty($r['json']['message']), $r['raw']);
$r = hold($base, [$x[4]], $B['jar']);
check("Người thua vẫn giữ được khung giờ khác ngay sau đó", $r['http'] === 200, $r['raw']);
$cleanup();

echo "\n== 4. Hai người giữ 2 ô KHÁC nhau cùng lúc → cả hai thành công ==\n";
[$ra, $rb] = parallel([holdH($base, [$x[5]], $A['jar']), holdH($base, [$x[6]], $B['jar'])]);
check("Cả hai đều HTTP 200", $ra['http'] === 200 && $rb['http'] === 200, json_encode([$ra['http'], $rb['http']]));
$cleanup();

// ---------------------------------------------------------------- 5
echo "\n== 5. Định dạng response (frontend của Minh + hợp đồng API) ==\n";
$r = hold($base, [$x[0], $x[1]], $A['jar']); $d = $r['json']['data'] ?? [];
check("200 có slot_ids/time_range/total_price/deposit_amount/expires_in", $r['http'] === 200 && ($d['slot_ids'] ?? []) === [$x[0], $x[1]] && isset($d['time_range'], $d['total_price'], $d['deposit_amount'], $d['expires_in']), $r['raw']);
check("expires_in ≈ 300 (298–300)", in_array($d['expires_in'] ?? -1, [298, 299, 300], true), (string)($d['expires_in'] ?? 'null'));
check("hold_expires_at ISO 8601 +07:00 (theo hợp đồng)", (bool)preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\+07:00$/', $d['hold_expires_at'] ?? ''), (string)($d['hold_expires_at'] ?? ''));
$r = hold($base, [$x[0], $x[1]], $A['jar']);
check("Chính mình giữ lại → 200 (gia hạn)", $r['http'] === 200, $r['raw']);
$r = hold($base, [$x[2]], $A['jar']);
check("Chọn vùng khác → ô cũ tự nhả về available", $r['http'] === 200 && row($pdo, $x[0])['status'] === 'available' && row($pdo, $x[1])['status'] === 'available');
$r = hold($base, [$x[2]], $A['jar'], 'release');
check("action=release → ô về available", $r['http'] === 200 && row($pdo, $x[2])['status'] === 'available', $r['raw']);
hold($base, [$x[3]], $A['jar']);
$r = hold($base, [$x[3]], $B['jar'], 'release');
check("Người khác KHÔNG release được ô đang do mình giữ", row($pdo, $x[3])['status'] === 'holding' && (int)row($pdo, $x[3])['held_by'] === $A['id']);
$cleanup();

// ---------------------------------------------------------------- 6
echo "\n== 6. Hết hạn giữ chỗ & script dọn (Task 8) ==\n";
hold($base, [$x[0]], $A['jar']);
$pdo->prepare("UPDATE time_slots SET hold_expires_at = NOW() - INTERVAL 1 MINUTE WHERE id = ?")->execute([$x[0]]);
$r = hold($base, [$x[0]], $B['jar']);
check("Ô holding đã hết hạn → người khác giữ được (200)", $r['http'] === 200 && (int)row($pdo, $x[0])['held_by'] === $B['id'], $r['raw']);
$cleanup();
hold($base, [$x[1]], $A['jar']);     // sẽ cho hết hạn
hold($base, [$x[2]], $B['jar']);     // còn hạn
$pdo->prepare("UPDATE time_slots SET hold_expires_at = NOW() - INTERVAL 2 MINUTE WHERE id = ?")->execute([$x[1]]);
$out = shell_exec('php ' . escapeshellarg(__DIR__ . '/../api/cleanup-expired.php') . ' 2>&1');
check("cleanup-expired.php (CLI) chạy không lỗi", is_string($out) && str_contains($out, 'Đã dọn'), (string)$out);
$r1 = row($pdo, $x[1]);
check("Ô hết hạn → available, held_by & hold_expires_at = NULL", $r1['status'] === 'available' && $r1['held_by'] === null && $r1['hold_expires_at'] === null, json_encode($r1));
check("Ô còn hạn KHÔNG bị dọn nhầm", row($pdo, $x[2])['status'] === 'holding');
$http = json_decode((string)@file_get_contents("$base/api/cleanup-expired.php"), true);
check("cleanup-expired.php (HTTP) trả JSON success", ($http['status'] ?? '') === 'success', json_encode($http));
$cleanup();

// ---------------------------------------------------------------- 7
echo "\n== 7. Luồng đầy đủ: giữ chỗ → tạo đơn (api/bookings.php) → ô thành 'booked' → không giữ được nữa ==\n";
hold($base, [$x[7], $x[8]], $A['jar']);
$r = req("$base/api/bookings.php", ['slot_ids' => [$x[7], $x[8]], 'court_id' => 1, 'payment_method' => 'VietQR'], $A['jar']);
check("Tạo đơn thành công (HTTP 201)", $r['http'] === 201 && !empty($r['json']['data']['booking_code']), $r['raw']);
check("Cả 2 ô chuyển sang 'booked', held_by = NULL", row($pdo, $x[7])['status'] === 'booked' && row($pdo, $x[8])['status'] === 'booked' && row($pdo, $x[7])['held_by'] === null);
$r = hold($base, [$x[7]], $B['jar']);
check("Người khác giữ ô đã booked → 409", $r['http'] === 409, $r['raw']);
$r = req("$base/api/bookings.php", ['slot_ids' => [$x[9]], 'court_id' => 1], $B['jar']);
check("Tạo đơn cho ô CHƯA giữ → bị từ chối (409)", $r['http'] === 409, $r['raw']);
$cleanup();

// ---------------------------------------------------------------- 8
echo "\n== 8. Xác thực & lỗi đầu vào ==\n";
check("Chưa đăng nhập → 401", hold($base, [$x[0]], tempnam(sys_get_temp_dir(), 'ckN'))['http'] === 401);
check("Không có slot_id/slot_ids → 422", hold($base, [], $A['jar'])['http'] === 422);
check("Slot không tồn tại → 404", hold($base, [999999999], $A['jar'])['http'] === 404);
check("action không hợp lệ → 422", hold($base, [$x[0]], $A['jar'], 'abc')['http'] === 422);
$other = $pdo->query("SELECT id FROM time_slots WHERE court_id = 2 LIMIT 1")->fetchColumn();
if (!$other) { req("$base/api/slots.php?court_id=2&date=$date", null, $A['jar'], 'GET'); $other = $pdo->query("SELECT id FROM time_slots WHERE court_id = 2 LIMIT 1")->fetchColumn(); }
check("Các ô khác sân trong cùng 1 lần giữ → 422", hold($base, [$x[0], (int)$other], $A['jar'])['http'] === 422);
$ch = curl_init("$base/api/hold.php"); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]); curl_exec($ch);
check("GET (sai phương thức) → 405", curl_getinfo($ch, CURLINFO_HTTP_CODE) === 405); curl_close($ch);
$cleanup();

echo "\n==== KẾT QUẢ: $pass đạt, $fail lỗi ====\n";
@unlink($A['jar']); @unlink($B['jar']);
exit($fail ? 1 : 0);
