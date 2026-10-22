<?php
require_once __DIR__.'/includes/auth-check.php';
$workflowUser=god_auth_user($pdo);
header('Cache-Control: no-store');
?>
<!doctype html><html lang="vi" data-bs-theme="light"><head><title>Đăng ký sân | GODMINTON</title><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/style.css?v=<?=filemtime(__DIR__.'/assets/css/style.css')?>">
<link rel="stylesheet" href="assets/css/admin_style.css?v=<?=filemtime(__DIR__.'/assets/css/admin_style.css')?>">
<link rel="stylesheet" href="assets/css/swal-godminton.css?v=<?=filemtime(__DIR__.'/assets/css/swal-godminton.css')?>">
<link rel="stylesheet" href="assets/css/owner-account-workflow.css?v=<?=filemtime(__DIR__.'/assets/css/owner-account-workflow.css')?>">
<script>window.__GODMINTON_USER__=<?=json_encode($workflowUser,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE)?>;</script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"></head>
<body data-workflow-page="application"><header class="wf-public-header"><a href="index.php"><img src="assets/img/logo-teal.svg" width="150" alt="GODMINTON"></a><div class="d-flex gap-2"><a href="profile.php" class="btn btn-outline-secondary rounded-pill">Trang cá nhân</a><?php if(in_array($workflowUser['role'],['owner','admin'],true)):?><a href="admin-courts.php" class="btn btn-teal rounded-pill">Kênh quản lý sân</a><?php endif;?><button class="btn btn-outline-secondary rounded-circle" id="themeToggle" aria-label="Đổi giao diện"><i class="fa-solid fa-moon"></i></button></div></header>
<main class="container py-4 wf-public-main"><section class="wf-hero mb-4"><span class="wf-eyebrow">ĐỒNG HÀNH CÙNG GODMINTON</span><h1>Đưa sân của bạn đến gần người chơi</h1><p>Đăng ký sân mới hoặc nhận quản lý sân đã có. Bạn sẽ được cấp quyền sau khi hồ sơ được Admin duyệt.</p><ol class="wf-steps"><li>1. Hoàn thiện hồ sơ</li><li>2. Admin xét duyệt</li><li>3. Tạo lịch sân</li><li>4. Mở nhận đặt</li></ol></section>
<div id="applicationNotices" aria-live="polite"></div>
<div class="row g-4"><div class="col-xl-7"><section class="wf-card"><div class="wf-heading"><h2 id="applicationTitle">Hồ sơ đăng ký sân</h2><button type="button" class="btn btn-outline-secondary btn-sm" id="newApplication">Hồ sơ mới</button></div>
<form id="ownerApplicationForm" novalidate><input type="hidden" name="id"><input type="hidden" name="revision">
<label class="form-label">Loại hồ sơ<select name="kind" class="form-select"><option value="new">Đăng ký sân mới</option><option value="claim">Nhận quản lý sân đã có</option></select></label>
<div id="claimFields" hidden class="mt-3"><label class="form-label">Tìm sân đã có</label><div class="input-group"><input id="claimSearch" class="form-control" placeholder="Nhập tên hoặc địa chỉ, tối thiểu 2 ký tự"><button class="btn btn-outline-success" id="searchExistingCourt" type="button">Tìm sân</button></div><select name="court_id" id="claimCourt" class="form-select mt-2" aria-label="Chọn sân cần nhận quản lý"><option value="">Chọn sân từ kết quả tìm kiếm</option></select><p class="wf-help">Nếu sân đã có người quản lý, Admin phải kiểm tra và xác nhận chuyển giao riêng.</p></div>
<div id="newCourtFields"><div class="row g-3 mt-1">
<label class="col-md-12">Tên cụm sân<input name="name" class="form-control" maxlength="150" placeholder="Tên sân đang hoạt động"></label>
<label class="col-md-12">Địa chỉ<input name="address" class="form-control" maxlength="255" placeholder="Số nhà, đường, phường"></label>
<label class="col-md-12">Phường / khu vực<select name="ward_id" id="applicationWard" class="form-select"></select></label>
<div class="col-12"><label class="form-label">Vị trí trên bản đồ</label><div id="ownerPinMap" aria-label="Chọn vị trí sân trên bản đồ"></div><p class="wf-help">Bấm trên bản đồ hoặc kéo ghim. Bạn cũng có thể nhập tọa độ bên dưới.</p></div>
<label class="col-sm-6">Vĩ độ<input name="latitude" class="form-control" type="number" step="0.0000001" placeholder="10.7769"></label><label class="col-sm-6">Kinh độ<input name="longitude" class="form-control" type="number" step="0.0000001" placeholder="106.7009"></label>
<label class="col-sm-6">Giá / giờ (đ)<input name="price_per_hour" class="form-control" type="number" min="1" max="10000000"></label><label class="col-sm-6">Số sân con<input name="total_sub_courts" class="form-control" type="number" min="1" max="100" value="1"></label>
<label class="col-sm-6">Giờ mở cửa<input name="opening_time" class="form-control" type="time" value="06:00"></label><label class="col-sm-6">Giờ đóng cửa<input name="closing_time" class="form-control" type="time" value="22:00"></label>
<label class="col-12">Ảnh thực tế của sân (URL HTTPS)<input name="image_url" class="form-control" type="url" placeholder="https://..."></label>
<label class="col-12">Giới thiệu sân<textarea name="description" class="form-control" rows="3" maxlength="5000"></textarea></label>
<div class="col-12"><label class="form-label">Tiện ích</label><div id="applicationAmenities" class="wf-checks"></div></div></div></div>
<div class="row g-3 mt-2"><label class="col-sm-6">Người đại diện<input name="representative_name" class="form-control" maxlength="150" autocomplete="name"></label><label class="col-sm-6">Số liên hệ<input name="representative_phone" class="form-control" type="tel" maxlength="30" autocomplete="tel"></label>
<label class="col-12">Thông tin xác nhận quyền quản lý<textarea name="evidence_note" class="form-control" rows="4" maxlength="5000" placeholder="Mô tả vai trò của bạn, thông tin hoạt động của sân và cách Admin có thể đối chiếu (ít nhất 20 ký tự khi gửi)."></textarea></label>
<label class="col-12">Liên kết tham khảo / tài liệu (HTTPS, không bắt buộc)<input name="evidence_url" class="form-control" type="url" maxlength="2000" placeholder="Trang chính thức hoặc tài liệu được chia sẻ cho Admin"></label></div>
<p class="wf-help mt-3">Thông tin xác nhận quyền quản lý chỉ được hiển thị cho bạn và Admin; không đưa lên trang sân công khai.</p>
<div class="d-flex flex-wrap gap-2 mt-4"><button type="button" class="btn btn-outline-secondary rounded-pill" id="saveApplication">Lưu nháp</button><button type="submit" class="btn btn-teal rounded-pill px-4" id="submitApplication">Gửi xét duyệt</button></div><p id="applicationMessage" class="mt-3 mb-0" role="status"></p>
</form></section></div>
<div class="col-xl-5"><section class="wf-card"><div class="wf-heading"><h2>Hồ sơ của bạn</h2><button class="btn btn-outline-secondary btn-sm" id="refreshApplications">Tải lại</button></div><div id="myApplications">Đang tải…</div></section><section class="wf-card mt-4" id="myApplicationDetail" hidden></section></div></div>
</main><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/swal-godminton.js?v=<?=filemtime(__DIR__.'/assets/js/swal-godminton.js')?>"></script>
<script src="assets/js/booking-account-client.js?v=<?=filemtime(__DIR__.'/assets/js/booking-account-client.js')?>"></script>
<script src="assets/js/app.js?v=<?=filemtime(__DIR__.'/assets/js/app.js')?>"></script>
<script src="assets/js/admin.js?v=<?=filemtime(__DIR__.'/assets/js/admin.js')?>"></script>
<script src="assets/js/owner-account-ui.js?v=<?=filemtime(__DIR__.'/assets/js/owner-account-ui.js')?>"></script><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script><script src="assets/js/owner-application.js?v=<?=filemtime(__DIR__.'/assets/js/owner-application.js')?>"></script></body></html>
