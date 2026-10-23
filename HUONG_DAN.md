# Sửa lỗi danh sách sân quay mãi

Nguyên nhân xác nhận từ Console: updateFilterBadge() truy cập badge.style khi không tìm thấy phần tử filterCountBadge. Lỗi xảy ra trước renderCourts(), nên danh sách chưa được gọi tải. API người dùng cung cấp trả thành công 41 sân.

## Cài bản sửa
1. Giải nén ZIP ra thư mục tạm.
2. Sao lưu file assets/js/courts.js hiện tại ra ngoài thư mục dự án.
3. Chép file assets/js/courts.js trong ZIP đè vào D:\xampp\htdocs\GODMINTON\assets\js\courts.js.
4. Mở http://localhost/GODMINTON/courts.php và nhấn Ctrl + F5.
5. Nếu vẫn thấy lỗi cũ: F12 → Application → Service Workers → đánh dấu Bypass for network → Ctrl + F5. Sau khi kiểm tra xong, bỏ đánh dấu này.

Không cần chạy SQL hoặc import lại database.

## Thay đổi và kiểm tra
Chỉ sửa updateFilterBadge(): tạo lại badge nếu nút bộ lọc còn tồn tại, kiểm tra phần tử trước khi cập nhật. Thiếu cả nút cũng không làm dừng quá trình tải danh sách.
Đã kiểm tra cú pháp JavaScript và mô phỏng khởi động khi thiếu badge/nút: renderCourts vẫn được gọi; số lượng bộ lọc vẫn cập nhật. Chưa chạy trên XAMPP của Huy.
