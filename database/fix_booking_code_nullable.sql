-- database/fix_booking_code_nullable.sql
-- DÀNH CHO AI ĐÃ CHẠY BẢN migration_week2.sql CŨ (booking_code NOT NULL, không có giá trị mặc định).
-- Bản cũ làm api/bookings.php lỗi:  "Field 'booking_code' doesn't have a default value".
-- Chạy file này 1 lần để cho phép NULL. An toàn: không mất dữ liệu.

USE badminton_booking;

ALTER TABLE bookings MODIFY booking_code VARCHAR(30) NULL;
