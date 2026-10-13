# HỢP ĐỒNG GIAO TIẾP API ĐẶT SÂN & GIỮ CHỖ (BOOKING API CONTRACT)

> **Dự án:** GODMINTON — Hệ thống Đặt sân Cầu lông Trực tuyến  
> **Thỏa thuận giữa:** Huy (Backend Lead - Quản lý Slot & Cơ chế Giữ chỗ) ↔ Minh (Frontend Lead - Giao diện Đặt sân, Xác nhận & Thanh toán)  
> **Phiên bản:** 1.0 (Tuần 2)  
> **Mục tiêu:** Thống nhất định dạng dữ liệu (Payload), mã lỗi HTTP và hành vi nghiệp vụ cho các API `slots.php`, `hold.php`, `confirm.php` / `bookings.php` nhằm giải quyết dứt điểm hiện tượng Race Condition (tranh chấp giữ chỗ đồng thời) và chuyển đổi hoàn toàn từ LocalStorage MockDB sang MySQL PDO thật.

---

## 1. Nguyên Tắc Thiết Kế Chung

1. **Chuẩn dữ liệu trao đổi:** Mọi Request Body và Response Body đều dùng `application/json; charset=utf-8`.
2. **Cơ chế Phiên Đăng Nhập:** Xác thực người dùng qua PHP Native Session (`$_SESSION['user_id']`). Không dùng token giả lập ở client.
   - Nếu client chưa có session hợp lệ khi gọi các API cần quyền (hold, booking), server lập tức trả mã HTTP `401 Unauthorized`.
3. **Giải quyết Race Condition (Tranh chấp chỗ):**
   - Mọi thao tác đổi trạng thái slot (`available` ↔ `holding` ↔ `booked`) bắt buộc phải bọc trong Transaction của PDO:
     ```php
     $pdo->beginTransaction();
     // Khóa dòng slot bằng FOR UPDATE để các process khác phải chờ
     $stmt = $pdo->prepare("SELECT * FROM time_slots WHERE id = ? FOR UPDATE");
     ...
     $pdo->commit();
     ```
4. **Thời hạn giữ chỗ (Holding Window):** Khung giờ được giữ tạm thời trong đúng **5 phút** (`hold_expires_at = NOW() + INTERVAL 5 MINUTE`). Quá 5 phút, hệ thống tự động coi slot là hết hạn và trả lại trạng thái khả dụng cho khách khác.

---

## 2. Đặc Tả Chi Tiết Các Endpoint

### 2.1. API Lấy Danh Sách Khung Giờ (Slots)
- **Đường dẫn chuẩn:** `GET /courts/:id/slots?date=YYYY-MM-DD`
- **File thực thi:** `api/slots.php` (Hỗ trợ gọi `api/slots.php?court_id={id}&date={YYYY-MM-DD}` hoặc `api/slots.php?id={slot_id}`)
- **Người phụ trách triển khai:** Huy
- **Người sử dụng:** Minh (Dùng trong `calendar_booking.js` để render lưới lịch và polling 5s/lần; dùng trong `confirm.js` để verify slot trước khi xác nhận).

#### Request:
- **Query Parameters:**
  - `court_id`: `int` (bắt buộc nếu không có `id`) — ID của sân cầu lông (ví dụ: `1`).
  - `date`: `string` (`YYYY-MM-DD`, mặc định là ngày hôm nay nếu bỏ trống).
  - `id`: `int` (tùy chọn) — Lấy thông tin chi tiết của 1 slot duy nhất.

#### Xử lý nghiệp vụ Backend:
1. Tự động kiểm tra và giải phóng (auto-release) các slot có `status = 'holding'` nhưng `hold_expires_at < NOW()`.
2. Nếu ngày được chọn chưa có khung giờ nào trong bảng `time_slots`, tự động khởi tạo (auto-seed) danh sách slot mẫu cho 4 thảm (`Thảm A1` đến `Thảm A4`) từ 06:00 đến 23:00.
3. So sánh `held_by` với `$_SESSION['user_id']` hiện tại để gán cờ `is_my_hold` (boolean) và tính số giây còn lại `hold_seconds_left`.

#### Response Thành Công (HTTP 200 OK):
```json
{
  "status": "success",
  "court": {
    "id": 1,
    "name": "Cầu Lông Quận 1 – Lê Thánh Tôn Sport",
    "price_per_hour": 100000
  },
  "date": "2026-10-14",
  "data": [
    {
      "id": 101,
      "court_id": 1,
      "sub_court_name": "Thảm A1",
      "slot_date": "2026-10-14",
      "start_time": "06:00:00",
      "end_time": "07:00:00",
      "price": 100000,
      "status": "available",
      "held_by": null,
      "hold_expires_at": null,
      "is_my_hold": false,
      "hold_seconds_left": 0
    },
    {
      "id": 102,
      "court_id": 1,
      "sub_court_name": "Thảm A1",
      "slot_date": "2026-10-14",
      "start_time": "07:00:00",
      "end_time": "08:00:00",
      "price": 100000,
      "status": "holding",
      "held_by": 1,
      "hold_expires_at": "2026-10-14 07:05:00",
      "is_my_hold": true,
      "hold_seconds_left": 280
    }
  ]
}
```

---

### 2.2. API Giữ Chỗ Tạm Thời (Hold Slot)
- **Đường dẫn chuẩn:** `POST /courts/:id/hold`
- **File thực thi:** `api/hold.php`
- **Người phụ trách triển khai:** Huy
- **Người sử dụng:** Minh (Khi người dùng click vào ô trống trong `calendar_booking.js` hoặc bấm nút Hủy giữ chỗ trong `confirm.js`).

#### Request:
- **Headers:** `Content-Type: application/json`
- **Body:**
```json
{
  "slot_id": 101,
  "action": "hold"
}
```
*(Nếu muốn hủy giữ chỗ sớm, gửi `"action": "release"`)*

#### Xử lý nghiệp vụ Backend:
1. Kiểm tra session đăng nhập. Nếu chưa đăng nhập: trả `401 Unauthorized`.
2. Mở Transaction: `$pdo->beginTransaction()`.
3. Khóa dòng: `SELECT * FROM time_slots WHERE id = ? FOR UPDATE`.
4. Nếu `action === 'release'`:
   - Nếu slot đang được giữ bởi chính user: cập nhật `status = 'available'`, `held_by = NULL`, `hold_expires_at = NULL`. Commit và trả về `200 OK`.
5. Nếu `action === 'hold'`:
   - Nếu slot đã có khách đặt (`status === 'booked'`): Rollback, trả `409 Conflict` (`Khung giờ đã có khách đặt`).
   - Nếu slot đang được giữ bởi người khác (`status === 'holding'` VÀ `held_by != current_user` VÀ `hold_expires_at >= NOW()`): Rollback, trả `409 Conflict` (`Khung giờ đang được người khác giữ chỗ`).
   - Ngược lại: Cho phép giữ chỗ, cập nhật:
     `status = 'holding'`, `held_by = current_user`, `hold_expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE)`.
   - Commit transaction.

#### Response Thành Công (HTTP 200 OK):
```json
{
  "status": "success",
  "message": "Giữ chỗ thành công! Bạn có 5 phút để hoàn tất đặt sân.",
  "data": {
    "slot_id": 101,
    "status": "holding",
    "expires_at": "2026-10-14 07:05:00",
    "expires_in": 300
  }
}
```

#### Response Lỗi (HTTP 409 Conflict):
```json
{
  "status": "error",
  "message": "Khung giờ này vừa có người giữ trước bạn ít giây. Vui lòng chọn khung giờ khác!"
}
```

---

### 2.3. API Xác Nhận Đặt Sân & Tạo Đơn Hàng (Create Booking)
- **Đường dẫn chuẩn:** `POST /bookings`
- **File thực thi:** `api/bookings.php` (Đồng thời `api/confirm.php` hỗ trợ tương thích)
- **Người phụ trách triển khai:** Minh (Backend Transaction & Frontend Integration)
- **Người sử dụng:** Minh (Gọi từ form hoàn tất tại `confirm.js` và `payment.js`).

#### Request:
- **Headers:** `Content-Type: application/json`
- **Body:**
```json
{
  "court_id": 1,
  "slot_id": 101,
  "payment_method": "VietQR",
  "notes": "Nhờ chuẩn bị 2 cặp vợt",
  "billing_name": "Nguyễn Văn An",
  "billing_phone": "0912345678",
  "billing_email": "khach1@gmail.com"
}
```

#### Xử lý nghiệp vụ Backend:
1. Kiểm tra session đăng nhập. Nếu chưa đăng nhập: trả `401 Unauthorized`.
2. Mở Transaction: `$pdo->beginTransaction()`.
3. Khóa dòng slot: `SELECT * FROM time_slots WHERE id = ? FOR UPDATE`.
4. **Kiểm tra tính hợp lệ của phiên giữ chỗ:**
   - Slot phải tồn tại.
   - Slot phải có `status = 'holding'`.
   - `held_by` phải bằng chính `$_SESSION['user_id']`.
   - `hold_expires_at` phải còn hiệu lực (`>= NOW()`).
   - *Nếu vi phạm bất kỳ điều kiện nào:* Rollback ngay lập tức, trả lỗi `409 Conflict` (`Phiên giữ chỗ của bạn đã hết hạn hoặc không hợp lệ. Vui lòng chọn lại sân!`).
5. **Thực thi trong cùng Transaction:**
   - Lấy giá sân từ bảng `courts`, tính toán `total_price` và tiền cọc `deposit_amount` (50%).
   - Thêm bản ghi mới vào bảng `bookings`:
     ```sql
     INSERT INTO bookings (user_id, court_id, total_price, deposit_amount, status, created_at)
     VALUES (?, ?, ?, ?, 'confirmed', NOW());
     ```
   - Thêm bản ghi liên kết vào bảng `booking_slots`:
     ```sql
     INSERT INTO booking_slots (booking_id, slot_id) VALUES (?, ?);
     ```
   - Đổi trạng thái slot từ `holding` sang `booked` và xóa thông tin giữ chỗ tạm:
     ```sql
     UPDATE time_slots 
     SET status = 'booked', held_by = NULL, hold_expires_at = NULL 
     WHERE id = ?;
     ```
   - Commit transaction.
6. Sinh mã đơn hàng chuẩn hệ thống: `GDM-YYYYMMDD-XXXX` (với `XXXX` là ID đơn hàng).

#### Response Thành Công (HTTP 201 Created / 200 OK):
```json
{
  "status": "success",
  "message": "Đặt sân thành công!",
  "data": {
    "booking_id": 12,
    "booking_code": "GDM-20261014-0012",
    "court_id": 1,
    "court_name": "Cầu Lông Quận 1 – Lê Thánh Tôn Sport",
    "sub_court_name": "Thảm A1",
    "slot_date": "2026-10-14",
    "time_range": "07:00 - 08:00",
    "total_price": 100000,
    "deposit_amount": 50000,
    "payment_method": "VietQR",
    "status": "confirmed",
    "created_at": "2026-10-14 07:02:15"
  }
}
```

---

## 3. Kế Hoạch Phối Hợp & Kiểm Thử Tuần 2

1. **Minh hoàn thiện Client:**
   - Sửa `calendar_booking.js`: Polling mỗi 5 giây gọi `api/slots.php`, click gọi `api/hold.php`. Giữ nguyên 100% bảng chú giải màu và giao diện.
   - Sửa `confirm.js` & `payment.js`: Đổi từ ghi `MockDB` sang gọi `POST api/bookings.php`.
2. **Kịch bản kiểm thử tranh chấp (Race Condition):**
   - Mở 2 cửa sổ ẩn danh (2 user khác nhau) cùng truy cập vào 1 khung giờ.
   - User 1 bấm chọn giữ chỗ trước -> User 2 bấm chọn ngay sau đó -> User 2 nhận thông báo lỗi 409 "Đã có người giữ chỗ" và ô lập tức đổi sang trạng thái khóa/được giữ sau lần polling kế tiếp.
   - User 1 đợi quá 5 phút -> Khung giờ tự động mở lại cho User 2.
