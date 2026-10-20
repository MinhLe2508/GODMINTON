<?php
require_once __DIR__.'/../../includes/auth-security.php';
try {
    $in=god_auth_input();$email=god_auth_email($in);
    $message='Nếu email có tài khoản phù hợp, bạn sẽ nhận được hướng dẫn. Vui lòng kiểm tra hộp thư đến và thư rác.';
    $ipAllowed=god_auth_limit($pdo,'mail-ip',god_auth_ip(),15,3600);
    $emailAllowed=god_auth_limit($pdo,'mail-email',$email,3,3600);
    if (!$ipAllowed || !$emailAllowed) god_auth_out(200,$message);
    $mail=god_auth_mailer();
    $pdo->beginTransaction();
    $s=$pdo->prepare('SELECT id,name,email,is_verified FROM users WHERE email=? FOR UPDATE');$s->execute([$email]);$user=$s->fetch();
    $purpose='verify';
    if ($user && ($purpose==='reset' || !$user['is_verified'])) {
        $token=god_auth_token($pdo,(int)$user['id'],$purpose);god_auth_send($mail,$user,$purpose,$token);
    }
    $pdo->commit();god_auth_out(200,$message);
} catch (Throwable $e) { god_auth_failure($e); }
