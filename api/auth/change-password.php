<?php
require_once __DIR__.'/../../includes/booking-account-api.php';
try {
 $user=booking_account_user();if($_SERVER['REQUEST_METHOD']!=='POST')booking_account_out(405,null,'Chỉ hỗ trợ POST.');booking_account_csrf();$in=booking_account_input();$old=$in['old_password']??null;$next=$in['new_password']??null;
 if(!is_string($old)||!is_string($next)||strlen($next)<6||strlen($next)>72||$old===$next)booking_account_out(422,null,'Mật khẩu mới cần từ 6 đến 72 byte và khác mật khẩu cũ.');
 $s=$pdo->prepare('SELECT password_hash FROM users WHERE id=?');$s->execute([$user['id']]);if(!password_verify($old,$s->fetchColumn()))booking_account_out(403,null,'Mật khẩu cũ không đúng.');
 $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($next,PASSWORD_BCRYPT),$user['id']]);session_regenerate_id(true);booking_account_out(200,null,'Đổi mật khẩu thành công.');
}catch(Throwable $e){booking_account_error($e);}
