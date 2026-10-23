<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
if (PHP_OS_FAMILY === 'Windows' && !getenv('OPENSSL_CONF')) {
    $xamppConfig = dirname(dirname(PHP_BINARY)) . DIRECTORY_SEPARATOR . 'apache' . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'openssl.cnf';
    if (is_file($xamppConfig)) {
        fwrite(STDERR, "Trước khi chạy, hãy đặt OPENSSL_CONF trong PowerShell: `$env:OPENSSL_CONF='$xamppConfig'\n");
        exit(1);
    }
}
try {
    $keys = \Minishlink\WebPush\VAPID::createVapidKeys();
} catch (Throwable $e) {
    fwrite(STDERR, "Không tạo được VAPID key. Kiểm tra OpenSSL và biến OPENSSL_CONF.\n");
    exit(1);
}
fwrite(STDOUT, "VAPID_PUBLIC_KEY={$keys['publicKey']}\nVAPID_PRIVATE_KEY={$keys['privateKey']}\n");
