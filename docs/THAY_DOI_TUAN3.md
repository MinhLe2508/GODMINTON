# Danh sách thay đổi từ ZIP gốc

Các file dưới đây cần tích hợp có chọn lọc vào nhánh feature từ project. README và docs của bản này cũng là tài liệu mới/sửa. Không chép .env hoặc cả bản sao dự án vào repo.

| Loại | File |
| --- | --- |
| Sửa | `.env.example` |
| Sửa | `admin-bookings.php` |
| Sửa | `api/admin/bookings.php` |
| Sửa | `api/auth/change-password.php` |
| Mới | `api/auth/me.php` |
| Sửa | `api/bookings.php` |
| Sửa | `api/cancel.php` |
| Sửa | `api/hold.php` |
| Sửa | `api/profile.php` |
| Sửa | `api/slots.php` |
| Mới | `assets/js/admin-bookings.js` |
| Sửa | `assets/js/app.js` |
| Sửa | `assets/js/booking.js` |
| Sửa | `assets/js/confirm.js` |
| Sửa | `assets/js/court_detail.js` |
| Sửa | `assets/js/courts.js` |
| Sửa | `assets/js/history.js` |
| Sửa | `assets/js/index_explore_dt.js` |
| Sửa | `assets/js/payment.js` |
| Sửa | `assets/js/profile.js` |
| Mới | `assets/js/booking-account-client.js` |
| Sửa | `booking.php` |
| Sửa | `config/db.php` |
| Sửa | `confirm.php` |
| Sửa | `court-detail.php` |
| Sửa | `courts.php` |
| Mới | `database/migrate_booking_metadata.php` |
| Sửa | `history.php` |
| Mới | `includes/booking-service.php` |
| Mới | `includes/booking-account-api.php` |
| Sửa | `index.php` |
| Sửa | `payment.php` |
| Sửa | `profile.php` |
| Sửa | `tests/oversell_test.php` |
| Mới | `tests/check_booking_integrity.php` |

booking.js đã bỏ engine LocalStorage cũ vì booking.php dùng calendar_booking.js; file được giữ với ghi chú để tránh nhầm. index_explore_dt.js chỉ giữ feed khám phá tĩnh minh họa, carousel sân dùng API trong index.php. Các phần news/contact/admin của Huy/Triết giữ nguyên theo nguồn trừ thay đổi shared app.js.

Các file api/hold.php, api/slots.php, api/auth/change-password.php, config/db.php và shared app.js cần review phối hợp Huy/Triết trước khi merge. API cancel và booking-service của Minh có chính sách hủy/hoàn thành ghi trong hướng dẫn.
