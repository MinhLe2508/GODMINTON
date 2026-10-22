<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/auth-session.php';
require_once __DIR__.'/auth-mailer.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
god_auth_start();
function god_auth_out(int $status,string $message='',array $data=[],string $code=''): never {
    http_response_code($status);
    echo json_encode(['status'=>$status<400?'success':'error','code'=>$code?:$status,'message'=>$message,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
}
function god_auth_input(): array {
    if (($_SERVER['REQUEST_METHOD']??'')!=='POST') { header('Allow: POST'); god_auth_out(405,'Chỉ hỗ trợ POST.'); }
    $csrf=$_SERVER['HTTP_X_GODMINTON_CSRF']??'';
    if (!hash_equals($_SESSION['csrf_token'],$csrf)) god_auth_out(403,'Phiên thao tác đã thay đổi. Tải lại trang để tiếp tục.');
    if ((int)($_SERVER['CONTENT_LENGTH']??0)>8192) god_auth_out(413,'Dữ liệu quá lớn.');
    $in=json_decode(file_get_contents('php://input'),true);
    if (!is_array($in)) god_auth_out(422,'JSON không hợp lệ.');
    return $in;
}
function god_auth_text(array $in,string $key): string {
    if (!isset($in[$key]) || !is_string($in[$key])) god_auth_out(422,'Thông tin không hợp lệ.');
    return $in[$key];
}
function god_auth_email(array $in): string {
    $email=strtolower(trim(god_auth_text($in,'email')));
    if (strlen($email)>150 || !filter_var($email,FILTER_VALIDATE_EMAIL)) god_auth_out(422,'Email không hợp lệ.');
    return $email;
}
function god_auth_password_valid(string $password): bool {
    return mb_strlen($password,'UTF-8')>=8 && !str_contains($password,"\0")
        && preg_match('/\p{Lu}/u',$password)===1
        && preg_match('/\p{Ll}/u',$password)===1
        && preg_match('/\p{Nd}/u',$password)===1
        && preg_match('/[\p{P}\p{S}]/u',$password)===1;
}
// Argon2id supports complete passwords without bcrypt's 72-byte truncation.
function god_auth_password_hash(string $password): string {
    return password_hash($password,PASSWORD_ARGON2ID);
}
// Atomic fixed-window counters shared between sessions, processes and servers.
function god_auth_limit(PDO $pdo,string $scope,string $key,int $max,int $seconds): bool {
    $hash=hash('sha256',$scope.'|'.$key); $cutoff=date('Y-m-d H:i:s',time()-$seconds);
    $s=$pdo->prepare('INSERT INTO auth_rate_limits (key_hash,attempts,window_started_at) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(window_started_at<=?,1,attempts+1),window_started_at=IF(window_started_at<=?,NOW(),window_started_at)');
    $s->execute([$hash,$cutoff,$cutoff]);
    $s=$pdo->prepare('SELECT attempts FROM auth_rate_limits WHERE key_hash=?');$s->execute([$hash]);
    return (int)$s->fetchColumn()<=$max;
}
function god_auth_ip(): string { return $_SERVER['REMOTE_ADDR']??'unknown'; }
function god_auth_token(PDO $pdo,int $id,string $purpose): string {
    $pdo->prepare('UPDATE auth_tokens SET used_at=NOW() WHERE user_id=? AND purpose=? AND used_at IS NULL')->execute([$id,$purpose]);
    $raw=bin2hex(random_bytes(32)); $expires=date('Y-m-d H:i:s',time()+($purpose==='email_change'?1800:(in_array($purpose,['reset','delete'],true)?900:3600)));
    $pdo->prepare('INSERT INTO auth_tokens (user_id,purpose,token_hash,expires_at) VALUES (?,?,?,?)')->execute([$id,$purpose,hash('sha256',$raw),$expires]);
    return $raw;
}
function god_auth_failure(Throwable $e): never {
    global $pdo;
    if ($pdo->inTransaction()) $pdo->rollBack();
    // Never log SMTP exception strings, credentials, raw tokens or request bodies.
    error_log('[GODMINTON auth] Operation failed: '.get_class($e));
    god_auth_out(503,'Chưa thể xử lý yêu cầu. Vui lòng thử lại sau hoặc liên hệ quản trị để kiểm tra SMTP và cơ sở dữ liệu.');
}
