<?php
/**
 * tests/test_voucher_logic.php
 * Script kiểm thử tự động các quy tắc nghiệp vụ mã giảm giá (Vouchers).
 * Chạy CLI: php tests/test_voucher_logic.php
 */

if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../includes/voucher-service.php';

echo "========================================================\n";
echo "BẮT ĐẦU KIỂM THỬ NGHIỆP VỤ VOUCHER (GODMINTON)\n";
echo "========================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $name, bool $condition, string $detail = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$name}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$name}" . ($detail ? " -> {$detail}" : "") . "\n";
    }
}

// -------------------------------------------------------------
// 1. Kiểm thử công thức tính tiền chuẩn theo Mục 2 của tài liệu
// -------------------------------------------------------------
echo "1. Kiểm thử công thức tính tiền:\n";

// Case 1.1: Đơn 200.000đ, giảm 15%, max_discount 50.000đ, platform funded
$voucherPlatform = [
    'id'               => 1,
    'code'             => 'WELCOME15',
    'discount_percent' => 15,
    'max_discount'     => 50000,
    'funded_by'        => 'platform'
];

$res1 = voucher_calculate_pricing(200000, $voucherPlatform);
assertTest('Tiền sân gốc = 200.000đ', $res1['court_amount'] === 200000);
assertTest('Mức giảm 15% của 200.000đ = 30.000đ', $res1['discount_amount'] === 30000, "Thực tế: {$res1['discount_amount']}");
// Sau giảm: 200.000 - 30.000 = 170.000đ. Phí 5% = 8.500đ.
assertTest('Phí nền tảng 5% = 8.500đ', $res1['platform_fee'] === 8500, "Thực tế: {$res1['platform_fee']}");
// Tổng khách trả: 170.000 + 8.500 = 178.500đ
assertTest('Tổng khách trả = 178.500đ', $res1['total_amount'] === 178500, "Thực tế: {$res1['total_amount']}");
// Cọc 50%: 178.500 / 2 = 89.250đ
assertTest('Cọc 50% = 89.250đ', $res1['deposit_amount'] === 89250, "Thực tế: {$res1['deposit_amount']}");
// Nền tảng chịu -> Chủ sân nhận đủ giá gốc 200.000đ
assertTest('Chủ sân nhận đủ (funded_by=platform) = 200.000đ', $res1['owner_amount'] === 200000, "Thực tế: {$res1['owner_amount']}");

// Case 1.2: Cùng đơn trên nhưng chủ sân chịu (funded_by = 'owner')
$voucherOwner = [
    'id'               => 2,
    'code'             => 'OWNER15',
    'discount_percent' => 15,
    'max_discount'     => 50000,
    'funded_by'        => 'owner'
];
$res2 = voucher_calculate_pricing(200000, $voucherOwner);
assertTest('Chủ sân chịu (funded_by=owner) = 170.000đ', $res2['owner_amount'] === 170000, "Thực tế: {$res2['owner_amount']}");

// Case 1.3: Chặn trần max_discount (Đơn 500.000đ, 15% = 75.000đ nhưng trần 50.000đ)
$res3 = voucher_calculate_pricing(500000, $voucherPlatform);
assertTest('Chặn trần max_discount = 50.000đ (thay vì 75.000đ)', $res3['discount_amount'] === 50000, "Thực tế: {$res3['discount_amount']}");

// Case 1.4: Đơn không dùng voucher (Hồi quy)
$resNoVoucher = voucher_calculate_pricing(200000, null);
assertTest('Đơn không dùng mã: discount = 0', $resNoVoucher['discount_amount'] === 0);
assertTest('Đơn không dùng mã: fee = 0', $resNoVoucher['platform_fee'] === 0);
assertTest('Đơn không dùng mã: total = 200.000đ', $resNoVoucher['total_amount'] === 200000);
assertTest('Đơn không dùng mã: deposit = 100.000đ', $resNoVoucher['deposit_amount'] === 100000);
assertTest('Đơn không dùng mã: owner_amount = 200.000đ', $resNoVoucher['owner_amount'] === 200000);

// -------------------------------------------------------------
// 2. Kiểm thử mã lỗi chuẩn theo tài liệu (Mocking DB)
// -------------------------------------------------------------
echo "\n2. Kiểm thử điều kiện hợp lệ và mã lỗi chuẩn:\n";

// Case 2.1: Để trống mã -> EMPTY_CODE
try {
    voucher_validate_and_get($pdo, '', 1, 1, 200000);
    assertTest('Để trống mã ném lỗi', false);
} catch (VoucherError $e) {
    assertTest('Để trống mã trả mã lỗi EMPTY_CODE', $e->errorCode === 'EMPTY_CODE');
    assertTest('Để trống mã trả thông báo chuẩn', $e->getMessage() === 'Vui lòng nhập mã giảm giá.');
}

// Case 2.2: Nhập mã không tồn tại -> VOUCHER_NOT_FOUND
try {
    voucher_clear_failure($pdo, 1);
    voucher_validate_and_get($pdo, 'MA_KHONG_TON_TAI_XYZ_999', 1, 1, 200000);
    assertTest('Mã không tồn tại ném lỗi', false);
} catch (VoucherError $e) {
    assertTest('Mã không tồn tại trả mã lỗi VOUCHER_NOT_FOUND', $e->errorCode === 'VOUCHER_NOT_FOUND');
    assertTest('Mã không tồn tại trả thông báo chuẩn', $e->getMessage() === 'Mã giảm giá không đúng. Vui lòng kiểm tra lại.');
}

// Case 2.3: Mã WELCOME15 có thật trong DB
try {
    // Tìm 1 court_id thật trong DB
    $courtId = (int)$pdo->query('SELECT id FROM courts LIMIT 1')->fetchColumn() ?: 1;
    $vWelcome = voucher_validate_and_get($pdo, 'welcome15', 0, $courtId, 200000);
    assertTest('Tìm thấy mã WELCOME15 trong DB (không phân biệt hoa thường)', $vWelcome['code'] === 'WELCOME15');
    assertTest('WELCOME15 giảm 15%', (int)$vWelcome['discount_percent'] === 15);
    assertTest('WELCOME15 giảm tối đa 50.000đ', (int)$vWelcome['max_discount'] === 50000);
    assertTest('WELCOME15 do platform tài trợ', $vWelcome['funded_by'] === 'platform');
} catch (VoucherError $e) {
    assertTest('Mã WELCOME15 tồn tại trong DB', false, $e->getMessage());
}

// Case 2.4: Kiểm thử Rate limit (nhập sai quá 5 lần)
try {
    $dummyUserId = 999999;
    for ($i = 0; $i < 6; $i++) {
        try {
            voucher_validate_and_get($pdo, 'WRONG_CODE_' . $i, $dummyUserId, 1, 200000);
        } catch (VoucherError $ve) {
            if ($ve->errorCode === 'VOUCHER_RATE_LIMIT') {
                throw $ve;
            }
        }
    }
    assertTest('Rate limit chặn dò mã khi thử sai quá 5 lần', false);
} catch (VoucherError $e) {
    assertTest('Rate limit kích hoạt mã lỗi VOUCHER_RATE_LIMIT', $e->errorCode === 'VOUCHER_RATE_LIMIT');
    voucher_clear_failure($pdo, 999999); // dọn dẹp sau test
}

// -------------------------------------------------------------
// 3. Tổng kết
// -------------------------------------------------------------
echo "\n========================================================\n";
echo "KẾT QUẢ KIỂM THỬ: {$passCount} PASSED, {$failCount} FAILED\n";
echo "========================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
