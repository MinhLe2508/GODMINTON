<?php
require_once __DIR__.'/../../includes/auth-security.php';
require_once __DIR__.'/../../includes/account-deletion.php';
try {
    $user=god_auth_user($pdo);if(!$user)god_auth_out(401,'Vui lòng đăng nhập.');
    $in=god_auth_input();$password=god_auth_text($in,'password');
    if(($in['confirm_delete']??false)!==true)god_auth_out(422,'Bạn cần xác nhận yêu cầu xóa tài khoản.');
    if(!god_auth_limit($pdo,'delete-user',(string)$user['id'],3,3600))god_auth_out(429,'Quá nhiều yêu cầu. Vui lòng thử lại sau một giờ.');
    $pdo->beginTransaction();
    $s=$pdo->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');$s->execute([$user['id']]);$stored=$s->fetch();
    if(!$stored || $stored['deleted_at']!==null || (int)$stored['session_version']!==(int)$_SESSION['auth_version'] || !password_verify($password,$stored['password_hash'])){
        $pdo->rollBack();god_auth_out(403,'Mật khẩu hiện tại hoặc phiên đăng nhập không hợp lệ.');
    }
    $block=god_auth_deletion_blocker($pdo,$stored);
    if($block){$pdo->rollBack();god_auth_out(409,$block);}
    $mail=god_auth_mailer();$token=god_auth_token($pdo,(int)$user['id'],'delete');
    god_auth_send($mail,$stored,'delete',$token);$pdo->commit();
    god_auth_out(200,'Đã gửi liên kết xác nhận.',['message'=>'Đã gửi liên kết xác nhận xóa đến email của bạn. Liên kết có hiệu lực 5 phút. Tài khoản vẫn hoạt động cho đến khi bạn xác nhận.']);
}catch(Throwable $e){god_auth_failure($e);}
