<?php
/**
 * api/auth/me.php  (file bổ sung ngoài danh sách ban đầu)
 * GET — trả user đang đăng nhập theo session PHP; 401 nếu chưa đăng nhập.
 * Dùng để các trang (Minh/Triết) biết trạng thái đăng nhập thật thay cho MockDB.getUser().
 *
 * Người phụ trách: Huy (Tuần 1)
 */
require_once __DIR__ . '/../../includes/api-helpers.php';
api_start();
api_require_method('GET');
api_ok(['user' => api_require_login()]);
