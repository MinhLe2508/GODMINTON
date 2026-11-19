-- database/reset_reviews.sql
-- DỌN SẠCH để làm lại chức năng đánh giá từ đầu. Chọn database badminton_booking trước khi import.
-- Sau file này, import tiếp database/seed_reviews_demo.sql (tự tạo lại bảng `reviews` + dữ liệu mẫu).
-- CẢNH BÁO: xóa TOÀN BỘ đánh giá hiện có (cả đánh giá thật nếu đã có). Không đụng bảng nào khác.

-- 1. Bảng cũ từ phiên bản trước (nếu có)
DROP TABLE IF EXISTS `court_reviews`;
-- 2. Bảng đánh giá hiện tại (nếu có)
DROP TABLE IF EXISTS `reviews`;
-- 3. Tài khoản demo cũ (nếu có)
DELETE FROM `users` WHERE `email` LIKE 'demo.%@demo.godminton.local';
