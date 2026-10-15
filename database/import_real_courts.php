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
$options = getopt('', ['execute', 'dry-run', 'owner-id:', 'help']);

if (isset($options['help'])) {
    echo "=====================================================================\n";
    echo "GODMINTON - CLI IMPORT SÂN CẦU LÔNG THẬT TỪ OPENSTREETMAP (NOMINATIM)\n";
    echo "=====================================================================\n";
    echo "Cách dùng:\n";
    echo "  php database/import_real_courts.php [tùy chọn]\n\n";
    echo "Tùy chọn:\n";
    echo "  --dry-run        Chạy thử nghiệm, kiểm tra dữ liệu và KHÔNG ghi vào DB (Mặc định).\n";
    echo "  --execute        Xác nhận ghi dữ liệu thật vào MySQL Database.\n";
    echo "  --owner-id=ID    Gán ID chủ sân sở hữu (Mặc định: 3 - Chủ Sân Quận 1).\n";
    echo "  --help           Hiển thị hướng dẫn này.\n\n";
    echo "Ví dụ:\n";
    echo "  php database/import_real_courts.php                # Chạy thử dry-run\n";
    echo "  php database/import_real_courts.php --execute      # Ghi thật vào CSDL\n";
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

// Danh sách các sân cầu lông có thật tại TP.HCM để truy vấn OSM
$targets = [
    [
        'query'          => 'Sân cầu lông Kỳ Hòa, 238 Ba Tháng Hai, Phường 12, Quận 10, Hồ Chí Minh',
        'name'           => 'CLB Cầu Lông Kỳ Hòa',
        'fallback_lat'   => 10.7712000,
        'fallback_lng'   => 106.6738000,
        'address'        => '238 Đường 3/2, Phường 12, Quận 10, TP.HCM',
        'price_per_hour' => 85000,
        'description'    => 'Sân cầu lông Kỳ Hòa tiêu chuẩn thi đấu, mặt sân thảm chống trơn trượt, hệ thống chiếu sáng chuẩn quốc tế.',
        'ward_hint'      => 'Chợ Lớn'
    ],
    [
        'query'          => 'Sân cầu lông Lan Anh, 291 Cách Mạng Tháng Tám, Quận 10, Hồ Chí Minh',
        'name'           => 'Sân Cầu Lông CLB Lan Anh',
        'fallback_lat'   => 10.7788000,
        'fallback_lng'   => 106.6791000,
        'address'        => '291 Cách Mạng Tháng 8, Phường 12, Quận 10, TP.HCM',
        'price_per_hour' => 95000,
        'description'    => 'Khu thể thao Lan Anh cao cấp, không gian thoáng đãng, có bãi đậu ô tô rộng và dịch vụ tiện ích đầy đủ.',
        'ward_hint'      => 'Chợ Lớn'
    ],
    [
        'query'          => 'Sân cầu lông Cung Văn Hóa Lao Động, 55B Nguyễn Thị Minh Khai, Bến Thành, Quận 1, Hồ Chí Minh',
        'name'           => 'Sân Cầu Lông Cung Văn Hóa Lao Động',
        'fallback_lat'   => 10.7745000,
        'fallback_lng'   => 106.6924000,
        'address'        => '55B Nguyễn Thị Minh Khai, Phường Bến Nghé, Quận 1, TP.HCM',
        'price_per_hour' => 110000,
        'description'    => 'Cụm sân cầu lông truyền thống ngay trung tâm Quận 1, sàn gỗ chuyên dụng, địa điểm quen thuộc của cộng đồng vợt thủ.',
        'ward_hint'      => 'Bến Nghé'
    ],
    [
        'query'          => 'Sân cầu lông Ga Trực Thăng, Bạch Đằng, Phường 2, Tân Bình, Hồ Chí Minh',
        'name'           => 'CLB Cầu Lông Ga Trực Thăng',
        'fallback_lat'   => 10.8123000,
        'fallback_lng'   => 106.6662000,
        'address'        => 'Đường Bạch Đằng, Phường 2, Tân Bình, TP.HCM',
        'price_per_hour' => 80000,
        'description'    => 'Quy mô 12 sân rộng rãi cạnh sân bay Tân Sơn Nhất, trần cao thoáng mát, mở cửa từ sáng sớm đến tối muộn.',
        'ward_hint'      => 'Phường 4'
    ],
    [
        'query'          => 'Sân cầu lông Bến Cát, Dương Quảng Hàm, Phường 5, Gò Vấp, Hồ Chí Minh',
        'name'           => 'Sân Cầu Lông Bến Cát Gò Vấp',
        'fallback_lat'   => 10.8415000,
        'fallback_lng'   => 106.6905000,
        'address'        => '145 Dương Quảng Hàm, Phường 5, Gò Vấp, TP.HCM',
        'price_per_hour' => 75000,
        'description'    => 'Sân thảm chất lượng tốt, giá thành hợp lý cho học sinh, sinh viên và câu lạc bộ tập luyện định kỳ.',
        'ward_hint'      => 'Phường 3'
    ]
];

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
