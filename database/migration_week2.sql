-- database/migration_week2.sql
-- Tuần 2 — Booking Engine. Chạy 1 LẦN sau schema.sql + seed.sql (cả Huy và Minh đều chạy).
-- Theo hợp đồng API (booking_api_contract.md, mục 1): bổ sung 3 cột cho bảng bookings.
-- Nếu chạy lần 2 sẽ báo "Duplicate column" — bỏ qua, không sao.

USE badminton_booking;

ALTER TABLE bookings
  ADD COLUMN booking_code    VARCHAR(30)  NOT NULL AFTER court_id,
  ADD COLUMN payment_method  VARCHAR(30)  NULL     AFTER deposit_amount,
  ADD COLUMN notes           TEXT         NULL     AFTER payment_method,
  ADD UNIQUE KEY uq_booking_code (booking_code);
