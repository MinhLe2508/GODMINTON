# GODMINTON

Website PHP thuần đặt sân cầu lông trực tuyến của Nguyễn Nhật Huy (52400198), Lê Nhật Minh (52400215) và Trương Minh Triết (52400323).

## Chức năng

- Khách hàng: tìm sân, xem lịch, giữ chỗ 5 phút, đặt cọc qua VietQR/SePay, xem lịch sử, hủy đơn và theo dõi hoàn cọc.
- Tài khoản: xác minh email, đăng nhập và ghi nhớ bằng token, khôi phục mật khẩu, hồ sơ cá nhân và đăng ký chủ sân.
- Chủ sân và quản trị viên: quản lý sân, lịch, đơn đặt, voucher, hội viên, bài viết và phân quyền theo vai trò.
- PWA: cài ứng dụng, lưu bản lịch đã đồng bộ để xem khi ngoại tuyến, thông báo Web Push và worker nhắc lịch bằng PHP CLI.

## Yêu cầu môi trường

- PHP **8.2 trở lên**, PDO MySQL, ctype, filter, hash, curl, json, mbstring và openssl.
- MySQL/MariaDB hỗ trợ InnoDB; XAMPP của nhóm dùng MySQL cổng **3307**.
- Composer cài thư viện theo `composer.lock`. PHPMailer được giữ tại `vendor/PHPMailer/`.
- HTTPS cho hosting; localhost dùng cho phát triển. Múi giờ ứng dụng: Asia/Ho_Chi_Minh / UTC+7.

## Chạy trên XAMPP

1. Đặt mã nguồn tại `D:\xampp\htdocs\GODMINTON`, bật Apache và MySQL.
2. Sao chép `.env.example` thành `.env`. Điền DB cục bộ: host `127.0.0.1`, port `3307`, tên DB và thông tin người dùng của máy.
3. Cài thư viện:

   ```text
   composer install --no-dev --prefer-dist --optimize-autoloader
   composer check-platform-reqs --no-dev
   ```

4. Chỉ trên **database trống**: chọn DB trong phpMyAdmin, import `database/schema.sql` rồi `database/seed.sql`, đối chiếu `database/migration_sepay_payments.sql` cho phần thanh toán. Trường hợp import hosting gặp thứ tự khóa ngoại, dùng `database/schema_hosting_import.sql` thay cho `schema.sql`; không import nối tiếp cả hai schema.
5. Dữ liệu bài viết/voucher/đánh giá mẫu nằm trong các tệp seed riêng. Kiểm tra phụ thuộc trước khi import; seed không khôi phục tài khoản và giao dịch thật. Với DB đang chạy, sao lưu và dùng migration phù hợp phần thiếu, không chạy lại schema/seed.
6. Mở `http://localhost/GODMINTON/`. Thông tin tài khoản phát triển của bộ dữ liệu mẫu nằm tại `database/README.md`; không mặc định chúng trùng tài khoản trên hosting.

Hướng dẫn chi tiết về database, SMTP và thư viện: `database/README.md`, `docs/AUTH_EMAIL_SETUP.md`, `docs/EMAIL_DELIVERY.md`, `docs/VENDOR_SETUP.md`.

## Cấu hình hosting

Website của nhóm: https://godminton.top/.

- Đặt `.env` riêng trên hosting, dùng DB/user/port do nhà cung cấp cấp và `APP_URL=https://godminton.top/`.
- Giao dịch thật dùng `APP_ENV=production`, `SEPAY_ALLOW_SIMULATE=0`, `SEPAY_STRICT_AUTH=1`. Khóa webhook phía SePay phải khớp cấu hình server.
- URL nhận webhook: `https://godminton.top/api/payments/sepay-webhook.php`.
- Điền SMTP và VAPID riêng; không chia sẻ mật khẩu hoặc khóa riêng trong mã nguồn/báo cáo.
- Giữ `.htaccess`: chặn truy cập HTTP vào cấu hình, `.git`, database, thư viện, công cụ, kiểm thử và báo cáo. Công cụ trong `tools/` chạy bằng **PHP CLI**, không mở bằng URL.
- Worker nhắc lịch dùng cron, ví dụ mỗi 5 phút:

  ```text
  */5 * * * * /DUONG_DAN_PHP /home/TAI_KHOAN/public_html/tools/send-push-reminders.php >> /home/TAI_KHOAN/logs/push-reminders.log 2>&1
  ```

  Thay các đường dẫn bằng giá trị thật. Worker hiện chọn đơn trong cửa sổ 1.800 giây; thời gian nhận phụ thuộc lịch cron, trạng thái đơn, mạng và quyền thông báo. Gửi thử thủ công không thay thế bằng chứng cron tự động.

## Cấu trúc mã nguồn

| Vị trí | Nội dung |
|---|---|
| Các trang `*.php` ở gốc | Giao diện khách, chủ sân và quản trị |
| `api/` | API JSON, xác thực và phân quyền phía server |
| `includes/`, `config/` | Dịch vụ nghiệp vụ, phiên, email, cấu hình |
| `assets/` | JavaScript, CSS, ảnh và biểu tượng |
| `database/` | Schema, seed, migration và hướng dẫn |
| `sw.js`, `manifest.json`, `offline.php` | Service Worker và thành phần PWA |
| `tools/`, `tests/`, `docs/` | Công cụ CLI, kiểm thử và tài liệu |

## Kiểm tra trước phát hành

```text
node tests/final-cleanup.test.cjs
composer validate --no-check-publish
composer check-platform-reqs --no-dev
```

Bộ `final-cleanup.test.cjs` dùng VM JavaScript và PHP server tạm trên 127.0.0.1 với SQLite, không kết nối MySQL, ngân hàng hoặc Push Service thật. Cần PHP CLI có PDO SQLite; có thể đặt biến môi trường `PHP_BINARY` nếu PHP chưa có trong PATH.

Các script kiểm thử nghiệp vụ cũ có thể tạo/sửa dữ liệu; chỉ chạy trên database thử riêng. Kết quả cú pháp và kiểm thử cô lập không thay thế thử nghiệm đăng nhập, email, thanh toán, Push và cron trên môi trường triển khai.

## Đóng gói mã nguồn

```powershell
.\tools\make-submission-zip.ps1 -DryRun
.\tools\make-submission-zip.ps1
```

Script giữ `.git`, `.htaccess` và `.env.example`; loại cấu hình thật `.env*`, log, backup, ZIP cũ, thư mục báo cáo và thư mục tạm. Script không ghi đè ZIP có sẵn. Đây là **gói source**, chưa phải gói nộp hoàn chỉnh: nhóm bổ sung báo cáo, Rubric.xlsx, video, minh chứng GitHub và thông tin tài khoản kiểm thử được phép bàn giao theo hướng dẫn giảng viên. Không tải gói source chứa `.git` lên hosting.
