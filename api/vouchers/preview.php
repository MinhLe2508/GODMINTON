<?php

/**
 * api/vouchers/preview.php
 * POST kiểm tra và tính thử số tiền khi áp dụng mã giảm giá.
 * Tuyệt đối không ghi dữ liệu, bỏ qua mọi số tiền từ client, tự tính từ DB.
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';
require_once __DIR__ . '/../../includes/voucher-service.php';

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        booking_account_out(405, null, 'Chỉ chấp nhận phương thức POST.');
    }

    // Xác thực người dùng (trang thanh toán đã qua auth-check)
    $user = booking_account_user(false);
    $userId = (int) $user['id'];

    $in = booking_account_input();
    $code = isset($in['code']) ? trim((string) $in['code']) : '';
    $courtId = isset($in['court_id']) ? booking_account_id($in['court_id']) : 0;
    $slotIds = $in['slot_ids'] ?? (isset($in['slot_id']) ? [$in['slot_id']] : []);

    if (!is_array($slotIds) || empty($slotIds)) {
        throw new VoucherError(422, 'EMPTY_SLOTS', 'Vui lòng chọn khung giờ để tính giá.');
    }

    $slotIds = array_values(array_unique(array_map('booking_account_id', $slotIds)));
    sort($slotIds);

    // Tính tiền sân gốc từ DB theo các slots
    $inClause = implode(',', array_fill(0, count($slotIds), '?'));
    $stmt = $pdo->prepare("
        SELECT s.id, s.court_id, s.start_time, c.price_per_hour
        FROM time_slots s
        JOIN courts c ON c.id = s.court_id
        WHERE s.id IN ($inClause)
    ");
    $stmt->execute($slotIds);
    $slots = $stmt->fetchAll();

    if (count($slots) !== count($slotIds)) {
        throw new VoucherError(404, 'SLOT_NOT_FOUND', 'Không tìm thấy đầy đủ khung giờ đã chọn.');
    }

    $courtAmount = 0;
    foreach ($slots as $slot) {
        if ($courtId > 0 && (int) $slot['court_id'] !== $courtId) {
            throw new VoucherError(422, 'COURT_MISMATCH', 'Khung giờ không khớp với sân đã chọn.');
        }

        $h = (int) substr($slot['start_time'], 0, 2);
        $courtAmount += (int) round((float) $slot['price_per_hour'] / 2) + (($h >= 17 && $h <= 21) ? 7500 : 0);
    }

    require_once __DIR__ . '/../../includes/membership-service.php';
    $activeMembership = membership_get_active_for_user($pdo, $userId);

    // Kiểm tra và lấy voucher
    $voucher = voucher_validate_and_get($pdo, $code, $userId, $courtId, $courtAmount, false);

    // Tính toán chi tiết tiền
    $pricing = voucher_calculate_pricing($courtAmount, $voucher, $activeMembership);

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'ok' => true,
        'data' => [
            'voucher' => [
                'id' => (int) $voucher['id'],
                'code' => strtoupper(trim($voucher['code'])),
                'discount_percent' => (int) $voucher['discount_percent'],
                'max_discount' => $voucher['max_discount'] !== null ? (int) $voucher['max_discount'] : null,
                'funded_by' => $voucher['funded_by'],
            ],
            'membership' => $activeMembership ? [
                'id' => (int) $activeMembership['id'],
                'tier' => $activeMembership['tier'],
                'discount_percent' => (int) $activeMembership['discount_percent'],
                'package_title' => $activeMembership['package_title'],
                'discount_amount' => $pricing['membership_discount_amount'],
            ] : null,
            'pricing' => $pricing,
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
} catch (VoucherError $e) {
    http_response_code($e->httpCode);
    echo json_encode([
        'status' => 'error',
        'ok' => false,
        'code' => $e->errorCode,
        'message' => $e->getMessage(),
        'meta' => (object) $e->meta,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
} catch (Throwable $e) {
    error_log('[preview.php] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'ok' => false,
        'code' => 'INTERNAL_ERROR',
        'message' => 'Lỗi máy chủ khi kiểm tra mã giảm giá.',
        'meta' => (object) [],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
