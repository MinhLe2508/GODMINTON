-- database/seed_slots.sql
-- Sinh khung giờ mẫu để test giữ chỗ: 7 sân × 8 ngày (hôm nay → +7) × các ô 30 phút từ 05:30 đến 22:00.
-- Chạy SAU seed.sql. Có thể chạy lại nhiều lần để "nạp thêm ngày mới": INSERT IGNORE bỏ qua slot đã có
-- (khoá duy nhất uq_slot_per_court), không ghi đè slot đang holding/booked.
-- Yêu cầu MySQL 8.0+ hoặc MariaDB 10.2+ (dùng WITH RECURSIVE).

USE badminton_booking;
SET time_zone = '+07:00'; -- CURDATE() tính theo giờ Việt Nam, khớp config/db.php

INSERT IGNORE INTO time_slots (court_id, slot_date, start_time, end_time, status)
SELECT c.id,
       DATE_ADD(CURDATE(), INTERVAL d.n DAY),
       SEC_TO_TIME(t.s),
       SEC_TO_TIME(t.s + 1800),
       'available'
FROM courts c
JOIN (WITH RECURSIVE r(n) AS (SELECT 0 UNION ALL SELECT n + 1 FROM r WHERE n < 7) SELECT n FROM r) d
JOIN (WITH RECURSIVE r(s) AS (SELECT 5*3600 + 1800 UNION ALL SELECT s + 1800 FROM r WHERE s < 21*3600 + 1800) SELECT s FROM r) t;
