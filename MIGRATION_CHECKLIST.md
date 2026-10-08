# MIGRATION_CHECKLIST — Chuyển MockDB (LocalStorage) → fetch API thật

> **Dự án:** GODMINTON · Nhóm 2, Đề tài 5  
> **Người lập:** Triết (52400323) · **Tuần 1:** Rà soát MockDB toàn dự án & Lập checklist chuyển đổi  
> **Nhánh làm việc:** `project` · **Ngày hoàn thiện:** 2026-10-08  
> **Cách dùng:** Mỗi dòng `- [ ]` là một đầu việc/vị trí code cần sửa. Người phụ trách tick `[x]` khi đã đổi sang `fetch()` API thật **và đã kiểm thử chạy tốt trên XAMPP**.

---

## 0. Tóm tắt nhanh & Báo cáo rà soát Tuần 1

- **Làm rõ con số 18 trang**:
  - **15 trang PHP thực tế đang chạy trên XAMPP**: `index.php`, `courts.php`, `court-detail.php`, `booking.php`, `confirm.php`, `payment.php`, `history.php`, `profile.php`, `news.php`, `contact.php`, `auth.php`, `admin-dashboard.php`, `admin-courts.php`, `admin-slots.php`, `admin-bookings.php`.
  - **3 trang HTML tĩnh ban đầu của template**: `login.html`, `register.html` (đã được gộp thành 1 trang `auth.php` dùng tab), và `booking.html` (bản gốc dùng `booking.js` với giao diện tĩnh, sau đó được thay bằng `booking.php` nạp `calendar_booking.js`).
  - Tổng cộng khớp đúng **18 trang** theo cấu trúc gốc của đề bài.
- **20 file mã nguồn còn dính líu MockDB**:
  - **16 file JS**: `admin.js`, `app.js`, `auth.js`, `bottom-nav.js`, `confirm.js`, `contact.js`, `court_detail.js`, `courts.js`, `history.js`, `index_explore_dt.js`, `news.js`, `payment.js`, `profile.js` + 3 file mồ côi (`booking.js`, `login.js`, `register.js`).
  - **4 trang PHP có inline script**: `admin-bookings.php`, `admin-courts.php`, `admin-dashboard.php`, `profile.php`.
- **Hiện trạng API backend**: Chỉ mới có **4/20 API** hoạt động (`api/auth/login.php`, `register.php`, `logout.php`, `change-password.php`). 16 API còn lại đang là file khung stub trả `{"status": "not_implemented"}`.
- **Hiện trạng Auth client**: `auth.js` đã dùng `fetch()` thật gửi tới backend login/register, nhưng vẫn tạm giữ `MockDB.setUser()` ở L80 và L114 để duy trì hiển thị navbar.

---

### 0.1 Thống kê tần suất theo hàm MockDB

| Hàm MockDB | Số lần xuất hiện | Giải pháp thay thế | Phụ trách | Tuần |
|---|---|---|---|---|
| `getUser()` | 16 | Đọc từ session PHP (`api/auth/me.php` hoặc `window.__GODMINTON_USER__`) | Huy | Tuần 1 |
| `formatVND()` | 16 | Hàm tiện ích JS thuần trong `utils.js` / `app.js`, **không gọi API** | Minh | Tuần 2 |
| `logout()` | 7 | `api/auth/logout.php` (POST) | Huy | Tuần 1 |
| `getBookings()` | 4 | `api/bookings.php` (GET) & `api/admin/bookings.php` (GET) | Minh | Tuần 3 |
| `setUser()` | 3 | `api/profile.php` (POST) + cập nhật session | Minh | Tuần 3 |
| `init()` | 3 | Xóa bỏ (chỉ có ở `mock-data.js` và `admin.js`) | Huy | Tuần 1 |
| `getCourts()` | 3 | `api/courts.php` (GET) | Minh | Tuần 1 |
| `holdSlot()` / `getSlotsForCourt()` / `createBooking()` / `cancelSlot()` | 2 mỗi hàm | `api/hold.php` / `api/slots.php` / `api/confirm.php` / `api/cancel.php` | Huy / Minh | Tuần 2 |
| `getNews()` | 2 | `api/news.php` (GET) | **Triết** | Tuần 3 |
| `sendContact()` | 1 | `api/contact.php` (POST) | **Triết** | Tuần 3 |
| `getCourtById()`, `getSlotById()`, `confirmBooking()`, `cancelBooking()`, `saveBookings()`, `login()`, `register()` | 1 mỗi hàm | Tương ứng từng endpoint API bên dưới | Nhóm | Tuần 1–3 |

---

### 0.2 Ba file JS mồ côi (Legacy Scripts)

| File | Tình trạng | Đề xuất xử lý |
|---|---|---|
| `assets/js/booking.js` | Không có file PHP nào nạp; `booking.php` nạp `calendar_booking.js` | Chốt với Minh: Chuyển logic giữ chỗ & đếm ngược sang `calendar_booking.js`, sau đó xóa hoặc lưu kho `booking.js`. |
| `assets/js/login.js` | Không dùng; `auth.php` đã nạp `auth.js` | Xóa bỏ sau khi kiểm tra không còn trang nào dẫn tới `login.html`. |
| `assets/js/register.js` | Không dùng; `auth.php` đã nạp `auth.js` | Xóa bỏ sau khi kiểm tra không còn trang nào dẫn tới `register.html`. |

> *Khuyến nghị:* Không tốn công chuyển đổi API trong 3 file này trước khi chốt để tránh dư thừa mã nguồn.

---

## B. Bảng tra cứu chi tiết 18 trang (15 trang PHP + 3 trang HTML gốc)

| # | Trang | Script JS nạp | Lời gọi MockDB bị ảnh hưởng | API tương ứng cần nối | Trạng thái |
|---|---|---|---|---|---|
| 1 | `index.php` | `mock-data.js`, `app.js`, `index_explore_dt.js`, `bottom-nav.js` | `getUser` (3), `getCourts` (1), `getNews` (1), `logout` (2) | `api/courts.php`, `api/news.php` | `[ ]` |
| 2 | `courts.php` | `mock-data.js`, `app.js`, `courts.js`, `bottom-nav.js` | `getCourts` (1), `formatVND` (1), `getUser` (3), `logout` (2) | `api/courts.php` | `[ ]` |
| 3 | `court-detail.php` | `mock-data.js`, `app.js`, `court_detail.js`, `bottom-nav.js` | `getCourtById` (1), `getUser` (3), `logout` (2) | `api/court-detail.php?id=` | `[ ]` |
| 4 | `booking.php` | `mock-data.js`, `app.js`, `calendar_booking.js`, `bottom-nav.js` | `getUser` (2), `logout` (2), comment reload MockDB L304 | `api/slots.php?court_id=&date=` | `[ ]` |
| 5 | `confirm.php` | `mock-data.js`, `app.js`, `confirm.js`, `bottom-nav.js` | `getUser` (3), `getSlotById` (1), `formatVND` (4), `confirmBooking` (1), `cancelSlot` (1), `logout` (2) | `api/slots.php`, `api/confirm.php`, `api/hold.php` | `[ ]` |
| 6 | `payment.php` | `mock-data.js`, `app.js`, `payment.js`, `bottom-nav.js` | `formatVND` (1), `getUser` (3), `getCourts` (1), `createBooking` (1), `logout` (2) | `api/courts.php`, `api/confirm.php` | `[ ]` |
| 7 | `history.php` | `mock-data.js`, `app.js`, `history.js`, `bottom-nav.js` | `getUser` (3), `getBookings` (1), `formatVND` (2), `cancelBooking` (1), `logout` (2) | `api/bookings.php`, `api/cancel.php` | `[ ]` |
| 8 | `profile.php` | `mock-data.js`, `app.js`, `profile.js`, `bottom-nav.js` + inline | `getUser` (4), `setUser` (1), `getBookings` (1), `formatVND` (2), `logout` inline L224, `logout` (2) | `api/profile.php`, `api/bookings.php`, `api/auth/logout.php` | `[ ]` |
| 9 | `news.php` | `mock-data.js`, `app.js`, `news.js`, `bottom-nav.js` | `getUser` (3), `getNews` (1), `logout` (2) | `api/news.php` | `[ ]` |
| 10 | `contact.php` | `mock-data.js`, `app.js`, `contact.js`, `bottom-nav.js` | `getUser` (3), `sendContact` (1), `logout` (2) | `api/contact.php` | `[ ]` |
| 11 | `auth.php` | `mock-data.js`, `app.js`, `auth.js`, `bottom-nav.js` | `setUser` (2) *(auth.js đã gọi API thật)*, `getUser` (2), `logout` (2) | Đã có API login/register; cần bỏ cầu nối MockDB | `[~]` |
| 12 | `admin-dashboard.php` | `mock-data.js`, `app.js`, `admin.js`, `bottom-nav.js` + inline | Inline L163 `formatVND`; qua AdminApp: `init`, `getUser`, `computeStats`, `getMyBookings`, `logout` | `api/admin/dashboard.php` | `[ ]` |
| 13 | `admin-courts.php` | `mock-data.js`, `app.js`, `admin.js`, `bottom-nav.js` + inline | Inline L209, 267 `formatVND`; qua AdminApp: `getMyCourts`, `getAllCourts`, `saveAllCourts` | `api/admin/courts.php` | `[ ]` |
| 14 | `admin-slots.php` | `mock-data.js`, `app.js`, `admin.js`, `bottom-nav.js` | Qua AdminApp: `getMyCourts`, `getSlots`, `generateSlots`, `setSlotStatus` | `api/admin/slots.php` | `[ ]` |
| 15 | `admin-bookings.php` | `mock-data.js`, `app.js`, `admin.js`, `bottom-nav.js` + inline | Inline L145 `formatVND`, L157 `getBookings`; qua AdminApp: `getMyBookings`, `saveBookings` | `api/admin/bookings.php` | `[ ]` |
| 16 | *`login.html` (gốc)* | `mock-data.js`, `login.js` | `MockDB.login()` (L53) | Đã thay bằng `auth.php` — đề xuất dọn bỏ | `[ ]` |
| 17 | *`register.html` (gốc)* | `mock-data.js`, `register.js` | `MockDB.register()` (L32) | Đã thay bằng `auth.php` — đề xuất dọn bỏ | `[ ]` |
| 18 | *`booking.html` (gốc)* | `mock-data.js`, `booking.js` | `getSlotsForCourt` (L68), `holdSlot` (L168), `cancelSlot` (L280) | Đã thay bằng `booking.php` + `calendar_booking.js` | `[ ]` |

---

## 1. Phiên đăng nhập — thay `MockDB.getUser()` / `logout()` (Huy, Tuần 1)

`getUser()` trong code cũ đọc `localStorage['bm_user']` đồng bộ. Khi chuyển sang `fetch` bất đồng bộ, cần thống nhất mô hình phiên đăng nhập:

- [ ] **Chốt cơ chế phiên người dùng**: Khuyến nghị tạo endpoint `api/auth/me.php` trả thông tin user từ `$_SESSION`, hoặc nhúng `window.__GODMINTON_USER__` trực tiếp từ PHP header để JS đọc đồng bộ mà không cần fetch lại mỗi trang.
- [ ] `assets/js/app.js:44` — `renderNavbarUser()` gọi `MockDB.getUser()` → đổi sang đọc từ session.
- [ ] `assets/js/app.js:88` — nút Đăng xuất gọi `MockDB.logout()` → `fetch('api/auth/logout.php', { method: 'POST' })` rồi xóa bộ nhớ tạm, reload.
- [ ] `assets/js/bottom-nav.js:12` — đọc user để render bottom bar theo role.
- [ ] `assets/js/bottom-nav.js:450-451` và `:458-459` — 2 nhánh đăng xuất (SweetAlert và Confirm) → gọi `api/auth/logout.php`.
- [ ] `profile.php:224` — nút Đăng xuất inline onclick gọi `MockDB.logout()` → đổi sang gọi hàm logout dùng API.
- [ ] `assets/js/auth.js:80` và `:114` — gỡ bỏ cầu nối `MockDB.setUser(...)` sau login/register khi đã có cơ chế lưu phiên.
- [ ] Các vị trí đọc `getUser()` để kiểm tra đăng nhập hoặc hiển thị tên:
  - [ ] `assets/js/court_detail.js:2`
  - [ ] `assets/js/news.js:2`
  - [ ] `assets/js/contact.js:2`
  - [ ] `assets/js/history.js:2`
  - [ ] `assets/js/index_explore_dt.js:127`
  - [ ] `assets/js/courts.js:285`
  - [ ] `assets/js/confirm.js:7` và `assets/js/payment.js:17` — có điều hướng về trang đăng nhập nếu chưa có phiên.
- [ ] Khắc phục link `login.html` / `register.html` còn hard-code trong navbar (`app.js`) → trỏ về `auth.php`.

---

## 2. Danh sách & Chi tiết sân (Minh, Tuần 1)

API mục tiêu: `api/courts.php` (GET hỗ trợ filter `search`, `district`, `max_price`, `amenities`, `limit`), `api/court-detail.php?id=` (GET).

- [ ] `assets/js/courts.js:170` — `renderCourts()` gọi `MockDB.getCourts()` rồi lọc ở client → chuyển sang `fetch('api/courts.php?...')`.
- [ ] `assets/js/courts.js:232` — `MockDB.formatVND(court.min_price)` → đổi sang dùng `formatVND` từ `utils.js`.
- [ ] `assets/js/index_explore_dt.js:132` — `getCourts()` lấy danh sách sân nổi bật; gỡ bỏ fallback `INITIAL_COURTS` → gọi `api/courts.php?limit=6`.
- [ ] `assets/js/payment.js:39` — gọi `getCourts()` chỉ để tìm thông tin 1 sân → đổi sang `api/court-detail.php?id=`.
- [ ] `assets/js/court_detail.js:8` — `MockDB.getCourtById(courtId)` → đổi sang `fetch('api/court-detail.php?id=' + courtId)`. Xử lý chuyển hướng nếu sân không tồn tại.

---

## 3. Giữ chỗ & Lịch khung giờ (Huy API, Minh UI, Tuần 2 — Ưu tiên số 1)

API mục tiêu: `api/slots.php` (GET), `api/hold.php` (POST), `api/cancel.php` (POST).

- [ ] **Tái cấu trúc `calendar_booking.js`** (file thật sự nạp bởi `booking.php`):
  - [ ] Mảng `COURTS` (4 sân con "Thảm A1–A4") ở đầu file → nạp từ API sân con.
  - [ ] Mảng `DEMO_EVENTS` (khung giờ cứng) → thay bằng `fetch('api/slots.php?court_id=&date=')`.
  - [ ] Dòng ~304: Xử lý sự kiện đổi ngày/chọn sân để gọi lại API nạp slots mới.
  - [ ] Dòng ~246: Link chuyển sang `payment.php` mang theo `slot_id` thật từ DB thay vì dữ liệu text.
  - [ ] Thêm logic click ô trống: gọi `fetch('api/hold.php')` với thời gian khóa 5 phút và hiển thị đồng hồ đếm ngược.
- [ ] Xử lý các file liên quan:
  - [ ] `assets/js/confirm.js:18` — `getSlotById()` kiểm tra slot còn đang được giữ bởi user → `fetch('api/slots.php?id=')`.
  - [ ] `assets/js/confirm.js:210` — hủy giữ chỗ: `MockDB.cancelSlot()` → gọi `fetch('api/cancel.php', { method: 'POST' })` hoặc `api/hold.php`.
  - [ ] `assets/js/booking.js:68, 168, 280` — đồng bộ hoặc dọn dẹp các lời gọi MockDB trong file mồ côi này.

---

## 4. Xác nhận đặt sân & Thanh toán (Minh, Tuần 2–3)

API mục tiêu: `api/confirm.php` (POST tạo đơn hàng & chốt slot).

- [ ] `assets/js/confirm.js:163` — `MockDB.confirmBooking(...)` đang bọc trong `setTimeout` 1000ms giả lập → bỏ `setTimeout`, chuyển sang `await fetch('api/confirm.php', { method: 'POST', body: ... })`.
- [ ] `assets/js/confirm.js:114, 118, 122, 132` — 4 vị trí gọi `formatVND` hiển thị giá tiền, cọc, còn lại → đổi sang `utils.js`.
- [ ] `assets/js/payment.js:198` — `MockDB.createBooking({...})` → chuyển sang gọi API.
- [ ] `assets/js/payment.js` ~L205 — mã đơn hàng hardcode `'GDM-' + Date.now()` ở client → phải nhận `booking_code` chuẩn từ backend.
- [ ] `assets/js/payment.js:4` — hàm bọc `fmt` gọi `MockDB?.formatVND` → chuyển sang hàm tiện ích chuẩn.
- [ ] **Thống nhất luồng tạo đơn:** `confirm.js` và `payment.js` không được cùng tạo đơn mới độc lập; thống nhất luồng: Giữ chỗ → Xác nhận (`confirm.php`) tạo booking pending → Thanh toán VNPay cập nhật booking status.

---

## 5. Lịch sử đặt sân & Hồ sơ cá nhân (Minh, Tuần 3)

API mục tiêu: `api/bookings.php` (GET), `api/cancel.php` (POST), `api/profile.php` (GET/POST), `api/auth/change-password.php`.

- [ ] `assets/js/history.js:11` — `MockDB.getBookings()` → `fetch('api/bookings.php')` lấy danh sách đơn của user đang đăng nhập.
- [ ] `assets/js/history.js:57, 58` — `MockDB.formatVND(b.total_amount)`, `MockDB.formatVND(b.deposit_amount)` → dùng `formatVND`.
- [ ] `assets/js/history.js:93` — `MockDB.cancelBooking(bookingId)` → `fetch('api/cancel.php', { method: 'POST', body: JSON.stringify({ booking_id: bookingId }) })`.
- [ ] `assets/js/profile.js:6` và `:149` — gỡ bỏ hardcode dữ liệu mẫu "Nguyễn Văn An" → nạp dữ liệu user từ `api/profile.php`.
- [ ] `assets/js/profile.js:45` — `MockDB.setUser(updated)` khi submit form → `fetch('api/profile.php', { method: 'POST', body: ... })`.
- [ ] `assets/js/profile.js:94` — `MockDB.getBookings()` tab lịch sử trong trang cá nhân → gọi `api/bookings.php`.
- [ ] `assets/js/profile.js:129, 130` — chuyển đổi `MockDB.formatVND`.
- [ ] `profile.php` — hoàn thiện form đổi mật khẩu nối vào `api/auth/change-password.php` đã có sẵn.

---

## 6. Khu quản trị — Admin & Chủ sân (Huy: courts/slots/dashboard · Minh: bookings, Tuần 3)

File trung tâm: `assets/js/admin.js` (`AdminApp`). Hiện tại toàn bộ dữ liệu admin đang đọc/ghi trực tiếp `localStorage` (`bm_courts`, `bm_slots_*`).

| Vị trí trong `admin.js` | Dòng | Nhiệm vụ chuyển đổi | Phụ trách |
|---|---|---|---|
| `requireAuth()` & `MockDB.init()` | L14–15, L33 | Chuyển kiểm tra quyền sang backend PHP; chặn ngay từ server, loại bỏ admin giả | Huy |
| `getAllCourts()`, `saveAllCourts()` | L34, L38 | Nối `api/admin/courts.php` (GET, POST, PUT, DELETE) | Huy |
| `getMyCourts()` | L42–47 | Lọc sân theo `owner_id` của user đang đăng nhập phía DB | Huy |
| `getMyBookings()` | L55 | Nối `api/admin/bookings.php` (GET) | Minh |
| `saveBookings()` | L59 | Nối `api/admin/bookings.php` (POST/PATCH cập nhật trạng thái đơn) | Minh |
| `getSlots()`, `saveSlots()`, `generateSlots()`, `setSlotStatus()`, `deleteSlot()` | L68, L72, L80–140 | Nối `api/admin/slots.php` (CRUD khung giờ sân) | Huy |
| `computeStats()` | L145 | Nối `api/admin/dashboard.php` (tính doanh thu, số đơn, số sân) | Huy |
| `renderShell()` → `MockDB.logout()` | L216 | Đổi sang `fetch('api/auth/logout.php', { method: 'POST' })` | Huy |
| `formatVND` trong `generateSlots` | L104 | Chuyển sang hàm tiện ích | Huy |

**Các trang admin PHP cần cập nhật inline script:**
- [ ] `admin-dashboard.php:159, 163` — `AdminApp.computeStats(user)` + `MockDB.formatVND(stats.revenue)`.
- [ ] `admin-courts.php:189, 258, 295, 306` — `getMyCourts`, `getAllCourts`, `saveAllCourts`; L209, 267 `formatVND`.
- [ ] `admin-slots.php:160, 209, 253, 272, 275` — `getMyCourts`, `getSlots`, `generateSlots`, `setSlotStatus`.
- [ ] `admin-bookings.php:118, 157, 161` — `getMyBookings`, `MockDB.getBookings()`, `saveBookings`; L145 `formatVND`.

---

## 7. Tin tức & Liên hệ (Triết, Tuần 3) — Việc của mình

API mục tiêu: `api/news.php` (GET), `api/contact.php` (POST).

- [ ] **Bổ sung cấu trúc bảng `news` và `contacts` vào `database/schema.sql`**:
  ```sql
  CREATE TABLE news (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      title VARCHAR(255) NOT NULL,
      slug VARCHAR(255) NOT NULL UNIQUE,
      summary TEXT NULL,
      content LONGTEXT NULL,
      image_url VARCHAR(255) NULL,
      category VARCHAR(50) DEFAULT 'general',
      published_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB;

  CREATE TABLE contacts (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      email VARCHAR(150) NOT NULL,
      phone VARCHAR(20) NULL,
      subject VARCHAR(200) NULL,
      message TEXT NOT NULL,
      status ENUM('pending', 'read', 'replied') DEFAULT 'pending',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB;
  ```
- [ ] **Thêm dữ liệu mẫu vào `database/seed.sql`**: Chuyển đổi dữ liệu `INITIAL_NEWS` từ `mock-data.js` (L194–231) thành các câu lệnh `INSERT INTO news`.
- [ ] **Triển khai `api/news.php`**: Viết endpoint trả JSON `{ status: 'success', data: [...] }`, hỗ trợ query param `?limit=3` và phân trang.
- [ ] **Triển khai `api/contact.php`**: Viết endpoint nhận POST JSON/Form, validate dữ liệu, lưu vào bảng `contacts`, trả kết quả chuẩn.
- [ ] `assets/js/news.js:6` — đổi `MockDB.getNews()` → `fetch('api/news.php')`.
- [ ] `assets/js/index_explore_dt.js:178` — đổi `MockDB.getNews().slice(0, 3)` → `fetch('api/news.php?limit=3')` (chỉ sửa đúng dòng này để tránh xung đột mã với Minh).
- [ ] `assets/js/contact.js:25` — đổi `MockDB.sendContact(data)` → `fetch('api/contact.php', { method: 'POST', body: ... })`, loại bỏ giả lập `setTimeout(800)`.

---

## 8. Hạng mục mở rộng & An toàn bảo mật (Triết)

- **Tuần 2:**
  - [ ] Kiểm thử giao diện Responsive trên 3 độ phân giải (Mobile 375px, Tablet 768px, Desktop 1200px) cho các trang `booking.php`, `confirm.php`, `payment.php` sau khi kết nối API.
- **Tuần 3:**
  - [ ] `admin-courts.php` — thêm 2 ô nhập tọa độ `latitude` / `longitude` khi tạo/sửa sân.
  - [ ] `courts.php` & `court-detail.php` — tích hợp bản đồ OpenStreetMap / Leaflet hiển thị vị trí sân.
  - [ ] `database/import_real_courts.php` — rà soát trường `osm_type`, `osm_id` và giá trị tọa độ.
  - [ ] **Rà soát bảo mật toàn diện**:
    - [ ] Mọi file PHP nghiệp vụ nhúng `includes/auth-check.php` để bảo vệ từ server thay vì chỉ dựa vào JS client.
    - [ ] `api/admin/*` bắt buộc kiểm tra `$_SESSION['role'] === 'admin'` hoặc `'owner'`.
    - [ ] Toàn bộ truy vấn SQL trong API dùng chuẩn Prepared Statements (`$pdo->prepare()`), tuyệt đối không nối chuỗi.

---

## 9. Công việc chung & Dọn dẹp sau cùng (Cả nhóm, Cuối Tuần 3)

- [ ] **Tách hàm `formatVND` (16 vị trí)**: Viết vào `assets/js/utils.js` hoặc gắn toàn cục vào `app.js` trước khi gỡ `mock-data.js`.
- [ ] **Rà soát toàn bộ link `.html` còn sót (153 vị trí trong 28 file)**: Thay thế hàng loạt các đường dẫn tĩnh `.html` sang `.php` (`courts.html` → `courts.php`, `payment.html` → `payment.php`, `login.html`/`register.html` → `auth.php`).
- [ ] Xóa bỏ các lệnh khởi tạo `MockDB.init()` và hằng số `INITIAL_*`.
- [ ] **Gỡ bỏ `mock-data.js`**:
  - [ ] Bỏ thẻ `<script src="assets/js/mock-data.js"></script>` khỏi toàn bộ 15 trang PHP.
  - [ ] Xóa hoặc đổi tên lưu kho file `assets/js/mock-data.js`.
  - [ ] Chạy lệnh kiểm tra không còn sót `MockDB`:
    ```bash
    grep -rn "MockDB" assets/js *.php
    ```

---

## 10. Chênh lệch dữ liệu giữa Giao diện và Cơ sở dữ liệu

| Giao diện client hiện tại | CSDL MySQL hiện tại | Hướng xử lý cần thống nhất |
|---|---|---|
| `court.district` (Quận) | `courts.ward_id` → bảng `wards` (Phường) | Đổi bộ lọc client sang danh mục Phường hoặc map từ `old_area_label` |
| `court.min_price`, `court.rating`, `reviews` | `price_per_hour`; chưa có bảng đánh giá | Bổ sung bảng `reviews` hoặc tạm ẩn sao đánh giá trên UI |
| `sub_court_name` ("Thảm A1", "Sân 1") | Bảng `time_slots` chưa có cột tên sân con | Thêm cột `sub_court_name VARCHAR(50)` vào `time_slots` |
| Trạng thái slot `blocked` (chủ sân khóa) | ENUM chỉ có `available`, `holding`, `booked` | Cập nhật ENUM thêm giá trị `'blocked'` |
| `booking_code`, `payment_method`, `notes` | Bảng `bookings` chưa có đủ cột | Thêm các cột tương ứng vào bảng `bookings` |
| `total_amount` (tên trường JS) | `total_price` (tên cột DB) | Chuẩn hóa API serialize trả về `total_amount` |

---

## 11. Bảng theo dõi tiến độ tổng thể

| Giai đoạn | Nội dung công việc | Người phụ trách | Hạn chót | Tình trạng |
|---|---|---|---|---|
| **Tuần 1** | Rà soát MockDB, lập Checklist chi tiết 18 trang | **Triết** | 08/10 | **[x] Hoàn thành** |
| Tuần 1 | Phiên đăng nhập, Auth Session (`me.php`, logout) | Huy | 13/10 | `[ ]` |
| Tuần 1 | API danh sách sân, chi tiết sân & nối UI | Minh | 13/10 | `[ ]` |
| Tuần 2 | API slots, hold, cancel & viết lại lịch đặt sân | Huy + Minh | 20/10 | `[ ]` |
| Tuần 2 | Kiểm thử responsive giao diện đặt sân & cọc | Triết | 20/10 | `[ ]` |
| Tuần 2–3 | Xác nhận đặt sân, thanh toán VNPay | Minh | 27/10 | `[ ]` |
| Tuần 3 | Lịch sử đặt sân & cập nhật thông tin cá nhân | Minh | 27/10 | `[ ]` |
| Tuần 3 | Khu vực quản trị Admin (Courts, Slots, Bookings, Stats) | Huy + Minh | 27/10 | `[ ]` |
| Tuần 3 | API Tin tức, Liên hệ, Schema DB & Tích hợp bản đồ | **Triết** | 27/10 | `[ ]` |
| Cuối Tuần 3 | Tách `formatVND`, sửa link `.html`, gỡ hoàn toàn `mock-data.js` | Cả nhóm | 27/10 | `[ ]` |
