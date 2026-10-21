-- database/seed_vouchers.sql
-- Seed dữ liệu mã giảm giá khởi tạo WELCOME15
-- Nền tảng chịu (funded_by = 'platform'), 15%, tối đa 50.000đ, chỉ áp dụng cho đơn đầu tiên.

INSERT INTO `vouchers` (
  `code`,
  `created_by`,
  `owner_id`,
  `court_id`,
  `discount_percent`,
  `max_discount`,
  `min_order`,
  `start_at`,
  `end_at`,
  `usage_limit`,
  `used_count`,
  `per_user_limit`,
  `first_booking_only`,
  `funded_by`,
  `status`
)
SELECT
  'WELCOME15',
  COALESCE((SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1), 1),
  NULL,
  NULL,
  15,
  50000,
  0,
  '2026-01-01 00:00:00',
  '2027-12-31 23:59:59',
  NULL,
  0,
  1,
  1,
  'platform',
  'active'
FROM DUAL
WHERE NOT EXISTS (
  SELECT 1 FROM `vouchers` WHERE `code` = 'WELCOME15'
);
