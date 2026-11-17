# GODMINTON — bàn giao sau clean code ngày 17/11/2026

## Phạm vi đã hoàn thành

Đối chiếu mã nguồn tại `D:\xampp\htdocs\GODMINTON`, nền commit `17f2cda`, sau đó chỉnh sửa có chọn lọc. Những thay đổi dưới đây chưa được commit hoặc tải lên hosting. Giữ nguyên CSS, ảnh, bố cục, schema và dữ liệu; không đổi công thức tính tiền, chính sách hủy/hoàn cọc hoặc giao thức SePay.

| File | Thay đổi |
|---|---|
| `assets/js/payment.js` | Bỏ hàm cập nhật CTA bị khai báo trùng, mã chuyển khoản ngẫu nhiên và thông tin ngân hàng dự phòng cũ không còn đi vào luồng đã tạo đơn. Hiển thị QR, tài khoản, mã thanh toán từ phản hồi máy chủ; sao chép mã đang hiển thị. |
| `assets/js/app.js` | Bỏ hàm không được gọi và bộ lắng nghe chỉ ghi log; giữ đăng ký Service Worker, cài PWA và giao diện. |
| `sw.js` | Phiên bản `godminton-v1.3.7-final-clean-20261117`; chỉ dọn cache GODMINTON, quản lý vòng đời tác vụ ghi cache, chịu lỗi cache đầy và phản hồi hợp lệ khi tài nguyên tĩnh chưa có cache/mất mạng. |
| `api/push/subscribe.php`, `api/push/send.php` | POST bắt buộc header `X-GODMINTON-CSRF` hợp lệ; thiếu, rỗng hoặc sai trả 403. Client hiện có đã gửi header này. GET đọc trạng thái đăng ký vẫn được giữ. |
| `api/auth/delete-account.php` | Đồng bộ thông báo thời hạn liên kết thành 5 phút, khớp token và email vốn đã dùng 5 phút. |
| `.htaccess` | Bổ sung chặn HTTP vào `.git`, `docs`, `report`, `scratch`, `tests`, `tools`; giữ chặn cấu hình/database/thư viện. PHP include nội bộ và công cụ CLI vẫn dùng được. |
| `composer.json`, `composer.lock` | Đồng bộ yêu cầu PHP từ 8.1 lên 8.2 theo thư viện đã khóa; không đổi phiên bản package. |
| `.env.example` | Mẫu XAMPP dùng MySQL 3307; bổ sung hướng dẫn xác thực SePay và cấu hình production. Không thay `.env` thật. |
| `tools/make-submission-zip.ps1` | Giữ file ẩn và `.git`, loại `.env*` thật/file tạm, kiểm tra tên ZIP và từ chối ghi đè. Đây là gói mã nguồn, chưa ghép báo cáo/video/rubric. |
| `README.md`, `Readme.txt` | Hướng dẫn chạy, cấu hình, cron, kiểm thử và bàn giao theo hệ thống hiện tại. |
| `tests/final-cleanup.test.cjs` | Bổ sung kiểm thử hồi quy cô lập cho payment JavaScript, Service Worker và hai API Push. |

## Kết quả kiểm tra

- Cú pháp: **112 file PHP, 29 file JavaScript và 1 file CJS** đạt.
- **44/44** tình huống hồi quy đạt: CTA/thông tin thanh toán/giá tiền, cache/offline, CSRF và phương thức HTTP của Push.
- **16/16** kiểm tra công cụ ZIP trên dữ liệu giả lập đạt: giữ file ẩn, loại cấu hình thật, kiểm tra nội dung và từ chối ghi đè.
- **13/13** kiểm tra định tuyến `.htaccess` bằng Apache cục bộ đạt. Đường dẫn webhook được phép đi qua; đây không phải kiểm chứng xử lý giao dịch SePay thật.
- Composer validate đạt, còn cảnh báo chưa khai báo license. Kiểm tra yêu cầu platform theo lock đạt trên PHP 8.2.12; không nâng cấp thư viện.

Các kiểm thử hồi quy dùng VM JavaScript và PHP/SQLite trên máy, không dùng database MySQL thật, ngân hàng, SMTP hay Push Service thật. Kiểm tra Apache dùng tài nguyên fixture. Chưa triển khai bản này lên hosting, chưa kiểm tra lại toàn bộ luồng đầu cuối trên hosting. Không diễn giải các con số trên thành kết luận mọi chức năng đã được kiểm thử đầy đủ.

## File cần tải lên hosting

Gói `GODMINTON_HOSTING_UPDATE_20261117.zip` có đúng **8 file** tại đường dẫn tương đối sau:

```text
.htaccess
admin-courts.php
api/auth/delete-account.php
api/push/subscribe.php
api/push/send.php
assets/js/app.js
assets/js/payment.js
sw.js
```

Trong đó **7 file** thuộc lần clean này; `admin-courts.php` là sửa đổi trạng thái sân theo giờ mở cửa đã có sẵn ở commit `17f2cda`, được kèm để đồng bộ nếu hosting còn bản trước 17/11. File này không bị sửa thêm trong lần clean. Chỉ dùng gói khi hosting và mã nguồn cục bộ cùng nhánh phát triển; nếu đã sửa riêng file trên hosting, đối chiếu và sao lưu trước khi ghi đè.

Không cần import SQL, thay `.env`, tải lại `vendor` hoặc chạy `composer update` cho lần cập nhật này. `composer.json`/`composer.lock` chỉ đổi yêu cầu môi trường, bộ thư viện chạy vẫn giữ nguyên; nếu cần đồng bộ metadata trên server, có thể tải thêm hai file này từ source. Metadata không nằm trong ZIP runtime. Các file test, tài liệu và công cụ đóng gói phục vụ mã nguồn/bản nộp, không cần tải thêm lên web.

### Cách cập nhật

1. Sao lưu 8 file tương ứng đang chạy trên hosting. Thực hiện khi không có thao tác thanh toán đang mở; không sửa đơn hay giao dịch trong bước cập nhật.
2. Trong File Manager, bật **Show Hidden Files** để nhìn thấy `.htaccess`. Giải nén gói vào **document root của godminton.top**, tức thư mục chứa `index.php` đang chạy. Gói không có thư mục bọc ngoài. Nếu tải từng file, giữ đúng thư mục, tải nhóm PHP/JavaScript trước và `sw.js` cuối cùng.
3. Giữ `.env` riêng của hosting. Đối chiếu cấu hình vận hành thật bên dưới, chỉ sửa giá trị chưa đúng:

   ```dotenv
   APP_URL=https://godminton.top/
   APP_ENV=production
   SEPAY_ALLOW_SIMULATE=0
   SEPAY_STRICT_AUTH=1
   ```

   Tắt giả lập sẽ làm nút/luồng DEV TEST không còn sử dụng trên production. Không thay khóa SePay, VAPID, SMTP hoặc thông tin DB bằng dữ liệu mẫu.
4. Khi có mạng, tải lại trang, đóng/mở lại cửa sổ PWA nếu bản worker mới đang chờ. Kiểm tra phiên bản worker/cache mới trong DevTools. Không xóa toàn bộ dữ liệu website chỉ để cập nhật, vì bản lịch ngoại tuyến cũng có thể bị xóa. Mở lịch sử khi có mạng để đồng bộ lại trước khi thử offline.
5. Kiểm tra nhanh đăng nhập, quản lý sân theo giờ mở cửa, chọn giờ/giữ chỗ, màn hình thanh toán và nút sao chép mã; bật/tắt Push; xem lịch đã đồng bộ khi offline. Chỉ thanh toán thật khi nhóm chủ động có kịch bản riêng.
6. Kiểm tra `.env`, `.git/config`, `tools/`, `tests/` bị từ chối qua HTTP; gọi POST webhook không có khóa phải vẫn trả 401. Công cụ `tools/` giờ chạy qua **PHP CLI/cron**, không qua trình duyệt. Cron cũ dùng CLI giữ nguyên; hướng dẫn lưu log không đồng nghĩa hosting đã có log.

## Báo cáo cần cập nhật ở đâu?

Không thay thiết kế, phân trang, ảnh giao diện hoặc sơ đồ. Dưới đây là các điểm đối chiếu với bản báo cáo đã bổ sung ảnh ngày 15/11; nếu đang dùng bản gộp khác, tìm theo tiêu đề và nội dung tương ứng. File Word chưa bị chỉnh sửa trong lần clean này.

### 1. Mục 3.1/3.2 — mốc mã nguồn và yêu cầu PHP

Giữ mốc 15/11 gắn với bằng chứng đã chụp ngày đó. Bổ sung câu:

> Đợt hoàn thiện mã nguồn ngày 17/11/2026 được thực hiện trên nền commit 17f2cda. Các thay đổi sau clean code được lưu trong bản bàn giao; mã commit cuối được ghi nhận sau khi nhóm commit mã nguồn. Minh chứng trên hosting được phân biệt với kiểm tra cục bộ.

Trong Mục 3.2, thay câu nói `composer.json` yêu cầu 8.1 nhưng lock yêu cầu 8.2 bằng:

> composer.json và composer.lock thống nhất yêu cầu PHP từ 8.2. Môi trường triển khai cần đáp ứng yêu cầu của bộ thư viện đã khóa; đợt hoàn thiện không thay đổi phiên bản thư viện.

**Ảnh mã nguồn cần chụp lại: Mã nguồn 3.3 — Khai báo thư viện và cơ chế tự động nạp lớp**, từ `composer.json` (phần `require` có `"php": ">=8.2"` và phần `autoload`). File ngắn có thể chụp toàn bộ, giữ tên ảnh cũ. Các ảnh mã nguồn 3.1/3.2 và ảnh giao diện không đổi theo lần clean này. Nếu đã có ảnh riêng của hai API Push hoặc `sw.js`, chỉ chụp lại đúng ảnh đó khi nó hiển thị dòng đã đổi; không bổ sung hàng loạt ảnh mới.

### 2. Mục 4.1.1 — liên kết xóa tài khoản

Thay ghi chú còn lệch 15 phút/5 phút bằng:

> Thời hạn liên kết xóa tài khoản là 5 phút; thông báo API, nội dung email và mã cấp token đã được đồng bộ trong đợt hoàn thiện ngày 17/11/2026.

Không thay thời hạn **đặt lại mật khẩu 15 phút** hoặc **thanh toán 15 phút**, vì chúng là các cơ chế khác.

### 3. Mục 4.6 — nhận định hạn chế

Bỏ nhận định thông báo xóa tài khoản còn ghi 15 phút vì đã sửa. Giữ hạn chế về mẫu số điện thoại nếu chưa có thay đổi khác. Có thể dùng:

> Thông báo thời hạn xóa tài khoản đã được đồng bộ với token và email ở mức 5 phút. Quy tắc số điện thoại giữa biểu mẫu đăng ký và hồ sơ vẫn cần được thống nhất và kiểm tra bằng cùng một bộ dữ liệu.

### 4. Bảng 7.3 và Mục 8.4.2 — CSRF của Push

Thay nội dung “chỉ kiểm tra khi có header/bỏ qua khi thiếu header” bằng:

> Các thao tác POST tới api/push/subscribe.php và api/push/send.php bắt buộc gửi X-GODMINTON-CSRF hợp lệ. Trường hợp thiếu, rỗng hoặc sai token trả HTTP 403; GET đọc trạng thái đăng ký vẫn được giữ. Các trường hợp này đã được kiểm tra bằng môi trường cô lập sau đợt hoàn thiện ngày 17/11/2026. Việc kiểm tra quyền sở hữu endpoint và kiểm toán đầy đủ hệ thống Push vẫn là phạm vi cần tiếp tục đánh giá.

Không kết luận từ sửa CSRF rằng toàn bộ hệ thống đã an toàn.

### 5. Mục 7.3/7.4 và 7.6/7.7 — cache và triển khai

Bổ sung ngắn:

> Service Worker của bản hoàn thiện sử dụng phiên bản godminton-v1.3.7-final-clean-20261117; chỉ xóa cache thuộc GODMINTON và quản lý tác vụ ghi cache để không làm hỏng phản hồi mạng khi lưu trữ thất bại. Những thao tác đặt mới, hủy và thanh toán vẫn yêu cầu kết nối máy chủ. Các thư mục nội bộ và công cụ được chặn qua HTTP; tác vụ nhắc lịch chạy bằng PHP CLI/cron.

Giữ ngày chụp của ảnh Service Worker cũ nếu đó là bằng chứng phiên kiểm thử cũ; không sửa chữ trên ảnh. Nếu báo cáo dùng ảnh đó để khẳng định phiên bản **cuối**, chụp lại đúng một ảnh worker sau khi triển khai. Không tự ghi đã có nhật ký cron hoặc đã nhận nhắc đúng đơn khi chưa có bằng chứng thật.

### 6. Chương 8 — bổ sung kết quả kiểm tra cuối

Thêm một đoạn hoặc một bảng nhỏ dưới phần kết quả kiểm thử, không thay các kết quả KT01–KT16 đã ghi:

| Nhóm kiểm tra ngày 17/11/2026 | Kết quả | Phạm vi |
|---|---|---|
| Cú pháp | 112 PHP, 29 JavaScript, 1 CJS đạt | Mã nguồn ngoài vendor; không thay thế kiểm thử chức năng. |
| Hồi quy sau clean | 44/44 đạt | JavaScript VM, PHP/SQLite cô lập; không giao dịch ngân hàng hay gửi Push thật. |
| Công cụ ZIP | 16/16 đạt | Dữ liệu giả lập, file ẩn, loại cấu hình thật và từ chối ghi đè. |
| Quy tắc HTTP Apache | 13/13 đạt | Cho phép đường dẫn công khai/webhook và chặn đường dẫn nội bộ; không xác nhận webhook SePay thật. |

Ghi riêng ngày và kết quả kiểm tra lại trên hosting sau khi nhóm triển khai. Không đổi ảnh thanh toán mô phỏng thành minh chứng chuyển tiền thật. Báo cáo vẫn phải phân biệt ảnh thông báo ưu đãi với bằng chứng cron nhắc đúng đơn và giữ các giới hạn đã nêu về tải, thiết bị, phân quyền, dữ liệu và dịch vụ ngoài.

## Bản nộp

Đợt clean chưa tạo commit/GitHub release, chưa thay báo cáo Word, chưa có Rubric.xlsx được cung cấp và không tạo video demo. Nhóm hoàn tất các phần đó trước khi chốt gói nộp. Công cụ ZIP mã nguồn không tự ghép các tài liệu này và không nên tải gói chứa `.git` lên hosting.
