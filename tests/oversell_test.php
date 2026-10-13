<?php
/**
 * tests/oversell_test.php — Task 11: kiểm thử 2 người cùng giữ 1 khung giờ → không oversell.
 * Người phụ trách: Huy (Tuần 2).
 *
 * Cách chạy (cần đã import schema.sql, seed.sql, migration_week2.sql, seed_slots.sql):
 *   php tests/oversell_test.php                       # tự bật PHP built-in server cổng 8099
 *   BASE_URL=http://localhost/godminton php tests/oversell_test.php   # test trên XAMPP/Apache thật
 *
 * Script dùng 2 tài khoản (2 cookie jar = 2 "trình duyệt") bắn request giữ chỗ SONG SONG bằng curl_multi.
 * Tài khoản thứ 2 (khach2@gmail.com / Khach@123) được tự tạo nếu chưa có.
 * Script chỉ đụng vào slot của ngày mai (sân 1) và trả chúng về 'available' khi xong.
 */
require_once __DIR__ . '/../config/db.php';

$ROUNDS = (int)(getenv('ROUNDS') ?: 25);
$base   = rtrim(getenv('BASE_URL') ?: 'http://127.0.0.1:8099', '/');
$server = null;

if (!getenv('BASE_URL')) {
    // PHP_CLI_SERVER_WORKERS: nhiều worker để request thật sự chạy song song (mặc định server chỉ 1 tiến trình).
    $cmd = sprintf('PHP_CLI_SERVER_WORKERS=8 php -S 127.0.0.1:8099 -t %s', escapeshellarg(dirname(__DIR__)));
    $server = proc_open($cmd, [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
    usleep(800000);
    register_shutdown_function(function () use ($server) { if ($server) proc_terminate($server); });
}

// ---------------------------------------------------------------- tiện ích
$pass = 0; $fail = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail;
    $ok ? $pass++ : $fail++;
    echo ($ok ? "  [PASS] " : "  [FAIL] ") . $name . ($ok || $detail === '' ? '' : "  → $detail") . "\n";
}
function post(string $url, ?array $body, string $jar = ''): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($body ?? new stdClass)]);
    if ($jar) { curl_setopt($ch, CURLOPT_COOKIEJAR, $jar); curl_setopt($ch, CURLOPT_COOKIEFILE, $jar); }
    $raw = curl_exec($ch);
    $r = ['http' => curl_getinfo($ch, CURLINFO_HTTP_CODE), 'json' => json_decode((string)$raw, true), 'raw' => $raw];
    curl_close($ch);
    return $r;
}
function holdReq(string $base, int $slotId, string $jar) {
    $ch = curl_init("$base/api/hold.php");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['slot_id' => $slotId]),
        CURLOPT_COOKIEFILE => $jar, CURLOPT_COOKIEJAR => $jar]);
    return $ch;
}
/** Bắn nhiều request cùng lúc, trả mảng [http, json] theo đúng thứ tự. */
function parallel(array $handles): array {
    $mh = curl_multi_init();
    foreach ($handles as $h) curl_multi_add_handle($mh, $h);
    do { curl_multi_exec($mh, $running); curl_multi_select($mh, 0.05); } while ($running > 0);
    $out = [];
    foreach ($handles as $h) {
        $out[] = ['http' => curl_getinfo($h, CURLINFO_HTTP_CODE), 'json' => json_decode((string)curl_multi_getcontent($h), true)];
        curl_multi_remove_handle($mh, $h); curl_close($h);
    }
    curl_multi_close($mh);
    return $out;
}
function slotRow(PDO $pdo, int $id): array {
    $s = $pdo->prepare('SELECT status, held_by, hold_expires_at FROM time_slots WHERE id = ?');
    $s->execute([$id]);
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
    $r = post("$base/api/auth/login.php", ['email' => $email, 'password' => 'Khach@123'], $u['jar']);
    if ($r['http'] !== 200) { fwrite(STDERR, "Không đăng nhập được $email (HTTP {$r['http']}): {$r['raw']}\n"); exit(2); }
}

// Slot của ngày mai, sân 1 — reset về trạng thái sạch.
$ids = $pdo->query("SELECT id FROM time_slots WHERE court_id = 1 AND slot_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
                    ORDER BY start_time")->fetchAll(PDO::FETCH_COLUMN);
if (count($ids) < $ROUNDS + 6) { fwrite(STDERR, "Thiếu slot — hãy chạy database/seed_slots.sql\n"); exit(2); }
$pdo->exec("UPDATE time_slots SET status='available', held_by=NULL, hold_expires_at=NULL
            WHERE id IN (" . implode(',', $ids) . ")");
$raceIds = array_slice($ids, 0, $ROUNDS);
$extra   = array_slice($ids, $ROUNDS);          // dùng cho các kịch bản phụ

// ---------------------------------------------------------------- 1. Cuộc đua 2 người / 1 slot
echo "\n== 1. Cuộc đua: 2 người cùng giữ 1 khung giờ ($ROUNDS lượt, request song song) ==\n";
$bad = []; $aWins = 0; $bWins = 0;
foreach ($raceIds as $sid) {
    [$ra, $rb] = parallel([holdReq($base, $sid, $A['jar']), holdReq($base, $sid, $B['jar'])]);
    $codes = [$ra['http'], $rb['http']]; sort($codes);
    $row = slotRow($pdo, $sid);
    $winner = $ra['http'] === 200 ? $A['id'] : ($rb['http'] === 200 ? $B['id'] : null);
    $okRound = $codes === [200, 409] && $row['status'] === 'holding' && (int)$row['held_by'] === $winner;
    if ($okRound) { $ra['http'] === 200 ? $aWins++ : $bWins++; } else { $bad[] = "slot $sid: HTTP " . json_encode([$ra['http'], $rb['http']]) . " DB " . json_encode($row); }
}
check("Mọi lượt: đúng 1 người 200, 1 người 409, DB held_by = người thắng", !$bad, implode(' | ', array_slice($bad, 0, 3)));
echo "      (người A thắng $aWins lượt, người B thắng $bWins lượt)\n";
$dup = $pdo->query("SELECT COUNT(*) FROM time_slots WHERE id IN (" . implode(',', $raceIds) . ") AND status='holding'")->fetchColumn();
check("Số slot đang holding = số lượt ($ROUNDS) — không slot nào bị giữ đúp / bỏ trống", (int)$dup === $ROUNDS, "holding=$dup");

echo "\n== 2. Người thua nhận lỗi rõ ràng & không bị khoá treo ==\n";
$sid = $raceIds[0]; $row = slotRow($pdo, $sid);
$loser = ((int)$row['held_by'] === $A['id']) ? $B : $A;
$r = post("$base/api/hold.php", ['slot_id' => $sid], $loser['jar']);
check("Người thua: HTTP 409 + status 'error' + message tiếng Việt", $r['http'] === 409 && ($r['json']['status'] ?? '') === 'error' && !empty($r['json']['message']), $r['raw']);
$r = post("$base/api/hold.php", ['slot_id' => $extra[0]], $loser['jar']);
check("Người thua vẫn giữ được khung giờ khác ngay sau đó (không bị khoá tài khoản)", $r['http'] === 200, $r['raw']);

// ---------------------------------------------------------------- 3. Hai slot khác nhau song song
echo "\n== 3. Hai người giữ 2 slot KHÁC nhau cùng lúc → cả hai thành công ==\n";
[$ra, $rb] = parallel([holdReq($base, $extra[1], $A['jar']), holdReq($base, $extra[2], $B['jar'])]);
check("Cả hai đều HTTP 200", $ra['http'] === 200 && $rb['http'] === 200, json_encode([$ra['http'], $rb['http']]));

// ---------------------------------------------------------------- 4. Định dạng response 200
echo "\n== 4. Định dạng response theo hợp đồng ==\n";
$r = post("$base/api/hold.php", ['slot_id' => $extra[3]], $A['jar']);
$d = $r['json']['data'] ?? [];
check("200 có data.slot_id / hold_expires_at / expires_in_seconds", $r['http'] === 200 && ($d['slot_id'] ?? 0) === $extra[3] && isset($d['hold_expires_at'], $d['expires_in_seconds']), $r['raw']);
check("expires_in_seconds ≈ 300 (299–300)", in_array($d['expires_in_seconds'] ?? -1, [299, 300], true), (string)($d['expires_in_seconds'] ?? 'null'));
check("hold_expires_at dạng ISO 8601 có +07:00", (bool)preg_match('/^\d{4}-\d\d-\d\dT\d\d:\d\d:\d\d\+07:00$/', $d['hold_expires_at'] ?? ''), (string)($d['hold_expires_at'] ?? ''));
$r2 = post("$base/api/hold.php", ['slot_id' => $extra[3]], $A['jar']);
check("Chính mình bấm giữ lại slot của mình → 200 (gia hạn)", $r2['http'] === 200, $r2['raw']);

// ---------------------------------------------------------------- 5. Hết hạn → giữ lại được + cleanup
echo "\n== 5. Hết hạn giữ chỗ & script dọn (Task 8) ==\n";
$exp = $raceIds[1]; $row = slotRow($pdo, $exp);
$holder = ((int)$row['held_by'] === $A['id']) ? $A : $B; $other = ($holder === $A) ? $B : $A;
$pdo->prepare("UPDATE time_slots SET hold_expires_at = NOW() - INTERVAL 1 MINUTE WHERE id = ?")->execute([$exp]);
$r = post("$base/api/hold.php", ['slot_id' => $exp], $other['jar']);
check("Slot đang holding nhưng đã hết hạn → người khác giữ được (200)", $r['http'] === 200, $r['raw']);
check("DB: held_by chuyển sang người mới", (int)slotRow($pdo, $exp)['held_by'] === $other['id']);

$expired = [$raceIds[2], $raceIds[3]];
$pdo->exec("UPDATE time_slots SET hold_expires_at = NOW() - INTERVAL 2 MINUTE WHERE id IN (" . implode(',', $expired) . ")");
$stillValid = $raceIds[4]; // đang holding, còn hạn
$out = shell_exec('php ' . escapeshellarg(__DIR__ . '/../api/cleanup-expired.php') . ' 2>&1');
check("cleanup-expired.php (CLI) chạy không lỗi", is_string($out) && str_contains($out, 'Đã dọn'), (string)$out);
foreach ($expired as $e) {
    $row = slotRow($pdo, $e);
    check("Slot $e hết hạn → available, held_by & hold_expires_at = NULL", $row['status'] === 'available' && $row['held_by'] === null && $row['hold_expires_at'] === null, json_encode($row));
}
check("Slot còn hạn ($stillValid) KHÔNG bị dọn nhầm", slotRow($pdo, $stillValid)['status'] === 'holding');
$http = json_decode((string)@file_get_contents("$base/api/cleanup-expired.php"), true);
check("cleanup-expired.php (HTTP) trả JSON success", ($http['status'] ?? '') === 'success', json_encode($http));

// ---------------------------------------------------------------- 6. Lỗi đầu vào / xác thực / booked
echo "\n== 6. Xác thực & lỗi đầu vào ==\n";
$r = post("$base/api/hold.php", ['slot_id' => $extra[4]], '');
check("Chưa đăng nhập → 401", $r['http'] === 401, $r['raw']);
check("Thiếu slot_id → 400", post("$base/api/hold.php", [], $A['jar'])['http'] === 400);
check("slot_id không phải số → 400", post("$base/api/hold.php", ['slot_id' => 'abc'], $A['jar'])['http'] === 400);
check("slot_id không tồn tại → 404", post("$base/api/hold.php", ['slot_id' => 999999999], $A['jar'])['http'] === 404);
$pdo->prepare("UPDATE time_slots SET status='booked', held_by=NULL, hold_expires_at=NULL WHERE id = ?")->execute([$extra[5]]);
check("Slot đã booked → 409", post("$base/api/hold.php", ['slot_id' => $extra[5]], $A['jar'])['http'] === 409);
$ch = curl_init("$base/api/hold.php"); curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true]); curl_exec($ch);
check("GET (sai phương thức) → 405", curl_getinfo($ch, CURLINFO_HTTP_CODE) === 405); curl_close($ch);
$past = $pdo->query("SELECT id FROM time_slots WHERE court_id = 1 AND slot_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) LIMIT 1")->fetchColumn();
if ($past) check("Slot đã qua giờ → 400", post("$base/api/hold.php", ['slot_id' => (int)$past], $A['jar'])['http'] === 400);

// ---------------------------------------------------------------- dọn dẹp
$pdo->exec("UPDATE time_slots SET status='available', held_by=NULL, hold_expires_at=NULL WHERE id IN (" . implode(',', $ids) . ")");
@unlink($A['jar']); @unlink($B['jar']);

echo "\n==== KẾT QUẢ: $pass đạt, $fail lỗi ====\n";
exit($fail ? 1 : 0);
