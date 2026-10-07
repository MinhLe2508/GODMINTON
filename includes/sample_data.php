<?php
/**
 * includes/sample_data.php
 * Tập hợp dữ liệu mẫu dùng cho các view PHP trong Tuần 1.
 * // DỮ LIỆU MẪU - thay bằng truy vấn DB ở tuần 2 (PDO từ $pdo qua config/db.php)
 * 
 * Các trường dữ liệu bám sát theo database/schema.sql do Minh thiết kế:
 * - users: id, name, email, phone, role
 * - wards: id, name, old_area_label
 * - courts: id, owner_id, ward_id, name, address, latitude, longitude, price_per_hour, description, status, amenities, images
 * - bookings: id, user_id, court_id, total_price, deposit_amount, status, created_at
 * - time_slots: id, court_id, slot_date, start_time, end_time, status
 */

// 1. Danh sách Phường / Khu vực đô thị mẫu (khớp bảng wards)
$SAMPLE_WARDS = [
    1 => ['id' => 1, 'name' => 'Sài Gòn',    'old_area_label' => 'Quận 1 (cũ)'],
    2 => ['id' => 2, 'name' => 'Chợ Lớn',    'old_area_label' => 'Quận 5 (cũ)'],
    3 => ['id' => 3, 'name' => 'Bình Tây',   'old_area_label' => 'Quận 6 (cũ)'],
    4 => ['id' => 4, 'name' => 'Bình Tiên',  'old_area_label' => 'Quận 6 (cũ)'],
    5 => ['id' => 5, 'name' => 'Bình Phú',   'old_area_label' => 'Quận 6 (cũ)'],
    6 => ['id' => 6, 'name' => 'Phú Lâm',    'old_area_label' => 'Quận 6 (cũ)'],
    7 => ['id' => 7, 'name' => 'Minh Phụng', 'old_area_label' => 'Quận 11 (cũ)'],
];

// 2. Danh mục tiện ích (khớp bảng amenities)
$SAMPLE_AMENITIES = [
    'aircon'  => 'Điều hòa',
    'parking' => 'Bãi đỗ xe',
    'shower'  => 'Phòng tắm',
    'rental'  => 'Cho thuê vợt',
    'cafe'    => 'Canteen/Cafe',
    'locker'  => 'Tủ khóa',
    'wifi'    => 'Wi-Fi',
    'indoor'  => 'Sân trong nhà'
];

// 3. Danh sách Sân cầu lông mẫu (khớp bảng courts + court_images + court_amenities)
$SAMPLE_COURTS = [
    [
        'id'             => 1,
        'owner_id'       => 3,
        'ward_id'        => 1,
        'ward_name'      => 'Sài Gòn',
        'old_area_label' => 'Quận 1 (cũ)',
        'name'           => 'Cầu Lông Quận 1 - Lê Thánh Tôn Sport',
        'address'        => '112 Lê Thánh Tôn, Phường Bến Nghé, Quận 1 (cũ), TP.HCM',
        'latitude'       => 10.7769000,
        'longitude'      => 106.7009000,
        'price_per_hour' => 100000,
        'min_price'      => 100000,
        'description'    => 'Tổ hợp sân cầu lông đạt chuẩn BWF, hệ thống thảm PVC cao cấp và đèn LED chống lóa hiện đại.',
        'status'         => 'active',
        'sub_courts'     => ['Thảm A1', 'Thảm A2', 'Thảm A3', 'Thảm A4'],
        'amenities'      => ['aircon', 'parking', 'shower', 'rental', 'cafe', 'wifi', 'indoor'],
        'images'         => [
            'assets/img/gmdiscount.png',
            'assets/img/logo.jpg'
        ]
    ],
    [
        'id'             => 2,
        'owner_id'       => 3,
        'ward_id'        => 2,
        'ward_name'      => 'Chợ Lớn',
        'old_area_label' => 'Quận 5 (cũ)',
        'name'           => 'CLB Cầu Lông Chợ Lớn Sport Center',
        'address'        => '215 Hồng Bàng, Phường Chợ Lớn, Quận 5 (cũ), TP.HCM',
        'latitude'       => 10.7552000,
        'longitude'      => 106.6601000,
        'price_per_hour' => 90000,
        'min_price'      => 90000,
        'description'    => 'Sân cầu lông thoáng mát, trần cao 11m, thảm thi đấu chuẩn quốc gia, bãi giữ xe rộng rãi.',
        'status'         => 'active',
        'sub_courts'     => ['Sân 1', 'Sân 2', 'Sân 3', 'Sân 4', 'Sân 5', 'Sân 6'],
        'amenities'      => ['parking', 'shower', 'rental', 'locker', 'wifi', 'indoor'],
        'images'         => [
            'assets/img/gmdiscount.png'
        ]
    ],
    [
        'id'             => 3,
        'owner_id'       => 3,
        'ward_id'        => 5,
        'ward_name'      => 'Bình Phú',
        'old_area_label' => 'Quận 6 (cũ)',
        'name'           => 'Trung Tâm Thể Thao Bình Phú Arena',
        'address'        => '45 Đường số 24, Phường Bình Phú, Quận 6 (cũ), TP.HCM',
        'latitude'       => 10.7431000,
        'longitude'      => 106.6320000,
        'price_per_hour' => 80000,
        'min_price'      => 80000,
        'description'    => 'Không gian chơi thể thao chuyên nghiệp với cụm 8 sân thảm, có căn tin phục vụ nước uống thể thao.',
        'status'         => 'active',
        'sub_courts'     => ['Sân B1', 'Sân B2', 'Sân B3', 'Sân B4'],
        'amenities'      => ['aircon', 'parking', 'cafe', 'wifi', 'indoor'],
        'images'         => [
            'assets/img/gmdiscount.png'
        ]
    ],
    [
        'id'             => 4,
        'owner_id'       => 3,
        'ward_id'        => 7,
        'ward_name'      => 'Minh Phụng',
        'old_area_label' => 'Quận 11 (cũ)',
        'name'           => 'Sân Cầu Lông Minh Phụng Pro Club',
        'address'        => '88 Minh Phụng, Phường Minh Phụng, Quận 11 (cũ), TP.HCM',
        'latitude'       => 10.7615000,
        'longitude'      => 106.6450000,
        'price_per_hour' => 85000,
        'min_price'      => 85000,
        'description'    => 'Sân mới nâng cấp thảm thi đấu Yonex, đèn LED 400 lux, hỗ trợ thuê vợt và bán cầu.',
        'status'         => 'active',
        'sub_courts'     => ['Sân M1', 'Sân M2', 'Sân M3'],
        'amenities'      => ['parking', 'shower', 'rental', 'locker', 'wifi', 'indoor'],
        'images'         => [
            'assets/img/gmdiscount.png'
        ]
    ]
];

// 4. Danh sách Lịch sử Đặt Sân mẫu (khớp bảng bookings + booking_slots)
$SAMPLE_BOOKINGS = [
    [
        'id'             => 'BK-20261007-001',
        'user_id'        => 1,
        'court_id'       => 1,
        'court_name'     => 'Cầu Lông Quận 1 - Lê Thánh Tôn Sport',
        'sub_court'      => 'Thảm A1',
        'booking_date'   => '2026-10-08',
        'time_range'     => '18:00 - 19:30',
        'total_price'    => 150000,
        'deposit_amount' => 75000,
        'status'         => 'confirmed', // confirmed, pending, completed, cancelled
        'status_label'   => 'Đã xác nhận',
        'payment_method' => 'VNPay',
        'created_at'     => '2026-10-07 09:30:00'
    ],
    [
        'id'             => 'BK-20261005-014',
        'user_id'        => 1,
        'court_id'       => 2,
        'court_name'     => 'CLB Cầu Lông Chợ Lớn Sport Center',
        'sub_court'      => 'Sân 2',
        'booking_date'   => '2026-10-05',
        'time_range'     => '19:00 - 20:00',
        'total_price'    => 90000,
        'deposit_amount' => 45000,
        'status'         => 'completed',
        'status_label'   => 'Đã hoàn thành',
        'payment_method' => 'Chuyển khoản MB Bank',
        'created_at'     => '2026-10-04 14:15:00'
    ],
    [
        'id'             => 'BK-20260928-088',
        'user_id'        => 1,
        'court_id'       => 3,
        'court_name'     => 'Trung Tâm Thể Thao Bình Phú Arena',
        'sub_court'      => 'Sân B1',
        'booking_date'   => '2026-09-29',
        'time_range'     => '17:00 - 18:00',
        'total_price'    => 80000,
        'deposit_amount' => 40000,
        'status'         => 'cancelled',
        'status_label'   => 'Đã hủy',
        'payment_method' => 'MoMo',
        'created_at'     => '2026-09-28 11:00:00'
    ]
];

// 5. Thống kê nhanh kênh quản lý chủ sân (Admin/Owner dashboard)
$SAMPLE_ADMIN_STATS = [
    'total_courts'   => count($SAMPLE_COURTS),
    'active_courts'  => count(array_filter($SAMPLE_COURTS, fn($c) => $c['status'] === 'active')),
    'today_bookings' => 5,
    'total_bookings' => 28,
    'revenue'        => 4250000,
];

// Helper định dạng tiền tệ VNĐ
if (!function_exists('format_vnd')) {
    function format_vnd($amount) {
        return number_format($amount, 0, ',', '.') . ' đ';
    }
}

// Alias chữ thường để tương thích với tất cả view gọi $sample_*
$sample_bookings   = $SAMPLE_BOOKINGS;
$sample_courts     = $SAMPLE_COURTS;
$sample_wards      = $SAMPLE_WARDS;
$sample_amenities  = $SAMPLE_AMENITIES;
$sample_admin_stats = $SAMPLE_ADMIN_STATS;

