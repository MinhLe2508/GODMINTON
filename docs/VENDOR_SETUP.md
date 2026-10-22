# Cấu trúc thư viện GODMINTON

```text
vendor/
├── PHPMailer/
│   ├── language/
│   │   └── phpmailer.lang-vi.php
│   ├── src/
│   │   ├── DSNConfigurator.php
│   │   ├── Exception.php
│   │   ├── OAuth.php
│   │   ├── OAuthTokenProvider.php
│   │   ├── PHPMailer.php
│   │   ├── POP3.php
│   │   └── SMTP.php
│   ├── get_oauth_token.php
│   ├── LICENSE
│   └── VERSION
├── autoload.php
└── composer/
```

PHPMailer 7.0.2 được đóng gói theo Vendor.zip. Composer autoload ánh xạ `PHPMailer\PHPMailer\` tới `vendor/PHPMailer/src/`. Tiện ích `get_oauth_token.php` nằm trong cây theo ZIP; luồng SMTP không gọi file này. Không cài TCPDF.

## Sau khi pull

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
```

Commit `vendor/PHPMailer/`, vì đây là thư viện được đóng gói trực tiếp. `vendor/composer/` và `vendor/autoload.php` do Composer tạo trên từng máy, không commit. Không chạy `composer require phpmailer/phpmailer` nếu muốn giữ cấu trúc hiện tại.

Các thư viện khác đã được gỡ khỏi Composer vì chỉ phục vụ Web Push PHP. Giao diện thông báo phía trình duyệt còn trong mã nguồn; muốn gửi push nền trực tiếp từ PHP thì cài lại `minishlink/web-push` bằng Composer. Không cần thay đổi database hay SMTP trong `.env`.
