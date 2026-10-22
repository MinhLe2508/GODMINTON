<?php
require_once __DIR__.'/../../includes/auth-security.php';
try {
    $in=god_auth_input(); $email=god_auth_email($in); $name=trim(god_auth_text($in,'name'));
    $phone=trim(god_auth_text($in,'phone')); $password=god_auth_text($in,'password');
    if (mb_strlen($name)<2 || mb_strlen($name)>150 || !preg_match('/^\+?[0-9 ()-]{9,20}$/',$phone)) god_auth_out(422,'Kiểm tra họ tên và số điện thoại.');
    if (!god_auth_password_valid($password)) god_auth_out(422,'Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường, chữ số và ký tự đặc biệt.');
    if (($in['agree_terms']??false)!==true) god_auth_out(422,'Bạn cần đồng ý điều khoản dịch vụ.');
    if (!god_auth_limit($pdo,'register-ip',god_auth_ip(),10,3600) || !god_auth_limit($pdo,'register-email',$email,3,3600)) god_auth_out(429,'Bạn đã gửi nhiều yêu cầu. Vui lòng thử lại sau.');
    $mail=god_auth_mailer();
    $pdo->beginTransaction();
    $s=$pdo->prepare('SELECT id FROM users WHERE email=? FOR UPDATE');$s->execute([$email]);
    if (!$s->fetch()) {
        $role='customer';$ownerIntent=($in['role']??'')==='owner'?1:0;
        $pdo->prepare('INSERT INTO users (name,email,phone,password_hash,role,is_verified,owner_intent) VALUES (?,?,?,?,?,0,?)')->execute([$name,$email,$phone,god_auth_password_hash($password),$role,$ownerIntent]);
        $id=(int)$pdo->lastInsertId();$token=god_auth_token($pdo,$id,'verify');
        god_auth_send($mail,['name'=>$name,'email'=>$email],'verify',$token);
    }
    $pdo->commit();
    god_auth_out(200,'Nếu email đủ điều kiện đăng ký, bạn sẽ nhận được liên kết xác minh. Nếu đã có tài khoản, hãy đăng nhập hoặc dùng chức năng quên mật khẩu.');
} catch (Throwable $e) { god_auth_failure($e); }
