# GODMINTON bản triển khai tuần 3 của Minh

Bản này được tạo từ GODMINTON.zip ngày kiểm tra 14/10/2026. Lịch sử, hồ sơ và quản lý đơn đã nối MySQL; kèm các sửa nền tuần 2 cần thiết.

Đọc trước:

1. [Hướng dẫn chạy và triển khai từng bước](docs/HUONG_DAN_TUAN3_MINH.md)
2. [Báo cáo kiểm tra bản tuần 2 trước sửa](docs/BAO_CAO_KIEM_TRA_TUAN2.md)
3. [Danh sách file thay đổi](docs/THAY_DOI_TUAN3.md)
4. [Kết quả kiểm thử](docs/KET_QUA_KIEM_THU.json)

## Chạy nhanh

Copy thư mục này vào D:\xampp\htdocs\GODMINTON-tuan3-Minh, bật Apache/MySQL, copy .env.example thành .env và điền DB của bạn. Sao lưu DB trước khi nâng cấp. Trên PowerShell:

```powershell
Set-Location 'D:\xampp\htdocs\GODMINTON-tuan3-Minh'
& 'D:\xampp\php\php.exe' '.\database\migrate_booking_metadata.php'
& 'D:\xampp\php\php.exe' '.\tests\check_booking_integrity.php'
```

Mở http://localhost/GODMINTON-tuan3-Minh/auth.php rồi thử history.php/profile.php/admin-bookings.php. DB mới cần import schema.sql và seed.sql trước migration. DB đã có dữ liệu không import lại schema/seed gốc.

Thanh toán vẫn mô phỏng; chưa kết nối xác thực giao dịch hay hoàn tiền thật. Các phần admin-courts/slots/dashboard của Huy, news/contact của Triết và PWA/push chưa được triển khai trong phạm vi Minh. Các trang đó giữ theo ZIP gốc, nên có thể vẫn dùng dữ liệu giả.

Bản này không phải checkout Git; tích hợp các file đã sửa vào nhánh feature từ project của repo nhóm rồi PR về project. Chỉ đưa project vào main khi toàn dự án hoàn tất. Không chép .env/.git từ bản khác hoặc đưa thư mục kiểm thử DB vào repo.

Không chạy tests/oversell_test.php trên DB nhóm: đây là script fixture có thay đổi/xóa dữ liệu. Bản mới có guard DB thử. check_booking_integrity.php chỉ đọc và có thể dùng để phát hiện dữ liệu cũ không nhất quán.
