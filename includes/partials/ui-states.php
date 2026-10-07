<?php
/**
 * includes/partials/ui-states.php
 * Component mẫu trạng thái UI phía server cho GODMINTON.
 * Giúp render sẵn empty, loading, error trực tiếp từ PHP khi cần.
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 */

if (!function_exists('render_empty_state')) {
    function render_empty_state($title = 'Không tìm thấy dữ liệu', $desc = 'Không có kết quả nào phù hợp.', $icon = 'fa-inbox', $actionText = '', $actionHref = '#') {
        ob_start(); ?>
        <div class="state-empty" data-aos="fade-up">
            <div class="state-empty-icon">
                <i class="fa-solid <?= htmlspecialchars($icon) ?>"></i>
            </div>
            <div class="state-empty-title"><?= htmlspecialchars($title) ?></div>
            <div class="state-empty-desc"><?= htmlspecialchars($desc) ?></div>
            <?php if (!empty($actionText)): ?>
                <a href="<?= htmlspecialchars($actionHref) ?>" class="btn btn-teal rounded-pill px-4 fw-semibold mt-2">
                    <?= htmlspecialchars($actionText) ?>
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('render_loading_state')) {
    function render_loading_state($text = 'Đang tải dữ liệu...') {
        ob_start(); ?>
        <div class="state-loading">
            <div class="loading-spinner-teal"></div>
            <div class="loading-text"><?= htmlspecialchars($text) ?></div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('render_error_state')) {
    function render_error_state($title = 'Không thể nạp dữ liệu', $desc = 'Đã có lỗi xảy ra trong quá trình xử lý.', $retryHref = '') {
        ob_start(); ?>
        <div class="state-error" data-aos="fade-up">
            <div class="state-error-icon">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <div class="state-error-title"><?= htmlspecialchars($title) ?></div>
            <div class="state-error-desc"><?= htmlspecialchars($desc) ?></div>
            <?php if (!empty($retryHref)): ?>
                <a href="<?= htmlspecialchars($retryHref) ?>" class="btn btn-outline-danger rounded-pill px-4 fw-semibold">
                    <i class="fa-solid fa-rotate-right me-2"></i>Thử lại ngay
                </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
