<?php
/**
 * api/admin/upload-post-image.php
 * Endpoint tải ảnh bìa bài viết dành riêng cho Quản trị viên (Admin).
 * Kiểm tra nghiêm ngặt loại file thực tế (MIME), kích thước và chặn thực thi PHP.
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    booking_account_out(405, null, 'Chỉ chấp nhận phương thức POST.');
}

try {
    // 1. Kiểm tra xác thực & vai trò Admin
    $user = booking_account_user();
    if (($user['role'] ?? '') !== 'admin') {
        booking_account_out(403, null, 'Chỉ Quản trị viên mới có quyền tải ảnh lên.');
    }

    // 2. Kiểm tra CSRF token
    booking_account_csrf();

    // 3. Kiểm tra file upload
    if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
        booking_account_out(422, null, 'Không tìm thấy file ảnh được gửi lên.');
    }

    $file = $_FILES['image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = match ($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dung lượng ảnh vượt quá giới hạn cho phép (tối đa 5 MiB).',
            UPLOAD_ERR_PARTIAL => 'File ảnh tải lên chưa hoàn tất.',
            UPLOAD_ERR_NO_FILE => 'Chưa chọn file ảnh nào.',
            default => 'Lỗi khi nhận file tải lên.'
        };
        booking_account_out(422, null, $msg);
    }

    // Giới hạn dung lượng tối đa 5 MiB (5 * 1024 * 1024 bytes)
    $maxBytes = 5 * 1024 * 1024;
    if ($file['size'] > $maxBytes) {
        booking_account_out(422, null, 'Dung lượng ảnh không được vượt quá 5 MiB.');
    }

    // 4. Kiểm tra loại MIME thực tế qua finfo (không dựa vào phần mở rộng hay header client)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimes[$mime])) {
        booking_account_out(422, null, 'Chỉ hỗ trợ định dạng ảnh JPG, PNG hoặc WEBP.');
    }

    $ext = $allowedMimes[$mime];

    // 5. Chuẩn bị thư mục lưu ảnh an toàn
    $targetDir = __DIR__ . '/../../assets/img/posts';
    if (!is_dir($targetDir)) {
        if (!mkdir($targetDir, 0755, true)) {
            booking_account_out(500, null, 'Không thể tạo thư mục lưu trữ ảnh.');
        }
    }

    // Đảm bảo file .htaccess cấm thực thi mã PHP trong thư mục ảnh
    $htaccessFile = $targetDir . '/.htaccess';
    if (!file_exists($htaccessFile)) {
        $htaccessContent = "# Chống thực thi script PHP trong thư mục ảnh upload\n"
            . "<FilesMatch \"\\.(php|php[0-9]|phtml|phar|cgi|pl)$\">\n"
            . "    Order Deny,Allow\n"
            . "    Deny from all\n"
            . "</FilesMatch>\n"
            . "Options -Indexes -ExecCGI\n";
        @file_put_contents($htaccessFile, $htaccessContent);
    }

    // 6. Sinh tên file ngẫu nhiên an toàn tuyệt đối
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destination = $targetDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        booking_account_out(500, null, 'Không thể lưu file ảnh vào máy chủ.');
    }

    $publicUrl = 'assets/img/posts/' . $filename;
    booking_account_out(200, ['url' => $publicUrl], 'Tải ảnh lên thành công.');
} catch (Throwable $e) {
    error_log('[GODMINTON upload-post-image] ' . $e->getMessage());
    booking_account_out(500, null, 'Lỗi hệ thống khi tải ảnh lên.');
}
