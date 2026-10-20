<?php
require_once __DIR__.'/../includes/auth-session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax']);
    god_auth_start();
    god_auth_user($pdo);
}
function contact_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}
if (empty($_SESSION['contact_csrf'])) $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'GET') contact_out(200,['status'=>'success','data'=>['csrf_token'=>$_SESSION['contact_csrf']]]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    contact_out(405,['status'=>'error','message'=>'Chỉ hỗ trợ GET/POST.']);
}
$token = $_SERVER['HTTP_X_GODMINTON_CSRF'] ?? '';
if (!is_string($token) || !hash_equals($_SESSION['contact_csrf'],$token)) contact_out(403,['status'=>'error','message'=>'Phiên gửi không hợp lệ. Tải lại trang rồi thử lại.']);
$raw = file_get_contents('php://input', false, null, 0, 20001);
if (strlen($raw)>20000) contact_out(413,['status'=>'error','message'=>'Nội dung gửi quá dài.']);
$input = json_decode($raw,true);
if (!is_array($input)) contact_out(400,['status'=>'error','message'=>'Dữ liệu gửi không hợp lệ.']);
$limits = ['name'=>120,'phone'=>20,'email'=>150,'subject'=>200,'message'=>5000];
$values = [];
foreach ($limits as $key=>$limit) {
    $value = $input[$key] ?? ($key==='subject' ? 'Liên hệ từ website' : '');
    if (!is_string($value) || trim($value)==='' || mb_strlen(trim($value))>$limit) contact_out(422,['status'=>'error','message'=>'Vui lòng điền đầy đủ thông tin và không vượt quá độ dài cho phép.']);
    $values[$key] = trim($value);
}
if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/^[+0-9().\s-]{8,30}$/',$values['phone'])) contact_out(422,['status'=>'error','message'=>'Email hoặc số điện thoại không hợp lệ.']);
if (time()-($_SESSION['contact_sent_at'] ?? 0)<15) contact_out(429,['status'=>'error','message'=>'Vui lòng chờ 15 giây trước khi gửi tiếp.']);
try {
    $stmt = $pdo->prepare('INSERT INTO contacts(user_id,name,phone,email,subject,message) VALUES(?,?,?,?,?,?)');
    $stmt->execute([$_SESSION['user_id'] ?? null,$values['name'],$values['phone'],$values['email'],$values['subject'],$values['message']]);
    $_SESSION['contact_sent_at'] = time();
    contact_out(201,['status'=>'success','data'=>['id'=>(int)$pdo->lastInsertId()],'message'=>'Đã tiếp nhận liên hệ của bạn.']);
} catch (Throwable $error) {
    error_log('[contact] '.$error->getMessage());
    contact_out(500,['status'=>'error','message'=>'Chưa lưu được liên hệ. Vui lòng thử lại sau.']);
}
