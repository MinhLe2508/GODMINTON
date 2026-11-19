<?php

/**
 * includes/image-url.php
 * Nâng độ phân giải ảnh hiển thị cho các link ảnh bên ngoài.
 *
 * Link ảnh Google Maps trong DB đang là bản thumbnail (=w408-h306-k-no, chỉ rộng 408px).
 * Khi trang chi tiết kéo giãn ra ~1000px thì ảnh bị mờ/nhòe. Hàm này đổi tham số kích thước
 * ở cuối link sang chiều rộng lớn hơn (giữ nguyên tỉ lệ, bỏ crop) mà không cần sửa dữ liệu DB.
 */
if (!function_exists('godminton_hires_image')) {
    function godminton_hires_image($url, int $width = 1600) {
        if (!is_string($url) || $url === '') {
            return $url;
        }

        // Google (lh3.googleusercontent.com/...=w408-h306-k-no) -> =w{width}
        if (strpos($url, 'googleusercontent.com/') !== false) {
            return preg_replace('/=[whs]\d+(?:-[a-z0-9]+)*$/i', '=w' . $width, $url, 1);
        }

        // Unsplash (...?w=800&...) -> w={width}
        if (strpos($url, 'images.unsplash.com/') !== false) {
            return preg_replace('/([?&])w=\d+/', '${1}w=' . $width, $url, 1);
        }

        return $url;
    }
}
