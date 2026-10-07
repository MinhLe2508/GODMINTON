-- database/seed.sql
-- Dữ liệu mẫu để test — chạy SAU khi đã import schema.sql.
-- Giữ đúng 3 tài khoản demo đã dùng trong bản giao diện tĩnh (xem BAOCAOWEB.pdf mục 7.3)
-- để không phải đổi lại thông tin demo khi quay video.
--
-- Tài khoản demo (mật khẩu đã băm bcrypt bằng password_hash(), KHÔNG phải plaintext):
--   khach1@gmail.com        / Khach@123   (customer, id=1)
--   admin@godminton.vn      / Admin@123   (admin,    id=2)
--   owner.q1@godminton.vn   / Owner@123   (owner,    id=3)

SET NAMES utf8mb4; -- đảm bảo tiếng Việt không bị lỗi mã hóa dù import bằng phpMyAdmin hay dòng lệnh
USE badminton_booking;

INSERT INTO users (name, email, phone, password_hash, role, is_verified) VALUES
    ('Nguyễn Văn An',   'khach1@gmail.com',       '0912345678', '$2y$10$9eNTIC8tlUkcPogEl1gh3ufKAvGBpoyX2psy3V2G3v7ZdkoEi/HxO', 'customer', 1),
    ('Quản Trị Viên',   'admin@godminton.vn',     '0900000000', '$2y$10$C85N.udc0MRb9iXNeE5rFu9plrbr7sn9I8eeaB3BIgsjIHCGDwIzC', 'admin',    1),
    ('Chủ Sân Quận 1',  'owner.q1@godminton.vn',  '0911111111', '$2y$10$ioeEFuydJMtEEE77jsAkKurjgTY3YEal7nLzCrXescM7uylgO3CPe', 'owner',    1);

-- 7 sân mẫu lấy từ assets/js/mock-data.js (INITIAL_COURTS), cùng owner_id = 3.
-- LƯU Ý: schema chỉ có 7 phường khu vực lõi, trong khi sân trong mock nằm rải nhiều quận
-- (Bình Thạnh, Tân Bình, Gò Vấp, Q7, Thủ Đức, Bình Dương) -> ward_id bên dưới chỉ là gán tạm 1..7
-- cho đủ khóa ngoại, TOẠ ĐỘ cũng là xấp xỉ. Tuần 3 Triết nạp sân thật bằng import_real_courts.php
-- sẽ thay thế; nhóm cần chốt lại phạm vi phường nếu muốn dữ liệu khớp địa chỉ.
INSERT INTO courts (owner_id, ward_id, name, address, latitude, longitude, price_per_hour, description, status) VALUES
    (3, 1, 'Cầu Lông Quận 1 - Lê Thánh Tôn Sport', '112 Lê Thánh Tôn, Phường Bến Nghé, Quận 1, TP.HCM',
     10.7769000, 106.7009000, 100000, 'Tổ hợp sân cầu lông hiện đại ngay trung tâm Quận 1, 6 sân thảm tiêu chuẩn BWF, hệ thống chiếu sáng LED chống lóa. Phù hợp cho người đi làm muốn luyện tập sau giờ hành chính.', 'active'),
    (3, 2, 'CLB Cầu Lông Bình Thạnh - Arena Xô Viết', '225 Xô Viết Nghệ Tĩnh, Phường 26, Bình Thạnh, TP.HCM',
     10.8100000, 106.7110000, 80000, 'Sân cầu lông quy mô lớn với 8 sân thảm Yonex cao cấp, dịch vụ căng vợt điện tử lấy ngay trong 30 phút, có phòng VIP riêng tư cho nhóm doanh nghiệp.', 'active'),
    (3, 3, 'Trung Tâm Thể Thao Tân Bình - TBSport', '78 Hoàng Văn Thụ, Phường 4, Tân Bình, TP.HCM',
     10.7990000, 106.6590000, 75000, 'Nằm gần sân bay Tân Sơn Nhất, 10 sân thảm tiêu chuẩn thi đấu, phù hợp cho câu lạc bộ và giải phong trào. Có khu vực ngồi chờ rộng rãi và quầy đồ uống thể thao.', 'active'),
    (3, 4, 'Sân Cầu Lông Gò Vấp - GV Arena', '350 Nguyễn Kiệm, Phường 3, Gò Vấp, TP.HCM',
     10.8200000, 106.6780000, 70000, 'Sân cầu lông gia đình thân thiện, 5 sân thảm Victor cao su tự nhiên, thích hợp cho mọi lứa tuổi. Có khu vực vui chơi trẻ em và phòng nghỉ ngơi sau khi chơi.', 'active'),
    (3, 5, 'Cầu Lông Quận 7 - Phú Mỹ Hưng Sport', 'Block B2, Khu đô thị Phú Mỹ Hưng, Quận 7, TP.HCM',
     10.7290000, 106.7190000, 120000, 'Sân cầu lông cao cấp trong khu đô thị Phú Mỹ Hưng, 4 sân thảm Li-Ning nhập khẩu, có đội ngũ HLV chuyên nghiệp từ đội tuyển quốc gia. Dịch vụ 5 sao, phòng tắm xông hơi sau tập.', 'active'),
    (3, 6, 'CLB Thể Thao Thủ Đức - ThủĐức Arena', '45 Võ Văn Ngân, Phường Bình Thọ, TP. Thủ Đức, TP.HCM',
     10.8500000, 106.7650000, 65000, 'Nằm gần ĐH Bách Khoa và ĐH Quốc Gia, là địa điểm yêu thích của sinh viên và giảng viên. 7 sân thảm giá sinh viên, mở cửa sớm từ 5 giờ sáng phục vụ buổi tập sáng.', 'active'),
    (3, 7, 'Sân Cầu Lông Bình Dương - BD Sport Center', '234 Đại lộ Bình Dương, Phường Bình Chuẩn, Thuận An, Bình Dương',
     10.9100000, 106.7000000, 60000, 'Sân cầu lông lớn nhất Bình Dương với 12 sân thảm tiêu chuẩn, sân gần khu công nghiệp VSIP tiện cho công nhân viên sau giờ làm. Có phòng gym và bể bơi đi kèm.', 'active');

-- Ảnh sân (1 ảnh/sân, lấy từ mock-data.js)
INSERT INTO court_images (court_id, image_url, sort_order) VALUES
    (1, 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop', 0),
    (2, 'https://images.unsplash.com/photo-1521537634581-0dced2fee2ef?w=800&auto=format&fit=crop', 0),
    (3, 'https://images.unsplash.com/photo-1613918108466-292b78a8ef95?w=800&auto=format&fit=crop', 0),
    (4, 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=800&auto=format&fit=crop', 0),
    (5, 'https://images.unsplash.com/photo-1529900748604-07564a03e7a6?w=800&auto=format&fit=crop', 0),
    (6, 'https://images.unsplash.com/photo-1526676037777-05a232554f77?w=800&auto=format&fit=crop', 0),
    (7, 'https://images.unsplash.com/photo-1551698618-1dfe5d97d256?w=800&auto=format&fit=crop', 0);

-- Tiện ích từng sân (mã 'canteen' trong mock được đổi thành 'cafe' cho khớp bảng amenities)
INSERT INTO court_amenities (court_id, amenity_id)
SELECT 1, id FROM amenities WHERE code IN ('wifi','parking','shower','aircon','cafe','rental')
UNION ALL
SELECT 2, id FROM amenities WHERE code IN ('wifi','parking','shower','aircon','rental','locker')
UNION ALL
SELECT 3, id FROM amenities WHERE code IN ('wifi','parking','shower','cafe','rental')
UNION ALL
SELECT 4, id FROM amenities WHERE code IN ('wifi','parking','shower','aircon','cafe')
UNION ALL
SELECT 5, id FROM amenities WHERE code IN ('wifi','parking','shower','aircon','cafe','rental','locker')
UNION ALL
SELECT 6, id FROM amenities WHERE code IN ('wifi','parking','shower','rental','cafe')
UNION ALL
SELECT 7, id FROM amenities WHERE code IN ('wifi','parking','shower','aircon','cafe','rental','locker');

-- TODO (Tuần 2): thêm time_slots mẫu (hoặc dùng admin-slots.php 'tạo khung giờ hàng loạt') để test giữ chỗ.
