# Hướng dẫn triển khai tuần 3 của Minh

Ngày 14/10/2026. Nguồn: bản GODMINTON.zip mới của nhóm và timeline, STT 17–18 (hàng 41–42). Công việc được làm trước mốc tuần 3 trong timeline là 20/10–27/10 theo yêu cầu của bạn.

## 1. Phần đã triển khai

- history.php: lấy đơn của đúng user đang đăng nhập, hiển thị mã/ngày/giờ/giá/phương thức/ghi chú, hủy đơn qua API.
- profile.php: đọc và lưu họ tên/email/điện thoại vào MySQL; kiểm tra email trùng; đổi mật khẩu có kiểm tra mật khẩu cũ.
- admin-bookings.php: owner chỉ thấy đơn thuộc sân có owner_id là mình; admin thấy tất cả; tìm mã đơn/khách hàng, lọc trạng thái, hoàn thành/hủy.
- API và trang có guard server. Không nhận user_id/owner_id/role từ client làm căn cứ phân quyền. Ghi dữ liệu của tuần 3 có token CSRF.
- Các trang của Minh đã gỡ script mock-data.js. app.js lấy tài khoản từ api/auth/me.php. LocalStorage chỉ còn phục vụ giao diện của shared app, không dùng làm nguồn dữ liệu cho ba chức năng tuần 3.
- Sửa các lỗi tuần 2 cần thiết: kiểm tra sân/ngày/thảm/slot liên tiếp và thời gian; lưu phương thức/ghi chú/mã đơn; chống auto-seed slot trùng; thông tin email/điện thoại xác nhận lấy từ DB.

Điểm, voucher, khu vực sinh sống/trình độ/giờ chơi ưa thích chưa có cột hoặc nghiệp vụ trong schema được cung cấp: giao diện ghi rõ chưa hỗ trợ. Không lưu giả rồi báo thành công.

## 2. Chạy bản riêng trước khi đưa vào repo nhóm

Thư mục nguồn:

D:\MinhDangHocCode\Web\Project\GODMINTON-tuan3-Minh

Copy cả thư mục này tới:

D:\xampp\htdocs\GODMINTON-tuan3-Minh

Bật Apache/MySQL trong XAMPP. Bản đang chạy của nhóm vẫn là thư mục GODMINTON cũ; URL bản mới có hậu tố tuan3-Minh, tránh mở nhầm.

Copy .env.example thành .env trong thư mục bản mới. Điền thông tin máy bạn, ví dụ:

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3307
DB_NAME=badminton_booking
DB_USER=root
DB_PASS=
```

Cổng 33316 và hai DB godminton_review_w2/w3 chỉ dùng cho kiểm thử của mình; không đưa các giá trị đó vào .env chạy nhóm. Bản bàn giao không chứa .env thử nghiệm hay khóa bí mật.

## 3. Chọn DB và sao lưu

Khuyến nghị dùng DB bản sao cho lượt thử đầu: phpMyAdmin → chọn DB đang chạy → Export SQL. Tạo DB badminton_booking_minh_w3 rồi import bản sao đó; bảo đảm file dump không chứa USE/CREATE DATABASE trỏ lại DB gốc. Trong .env bản mới, đặt DB_NAME=badminton_booking_minh_w3.

Nếu dùng ngay DB nhóm, Export trước khi migration. Migration thêm/cập nhật cột; không xóa booking hay reset slot. Vẫn phải sao lưu vì ALTER TABLE trên MySQL/MariaDB có thể tự commit.

Với DB hoàn toàn mới: schema.sql → seed.sql → migration tuần 3 ở bước tiếp theo. Không import lại schema/seed gốc vào DB đã có dữ liệu vì các file gốc chưa hỗ trợ chạy lại.

## 4. Chạy migration tuần 3

Mở PowerShell tại thư mục bản chạy thử:

```powershell
Set-Location 'D:\xampp\htdocs\GODMINTON-tuan3-Minh'
& 'D:\xampp\php\php.exe' '.\database\migrate_booking_metadata.php'
```

Migration đọc .env, thêm booking_code/payment_method/notes nếu thiếu, sửa booking_code nullable cho INSERT tạo ID trước, bổ sung mã cho đơn cũ còn trống và tạo unique index nếu cần. Có thể chạy lại. Kết quả cần là “Migration tuần 3 thành công” và đúng tên DB đã chọn.

Nếu thất bại, đọc thông báo; không dùng script xóa lịch/đơn để chữa. Các phương thức/ghi chú đã không được lưu trong bản cũ không thể tự khôi phục bằng migration. Migration chỉ backfill mã đơn từ slot_date/id, không dựng giả dữ liệu thanh toán cũ.

## 5. Kiểm tra ba trang

Mở:

- http://localhost/GODMINTON-tuan3-Minh/auth.php
- http://localhost/GODMINTON-tuan3-Minh/history.php
- http://localhost/GODMINTON-tuan3-Minh/profile.php
- http://localhost/GODMINTON-tuan3-Minh/admin-bookings.php

Nếu Apache dùng cổng khác, thêm cổng đó vào URL.

Tài khoản seed mới đã xác minh hash:

| Vai trò | Email | Mật khẩu |
| --- | --- | --- |
| Khách | khach1@gmail.com | Khach@123 |
| Chủ sân | owner.q1@godminton.vn | Owner@123 |
| Admin | admin@godminton.vn | Admin@123 |

Nếu nhóm đã đổi mật khẩu trong DB thật, dùng mật khẩu hiện tại; không import seed lại để ép về mật khẩu demo.

history/profile yêu cầu đăng nhập. Customer mở admin-bookings phải nhận 403. API cũng kiểm tra quyền độc lập với trang.

## 6. Tạo dữ liệu thật để thử lịch sử

1. Đăng nhập khách trên auth.php của bản mới.
2. Chọn một sân, ngày trong tương lai và các ô 30 phút liên tiếp trên cùng thảm.
3. Giữ chỗ và tạo đơn từ confirm/payment. Thanh toán hiện là mô phỏng; không chuyển tiền tới thông tin demo.
4. Mở history.php: đơn phải có đúng sân, ngày/giờ, tổng tiền, mã đơn.
5. Chọn phương thức và nhập ghi chú trước khi tạo đơn; hai giá trị phải còn khi tải lại history.
6. Trong phpMyAdmin kiểm tra bookings và booking_slots. Tiền phải do server tính theo giá sân và phụ thu, không theo giá gửi từ browser.

Ví dụ truy vấn chỉ đọc:

```sql
SELECT id,user_id,court_id,booking_code,status,total_price,
       deposit_amount,payment_method,notes
FROM bookings ORDER BY id DESC;

SELECT bs.booking_id,s.id,s.court_id,s.sub_court_name,
       s.slot_date,s.start_time,s.end_time,s.status
FROM booking_slots bs
JOIN time_slots s ON s.id=bs.slot_id
ORDER BY bs.booking_id DESC,s.start_time;
```

Luồng này chưa có webhook/xác nhận giao dịch của VNPay/MoMo hoặc gửi email thật. Việc chọn một phương thức và tạo đơn không chứng minh đã thu tiền.

## 7. Kiểm thử lịch sử và hủy đơn

- Khách A tạo đơn. Đăng nhập khách B trên trình duyệt/private window khác: không được thấy đơn A.
- Dùng DevTools Network xem GET api/bookings.php. Thêm user_id của A vào URL khi đang là B không được chuyển lịch sử sang A.
- Hủy một đơn confirmed trong tương lai. Đơn chuyển cancelled, slot về available; booking_slots vẫn giữ để đọc lịch sử.
- Khách khác đặt lại slot đã mở. Gửi lại yêu cầu hủy đơn cũ không được mở slot của đơn mới.
- Đơn đã bắt đầu, completed hoặc cancelled không được chuyển ngược thành confirmed bằng API quản lý.
- Việc hủy không tự hoàn tiền cọc. Chính sách hoàn tiền cần nhóm/chủ sân xử lý riêng; bản này không khẳng định đã hoàn tiền.

Quy tắc hiện triển khai: hủy trước giờ bắt đầu; owner/admin chỉ đánh dấu hoàn thành sau khi tất cả slot đã kết thúc. Nếu nhóm muốn chính sách khác, sửa điều kiện trong booking-service.php cùng thông báo UI và cập nhật kiểm thử.

## 8. Kiểm thử hồ sơ và mật khẩu

1. Sửa họ tên/email/điện thoại → Lưu thay đổi → tải lại → kiểm tra users trong DB.
2. Email trùng tài khoản khác cần bị từ chối 409; số điện thoại/họ tên không hợp lệ nhận 422.
3. Thêm role=admin hoặc user_id khác vào JSON gửi lên không được đổi quyền hay sửa tài khoản khác.
4. Thử đổi mật khẩu cũ sai: phải từ chối. Mật khẩu mới cần khác mật khẩu cũ, từ 6 tới 72 byte theo bcrypt.
5. Đổi đúng → đăng xuất → mật khẩu cũ không đăng nhập được, mật khẩu mới đăng nhập được.
6. Nếu API ghi dữ liệu trả 403 “Phiên thao tác không hợp lệ”, tải lại trang. booking-account-client.js lấy token từ me/profile và gửi X-GODMINTON-CSRF.

Đổi mật khẩu là API của Huy nhưng phần profile của Minh phải nối vào API thật; bản này thêm kiểm tra CSRF cho endpoint đó. Báo Huy khi tích hợp thay đổi.

## 9. Kiểm thử quản lý đơn

1. Đăng nhập owner → admin-bookings.php. Chỉ xem đơn của sân thuộc owner đó.
2. Tìm theo mã GDM… hoặc tên/email khách hàng; lọc confirmed/completed/cancelled.
3. Owner thử PATCH đơn thuộc owner khác: 404. Customer gọi API admin: 403; chưa đăng nhập: 401.
4. Đăng nhập admin: thấy toàn bộ, vẫn phải tuân thủ chuyển trạng thái.
5. Đơn tương lai chỉ có nút Hủy; khi thời gian đã kết thúc, nút Hoàn thành xuất hiện. Không sửa ngày DB nhóm để ép thử; dùng DB thử riêng nếu cần dữ liệu quá khứ.
6. Trên mobile, vuốt ngang bảng để xem các cột thao tác. Thử ở 390/768/1440 px bằng DevTools.

Seed gốc gán cả 7 sân cho một owner, nên chưa đủ để chứng minh cô lập hai owner. Muốn kiểm thử đầy đủ, trên DB thử tạo owner thứ hai và gán một sân cho người đó; tạo đơn tại hai sân rồi so sánh.

## 10. Hợp đồng API đã triển khai

| API | Phương thức | Dữ liệu |
| --- | --- | --- |
| api/auth/me.php | GET | data.user hoặc null, csrf_token |
| api/profile.php | GET | data.user, csrf_token |
| api/profile.php | POST | name, email, phone; bỏ qua role/user_id |
| api/auth/change-password.php | POST | old_password, new_password |
| api/bookings.php | GET | data là mảng đơn riêng; query status, q tùy chọn |
| api/bookings.php / api/confirm.php | POST | slot_ids hoặc slot_id; court_id tùy chọn nhưng phải khớp; payment_method, notes |
| api/cancel.php | POST | booking_id hoặc slot_ids đang giữ |
| api/admin/bookings.php | GET | query q, status; phạm vi owner/admin |
| api/admin/bookings.php | PATCH | booking_id, status=completed/cancelled |

Các thao tác profile/password/cancel/admin cần X-GODMINTON-CSRF. POST booking giữ tương thích frontend tuần 2 và từ chối Origin khác website; chuyển toàn bộ endpoint cũ sang CSRF thống nhất là việc cần phối hợp khi nhóm rà soát bảo mật.

## 11. Đưa vào repo đang dùng nhánh project

Chỉ thao tác trong checkout Git thật của nhóm, không git init thư mục chạy thử này. Kiểm tra đúng repo và lưu thay đổi còn dở trước khi chuyển nhánh:

```bash
git status
git switch project
git pull --ff-only origin project
git switch -c feature/minh-week3-bookings-profile
```

Mở docs/THAY_DOI_TUAN3.md để xem danh sách file. Chép các file mới/sửa vào đúng đường dẫn trong checkout. Không chép .env, folder DB kiểm thử, cookie, log hay toàn bộ bản sao. Nếu project đã có thay đổi mới hơn ZIP, dùng diff và hợp nhất phần cần thiết, tránh copy đè file dùng chung.

Các file cần phối hợp Huy: api/hold.php, api/slots.php, api/auth/change-password.php và config/db.php. app.js dùng chung cần báo cả Huy/Triết. Mã quản trị sân/khung giờ/dashboard của Huy vẫn theo bản ZIP; không dùng việc admin-bookings đã chạy để kết luận tất cả menu admin hoàn thành.

Sau khi kiểm thử checkout thật:

```bash
git diff --stat
git diff
# Stage từng file đã kiểm tra, sau đó:
git diff --cached
git commit -m "feat(minh): implement week3 history profile and booking management"
git push -u origin feature/minh-week3-bookings-profile
```

Tạo PR base=project. Ghi migration, cách thử, các sửa nền tuần 2 và giới hạn thanh toán mô phỏng. Huy review DB/Auth/hold; một thành viên khác review giao diện. Chỉ đưa project sang main khi toàn dự án hoàn tất theo quy ước đã thống nhất.

## 12. Checklist hoàn thành của Minh

- [ ] Migration thành công trên DB thử và DB dùng chung sau sao lưu.
- [ ] History đúng user, đọc booking thật, không phụ thuộc MockDB.
- [ ] Profile lưu thật; email trùng và dữ liệu lỗi bị từ chối.
- [ ] Đổi mật khẩu đúng/sai đã thử, đăng nhập lại bằng mật khẩu mới.
- [ ] Owner cô lập theo owner_id; admin xem tất cả.
- [ ] Tìm kiếm/lọc/hoàn thành/hủy đều cập nhật DB thật.
- [ ] Hủy mở slot, giữ lịch sử và không mở nhầm slot được đặt lại.
- [ ] Các trang của Minh chạy không cần mock-data.js.
- [ ] Kiểm thử lại luồng tuần 2 sau sửa, PR vào project được review.

Kết quả trên môi trường kiểm thử riêng: 54/54 kiểm tra API đạt; 9/9 lượt hiển thị ba trang tuần 3 ở ba độ rộng đạt. Dữ liệu kiểm thử không đưa vào seed hoặc DB nhóm. Chi tiết kiểm tra bản ZIP trước sửa nằm trong BAO_CAO_KIEM_TRA_TUAN2.md.

## Kiểm tra DB cũ sau migration bằng lệnh chỉ đọc

```powershell
& 'D:\xampp\php\php.exe' '.\tests\check_booking_integrity.php'
```

Script báo các cột tuần 3, booking thiếu slot, court_id không khớp, slot có nhiều đơn đang chiếm và slot booked không có booking hoạt động. Nếu status=review_required, kiểm tra với Huy; không tự xóa/reset hàng loạt. Các slot booked demo từ bản cũ có thể xuất hiện trong báo cáo. Migration không tự sửa đơn cũ có sân/slot không khớp vì cần xác minh dữ liệu trước.
