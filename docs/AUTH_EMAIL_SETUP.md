# Đăng ký và khôi phục mật khẩu bằng PHPMailer

## Máy đang chạy website

PHPMailer đã được cài bằng Composer. Không có mật khẩu SMTP nào được ghi vào mã nguồn.
Mở `.env` trong thư mục gốc website, điền trực tiếp:

```dotenv
APP_URL=http://localhost/GODMINTON
APP_ENV=development
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_ENCRYPTION=tls
SMTP_USERNAME=your-account@gmail.com
SMTP_PASSWORD=your-google-app-password
MAIL_FROM_ADDRESS=your-account@gmail.com
MAIL_FROM_NAME=GODMINTON
```

Gmail cần bật xác minh hai bước rồi tạo **Mật khẩu ứng dụng** (App Password).
Điền App Password 16 ký tự không có dấu cách, không dùng mật khẩu đăng nhập Google.
Không gửi mật khẩu qua chat, không commit `.env`. Một số tài khoản trường/công ty không cho tạo App Password; khi đó dùng SMTP được đơn vị cho phép.

`APP_URL` là URL người nhận email có thể truy cập. `localhost` chỉ chạy trên chính máy mở email.
Khi triển khai thật, đặt URL HTTPS của website và `APP_ENV=production`. Không tắt kiểm tra chứng chỉ TLS.
Không dùng Host header của request để tạo liên kết email.

## Thành viên vừa pull code

Tại thư mục website, chạy (đổi đường dẫn PHP nếu XAMPP khác):

```powershell
& 'D:\xampp\php\php.exe' 'C:\composer\composer.phar' install --no-dev --prefer-dist
```

Trong phpMyAdmin: chọn database website → tab **Import/Nhập** → chọn `database/migration_customer_accounts.sql` → **Go/Thực hiện**. File có thể import lại; thêm `users.session_version`, `users.deleted_at`, `auth_tokens` (verify/reset/delete) và `auth_rate_limits`, không thay đổi tài khoản hiện có. Không cần chạy file PHP migration tài khoản.
Không import lại `schema.sql`/`seed.sql` vào DB đã có dữ liệu. Với cài đặt hoàn toàn mới: import `schema.sql`, rồi `seed.sql`.
Mỗi máy tự điền SMTP trong `.env`. Không sao chép `.env` lên Git hoặc dùng chung mật khẩu cá nhân trong nhóm.

## Kiểm tra từng luồng

1. Mở `auth.php`, đăng ký bằng email bạn kiểm soát; mật khẩu mới phải có chữ hoa, chữ thường, chữ số và ký tự đặc biệt; không yêu cầu tối thiểu 12 ký tự hoặc giới hạn 72 byte.
2. Trước xác minh, đăng nhập phải bị từ chối. Email có tên đăng nhập là email và liên kết xác minh 60 phút; mở liên kết rồi bấm **Xác minh email**.
3. Đăng nhập thành công sau xác minh. Đăng ký không tự đăng nhập; không tự cấp vai trò admin.
4. Bấm **Quên mật khẩu**, nhập email; mở email và đặt mật khẩu mới trong 15 phút. Email không chứa mật khẩu.
5. Liên kết đã dùng không dùng lại được; mật khẩu cũ và các phiên trên thiết bị khác đều bị vô hiệu sau đặt lại.
6. Với email không tồn tại, màn hình vẫn trả cùng thông báo tiếp nhận; không tiết lộ danh sách tài khoản.
7. Gửi lại xác minh tạo liên kết mới và vô hiệu liên kết xác minh trước.
8. Nếu SMTP chưa cấu hình/gửi thất bại, hiển thị lỗi dịch vụ, không báo đã gửi giả; lần đăng ký thất bại được rollback.
9. Thử giao diện trên điện thoại, kiểm tra cả hộp thư rác. Khi lỗi, xem Apache/PHP error log; không bật SMTPDebug trên website.

## Các biện pháp đi kèm

- Hash mật khẩu mới bằng Argon2id, vẫn kiểm tra được hash bcrypt của tài khoản cũ; token ngẫu nhiên 256 bit, DB chỉ lưu SHA-256, hết hạn và dùng một lần.
- CSRF cho đăng nhập, đăng ký, email xác minh, quên/đổi/đặt lại mật khẩu; regenerate session khi đăng nhập/đổi mật khẩu.
- Cookie HttpOnly, SameSite=Lax, Secure khi HTTPS; hết phiên sau 30 phút không hoạt động hoặc tối đa 12 giờ.
- Giới hạn đăng nhập: 10 lần/email/15 phút, 40 lần/IP/15 phút; thành công xóa bộ đếm email.
- Email: 3 yêu cầu/email/giờ, 15/IP/giờ; đăng ký: 3/email/giờ, 10/IP/giờ. Bộ đếm lưu MySQL, không phụ thuộc cookie.
- Service Worker không cache trang xác thực/hồ sơ/lịch sử/thanh toán/quản trị hoặc response có `Cache-Control: no-store/private`; phiên bản cache mới dọn bản cũ.
- Các trang và API dùng chung kiểm tra phiên/phiên bản tài khoản; DB vẫn giữ tài khoản hiện có. Không bắt đổi mật khẩu cũ ngay, nhưng mật khẩu mới phải theo chính sách mới.
- Trang nhận token không tải CDN, có CSP và Referrer-Policy; token nằm trong fragment rồi bị xóa khỏi thanh địa chỉ, không nằm trong GET/access log.
- Apache chặn đọc `.env`, Composer metadata và thư mục config/includes/database/vendor. Nếu chạy Nginx, tự thêm quy tắc tương đương.
- Các tài khoản seed chỉ dành phát triển. Đổi mật khẩu hoặc xóa trước khi triển khai công khai.

Không có dịch vụ SMTP/queue gửi nền: request gửi email chờ SMTP tối đa khoảng 15 giây. Thông báo không tiết lộ tồn tại tài khoản, nhưng thời gian phản hồi chưa được đồng nhất tuyệt đối. Khi đưa lên môi trường thật nên bổ sung hàng đợi email, giám sát gửi thất bại và MFA cho quản trị viên.
Email đã xác minh không thể đổi trực tiếp qua hồ sơ; thay đổi địa chỉ cần một luồng xác minh email mới riêng, chưa triển khai ở phiên bản này.
Không có OAuth Google hay đăng nhập giả. Quên mật khẩu áp dụng cho mọi vai trò hiện có (`customer`, `owner`, `admin`); chưa có vai trò nhân viên riêng trong schema.

Dọn dữ liệu auth cũ định kỳ qua công cụ quản trị DB (không xóa token còn hiệu lực):

```sql
DELETE FROM auth_tokens WHERE expires_at < NOW() - INTERVAL 7 DAY;
DELETE FROM auth_rate_limits WHERE window_started_at < NOW() - INTERVAL 2 DAY;
```

Tài liệu: [PHPMailer](https://github.com/PHPMailer/PHPMailer), [Google App Passwords](https://support.google.com/accounts/answer/185833), [OWASP Forgot Password](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html).

Quy tắc áp dụng cho đăng ký, đặt lại và đổi mật khẩu: đủ bốn nhóm chữ hoa/chữ thường/số/ký tự đặc biệt, hỗ trợ chữ có dấu. Khoảng trắng không được coi là ký tự đặc biệt. Hash mới dùng Argon2id để không bị cắt ở 72 byte; tài khoản cũ vẫn đăng nhập bình thường. Giới hạn kích thước request API vẫn áp dụng để bảo vệ máy chủ.

Cập nhật ngày 21/10/2026: mật khẩu mới phải có ít nhất 8 ký tự và đủ chữ hoa, chữ thường, số, ký tự đặc biệt. Đăng ký, đặt lại và đổi mật khẩu đều có danh sách 5 điều kiện, tích xanh theo nội dung đang nhập. API kiểm tra cùng quy tắc; không áp dụng giới hạn 72 byte.

## Cập nhật hai luồng đã chốt ngày 22/10/2026

Sau khi pull bản có xét duyệt chủ sân, cần import thêm database/migration_owner_account_management.sql sau migration_customer_accounts.sql trong phpMyAdmin. Mã đăng nhập và quản lý tài khoản hiện dùng các cột trạng thái mới, vì vậy phải cập nhật cả hai file SQL trên DB cũ. Không import lại schema/seed vào DB đang dùng. Xem docs/OWNER_ACCOUNT_WORKFLOW.md để kiểm thử đầy đủ.
