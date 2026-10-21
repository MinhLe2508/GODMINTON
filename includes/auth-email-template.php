<?php
/** HTML + text alternatives for security emails. No remote images or tracking. */
function god_auth_email_content(array $user,string $purpose,string $link): array {
    $types=[
        'verify'=>['title'=>'Xác minh email GODMINTON','heading'=>'Chào mừng bạn đến GODMINTON',
            'intro'=>'Cảm ơn bạn đã đăng ký. Vui lòng xác minh địa chỉ email để kích hoạt tài khoản và bắt đầu đặt sân.',
            'action'=>'Xác minh email','minutes'=>60],
        'reset'=>['title'=>'Đặt lại mật khẩu GODMINTON','heading'=>'Đặt lại mật khẩu của bạn',
            'intro'=>'Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản GODMINTON của bạn. Bấm nút bên dưới để tạo mật khẩu mới.',
            'action'=>'Đặt mật khẩu mới','minutes'=>15],
        'delete'=>['title'=>'Xác nhận xóa tài khoản GODMINTON','heading'=>'Xác nhận yêu cầu xóa tài khoản',
            'intro'=>'Bạn đã yêu cầu xóa tài khoản GODMINTON. Sau khi xác nhận, tài khoản sẽ bị vô hiệu và hồ sơ được ẩn danh. Lịch sử giao dịch được giữ để đối soát. Thao tác không thể hoàn tác.',
            'action'=>'Xác nhận xóa tài khoản','minutes'=>15],
    ];
    if(!isset($types[$purpose]))throw new InvalidArgumentException('Unsupported email purpose');
    $type=$types[$purpose];
    $escape=fn(string $value):string=>htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
    $name=$escape((string)$user['name']);$email=$escape((string)$user['email']);$safeLink=$escape($link);
    $heading=$escape($type['heading']);$intro=$escape($type['intro']);$action=$escape($type['action']);
    $minutes=$type['minutes'];$color=$purpose==='delete'?'#b83240':'#0d9488';$year=date('Y');
    $logo=is_file(__DIR__.'/../assets/img/logo.jpg')
        ?'<img src="cid:godminton-logo" width="180" height="121" alt="GODMINTON" style="display:block;width:180px;max-width:100%;height:auto;border:0;margin:0 auto;">'
        :'<span style="font-size:26px;font-weight:bold;color:#0d9488;">GODMINTON</span>';
    $body=<<<HTML
<!doctype html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{$escape($type['title'])}</title></head>
<body style="margin:0;padding:0;background-color:#f0f7f6;font-family:Arial,Helvetica,sans-serif;color:#193d42;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;">$heading — liên kết có hiệu lực $minutes phút.</div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f0f7f6"><tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="width:100%;max-width:600px;border:1px solid #dbe9e5;border-radius:18px;">
<tr><td align="center" style="padding:20px 24px 12px;border-bottom:1px solid #e7efed;">$logo<p style="font-size:12px;letter-spacing:1px;color:#53716f;margin:8px 0 0;">NỀN TẢNG ĐẶT SÂN CẦU LÔNG</p></td></tr>
<tr><td style="padding:28px 24px 24px;">
<p style="font-size:11px;letter-spacing:1px;color:#087b72;font-weight:bold;margin:0 0 12px;">BẢO MẬT TÀI KHOẢN</p>
<h1 style="font-size:24px;line-height:1.35;margin:0 0 20px;color:#173e42;">$heading</h1>
<p style="font-size:15px;line-height:1.7;margin:0 0 12px;">Xin chào <strong>$name</strong>,</p>
<p style="font-size:15px;line-height:1.7;color:#496369;margin:0 0 20px;">$intro</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f2f8f6" style="border-radius:10px;"><tr><td style="padding:14px 16px;">
<p style="margin:0 0 5px;font-size:12px;color:#58726e;">Tài khoản của bạn</p><p style="margin:0;font-size:15px;font-weight:bold;word-break:break-all;color:#173e42;">$email</p>
</td></tr></table>
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px auto 16px;"><tr><td align="center" bgcolor="$color" style="border-radius:9px;">
<a href="$safeLink" style="display:inline-block;padding:15px 24px;font-size:15px;line-height:1.4;font-weight:bold;color:#ffffff;text-decoration:none;border:1px solid $color;border-radius:9px;">$action</a>
</td></tr></table>
<p style="font-size:13px;line-height:1.6;text-align:center;margin:0 0 24px;color:#58716f;">Liên kết có hiệu lực <strong>$minutes phút</strong> và chỉ sử dụng một lần.</p>
<p style="font-size:13px;line-height:1.7;margin:0;color:#58716f;">Nếu không phải bạn yêu cầu, hãy bỏ qua email này. Không chia sẻ liên kết hoặc mật khẩu với người khác.</p>
<p style="font-size:13px;line-height:1.7;margin:8px 0 0;color:#58716f;">GODMINTON không yêu cầu bạn gửi mật khẩu qua email.</p>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:24px;border-top:1px solid #e7efed;"><tr><td style="padding-top:16px;">
<p style="font-size:12px;line-height:1.6;color:#6b817e;margin:0 0 8px;">Nếu nút không hoạt động, sao chép đầy đủ liên kết sau vào trình duyệt:</p>
<a href="$safeLink" style="font-size:12px;line-height:1.6;color:#087b72;word-break:break-all;overflow-wrap:anywhere;">$safeLink</a>
</td></tr></table>
</td></tr><tr><td align="center" bgcolor="#f6faf9" style="padding:18px 24px;border-top:1px solid #e7efed;border-radius:0 0 18px 18px;">
<p style="margin:0;color:#6b817e;font-size:12px;line-height:1.7;">© $year GODMINTON · Email bảo mật tài khoản<br>Bạn nhận email này vì có yêu cầu liên quan đến tài khoản trên GODMINTON.</p>
</td></tr></table></td></tr></table></body></html>
HTML;
    $plain=$type['title']."\n\nXin chào ".$user['name'].",\n\n".$type['intro']."\n\nTài khoản: ".$user['email'].
        "\n\n".$type['action'].":\n".$link."\n\nLiên kết có hiệu lực $minutes phút và chỉ sử dụng một lần.\n\n".
        "Nếu không phải bạn yêu cầu, hãy bỏ qua email này. Không chia sẻ liên kết hoặc mật khẩu với người khác. GODMINTON không yêu cầu bạn gửi mật khẩu qua email.\n\nGODMINTON — Nền tảng đặt sân cầu lông.";
    return ['subject'=>$type['title'],'html'=>$body,'text'=>$plain];
}
