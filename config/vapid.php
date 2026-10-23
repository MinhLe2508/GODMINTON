<?php
require_once __DIR__ . '/load-env.php';

$subject = trim((string)(getenv('VAPID_SUBJECT') ?: ''));
$publicKey = trim((string)(getenv('VAPID_PUBLIC_KEY') ?: ''));
$privateKey = trim((string)(getenv('VAPID_PRIVATE_KEY') ?: ''));

if ($subject === '' || $publicKey === '' || $privateKey === '') {
    throw new RuntimeException('Web Push chưa được cấu hình đầy đủ trong .env.');
}
if (!preg_match('/^(mailto:[^\s@]+@[^\s@]+|https:\/\/[^\s]+)$/i', $subject)) {
    throw new RuntimeException('VAPID_SUBJECT phải là mailto: hoặc URL HTTPS.');
}
if (!preg_match('/^[A-Za-z0-9_-]{80,100}$/', $publicKey) || !preg_match('/^[A-Za-z0-9_-]{40,60}$/', $privateKey)) {
    throw new RuntimeException('Cặp VAPID key không hợp lệ.');
}

return ['subject' => $subject, 'publicKey' => $publicKey, 'privateKey' => $privateKey];
