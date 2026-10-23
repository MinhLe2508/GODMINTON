<?php
/**
 * includes/membership-service.php
 * Logic nghiệp vụ Quản lý & Kích hoạt Gói Hội Viên GODMINTON:
 * - Silver (Bạc): 5%
 * - Gold (Vàng): 10%
 * - Platinum (Bạch Kim): 15%
 */

require_once __DIR__ . '/../config/db.php';

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
 * Lấy gói hội viên đang có hiệu lực của người dùng (nếu có)
 */
function membership_get_active_for_user(PDO $pdo, int $userId): ?array {
    if ($userId <= 0) return null;

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
 * Đăng ký hoặc gia hạn gói hội viên cho khách hàng
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
        // Tự động nhận diện theo tên hoặc slug nếu discount_percent chưa gán
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

    // Kiểm tra gói đang kích hoạt của user
    $active = membership_get_active_for_user($pdo, $userId);

    $now = date('Y-m-d H:i:s');
    $startDate = $now;
    $isExtend = false;

    if ($active) {
        if ($active['tier'] === $tier) {
            // Gia hạn gói cùng hạng: cộng dồn thời hạn
            $isExtend = true;
            $currentEnd = strtotime($active['end_date']);
            $baseTime = ($currentEnd > time()) ? $currentEnd : time();
            $endDate = date('Y-m-d H:i:s', strtotime("+{$durationDays} days", $baseTime));
        } else {
            // Đổi hạng gói: tính từ thời điểm hiện tại
            $endDate = date('Y-m-d H:i:s', strtotime("+{$durationDays} days", time()));
            // Hủy gói cũ thấp hơn hoặc gói trước đó
            $pdo->prepare("UPDATE user_memberships SET status = 'cancelled' WHERE id = ?")->execute([$active['id']]);
        }
    } else {
        $endDate = date('Y-m-d H:i:s', strtotime("+{$durationDays} days", time()));
    }

    // Tạo bản ghi user_memberships
    $stmt = $pdo->prepare("
        INSERT INTO user_memberships (
            user_id, post_id, tier, discount_percent, price_paid, duration_days,
            start_date, end_date, status, payment_method, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())
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
        $paymentMethod
    ]);

    $membershipId = (int)$pdo->lastInsertId();

    return [
        'membership_id'    => $membershipId,
        'package_id'       => (int)$pkg['id'],
        'package_title'    => $pkg['title'],
        'tier'             => $tier,
        'discount_percent' => $discountPercent,
        'duration_days'    => $durationDays,
        'price_paid'       => $price,
        'start_date'       => $startDate,
        'end_date'         => $endDate,
        'status'           => 'active',
        'is_extend'        => $isExtend,
        'message'          => $isExtend ? "Đã gia hạn thành công thêm {$durationDays} ngày!" : "Đã kích hoạt thành công Gói {$tier} ({$discountPercent}%)!"
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
