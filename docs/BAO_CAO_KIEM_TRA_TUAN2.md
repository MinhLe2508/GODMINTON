# Báo cáo kiểm tra tuần 1 và tuần 2

Ngày 14/10/2026. Bản kiểm tra: D:\xampp\htdocs\GODMINTON.zip. Tiêu chí: timeline hàng 21–35 và tài liệu hợp đồng API có trong ZIP. Hàng trạng thái trong timeline vẫn là “Chưa bắt đầu”, nên kết luận dựa vào code và thử nghiệm thực tế, không dựa vào ô trạng thái.

## Kết luận

Chưa nên xác nhận “tất cả đã ổn”. Nền Auth/PDO và luồng giữ chỗ đã hoạt động trên MariaDB kiểm thử riêng. 10 cuộc đua thật qua hai tiến trình PHP độc lập đều có đúng một request 200 và một request 409. Xác nhận đơn có transaction và chuyển slot sang booked. Nhưng còn các lỗi quan trọng về dữ liệu đơn, thời gian slot và hủy đơn trước khi triển khai tuần 3.

Không sửa bản ZIP hoặc checkout D:\xampp\htdocs\GODMINTON đang dùng. Tạo bản chạy thử GODMINTON-tuan3-Minh trong workspace, kèm các sửa nền cần cho phần Minh. Không dùng database nhóm để chạy test phá dữ liệu.

## Môi trường và phạm vi kiểm thử

- PHP 8.2.12 của XAMPP; MariaDB 10.4.32.
- MariaDB riêng trên cổng 33316, data directory mới trong workspace; hai DB godminton_review_w2 và godminton_review_w3.
- Import schema, seed và migration tuần 2 từ ZIP gốc thành công trên DB mới. Đủ 9 bảng; seed có 7 sân, 14 phường (7 + 7 bổ sung), 8 tiện ích và các tài khoản demo. Cả ba bcrypt demo khớp mật khẩu ghi trong seed.
- Hai tiến trình PHP riêng thực thi transaction đồng thời; không dùng một PHP built-in server đơn luồng để giả định đã test race condition.
- Chromium headless: booking/confirm/payment của ZIP gốc tại 390, 768, 1440 px. Không gặp pageerror JS hoặc tràn ngang toàn trang trong 9 lượt tải. Bảng lịch có cuộn ngang bên trong.
- Chưa kiểm tra GitHub collaborators/PR, tiến trình scheduler trên máy nhóm, thiết bị thật, cổng thanh toán thật hoặc dữ liệu DB đang chạy. Các chức năng tuần 3–4 chưa có trong ZIP không tính là lỗi hoàn thành tuần 2.

## Kết quả theo công việc tuần 2

| Công việc | Nhận định |
| --- | --- |
| Auth và đọc dữ liệu sân của tuần 1 | Có API thật và SQL tham số. Trang chủ còn hai luồng render sân, một luồng đọc MockDB, một luồng API; app.js lấy user từ MockDB. Chưa đạt mục gỡ phụ thuộc dữ liệu giả hoàn toàn. |
| API hold có transaction + FOR UPDATE | Đạt các ca thử: giữ một/nhiều ô, người khác bị từ chối, ô đã booked không giữ được. Có sắp khóa theo ID và retry deadlock. |
| Cleanup hết hạn | Hàm/CLI/HTTP đã tồn tại; thử endpoint dọn hết hạn thành công. api/slots.php cũng dọn inline. Chưa xác nhận Task Scheduler của nhóm đã cấu hình. |
| Polling 5 giây | calendar_booking.js gọi API slots thật theo chu kỳ 5000 ms, gửi hold/release thật. |
| Confirm tạo booking + booking_slots | Có transaction; xác nhận thành công, không giữ trước thì bị từ chối, giữ hết hạn bị từ chối, xác nhận lặp bị từ chối. Nhưng chưa đủ kiểm tra hợp lệ và chưa lưu đủ metadata. |
| Chống oversell | 10/10 cuộc đua cùng slot: đúng 200/409; đây là kiểm thử do mình chạy, không suy ra nhóm đã chạy script cũ thành công. |
| Responsive | 9 lượt booking/confirm/payment ở ba độ rộng không có lỗi JS hoặc tràn ngang toàn trang; vẫn có thông tin người dùng thiếu trên confirm. |

Bộ kiểm tra nền tuần 2: 15 kiểm tra, 11 đạt và 4 không đạt. Chi tiết các lỗi dưới đây.

## Lỗi cần sửa trước nghiệm thu

### 1. API xác nhận chấp nhận court_id không khớp slot

Trong api/bookings.php, court_id lấy từ request rồi dùng INSERT booking nhưng không so sánh với court_id của slot bị khóa. Tái hiện: giữ một slot sân 2, gửi POST booking với court_id=1 → HTTP 201. Đơn ghi sân 1 nhưng liên kết slot sân 2. Điều này làm sai phạm vi owner, lịch sử và thống kê.

Bản tuần 3 đã từ chối 422; đồng thời kiểm tra cùng sân, ngày, thảm và slot liên tiếp, rồi khóa theo ID để tránh khóa đảo thứ tự.

### 2. Phương thức thanh toán và ghi chú không được lưu

API đọc payment_method/notes nhưng INSERT chỉ có user_id, court_id, tổng tiền, cọc, status, created_at. Mã đơn chỉ được tính khi trả JSON; migration có cột nhưng code chưa ghi. Tái hiện gửi MoMo + ghi chú, SELECT booking cho ra NULL ở hai cột.

Bản tuần 3 ghi đủ mã/phương thức/ghi chú trong transaction. Migration backfill mã cho đơn cũ còn trống; không thể phục hồi phương thức/ghi chú chưa từng được lưu.

### 3. Giữ được khung giờ trong quá khứ

hold.php không so sánh slot_date/start_time với NOW(). Tái hiện tạo slot ngày hôm qua trên DB thử, POST hold nhận 200. Giao diện hạn chế chọn ngày chưa đủ vì client có thể gọi API trực tiếp.

Bản tuần 3 chặn cả hold và confirm nếu khung giờ đã bắt đầu. Kiểm tra quá hạn giữ cũng dùng NOW() của MySQL, tránh phụ thuộc đồng hồ PHP khác nguồn.

### 4. API hủy còn là khung trống

api/cancel.php trả not_implemented. Release giữ chỗ qua hold.php đã có, nhưng chưa có API hủy đơn thật. history.php vẫn gọi MockDB.cancelBooking nên thao tác không cập nhật booking trên DB.

Bản tuần 3 có hủy đơn trong transaction, kiểm tra user/quyền/trạng thái/thời gian; giữ liên kết lịch sử và mở lại slot khi không có đơn khác đang chiếm.

### 5. Khởi tạo slot lần đầu có nguy cơ đua và slot booked giả

slots.php kiểm tra COUNT=0 rồi INSERT từng ô không chống trùng. Hai lần tải đầu tiên có thể cùng thấy 0 rồi chạm unique key. Code còn tự gán ba ô booked mà không tạo booking liên quan. Lỗi đua được nhận diện từ code; không đưa vào 4 ca lỗi đã tái hiện trên baseline.

Bản tuần 3 dùng INSERT IGNORE theo unique key có sẵn; thử hai request song song tải ngày mới đều 200, có đúng 144 slot và không có slot booked giả. Các ô demo đã booked trong DB cũ không bị tự mở, vì không thể suy đoán đó là dữ liệu cần giữ hay demo. Nhóm kiểm tra riêng nếu cần dọn.

### 6. Thông tin xác nhận có email undefined

Login chỉ lưu user_id/role/name vào session. confirm.php và payment.php dựng user từ ba trường này; frontend ưu tiên user đó nên email/phone thiếu, dù MockDB có cache khác. Ảnh xác nhận baseline hiện email “undefined”, điện thoại “N/A”.

Bản tuần 3 đọc đủ name/email/phone từ users khi dựng ba trang booking/confirm/payment. JSON nhúng có cờ HEX để không phá thẻ script. Confirm hiển thị văn bản user qua escape.

### 7. Trang chủ còn hai renderer và phụ thuộc MockDB

index.php có inline AJAX render carousel; index_explore_dt.js cũng đọc MockDB rồi render và khởi tạo Swiper trên cùng vùng. app.js tiếp tục dùng user cached trong trình duyệt, chưa phản ánh session thật.

Bản tuần 3 bỏ renderer sân/news mock trùng, giữ carousel API trong index.php; app.js dùng api/auth/me.php. Các trang của Minh không nạp mock-data.js nữa. Các trang Auth/news/contact/admin khác còn phần MockDB riêng theo bản gốc; cần Huy/Triết hoàn thiện đúng phần được giao.

### 8. Các giới hạn cần ghi rõ

- Thanh toán hiện mô phỏng: server tạo đơn confirmed và cọc 50% khi client bấm xác nhận; chưa có xác minh giao dịch VNPay/MoMo/webhook. Không dùng để thu tiền thật hoặc khẳng định đã thanh toán thật.
- Một số API gốc trả chi tiết exception/DB ra client; config/db.php đã được sửa trong bản mới. Rà toàn bộ API là phần Triết tuần 3.
- .env có trong ZIP gốc. Bản bàn giao mới loại .env và .git; không kết luận .env đã được commit vì chưa kiểm tra lịch sử Git.
- tests/oversell_test.php gốc dùng cú pháp server dành cho Unix, không chạy đúng mặc định trên Windows; cleanup xóa booking theo slot thử, có thể đụng dữ liệu thật. Bản mới chặn HTTP, bắt buộc DB thử và yêu cầu BASE_URL trên Windows. Không chạy script này trên DB nhóm.

## Kết quả bản triển khai tuần 3 của Minh

- 54/54 kiểm tra API trên MariaDB thật đạt, gồm 5 vai trò/tài khoản thử, user_id giả, email trùng, CSRF, mật khẩu, owner_id, hủy/đặt lại, trạng thái, rollback, auto-seed đồng thời, giữ chỗ và xác nhận đồng thời.
- History đọc được với ONLY_FULL_GROUP_BY; không phụ thuộc GROUP BY b.id với các cột tùy ý.
- Migration chạy hai lần thành công; không xóa dữ liệu.
- 9/9 lượt UI tuần 3 (history/profile/admin-bookings × 390/768/1440) đạt kiểm tra pageerror, nguồn script và tràn ngang toàn trang. Đã thử lưu hồ sơ trên UI, lọc trạng thái admin và kiểm tra tên chứa HTML không được thực thi.
- Có kiểm thử hồi quy booking/confirm/payment sau khi sửa; xem hướng dẫn và kết quả kèm theo.
- PHP và JavaScript kiểm tra cú pháp đạt; số file cuối cùng ghi trong KET_QUA_KIEM_THU.json.

## Việc còn của nhóm

Huy: hoàn thiện admin-courts/admin-slots/dashboard tuần 3, chốt schema/giá slot/cleanup scheduler; review các sửa hold/slots/Auth dùng chung. Triết: tin tức/liên hệ, OSM/bản đồ, rà toàn bộ route admin và prepared statements theo tuần 3. Phần news/contact/admin API ngoài Minh vẫn là stub trong ZIP. PWA/push thuộc tuần 4, chưa đánh giá hoàn thành ở lần này.

Kết quả này chứng minh hành vi trên DB thử với các ca đã mô tả, không thay cho nghiệm thu toàn diện mọi đầu vào, thiết bị và dữ liệu thật của nhóm.
