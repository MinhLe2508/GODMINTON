# HƯỚNG DẪN SỬ DỤNG MẪU TRẠNG THÁI GIAO DIỆN (UI STATES) — GODMINTON

Tài liệu dành cho: **Huy** (Auth, Core backend, API), **Minh** (Courts, Slots, Bookings, Dashboard) và **P3: Triết**.

Các file định nghĩa trạng thái dùng chung:
- CSS: `assets/css/ui-states.css` (đã đồng bộ `public/assets/css/ui-states.css`)
- JS: `assets/js/ui-states.js` (đã đồng bộ `public/assets/js/ui-states.js`)
- PHP Helper: `includes/partials/ui-states.php` (hoặc `views/partials/ui-states.php`)

---

## 1. Trạng thái rỗng (Empty State)
Dùng khi danh sách rỗng, tìm kiếm không có kết quả, giỏ slot chưa có item.

### Phía JavaScript:
```javascript
GodmintonUI.renderEmpty('containerId', {
    icon: 'fa-inbox',
    title: 'Không tìm thấy sân nào',
    desc: 'Thử đổi từ khóa hoặc đặt lại bộ lọc để tìm kiếm sân phù hợp.',
    actionText: 'Đặt lại bộ lọc',
    actionClick: function() { resetAllFilters(); }
});
```

### Phía PHP (Server-render):
```php
<?php require_once 'includes/partials/ui-states.php'; ?>
<div id="bookingList">
    <?= render_empty_state('Chưa có lịch sử đặt sân', 'Bạn chưa đặt sân nào. Hãy chọn sân ngay!', 'fa-calendar-xmark', 'Đặt sân ngay', 'courts.php') ?>
</div>
```

---

## 2. Trạng thái đang tải (Loading Spinner & Skeleton)
Dùng khi đang fetch API qua AJAX hoặc tải danh sách sân, danh sách slot.

### Phía JavaScript:
```javascript
// Spinner đơn giản
GodmintonUI.renderLoading('containerId', 'Đang nạp danh sách sân...');

// Hoặc Skeleton cards dạng lưới (shimmer animation):
GodmintonUI.renderSkeletons('courtsGrid', 6);
```

---

## 3. Trạng thái lỗi (Error State & Lỗi trường form)
Dùng khi fetch API thất bại (HTTP 500, mất mạng, timeout).

### Phía JavaScript (Toàn vùng container):
```javascript
GodmintonUI.renderError('containerId', {
    title: 'Lỗi tải dữ liệu',
    desc: 'Không thể kết nối tới máy chủ. Vui lòng kiểm tra lại đường truyền.',
    retryText: 'Thử lại',
    onRetry: function() { loadCourtsData(); }
});
```

### Lỗi trường form (Inline Field Error):
```html
<div class="auth-field">
    <label for="regEmail">Email *</label>
    <input type="email" id="regEmail" class="is-invalid-field">
    <div class="auth-field-error" id="regEmailError">
        <i class="fa-solid fa-circle-exclamation"></i> Email không đúng định dạng.
    </div>
</div>
```

---

## 4. Toast Thông Báo (Success / Error / Warning / Info)
Thay thế alert() trình duyệt, tự động biến mất sau 3.5 giây:

```javascript
GodmintonUI.toast('success', 'Thành công', 'Đã lưu thông tin sân!');
GodmintonUI.toast('error', 'Lỗi', 'Không thể hoàn tất giao dịch.');
GodmintonUI.toast('warning', 'Cảnh báo', 'Slot này chỉ được giữ trong 5 phút.');
GodmintonUI.toast('info', 'Thông báo', 'Hệ thống đang bảo trì một số cụm sân.');
```

---

## 5. Disabled nút khi đang gửi (Form Submit State)
Khóa nút và hiển thị spinner ngăn người dùng bấm nhiều lần (chống double submit):

```javascript
const btn = document.getElementById('btnSubmitBooking');

// 1. Khi bắt đầu submit
GodmintonUI.setButtonLoading(btn, 'Đang giữ chỗ...');

// 2. Sau khi API trả về xong (dù thành công hay lỗi)
GodmintonUI.resetButton(btn);
```
