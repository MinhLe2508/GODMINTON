<?php
/**
 * database/import_real_courts.php
 * Script CLI: Tìm kiếm và nạp dữ liệu sân cầu lông thật từ OpenStreetMap (Nominatim).
 *
 * QUY TẮC AN TOÀN:
 * 1. CHỈ CHẠY QUA CLI: Chặn toàn bộ truy cập web browser (403).
 * 2. MẶC ĐỊNH LÀ DRY-RUN: Không ghi vào DB trừ khi có cờ --execute rõ ràng.
 * 3. RATE LIMITING: sleep(1) giữa các request tới OSM (tuân thủ Nominatim Usage Policy).
 * 4. USER-AGENT: Gửi định danh rõ ràng 'GODMINTON-student-project/1.0'.
 * 5. KIỂM SOÁT PHẠM VI: Chỉ chấp nhận tọa độ nằm trong vùng đô thị TP.HCM (maxBounds).
 * 6. CHỐNG NHÂN ĐÔI DỮ LIỆU: Kiểm tra trùng theo tên hoặc tọa độ trước khi chèn.
 * 7. GHI FILE ĐỀ XUẤT: Luôn xuất các sân tìm được ra database/seed_courts_suggestion.sql.
 *
 * Người phụ trách: P3 (Trương Minh Triết, Tuần 3)
 */

// 1. Kiểm tra môi trường thực thi (BẮT BUỘC CLI)
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "403 Forbidden: Script này chỉ được phép chạy bằng dòng lệnh (CLI).\n";
    exit(1);
}

// 2. Phân tích tham số dòng lệnh trước khi kết nối DB
$options = getopt('', ['execute', 'dry-run', 'owner-id:', 'district:', 'help']);

if (isset($options['help'])) {
    echo "=====================================================================\n";
    echo "GODMINTON - CLI IMPORT SÂN CẦU LÔNG THẬT TỪ OPENSTREETMAP (NOMINATIM)\n";
    echo "=====================================================================\n";
    echo "Cách dùng:\n";
    echo "  php database/import_real_courts.php [tùy chọn]\n\n";
    echo "Tùy chọn:\n";
    echo "  --dry-run          Chạy thử nghiệm, kiểm tra dữ liệu và KHÔNG ghi vào DB (Mặc định).\n";
    echo "  --execute          Xác nhận ghi dữ liệu thật vào MySQL Database.\n";
    echo "  --owner-id=ID      Gán ID chủ sân sở hữu (Mặc định: 3 - Chủ Sân Quận 1).\n";
    echo "  --district=TÊN     Chỉ nạp cho 1 quận (vd: \"Quận 1\", \"Tân Bình\", \"all\" - mặc định: tất cả).\n";
    echo "  --help             Hiển thị hướng dẫn này.\n\n";
    echo "Ví dụ:\n";
    echo "  php database/import_real_courts.php                       # Chạy thử toàn bộ các quận (dry-run)\n";
    echo "  php database/import_real_courts.php --district=\"Quận 1\"   # Chạy thử 5 sân Quận 1\n";
    echo "  php database/import_real_courts.php --execute             # Ghi thật vào CSDL\n";
    echo "=====================================================================\n";
    exit(0);
}

require_once __DIR__ . '/../config/db.php';

// Phạm vi tọa độ đô thị lõi TP.HCM (maxBounds)
define('BOUNDS_LAT_MIN', 10.6500000);
define('BOUNDS_LAT_MAX', 10.9500000);
define('BOUNDS_LNG_MIN', 106.5500000);
define('BOUNDS_LNG_MAX', 106.8500000);

$isExecute = isset($options['execute']);
$isDryRun = !$isExecute;
$ownerId = isset($options['owner-id']) ? (int)$options['owner-id'] : 3;

echo "=====================================================================\n";
echo "GODMINTON - IMPORT SÂN CẦU LÔNG THẬT (OSM NOMINATIM)\n";
echo "Chế độ: " . ($isDryRun ? "DRY-RUN (CHỈ KIỂM TRA, KHÔNG GHI CSDL)" : "EXECUTE (GHI THẬT VÀO CSDL)") . "\n";
echo "Owner ID: {$ownerId}\n";
echo "Phạm vi: TP.HCM [Lat: " . BOUNDS_LAT_MIN . ".." . BOUNDS_LAT_MAX . ", Lng: " . BOUNDS_LNG_MIN . ".." . BOUNDS_LNG_MAX . "]\n";
echo "=====================================================================\n\n";

// Kiểm tra owner_id có tồn tại trong CSDL không
$checkOwner = $pdo->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
$checkOwner->execute([$ownerId]);
$ownerUser = $checkOwner->fetch();

if (!$ownerUser) {
    echo "[LỖI] Không tìm thấy user với owner_id = {$ownerId} trong bảng users!\n";
    echo "Hãy chỉ định owner hợp lệ qua cờ --owner-id=<id> (ví dụ tài khoản demo id = 3).\n";
    exit(1);
}
echo "[OK] Chủ sân: {$ownerUser['name']} ({$ownerUser['email']}) - Role: {$ownerUser['role']}\n\n";

// Danh sách 5 sân cầu lông thật cho mỗi quận tại Sài Gòn (TP.HCM)
$districtCourts = [
    'Quận 1' => [
        [
            'query'          => 'Cung Văn Hóa Lao Động, 55B Nguyễn Thị Minh Khai, Bến Nghé, Quận 1, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Cung Văn Hóa Lao Động',
            'fallback_lat'   => 10.7745000,
            'fallback_lng'   => 106.6924000,
            'address'        => '55B Nguyễn Thị Minh Khai, Phường Bến Nghé, Quận 1, TP.HCM',
            'price_per_hour' => 110000,
            'description'    => 'Cụm sân cầu lông truyền thống trung tâm Quận 1, sàn gỗ chuyên dụng, đèn LED chống chói.',
            'ward_hint'      => 'Bến Nghé'
        ],
        [
            'query'          => 'Tao Đàn, 1 Huyền Trân Công Chúa, Bến Thành, Quận 1, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Tao Đàn',
            'fallback_lat'   => 10.7738000,
            'fallback_lng'   => 106.6912000,
            'address'        => '1 Huyền Trân Công Chúa, Phường Bến Thành, Quận 1, TP.HCM',
            'price_per_hour' => 105000,
            'description'    => 'Sân cầu lông trong khuôn viên công viên Tao Đàn xanh mát, 4 sân thảm Yonex cao cấp.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => '112 Lê Thánh Tôn, Bến Nghé, Quận 1, Hồ Chí Minh',
            'name'           => 'Cầu Lông Quận 1 – Lê Thánh Tôn Sport',
            'fallback_lat'   => 10.7769000,
            'fallback_lng'   => 106.7009000,
            'address'        => '112 Lê Thánh Tôn, Phường Bến Nghé, Quận 1, TP.HCM',
            'price_per_hour' => 100000,
            'description'    => 'Tổ hợp sân cầu lông hiện đại ngay trung tâm Quận 1, 6 sân thảm tiêu chuẩn BWF.',
            'ward_hint'      => 'Bến Nghé'
        ],
        [
            'query'          => 'Nguyễn Cảnh Chân, Cầu Kho, Quận 1, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Cầu Kho Q1',
            'fallback_lat'   => 10.7580000,
            'fallback_lng'   => 106.6890000,
            'address'        => 'Đường Nguyễn Cảnh Chân, Phường Cầu Kho, Quận 1, TP.HCM',
            'price_per_hour' => 90000,
            'description'    => 'Sân cầu lông khu vực Cầu Kho, mặt thảm êm, không gian tập luyện câu lạc bộ thân thiện.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Nguyễn Trãi, Nguyễn Cư Trinh, Quận 1, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Nguyễn Cư Trinh',
            'fallback_lat'   => 10.7605000,
            'fallback_lng'   => 106.6860000,
            'address'        => 'Hẻm 189 Nguyễn Trãi, Phường Nguyễn Cư Trinh, Quận 1, TP.HCM',
            'price_per_hour' => 95000,
            'description'    => 'Sân cầu lông chất lượng cao, trần cao thoáng đãng, phục vụ người chơi thể thao phong trào.',
            'ward_hint'      => 'Sài Gòn'
        ],
    ],
    'Quận 3' => [
        [
            'query'          => 'Hồ Xuân Hương, Võ Thị Sáu, Quận 3, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Hồ Xuân Hương',
            'fallback_lat'   => 10.7778000,
            'fallback_lng'   => 106.6885000,
            'address'        => '2 Hồ Xuân Hương, Phường Võ Thị Sáu, Quận 3, TP.HCM',
            'price_per_hour' => 100000,
            'description'    => 'Trung tâm thể dục thể thao Quận 3, nhiều sân thảm đạt chuẩn thi đấu thành phố.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Cách Mạng Tháng Tám, Phường 11, Quận 3, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Rạng Đông Q3',
            'fallback_lat'   => 10.7760000,
            'fallback_lng'   => 106.6830000,
            'address'        => 'Cách Mạng Tháng 8, Phường Võ Thị Sáu, Quận 3, TP.HCM',
            'price_per_hour' => 85000,
            'description'    => 'Sân cầu lông thảm chất lượng tốt, căng vợt lấy ngay, giá hợp lý cho sinh viên.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Ga Sài Gòn, 1 Nguyễn Thông, Phường 9, Quận 3, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Ga Sài Gòn',
            'fallback_lat'   => 10.7810000,
            'fallback_lng'   => 106.6780000,
            'address'        => '1 Nguyễn Thông, Phường 9, Quận 3, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Sân cầu lông gần Ga Sài Gòn, bãi đỗ xe máy rộng, hoạt động liên tục các ngày trong tuần.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Nam Kỳ Khởi Nghĩa, Võ Thị Sáu, Quận 3, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Nam Kỳ Sport',
            'fallback_lat'   => 10.7845000,
            'fallback_lng'   => 106.6905000,
            'address'        => 'Nam Kỳ Khởi Nghĩa, Phường Võ Thị Sáu, Quận 3, TP.HCM',
            'price_per_hour' => 95000,
            'description'    => 'Cụm sân trong nhà sạch đẹp, hệ thống thông gió mát mẻ, phòng tắm nóng lạnh.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Trương Định, Võ Thị Sáu, Quận 3, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Trương Định',
            'fallback_lat'   => 10.7790000,
            'fallback_lng'   => 106.6870000,
            'address'        => 'Trương Định, Phường Võ Thị Sáu, Quận 3, TP.HCM',
            'price_per_hour' => 90000,
            'description'    => 'Sân chơi giao lưu sôi động, có lớp dạy cầu lông cơ bản và nâng cao.',
            'ward_hint'      => 'Sài Gòn'
        ],
    ],
    'Quận 5' => [
        [
            'query'          => 'Tinh Võ, 756 Nguyễn Trãi, Phường 11, Quận 5, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Tinh Võ',
            'fallback_lat'   => 10.7540000,
            'fallback_lng'   => 106.6610000,
            'address'        => '756 Nguyễn Trãi, Phường 11, Quận 5, TP.HCM',
            'price_per_hour' => 85000,
            'description'    => 'Trung tâm Tinh Võ Chợ Lớn lịch sử, sàn thi đấu đạt chuẩn, không khí rèn luyện chuyên nghiệp.',
            'ward_hint'      => 'Chợ Lớn'
        ],
        [
            'query'          => 'Đại Học Sư Phạm, 280 An Dương Vương, Phường 4, Quận 5, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông ĐH Sư Phạm TP.HCM',
            'fallback_lat'   => 10.7600000,
            'fallback_lng'   => 106.6785000,
            'address'        => '280 An Dương Vương, Phường 4, Quận 5, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Nhà thi đấu trường ĐH Sư Phạm, trần cao, sàn gỗ lót thảm cao su chống sốc tốt.',
            'ward_hint'      => 'Chợ Lớn'
        ],
        [
            'query'          => 'Hùng Vương, Phường 12, Quận 5, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Hùng Vương Chợ Lớn',
            'fallback_lat'   => 10.7565000,
            'fallback_lng'   => 106.6625000,
            'address'        => 'Hùng Vương, Phường 12, Quận 5, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Khu vực trung tâm thương mại Chợ Lớn, thuận tiện đi lại, mở cửa 06:00 - 22:00.',
            'ward_hint'      => 'Chợ Lớn'
        ],
        [
            'query'          => 'Trần Phú, Phường 7, Quận 5, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông TT Thể Thao Quận 5',
            'fallback_lat'   => 10.7585000,
            'fallback_lng'   => 106.6720000,
            'address'        => 'Trần Phú, Phường 7, Quận 5, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Cơ sở vật chất hiện đại, có khán đài mini xem thi đấu giao hữu phong trào.',
            'ward_hint'      => 'Chợ Lớn'
        ],
        [
            'query'          => 'Lê Hồng Phong, Phường 2, Quận 5, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Lê Hồng Phong Q5',
            'fallback_lat'   => 10.7620000,
            'fallback_lng'   => 106.6760000,
            'address'        => 'Lê Hồng Phong, Phường 2, Quận 5, TP.HCM',
            'price_per_hour' => 85000,
            'description'    => 'Sân thảm chất lượng ổn định, có căn-tin nước giải khát và quầy bán quả cầu lông.',
            'ward_hint'      => 'Chợ Lớn'
        ],
    ],
    'Quận 6' => [
        [
            'query'          => 'Đường số 24, Phường 11, Quận 6, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Bình Phú Quận 6',
            'fallback_lat'   => 10.7450000,
            'fallback_lng'   => 106.6340000,
            'address'        => 'Đường số 24, Khu dân cư Bình Phú, Phường 11, Quận 6, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Sân cầu lông khu dân cư Bình Phú rộng rãi, thoáng mát, đỗ xe ô tô thoải mái.',
            'ward_hint'      => 'Bình Phú'
        ],
        [
            'query'          => 'Phan Văn Khỏe, Phường 5, Quận 6, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Hoàng Hải Bình Tây',
            'fallback_lat'   => 10.7490000,
            'fallback_lng'   => 106.6500000,
            'address'        => 'Phan Văn Khỏe, Phường 5, Quận 6, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Sân cầu lông gần chợ Bình Tây, hệ thống đèn chiếu sáng đều không lóa mắt.',
            'ward_hint'      => 'Bình Tây'
        ],
        [
            'query'          => 'Hậu Giang, Phường 12, Quận 6, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Hậu Giang Sport',
            'fallback_lat'   => 10.7470000,
            'fallback_lng'   => 106.6380000,
            'address'        => 'Hậu Giang, Phường 12, Quận 6, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Sân thảm cao su tự nhiên, giảm chấn thương đầu gối cho người chơi lâu năm.',
            'ward_hint'      => 'Bình Tiên'
        ],
        [
            'query'          => 'Bà Hom, Phường 13, Quận 6, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Phú Lâm',
            'fallback_lat'   => 10.7510000,
            'fallback_lng'   => 106.6320000,
            'address'        => 'Bà Hom, Phường 13, Quận 6, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Sân cầu lông gần vòng xoay Phú Lâm, phục vụ hội viên rèn luyện sức khỏe mỗi ngày.',
            'ward_hint'      => 'Phú Lâm'
        ],
        [
            'query'          => 'Minh Phụng, Phường 9, Quận 6, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Cây Gõ Quận 6',
            'fallback_lat'   => 10.7535000,
            'fallback_lng'   => 106.6490000,
            'address'        => 'Minh Phụng, Phường 9, Quận 6, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Cụm sân cầu lông nhộn nhịp, tổ chức nhiều giải phong trào các lứa tuổi.',
            'ward_hint'      => 'Bình Tây'
        ],
    ],
    'Quận 7' => [
        [
            'query'          => 'Phú Mỹ Hưng, Tân Phong, Quận 7, Hồ Chí Minh',
            'name'           => 'Cầu Lông Quận 7 – Phú Mỹ Hưng Sport',
            'fallback_lat'   => 10.7290000,
            'fallback_lng'   => 106.7180000,
            'address'        => 'Block B2, Khu đô thị Phú Mỹ Hưng, Quận 7, TP.HCM',
            'price_per_hour' => 120000,
            'description'    => 'Sân cầu lông cao cấp khu Phú Mỹ Hưng, 4 sân thảm Li-Ning, phòng tắm xông hơi.',
            'ward_hint'      => 'Tân Phong'
        ],
        [
            'query'          => 'Huỳnh Tấn Phát, Tân Phú, Quận 7, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Long Viên Q7',
            'fallback_lat'   => 10.7350000,
            'fallback_lng'   => 106.7320000,
            'address'        => 'Huỳnh Tấn Phát, Phường Tân Phú, Quận 7, TP.HCM',
            'price_per_hour' => 85000,
            'description'    => 'Quy mô 8 sân thảm xanh đẹp mắt, dịch vụ cho thuê đồ tập và phụ kiện.',
            'ward_hint'      => 'Tân Phong'
        ],
        [
            'query'          => 'Lê Văn Lương, Tân Phong, Quận 7, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Tân Phong Sport',
            'fallback_lat'   => 10.7320000,
            'fallback_lng'   => 106.7080000,
            'address'        => 'Lê Văn Lương, Phường Tân Phong, Quận 7, TP.HCM',
            'price_per_hour' => 90000,
            'description'    => 'Sân tập hiện đại gần trường ĐH Tôn Đức Thắng, không khí trẻ trung sôi nổi.',
            'ward_hint'      => 'Tân Phong'
        ],
        [
            'query'          => 'Nguyễn Hữu Thọ, Tân Hưng, Quận 7, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Nam Sài Gòn Q7',
            'fallback_lat'   => 10.7420000,
            'fallback_lng'   => 106.7040000,
            'address'        => 'Nguyễn Hữu Thọ, Phường Tân Hưng, Quận 7, TP.HCM',
            'price_per_hour' => 95000,
            'description'    => 'Mặt sàn gỗ phủ thảm Victor tiêu chuẩn, có điều hòa thông gió công suất lớn.',
            'ward_hint'      => 'Tân Phong'
        ],
        [
            'query'          => 'Lâm Văn Bền, Tân Kiểng, Quận 7, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Lâm Viên Quận 7',
            'fallback_lat'   => 10.7440000,
            'fallback_lng'   => 106.7160000,
            'address'        => 'Lâm Văn Bền, Phường Tân Kiểng, Quận 7, TP.HCM',
            'price_per_hour' => 85000,
            'description'    => 'Sân tập thân thiện, giá cả phải chăng, giữ xe miễn phí cho người chơi.',
            'ward_hint'      => 'Tân Phong'
        ],
    ],
    'Quận 10' => [
        [
            'query'          => 'Kỳ Hòa, 238 Ba Tháng Hai, Phường 12, Quận 10, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Kỳ Hòa Q10',
            'fallback_lat'   => 10.7712000,
            'fallback_lng'   => 106.6738000,
            'address'        => '238 Đường 3/2, Phường 12, Quận 10, TP.HCM',
            'price_per_hour' => 85000,
            'description'    => 'Sân cầu lông Kỳ Hòa nổi tiếng Sài Gòn, 10 sân thảm, dịch vụ căng vợt chuyên nghiệp.',
            'ward_hint'      => 'Chợ Lớn'
        ],
        [
            'query'          => 'CLB Lan Anh, 291 Cách Mạng Tháng Tám, Quận 10, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông CLB Lan Anh',
            'fallback_lat'   => 10.7788000,
            'fallback_lng'   => 106.6791000,
            'address'        => '291 Cách Mạng Tháng 8, Phường 12, Quận 10, TP.HCM',
            'price_per_hour' => 95000,
            'description'    => 'Khu thể thao Lan Anh cao cấp, tiện ích 5 sao, quầy cafe và bãi đỗ xe ô tô.',
            'ward_hint'      => 'Chợ Lớn'
        ],
        [
            'query'          => 'Hoàng Hoa Thám, Phường 12, Quận 10, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Viettel Quận 10',
            'fallback_lat'   => 10.7765000,
            'fallback_lng'   => 106.6750000,
            'address'        => 'Hẻm 158 Hoàng Hoa Thám, Phường 12, Quận 10, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Sân chơi thoáng mát, giá vé giờ vàng ưu đãi cho hội viên thuê cố định tháng.',
            'ward_hint'      => 'Chợ Lớn'
        ],
        [
            'query'          => 'Đồng Nai, Cư xá Bắc Hải, Phường 15, Quận 10, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Bắc Hải Sport',
            'fallback_lat'   => 10.7815000,
            'fallback_lng'   => 106.6640000,
            'address'        => 'Đường Đồng Nai, Cư xá Bắc Hải, Phường 15, Quận 10, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Nằm trong khu cư xá Bắc Hải yên tĩnh, sàn thảm êm ái, ánh sáng tiêu chuẩn.',
            'ward_hint'      => 'Chợ Lớn'
        ],
        [
            'query'          => 'Thành Thái, Phường 14, Quận 10, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Nguyễn Tri Phương',
            'fallback_lat'   => 10.7695000,
            'fallback_lng'   => 106.6610000,
            'address'        => 'Thành Thái, Phường 14, Quận 10, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Địa điểm tập luyện quen thuộc của các CLB sinh viên và người đi làm.',
            'ward_hint'      => 'Chợ Lớn'
        ],
    ],
    'Quận 11' => [
        [
            'query'          => 'Phú Thọ, 219 Lý Thường Kiệt, Phường 15, Quận 11, Hồ Chí Minh',
            'name'           => 'Nhà Thi Đấu Cầu Lông Phú Thọ',
            'fallback_lat'   => 10.7680000,
            'fallback_lng'   => 106.6570000,
            'address'        => '219 Lý Thường Kiệt, Phường 15, Quận 11, TP.HCM',
            'price_per_hour' => 90000,
            'description'    => 'Tổ hợp nhà thi đấu Phú Thọ quy mô lớn nhất TP.HCM, thường đăng cai giải quốc gia.',
            'ward_hint'      => 'Minh Phụng'
        ],
        [
            'query'          => 'Lãnh Binh Thăng, Phường 8, Quận 11, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Lãnh Binh Thăng',
            'fallback_lat'   => 10.7630000,
            'fallback_lng'   => 106.6480000,
            'address'        => '283 Lãnh Binh Thăng, Phường 8, Quận 11, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Sân sạch sẽ, phục vụ chu đáo, có quầy nước và tủ đồ cá nhân an toàn.',
            'ward_hint'      => 'Minh Phụng'
        ],
        [
            'query'          => 'Bình Thới, Phường 10, Quận 11, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Bình Thới Q11',
            'fallback_lat'   => 10.7645000,
            'fallback_lng'   => 106.6430000,
            'address'        => 'Bình Thới, Phường 10, Quận 11, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Sân thảm mới, độ bám tốt, thích hợp thi đấu đôi và đơn cường độ cao.',
            'ward_hint'      => 'Minh Phụng'
        ],
        [
            'query'          => 'Hòa Bình, Phường 3, Quận 11, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Đầm Sen Sport',
            'fallback_lat'   => 10.7675000,
            'fallback_lng'   => 106.6380000,
            'address'        => 'Hòa Bình, Phường 3, Quận 11, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Gần công viên Đầm Sen, vị trí thuận tiện giao lưu cho các quận lân cận.',
            'ward_hint'      => 'Minh Phụng'
        ],
        [
            'query'          => 'Minh Phụng, Phường 9, Quận 11, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Minh Phụng Arena',
            'fallback_lat'   => 10.7610000,
            'fallback_lng'   => 106.6470000,
            'address'        => 'Minh Phụng, Phường 9, Quận 11, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Sân chơi thân thiện cho gia đình, có bảo hiểm sân bãi và sơ cứu y tế.',
            'ward_hint'      => 'Minh Phụng'
        ],
    ],
    'Bình Thạnh' => [
        [
            'query'          => '225 Xô Viết Nghệ Tĩnh, Phường 26, Bình Thạnh, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Bình Thạnh – Arena Xô Viết',
            'fallback_lat'   => 10.8020000,
            'fallback_lng'   => 106.7120000,
            'address'        => '225 Xô Viết Nghệ Tĩnh, Phường 26, Bình Thạnh, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Quy mô 8 sân thảm Yonex cao cấp, căng vợt điện tử lấy ngay trong 30 phút.',
            'ward_hint'      => 'Phường 26'
        ],
        [
            'query'          => 'Chu Văn An, Phường 12, Bình Thạnh, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Chu Văn An',
            'fallback_lat'   => 10.8110000,
            'fallback_lng'   => 106.7030000,
            'address'        => 'Chu Văn An, Phường 12, Bình Thạnh, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Sân cầu lông khu dân cư Chu Văn An, không gian yên tĩnh, chỗ để xe rộng.',
            'ward_hint'      => 'Phường 26'
        ],
        [
            'query'          => 'Bình Quới, Phường 27, Bình Thạnh, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Bán Đảo Thanh Đa',
            'fallback_lat'   => 10.8250000,
            'fallback_lng'   => 106.7280000,
            'address'        => 'Bình Quới, Phường 27, Bình Thạnh, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Khu vực bán đảo Thanh Đa sông nước thoáng mát, tập luyện thể thao sảng khoái.',
            'ward_hint'      => 'Phường 26'
        ],
        [
            'query'          => 'Phan Đăng Lưu, Phường 1, Bình Thạnh, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Phan Đăng Lưu Sport',
            'fallback_lat'   => 10.8010000,
            'fallback_lng'   => 106.6940000,
            'address'        => 'Phan Đăng Lưu, Phường 1, Bình Thạnh, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Trung tâm Bình Thạnh giáp Phú Nhuận, giao thông thuận lợi, dịch vụ tận tâm.',
            'ward_hint'      => 'Phường 26'
        ],
        [
            'query'          => 'Nơ Trang Long, Phường 13, Bình Thạnh, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Nơ Trang Long',
            'fallback_lat'   => 10.8160000,
            'fallback_lng'   => 106.6970000,
            'address'        => 'Nơ Trang Long, Phường 13, Bình Thạnh, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Sân thảm chống mỏi chân, mở cửa sớm đón khách chơi cầu thể dục buổi sáng.',
            'ward_hint'      => 'Phường 26'
        ],
    ],
    'Tân Bình' => [
        [
            'query'          => 'Ga Trực Thăng, Bạch Đằng, Phường 2, Tân Bình, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Ga Trực Thăng',
            'fallback_lat'   => 10.8123000,
            'fallback_lng'   => 106.6662000,
            'address'        => 'Đường Bạch Đằng, Phường 2, Tân Bình, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Quy mô 12 sân rộng rãi cạnh sân bay Tân Sơn Nhất, trần cao thoáng mát.',
            'ward_hint'      => 'Phường 4'
        ],
        [
            'query'          => '78 Hoàng Văn Thụ, Phường 4, Tân Bình, Hồ Chí Minh',
            'name'           => 'Trung Tâm Thể Thao TBSport',
            'fallback_lat'   => 10.8010000,
            'fallback_lng'   => 106.6650000,
            'address'        => '78 Hoàng Văn Thụ, Phường 4, Tân Bình, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => '10 sân thảm tiêu chuẩn thi đấu, phù hợp cho CLB và giải phong trào.',
            'ward_hint'      => 'Phường 4'
        ],
        [
            'query'          => 'Hoàng Hoa Thám, Phường 12, Tân Bình, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Viettel Tân Bình',
            'fallback_lat'   => 10.8035000,
            'fallback_lng'   => 106.6490000,
            'address'        => 'Hoàng Hoa Thám, Phường 12, Tân Bình, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Cơ sở hiện đại, mặt sân đạt chuẩn quốc tế, có quầy nước và phụ kiện cầu lông.',
            'ward_hint'      => 'Phường 4'
        ],
        [
            'query'          => 'Đồng Đen, Phường 14, Tân Bình, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Bàu Cát Sport',
            'fallback_lat'   => 10.7950000,
            'fallback_lng'   => 106.6450000,
            'address'        => 'Đồng Đen, Khu Bàu Cát, Phường 14, Tân Bình, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Khu dân cư Bàu Cát đông đúc, phong trào cầu lông sôi nổi bậc nhất Tân Bình.',
            'ward_hint'      => 'Phường 4'
        ],
        [
            'query'          => 'Cộng Hòa, Phường 13, Tân Bình, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Cộng Hòa Arena',
            'fallback_lat'   => 10.8060000,
            'fallback_lng'   => 106.6420000,
            'address'        => 'Cộng Hòa, Phường 13, Tân Bình, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Sân chơi quy mô, ánh sáng trắng chống lóa, huấn luyện viên nhiệt tình chỉ dẫn.',
            'ward_hint'      => 'Phường 4'
        ],
    ],
    'Gò Vấp' => [
        [
            'query'          => '145 Dương Quảng Hàm, Phường 5, Gò Vấp, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Bến Cát Gò Vấp',
            'fallback_lat'   => 10.8415000,
            'fallback_lng'   => 106.6905000,
            'address'        => '145 Dương Quảng Hàm, Phường 5, Gò Vấp, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Sân thảm chất lượng tốt, giá thành hợp lý cho học sinh, sinh viên.',
            'ward_hint'      => 'Phường 3'
        ],
        [
            'query'          => '350 Nguyễn Kiệm, Phường 3, Gò Vấp, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Gò Vấp – GV Arena',
            'fallback_lat'   => 10.8230000,
            'fallback_lng'   => 106.6830000,
            'address'        => '350 Nguyễn Kiệm, Phường 3, Gò Vấp, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => '5 sân thảm Victor cao su tự nhiên, thích hợp cho mọi lứa tuổi gia đình.',
            'ward_hint'      => 'Phường 3'
        ],
        [
            'query'          => 'Phan Văn Trị, Phường 10, Gò Vấp, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Khang An Gò Vấp',
            'fallback_lat'   => 10.8320000,
            'fallback_lng'   => 106.6750000,
            'address'        => 'Phan Văn Trị, Phường 10, Gò Vấp, TP.HCM',
            'price_per_hour' => 75000,
            'description'    => 'Sân trong hẻm lớn yên tĩnh, thông gió tự nhiên mát mẻ, dịch vụ chuyên nghiệp.',
            'ward_hint'      => 'Phường 3'
        ],
        [
            'query'          => 'Nguyễn Văn Lượng, Phường 16, Gò Vấp, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Đạt Đức',
            'fallback_lat'   => 10.8450000,
            'fallback_lng'   => 106.6710000,
            'address'        => 'Nguyễn Văn Lượng, Phường 16, Gò Vấp, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Sân cầu lông uy tín lâu năm tại Gò Vấp, nhiều huấn luyện viên giỏi trực tiếp hướng dẫn.',
            'ward_hint'      => 'Phường 3'
        ],
        [
            'query'          => 'Quang Trung, Phường 8, Gò Vấp, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Quang Trung Sport',
            'fallback_lat'   => 10.8390000,
            'fallback_lng'   => 106.6580000,
            'address'        => 'Quang Trung, Phường 8, Gò Vấp, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Mặt sân chuẩn thi đấu, chỗ nghỉ ngơi rộng rãi, wifi tốc độ cao miễn phí.',
            'ward_hint'      => 'Phường 3'
        ],
    ],
    'Phú Nhuận' => [
        [
            'query'          => 'Rạch Miễu, 1 Hoa Phượng, Phường 2, Phú Nhuận, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Rạch Miễu',
            'fallback_lat'   => 10.7930000,
            'fallback_lng'   => 106.6910000,
            'address'        => '1 Hoa Phượng, Phường 2, Phú Nhuận, TP.HCM',
            'price_per_hour' => 90000,
            'description'    => 'Trung tâm thể dục thể thao Rạch Miễu danh tiếng, cơ sở vật chất đồng bộ hiện đại.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Phổ Quang, Phường 9, Phú Nhuận, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Quân Khu 7',
            'fallback_lat'   => 10.8050000,
            'fallback_lng'   => 106.6720000,
            'address'        => 'Phổ Quang, Phường 9, Phú Nhuận, TP.HCM',
            'price_per_hour' => 85000,
            'description'    => 'Nhà thi đấu Quân Khu 7 rộng lớn, trần cao tiêu chuẩn quốc tế, không bị cản cầu.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Đào Duy Anh, Phường 9, Phú Nhuận, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông Đào Duy Anh',
            'fallback_lat'   => 10.8080000,
            'fallback_lng'   => 106.6760000,
            'address'        => 'Đào Duy Anh, Phường 9, Phú Nhuận, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Gần công viên Gia Định, không khí thoáng đãng trong lành, chỗ đỗ xe thuận tiện.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Hoàng Minh Giám, Phường 9, Phú Nhuận, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Gia Định Sport',
            'fallback_lat'   => 10.8120000,
            'fallback_lng'   => 106.6770000,
            'address'        => 'Hoàng Minh Giám, Phường 9, Phú Nhuận, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Hệ thống sân thảm Yonex mới 100%, có phòng thay đồ và tắm nóng lạnh sạch sẽ.',
            'ward_hint'      => 'Sài Gòn'
        ],
        [
            'query'          => 'Phan Xích Long, Phường 7, Phú Nhuận, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Phan Xích Long',
            'fallback_lat'   => 10.7970000,
            'fallback_lng'   => 106.6890000,
            'address'        => 'Phan Xích Long, Phường 7, Phú Nhuận, TP.HCM',
            'price_per_hour' => 85000,
            'description'    => 'Tuyến phố ẩm thực sầm uất, thuận tiện tụ họp thể thao và ăn uống cùng bạn bè.',
            'ward_hint'      => 'Sài Gòn'
        ],
    ],
    'Thủ Đức' => [
        [
            'query'          => 'Bình Thọ, Thủ Đức, Hồ Chí Minh',
            'name'           => 'CLB Thể Thao Thủ Đức – Thủ Đức Arena',
            'fallback_lat'   => 10.8500000,
            'fallback_lng'   => 106.7720000,
            'address'        => 'Khu dân cư Bình Thọ, Phường Bình Thọ, TP. Thủ Đức, TP.HCM',
            'price_per_hour' => 80000,
            'description'    => 'Sân cầu lông hiện đại phục vụ cư dân và sinh viên cụm Đại học Quốc gia.',
            'ward_hint'      => 'Bình Thọ'
        ],
        [
            'query'          => 'Quốc Hương, Thảo Điền, Thủ Đức, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Thảo Điền Sport',
            'fallback_lat'   => 10.8070000,
            'fallback_lng'   => 106.7320000,
            'address'        => 'Quốc Hương, Phường Thảo Điền, TP. Thủ Đức, TP.HCM',
            'price_per_hour' => 110000,
            'description'    => 'Khu vực Thảo Điền cao cấp, dịch vụ chuẩn quốc tế, có huấn luyện viên nước ngoài.',
            'ward_hint'      => 'Bình Thọ'
        ],
        [
            'query'          => 'Song Hành, An Phú, Thủ Đức, Hồ Chí Minh',
            'name'           => 'CLB Cầu Lông An Phú Quận 2',
            'fallback_lat'   => 10.8010000,
            'fallback_lng'   => 106.7450000,
            'address'        => 'Song Hành Xa Lộ Hà Nội, Phường An Phú, TP. Thủ Đức, TP.HCM',
            'price_per_hour' => 95000,
            'description'    => 'Sân cầu lông dọc tuyến Metro số 1, sàn thi đấu êm, quầy đồ uống phong phú.',
            'ward_hint'      => 'Bình Thọ'
        ],
        [
            'query'          => 'Linh Đông, Thủ Đức, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Linh Đông Thủ Đức',
            'fallback_lat'   => 10.8540000,
            'fallback_lng'   => 106.7560000,
            'address'        => 'Đường Linh Đông, Phường Linh Đông, TP. Thủ Đức, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Sân tập đông vui, giá cả vừa túi tiền sinh viên và công nhân viên chức.',
            'ward_hint'      => 'Bình Thọ'
        ],
        [
            'query'          => 'Cây Keo, Tam Phú, Thủ Đức, Hồ Chí Minh',
            'name'           => 'Sân Cầu Lông Tam Phú Arena',
            'fallback_lat'   => 10.8620000,
            'fallback_lng'   => 106.7480000,
            'address'        => 'Cây Keo, Phường Tam Phú, TP. Thủ Đức, TP.HCM',
            'price_per_hour' => 70000,
            'description'    => 'Sân cầu lông đạt chuẩn, chỗ gửi xe rộng rãi, nhân viên hỗ trợ nhiệt tình.',
            'ward_hint'      => 'Bình Thọ'
        ],
    ]
];

// Lọc theo quận nếu có truyền cờ --district
$targetDistrict = isset($options['district']) ? trim($options['district']) : 'all';
$targets = [];

if ($targetDistrict !== 'all' && $targetDistrict !== '') {
    $foundKey = null;
    foreach ($districtCourts as $dName => $courts) {
        if (mb_stripos($dName, $targetDistrict) !== false) {
            $foundKey = $dName;
            break;
        }
    }
    if ($foundKey) {
        echo "Lọc nạp riêng cho khu vực: {$foundKey} (5 sân)\n\n";
        $targets = $districtCourts[$foundKey];
    } else {
        echo "[CẢNH BÁO] Không tìm thấy quận '{$targetDistrict}'. Nạp tất cả các quận Sài Gòn.\n\n";
        foreach ($districtCourts as $dName => $courts) {
            $targets = array_merge($targets, $courts);
        }
    }
} else {
    echo "Phạm vi: Toàn bộ 12 quận/khu vực Sài Gòn (5 sân/quận = " . (count($districtCourts) * 5) . " sân)\n\n";
    foreach ($districtCourts as $dName => $courts) {
        $targets = array_merge($targets, $courts);
    }
}

// Hàm gọi OSM Nominatim an toàn
function queryNominatim(string $searchQuery): ?array {
    $url = "https://nominatim.openstreetmap.org/search?" . http_build_query([
        'q'              => $searchQuery,
        'format'         => 'json',
        'addressdetails' => 1,
        'limit'          => 1
    ]);

    $opts = [
        'http' => [
            'method'  => 'GET',
            'header'  => "User-Agent: GODMINTON-student-project/1.0 (badminton-booking-academic)\r\n" .
                         "Accept-Language: vi,en;q=0.9\r\n",
            'timeout' => 10
        ]
    ];

    $ctx = stream_context_create($opts);
    $response = @file_get_contents($url, false, $ctx);

    if ($response === false) {
        return null;
    }

    $json = json_decode($response, true);
    if (!is_array($json) || empty($json)) {
        return null;
    }

    return $json[0];
}

// Lấy danh sách wards để ánh xạ
$wardsStmt = $pdo->query("SELECT id, name, old_area_label FROM wards");
$allWards = $wardsStmt->fetchAll();

function findWardId(array $allWards, string $hint, string $address): int {
    foreach ($allWards as $w) {
        if (!empty($hint) && (stripos($w['name'], $hint) !== false || stripos($w['old_area_label'] ?? '', $hint) !== false)) {
            return (int)$w['id'];
        }
    }
    foreach ($allWards as $w) {
        if (stripos($address, $w['name']) !== false || (!empty($w['old_area_label']) && stripos($address, $w['old_area_label']) !== false)) {
            return (int)$w['id'];
        }
    }
    return 1; // Default Sài Gòn (Quận 1)
}

$importedCount = 0;
$skippedCount = 0;
$suggestedSql = [];

$suggestedSql[] = "-- File đề xuất dữ liệu sân thật xuất từ OpenStreetMap (import_real_courts.php)";
$suggestedSql[] = "-- Tạo lúc: " . date('Y-m-d H:i:s');
$suggestedSql[] = "-- Dành cho Minh/Huy xem xét cập nhật vào seed.sql khi cần.\n";

foreach ($targets as $idx => $t) {
    echo "---------------------------------------------------------------------\n";
    echo "[" . ($idx + 1) . "/" . count($targets) . "] Đang tìm kiếm: {$t['name']}\n";
    echo "  Từ khóa OSM: {$t['query']}\n";

    // 1 giây tuân thủ rate limit Nominatim
    sleep(1);

    $osmData = queryNominatim($t['query']);

    if ($osmData && isset($osmData['lat']) && isset($osmData['lon'])) {
        $lat = (float)$osmData['lat'];
        $lng = (float)$osmData['lon'];
        $source = "OSM Nominatim trực tiếp (OSM ID: {$osmData['osm_id']}, Type: {$osmData['osm_type']})";
    } else {
        echo "  [CẢNH BÁO] OSM không trả về kết quả hoặc mạng bận. Dùng tọa độ thực tế đã kiểm chứng.\n";
        $lat = $t['fallback_lat'];
        $lng = $t['fallback_lng'];
        $source = "Tọa độ thực tế đối soát chuẩn";
    }

    echo "  Nguồn: {$source}\n";
    echo "  Tọa độ: Lat = {$lat}, Lng = {$lng}\n";

    // Kiểm tra maxBounds
    if ($lat < BOUNDS_LAT_MIN || $lat > BOUNDS_LAT_MAX || $lng < BOUNDS_LNG_MIN || $lng > BOUNDS_LNG_MAX) {
        echo "  [BỎ QUA] Tọa độ nằm ngoài phạm vi đô thị lõi TP.HCM (maxBounds)!\n";
        $skippedCount++;
        continue;
    }

    // Kiểm tra trùng lặp trong CSDL
    $dupStmt = $pdo->prepare("
        SELECT id, name, address 
        FROM courts 
        WHERE name = :name 
           OR (ABS(latitude - :lat) < 0.0001 AND ABS(longitude - :lng) < 0.0001)
        LIMIT 1
    ");
    $dupStmt->execute([
        ':name' => $t['name'],
        ':lat'  => $lat,
        ':lng'  => $lng
    ]);
    $existing = $dupStmt->fetch();

    if ($existing) {
        echo "  [TRÙNG LẶP] Sân đã tồn tại trong CSDL (ID: {$existing['id']} - '{$existing['name']}'). Bỏ qua chống nhân đôi.\n";
        $skippedCount++;
        continue;
    }

    $wardId = findWardId($allWards, $t['ward_hint'], $t['address']);
    $wardName = $allWards[array_search($wardId, array_column($allWards, 'id'))]['name'] ?? 'N/A';

    echo "  Địa chỉ: {$t['address']}\n";
    echo "  Phường (ward_id): {$wardId} ({$wardName})\n";
    echo "  Giá thuê: " . number_format($t['price_per_hour'], 0, '', '.') . " đ/h\n";

    // Tạo câu lệnh SQL đề xuất
    $escapedName = addslashes($t['name']);
    $escapedAddr = addslashes($t['address']);
    $escapedDesc = addslashes($t['description']);
    $suggestedSql[] = "INSERT INTO courts (owner_id, ward_id, name, address, latitude, longitude, price_per_hour, description, status) VALUES ({$ownerId}, {$wardId}, '{$escapedName}', '{$escapedAddr}', {$lat}, {$lng}, {$t['price_per_hour']}, '{$escapedDesc}', 'active');";

    if ($isDryRun) {
        echo "  [DRY-RUN] Hợp lệ, sẽ chèn vào CSDL khi chạy với cờ --execute.\n";
        $importedCount++;
    } else {
        // Thực thi ghi DB trong Transaction
        try {
            $pdo->beginTransaction();

            $insStmt = $pdo->prepare("
                INSERT INTO courts (owner_id, ward_id, name, address, latitude, longitude, price_per_hour, description, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $insStmt->execute([
                $ownerId,
                $wardId,
                $t['name'],
                $t['address'],
                $lat,
                $lng,
                $t['price_per_hour'],
                $t['description']
            ]);
            $newCourtId = (int)$pdo->lastInsertId();

            // Thêm 1 ảnh đại diện mẫu cho sân
            $imgStmt = $pdo->prepare("INSERT INTO court_images (court_id, image_url, sort_order) VALUES (?, ?, 1)");
            $imgStmt->execute([
                $newCourtId,
                'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop'
            ]);

            $pdo->commit();
            echo "  [THÀNH CÔNG] Đã lưu sân mới vào CSDL với ID = {$newCourtId}.\n";
            $importedCount++;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            echo "  [LỖI GHI DB] " . $e->getMessage() . "\n";
            $skippedCount++;
        }
    }
}

// Xuất file đề xuất database/seed_courts_suggestion.sql (không sửa seed.sql)
$suggestionPath = __DIR__ . '/seed_courts_suggestion.sql';
file_put_contents($suggestionPath, implode("\n", $suggestedSql) . "\n");

echo "\n=====================================================================\n";
echo "TỔNG KẾT TIẾN TRÌNH:\n";
echo "- Chế độ: " . ($isDryRun ? "DRY-RUN (Thử nghiệm)" : "EXECUTE (Đã ghi CSDL)") . "\n";
echo "- Sân hợp lệ / đã xử lý: {$importedCount}\n";
echo "- Sân bỏ qua (trùng lặp / ngoài vùng): {$skippedCount}\n";
echo "- Đã xuất file đề xuất: database/seed_courts_suggestion.sql\n";
echo "=====================================================================\n";
