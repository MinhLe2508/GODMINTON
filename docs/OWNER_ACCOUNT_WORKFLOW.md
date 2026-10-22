# Đăng ký sân và quản lý tài khoản GODMINTON

## Cập nhật sau khi pull

1. Bật Apache/MySQL, sao lưu DB đang dùng.
2. Chạy `composer install` trong thư mục website nếu chưa có vendor.
3. Chọn DB website trong phpMyAdmin → Import/Nhập → `database/migration_customer_accounts.sql` (nếu chưa cập nhật tài khoản).
4. Import `database/migration_owner_account_management.sql`. Có thể import lại; không import lại schema/seed vào DB đã có dữ liệu. Không cần chạy file PHP import.
5. Điền SMTP và APP_URL trong `.env` riêng từng máy; không đưa mật khẩu lên Git. Nhấn Ctrl+F5 sau khi cập nhật.

Cài mới hoàn toàn: chọn DB trống, import schema.sql rồi seed.sql. Tài khoản seed chỉ dành phát triển. Không dùng tài khoản thử để quản lý dữ liệu thật.

## Người dùng đăng ký sân

`auth.php` → đăng ký (có thể chọn ý định làm chủ sân) → xác minh email → đăng nhập → `owner-application.php`. Mọi đăng ký mới đều mang vai trò customer cho đến khi được duyệt. Người có tài khoản truy cập từ Trang cá nhân → Đăng ký/Theo dõi hồ sơ sân.

- Sân mới: tên, địa chỉ, phường, ghim bản đồ hoặc nhập tọa độ, giá/giờ, số sân con, giờ hoạt động, ảnh thực tế bằng URL HTTPS, tiện ích và giới thiệu.
- Nhận quản lý: tìm và chọn sân có sẵn, không tự tạo thêm bản ghi.
- Nhập đại diện, số liên hệ, mô tả xác nhận quyền quản lý tối thiểu 20 ký tự; có thể cung cấp URL HTTPS tới tài liệu/trang chính thức. Phiên bản này dùng liên kết tài liệu, không có tải tệp chứng minh lên server.
- Lưu nháp, chỉnh sửa, gửi xét duyệt hoặc rút hồ sơ chưa duyệt. Hồ sơ cần bổ sung/bị từ chối được chỉnh sửa và gửi lại. Mỗi người có tối đa một hồ sơ sân mới đang chờ tại một thời điểm; có thể gửi thêm sau khi hồ sơ được xử lý, và nhận quản lý nhiều sân khác nhau.
- Hồ sơ và thông tin chứng minh chỉ người gửi và Admin xem được. Cảnh báo sân cùng tên/địa chỉ hoặc tọa độ gần nhau không tự gộp hay tự xóa sân.

## Admin xét duyệt

Đăng nhập Admin → `admin-owner-requests.php`. Chủ sân/khách không truy cập được cả trang và API này.

Xem hồ sơ, người gửi, thông tin chứng minh, sân đang thuộc ai và cảnh báo trùng. Chọn Duyệt / Cần bổ sung / Từ chối, nhập lý do và mật khẩu Admin. Duyệt sân mới có cảnh báo trùng yêu cầu xác nhận đã kiểm tra đây là địa điểm riêng.

Với sân đã có người quản lý, nút Duyệt thông thường không tự chuyển quyền. Chỉ dùng **Xác nhận chuyển giao và duyệt** khi Admin đã xác nhận bàn giao với các bên. Hệ thống chặn nếu còn đơn pending/confirmed hoặc phiên giữ chỗ còn hạn; giữ mọi đơn đã hoàn thành và lịch sử.

Duyệt thực hiện trong transaction: trạng thái hồ sơ, cấp role owner nếu cần, giao sân và nhật ký. Hồ sơ chỉ được xử lý một lần theo phiên bản đang xem. Email gửi sau khi commit; SMTP lỗi không đảo ngược quyết định, kết quả vẫn có trong trang hồ sơ của người dùng, thông báo trạng thái gửi mail rõ ràng.

Quyền thay đổi sẽ thu hồi phiên cũ; người dùng đăng nhập lại. Sân mới/chuyển giao bắt đầu inactive và booking_open=0. Chủ sân vào Quản lý khung giờ tạo lịch tương lai rồi sửa sân chọn Hoạt động để mở nhận đặt. Khi không đủ điều kiện, API từ chối mở. Sân chưa mở không xuất hiện trong danh sách công khai.

Chủ sân chỉ sửa sân được giao. Đổi tên/địa chỉ/phường/tọa độ tạo hồ sơ update; thông tin cũ giữ nguyên tới khi Admin duyệt. Khi được yêu cầu bổ sung, vào chỉnh sửa sân để gửi lại. Các thông tin vận hành như giá/giờ, ảnh và tiện ích cập nhật trực tiếp. Xóa sân bị chặn; nút tạm ngừng chỉ dừng nhận đơn mới, không xóa giao dịch.

## Admin quản lý tài khoản

`admin-users.php`: tìm theo tên/email/điện thoại, lọc vai trò, trạng thái tài khoản và xác minh email, phân trang 20 dòng. Chi tiết gồm sân đang giao, 30 đơn gần nhất, 50 thao tác quản trị gần nhất. API không trả hash mật khẩu hoặc token.

- Khóa: nhập lý do và mật khẩu Admin; thu hồi phiên, chặn đăng nhập và yêu cầu mới. Các sân của người đó dừng nhận đơn mới. Giữ đơn và số tiền để giải quyết; không tự hủy hay hoàn tiền.
- Mở khóa: tài khoản được dùng lại, các sân không tự mở lại.
- Không được tự khóa Admin đang sử dụng hoặc khóa Admin hoạt động cuối cùng. Không có chức năng cấp Admin qua giao diện/API.
- Sửa tên/điện thoại: phải xác thực lại và có lý do, ghi nhật ký (không lưu mật khẩu vào nhật ký).
- Yêu cầu đổi email: gửi liên kết tới hộp thư mới, có hiệu lực 30 phút. Email đăng nhập cũ chỉ đổi sau khi xác minh; thu hồi các phiên cũ và token khi xác nhận. Không cho sửa trực tiếp email hoặc dấu xác minh. Nếu SMTP lỗi thì giữ nguyên yêu cầu/dữ liệu trước đó.
- Gửi đặt lại mật khẩu: gửi liên kết một lần cho tài khoản hoạt động, đã xác minh; Admin không biết và không đặt mật khẩu thay người dùng.
- Thu hồi quyền chủ sân: chỉ khi không còn sở hữu sân và không có đơn cá nhân pending/confirmed.
- Tài khoản đã xóa không được sửa, mở khóa hoặc phục hồi từ giao diện. Người dùng tiếp tục dùng luồng tự yêu cầu xóa đã có; hồ sơ chủ sân được ẩn danh khi xóa, lịch sử giao dịch được giữ.

## Kiểm thử nhóm

Dùng DB riêng và email/tài khoản thử. Không khóa tài khoản của nhóm hoặc chuyển giao sân thật để thử.

1. Đăng ký ý định owner, xác minh, kiểm tra vẫn customer và không vào API quản trị.
2. Lưu nháp sân mới; tài khoản khác không đọc/sửa được hồ sơ bằng id. Sửa và gửi; chưa duyệt không có sân công khai.
3. Admin yêu cầu bổ sung, người dùng sửa/gửi lại, Admin duyệt; role đổi owner, phải đăng nhập lại và chỉ thấy sân mình.
4. Chưa có lịch tương lai thì không mở được; tạo lịch, mở nhận đặt, kiểm tra danh sách/chi tiết/bản đồ và đặt sân.
5. Thử hồ sơ trùng, phê duyệt hai lần, sửa phiên bản cũ, nhận quản lý sân có đơn còn hoạt động; đều phải cảnh báo/chặn phù hợp.
6. Chuyển giao sân thử không có đơn/giữ chỗ hoạt động; chủ cũ mất quyền, chủ mới quản lý, đơn cũ không bị xóa.
7. Khóa owner đang đăng nhập; phiên cũ, giữ chỗ và đặt sân mới phải bị chặn. Mở khóa không tự bật sân.
8. Kiểm tra sửa liên hệ, yêu cầu đổi email (chưa bấm thư thì chưa đổi), link đã dùng/hết hạn; đặt lại mật khẩu; nhật ký không có mật khẩu/token.
9. Owner/khách vào admin-users.php, admin-owner-requests.php và hai API tương ứng phải bị từ chối; không được cấp role=admin bằng request sửa tài khoản.
10. Kiểm tra xóa tài khoản thử, voucher và đặt sân đang có để tránh hồi quy. Email thật còn phụ thuộc SMTP/hosting, không bảo đảm vào Inbox.

## Triển khai và giới hạn

Migration không ép các owner/sân hiện có đăng ký lại, không mở hoặc đóng sân hàng loạt. Chỉ thao tác quản trị được xác nhận mới thay đổi trạng thái. Hệ thống có ba vai trò customer/owner/admin, chưa tách nhân viên riêng. Vùng ghim theo cấu hình dự án TP.HCM. Chưa có tải tệp chứng minh hoặc hàng đợi email nền; gửi mail đồng bộ sau commit, lỗi được báo và thông báo vẫn lưu. Không tự sửa dữ liệu OpenStreetMap công khai.
