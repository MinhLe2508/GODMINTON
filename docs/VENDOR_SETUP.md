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
├── composer/
└── [Minishlink Web Push và dependency do Composer cài]
```

PHPMailer 7.0.2 được đóng gói theo Vendor.zip. Composer autoload ánh xạ `PHPMailer\PHPMailer\` tới `vendor/PHPMailer/src/`. Tiện ích `get_oauth_token.php` nằm trong cây theo ZIP; luồng SMTP không gọi file này. Không cài TCPDF. Composer cài `minishlink/web-push` cùng các thư viện phụ thuộc vì chức năng push nền từ PHP cần chúng.

## Sau khi pull

```powershell
composer install --no-dev --prefer-dist --optimize-autoloader
composer check-platform-reqs --no-dev
```

Commit `vendor/PHPMailer/`, vì đây là thư viện được đóng gói trực tiếp. `vendor/composer/` và `vendor/autoload.php` do Composer tạo trên từng máy, không commit. Không chạy `composer require phpmailer/phpmailer` nếu muốn giữ cấu trúc hiện tại.

Sau khi pull, `composer install` sẽ cài thư viện Web Push cùng dependency của nó. Cấu hình riêng VAPID key trong `.env` theo hướng dẫn ở [database/README.md](../database/README.md), rồi import `database/migration_push_reminders.sql` nếu đây là database đã có sẵn. Đặt Task Scheduler hoặc cron chạy `tools/send-push-reminders.php` mỗi 5 phút để bật nhắc lịch tự động. Không cần cài TCPDF.
