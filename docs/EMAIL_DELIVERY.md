# Email GODMINTON: logo, Spam và triển khai

Email xác minh, đặt lại mật khẩu và xác nhận xóa tài khoản dùng chung mẫu HTML có logo hiện tại assets/img/logo.jpg. PHPMailer đính kèm logo dạng CID inline, không lấy ảnh từ localhost hoặc dịch vụ bên ngoài. Có nội dung văn bản dự phòng, nút thao tác, thời hạn và liên kết dự phòng. Việc có logo không đảm bảo vào Inbox; ứng dụng email có thể chặn hiển thị ảnh.

Ảnh tròn cạnh tên người gửi trong Gmail là ảnh hồ sơ của tài khoản Google gửi SMTP. Đăng nhập tài khoản đó, mở Google Account → Personal info → Profile picture và tải logo lên. Có thể dùng assets/img/logo.jpg hiện có. Website không tự thay ảnh hồ sơ Gmail bằng HTML hoặc PHPMailer.

Ảnh chụp báo Gmail phân loại vì thư tương tự các thư từng bị đánh dấu Spam; ảnh chụp không cho biết đầy đủ SPF/DKIM/DMARC hoặc uy tín người gửi. Mở email → dấu ba chấm cạnh Trả lời → Show original/Hiển thị thư gốc để kiểm tra xác thực; không chia sẻ token còn hiệu lực hoặc mật khẩu SMTP.

Trong khi kiểm thử, với thư thực sự do mình yêu cầu, bấm Report not spam/Không phải thư rác. Không gửi dồn dập yêu cầu không cần thiết. Đây không phải bảo đảm thư sau luôn vào Inbox.

Hiện SMTP dùng Gmail: dùng tài khoản gửi riêng cho hệ thống và From cùng tài khoản được xác thực/alias được phép. Không giả địa chỉ noreply ở tên miền chưa được xác minh; không tự thêm header SPF/DKIM/DMARC giả. Chủ ứng dụng không quản lý bản ghi DNS của gmail.com.

Khi lên website thật: đổi APP_URL sang HTTPS có thể truy cập công khai, cấu hình dịch vụ gửi với tên miền của nhóm và xác thực SPF/DKIM/DMARC theo hướng dẫn nhà cung cấp. localhost trong thư chỉ dùng được trên chính máy có website, không phù hợp gửi khách thật. Không thể xác định localhost là nguyên nhân duy nhất của Spam từ ảnh chụp.

Nguồn chính thức: https://support.google.com/mail/answer/81126 ; https://support.google.com/mail/answer/35529 .
