# GODMINTON

Website PHP thuần đặt sân cầu lông trực tuyến của Nguyễn Nhật Huy (52400198), Lê Nhật Minh (52400215) và Trương Minh Triết (52400323).

## Chức năng

- Khách hàng tìm sân, xem lịch, giữ chỗ, đặt cọc qua VietQR/SePay, xem lịch sử, hủy đơn và theo dõi hoàn cọc.
- Tài khoản hỗ trợ xác minh email, ghi nhớ đăng nhập bằng token, khôi phục mật khẩu, hồ sơ và đăng ký chủ sân.
- Chủ sân và quản trị viên quản lý sân, khung giờ, đơn đặt, voucher, hội viên, bài viết và tài khoản theo quyền.
- PWA hỗ trợ cài ứng dụng, tra cứu bản lịch đã đồng bộ khi ngoại tuyến, Web Push và cron nhắc lịch.

## Chạy trên XAMPP

1. Bật Apache và MySQL. Dự án đặt tại `D:\xampp\htdocs\GODMINTON`; MySQL của nhóm dùng cổng **3307**.
2. Dùng PHP **từ 8.2**, có PDO MySQL, ctype, filter, hash, curl, json, mbstring và openssl.
3. Sao chép `.env.example` thành `.env` nếu chưa có. Điền DB cục bộ; không ghi đè `.env` đang dùng. Ví dụ:

   ```dotenv
   DB_HOST=127.0.0.1
   DB_PORT=3307
   DB_NAME=badminton_booking_local
   DB_USER=root
   DB_PASS=
   APP_URL=http://localhost/GODMINTON/
   APP_ENV=development
   ```

4. Nếu chưa có thư viện, chạy `composer install --no-dev --prefer-dist --optimize-autoloader`. Giữ thư mục `vendor/PHPMailer/` có trong mã nguồn; PHPMailer được nạp qua ánh xạ Composer.
5. **Cài mới trên database trống:** tạo DB utf8mb4, chọn đúng DB trong phpMyAdmin rồi import duy nhất `database/cai-dat-moi.sql`. Bộ cài đã tổng hợp cấu trúc, phần thanh toán và dữ liệu mẫu; không import tiếp schema/seed sau đó.
6. **Database đang có dữ liệu:** không chạy lại bộ cài mới hoặc seed. Sao lưu và đối chiếu migration trong `database/README.md` khi thật sự cần nâng cấp. Lần clean code này không yêu cầu sửa database.
7. Mở `http://localhost/GODMINTON/`. Các tệp giao diện, CSS, JavaScript và ảnh được giữ nguyên trong lần clean này.

Nếu Apache không dùng cổng 80, thêm cổng tương ứng vào URL truy cập và `APP_URL`.

## Tài khoản mẫu khi cài mới

| Vai trò | Email | Mật khẩu phát triển |
|---|---|---|
| Khách | `khach1@gmail.com` | `Khach@123` |
| Chủ sân | `owner.q1@godminton.vn` | `Owner@123` |
| Quản trị | `admin@godminton.vn` | `Admin@123` |

Đây là ba tài khoản seed, không phải thông tin đăng nhập của website đang vận hành. Chủ sân chỉ quản lý tài nguyên được phân quyền. Không suy ra các tài khoản chủ sân khác tồn tại từ danh sách quận; đối chiếu dữ liệu thực tế khi bàn giao.

## SMTP, Web Push và cron

- Điền cấu hình SMTP và địa chỉ gửi trong `.env` để thử xác minh email, quên mật khẩu, liên hệ và email thanh toán. Cần kiểm tra email nhận thực tế; thông báo trong giao diện không thay thế bằng chứng gửi thư.
- Dùng cặp VAPID riêng của môi trường. Khóa công khai cấp cho trình duyệt; khóa riêng giữ tại server. Trên PowerShell XAMPP, tạo khóa bằng:

  ```powershell
  $env:OPENSSL_CONF = 'D:\xampp\apache\conf\openssl.cnf'
  & 'D:\xampp\php\php.exe' 'D:\xampp\htdocs\GODMINTON\tools\generate-vapid-keys.php'
  ```

- Khi đổi origin hoặc cặp khóa, kiểm tra và đăng ký lại thiết bị nhận Push. Thiết bị cần quyền thông báo, trình duyệt hỗ trợ và kết nối phù hợp.
- Worker nhắc lịch chạy bằng PHP CLI, kiểm tra các đơn đủ điều kiện trong cửa sổ **1.800 giây, tức 30 phút**. Có thể thử không gửi ra ngoài:

  ```powershell
  & 'D:\xampp\php\php.exe' 'D:\xampp\htdocs\GODMINTON\tools\send-push-reminders.php' --dry-run
  ```

- Lên lịch worker mỗi 5 phút bằng Task Scheduler hoặc cron hosting. Khi cần chạy ẩn trên Windows, giữ công cụ `tools/run-push-reminders-hidden.vbs` và truyền đường dẫn PHP theo cấu hình tác vụ.
- Lưu stdout/stderr vào log ngoài thư mục công khai. Thời điểm nhận phụ thuộc lượt quét, trạng thái đơn và thiết bị; gửi thử hoặc dry-run không xác nhận cron đã gửi nhắc thật.

## Cấu hình hosting

Giữ `.env` riêng trên hosting và dùng thông tin DB/SMTP/VAPID/SePay của môi trường đó. Với tên miền của nhóm:

```dotenv
APP_URL=https://godminton.top/
APP_ENV=production
SEPAY_ALLOW_SIMULATE=0
SEPAY_STRICT_AUTH=1
```

Webhook nhận tại `https://godminton.top/api/payments/sepay-webhook.php`. `SEPAY_WEBHOOK_API_KEY` phải khớp cấu hình phía SePay. Không chia sẻ khóa thật hoặc thay `.env` hosting bằng bản mẫu. Chức năng giả lập chỉ phục vụ môi trường phát triển và không bật khi vận hành thật.

Giữ `.htaccess` để chặn `.env`, Git, database, thư viện, công cụ CLI, tệp kiểm thử và báo cáo qua HTTP. Không tải bản sao lưu hoặc gói có `.git` lên document root. Khi cập nhật phiên bản, kiểm tra lại PWA với cả trình duyệt có cache cũ.

## Cấu trúc

| Vị trí | Nội dung |
|---|---|
| `pages/` | Các trang giao diện PHP (khách hàng, chủ sân và quản trị) được ánh xạ URL gốc qua `.htaccess` |
| `api/` | API JSON, xác thực và kiểm tra quyền phía server |
| `includes/`, `config/` | Dịch vụ nghiệp vụ, phiên, email và cấu hình |
| `assets/` | JavaScript, CSS, ảnh và biểu tượng |
| `database/` | Bộ cài mới, schema, seed và migration |
| `sw.js`, `manifest.json`, `offline.php` | Service Worker, metadata PWA và lịch ngoại tuyến |
| `tools/` | Tạo khóa VAPID và chạy tác vụ nhắc lịch |
| `KIEM_THU.cjs` | Bộ hồi quy độc lập 44 tình huống |
| `report/` | Báo cáo lưu cục bộ; không commit và không phục vụ qua HTTP |

## Kiểm thử

Tại thư mục dự án, chạy:

```powershell
$env:PHP_BINARY = 'D:\xampp\php\php.exe'
node .\KIEM_THU.cjs
```

Cần Node.js từ 20 và PHP CLI có PDO SQLite. Bộ kiểm thử dùng JavaScript VM, SQLite và server localhost tạm; không dùng MySQL hiện có, không chuyển tiền hoặc gửi Push thật. Kết quả thành công là `44 passed, 0 failed`. Có thể lưu kết quả JSON ngoài webroot bằng biến `GODMINTON_TEST_REPORT`.

Kiểm thử thủ công trước phát hành:

1. Đăng nhập ba vai trò; kiểm tra khách không truy cập chức năng quản trị và chủ sân chỉ quản lý sân thuộc quyền.
2. Đọc danh sách sân, Khám phá, chi tiết và đánh giá; thử tìm kiếm không có kết quả.
3. Giữ/nhả ô, hết hạn giữ, hai phiên tranh cùng ô, tạo đơn và đối chiếu tiền sân, ưu đãi, phí, cọc/phần còn lại.
4. Với môi trường phát triển, dùng dữ liệu thử riêng. Nếu nghiệm thu thanh toán/hoàn cọc thật, đối chiếu chứng từ, mã giao dịch, webhook và trạng thái đơn; không chỉ dựa vào thông báo thành công trên giao diện.
5. Đồng bộ lịch khi online, chuyển offline và mở lịch; khôi phục mạng, đăng xuất hoặc đổi tài khoản để kiểm tra dữ liệu đúng phạm vi người dùng.
6. Thử email, quyền Push, thông báo hệ điều hành và cron theo nhật ký. Kiểm tra giao diện trên các kích thước màn hình và banner không che nút thao tác.

Kiểm tra cú pháp và bộ hồi quy không thay thế kiểm thử toàn trình, kiểm thử tải hoặc kiểm toán bảo mật. Các tài liệu phát triển và test cũ đã được lưu riêng ngoài webroot; lịch sử mã nguồn vẫn được giữ trong Git.
