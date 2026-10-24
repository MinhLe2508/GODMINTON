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
│   ├── schema.sql             ← cấu trúc hiện tại (12 bảng)
│   └── seed.sql                ← danh mục hiện tại và tài khoản phát triển
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
6. Chạy `composer install --no-dev --prefer-dist --optimize-autoloader` để cài thư viện Web Push và tạo Composer autoload. PHPMailer được đóng gói tại `vendor/PHPMailer`; không cần TCPDF.
7. Điền `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY` trong `.env`. Trên XAMPP PowerShell, tạo cặp khóa bằng `$env:OPENSSL_CONF='D:\xampp\apache\conf\openssl.cnf'; php tools/generate-vapid-keys.php`; lưu hai dòng key vào `.env`, không commit private key.

## 3. Thứ tự code gợi ý

Theo đúng file timeline (`Timeline_DatSanCauLong_GODMINTON.xlsx`) đã lập:
Tuần 1 → `config/db.php` + `api/auth/*` + `api/courts.php` (Huy, Minh);
Tuần 2 → `api/slots.php`, `api/hold.php`, `api/confirm.php` (lõi giữ chỗ — ưu tiên số 1);
Tuần 3 → `api/admin/*`, `api/bookings.php`, `api/profile.php`;
Tuần 4 → `manifest.json`, `sw.js`, `api/push/*`.

## 4. Tuần 2 — Giữ chỗ 5 phút (Huy: task 7, 8, 11)

1. `api/slots.php` (Minh) **tự sinh** khung giờ cho ngày chưa có slot (4 thảm A1–A4, 05:30–23:30) nên **không cần seed slot thủ công**.
   DB cài mới đã có đủ cột. Với DB cũ, xem [hướng dẫn database](database/README.md); không dùng script reset dữ liệu để sửa lỗi.
2. `api/hold.php` — giữ chỗ 5 phút bằng transaction + `SELECT ... FOR UPDATE`. Giao diện (đúng với `calendar_booking.js`):
   `{"slot_ids":[101,102],"action":"hold"}` (hoặc `{"slot_id":101}`), `{"action":"release","slot_ids":[...]}`.
   Mã trả về: 200 thành công · 401 chưa đăng nhập · 404 không thấy slot · 409 đã bị người khác giữ/đã đặt ·
   422 thiếu/sai tham số (hoặc các ô khác sân/khác ngày).
3. `api/cleanup-expired.php` — trả slot `holding` đã hết hạn về `available`. Dùng được 3 cách:
   - Linux (cron, mỗi phút): `* * * * * php /đường/dẫn/api/cleanup-expired.php`
   - Windows/XAMPP (Task Scheduler, lặp mỗi 1 phút): chạy `C:\xampp\php\php.exe <đường dẫn file>`
   - Gọi trong PHP: `require_once __DIR__ . '/cleanup-expired.php'; cleanup_expired_holds($pdo);`
4. Kiểm thử không oversell: `php tests/oversell_test.php`
   (XAMPP/Apache thật: `BASE_URL=http://localhost/GODMINTON php tests/oversell_test.php`). Kỳ vọng: `28 đạt, 0 lỗi`.
5. `config/db.php` đặt múi giờ PHP và MySQL là `+07:00` để `NOW()` luôn khớp giờ Việt Nam.

## 5. Tuần 3 — Khu quản trị Sân, Khung giờ & Dashboard (Huy: task 13, 14)

1. **Migration CSDL Tuần 3 (`database/migration_admin_management.sql`)**:
   - Mở rộng `time_slots.status` thêm trạng thái `'blocked'` (Ngừng nhận đặt) và cột `price`.
   - Bổ sung `total_sub_courts`, `opening_time`, `closing_time` cho bảng `courts`.
   - *Lưu ý:* `api/admin/courts.php` và `api/admin/slots.php` có cơ chế tự kiểm tra và nâng cấp bảng tự động (`ensureCourtsSchema` / `ensureSlotsSchema`) nên hệ thống vẫn chạy trơn tru ngay cả khi quên chạy file migration thủ công.
2. **Task 13 — Quản lý sân (`api/admin/courts.php` & `admin-courts.php`)**:
   - Hỗ trợ đầy đủ `GET`, `POST`, `PUT`/`PATCH`, `DELETE` với PDO Prepared Statements + Transaction.
   - Phân quyền chặt chẽ: Chủ sân (`role = 'owner'`) chỉ xem/sửa/xóa sân có `owner_id` của chính mình; Admin (`role = 'admin'`) quản lý toàn bộ sân.
3. **Task 14 — Quản lý khung giờ (`api/admin/slots.php` & `admin-slots.php`)**:
   - Tạo khung giờ hàng loạt (`POST`) theo khoảng giờ, thời lượng mỗi khung (30/60/90/120 phút), giá tiền và danh sách sân con (`Thảm A1..An`), tự động chống tạo trùng/chồng lấn giờ.
   - Đổi trạng thái từng khung giờ (`PATCH`: `available` ↔ `blocked`) và xóa từng khung giờ hoặc xóa nhanh các khung giờ trống trong ngày (`DELETE`).
4. **Tổng quan Dashboard (`api/admin/dashboard.php` & `admin-dashboard.php`)**:
   - Thống kê số sân quản lý, lượt đặt hôm nay, lượt đặt tuần này, doanh thu hợp lệ và danh sách đơn đặt gần đây từ MySQL.
5. **Kiểm thử tự động Tuần 3**:
   - Chạy `php tests/admin_week3_test.php` (hoặc `BASE_URL=http://localhost/GODMINTON php tests/admin_week3_test.php`). Kỳ vọng: `23 đạt, 0 lỗi`.

## 6. Tuần 4 — PWA, Web Push Notification, Seed Final & Nghiệm Thu (Triết, Huy, Minh)

### A. Progressive Web App (PWA) & Chế độ ngoại tuyến (Offline Mode)
1. **Manifest (`manifest.json`)**:
   - Cấu hình chuẩn W3C tại thư mục gốc, `display: "standalone"`, `start_url: "./index.php"`, `theme_color: "#0d9488"`.
   - Bộ icon đầy đủ chuẩn kích thước (`assets/img/icons/icon-192.png`, `icon-512.png`, maskable icons, favicon, apple-touch-icon).
2. **Service Worker (`sw.js`) & Trang Offline (`offline.php`)**:
   - Phạm vi toàn bộ website (`scope: ./`).
   - **Quy tắc tuyệt đối:** Các endpoint giao dịch `/api/` luôn dùng **Network-Only** (không bao giờ đọc/lưu cache để chống giữ chỗ ảo hoặc dữ liệu đơn bị sai lệch).
   - Điều hướng HTML: Chiến lược *Network-First* kết hợp bộ nhớ đệm; khi mất mạng hiển thị trang `offline.php` đẹp mắt với tính năng tự động tải lại ngay khi có kết nối trở lại.
   - Tài nguyên tĩnh (CSS, JS, Fonts, Images): Chiến lược *Stale-While-Revalidate* cho tốc độ tải tức thì.
3. **Banner Cài đặt PWA**:
   - Tự động bắt sự kiện `beforeinstallprompt` hiển thị banner cài đặt tuỳ chỉnh (`pwa-install-banner.php`).

### B. Web Push Notification (Thông báo đẩy nhắc lịch thi đấu)
1. **Cơ sở dữ liệu**:
   - Bảng `push_subscriptions` (`database/migration_push_subscriptions.sql`) lưu trữ endpoint, p256dh, auth của từng thiết bị.
2. **Cấu hình VAPID (`config/vapid.php`)**:
   - Cặp khóa mật mã đường cong elliptic NIST P-256 tiêu chuẩn, cấp phát VAPID Public Key qua `api/push/vapid-public-key.php`.
3. **Luồng gửi thật**: `api/push/send.php` gửi qua Push Service tới đúng subscription của thiết bị đang đăng nhập; Service Worker hiển thị thông báo kể cả khi trang đã đóng. Worker `tools/send-push-reminders.php` nhắc đơn đã xác nhận trước giờ chơi ~5 phút, chống gửi trùng và thử lại tối đa 3 lần.
4. **Giao diện quản lý thông báo**:
   - Tích hợp nút bật/tắt và nút "Gửi thử thông báo" trực quan trên trang [Hồ sơ cá nhân](file:///c:/xampp/htdocs/GODMINTON/profile.php) và [Lịch sử đặt sân](file:///c:/xampp/htdocs/GODMINTON/history.php) thông qua [assets/js/push-client.js](file:///c:/xampp/htdocs/GODMINTON/assets/js/push-client.js).

### C. CSDL cài mới và nâng cấp

Thực hiện theo [database/README.md](database/README.md). Trên database trống, import `schema.sql` trước rồi `seed.sql`. Không import seed lên DB nhóm đang chạy. Bộ cài bao gồm 22 bảng; database hiện có cần import `migration_push_reminders.sql` một lần.

Seed giữ danh mục sân/tiện ích/ảnh, địa chỉ TPro và trạng thái ngừng hoạt động của các bản trùng. Không chứa đơn đặt, khung giờ, liên hệ, tài khoản thật hoặc thiết bị nhận thông báo. Ba tài khoản phát triển và mật khẩu được ghi trong hướng dẫn database.

### D. Bộ lệnh Kiểm thử tự động (Automated Test Suite)
Chạy trực tiếp từ thư mục gốc dự án:
```bash
# 1. Kiểm tra toàn vẹn CSDL và liên kết đơn đặt
php tests/check_booking_integrity.php

# 2. Kiểm tra phân quyền Admin / Chủ sân (23 test pass)
$env:BASE_URL="http://127.0.0.1:8098"; php tests/admin_week3_test.php

# 3. Kiểm tra toàn diện logic giữ chỗ, chống oversell, tính tiền và hủy đơn (44 test pass)
php tests/audit_logic.php

# 4. Kiểm tra PWA, manifest, Service Worker và Web Push Notification (29 test pass)
php tests/test_pwa_push.php
```

