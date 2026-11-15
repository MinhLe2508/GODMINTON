<?php
require_once __DIR__.'/auth-session.php';
require_once __DIR__.'/../config/db.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
god_auth_start();
god_auth_user($pdo);
class BookingError extends RuntimeException{public function __construct(public int $httpCode,string $message){parent::__construct($message);}}
function booking_account_out(int $code,$data=null,string $message=''):never{http_response_code($code);echo json_encode(['status'=>$code<400?'success':'error','code'=>$code,'message'=>$message,'data'=>$data],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function booking_account_input():array{$raw=file_get_contents('php://input');if($raw==='')return $_POST;$in=json_decode($raw,true);if(!is_array($in))booking_account_out(422,null,'JSON không hợp lệ.');return $in;}
function booking_account_user(bool $optional=false):?array{global $pdo;$id=(int)($_SESSION['user_id']??0);if(!$id){if($optional)return null;booking_account_out(401,null,'Vui lòng đăng nhập.');}$s=$pdo->prepare('SELECT id,name,email,phone,role,avatar_url,created_at FROM users WHERE id=?');$s->execute([$id]);$u=$s->fetch();if(!$u){if($optional)return null;booking_account_out(401,null,'Tài khoản không còn tồn tại.');}$u['id']=(int)$u['id'];return $u;}
function booking_account_csrf():void{$token=$_SERVER['HTTP_X_GODMINTON_CSRF']??'';if(!is_string($token)||!hash_equals($_SESSION['csrf_token'],$token))booking_account_out(403,null,'Phiên thao tác không hợp lệ. Tải lại trang rồi thử lại.');}
function booking_account_id($value):int{if(!is_scalar($value)||!preg_match('/^[1-9][0-9]*$/',(string)$value)||filter_var($value,FILTER_VALIDATE_INT)===false)throw new BookingError(422,'ID không hợp lệ.');return (int)$value;}
function booking_account_transaction(callable $callback){global $pdo;for($n=0;$n<3;$n++){try{$pdo->beginTransaction();$result=$callback();$pdo->commit();return $result;}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if($e instanceof PDOException&&in_array((int)($e->errorInfo[1]??0),[1205,1213],true)&&$n<2){usleep(30000);continue;}throw $e;}}}
function booking_account_error(Throwable $e):never{if($e instanceof BookingError)booking_account_out($e->httpCode,null,$e->getMessage());error_log('[GODMINTON booking-account] '.$e->getMessage());booking_account_out(500,null,'Lỗi hệ thống. Vui lòng kiểm tra cấu hình cơ sở dữ liệu và xem log PHP.');}
