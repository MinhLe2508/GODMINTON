<?php
/**
 * api/admin/upload-post-image.php
 * Endpoint tải ảnh bìa bài viết dành riêng cho Quản trị viên (Admin).
 * Tương thích mọi cấu hình Shared Hosting (không phụ thuộc cứng vào ext-fileinfo).
 */

require_once __DIR__ . '/../../includes/booking-account-api.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    booking_account_out(405, null, 'Chỉ chấp nhận phương thức POST.');
}

/**
 * Nhận diện MIME thực tế của file ảnh mà không phụ thuộc cứng vào extension fileinfo trên Shared Hosting.
 */
function god_detect_image_mime(string $tmpPath): string
{
    if (!is_file($tmpPath) || !is_readable($tmpPath)) {
        return '';
    }

    // 1. Ưu tiên getimagesize() (hàm có sẵn trong nhân PHP, kiểm tra cả header ảnh và kích thước thực)
    if (function_exists('getimagesize')) {
        $info = @getimagesize($tmpPath);
        if (is_array($info) && !empty($info['mime']) && ($info[0] ?? 0) > 0 && ($info[1] ?? 0) > 0) {
            return strtolower((string)$info['mime']);
        }
    }

    // 2. Dùng finfo nếu hosting có bật extension fileinfo
    if (class_exists('finfo') && defined('FILEINFO_MIME_TYPE')) {
        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpPath);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        } catch (Throwable $e) {
            // Bỏ qua để dùng fallback bên dưới
        }
    }

    // 3. Dùng mime_content_type nếu có
    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($tmpPath);
        if (is_string($mime) && $mime !== '') {
            return strtolower($mime);
        }
    }

    // 4. Kiểm tra chữ ký nhị phân (Magic Bytes) đầu file
    $fh = @fopen($tmpPath, 'rb');
    if ($fh) {
        $head = (string)@fread($fh, 16);
        @fclose($fh);
        if (strncmp($head, "\xFF\xD8\xFF", 3) === 0) {
            return 'image/jpeg';
        }
        if (strncmp($head, "\x89PNG\r\n\x1A\n", 8) === 0) {
            return 'image/png';
        }
        if ( strlen($head) >= 12 && substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
        if (strncmp($head, 'GIF87a', 6) === 0 || strncmp($head, 'GIF89a', 6) === 0) {
            return 'image/gif';
        }
    }

    return '';
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
        booking_account_out(422, null, 'Không nhận được file ảnh (có thể dung lượng vượt giới hạn post_max_size của hosting).');
    }

    $file = $_FILES['image'];
    $errCode = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errCode !== UPLOAD_ERR_OK) {
        $msg = match ($errCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dung lượng ảnh vượt quá giới hạn của máy chủ (tối đa 5 MiB).',
            UPLOAD_ERR_PARTIAL => 'File ảnh tải lên chưa hoàn tất. Vui lòng thử lại.',
            UPLOAD_ERR_NO_FILE => 'Chưa chọn file ảnh nào.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'Máy chủ tạm thời không thể ghi file tạm.',
            default => 'Lỗi khi nhận file tải lên (mã lỗi ' . $errCode . ').'
        };
        booking_account_out(422, null, $msg);
    }

    // Giới hạn dung lượng tối đa 5 MiB (5 * 1024 * 1024 bytes)
    $maxBytes = 5 * 1024 * 1024;
    if ((int)($file['size'] ?? 0) > $maxBytes) {
        booking_account_out(422, null, 'Dung lượng ảnh không được vượt quá 5 MiB.');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !file_exists($tmpName)) {
        booking_account_out(422, null, 'Không tìm thấy tệp tạm trên máy chủ.');
    }

    // 4. Kiểm tra loại MIME thực tế (không phụ thuộc cứng vào class finfo)
    $mime = god_detect_image_mime($tmpName);

    $allowedMimes = [
        'image/jpeg'  => 'jpg',
        'image/jpg'   => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png'   => 'png',
        'image/x-png' => 'png',
        'image/webp'  => 'webp',
        'image/gif'   => 'gif',
    ];

    if (!isset($allowedMimes[$mime])) {
        booking_account_out(422, null, 'Chỉ hỗ trợ định dạng ảnh JPG, PNG, WEBP hoặc GIF hợp lệ.');
    }

    $ext = $allowedMimes[$mime];

    // 5. Chuẩn bị thư mục lưu ảnh an toàn
    $rootDir = realpath(__DIR__ . '/../..') ?: (__DIR__ . '/../..');
    $targetDir = $rootDir . '/assets/img/posts';
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }
    @chmod($targetDir, 0755);

    // Đảm bảo file .htaccess tương thích Shared Hosting (không dùng Options -ExecCGI gây lỗi 500 trên LiteSpeed/Apache)
    $htaccessFile = $targetDir . '/.htaccess';
    $safeHtaccess = "# Chống thực thi script PHP trong thư mục ảnh upload\n"
        . "<FilesMatch \"\\.(php|php[0-9]|phtml|phar|cgi|pl)$\">\n"
        . "    <IfModule mod_authz_core.c>\n"
        . "        Require all denied\n"
        . "    </IfModule>\n"
        . "    <IfModule !mod_authz_core.c>\n"
        . "        Order Deny,Allow\n"
        . "        Deny from all\n"
        . "    </IfModule>\n"
        . "</FilesMatch>\n";
    if (!file_exists($htaccessFile) || str_contains((string)@file_get_contents($htaccessFile), '-ExecCGI')) {
        @file_put_contents($htaccessFile, $safeHtaccess);
    }

    // 6. Sinh tên file ngẫu nhiên an toàn tuyệt đối
    $filename = 'post_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $destination = $targetDir . '/' . $filename;

    $saved = @move_uploaded_file($tmpName, $destination);
    if (!$saved && is_readable($tmpName)) {
        $saved = @copy($tmpName, $destination);
    }
    if (!$saved && is_readable($tmpName)) {
        $raw = @file_get_contents($tmpName);
        if ($raw !== false) {
            $saved = (@file_put_contents($destination, $raw) !== false);
        }
    }

    if ($saved && is_file($destination)) {
        @chmod($destination, 0644);
        $publicUrl = 'assets/img/posts/' . $filename;
        booking_account_out(200, ['url' => $publicUrl], 'Tải ảnh lên thành công.');
    }

    // Dự phòng cuối: Nếu thư mục trên hosting bị khóa quyền ghi (Read-Only), nâng cấp cột cover_image và trả về Data URI
    $rawBytes = @file_get_contents($tmpName);
    if ($rawBytes !== false && strlen($rawBytes) <= 2 * 1024 * 1024) {
        try {
            $pdo->exec("ALTER TABLE posts MODIFY COLUMN cover_image MEDIUMTEXT NULL");
        } catch (Throwable $ignore) {
        }
        $dataUri = 'data:' . $mime . ';base64,' . base64_encode($rawBytes);
        booking_account_out(200, ['url' => $dataUri], 'Tải ảnh lên thành công.');
    }

    booking_account_out(500, null, 'Không thể lưu file ảnh vào thư mục assets/img/posts (kiểm tra quyền ghi CHMOD 755 trên hosting).');
} catch (Throwable $e) {
    error_log('[GODMINTON upload-post-image] ' . $e->getMessage());
    booking_account_out(500, null, 'Lỗi khi tải ảnh lên: ' . $e->getMessage());
}
