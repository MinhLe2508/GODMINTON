# Xóa tài khoản

Sau khi pull: `composer install`, `php database/migrate_auth_security.php`, `php database/migrate_account_deletion.php`. SMTP phải được cấu hình theo AUTH_EMAIL_SETUP.md. Cài mới vẫn import schema.sql rồi seed.sql.

Trang cá nhân → Mật khẩu & Bảo mật → Xóa tài khoản. Nhập mật khẩu hiện tại, gửi email, mở liên kết trong 15 phút, đánh dấu đồng ý và bấm Xác nhận xóa tài khoản. GET/mở email không tự xóa. Chưa xác nhận thì tài khoản vẫn hoạt động. Liên kết chỉ dùng một lần; gửi lại thay thế liên kết cũ.

Hệ thống chặn tài khoản quản trị, tài khoản còn sở hữu bất kỳ sân nào, đơn pending/confirmed và đơn cancelled còn deposit_amount > 0. DB hiện chưa có trạng thái hoàn tiền riêng, nên đơn hủy có cọc được chặn thận trọng và cần quản trị xử lý; không tự coi tiền cọc đã hoàn.

Xác nhận xóa sẽ vô hiệu tài khoản, ẩn danh tên/email, bỏ số điện thoại/ảnh, thay hash mật khẩu ngẫu nhiên, thu hồi mọi phiên và token. Xóa đăng ký thông báo đẩy và các liên hệ gắn user_id; bỏ ghi chú đơn của tài khoản; trả các khung giờ đang giữ. Hàng users giữ lại dưới dạng ẩn danh để không làm đứt khóa ngoại: giữ nguyên mã đơn, số tiền, trạng thái, sân và khung giờ lịch sử. Đây không phải thao tác DELETE users/cascade giao dịch.

Yêu cầu và xác nhận đều chống CSRF, giới hạn số lần, kiểm tra điều kiện lại trong transaction. Không gửi mail/tạo token khi sai mật khẩu hoặc đang bị chặn. Không kiểm thử bằng cách xóa tài khoản thật; dùng DB và SMTP thử nội bộ.
