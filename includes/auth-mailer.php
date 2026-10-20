<?php
/** Only SMTP delivery through PHPMailer; no public token logs or fake delivery. */
function god_auth_mailer(): \PHPMailer\PHPMailer\PHPMailer {
    $autoload=__DIR__.'/../vendor/autoload.php';
    if (!is_file($autoload)) throw new RuntimeException('Mailer unavailable');
    require_once $autoload;
    $host=getenv('SMTP_HOST')?:'';
    $security=getenv('SMTP_ENCRYPTION')?:'tls';
    $localTest=getenv('APP_ENV')==='test' && in_array($host,['127.0.0.1','localhost'],true);
    $base=getenv('APP_URL')?:'';
    $url=parse_url($base);
    if (!$host || !$base || !filter_var(getenv('MAIL_FROM_ADDRESS'),FILTER_VALIDATE_EMAIL)
        || !$url || empty($url['host']) || !empty($url['user']) || !empty($url['pass'])
        || (!in_array($url['scheme']??'',['https'],true) && !(($url['scheme']??'')==='http' && in_array($url['host'],['localhost','127.0.0.1'],true)))
        || (!$localTest && (!getenv('SMTP_USERNAME') || !getenv('SMTP_PASSWORD') || !in_array($security,['tls','ssl'],true)))) {
        throw new RuntimeException('SMTP configuration incomplete');
    }
    $mail=new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP(); $mail->Host=$host;
    $mail->Port=(int)(getenv('SMTP_PORT')?:587);
    $mail->SMTPAuth=!$localTest || (bool)getenv('SMTP_USERNAME');
    $mail->Username=getenv('SMTP_USERNAME')?:''; $mail->Password=getenv('SMTP_PASSWORD')?:'';
    $mail->SMTPSecure=$localTest && $security==='none'?'':$security;
    $mail->SMTPAutoTLS=!($localTest && $security==='none');
    $mail->Timeout=12; $mail->Timelimit=15; $mail->SMTPDebug=0;
    $mail->CharSet='UTF-8';
    $mail->setFrom(getenv('MAIL_FROM_ADDRESS'),getenv('MAIL_FROM_NAME')?:'GODMINTON');
    return $mail;
}
function god_auth_send(\PHPMailer\PHPMailer\PHPMailer $mail,array $user,string $purpose,string $token): void {
    $link=rtrim(getenv('APP_URL'),'/').'/auth-action.php?mode='.$purpose.'#token='.$token;
    $reset=$purpose==='reset'; $delete=$purpose==='delete'; $minutes=($reset||$delete)?15:60;
    $title=$delete?'Xác nhận xóa tài khoản GODMINTON':($reset?'Đặt lại mật khẩu GODMINTON':'Xác minh email GODMINTON');
    $plain="Xin chào ".$user['name'].",\n\nTên đăng nhập: ".$user['email']."\n".
        ($delete?'Bạn đã yêu cầu xóa tài khoản. Sau khi xác nhận, tài khoản sẽ bị vô hiệu và thông tin hồ sơ được ẩn danh; lịch sử giao dịch được giữ để đối soát. Thao tác không thể hoàn tác.':($reset?'Bạn đã yêu cầu đặt lại mật khẩu.':'Vui lòng xác minh email để kích hoạt tài khoản.')).
        "\nMở liên kết sau, có hiệu lực $minutes phút và chỉ sử dụng một lần:\n$link\n\n".
        "Không chia sẻ liên kết này. Nếu không phải bạn yêu cầu, hãy bỏ qua email. GODMINTON không gửi mật khẩu qua email.";
    $mail->addAddress($user['email']); $mail->Subject=$title;
    $mail->isHTML(true);
    $mail->Body='<div style="font-family:Arial,sans-serif;line-height:1.7;max-width:560px"><h2>'.htmlspecialchars($title).'</h2><p>'.
        nl2br(htmlspecialchars($plain,ENT_QUOTES,'UTF-8')).'</p><p><a href="'.htmlspecialchars($link,ENT_QUOTES,'UTF-8').'">'.
        ($delete?'Xác nhận xóa tài khoản':($reset?'Đặt mật khẩu mới':'Xác minh email')).'</a></p></div>';
    $mail->AltBody=$plain; $mail->send();
}
