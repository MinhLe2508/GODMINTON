# Dữ liệu nghiệp vụ qua API — tuần 3

Các trang hoạt động không cần `assets/js/mock-data.js`. File cũ chỉ còn chú thích ngừng sử dụng, không được trang nào nạp.

- Sân, tiện ích, khung giờ: `api/courts.php`, `api/court-detail.php`, `api/admin/courts.php`, `api/admin/slots.php`.
- Đơn đặt sân, lịch sử, quản lý đơn: API bookings/cancel/admin bookings và MySQL; PATCH dùng token phiên từ `api/auth/me.php`.
- Hồ sơ, đăng nhập, đăng ký, đăng xuất: API và PHP session, không lưu tài khoản trong LocalStorage.
- Tin tức và khám phá trang chủ: `GET api/news.php`, chỉ bài `status=published` và `published_at` không ở tương lai.
- Liên hệ: `GET api/contact.php` cấp token cho cả khách chưa đăng nhập; `POST` kiểm tra dữ liệu rồi lưu bảng `contacts`. Không báo thành công nếu chưa lưu được.
- LocalStorage chỉ dùng cho tùy chọn sáng/tối; không dùng để lưu sân, đơn đặt, tin tức, liên hệ hoặc người dùng.

## Máy khác sau khi pull

Trong terminal ở thư mục dự án, chạy PHP CLI của XAMPP:

```powershell
& D:\xampp\php\php.exe .\database\migrate_content.php
```

Migration chỉ bổ sung bảng/cột thiếu và giữ dữ liệu hiện có. Với DB cũ có bài viết nhưng chưa có cột trạng thái, bài có ngày xuất bản hợp lệ tiếp tục được hiển thị. Bài mới trong schema mới mặc định là bản nháp. Không thêm tin tức mẫu, không chuyển dữ liệu từ LocalStorage vào DB.

Nếu DB nhóm đã có hai bảng nội dung, dùng script PHP trên để kiểm tra và bổ sung cột; chỉ import file SQL sẽ không bổ sung cột cho bảng đã tồn tại.

Tải lại trang bằng Ctrl+F5. Các script trên trang đã gắn phiên bản theo thời điểm sửa file.

## Kiểm tra

- Rà soát PHP/JS/HTML: không còn nạp file dữ liệu giả hoặc tham chiếu đối tượng dữ liệu giả.
- Cú pháp các file PHP, JavaScript và script trong trang.
- Chrome không nạp file dữ liệu giả: tin tức, liên hệ lỗi/thành công và quản lý đơn.
- API news/contact chạy trên database kiểm thử riêng, gồm lọc bài nháp/hẹn giờ, xác thực token, kiểm tra dữ liệu, lưu liên hệ và chặn gửi lặp. Database kiểm thử được xóa sau khi chạy.

Thông tin cổng thanh toán, đánh giá, ưu đãi và đăng ký giải đấu chưa được triển khai thành nghiệp vụ mới trong lần dọn này. Không tạo các sự kiện hoặc ưu đãi mẫu để thay cho nội dung trống.
