-- database/seed.sql
-- Dữ liệu mẫu để test — chạy SAU khi đã import schema.sql.
-- Giữ đúng 3 tài khoản demo đã dùng trong bản giao diện tĩnh (xem BAOCAOWEB.pdf mục 7.3)
-- để không phải đổi lại thông tin demo khi quay video.

USE badminton_booking;

-- =====================================================================
-- THÊM WARDS (phường) — bổ sung cho đủ khu vực sân trong mock-data
-- Schema đã INSERT 7 phường (id 1-7), ta thêm các phường còn thiếu
-- =====================================================================
INSERT INTO wards (name, old_area_label) VALUES
    ('Bến Nghé',    'Quận 1 (cũ)'),        -- id=8   → cho sân Quận 1
    ('Phường 26',   'Bình Thạnh (cũ)'),     -- id=9   → Bình Thạnh
    ('Phường 4',    'Tân Bình (cũ)'),       -- id=10  → Tân Bình
    ('Phường 3',    'Gò Vấp (cũ)'),         -- id=11  → Gò Vấp
    ('Tân Phong',   'Quận 7 (cũ)'),         -- id=12  → Quận 7 / Phú Mỹ Hưng
    ('Bình Thọ',    'TP. Thủ Đức (cũ)'),    -- id=13  → Thủ Đức
    ('Bình Chuẩn',  'Thuận An, Bình Dương');-- id=14  → Bình Dương

-- =====================================================================
-- USERS — 3 tài khoản demo
-- Mật khẩu mẫu bên dưới là bcrypt của: Khach@123 / Admin@123 / Owner@123
-- =====================================================================
INSERT INTO users (name, email, phone, password_hash, role, is_verified) VALUES
    ('Nguyễn Văn An',   'khach1@gmail.com',       '0912345678', '$2y$10$3hgwe0BT37Nzj6hN/b7vDeM0xOWnSwZJGd6LAgJiu1CT60io.1v5u', 'customer', 1),
    ('Quản Trị Viên',   'admin@godminton.vn',     '0900000000', '$2y$10$CFJ7WgeODP.0LwIVtVu1YOYSyChd86T7Qj/Em5TVO5Zx0Kvbsu5D2', 'admin',    1),
    ('Chủ Sân Quận 1',  'owner.q1@godminton.vn',  '0911111111', '$2y$10$Z1jvuTrsmcDW9OHina1Oq.MtiPF7z1UJN7rNMWik6iW/astsatoSC', 'owner',    1);

-- =====================================================================
-- COURTS — 7 sân khớp với mock-data.js
-- owner_id = 3 (Chủ Sân Quận 1) cho tất cả (đơn giản)
-- =====================================================================
INSERT INTO courts (id, owner_id, ward_id, name, address, latitude, longitude, price_per_hour, description, status) VALUES
    (1, 3, 8,  'Cầu Lông Quận 1 – Lê Thánh Tôn Sport',
     '112 Lê Thánh Tôn, Phường Bến Nghé, Quận 1, TP.HCM',
     10.7769000, 106.7009000, 100000,
     'Tổ hợp sân cầu lông hiện đại ngay trung tâm Quận 1, 6 sân thảm tiêu chuẩn BWF, hệ thống chiếu sáng LED chống lóa. Phù hợp cho người đi làm muốn luyện tập sau giờ hành chính.', 'active'),

    (2, 3, 9,  'CLB Cầu Lông Bình Thạnh – Arena Xô Viết',
     '225 Xô Viết Nghệ Tĩnh, Phường 26, Bình Thạnh, TP.HCM',
     10.8020000, 106.7120000, 80000,
     'Sân cầu lông quy mô lớn với 8 sân thảm Yonex cao cấp, dịch vụ căng vợt điện tử lấy ngay trong 30 phút, có phòng VIP riêng tư cho nhóm doanh nghiệp.', 'active'),

    (3, 3, 10, 'Trung Tâm Thể Thao Tân Bình – TBSport',
     '78 Hoàng Văn Thụ, Phường 4, Tân Bình, TP.HCM',
     10.8010000, 106.6650000, 75000,
     'Nằm gần sân bay Tân Sơn Nhất, 10 sân thảm tiêu chuẩn thi đấu, phù hợp cho câu lạc bộ và giải phong trào. Có khu vực ngồi chờ rộng rãi và quầy đồ uống thể thao.', 'active'),

    (4, 3, 11, 'Sân Cầu Lông Gò Vấp – GV Arena',
     '350 Nguyễn Kiệm, Phường 3, Gò Vấp, TP.HCM',
     10.8230000, 106.6830000, 70000,
     'Sân cầu lông gia đình thân thiện, 5 sân thảm Victor cao su tự nhiên, thích hợp cho mọi lứa tuổi. Có khu vực vui chơi trẻ em và phòng nghỉ ngơi sau khi chơi.', 'active'),

    (5, 3, 12, 'Cầu Lông Quận 7 – Phú Mỹ Hưng Sport',
     'Block B2, Khu đô thị Phú Mỹ Hưng, Quận 7, TP.HCM',
     10.7290000, 106.7180000, 120000,
     'Sân cầu lông cao cấp trong khu đô thị Phú Mỹ Hưng, 4 sân thảm Li-Ning nhập khẩu, có đội ngũ HLV chuyên nghiệp từ đội tuyển quốc gia. Dịch vụ 5 sao, phòng tắm xông hơi sau tập.', 'active'),

    (6, 3, 13, 'CLB Thể Thao Thủ Đức – Thủ Đức Arena',
     '45 Võ Văn Ngân, Phường Bình Thọ, TP. Thủ Đức, TP.HCM',
     10.8510000, 106.7720000, 65000,
     'Nằm gần ĐH Bách Khoa và ĐH Quốc Gia, là địa điểm yêu thích của sinh viên và giảng viên. 7 sân thảm giá sinh viên, mở cửa sớm từ 5 giờ sáng phục vụ buổi tập sáng.', 'active'),

    (7, 3, 14, 'Sân Cầu Lông Bình Dương – BD Sport Center',
     '234 Đại lộ Bình Dương, Phường Bình Chuẩn, Thuận An, Bình Dương',
     11.0100000, 106.6530000, 60000,
     'Sân cầu lông lớn nhất Bình Dương với 12 sân thảm tiêu chuẩn, sân gần khu công nghiệp VSIP tiện cho công nhân viên sau giờ làm. Có phòng gym và bể bơi đi kèm.', 'active');

-- =====================================================================
-- COURT_IMAGES — ảnh cho từng sân
-- =====================================================================
INSERT INTO court_images (court_id, image_url, sort_order) VALUES
    (1, 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop', 0),
    (2, 'https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop', 0),
    (3, 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop', 0),
    (4, 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop', 0),
    (5, 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&auto=format&fit=crop', 0),
    (6, 'https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop', 0),
    (7, 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop', 0);

-- =====================================================================
-- COURT_AMENITIES — gắn tiện ích cho từng sân (khớp mock-data.js)
-- Amenity IDs (từ schema.sql):
--   1=aircon, 2=parking, 3=shower, 4=rental, 5=cafe, 6=locker, 7=wifi, 8=indoor
-- =====================================================================
-- Court 1: wifi, parking, shower, aircon, cafe, rental
INSERT INTO court_amenities (court_id, amenity_id) VALUES
    (1, 7), (1, 2), (1, 3), (1, 1), (1, 5), (1, 4);

-- Court 2: wifi, parking, shower, aircon, rental, locker
INSERT INTO court_amenities (court_id, amenity_id) VALUES
    (2, 7), (2, 2), (2, 3), (2, 1), (2, 4), (2, 6);

-- Court 3: wifi, parking, shower, cafe, rental
INSERT INTO court_amenities (court_id, amenity_id) VALUES
    (3, 7), (3, 2), (3, 3), (3, 5), (3, 4);

-- Court 4: wifi, parking, shower, aircon, cafe
INSERT INTO court_amenities (court_id, amenity_id) VALUES
    (4, 7), (4, 2), (4, 3), (4, 1), (4, 5);

-- Court 5: wifi, parking, shower, aircon, cafe, rental, locker
INSERT INTO court_amenities (court_id, amenity_id) VALUES
    (5, 7), (5, 2), (5, 3), (5, 1), (5, 5), (5, 4), (5, 6);

-- Court 6: wifi, parking, shower, rental, cafe
INSERT INTO court_amenities (court_id, amenity_id) VALUES
    (6, 7), (6, 2), (6, 3), (6, 4), (6, 5);

-- Court 7: wifi, parking, shower, aircon, cafe, rental, locker
INSERT INTO court_amenities (court_id, amenity_id) VALUES
    (7, 7), (7, 2), (7, 3), (7, 1), (7, 5), (7, 4), (7, 6);
