-- database/seed.sql
-- Dữ liệu mẫu để test — chạy SAU khi đã import schema.sql.
-- Giữ đúng 3 tài khoản demo đã dùng trong bản giao diện tĩnh (xem BAOCAOWEB.pdf mục 7.3)
-- để không phải đổi lại thông tin demo khi quay video.

USE badminton_booking;

-- Mật khẩu mẫu bên dưới là bcrypt của: Khach@123 / Admin@123 / Owner@123
-- (tạo lại bằng password_hash('Khach@123', PASSWORD_BCRYPT) nếu muốn đổi)
INSERT INTO users (name, email, phone, password_hash, role, is_verified) VALUES
    ('Nguyễn Văn An',   'khach1@gmail.com',       '0912345678', '$2y$10$REPLACE_WITH_REAL_BCRYPT_HASH', 'customer', 1),
    ('Quản Trị Viên',   'admin@godminton.vn',     '0900000000', '$2y$10$REPLACE_WITH_REAL_BCRYPT_HASH', 'admin',    1),
    ('Chủ Sân Quận 1',  'owner.q1@godminton.vn',  '0911111111', '$2y$10$REPLACE_WITH_REAL_BCRYPT_HASH', 'owner',    1);

-- Sân mẫu (owner_id = 3 ở trên, ward_id = 1 "Sài Gòn" có sẵn từ schema.sql)
INSERT INTO courts (owner_id, ward_id, name, address, latitude, longitude, price_per_hour, description, status) VALUES
    (3, 1, 'Cầu Lông Quận 1 - Lê Thánh Tôn Sport', '112 Lê Thánh Tôn, Phường Bến Nghé, Quận 1, TP.HCM',
     10.7769000, 106.7009000, 100000, 'Tổ hợp sân cầu lông đạt chuẩn BWF, hệ thống chiếu sáng LED.', 'active');

-- TODO (Tuần 1-2): bổ sung thêm sân + time_slots mẫu tương ứng dữ liệu đã có trong
-- assets/js/mock-data.js để giữ nguyên kịch bản demo (giao diện -> dữ liệu thật).
