<?php
/** HTML + text alternatives for security emails. No remote images or tracking. */
function god_auth_email_content(array $user,string $purpose,string $link): array {
    $types=[
        'verify'=>['title'=>'Xác minh email GODMINTON','heading'=>'Chào mừng bạn đến GODMINTON',
            'intro'=>'Cảm ơn bạn đã đăng ký. Vui lòng xác minh địa chỉ email để kích hoạt tài khoản và bắt đầu đặt sân.',
            'action'=>'Xác minh email','minutes'=>5],
        'reset'=>['title'=>'Đặt lại mật khẩu GODMINTON','heading'=>'Đặt lại mật khẩu của bạn',
            'intro'=>'Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản GODMINTON của bạn. Bấm nút bên dưới để tạo mật khẩu mới.',
            'action'=>'Đặt mật khẩu mới','minutes'=>15],
        'delete'=>['title'=>'Xác nhận xóa tài khoản GODMINTON','heading'=>'Xác nhận yêu cầu xóa tài khoản',
            'intro'=>'Bạn đã yêu cầu xóa tài khoản GODMINTON. Sau khi xác nhận, tài khoản sẽ bị vô hiệu và hồ sơ được ẩn danh. Lịch sử giao dịch được giữ để đối soát. Thao tác không thể hoàn tác.',
            'action'=>'Xác nhận xóa tài khoản','minutes'=>5],
        'email_change'=>['title'=>'Xác nhận email mới GODMINTON','heading'=>'Xác minh địa chỉ email mới',
            'intro'=>'Có yêu cầu thay đổi email đăng nhập cho tài khoản của bạn. Chỉ bấm xác nhận nếu đây là thay đổi bạn mong muốn. Sau xác nhận, các phiên đăng nhập cũ sẽ kết thúc.',
            'action'=>'Xác nhận email mới','minutes'=>30],
    ];
    if(!isset($types[$purpose]))throw new InvalidArgumentException('Unsupported email purpose');
    $type=$types[$purpose];
    $escape=fn(string $value):string=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $name=$escape((string)$user['name']);$email=$escape((string)$user['email']);$safeLink=$escape($link);
    $heading=$escape($type['heading']);$intro=$escape($type['intro']);$action=$escape($type['action']);
    $minutes=$type['minutes'];$color=$purpose==='delete'?'#b83240':'#0d9488';$year=date('Y');
    $buttonGradient=$purpose==='delete'?'linear-gradient(120deg,#be123c 0%,#c2410c 100%)':'linear-gradient(120deg,#0f766e 0%,#0891b2 100%)';
    $logo=is_file(__DIR__.'/../assets/img/logo.jpg')
        ?'<img src="cid:godminton-logo" width="180" height="121" alt="GODMINTON" style="display:block;width:180px;max-width:100%;height:auto;border:0;margin:0 auto;">'
        :'<span style="font-size:26px;font-weight:bold;color:#0d9488;">GODMINTON</span>';
    $body=<<<HTML
<!doctype html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{$escape($type['title'])}</title></head>
<body style="margin:0;padding:0;background-color:#e5f5f1;background-image:linear-gradient(140deg,#d9f7ed 0%,#edf5ff 100%);font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;color:#193d42;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">$heading — liên kết có hiệu lực $minutes phút.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#e5f5f1" style="background-image:linear-gradient(140deg,#d9f7ed 0%,#edf5ff 100%);font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;"><tr><td align="center" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:600px;border:1px solid #c8e7e0;border-radius:20px;box-shadow:0 16px 44px #163e3e12;font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;">
<tr><td align="center" bgcolor="#0f766e" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;padding:26px 24px 24px;background-image:linear-gradient(125deg,#115e59 0%,#0d9488 60%,#0891b2 100%);border-radius:20px 20px 0 0;">
<table role="presentation" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;border-radius:14px;"><tr><td align="center" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;padding:8px 16px;border-radius:14px;">$logo</td></tr></table>
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:11px;line-height:1.7;letter-spacing:.8px;font-weight:bold;color:#e3fffa;margin:18px 0 0;">NỀN TẢNG ĐẶT SÂN CẦU LÔNG</p></td></tr>
<tr><td height="5" bgcolor="#5eead4" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;height:5px;font-size:0;line-height:0;background-image:linear-gradient(90deg,#5eead4 0%,#67e8f9 100%);">&nbsp;</td></tr>
<tr><td style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;padding:28px 24px 24px;">
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;display:inline-block;padding:6px 10px;border-radius:6px;background-color:#e6f7f2;font-size:11px;letter-spacing:.5px;color:#0f766e;font-weight:bold;margin:0 0 14px;">BẢO MẬT TÀI KHOẢN</p>
<h1 style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:24px;letter-spacing:-.3px;line-height:1.4;font-weight:700;margin:0 0 22px;color:#115e59;">$heading</h1>
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:15px;line-height:1.75;margin:0 0 12px;">Xin chào <strong>$name</strong>,</p>
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:15px;line-height:1.75;color:#496369;margin:0 0 20px;">$intro</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#eef9f6" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;border:1px solid #cdece3;border-left:4px solid #14b8a6;border-radius:10px;background-image:linear-gradient(110deg,#e8faf3 0%,#edf7ff 100%);"><tr><td style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;padding:14px 16px;">
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;margin:0 0 5px;font-size:12px;color:#58726e;">Tài khoản của bạn</p><p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;margin:0;font-size:15px;font-weight:bold;word-break:break-all;color:#173e42;">$email</p>
</td></tr></table>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;margin:24px auto 16px;"><tr><td align="center" bgcolor="$color" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;border-radius:10px;background-image:$buttonGradient;">
<a href="$safeLink" style="display:inline-block;padding:15px 24px;font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:15px;line-height:1.4;letter-spacing:0;font-weight:600;color:#ffffff;text-decoration:none;border:1px solid $color;border-radius:10px;">$action</a>
</td></tr></table>
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:13px;line-height:1.6;text-align:center;margin:0 0 24px;color:#58716f;">Liên kết có hiệu lực <strong>$minutes phút</strong> và chỉ sử dụng một lần.</p>
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:13px;line-height:1.7;margin:0;color:#58716f;">Nếu không phải bạn yêu cầu, hãy bỏ qua email này. Không chia sẻ liên kết hoặc mật khẩu với người khác.</p>
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:13px;line-height:1.7;margin:8px 0 0;color:#58716f;">GODMINTON không yêu cầu bạn gửi mật khẩu qua email.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;margin-top:24px;border-top:1px solid #e7efed;"><tr><td style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;padding-top:16px;">
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:12px;line-height:1.6;color:#6b817e;margin:0 0 8px;">Nếu nút không hoạt động, sao chép đầy đủ liên kết sau vào trình duyệt:</p>
<a href="$safeLink" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;font-size:12px;line-height:1.6;color:#087b72;word-break:break-all;overflow-wrap:anywhere;">$safeLink</a>
</td></tr></table>
</td></tr><tr><td align="center" bgcolor="#edf8f5" style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;padding:18px 24px;border-top:1px solid #d5ebe5;border-radius:0 0 20px 20px;background-image:linear-gradient(110deg,#edf9f4 0%,#eef6ff 100%);">
<p style="font-family:'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif;margin:0;color:#6b817e;font-size:12px;line-height:1.7;">© $year GODMINTON · Email bảo mật tài khoản<br>Bạn nhận email này vì có yêu cầu liên quan đến tài khoản trên GODMINTON.</p>
</td></tr></table></td></tr></table></body></html>
HTML;
    $plain=$type['title']."\n\nXin chào ".$user['name'].",\n\n".$type['intro']."\n\nTài khoản: ".$user['email'].
        "\n\n".$type['action'].":\n".$link."\n\nLiên kết có hiệu lực $minutes phút và chỉ sử dụng một lần.\n\n".
        "Nếu không phải bạn yêu cầu, hãy bỏ qua email này. Không chia sẻ liên kết hoặc mật khẩu với người khác. GODMINTON không yêu cầu bạn gửi mật khẩu qua email.\n\nGODMINTON — Nền tảng đặt sân cầu lông.";
    return ['subject'=>$type['title'],'html'=>$body,'text'=>$plain];
}
