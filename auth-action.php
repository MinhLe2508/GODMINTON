<?php
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
header('X-Content-Type-Options: nosniff');
?>
<!doctype html><html lang="vi"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Xác thực tài khoản | GODMINTON</title><link rel="stylesheet" href="assets/css/auth-action.css?v=<?= filemtime(__DIR__.'/assets/css/auth-action.css') ?>"></head>
<body><main class="account-card"><a href="index.php"><img class="logo" src="assets/img/logo-teal.svg" alt="GODMINTON"></a>
<span class="eyebrow">BẢO MẬT TÀI KHOẢN</span><h1 id="actionTitle">Xác minh email</h1>
<p id="actionDescription">Xác nhận để kích hoạt tài khoản và bắt đầu đặt sân.</p>
<form id="accountAction"><div id="passwordFields" hidden>
<label for="newPassword">Mật khẩu mới</label><input id="newPassword" pattern="(?=[\s\S]*\p{Lu})(?=[\s\S]*\p{Ll})(?=[\s\S]*\p{Nd})(?=[\s\S]*[\p{P}\p{S}])[\s\S]*" title="Mật khẩu phải có chữ hoa, chữ thường, chữ số và ký tự đặc biệt." type="password" autocomplete="new-password">
<small>Mật khẩu phải có chữ hoa, chữ thường, chữ số và ký tự đặc biệt.</small>
<label for="confirmPassword">Nhập lại mật khẩu</label><input id="confirmPassword" type="password" autocomplete="new-password">
</div><div id="deleteConfirmation" hidden><label class="delete-consent"><input id="confirmDeleteAccount" type="checkbox"> Tôi hiểu và đồng ý xóa tài khoản.</label></div><button type="submit" id="actionSubmit">Xác minh email</button></form>
<p id="actionMessage" role="status" aria-live="polite"></p>
<a class="back" href="auth.php">Quay lại đăng nhập</a><p class="security-note">Liên kết chỉ có hiệu lực một lần. Không chia sẻ với người khác.</p>
</main><script src="assets/js/auth-action.js?v=<?= filemtime(__DIR__.'/assets/js/auth-action.js') ?>"></script></body></html>
