# GODMINTON – 3 địa điểm/quận cũ

Ngày tra cứu: 16/10/2026. Danh mục: 57 địa điểm, 19 quận lịch sử (Quận 1–12, Bình Thạnh, Tân Bình, Gò Vấp, Phú Nhuận, Thủ Đức, Bình Tân, Tân Phú). Tách Quận 2, Quận 9, quận Thủ Đức theo cách gọi trước 2021. Không tính các huyện trong 57 địa điểm.

## Độ tin cậy

56 bản ghi dùng tọa độ ghim địa điểm từ liên kết bản đồ công khai. Riêng Cầu Kho: cả hai nguồn trỏ tới ghim địa chỉ số 2 Nguyễn Văn Cừ, chưa xác minh được ghim riêng/lối vào sân. Đây là vị trí theo địa chỉ, không bảo đảm chính xác vị trí sân. Không có tọa độ nào được đo thực địa; chưa gọi điện xác nhận sân còn hoạt động.

Địa chỉ phường hiện hành lấy từ nguồn cập nhật; một số tên phường được đối chiếu bổ sung. Tọa độ dùng WGS84, có thể dùng trực tiếp với Leaflet/OpenStreetMap, không có nghĩa địa điểm đã được đăng ký thành POI trên OSM. Không lấy tọa độ sau ký tự @ trong URL Google (đó có thể là tâm camera); lấy !3d và !4d của địa điểm.

## Cách chạy trên XAMPP

Giải nén đè các file đã sửa vào dự án; mở PowerShell tại thư mục GODMINTON. Nếu CSDL mới, tạo schema và seed theo README cũ trước, sau đó chạy import này. Không nạp lại seed sau khi đồng bộ danh mục.

```powershell
& "D:\xampp\php\php.exe" database/import_real_courts.php --dry-run
& "D:\xampp\php\php.exe" database/import_real_courts.php --execute --replace-catalog --owner-id=3
```

Nếu user 3 không có role owner/admin, thay bằng ID chủ sân phù hợp. Kết nối sử dụng config/db.php hiện có (hỗ trợ cấu hình DB_PORT=3307). Không cần API key, không gọi Nominatim.

`--replace-catalog` sẽ ẩn toàn bộ sân ngoài danh mục bằng status=inactive; không xóa sân, booking hay thanh toán. Không dùng cờ này thì sân cũ vẫn hiện nên tổng số có thể vượt 3/quận. Chạy lại cập nhật theo khóa nguồn và không nhân đôi. Có thể lọc chính xác `--district="Quận 1"`.

Trước khi cập nhật, script lưu bản sao courts kèm old_area_label vào courts_before_import_*.json. Đây là bản sao danh mục, không phải bản backup toàn bộ MySQL. Toàn bộ thay đổi wards/courts thực hiện trong một transaction.

Giá 100.000đ/giờ cho sân mới là giá DEMO, có ghi rõ trong mô tả; giá sân đã tồn tại được giữ lại. Không tự bịa hình ảnh sân, tiện ích hay lịch trống. Muốn demo đặt sân phải tạo khung giờ trong trang quản trị.

## File đã thay đổi

- database/import_real_courts.php: importer mới, chứa bản dữ liệu dự phòng nhúng để file PHP tải riêng vẫn chạy được khi đặt trong database/.
- database/real_courts.json: danh mục chính; khi có file này importer ưu tiên đọc JSON, thuận tiện chỉnh sửa sau.
- assets/js/court-map.js: bỏ tọa độ giả dự phòng theo ID, mở rộng vùng hiển thị.
- admin-courts.php: đồng bộ phần hướng dẫn phạm vi tọa độ.
- database/optional_rural_courts.json: 12 địa điểm bổ sung (Bình Chánh, Hóc Môn, Nhà Bè, Củ Chi), chưa import; Cần Giờ chưa đủ dữ liệu.

## Kiểm tra đã thực hiện

Đã kiểm tra JSON, đúng 19 nhóm × 3 địa điểm, khóa nguồn và tọa độ không trùng, tọa độ nằm trong phạm vi, đường dẫn nguồn và OSM đầy đủ; bản JSON nhúng trùng với file JSON. Môi trường này không có PHP/MySQL nên chưa chạy kiểm tra cú pháp PHP hoặc import trên MySQL thực tế. Hãy chạy dry-run trước.

## Danh mục và nguồn

### Quận 1

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Tao Đàn | Số 01 đường Huyền Trân Công Chúa, Phường Bến Thành, Thành phố Hồ Chí Minh | 10.7747674, 106.6947053 | [OSM](https://www.openstreetmap.org/?mlat=10.7747674&mlon=106.6947053#map=19/10.7747674/106.6947053) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-1) · [Ghim nguồn](https://maps.app.goo.gl/Pc8v8sMY4WVqoF5q7) |
| Sân cầu lông Cung Văn hóa Lao động TP.HCM | 55B, Nguyễn Thị Minh Khai, Phường Bến Thành, Thành phố Hồ Chí Minh | 10.7763541, 106.6931260 | [OSM](https://www.openstreetmap.org/?mlat=10.7763541&mlon=106.6931260#map=19/10.7763541/106.6931260) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-1) · [Ghim nguồn](https://maps.app.goo.gl/Qw7b7DNqkbC5iyvj6) |
| Sân cầu lông Cầu Kho **(ghim địa chỉ, cần xác nhận)** | Số 2, Đường Nguyễn Văn Cừ, Phường Cầu Ông Lãnh, Thành phố Hồ Chí Minh | 10.7547701, 106.6865755 | [OSM](https://www.openstreetmap.org/?mlat=10.7547701&mlon=106.6865755#map=19/10.7547701/106.6865755) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-1) · [Ghim nguồn](https://maps.app.goo.gl/Vj5nY2ZyTUcQQVph7) |

### Quận 2

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Lavie | 40 Lương Định Của, Phường An Khánh, TP.HCM | 10.7858758, 106.7312755 | [OSM](https://www.openstreetmap.org/?mlat=10.7858758&mlon=106.7312755#map=19/10.7858758/106.7312755) · [Địa chỉ](https://gosensports.com/san-cau-long-quan-2) · [Ghim nguồn](https://maps.app.goo.gl/nZHad1HQVqEC4nSj7) |
| Sân cầu lông Trần Não | 22 Đường số 24, Phường An Khánh, TP.HCM | 10.7944184, 106.7298283 | [OSM](https://www.openstreetmap.org/?mlat=10.7944184&mlon=106.7298283#map=19/10.7944184/106.7298283) · [Địa chỉ](https://gosensports.com/san-cau-long-quan-2) · [Ghim nguồn](https://maps.app.goo.gl/3FWjo1Vj52kPzkpm6) |
| Sân cầu lông Bình An | 11B Đường số 29, Phường An Khánh, TP.HCM | 10.7922769, 106.7293126 | [OSM](https://www.openstreetmap.org/?mlat=10.7922769&mlon=106.7293126#map=19/10.7922769/106.7293126) · [Địa chỉ](https://gosensports.com/san-cau-long-thu-duc) · [Ghim nguồn](https://maps.app.goo.gl/E7hEbDgXDHTsCmpq7) |

### Quận 3

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân Cầu Lông NCUT | 269 Rạch Bùng Binh, Phường Nhiêu Lộc, Thành phố Hồ Chí Minh | 10.7821652, 106.6801638 | [OSM](https://www.openstreetmap.org/?mlat=10.7821652&mlon=106.6801638#map=19/10.7821652/106.6801638) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-3) · [Ghim nguồn](https://maps.app.goo.gl/8qRQDpzc4TpwVNGs5) |
| Sân cầu lông Trung Tâm TDTT Ga Sài Gòn | 01 Nguyễn Thông, Phường Nhiêu Lộc, Thành phố Hồ Chí Minh | 10.7825189, 106.6768555 | [OSM](https://www.openstreetmap.org/?mlat=10.7825189&mlon=106.6768555#map=19/10.7825189/106.6768555) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-3) · [Ghim nguồn](https://maps.app.goo.gl/b4MwkooGTQD8VcNV6) |
| Sân cầu lông Marie Curie | 26 Lê Quý Đôn, Phường Xuân Hòa, Thành phố Hồ Chí Minh | 10.7819577, 106.6902981 | [OSM](https://www.openstreetmap.org/?mlat=10.7819577&mlon=106.6902981#map=19/10.7819577/106.6902981) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-3) · [Ghim nguồn](https://maps.app.goo.gl/E2mhguC8kXKQiYwc6) |

### Quận 4

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Tân Cảng | 122 Đ. Khánh Hội, Phường Khánh Hội, Thành phố Hồ Chí Minh | 10.7563925, 106.7011714 | [OSM](https://www.openstreetmap.org/?mlat=10.7563925&mlon=106.7011714#map=19/10.7563925/106.7011714) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-4) · [Ghim nguồn](https://maps.app.goo.gl/UwKZDzoxqBkdMYYu7) |
| Sân Cầu Lông TPro | 1B Đ. Hoàng Diệu, Phường Xóm Chiếu, Thành phố Hồ Chí Minh | 10.7641603, 106.7094772 | [OSM](https://www.openstreetmap.org/?mlat=10.7641603&mlon=106.7094772#map=19/10.7641603/106.7094772) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-4) · [Ghim nguồn](https://maps.app.goo.gl/kzNwD6adtr3AZrC16) |
| Sân cầu lông T12 | 1B Hoàng Diệu, Phường Xóm Chiếu, TP.HCM | 10.7638079, 106.7091101 | [OSM](https://www.openstreetmap.org/?mlat=10.7638079&mlon=106.7091101#map=19/10.7638079/106.7091101) · [Địa chỉ](https://shopvnb.com/san-cau-long-t12.html) · [Ghim nguồn](https://maps.app.goo.gl/FGPuVeVBor5Xdaeo6) |

### Quận 5

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân Cầu Lông Đại Học Sài Gòn | Số 8, Đ. Nguyễn Trãi, Phường Chợ Quán, Thành phố Hồ Chí Minh | 10.7590400, 106.6818537 | [OSM](https://www.openstreetmap.org/?mlat=10.7590400&mlon=106.6818537#map=19/10.7590400/106.6818537) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-5) · [Ghim nguồn](https://maps.app.goo.gl/UiKzaJFVL52KSB3MA) |
| Sân Cầu Lông Lam Sơn | Số 302/1, Đường Trần Bình Trọng, Phường Chợ Quán, Thành phố Hồ Chí Minh | 10.7623271, 106.6798241 | [OSM](https://www.openstreetmap.org/?mlat=10.7623271&mlon=106.6798241#map=19/10.7623271/106.6798241) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-5) · [Ghim nguồn](https://maps.app.goo.gl/myL6ZybmUizFxXX58) |
| Sân Cầu Lông Lý Phong | Số 78, Đường An Dương Vương, Phường An Đông, Thành phố Hồ Chí Minh | 10.7578899, 106.6708473 | [OSM](https://www.openstreetmap.org/?mlat=10.7578899&mlon=106.6708473#map=19/10.7578899/106.6708473) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-5) · [Ghim nguồn](https://maps.app.goo.gl/4DSUz4tQpq1dCp297) |

### Quận 6

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân Cầu Lông Thảo Trang | 24 Đ. Lý Chiêu Hoàng, Phường Bình Phú, Thành phố Hồ Chí Minh | 10.7377355, 106.6318526 | [OSM](https://www.openstreetmap.org/?mlat=10.7377355&mlon=106.6318526#map=19/10.7377355/106.6318526) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-6) · [Ghim nguồn](https://maps.app.goo.gl/uxoAGUjdoWSNKQEF6) |
| Sân Cầu Lông Thể Thao Phong Sơn Bình Phú | 102 Đ. Trần Văn Kiểu, Phường Bình Phú, Thành phố Hồ Chí Minh | 10.7357749, 106.6270888 | [OSM](https://www.openstreetmap.org/?mlat=10.7357749&mlon=106.6270888#map=19/10.7357749/106.6270888) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-6) · [Ghim nguồn](https://maps.app.goo.gl/7a5YxnMRoQGhryDP7) |
| Sân Cầu Lông Nhựt Minh | 3-5 Đ. Lý Chiêu Hoàng, Khu II, Phường Bình Phú, Thành phố Hồ Chí Minh | 10.7358356, 106.6335628 | [OSM](https://www.openstreetmap.org/?mlat=10.7358356&mlon=106.6335628#map=19/10.7358356/106.6335628) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-6) · [Ghim nguồn](https://maps.app.goo.gl/Ndw64Pq4A8daXWyp8) |

### Quận 7

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân Cầu Lông Trung Tâm Thể Thao Victory | Nhà thi đấu Nam Sài Gòn, 157 Nguyễn Văn Quỳ, Phường Tân Thuận, Thành phố Hồ Chí Minh | 10.7421688, 106.7381973 | [OSM](https://www.openstreetmap.org/?mlat=10.7421688&mlon=106.7381973#map=19/10.7421688/106.7381973) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-7) · [Ghim nguồn](https://maps.app.goo.gl/Q3jKnJHLLHkaRm9K6) |
| Sân Cầu Lông Nhà thi đấu đa năng Quận 7 | 504 Huỳnh Tấn Phát, Phường Tân Thuận, Thành phố Hồ Chí Minh | 10.7415332, 106.7295191 | [OSM](https://www.openstreetmap.org/?mlat=10.7415332&mlon=106.7295191#map=19/10.7415332/106.7295191) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-7) · [Ghim nguồn](https://maps.app.goo.gl/rSzDcgB3hjE8xKrT6) |
| Sân Cầu Lông 79 Pro | 300/13 Nguyễn Văn Linh, Phường Tân Thuận, Thành phố Hồ Chí Minh | 10.7420631, 106.7206122 | [OSM](https://www.openstreetmap.org/?mlat=10.7420631&mlon=106.7206122#map=19/10.7420631/106.7206122) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-7) · [Ghim nguồn](https://maps.app.goo.gl/g1vi7c6nWkNc8U7f6) |

### Quận 8

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Hoàng Vy | 218/6 An Dương Vương, Phường Bình Phú, Thành phố Hồ Chí Minh | 10.7309514, 106.6213914 | [OSM](https://www.openstreetmap.org/?mlat=10.7309514&mlon=106.6213914#map=19/10.7309514/106.6213914) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-8) · [Ghim nguồn](https://maps.app.goo.gl/1m2rNmoJRr4CMKnk7) |
| CLB cầu lông Xóm Củi | 63 Đ. Tùng Thiện Vương, Phường Phú Định, Thành phố Hồ Chí Minh | 10.7484872, 106.6662164 | [OSM](https://www.openstreetmap.org/?mlat=10.7484872&mlon=106.6662164#map=19/10.7484872/106.6662164) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-8) · [Ghim nguồn](https://maps.app.goo.gl/YjvzYPFrFqqstqrA8) |
| Sân cầu lông Aquaminton | 1458 Đ. Hoài Thanh, Phường Phú Định, Thành phố Hồ Chí Minh | 10.7359953, 106.6448645 | [OSM](https://www.openstreetmap.org/?mlat=10.7359953&mlon=106.6448645#map=19/10.7359953/106.6448645) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-8) · [Ghim nguồn](https://maps.app.goo.gl/Sq3caKtVh484VatL9) |

### Quận 9

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Hải Sơn | 167 Đường số 14, Phường Phước Long, TP.HCM | 10.8110270, 106.7681039 | [OSM](https://www.openstreetmap.org/?mlat=10.8110270&mlon=106.7681039#map=19/10.8110270/106.7681039) · [Địa chỉ](https://gosensports.com/san-cau-long-quan-9) · [Ghim nguồn](https://maps.app.goo.gl/kHBNmJG596S5BJg49) |
| Sân cầu lông Long Sen | 39P Gò Cát, Phường Long Trường, TP.HCM | 10.7860298, 106.8013464 | [OSM](https://www.openstreetmap.org/?mlat=10.7860298&mlon=106.8013464#map=19/10.7860298/106.8013464) · [Địa chỉ](https://gosensports.com/san-cau-long-quan-9) · [Ghim nguồn](https://maps.app.goo.gl/HK23Qgp1tPcYRXMP8) |
| Sân cầu lông Công Thương | 103 Tăng Nhơn Phú, Phường Phước Long, TP.HCM | 10.8295874, 106.7732033 | [OSM](https://www.openstreetmap.org/?mlat=10.8295874&mlon=106.7732033#map=19/10.8295874/106.7732033) · [Địa chỉ](https://shopvnb.com/san-cau-long-cong-thuong.html) · [Ghim nguồn](https://maps.app.goo.gl/6RjL9NmeoeSdA6NV8) |

### Quận 10

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Phong Sơn Cao Đẳng Kinh Tế | 253 Trần Nhân Tôn, Phường Vườn Lài, Thành phố Hồ Chí Minh | 10.7648250, 106.6741632 | [OSM](https://www.openstreetmap.org/?mlat=10.7648250&mlon=106.6741632#map=19/10.7648250/106.6741632) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-10) · [Ghim nguồn](https://maps.app.goo.gl/BKvfeFoZEKAgp3m2A) |
| Sân cầu lông 291 | 291 Hẻm 285 Cách Mạng Tháng Tám, Phường Hòa Hưng, Thành phố Hồ Chí Minh | 10.7758833, 106.6734950 | [OSM](https://www.openstreetmap.org/?mlat=10.7758833&mlon=106.6734950#map=19/10.7758833/106.6734950) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-10) · [Ghim nguồn](https://maps.app.goo.gl/SNjhgrbx6xdw7nyu7) |
| Sân Cầu Lông Bắc Hải | LL1 Bis, Đ. Hương Giang, Cư xá Bắc Hải, Phường Hòa Hưng, Thành phố Hồ Chí Minh | 10.7806129, 106.6638385 | [OSM](https://www.openstreetmap.org/?mlat=10.7806129&mlon=106.6638385#map=19/10.7806129/106.6638385) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-10) · [Ghim nguồn](https://maps.app.goo.gl/TWwCWGFhNg1UfEj17) |

### Quận 11

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Toàn Võ (Châu Dương cũ) | Số 185 Đường Lê Thị Bạch Cát, Phường Phú Thọ, Thành phố Hồ Chí Minh | 10.7650614, 106.6492458 | [OSM](https://www.openstreetmap.org/?mlat=10.7650614&mlon=106.6492458#map=19/10.7650614/106.6492458) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-11) · [Ghim nguồn](https://maps.app.goo.gl/ycG3iC1bkrQDcEgt8) |
| Sân cầu lông Nhà thi đấu Phú Thọ | Lầu 1, Số 1 Lữ Gia, Phường Phú Thọ, Thành phố Hồ Chí Minh | 10.7690285, 106.6577739 | [OSM](https://www.openstreetmap.org/?mlat=10.7690285&mlon=106.6577739#map=19/10.7690285/106.6577739) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-11) · [Ghim nguồn](https://maps.app.goo.gl/pkz7GBWjo5J8BSwRA) |
| Sân cầu lông Phú Thọ | 219 Lý Thường Kiệt, Phường Phú Thọ, Thành phố Hồ Chí Minh | 10.7675886, 106.6584777 | [OSM](https://www.openstreetmap.org/?mlat=10.7675886&mlon=106.6584777#map=19/10.7675886/106.6584777) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-11) · [Ghim nguồn](https://maps.app.goo.gl/5geHuQoXNifFnwCJ7) |

### Quận 12

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Nhà thi đấu đa năng Quận 12 | số 493, đường Đường Thị Mười, Phường Tân Thới Hiệp, Thành phố Hồ Chí Minh | 10.8737375, 106.6385800 | [OSM](https://www.openstreetmap.org/?mlat=10.8737375&mlon=106.6385800#map=19/10.8737375/106.6385800) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-12) · [Ghim nguồn](https://maps.app.goo.gl/DzmdXJFLtsCf6Uv19) |
| Sân cầu lông Võ Trường Toản | Số 482, Đường Nguyễn Thị Đặng, Phường Tân Thới Hiệp, Thành phố Hồ Chí Minh | 10.8725841, 106.6458344 | [OSM](https://www.openstreetmap.org/?mlat=10.8725841&mlon=106.6458344#map=19/10.8725841/106.6458344) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-12) · [Ghim nguồn](https://maps.app.goo.gl/mHH9EjaY7xQotEbi6) |
| Sân cầu lông Hiệp Thành | 163/50, Đường Bùi Văn Ngữ, khu phố 7, Phường Tân Thới Hiệp, Thành phố Hồ Chí Minh | 10.8864202, 106.6295177 | [OSM](https://www.openstreetmap.org/?mlat=10.8864202&mlon=106.6295177#map=19/10.8864202/106.6295177) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-12) · [Ghim nguồn](https://maps.app.goo.gl/Cjqtk3dbEVvBbyRPA) |

### Bình Thạnh

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân Cầu Lông Kim Minh | 206/8A Bình Quới, Phường Bình Quới, Thành phố Hồ Chí Minh | 10.8188471, 106.7260652 | [OSM](https://www.openstreetmap.org/?mlat=10.8188471&mlon=106.7260652#map=19/10.8188471/106.7260652) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-thanh) · [Ghim nguồn](https://maps.app.goo.gl/Yup9YfGEXabgXrMS8) |
| Sân Cầu Lông Như Nhiên | 353/3 Bình Quới, Phường Bình Quới, Thành phố Hồ Chí Minh | 10.8263405, 106.7355239 | [OSM](https://www.openstreetmap.org/?mlat=10.8263405&mlon=106.7355239#map=19/10.8263405/106.7355239) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-thanh) · [Ghim nguồn](https://maps.app.goo.gl/kBszXE9mowpj84Ys9) |
| Sân Cầu Lông Nguyễn Xí | 161 Đ. Nguyễn Xí, Phường Bình Thạnh, Thành phố Hồ Chí Minh | 10.8141754, 106.7086153 | [OSM](https://www.openstreetmap.org/?mlat=10.8141754&mlon=106.7086153#map=19/10.8141754/106.7086153) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-thanh) · [Ghim nguồn](https://maps.app.goo.gl/D6WQYwrMHuCu2xE38) |

### Tân Bình

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông 18B Cộng Hoà | 18B Cộng Hoà, Phường Tân Sơn Nhất, Thành phố Hồ Chí Minh | 10.8051712, 106.6556922 | [OSM](https://www.openstreetmap.org/?mlat=10.8051712&mlon=106.6556922#map=19/10.8051712/106.6556922) · [Địa chỉ](https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-tan-binh) · [Ghim nguồn](https://maps.app.goo.gl/A2tNv9zJ5Q46dpgM8) |
| Sân cầu lông Bùi Thị Xuân | 191 Bùi Thị Xuân, Phường Tân Sơn Hòa, Thành phố Hồ Chí Minh | 10.7949933, 106.6643541 | [OSM](https://www.openstreetmap.org/?mlat=10.7949933&mlon=106.6643541#map=19/10.7949933/106.6643541) · [Địa chỉ](https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-tan-binh) · [Ghim nguồn](https://maps.app.goo.gl/UgCyCD7ahiRSJyHU7) |
| Sân cầu lông 98 | 98/ Đ. Trần Thị Trọng, Phường Tân Bình, Thành phố Hồ Chí Minh | 10.8255865, 106.6359921 | [OSM](https://www.openstreetmap.org/?mlat=10.8255865&mlon=106.6359921#map=19/10.8255865/106.6359921) · [Địa chỉ](https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-tan-binh) · [Ghim nguồn](https://maps.app.goo.gl/CcMsphtTRfveTbrLA) |

### Gò Vấp

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Đại Phát | 313/17 Đ. Phan Huy Ích, Phường An Hội Tây, Thành phố Hồ Chí Minh | 10.8387338, 106.6341503 | [OSM](https://www.openstreetmap.org/?mlat=10.8387338&mlon=106.6341503#map=19/10.8387338/106.6341503) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-go-vap) · [Ghim nguồn](https://maps.app.goo.gl/rWnEh3ZW4qDLhDSk7) |
| Sân cầu lông Đức Lợi | 611 Đ. Lê Văn Thọ, Phường An Hội Tây, Thành phố Hồ Chí Minh | 10.8548593, 106.6551045 | [OSM](https://www.openstreetmap.org/?mlat=10.8548593&mlon=106.6551045#map=19/10.8548593/106.6551045) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-go-vap) · [Ghim nguồn](https://maps.app.goo.gl/aeAYbUAPxJgRx2uS9) |
| Sân cầu lông An Hội | 256/110 Đ. Phan Huy Ích, Phường An Hội Tây, Thành phố Hồ Chí Minh | 10.8380834, 106.6402183 | [OSM](https://www.openstreetmap.org/?mlat=10.8380834&mlon=106.6402183#map=19/10.8380834/106.6402183) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-go-vap) · [Ghim nguồn](https://maps.app.goo.gl/s5NqiAC8CwhQtCYq6) |

### Phú Nhuận

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông PooC | 202B Hoàng Văn Thụ, Phường Đức Nhuận, Thành phố Hồ Chí Minh | 10.8009171, 106.6699606 | [OSM](https://www.openstreetmap.org/?mlat=10.8009171&mlon=106.6699606#map=19/10.8009171/106.6699606) · [Địa chỉ](https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-phu-nhuan) · [Ghim nguồn](https://maps.app.goo.gl/p8spqz3cEhBFAufL6) |
| Sân cầu lông Sky Phú Nhuận | 84 Mai Văn Ngọc, Phường Phú Nhuận, Thành phố Hồ Chí Minh | 10.7926856, 106.6725338 | [OSM](https://www.openstreetmap.org/?mlat=10.7926856&mlon=106.6725338#map=19/10.7926856/106.6725338) · [Địa chỉ](https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-phu-nhuan) · [Ghim nguồn](https://maps.app.goo.gl/akvaFwMnDej5KDZcA) |
| Sân cầu lông Đào Duy Anh | 21 Đào Duy Anh, Phường Đức Nhuận, TP.HCM | 10.8051688, 106.6758775 | [OSM](https://www.openstreetmap.org/?mlat=10.8051688&mlon=106.6758775#map=19/10.8051688/106.6758775) · [Địa chỉ](https://tuanviet.vn/danh-sach-cac-san-cau-long-quan-phu-nhuan) · [Ghim nguồn](https://maps.app.goo.gl/L52ad9AT926naPYb6) |

### Thủ Đức

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Aurora Sport | 158 Đường số 17, Phường Linh Xuân, TP.HCM | 10.8662804, 106.7875994 | [OSM](https://www.openstreetmap.org/?mlat=10.8662804&mlon=106.7875994#map=19/10.8662804/106.7875994) · [Địa chỉ](https://shopvnb.com/san-cau-long-aurora-sport.html) · [Ghim nguồn](https://maps.app.goo.gl/zuB19KcNpRs1LQg89) |
| Sân cầu lông Tường Anh | 56/1 Đường số 2, Phường Thủ Đức, TP.HCM | 10.8349018, 106.7546823 | [OSM](https://www.openstreetmap.org/?mlat=10.8349018&mlon=106.7546823#map=19/10.8349018/106.7546823) · [Địa chỉ](https://gosensports.com/san-cau-long-thu-duc) · [Ghim nguồn](https://maps.app.goo.gl/YLkwswtjS2tVHvX79) |
| Sân cầu lông Quốc Lộ 1 | 400 Quốc lộ 1A, Phường Tam Bình, TP.HCM | 10.8721689, 106.7417341 | [OSM](https://www.openstreetmap.org/?mlat=10.8721689&mlon=106.7417341#map=19/10.8721689/106.7417341) · [Địa chỉ](https://shopvnb.com/san-cau-long-quoc-lo-1.html) · [Ghim nguồn](https://maps.app.goo.gl/EL2jR8Z6UNnjhn1DA) |

### Bình Tân

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Sân cầu lông Aka Badminton Center | 730/4 Hương Lộ 2, Phường Bình Trị Đông, Thành phố Hồ Chí Minh | 10.7672571, 106.6051067 | [OSM](https://www.openstreetmap.org/?mlat=10.7672571&mlon=106.6051067#map=19/10.7672571/106.6051067) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-tan) · [Ghim nguồn](https://maps.app.goo.gl/xvjqicGXG4AWXWLn9) |
| CLB cầu lông Sinh Đôi | 327 Đ. Bình Trị Đông, Phường Bình Trị Đông, Thành phố Hồ Chí Minh | 10.7700147, 106.6093781 | [OSM](https://www.openstreetmap.org/?mlat=10.7700147&mlon=106.6093781#map=19/10.7700147/106.6093781) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-tan) · [Ghim nguồn](https://maps.app.goo.gl/dNTEaQj9r42h4p9W9) |
| Sân cầu lông Hưng Phú | 15 Liên Khu 2-5, Phường Bình Trị Đông Thành phố Hồ Chí Minh | 10.7675902, 106.6168394 | [OSM](https://www.openstreetmap.org/?mlat=10.7675902&mlon=106.6168394#map=19/10.7675902/106.6168394) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-binh-tan) · [Ghim nguồn](https://maps.app.goo.gl/MoWcm2gNfY5evqGV7) |

### Tân Phú

| Địa điểm | Địa chỉ hiện hành theo nguồn | Vĩ độ, kinh độ | Kiểm tra |
|---|---|---|---|
| Câu lạc bộ Cầu Lông TTC | 17/5/15, Lương Minh Nguyệt, P.Tân Phú, TP.Hồ Chí Minh | 10.7602202, 106.6310087 | [OSM](https://www.openstreetmap.org/?mlat=10.7602202&mlon=106.6310087#map=19/10.7602202/106.6310087) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-tan-phu) · [Ghim nguồn](https://maps.app.goo.gl/CKv5Mt5VWBFg5VLn7) |
| Sân cầu lông Ecosport | 15 Cách Mạng, P.Tân Sơn Nhì, TP.Hồ Chí Minh | 10.7890963, 106.6364317 | [OSM](https://www.openstreetmap.org/?mlat=10.7890963&mlon=106.6364317#map=19/10.7890963/106.6364317) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-tan-phu) · [Ghim nguồn](https://maps.app.goo.gl/8SLNxwrVPxaSM9Xg7) |
| Sân cầu lông Tấn Phúc | 452 Lũy Bán Bích, P.Tân Phú, TP.Hồ Chí Minh | 10.7787125, 106.6362366 | [OSM](https://www.openstreetmap.org/?mlat=10.7787125&mlon=106.6362366#map=19/10.7787125/106.6362366) · [Địa chỉ](https://tuanviet.vn/danh-sach-san-cau-long-quan-tan-phu) · [Ghim nguồn](https://maps.app.goo.gl/5TQnP5ftdoCftVSn9) |

## Đối chiếu bổ sung

- Cầu Kho: https://shopvnb.com/san-cau-long-cau-kho.html (cùng ghim địa chỉ, không phải xác nhận độc lập vị trí sân).
- Đào Duy Anh: https://aikido-kansha.com/ và https://vmito.com/vi/venues/san-cau-long-san-cau-long-dao-duy-anh (sửa lỗi “Đức Thuận/Phượng” của nguồn thành “Đức Nhuận”).
- Công Thương: https://ktx.hitu.edu.vn/gioi-thieu/ (103 Tăng Nhơn Phú, Phước Long).
- Long Sen: https://vmito.com/vi/venues/san-cau-long-long-sen (39P Gò Cát, Long Trường).
- Tường Anh: https://vmito.com/vi/venues/san-cau-long-tuong-anh (phường Thủ Đức).
- Toàn Võ: tên mới đọc từ ghim nguồn, trước đây là Châu Dương.
