# Database GODMINTON

Đã đối chiếu cấu trúc MySQL đang chạy ngày 20/10/2026. Bộ cài gồm **14 bảng**, không dùng seed/dump cũ theo tuần.

## File được giữ

| File | Mục đích |
|---|---|
| `schema.sql` | Cấu trúc 14 bảng, khóa và chỉ mục hiện tại. Chỉ import vào DB trống. |
| `seed.sql` | Danh mục phường, tiện ích, sân, ảnh, liên kết tiện ích và tin tức hiện có; tài khoản phát triển được tạo riêng. |
| `export_installation.php` | Đọc DB hiện tại, tạo lại schema/seed cài mới mà không xuất dữ liệu cá nhân/giao dịch. |
| `migrate_booking_metadata.php` | Nâng cấp metadata đơn đặt trên DB cũ, giữ lịch sử. |
| `migrate_content.php` | Nâng cấp bảng tin tức/liên hệ trên DB cũ; gọi file SQL bên dưới. |
| `migration_content.sql` | Tạo bảng tin tức và liên hệ nếu chưa có. |
| `migration_admin_management.sql` | Thêm cột quản lý sân, khung giờ cho DB cũ. Không sửa giá/giờ của sân theo dữ liệu mẫu. |
| `migration_push_subscriptions.sql` | Tạo bảng thiết bị Web Push cho DB cũ. |
| `migration_customer_accounts.sql` | Import bằng phpMyAdmin để nâng cấp đăng ký/đăng nhập, xác minh email, khôi phục mật khẩu và xóa tài khoản; có thể import lại. |

## Cài mới

1. Khởi động MySQL trong XAMPP.
2. Tạo một database trống tên `badminton_booking`, charset `utf8mb4`, collation `utf8mb4_unicode_ci`.
3. Chọn database đó trong phpMyAdmin, import **`schema.sql` trước**, sau đó **`seed.sql`**.
4. Cấu hình `.env` trỏ đúng DB; máy đang dùng MySQL cổng `3307` thì đặt `DB_HOST=127.0.0.1`, `DB_PORT=3307`.

Không chạy lại seed trên database nhóm đang có dữ liệu. Schema/seed không có lệnh DROP hoặc tự chuyển sang một database cố định.

Snapshot danh mục hiện tại: 47 phường, 8 tiện ích, 114 bản ghi sân (41 đang hoạt động), 114 ảnh, 576 liên kết sân–tiện ích. Bản sân trùng cũ được giữ `inactive` để tương thích ID lịch sử và cấu hình sân thay thế. TPro giữ địa chỉ/tọa độ đã sửa. DB hiện có **0 bài tin tức** nên seed không tự khôi phục các bài đã xóa.

Các bảng `bookings`, `booking_slots`, `time_slots`, `contacts`, `push_subscriptions` ban đầu trống. Không sao chép tài khoản thật, hash mật khẩu thật hay dữ liệu thiết bị. Tạo khung giờ bằng trang quản lý khung giờ; API hiện tại cũng có cơ chế sinh lịch cho ngày chưa có dữ liệu.

### Tài khoản phát triển cho máy cài mới

| Vai trò | Email | Mật khẩu phát triển |
|---|---|---|
| Khách | `khach1@gmail.com` | `Khach@123` |
| Admin | `admin@godminton.vn` | `Admin@123` |
| Chủ sân | `owner.q1@godminton.vn` | `Owner@123` |

Đây là tài khoản tạo riêng cho môi trường phát triển; dữ liệu tài khoản của DB đang chạy không bị thay đổi. Không dùng bộ mật khẩu này khi triển khai công khai.

## Nâng cấp database cũ

Máy đã import schema mới không cần chạy các migration. Với máy đang dùng cấu trúc cũ, sao lưu trước rồi chọn migration đúng phần còn thiếu:

```powershell
& D:\xampp\php\php.exe .\database\migrate_booking_metadata.php
& D:\xampp\php\php.exe .\database\migrate_content.php
```

Hai file migration SQL quản lý sân/Web Push được import trong phpMyAdmin sau khi chọn DB đích. Chúng không xóa lịch/đơn hoặc ghi đè thông tin sân.

## Xuất lại bộ cài sau khi cập nhật dữ liệu

```powershell
& D:\xampp\php\php.exe .\database\export_installation.php
```

Script chỉ SELECT/SHOW từ DB theo `.env`, rồi ghi đè **hai file** schema/seed. Có thể dùng `--output-dir=ĐƯỜNG_DẪN_THƯ_MỤC_ĐÃ_TỒN_TẠI` để xuất ra thư mục khác. Công cụ này không phải backup DB đầy đủ vì chủ động bỏ dữ liệu cá nhân và giao dịch.

## File cũ đã đưa ra khỏi website

Đã lưu riêng các bản dump/seed cũ, JSON danh mục, importer dữ liệu nhúng, script thêm bài/đính chính tọa độ/ẩn sân trùng, script reset lịch và `.gitkeep`. Không cần chạy các script đó để cài hoặc vận hành phiên bản hiện tại.

Bản lưu trước khi dọn trên máy Minh: `D:\MinhDangHocCode\Web\Project\database-cleanup-2026-10-20`. Bản này chứa snapshot riêng của DB hiện tại, không đưa vào Git hoặc thư mục public của website.

## Nâng cấp xác thực email

Sau khi pull, chọn DB website trong phpMyAdmin → Import/Nhập → chọn `migration_customer_accounts.sql` → Go/Thực hiện. File gộp cả xác thực email và xóa tài khoản, có thể import lại. Sau đó chạy `composer install` và cấu hình SMTP trong `.env`. Không import lại schema/seed vào DB đang dùng. Hướng dẫn chi tiết: [AUTH_EMAIL_SETUP.md](../docs/AUTH_EMAIL_SETUP.md). Hai bảng auth không chứa dữ liệu seed.

## Xóa tài khoản

Phần xóa tài khoản đã được gộp trong `migration_customer_accounts.sql`; không chạy migration PHP riêng. File thêm `users.deleted_at` và mục đích token `delete`, không xóa dữ liệu. Không import lại seed/schema vào DB đang dùng.

## Ảnh sân theo Google Maps và ẩn sân không thuê được

- `migration_hide_unrentable_courts.sql`: chuyển 18 sân (sân trong trường, nhà thi đấu công, sân nghi vấn, sân đã đóng cửa) sang `inactive`. DB đang chạy import file này trong phpMyAdmin; cài mới đã có sẵn trong `seed.sql`.
- `court_images_google.csv`: danh sách 41 sân đang hoạt động kèm link ảnh Google Maps đã điền sẵn.
- `update_court_images.php`: đọc CSV và cập nhật `court_images` (`--apply` để ghi thật, `--sql` để xuất `update_court_images.sql` cho thành viên khác).
- `fetch_google_place_photos.php`: tùy chọn, tự lấy link ảnh bằng Google Places API (cần `GOOGLE_MAPS_API_KEY` trong `.env`).
- `update_court_images.sql`: cập nhật link ảnh của 41 sân cho DB đang chạy (import trong phpMyAdmin sau file ẩn sân). Cài mới đã có ảnh trong `seed.sql`.
