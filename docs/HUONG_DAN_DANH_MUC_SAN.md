# Danh mục sân — nguồn dữ liệu hiện tại

Danh mục đang chạy nằm trong MySQL, được chỉnh tại **Kênh chủ sân → Quản lý sân**. Các tiện ích, ảnh, địa chỉ và tọa độ phải lưu qua API quản lý sân.

Bộ cài mới dùng `database/schema.sql` rồi `database/seed.sql`; hướng dẫn đầy đủ tại [database/README.md](database/README.md).

Seed mới được xuất từ DB hiện tại, giữ ID và trạng thái các sân để tương thích với `config/court-replacements.php`. Các bản sân trùng cũ vẫn ở trạng thái ngừng hoạt động. Học viện cầu lông TPro giữ địa chỉ 1k Ngô Văn Sở và tọa độ đã đính chính.

Các JSON danh mục, importer nhúng dữ liệu, seed gợi ý và script đính chính một lần đã được chuyển ra bản lưu ngoài website. Không chạy lại các script cũ vì có thể khôi phục địa chỉ hoặc trạng thái sân đã được sửa.

Sau khi nhóm đã cập nhật dữ liệu và muốn tạo lại bộ cài, chạy PHP CLI `database/export_installation.php`. Script chỉ đọc DB và ghi `schema.sql`/`seed.sql`; không thay đổi dữ liệu đang chạy. Seed không chứa tài khoản hay giao dịch thật.
