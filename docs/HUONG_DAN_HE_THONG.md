# 📖 HƯỚNG DẪN HỆ THỐNG GODMINTON

---

## 🔑 1. Danh Sách Tài Khoản Quản Trị & Chủ Sân Từng Quận

Hệ thống đã phân quyền theo mô hình thực tế:
- **Admin**: Quản lý toàn bộ 100+ sân, đơn đặt, tài khoản và bài viết trên toàn hệ thống.
- **Chủ sân (Owner)**: Mỗi tài khoản được phân quyền theo **từng quận/huyện** và chỉ xem/sửa/quản lý khung giờ/đơn đặt cho các sân thuộc quận đó.

> **Thông tin đăng nhập chung:**
> - **URL đăng nhập:** [http://localhost/GODMINTON/auth.php](http://localhost/GODMINTON/auth.php)
> - **Mật khẩu Chủ Sân (Owner):** `Owner@123`
> - **Mật khẩu Admin:** `Admin@123`
> - **Chi tiết từng sân gán cho từng chủ sân:** Xem tại [docs/DANH_SACH_TAI_KHOAN_CHU_SAN.md](docs/DANH_SACH_TAI_KHOAN_CHU_SAN.md)

### Bảng Tài Khoản Đăng Nhập:

| STT | Khu Vực / Vai Trò | Tên Hiển Thị | Email Đăng Nhập | Số Điện Thoại | Mật Khẩu | Sân Quản Lý |
| :---: | :--- | :--- | :--- | :---: | :---: | :---: |
| **0** | 🛡️ **Quản Trị Hệ Thống** | **Quản trị phát triển** | `admin@godminton.vn` | `0900000002` | `Admin@123` | **Tất cả các sân** |
| 1 | 🏸 **Quận 1** | Chủ Sân Quận 1 | `owner.q1@godminton.vn` | `0901000001` | `Owner@123` | 8 sân |
| 2 | 🏸 **Quận 2 (Thủ Đức)** | Chủ Sân Quận 2 (Thủ Đức) | `owner.q2@godminton.vn` | `0901000002` | `Owner@123` | 5 sân |
| 3 | 🏸 **Quận 3** | Chủ Sân Quận 3 | `owner.q3@godminton.vn` | `0901000003` | `Owner@123` | 8 sân |
| 4 | 🏸 **Quận 4** | Chủ Sân Quận 4 | `owner.q4@godminton.vn` | `0901000004` | `Owner@123` | 3 sân |
| 5 | 🏸 **Quận 5** | Chủ Sân Quận 5 | `owner.q5@godminton.vn` | `0901000005` | `Owner@123` | 8 sân |
| 6 | 🏸 **Quận 6** | Chủ Sân Quận 6 | `owner.q6@godminton.vn` | `0901000006` | `Owner@123` | 6 sân |
| 7 | 🏸 **Quận 7** | Chủ Sân Quận 7 | `owner.q7@godminton.vn` | `0901000007` | `Owner@123` | 8 sân |
| 8 | 🏸 **Quận 8** | Chủ Sân Quận 8 | `owner.q8@godminton.vn` | `0901000008` | `Owner@123` | 3 sân |
| 9 | 🏸 **Quận 9 (Thủ Đức)** | Chủ Sân Quận 9 (Thủ Đức) | `owner.q9@godminton.vn` | `0901000009` | `Owner@123` | 3 sân |
| 10 | 🏸 **Quận 10** | Chủ Sân Quận 10 | `owner.q10@godminton.vn` | `0901000010` | `Owner@123` | 8 sân |
| 11 | 🏸 **Quận 11** | Chủ Sân Quận 11 | `owner.q11@godminton.vn` | `0901000011` | `Owner@123` | 8 sân |
| 12 | 🏸 **Quận 12** | Chủ Sân Quận 12 | `owner.q12@godminton.vn` | `0901000012` | `Owner@123` | 3 sân |
| 13 | 🏸 **Bình Thạnh** | Chủ Sân Bình Thạnh | `owner.binhthanh@godminton.vn` | `0901000013` | `Owner@123` | 6 sân |
| 14 | 🏸 **Tân Bình** | Chủ Sân Tân Bình | `owner.tanbinh@godminton.vn` | `0901000014` | `Owner@123` | 8 sân |
| 15 | 🏸 **Gò Vấp** | Chủ Sân Gò Vấp | `owner.govap@godminton.vn` | `0901000015` | `Owner@123` | 8 sân |
| 16 | 🏸 **Phú Nhuận** | Chủ Sân Phú Nhuận | `owner.phunhuan@godminton.vn` | `0901000016` | `Owner@123` | 8 sân |
| 17 | 🏸 **Tân Phú** | Chủ Sân Tân Phú | `owner.tanphu@godminton.vn` | `0901000017` | `Owner@123` | 3 sân |
| 18 | 🏸 **Bình Tân** | Chủ Sân Bình Tân | `owner.binhtan@godminton.vn` | `0901000018` | `Owner@123` | 3 sân |
| 19 | 🏸 **TP. Thủ Đức** | Chủ Sân TP. Thủ Đức | `owner.thuduc@godminton.vn` | `0901000019` | `Owner@123` | 6 sân |
| 20 | 🏸 **Bình Chánh** | Chủ Sân Bình Chánh | `owner.binhchanh@godminton.vn` | `0901000020` | `Owner@123` | 1 sân |

---

## 🛠️ 2. Sửa lỗi danh sách sân quay mãi (Tài liệu kỹ thuật cũ)

Nguyên nhân xác nhận từ Console: `updateFilterBadge()` truy cập `badge.style` khi không tìm thấy phần tử `filterCountBadge`. Lỗi xảy ra trước `renderCourts()`, nên danh sách chưa được gọi tải. API người dùng cung cấp trả thành công 41 sân.

### Cài bản sửa
1. Giải nén ZIP ra thư mục tạm.
2. Sao lưu file `assets/js/courts.js` hiện tại ra ngoài thư mục dự án.
3. Chép file `assets/js/courts.js` trong ZIP đè vào `D:\xampp\htdocs\GODMINTON\assets\js\courts.js`.
4. Mở [http://localhost/GODMINTON/courts.php](http://localhost/GODMINTON/courts.php) và nhấn `Ctrl + F5`.
5. Nếu vẫn thấy lỗi cũ: `F12` → `Application` → `Service Workers` → đánh dấu `Bypass for network` → `Ctrl + F5`. Sau khi kiểm tra xong, bỏ đánh dấu này.

Không cần chạy SQL hoặc import lại database.
