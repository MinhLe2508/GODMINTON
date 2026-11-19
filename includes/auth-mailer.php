<?php

/** Only SMTP delivery through PHPMailer; no public token logs or fake delivery. */
function god_auth_mailer(): \PHPMailer\PHPMailer\PHPMailer {
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (is_file($autoload)) {
        require_once $autoload;
    } elseif (is_file(__DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';
    } else {
        throw new RuntimeException('Mailer unavailable');
    }

    $host = getenv('SMTP_HOST') ?: '';
    $security = getenv('SMTP_ENCRYPTION') ?: 'tls';
    $localTest = getenv('APP_ENV') === 'test' && in_array($host, ['127.0.0.1', 'localhost'], true);
    $base = getenv('APP_URL') ?: '';
    $url = parse_url($base);
    if (!$host || !$base || !filter_var(getenv('MAIL_FROM_ADDRESS'), FILTER_VALIDATE_EMAIL)
        || !$url || empty($url['host']) || !empty($url['user']) || !empty($url['pass'])
        || (!in_array($url['scheme'] ?? '', ['https'], true) && !(($url['scheme'] ?? '') === 'http' && in_array($url['host'], ['localhost', '127.0.0.1'], true)))
        || (!$localTest && (!getenv('SMTP_USERNAME') || !getenv('SMTP_PASSWORD') || !in_array($security, ['tls', 'ssl'], true)))) {
        throw new RuntimeException('SMTP configuration incomplete');
    }

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->Port = (int) (getenv('SMTP_PORT') ?: 587);
    $mail->SMTPAuth = !$localTest || (bool) getenv('SMTP_USERNAME');
    $mail->Username = getenv('SMTP_USERNAME') ?: '';
    $mail->Password = getenv('SMTP_PASSWORD') ?: '';
    $mail->SMTPSecure = $localTest && $security === 'none' ? '' : $security;
    $mail->SMTPAutoTLS = !($localTest && $security === 'none');
    $mail->Timeout = 12;
    $mail->SMTPDebug = 0;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(getenv('MAIL_FROM_ADDRESS'), getenv('MAIL_FROM_NAME') ?: 'GODMINTON');
    return $mail;
}

function god_auth_send(\PHPMailer\PHPMailer\PHPMailer $mail, array $user, string $purpose, string $token): void {
    require_once __DIR__ . '/auth-email-template.php';
    $link = rtrim(getenv('APP_URL'), '/') . '/auth-action.php?mode=' . $purpose . '#token=' . $token;
    $content = god_auth_email_content($user, $purpose, $link);
    $logo = __DIR__ . '/../assets/img/logo.jpg';
    if (is_file($logo))
        $mail->addEmbeddedImage($logo, 'godminton-logo', 'godminton-logo.jpg', 'base64', 'image/jpeg');
    $mail->addAddress($user['email']);
    $mail->Subject = $content['subject'];
    $mail->isHTML(true);
    $mail->Body = $content['html'];
    $mail->AltBody = $content['text'];
    $mail->send();
}
