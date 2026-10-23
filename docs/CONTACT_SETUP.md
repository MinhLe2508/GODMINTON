# Cấu hình email trang liên hệ

Biểu mẫu liên hệ lưu nội dung vào bảng `contacts` và gửi một email thông báo tới địa chỉ quản trị. Email người gửi được đặt làm `Reply-To` để admin có thể trả lời khách trực tiếp. Không dùng email khách làm địa chỉ `From`, vì máy chủ SMTP có thể từ chối hoặc đánh dấu thư là giả mạo.

Trong `.env`, cấu hình SMTP đã dùng cho email xác minh và đặt lại mật khẩu, đồng thời đặt:

```dotenv
CONTACT_ADMIN_EMAIL=admgodminton@gmail.com
```

Tài khoản SMTP và `MAIL_FROM_ADDRESS` phải thuộc tài khoản gửi của website; không đưa mật khẩu SMTP vào Git. Nếu gửi mail thất bại, nội dung liên hệ vẫn được lưu và API báo mã liên hệ để quản trị viên tra cứu. Kiểm tra `error_log` của PHP để chẩn đoán lỗi SMTP; log không ghi nội dung tin nhắn hay thông tin xác thực.
