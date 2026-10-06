-- =====================================================================
-- CƠ SỞ DỮ LIỆU: Website Đặt Sân Cầu Lông Trực Tuyến
-- Phạm vi: khu vực đô thị lõi cũ TP.HCM (các phường trung tâm)
-- Engine: MySQL 8.0+ (InnoDB, hỗ trợ FOREIGN KEY)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS badminton_booking
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE badminton_booking;

-- ---------------------------------------------------------------------
-- 1. USERS — tài khoản khách hàng / chủ sân / quản trị viên
-- ---------------------------------------------------------------------
CREATE TABLE users (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(150)    NOT NULL,
    email           VARCHAR(150)    NOT NULL UNIQUE,
    phone           VARCHAR(20)     NULL,
    password_hash   VARCHAR(255)    NOT NULL,           -- luôn lưu bcrypt, không lưu plaintext
    role            ENUM('customer', 'owner', 'admin') NOT NULL DEFAULT 'customer',
    avatar_url      VARCHAR(255)    NULL,
    is_verified     TINYINT(1)      NOT NULL DEFAULT 0, -- đã kích hoạt qua email hay chưa
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 2. WARDS — danh mục phường thuộc khu vực đô thị lõi cũ
--    (không dùng "quận" vì cấp quận/huyện đã bị bãi bỏ từ 1/7/2025;
--     old_area chỉ để hiển thị cho người dùng quen tên cũ)
--    Đây là DANH SÁCH VÍ DỤ — nhóm cần bổ sung đầy đủ các phường
--    thuộc khu vực lõi mà đồ án chọn làm phạm vi hoạt động.
-- ---------------------------------------------------------------------
CREATE TABLE wards (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(100)    NOT NULL,           -- tên phường hiện hành, vd "Sài Gòn"
    old_area_label  VARCHAR(100)    NULL                -- tên gợi nhớ khu vực cũ, vd "Quận 1 (cũ)"
) ENGINE=InnoDB;

INSERT INTO wards (name, old_area_label) VALUES
    ('Sài Gòn',      'Quận 1 (cũ)'),
    ('Chợ Lớn',      'Quận 5 (cũ)'),
    ('Bình Tây',     'Quận 6 (cũ)'),
    ('Bình Tiên',    'Quận 6 (cũ)'),
    ('Bình Phú',     'Quận 6 (cũ)'),
    ('Phú Lâm',      'Quận 6 (cũ)'),
    ('Minh Phụng',   'Quận 11 (cũ)');
    -- TODO: bổ sung đầy đủ các phường còn lại trong phạm vi đô án chọn

-- ---------------------------------------------------------------------
-- 3. COURTS — sân cầu lông
-- ---------------------------------------------------------------------
CREATE TABLE courts (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id        INT UNSIGNED    NOT NULL,
    ward_id         INT UNSIGNED    NOT NULL,
    name            VARCHAR(150)    NOT NULL,
    address         VARCHAR(255)    NOT NULL,
    latitude        DECIMAL(10,7)   NOT NULL,           -- ví dụ 10.7769000
    longitude       DECIMAL(10,7)   NOT NULL,           -- ví dụ 106.7009000
    price_per_hour  DECIMAL(10,0)   NOT NULL,
    description     TEXT            NULL,
    status          ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_courts_owner
        FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT,
    CONSTRAINT fk_courts_ward
        FOREIGN KEY (ward_id)  REFERENCES wards(id)  ON DELETE RESTRICT,

    INDEX idx_courts_ward (ward_id),
    INDEX idx_courts_location (latitude, longitude)     -- đủ dùng ở quy mô 1 khu vực,
                                                         -- không cần kiểu POINT/spatial index
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 4. COURT_IMAGES — ảnh sân (1 sân có nhiều ảnh)
-- ---------------------------------------------------------------------
CREATE TABLE court_images (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    court_id        INT UNSIGNED    NOT NULL,
    image_url       VARCHAR(255)    NOT NULL,
    sort_order      INT UNSIGNED    NOT NULL DEFAULT 0,

    CONSTRAINT fk_images_court
        FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE,

    INDEX idx_images_court (court_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 5. AMENITIES — danh mục tiện ích (khớp bộ lọc trên courts.html)
-- ---------------------------------------------------------------------
CREATE TABLE amenities (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code            VARCHAR(30)     NOT NULL UNIQUE,    -- aircon, parking, shower, rental, cafe, locker, wifi, indoor
    label           VARCHAR(50)     NOT NULL            -- Điều hòa, Bãi đỗ xe, Phòng tắm, ...
) ENGINE=InnoDB;

INSERT INTO amenities (code, label) VALUES
    ('aircon',  'Điều hòa'),
    ('parking', 'Bãi đỗ xe'),
    ('shower',  'Phòng tắm'),
    ('rental',  'Cho thuê vợt'),
    ('cafe',    'Canteen/Cafe'),
    ('locker',  'Tủ khóa'),
    ('wifi',    'Wi-Fi'),
    ('indoor',  'Sân trong nhà');

-- ---------------------------------------------------------------------
-- 6. COURT_AMENITIES — bảng trung gian N-N giữa courts và amenities
-- ---------------------------------------------------------------------
CREATE TABLE court_amenities (
    court_id        INT UNSIGNED    NOT NULL,
    amenity_id      INT UNSIGNED    NOT NULL,

    PRIMARY KEY (court_id, amenity_id),
    CONSTRAINT fk_ca_court
        FOREIGN KEY (court_id)   REFERENCES courts(id)    ON DELETE CASCADE,
    CONSTRAINT fk_ca_amenity
        FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 7. TIME_SLOTS — khung giờ theo từng sân, từng ngày
--    (status 'holding' phục vụ cơ chế giữ chỗ 5 phút trước khi thanh toán)
-- ---------------------------------------------------------------------
CREATE TABLE time_slots (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    court_id        INT UNSIGNED    NOT NULL,
    slot_date       DATE            NOT NULL,
    start_time      TIME            NOT NULL,
    end_time        TIME            NOT NULL,
    status          ENUM('available', 'holding', 'booked') NOT NULL DEFAULT 'available',
    held_by         INT UNSIGNED    NULL,               -- user đang giữ chỗ (nếu status='holding')
    hold_expires_at DATETIME        NULL,               -- hết hạn giữ chỗ (now() + 5 phút)

    CONSTRAINT fk_slots_court
        FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE CASCADE,
    CONSTRAINT fk_slots_held_by
        FOREIGN KEY (held_by)  REFERENCES users(id)  ON DELETE SET NULL,

    UNIQUE KEY uq_slot_per_court (court_id, slot_date, start_time),
    INDEX idx_slots_lookup (court_id, slot_date, status)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 8. BOOKINGS — đơn đặt sân (đã xác nhận/thanh toán)
-- ---------------------------------------------------------------------
CREATE TABLE bookings (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED    NOT NULL,
    court_id        INT UNSIGNED    NOT NULL,
    total_price     DECIMAL(12,0)   NOT NULL,
    deposit_amount  DECIMAL(12,0)   NOT NULL DEFAULT 0,
    status          ENUM('pending', 'confirmed', 'cancelled', 'completed')
                    NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_bookings_user
        FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE RESTRICT,
    CONSTRAINT fk_bookings_court
        FOREIGN KEY (court_id) REFERENCES courts(id) ON DELETE RESTRICT,

    INDEX idx_bookings_user (user_id),
    INDEX idx_bookings_court (court_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- 9. BOOKING_SLOTS — 1 đơn đặt có thể gồm nhiều khung giờ liên tiếp
-- ---------------------------------------------------------------------
CREATE TABLE booking_slots (
    booking_id      INT UNSIGNED    NOT NULL,
    slot_id         INT UNSIGNED    NOT NULL,

    PRIMARY KEY (booking_id, slot_id),
    CONSTRAINT fk_bs_booking
        FOREIGN KEY (booking_id) REFERENCES bookings(id)   ON DELETE CASCADE,
    CONSTRAINT fk_bs_slot
        FOREIGN KEY (slot_id)    REFERENCES time_slots(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================================================================
-- Ghi chú triển khai:
-- 1. Ràng buộc "giữ chỗ 5 phút" (held_by / hold_expires_at) nên được xử lý
--    bằng transaction PDO có SELECT ... FOR UPDATE khi cập nhật time_slots,
--    để tránh race condition khi 2 người bấm giữ cùng 1 slot cùng lúc.
-- 2. latitude/longitude dùng DECIMAL thường là đủ ở quy mô 1 khu vực đô
--    thị lõi; chỉ cần cân nhắc kiểu POINT + SPATIAL INDEX nếu sau này mở
--    rộng ra hàng nghìn sân trải khắp TP.HCM sau sáp nhập.
-- 3. Khi mở rộng phạm vi, chỉ cần thêm cột "region" vào bảng wards
--    (vd: 'hcm_loi' / 'binh_duong_cu' / 'ba_ria_vung_tau_cu') làm tầng
--    lọc thô phía trên ward, không cần đổi cấu trúc các bảng còn lại.
-- =====================================================================
