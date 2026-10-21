-- Chỉ giữ 41 sân thuê được đang hoạt động; ẩn mọi sân khác trong danh mục gốc (ID 1-117):
-- sân trùng cũ, sân trong trường, nhà thi đấu công, sân nghi vấn và sân đã đóng cửa.
-- Chạy trong phpMyAdmin sau khi chọn database badminton_booking. Có thể chạy lại nhiều lần.
-- Không xóa sân và không đụng tới lịch sử đặt sân; chỉ đổi trạng thái sang 'inactive'.
-- Sân mới do nhóm thêm sau này (ID lớn hơn 117) không bị ảnh hưởng.
UPDATE `courts`
SET `status` = 'inactive'
WHERE `id` BETWEEN 1 AND 117
  AND `id` NOT IN (2,7,22,24,30,42,65,68,69,70,74,75,76,83,84,85,86,87,88,91,92,93,98,99,100,101,102,103,104,105,106,107,108,109,110,111,112,113,115,116,117);
