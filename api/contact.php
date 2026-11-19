<?php

require_once __DIR__ . '/../includes/auth-session.php';
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    god_auth_start();
    god_auth_user($pdo);
}

function contact_out(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['contact_csrf']))
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'GET')
    contact_out(200, ['status' => 'success', 'data' => ['csrf_token' => $_SESSION['contact_csrf']]]);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: GET, POST');
    contact_out(405, ['status' => 'error', 'message' => 'Chỉ hỗ trợ GET/POST.']);
}

$token = $_SERVER['HTTP_X_GODMINTON_CSRF'] ?? '';
if (!is_string($token) || !hash_equals($_SESSION['contact_csrf'], $token))
    contact_out(403, ['status' => 'error', 'message' => 'Phiên gửi không hợp lệ. Tải lại trang rồi thử lại.']);
$raw = file_get_contents('php://input', false, null, 0, 20001);
if (strlen($raw) > 20000)
    contact_out(413, ['status' => 'error', 'message' => 'Nội dung gửi quá dài.']);
$input = json_decode($raw, true);
if (!is_array($input))
    contact_out(400, ['status' => 'error', 'message' => 'Dữ liệu gửi không hợp lệ.']);
$limits = ['name' => 120, 'phone' => 20, 'email' => 150, 'subject' => 200, 'message' => 5000];
$values = [];
foreach ($limits as $key => $limit) {
    $value = $input[$key] ?? ($key === 'subject' ? 'Liên hệ từ website' : '');
    if (!is_string($value) || trim($value) === '' || mb_strlen(trim($value)) > $limit)
        contact_out(422, ['status' => 'error', 'message' => 'Vui lòng điền đầy đủ thông tin và không vượt quá độ dài cho phép.']);
    $values[$key] = trim($value);
}

if (!filter_var($values['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/^[+0-9().\s-]{8,30}$/', $values['phone']))
    contact_out(422, ['status' => 'error', 'message' => 'Email hoặc số điện thoại không hợp lệ.']);
if (time() - ($_SESSION['contact_sent_at'] ?? 0) < 15)
    contact_out(429, ['status' => 'error', 'message' => 'Vui lòng chờ 15 giây trước khi gửi tiếp.']);
try {
    $stmt = $pdo->prepare('INSERT INTO contacts(user_id,name,phone,email,subject,message) VALUES(?,?,?,?,?,?)');
    $stmt->execute([$_SESSION['user_id'] ?? null, $values['name'], $values['phone'], $values['email'], $values['subject'], $values['message']]);
    $contactId = (int) $pdo->lastInsertId();
    $_SESSION['contact_sent_at'] = time();
    try {
        require_once __DIR__ . '/../includes/auth-mailer.php';
        $mail = god_auth_mailer();
        $adminEmail = getenv('CONTACT_ADMIN_EMAIL') ?: 'admgodminton@gmail.com';
        $subject = preg_replace('/[\r\n]+/u', ' ', $values['subject']);
        $senderName = preg_replace('/[\r\n]+/u', ' ', $values['name']);
        $escape = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $logoPath = __DIR__ . '/../assets/img/logo.jpg';
        $logoMarkup = '';
        if (is_file($logoPath)) {
            $mail->addEmbeddedImage($logoPath, 'godminton-contact-logo', 'godminton-logo.jpg', 'base64', 'image/jpeg');
            $logoMarkup = '<img src="cid:godminton-contact-logo" width="150" alt="GODMINTON" style="display:block;width:150px;max-width:100%;height:auto;margin:0 auto;border:0">';
        } else {
            $logoMarkup = '<span style="font-size:25px;font-weight:800;letter-spacing:1px;color:#0d766e">GODMINTON</span>';
        }

        $mail->addAddress($adminEmail);
        $mail->addReplyTo($values['email'], $senderName);
        $mail->Subject = '[GODMINTON] Liên hệ mới: ' . $subject;
        $mail->isHTML(true);
        $font = "'Plus Jakarta Sans','Segoe UI',Roboto,Arial,sans-serif";
        $mail->Body = '<div style="margin:0;padding:28px 12px;background:#eef8f6;font-family:' . $font . ';color:#173b3b"><div style="max-width:620px;margin:auto;background:#fff;border:1px solid #d6ebe6;border-radius:16px;overflow:hidden"><div style="padding:24px 26px 20px;text-align:center;background:linear-gradient(120deg,#115e59,#0d9488);color:#fff"><div style="display:inline-block;min-width:190px;padding:8px 14px;background:#fff;border-radius:14px">' . $logoMarkup . '</div><div style="margin-top:15px;font-size:11px;font-weight:700;letter-spacing:1.6px;color:#d5fff7">GODMINTON · HỖ TRỢ KHÁCH HÀNG</div><h1 style="font-family:' . $font . ';font-size:23px;line-height:1.35;margin:7px 0 0;font-weight:800">Tin nhắn liên hệ mới</h1></div><div style="padding:24px 26px"><p style="margin:0 0 18px;color:#64748b;font-size:14px">Mã liên hệ #' . $contactId . '</p><table role="presentation" style="width:100%;border-collapse:collapse;font-family:' . $font . ';font-size:14px">' .
            '<tr><td style="padding:10px 0;border-bottom:1px solid #e5eeec;color:#64748b;width:145px">Họ tên</td><td style="padding:10px 0;border-bottom:1px solid #e5eeec;font-weight:bold">' . $escape($values['name']) . '</td></tr>' .
            '<tr><td style="padding:10px 0;border-bottom:1px solid #e5eeec;color:#64748b">Số điện thoại</td><td style="padding:10px 0;border-bottom:1px solid #e5eeec"><a href="tel:' . $escape($values['phone']) . '" style="color:#0d9488">' . $escape($values['phone']) . '</a></td></tr>' .
            '<tr><td style="padding:10px 0;border-bottom:1px solid #e5eeec;color:#64748b">Email phản hồi</td><td style="padding:10px 0;border-bottom:1px solid #e5eeec"><a href="mailto:' . $escape($values['email']) . '" style="color:#0d9488">' . $escape($values['email']) . '</a></td></tr>' .
            '<tr><td style="padding:10px 0;border-bottom:1px solid #e5eeec;color:#64748b">Chủ đề</td><td style="padding:10px 0;border-bottom:1px solid #e5eeec">' . $escape($subject) . '</td></tr></table>' .
            '<h2 style="font-family:' . $font . ';font-size:15px;margin:22px 0 8px">Nội dung</h2><div style="padding:16px;background:#f2faf8;border-radius:10px;line-height:1.7;white-space:pre-wrap;font-family:' . $font . ';font-size:14px">' . $escape($values['message']) . '</div></div><div style="padding:15px 26px;text-align:center;background:#f7fbfa;color:#71827f;font-family:' . $font . ';font-size:12px">Gửi từ biểu mẫu liên hệ trên website GODMINTON.</div></div></div>';
        $mail->AltBody = "Tin nhắn liên hệ mới #{$contactId}\n\nHọ tên: {$values['name']}\nSố điện thoại: {$values['phone']}\nEmail: {$values['email']}\nChủ đề: {$subject}\n\nNội dung:\n{$values['message']}";
        $mail->send();
    } catch (Throwable $mailError) {
        error_log('[contact] Mail delivery failed: ' . get_class($mailError));
        contact_out(503, ['status' => 'error', 'data' => ['id' => $contactId], 'message' => 'Tin nhắn đã được lưu nhưng chưa gửi được email cho bộ phận hỗ trợ. Mã liên hệ #' . $contactId . '. Vui lòng thử lại sau.']);
    }

    contact_out(201, ['status' => 'success', 'data' => ['id' => $contactId], 'message' => 'Đã gửi tin nhắn đến bộ phận hỗ trợ GODMINTON.']);
} catch (Throwable $error) {
    error_log('[contact] Request failed: ' . get_class($error));
    contact_out(500, ['status' => 'error', 'message' => 'Chưa lưu được liên hệ. Vui lòng thử lại sau.']);
}
