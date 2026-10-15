<?php
require_once __DIR__.'/../includes/booking-account-api.php';
try{$user=booking_account_user();$method=$_SERVER['REQUEST_METHOD'];if($method==='GET')booking_account_out(200,['user'=>$user,'csrf_token'=>$_SESSION['csrf_token']]);if($method!=='POST')booking_account_out(405,null,'Chỉ hỗ trợ GET/POST.');booking_account_csrf();$in=booking_account_input();
foreach(['name','email','phone']as $key)if(!isset($in[$key])||!is_string($in[$key]))booking_account_out(422,null,'Vui lòng nhập họ tên, email và điện thoại.');
$name=trim($in['name']);$email=strtolower(trim($in['email']));$phone=preg_replace('/\s+/','',$in['phone']);if(mb_strlen($name)<2||mb_strlen($name)>150||!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>150||!preg_match('/^(0|\+84)\d{9}$/',$phone))booking_account_out(422,null,'Họ tên, email hoặc số điện thoại không hợp lệ.');
$pdo->prepare('UPDATE users SET name=?,email=?,phone=? WHERE id=?')->execute([$name,$email,$phone,$user['id']]);$_SESSION['name']=$name;$_SESSION['email']=$email;$_SESSION['phone']=$phone;booking_account_out(200,['user'=>booking_account_user()],'Đã lưu hồ sơ vào MySQL.');
}catch(PDOException $e){if((int)($e->errorInfo[1]??0)===1062)booking_account_out(409,null,'Email đã được tài khoản khác sử dụng.');booking_account_error($e);}catch(Throwable $e){booking_account_error($e);}
