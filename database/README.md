# Database GODMINTON

## Cài mới

Chỉ thực hiện trên **database mới trống**:

1. Bật MySQL/MariaDB. XAMPP của nhóm dùng cổng **3307**.
2. Tạo database utf8mb4, ví dụ `badminton_booking_local`, collation `utf8mb4_unicode_ci`.
3. Chọn đúng database trong phpMyAdmin, import **duy nhất `cai-dat-moi.sql`**.
4. Điền tên database, host, cổng và tài khoản cục bộ trong `.env`, rồi mở website.

Bộ cài tổng hợp cấu trúc từ `schema.sql`, phần bổ sung SePay và token đăng nhập, nội dung hội viên và các seed. Các bảng được sắp theo phụ thuộc để tránh lỗi khóa ngoại khi tạo mới. Không import thêm `schema.sql`, `schema_hosting_import.sql` hoặc các seed sau bộ cài tổng hợp.

Bộ dữ liệu mẫu gồm 28 bảng, 114 sân, 9 bài viết, 125 đánh giá và ba tài khoản đang hoạt động. Các tài khoản phục vụ dữ liệu đánh giá được khóa. Không có dữ liệu giao dịch hoặc thông tin đăng nhập cá nhân của hosting.

| Vai trò mẫu | Email | Mật khẩu phát triển |
|---|---|---|
| Khách | `khach1@gmail.com` | `Khach@123` |
| Chủ sân | `owner.q1@godminton.vn` | `Owner@123` |
| Admin | `admin@godminton.vn` | `Admin@123` |

## Database đang vận hành

Không import lại bộ cài mới, schema hoặc seed vào DB có dữ liệu. Lần clean code không yêu cầu import SQL hoặc chuyển dữ liệu hiện có.

Khi cần nâng cấp một DB cũ, sao lưu và kiểm tra `SHOW CREATE TABLE` trước. Các migration được giữ để chọn đúng phần còn thiếu; không chạy toàn bộ theo tên hoặc theo thứ tự chữ cái.

| Nhóm chức năng | Tệp liên quan |
|---|---|
| Đăng nhập, xác minh email, token | `migration_customer_accounts.sql` |
| Hồ sơ người dùng | `migration_user_profile.sql` |
| Hồ sơ chủ sân, xét duyệt, nhật ký quản trị | `migration_owner_account_management.sql` |
| Quản lý sân, khung giờ | `migration_admin_management.sql` |
| Thanh toán, hội viên và token ghi nhớ | `migration_sepay_payments.sql`, `migration_memberships.sql` |
| Bài viết, liên hệ, nội dung | `migration_content.sql`, `migration_posts.sql` |
| Voucher | `migration_vouchers.sql` |
| Đánh giá | `migration_reviews.sql` |
| Web Push, lịch sử gửi nhắc | `migration_push_subscriptions.sql`, `migration_push_reminders.sql` |

Các công cụ `migrate_*.php`, `export_installation.php`, tệp cập nhật danh mục và `reset_reviews.sql` chỉ dùng khi chủ động bảo trì dữ liệu đúng phạm vi. Không chạy để thử giao diện hoặc sửa lỗi cấu hình kết nối. Tệp reset có thể xóa dữ liệu đánh giá.

## Tệp hỗ trợ khởi tạo

`config/db.php` vẫn tham chiếu `schema.sql`, `seed.sql`, `seed_vouchers.sql` và `seed_reviews_demo.sql` cho cơ chế dự phòng. Các tệp này được giữ để không làm mất phụ thuộc; cài đặt có kiểm soát nên import bộ tổng hợp trước khi truy cập website.

`schema_hosting_import.sql` là phương án schema của phiên bản cũ, không phải bước tiếp theo sau `schema.sql`. Đối với bộ cài hiện tại, dùng `cai-dat-moi.sql` trên DB trống để có đủ dữ liệu Khám phá, tài khoản và phần thanh toán.

## Kiểm tra sau cài đặt

- Kiểm tra số bảng, các bảng tài khoản, sân, khung giờ, đơn, thanh toán, nội dung và Web Push.
- Đăng nhập ba vai trò, xem sân và Khám phá; kiểm tra chủ sân không có quyền ngoài phạm vi sở hữu.
- Tạo lịch cho một ngày tương lai, giữ ô và đặt đơn trên dữ liệu thử. Đối chiếu giá và trạng thái trước/sau thao tác.
- Cấu hình SMTP/VAPID và dịch vụ thanh toán riêng theo môi trường; SQL không thay thế các khóa dịch vụ.

Hướng dẫn chạy ứng dụng, cấu hình và kiểm thử đặt tại `../README.md`. Các tệp SQL và công cụ database bị chặn qua HTTP bằng `.htaccess`.
