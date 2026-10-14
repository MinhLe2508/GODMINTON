-- database/migration_week2.sql
-- Tuần 2 — chạy 1 LẦN sau schema.sql + seed.sql (không bắt buộc cho luồng đặt sân hiện tại).
-- Bổ sung 3 cột phụ cho bảng bookings theo hợp đồng API. Cả 3 cột đều cho phép NULL
-- vì api/bookings.php (Minh) hiện chưa ghi các cột này — mã đơn được sinh từ id: GDM-yyyymmdd-0001.
-- Nếu báo "Duplicate column name" nghĩa là đã chạy rồi → bỏ qua, rồi chạy fix_booking_code_nullable.sql.

USE badminton_booking;

ALTER TABLE bookings
  ADD COLUMN booking_code    VARCHAR(30)  NULL AFTER court_id,
  ADD COLUMN payment_method  VARCHAR(30)  NULL AFTER deposit_amount,
  ADD COLUMN notes           TEXT         NULL AFTER payment_method,
  ADD UNIQUE KEY uq_booking_code (booking_code);
