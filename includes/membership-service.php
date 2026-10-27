<?php
/**
 * includes/membership-service.php
 * Logic nghiệp vụ Quản lý & Kích hoạt Gói Hội Viên GODMINTON:
 * - Silver (Bạc): 5%
 * - Gold (Vàng): 10%
 * - Platinum (Bạch Kim): 15%
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/payment-service.php';

class MembershipError extends RuntimeException {
    public function __construct(
        public int $httpCode,
        public string $errorCode,
        string $message,
        public array $meta = []
    ) {
        parent::__construct($message);
    }
}

/**
 * Tự động chuyển các gói hội viên đã qua 23h59p ngày kết thúc sang trạng thái 'expired'
 */
function membership_sync_expired(PDO $pdo): int {
    $stmt = $pdo->prepare("
        UPDATE user_memberships 
        SET status = 'expired', updated_at = NOW() 
        WHERE status = 'active' AND end_date < NOW()
    ");
    $stmt->execute();
    return $stmt->rowCount();
}

/**
 * Lấy gói hội viên đang có hiệu lực của người dùng (nếu có)
 */
function membership_get_active_for_user(PDO $pdo, int $userId): ?array {
    if ($userId <= 0) return null;

    // Tự động đồng bộ trạng thái hết hạn sau 23h59p
    membership_sync_expired($pdo);

    $stmt = $pdo->prepare("
        SELECT um.*, p.title AS package_title, p.slug AS package_slug, p.cover_image, p.benefits
        FROM user_memberships um
        JOIN posts p ON p.id = um.post_id
        WHERE um.user_id = ?
          AND um.status = 'active'
          AND um.end_date >= NOW()
        ORDER BY um.discount_percent DESC, um.end_date DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) return null;

    // Tính số ngày còn lại
    $now = time();
    $end = strtotime($row['end_date']);
    $daysLeft = max(0, (int)ceil(($end - $now) / 86400));
    $row['days_left'] = $daysLeft;

    return $row;
}

/**
 * Lấy gói hội viên đang chờ thanh toán / chờ admin xác nhận (pending) của người dùng
 */
function membership_get_pending_for_user(PDO $pdo, int $userId): ?array {
    if ($userId <= 0) return null;

    payment_expire_overdue($pdo, 20);

    $stmt = $pdo->prepare("
        SELECT um.*, p.title AS package_title, p.slug AS package_slug, p.cover_image
        FROM user_memberships um
        JOIN posts p ON p.id = um.post_id
        WHERE um.user_id = ?
          AND um.status = 'pending'
        ORDER BY um.id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $row['tier_label'] = match($row['tier']) {
        'platinum' => 'Bạch Kim (15%)',
        'gold'     => 'Vàng (10%)',
        'silver'   => 'Bạc (5%)',
        default    => 'Hội Viên'
    };

    $pay = payment_fetch_by_membership($pdo, (int)$row['id']);
    $row['payment'] = $pay ? payment_public_data($pay) : null;

    return $row;
}

/**
 * Lấy lịch sử các gói hội viên đã đăng ký của người dùng
 */
function membership_get_history(PDO $pdo, int $userId): array {
    if ($userId <= 0) return [];

    $stmt = $pdo->prepare("
        SELECT um.*, p.title AS package_title, p.slug AS package_slug
        FROM user_memberships um
        JOIN posts p ON p.id = um.post_id
        WHERE um.user_id = ?
        ORDER BY um.id DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll() ?: [];
}

/**
 * Lấy chi tiết gói hội viên từ posts
 */
function membership_get_package_by_id(PDO $pdo, int $postId): ?array {
    if ($postId <= 0) return null;

    $stmt = $pdo->prepare("
        SELECT * FROM posts
        WHERE id = ? AND type = 'membership' AND status = 'published'
    ");
    $stmt->execute([$postId]);
    $pkg = $stmt->fetch();
    return $pkg ?: null;
}

/**
 * Đăng ký hoặc gia hạn gói hội viên cho khách hàng (khởi tạo phiên thanh toán SePay VietQR tự động)
 */
function membership_subscribe(PDO $pdo, int $userId, int $postId, string $paymentMethod = 'VietQR'): array {
    if ($userId <= 0) {
        throw new MembershipError(401, 'UNAUTHORIZED', 'Vui lòng đăng nhập để đăng ký gói hội viên.');
    }

    // Kiểm tra tài khoản người dùng
    $userStmt = $pdo->prepare("SELECT id, name, email, role, account_status, is_verified, deleted_at FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    if (!$user || $user['account_status'] !== 'active' || $user['deleted_at'] !== null) {
        throw new MembershipError(403, 'ACCOUNT_INACTIVE', 'Tài khoản người dùng không hoạt động.');
    }

    // Lấy thông tin gói
    $pkg = membership_get_package_by_id($pdo, $postId);
    if (!$pkg) {
        throw new MembershipError(404, 'PACKAGE_NOT_FOUND', 'Gói hội viên không tồn tại hoặc đã ngừng cung cấp.');
    }

    $price = (float)($pkg['price'] ?? 0);
    $durationDays = (int)($pkg['duration_days'] ?? 30);
    if ($durationDays <= 0) $durationDays = 30;

    $discountPercent = (int)($pkg['discount_percent'] ?? 0);
    if ($discountPercent <= 0) {
        $slug = $pkg['slug'] ?? '';
        if (str_contains($slug, 'platinum')) $discountPercent = 15;
        elseif (str_contains($slug, 'gold') || str_contains($slug, 'golden')) $discountPercent = 10;
        elseif (str_contains($slug, 'silver')) $discountPercent = 5;
        else $discountPercent = 5;
    }

    // Xác định tier
    if ($discountPercent >= 15) {
        $tier = 'platinum';
    } elseif ($discountPercent >= 10) {
        $tier = 'gold';
    } else {
        $tier = 'silver';
    }

    payment_ensure_schema($pdo);
    payment_expire_overdue($pdo, 20);

    // Kiểm tra gói đang kích hoạt của user
    $active = membership_get_active_for_user($pdo, $userId);

    $now = date('Y-m-d H:i:s');
    $startDate = $now;
    $isExtend = ($active && $active['tier'] === $tier);

    // Kiểm tra xem user đã có phiên thanh toán pending nào chưa
    $pending = membership_get_pending_for_user($pdo, $userId);
    if ($pending) {
        $pendingId = (int)$pending['id'];
        $existingPay = payment_fetch_by_membership($pdo, $pendingId);
        // Nếu cùng gói và phiên thanh toán SePay vẫn còn hạn -> trả về luôn phiên thanh toán hiện tại để khách quét tiếp
        if ((int)$pending['post_id'] === (int)$pkg['id'] && $existingPay && $existingPay['status'] === 'pending' && (int)$existingPay['expires_in_seconds'] > 0) {
            return [
                'membership_id'    => $pendingId,
                'membership_code'  => 'MB-' . str_pad((string)$pendingId, 6, '0', STR_PAD_LEFT),
                'package_id'       => (int)$pkg['id'],
                'package_title'    => $pkg['title'],
                'tier'             => $tier,
                'discount_percent' => $discountPercent,
                'duration_days'    => $durationDays,
                'price_paid'       => (float)$pending['price_paid'],
                'start_date'       => $pending['start_date'],
                'end_date'         => $pending['end_date'],
                'status'           => 'pending',
                'is_extend'        => $isExtend,
                'payment'          => payment_public_data($existingPay),
                'message'          => 'Vui lòng quét mã VietQR để hoàn tất kích hoạt gói hội viên.'
            ];
        }
        // Ngược lại (khách đổi sang gói khác hoặc phiên cũ đã hết hạn) -> hủy yêu cầu pending cũ để tạo mã QR mới
        $pdo->prepare("UPDATE user_memberships SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND status = 'pending'")->execute([$pendingId]);
        if (payment_tables_ready($pdo)) {
            $pdo->prepare("UPDATE payments SET status = 'cancelled' WHERE membership_id = ? AND status = 'pending'")->execute([$pendingId]);
        }
    }

    // Tính hạn dự kiến (sẽ được chốt chính xác khi thanh toán thành công, hết hạn vào 23:59:59 ngày cuối cùng)
    if ($isExtend) {
        $currentEnd = strtotime($active['end_date']);
        $baseTime = ($currentEnd > time()) ? $currentEnd : time();
        $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", $baseTime));
        $endDate = $targetDate . ' 23:59:59';
    } else {
        $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", time()));
        $endDate = $targetDate . ' 23:59:59';
    }

    // Tạo bản ghi user_memberships với status = 'pending'
    $stmt = $pdo->prepare("
        INSERT INTO user_memberships (
            user_id, post_id, tier, discount_percent, price_paid, duration_days,
            start_date, end_date, status, payment_method, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
    ");

    $stmt->execute([
        $userId,
        $pkg['id'],
        $tier,
        $discountPercent,
        $price,
        $durationDays,
        $startDate,
        $endDate,
        'VietQR (SePay 24/7)'
    ]);

    $membershipId = (int)$pdo->lastInsertId();

    // Tạo phiên thanh toán SePay VietQR cho gói hội viên
    $paymentData = null;
    if (payment_tables_ready($pdo)) {
        $paymentCode = payment_code_for_membership($membershipId);
        $timeout = payment_config()['timeout'];
        $payStmt = $pdo->prepare("
            INSERT INTO payments (booking_id, membership_id, payment_code, amount, paid_amount, status, expires_at)
            VALUES (NULL, ?, ?, ?, 0, 'pending', DATE_ADD(NOW(), INTERVAL ? MINUTE))
        ");
        $payStmt->execute([$membershipId, $paymentCode, (int)round($price), $timeout]);
        $payRow = payment_fetch_by_membership($pdo, $membershipId);
        if ($payRow) {
            $paymentData = payment_public_data($payRow);
        }
    }

    return [
        'membership_id'    => $membershipId,
        'membership_code'  => 'MB-' . str_pad((string)$membershipId, 6, '0', STR_PAD_LEFT),
        'package_id'       => (int)$pkg['id'],
        'package_title'    => $pkg['title'],
        'tier'             => $tier,
        'discount_percent' => $discountPercent,
        'duration_days'    => $durationDays,
        'price_paid'       => $price,
        'start_date'       => $startDate,
        'end_date'         => $endDate,
        'status'           => 'pending',
        'is_extend'        => $isExtend,
        'payment'          => $paymentData,
        'message'          => 'Đã khởi tạo mã thanh toán VietQR. Gói hội viên sẽ tự động kích hoạt ngay sau khi nhận thanh toán.'
    ];
}

/**
 * Quản trị viên phê duyệt (kích hoạt) gói hội viên
 */
function membership_approve(PDO $pdo, int $membershipId): array {
    $stmt = $pdo->prepare("
        SELECT um.*, p.title AS package_title, u.name AS user_name, u.email AS user_email
        FROM user_memberships um
        JOIN posts p ON p.id = um.post_id
        JOIN users u ON u.id = um.user_id
        WHERE um.id = ?
    ");
    $stmt->execute([$membershipId]);
    $mem = $stmt->fetch();
    if (!$mem) {
        throw new MembershipError(404, 'NOT_FOUND', 'Không tìm thấy yêu cầu đăng ký gói hội viên.');
    }
    if ($mem['status'] !== 'pending') {
        throw new MembershipError(400, 'INVALID_STATUS', 'Yêu cầu này không ở trạng thái chờ duyệt (Trạng thái hiện tại: ' . $mem['status'] . ').');
    }

    $userId = (int)$mem['user_id'];
    $tier = $mem['tier'];
    $durationDays = (int)$mem['duration_days'];
    $now = date('Y-m-d H:i:s');

    // Kiểm tra gói active hiện tại của user
    $active = membership_get_active_for_user($pdo, $userId);

    if ($active) {
        if ($active['tier'] === $tier) {
            // Gia hạn cùng hạng: cộng dồn thời hạn
            $curEnd = strtotime($active['end_date']);
            $baseTime = ($curEnd > time()) ? $curEnd : time();
            $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", $baseTime));
            $newEnd = $targetDate . ' 23:59:59';
            $newStart = $active['start_date'];
        } else {
            // Đổi hạng: hủy gói cũ, tính mới từ NOW()
            $pdo->prepare("UPDATE user_memberships SET status = 'cancelled' WHERE id = ?")->execute([$active['id']]);
            $newStart = $now;
            $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", time()));
            $newEnd = $targetDate . ' 23:59:59';
        }
    } else {
        $newStart = $now;
        $targetDate = date('Y-m-d', strtotime("+{$durationDays} days", time()));
        $newEnd = $targetDate . ' 23:59:59';
    }

    $upd = $pdo->prepare("
        UPDATE user_memberships 
        SET start_date = ?, end_date = ?, status = 'active', updated_at = NOW() 
        WHERE id = ?
    ");
    $upd->execute([$newStart, $newEnd, $membershipId]);

    if (payment_tables_ready($pdo)) {
        $pdo->prepare("UPDATE payments SET status = 'paid', paid_amount = amount, paid_at = COALESCE(paid_at, NOW()) WHERE membership_id = ? AND status = 'pending'")->execute([$membershipId]);
    }

    return [
        'id'            => $membershipId,
        'user_name'     => $mem['user_name'],
        'package_title' => $mem['package_title'],
        'tier'          => $mem['tier'],
        'start_date'    => $newStart,
        'end_date'      => $newEnd,
        'status'        => 'active',
        'message'       => "Đã duyệt và kích hoạt thành công Gói {$mem['package_title']} cho thành viên {$mem['user_name']}!"
    ];
}

/**
 * Quản trị viên từ chối yêu cầu gói hội viên
 */
function membership_reject(PDO $pdo, int $membershipId, string $reason = ''): array {
    $stmt = $pdo->prepare("
        SELECT um.*, p.title AS package_title, u.name AS user_name
        FROM user_memberships um
        JOIN posts p ON p.id = um.post_id
        JOIN users u ON u.id = um.user_id
        WHERE um.id = ?
    ");
    $stmt->execute([$membershipId]);
    $mem = $stmt->fetch();
    if (!$mem) {
        throw new MembershipError(404, 'NOT_FOUND', 'Không tìm thấy yêu cầu đăng ký gói hội viên.');
    }
    if ($mem['status'] !== 'pending') {
        throw new MembershipError(400, 'INVALID_STATUS', 'Yêu cầu này không ở trạng thái chờ duyệt.');
    }

    $pdo->prepare("UPDATE user_memberships SET status = 'rejected', updated_at = NOW() WHERE id = ?")->execute([$membershipId]);
    if (payment_tables_ready($pdo)) {
        $pdo->prepare("UPDATE payments SET status = 'cancelled' WHERE membership_id = ? AND status = 'pending'")->execute([$membershipId]);
    }

    return [
        'id'            => $membershipId,
        'user_name'     => $mem['user_name'],
        'package_title' => $mem['package_title'],
        'status'        => 'rejected',
        'message'       => "Đã từ chối yêu cầu đăng ký của thành viên {$mem['user_name']}."
    ];
}

/**
 * Tính toán chiết khấu tiền sân từ Gói hội viên
 */
function membership_calculate_discount(int $courtAmount, ?array $activeMembership): array {
    $courtAmount = max(0, $courtAmount);

    if (!$activeMembership || empty($activeMembership['discount_percent'])) {
        return [
            'membership_id'            => null,
            'tier'                     => null,
            'discount_percent'         => 0,
            'membership_discount_amount' => 0
        ];
    }

    $percent = (int)$activeMembership['discount_percent'];
    $discount = (int)round(($courtAmount * $percent) / 100);
    $discount = min($courtAmount, max(0, $discount));

    return [
        'membership_id'              => (int)$activeMembership['id'],
        'tier'                       => $activeMembership['tier'],
        'tier_label'                 => match($activeMembership['tier']) {
            'platinum' => 'Bạch Kim (15%)',
            'gold'     => 'Vàng (10%)',
            'silver'   => 'Bạc (5%)',
            default    => 'Hội Viên'
        },
        'discount_percent'           => $percent,
        'membership_discount_amount' => $discount
    ];
}
