GODMINTON - HƯỚNG DẪN CHẠY VÀ BÀN GIAO MÃ NGUỒN

Nhóm: Nguyễn Nhật Huy 52400198; Lê Nhật Minh 52400215; Trương Minh Triết 52400323.
Website: https://godminton.top/

1. Môi trường: PHP >= 8.2, PDO MySQL, curl, mbstring, openssl; MySQL/MariaDB InnoDB.
   Máy XAMPP của nhóm dùng MySQL 3307. Mở http://localhost/GODMINTON/ sau khi bật Apache/MySQL.
2. Copy .env.example thành .env, điền DB/SMTP/VAPID/SePay đúng môi trường.
   Chạy composer install --no-dev --prefer-dist --optimize-autoloader.
3. Chỉ trên DB trống: import database/schema.sql hoặc schema_hosting_import.sql
   (chọn một), rồi seed.sql và đối chiếu migration_sepay_payments.sql.
   Xem database/README.md; không chạy lại schema/seed trên DB có giao dịch.
4. Tài khoản phát triển theo dữ liệu mẫu được ghi tại database/README.md.
   Tài khoản hosting có thể đã được thay đổi. Nhóm cần điền tài khoản demo đã
   kiểm tra vào bảng tự đánh giá bàn giao; không dùng mật khẩu cá nhân.
5. Hosting: APP_URL=https://godminton.top/, APP_ENV=production,
   SEPAY_ALLOW_SIMULATE=0, SEPAY_STRICT_AUTH=1. Không thay .env thật bằng bản mẫu.
   Webhook: https://godminton.top/api/payments/sepay-webhook.php
6. Giữ .htaccess. Các thư mục tools/tests/config/database/includes/vendor/.git
   không được phục vụ qua HTTP. Cron gọi tools/send-push-reminders.php bằng PHP CLI.
7. node tests/final-cleanup.test.cjs kiểm thử cô lập, không giao dịch ngân hàng thật.
   Xem README.md để biết cách chạy và giới hạn kiểm thử.
8. tools/make-submission-zip.ps1 tạo gói source có .git, loại .env và file tạm.
   Báo cáo, Rubric.xlsx, video và minh chứng GitHub được bổ sung riêng vào gói nộp.

Tài liệu chi tiết: README.md, database/README.md và thư mục docs/.
