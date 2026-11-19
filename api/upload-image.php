<?php

/**
 * api/upload-image.php
 * Endpoint tải ảnh đại diện cá nhân (avatar) hoặc ảnh đại diện sân cầu lông (court).
 * Tương thích mọi cấu hình Shared Hosting (không phụ thuộc cứng vào ext-fileinfo).
 */

require_once __DIR__ . '/../includes/booking-account-api.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    booking_account_out(405, null, 'Chỉ chấp nhận phương thức POST.');
}

function god_detect_uploaded_image_mime(string $tmpPath): string {
    if (!is_file($tmpPath) || !is_readable($tmpPath)) {
        return '';
    }

    if (function_exists('getimagesize')) {
        $info = @getimagesize($tmpPath);
        if (is_array($info) && !empty($info['mime']) && ($info[0] ?? 0) > 0 && ($info[1] ?? 0) > 0) {
            return strtolower((string) $info['mime']);
        }
    }

    if (class_exists('finfo') && defined('FILEINFO_MIME_TYPE')) {
        try {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($tmpPath);
            if (is_string($mime) && $mime !== '') {
                return strtolower($mime);
            }
        } catch (Throwable $e) {
        }
    }

    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($tmpPath);
        if (is_string($mime) && $mime !== '') {
            return strtolower($mime);
        }
    }

    $fh = @fopen($tmpPath, 'rb');
    if ($fh) {
        $head = (string) @fread($fh, 16);
        @fclose($fh);
        if (strncmp($head, "\xFF\xD8\xFF", 3) === 0) {
            return 'image/jpeg';
        }

        if (strncmp($head, "\x89PNG\r\n\x1A\n", 8) === 0) {
            return 'image/png';
        }

        if (strlen($head) >= 12 && substr($head, 0, 4) === 'RIFF' && substr($head, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
    }

    return '';
}

try {
    $user = booking_account_user();
    booking_account_csrf();

    $type = strtolower(trim((string) ($_POST['type'] ?? $_GET['type'] ?? 'avatar')));
    if (!in_array($type, ['avatar', 'court'], true)) {
        $type = 'avatar';
    }

    if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
        booking_account_out(422, null, 'Không nhận được file ảnh (có thể dung lượng vượt giới hạn của máy chủ).');
    }

    $file = $_FILES['image'];
    $errCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($errCode !== UPLOAD_ERR_OK) {
        $msg = match ($errCode) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Dung lượng ảnh vượt quá giới hạn máy chủ (tối đa 5 MiB).',
            UPLOAD_ERR_PARTIAL => 'File ảnh tải lên chưa hoàn tất. Vui lòng thử lại.',
            UPLOAD_ERR_NO_FILE => 'Chưa chọn file ảnh nào.',
            default => 'Lỗi khi tải file ảnh lên (mã lỗi ' . $errCode . ').'
        };
        booking_account_out(422, null, $msg);
    }

    $maxBytes = 5 * 1024 * 1024;
    if ((int) ($file['size'] ?? 0) > $maxBytes) {
        booking_account_out(422, null, 'Dung lượng ảnh không được vượt quá 5 MiB.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    if ($tmpName === '' || !file_exists($tmpName)) {
        booking_account_out(422, null, 'Không tìm thấy tệp tạm trên máy chủ.');
    }

    $mime = god_detect_uploaded_image_mime($tmpName);
    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimes[$mime])) {
        booking_account_out(422, null, 'Định dạng ảnh không hợp lệ. Chỉ chấp nhận JPG, PNG hoặc WEBP.');
    }

    $ext = $allowedMimes[$mime];
    $subFolder = $type === 'court' ? 'courts' : 'avatars';
    $uploadDir = dirname(__DIR__) . '/assets/img/' . $subFolder;

    if (!is_dir($uploadDir)) {
        if (!@mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            booking_account_out(500, null, 'Không thể tạo thư mục lưu trữ ảnh trên máy chủ.');
        }
    }

    $htaccessPath = $uploadDir . '/.htaccess';
    if (!file_exists($htaccessPath)) {
        @file_put_contents(
            $htaccessPath,
            "<FilesMatch \"\\.(php|php5|php7|php8|phtml|pl|py|jsp|asp|sh|cgi)$\">\n    Require all denied\n</FilesMatch>\n"
        );
    }

    $userId = (int) $user['id'];
    $filename = sprintf('%s_%d_%s_%s.%s', $type, $userId, date('Ymd_His'), bin2hex(random_bytes(4)), $ext);
    $destPath = $uploadDir . '/' . $filename;

    $moved = @move_uploaded_file($tmpName, $destPath);
    if (!$moved) {
        $moved = @copy($tmpName, $destPath);
    }

    if (!$moved || !file_exists($destPath)) {
        booking_account_out(500, null, 'Không thể lưu file ảnh lên máy chủ. Vui lòng kiểm tra quyền ghi thư mục assets/img/' . $subFolder . '.');
    }

    @chmod($destPath, 0644);
    $publicUrl = 'assets/img/' . $subFolder . '/' . $filename;

    // Nếu là ảnh đại diện cá nhân -> lưu luôn vào cột avatar_url của bảng users
    if ($type === 'avatar') {
        $stmt = $pdo->prepare('UPDATE users SET avatar_url = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$publicUrl, $userId]);
        $_SESSION['avatar_url'] = $publicUrl;
    }

    booking_account_out(200, [
        'url' => $publicUrl,
        'filename' => $filename,
        'type' => $type
    ], $type === 'avatar' ? 'Đã cập nhật ảnh đại diện thành công.' : 'Tải ảnh đại diện sân thành công.');
} catch (Throwable $e) {
    error_log('[GODMINTON api/upload-image.php] ' . $e->getMessage());
    booking_account_out(500, null, 'Lỗi máy chủ khi xử lý tải ảnh lên.');
}
