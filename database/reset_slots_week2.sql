-- database/reset_slots_week2.sql
-- DÀNH CHO AI ĐÃ CHẠY seed_slots.sql CŨ (đã bị xoá khỏi repo).
-- File cũ chèn khung giờ chỉ cho 'Thảm A1' → api/slots.php (Minh) thấy ngày đã có slot nên KHÔNG tự sinh
-- đủ 4 thảm (A1–A4, 05:30–23:30) + ô demo đã đặt → lịch hiển thị thiếu.
--
-- ⚠ XOÁ TOÀN BỘ khung giờ VÀ các đơn đặt sân đã tạo khi test (booking_slots, bookings, time_slots).
-- Chỉ chạy trên DB test của bạn. Sau đó mở lại trang booking.php: slots.php tự sinh lại slot đúng.

USE badminton_booking;

DELETE FROM booking_slots;
DELETE FROM bookings;
DELETE FROM time_slots;
