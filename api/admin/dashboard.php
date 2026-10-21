<?php
require_once __DIR__.'/../../includes/auth-session.php';
/**
 * api/admin/dashboard.php
 * GET số liệu thống kê tổng quan, danh sách đơn gần đây và sân quản lý cho chủ sân / admin.
 *   - Chủ sân (owner): chỉ tính trên các sân có owner_id = $_SESSION['user_id']
 *   - Quản trị viên (admin): tính trên toàn bộ hệ thống
 *
 * Người phụ trách: Huy (Tuần 3)
 */
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json; charset=utf-8');

function jsonOut(int $code, array $body): void {
    http_response_code($code);
    if (!isset($body['code'])) {
        $body = ['status' => $body['status'] ?? ($code < 400 ? 'success' : 'error'), 'code' => $code] + $body;
    }
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
if (session_status() === PHP_SESSION_NONE) {
    god_auth_start();
    god_auth_user($pdo);
}
$currentUserId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$currentRole   = $_SESSION['role'] ?? '';
session_write_close();

if ($currentUserId <= 0) {
    jsonOut(401, ['status' => 'error', 'message' => 'Vui lòng đăng nhập để xem thống kê quản trị.']);
}
if (!in_array($currentRole, ['admin', 'owner'], true)) {
    jsonOut(403, ['status' => 'error', 'message' => 'Bạn không có quyền truy cập trang tổng quan quản trị.']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonOut(405, ['status' => 'error', 'message' => 'Chỉ chấp nhận phương thức GET.']);
}

try {
    // Kiểm tra xem bảng courts đã có cột total_sub_courts chưa
    $cols = $pdo->query("SHOW COLUMNS FROM courts")->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasSubCol = in_array('total_sub_courts', $cols, true);
    $subSelect = $hasSubCol ? 'c.total_sub_courts' : '6 AS total_sub_courts';

    // 1. Lấy danh sách sân thuộc phạm vi quản lý
    $courtSql = "
        SELECT
            c.id,
            c.owner_id,
            c.name,
            c.address,
            c.price_per_hour,
            {$subSelect},
            c.status,
            w.name           AS ward_name,
            w.old_area_label AS old_area_label,
            (SELECT ci.image_url
             FROM court_images ci
             WHERE ci.court_id = c.id
             ORDER BY ci.sort_order ASC, ci.id ASC
             LIMIT 1) AS image_url
        FROM courts c
        JOIN wards w ON w.id = c.ward_id
    ";
    $courtParams = [];
    if ($currentRole !== 'admin') {
        $courtSql .= " WHERE c.owner_id = :owner_id";
        $courtParams[':owner_id'] = $currentUserId;
    }
    $courtSql .= " ORDER BY c.id ASC";

    $courtStmt = $pdo->prepare($courtSql);
    $courtStmt->execute($courtParams);
    $rawCourts = $courtStmt->fetchAll();

    $defaultImg   = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=800&auto=format&fit=crop';
    $courts       = [];
    $activeCourts = 0;

    foreach ($rawCourts as $rc) {
        if ($rc['status'] === 'active') {
            $activeCourts++;
        }
        $districtLabel = $rc['old_area_label']
            ? preg_replace('/\s*\(cũ\)\s*$/u', '', $rc['old_area_label'])
            : $rc['ward_name'];

        $courts[] = [
            'id'               => (int)$rc['id'],
            'name'             => $rc['name'],
            'address'          => $rc['address'],
            'district'         => $districtLabel,
            'price_per_hour'   => (int)$rc['price_per_hour'],
            'total_sub_courts' => (int)($rc['total_sub_courts'] ?? 6),
            'status'           => $rc['status'],
            'image_url'        => $rc['image_url'] ?: $defaultImg,
        ];
    }

    // Kiểm tra xem bảng bookings đã có các cột voucher chưa
    $bCols = $pdo->query("SHOW COLUMNS FROM bookings")->fetchAll(PDO::FETCH_COLUMN, 0);
    $hasVoucherCols = in_array('owner_amount', $bCols, true);
    $voucherSelect = $hasVoucherCols
        ? 'b.court_amount, b.discount_amount, b.discount_funded_by, b.platform_fee, b.owner_amount,'
        : 'b.total_price AS court_amount, 0 AS discount_amount, NULL AS discount_funded_by, 0 AS platform_fee, b.total_price AS owner_amount,';

    // 2. Lấy danh sách đơn đặt thuộc phạm vi sân quản lý
    $bookingSql = "
        SELECT
            b.id AS booking_id,
            b.court_id,
            c.name AS court_name,
            u.name AS user_name,
            u.phone AS user_phone,
            b.total_price,
            b.deposit_amount,
            {$voucherSelect}
            b.status AS booking_status,
            b.created_at,
            GROUP_CONCAT(DISTINCT s.sub_court_name ORDER BY s.sub_court_name SEPARATOR ', ') AS sub_courts,
            MIN(s.slot_date) AS booking_date,
            MIN(s.start_time) AS start_time,
            MAX(s.end_time) AS end_time
        FROM bookings b
        JOIN courts c ON c.id = b.court_id
        JOIN users u ON u.id = b.user_id
        LEFT JOIN booking_slots bs ON bs.booking_id = b.id
        LEFT JOIN time_slots s ON s.id = bs.slot_id
    ";
    $bookingParams = [];
    if ($currentRole !== 'admin') {
        $bookingSql .= " WHERE c.owner_id = :owner_id";
        $bookingParams[':owner_id'] = $currentUserId;
    }
    $bookingSql .= " GROUP BY b.id ORDER BY b.created_at DESC, b.id DESC";

    $bStmt = $pdo->prepare($bookingSql);
    $bStmt->execute($bookingParams);
    $rawBookings = $bStmt->fetchAll();

    $todayStr       = date('Y-m-d');
    $mondayThisWeek = date('Y-m-d', strtotime('monday this week'));
    $sundayThisWeek = date('Y-m-d', strtotime('sunday this week'));

    $todayCount     = 0;
    $weekCount      = 0;
    $pendingCount   = 0;
    $cancelledCount = 0;
    $revenue        = 0;
    $recentBookings = [];

    foreach ($rawBookings as $idx => $rb) {
        $bDate  = $rb['booking_date'] ?: substr((string)$rb['created_at'], 0, 10);
        $status = $rb['booking_status'];
        $amount = (int)round((float)$rb['total_price']);

        if ($status !== 'cancelled') {
            if ($bDate === $todayStr) {
                $todayCount++;
            }
            if ($bDate >= $mondayThisWeek && $bDate <= $sundayThisWeek) {
                $weekCount++;
            }
        }

        if ($status === 'confirmed' || $status === 'pending') {
            $pendingCount++;
        } elseif ($status === 'cancelled') {
            $cancelledCount++;
        }

        if ($status === 'confirmed' || $status === 'completed') {
            if ($currentRole === 'admin') {
                // Doanh thu nền tảng: Phí sàn trừ đi tiền giảm do sàn chịu (nếu có)
                $platFee = (int)round((float)($rb['platform_fee'] ?? 0));
                $fundedBy = $rb['discount_funded_by'] ?? null;
                $discAmt = ($fundedBy === 'platform') ? (int)round((float)($rb['discount_amount'] ?? 0)) : 0;
                $revenue += ($platFee - $discAmt);
            } else {
                // Doanh thu chủ sân: Thực nhận của chủ sân (owner_amount), fallback về total_price nếu đơn cũ
                $ownerAmt = (isset($rb['owner_amount']) && (float)$rb['owner_amount'] > 0)
                    ? (int)round((float)$rb['owner_amount'])
                    : $amount;
                $revenue += $ownerAmt;
            }
        }

        if ($idx < 6) {
            $dateCode   = str_replace('-', '', $bDate);
            $startShort = $rb['start_time'] ? substr($rb['start_time'], 0, 5) : '--:--';
            $endShort   = $rb['end_time']   ? substr($rb['end_time'], 0, 5)   : '--:--';

            $recentBookings[] = [
                'id'               => (int)$rb['booking_id'],
                'booking_code'     => 'GDM-' . $dateCode . '-' . str_pad((string)$rb['booking_id'], 4, '0', STR_PAD_LEFT),
                'court_id'         => (int)$rb['court_id'],
                'court_name'       => $rb['court_name'],
                'sub_court_name'   => $rb['sub_courts'] ?: 'Thảm A1',
                'user_name'        => $rb['user_name'],
                'user_phone'       => $rb['user_phone'] ?? '',
                'booking_date'     => $bDate,
                'time_range'       => "{$startShort} - {$endShort}",
                'total_amount'     => $amount,
                'deposit_amount'   => (int)round((float)$rb['deposit_amount']),
                'booking_status'   => $status,
                'created_at'       => $rb['created_at'],
            ];
        }
    }

    $stats = [
        'totalCourts'       => count($courts),
        'activeCourts'      => $activeCourts,
        'todayCount'        => $todayCount,
        'weekCount'         => $weekCount,
        'totalBookings'     => count($rawBookings),
        'pendingCount'      => $pendingCount,
        'cancelledCount'    => $cancelledCount,
        'revenue'           => $revenue,
        'revenue_formatted' => number_format($revenue, 0, '', '.') . ' đ',
    ];

    jsonOut(200, [
        'status' => 'success',
        'data'   => [
            'stats'          => $stats,
            'recentBookings' => $recentBookings,
            'courts'         => $courts,
        ],
    ]);

} catch (Throwable $e) {
    error_log('[admin/dashboard.php] ' . $e->getMessage());
    jsonOut(500, ['status' => 'error', 'message' => 'Lỗi máy chủ khi tải thống kê: ' . $e->getMessage()]);
}
