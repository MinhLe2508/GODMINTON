# GODMINTON — Website Đặt Sân Cầu Lông Trực Tuyến

Nhóm 2, Đề tài 5 — Nguyễn Nhật Huy (52400198), Lê Nhật Minh (52400215), Trương Minh Triết (52400323).

Đây là khung thư mục PHP khởi tạo từ bộ giao diện tĩnh đã có (các trang `.html` được đổi
tên thành `.php`, nội dung giữ nguyên) cộng với các thư mục backend còn trống (`api/`,
`config/`, `includes/`, `database/`) để triển khai dần theo kế hoạch 4 tuần (6/10–31/10).

## 1. Cây thư mục

```
godminton/
├── index.php, courts.php, court-detail.php, booking.php,
│   confirm.php, payment.php, history.php, profile.php,
│   news.php, contact.php, auth.php              ← trang khách hàng (đổi từ .html)
├── admin-dashboard.php, admin-courts.php,
│   admin-slots.php, admin-bookings.php           ← trang quản trị (đổi từ .html)
│
├── assets/
│   ├── css/        ← nguyên vẹn từ bản tĩnh
│   ├── js/          ← nguyên vẹn từ bản tĩnh (mock-data.js sẽ bị thay dần bằng fetch tới api/)
│   └── img/
│
├── api/                      ← các endpoint AJAX trả JSON, mỗi file include config/db.php
│   ├── auth/                  (login, register, logout, change-password)
│   ├── admin/                 (dashboard, courts, slots, bookings)
│   ├── push/                  (subscribe, send — Tuần 4)
│   ├── courts.php, court-detail.php
│   ├── slots.php, hold.php, confirm.php, cancel.php   ★ lõi giữ chỗ 5 phút
│   ├── bookings.php, profile.php
│   └── news.php, contact.php
│
├── config/
│   └── db.php                ← kết nối PDO dùng chung, đọc từ .env
│
├── includes/
│   └── auth-check.php        ← include ở đầu trang cần đăng nhập
│
├── database/
│   ├── schema.sql             ← đúng schema đã thiết kế (9 bảng)
│   └── seed.sql                ← dữ liệu mẫu, khớp 3 tài khoản demo cũ
│
├── .env.example, .gitignore, composer.json
```

Thư mục tối đa 2 cấp (ví dụ `api/auth/login.php`), file PHP của từng trang khách hàng/
quản trị nằm thẳng ở gốc — đúng như kiểu cây đơn giản nhóm đã thống nhất, dễ theo dõi hơn
kiểu enterprise nhiều tầng.

**Lưu ý:** bản tĩnh trước đây có cả `login.html`/`register.html` lẫn `auth.html` (2 bản
trùng chức năng) — khung này chỉ giữ `auth.php` theo đề xuất gộp lại trước đó. Nếu nhóm vẫn
muốn giữ 2 trang riêng, chỉ cần copy lại `login.html`/`register.html` cũ và đổi đuôi `.php`.

## 2. Chạy trên XAMPP

1. Cài XAMPP (nếu chưa có), khởi động **Apache** và **MySQL** trong XAMPP Control Panel.
2. Copy toàn bộ thư mục này vào `C:\xampp\htdocs\godminton` (Windows) hoặc
   `/Applications/XAMPP/htdocs/godminton` (macOS).
3. Mở `http://localhost/phpmyadmin`, tạo database tên `badminton_booking`, import lần lượt
   `database/schema.sql` rồi `database/seed.sql`.
4. Copy `.env.example` thành `.env`, điền lại thông tin nếu MySQL XAMPP của bạn có đặt mật khẩu
   (mặc định XAMPP user `root` không có mật khẩu nên có thể để trống `DB_PASS`).
5. Mở `http://localhost/godminton/index.php` để kiểm tra.
6. Chạy `composer install` trong thư mục dự án để cài `minishlink/web-push` (cần cho Tuần 4).

## 3. Thứ tự code gợi ý

Theo đúng file timeline (`Timeline_DatSanCauLong_GODMINTON.xlsx`) đã lập:
Tuần 1 → `config/db.php` + `api/auth/*` + `api/courts.php` (Huy, Minh);
Tuần 2 → `api/slots.php`, `api/hold.php`, `api/confirm.php` (lõi giữ chỗ — ưu tiên số 1);
Tuần 3 → `api/admin/*`, `api/bookings.php`, `api/profile.php`;
Tuần 4 → `manifest.json`, `sw.js`, `api/push/*`.
