-- database/migration_admin_management.sql
-- Tuần 3 (Huy — Task 13 & 14): Cập nhật CSDL cho phân hệ Quản trị sân & Khung giờ
-- Chỉ dùng cho DB cũ chưa có các cột quản lý sân (các API api/admin/courts.php và api/admin/slots.php
-- cũng tự động kiểm tra và bổ sung các cột này nếu bạn chưa kịp chạy file SQL thủ công).

-- Select the target database before running this migration.

-- 1. Mở rộng ENUM status của bảng time_slots thêm trạng thái 'blocked' (Ngừng nhận đặt)
--    và thêm cột price (giá riêng cho khung giờ nếu chủ sân cấu hình khi tạo hàng loạt)
ALTER TABLE time_slots
    MODIFY COLUMN status ENUM('available', 'holding', 'booked', 'blocked') NOT NULL DEFAULT 'available',
    ADD COLUMN IF NOT EXISTS price DECIMAL(10,0) NULL AFTER end_time;

-- 2. Bổ sung số sân con và giờ mở/đóng cửa cho bảng courts để lưu đúng biểu mẫu Quản lý sân
ALTER TABLE courts
    ADD COLUMN IF NOT EXISTS total_sub_courts INT UNSIGNED NOT NULL DEFAULT 6 AFTER price_per_hour,
    ADD COLUMN IF NOT EXISTS opening_time     TIME         NOT NULL DEFAULT '06:00:00' AFTER total_sub_courts,
    ADD COLUMN IF NOT EXISTS closing_time     TIME         NOT NULL DEFAULT '22:00:00' AFTER opening_time;

