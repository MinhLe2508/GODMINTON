/**
 * GODMINTON — UI STATES & FEEDBACK HELPER
 * Cung cấp hàm render mẫu cho 5 trạng thái giao diện chuẩn:
 * 1. Trạng thái rỗng (Empty State)
 * 2. Trạng thái đang tải (Loading Spinner & Skeleton)
 * 3. Trạng thái lỗi (Error State)
 * 4. Toast notification (Thành công, Lỗi, Cảnh báo, Thông tin)
 * 5. Disabled / Loading cho nút khi đang submit form
 * 
 * Người phụ trách: P3 (Trương Minh Triết)
 */
(function(window) {
    'use strict';

    const GodmintonUI = {
        // --- 1. TOAST NOTIFICATION ---
        toast: function(type = 'success', title = '', message = '', duration = 3500) {
            let container = document.getElementById('godmintonToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'godmintonToastContainer';
                document.body.appendChild(container);
            }

            const icons = {
                success: 'fa-circle-check',
                error: 'fa-circle-xmark',
                warning: 'fa-triangle-exclamation',
                info: 'fa-circle-info'
            };

            const titles = {
                success: 'Thành công',
                error: 'Đã xảy ra lỗi',
                warning: 'Cảnh báo',
                info: 'Thông báo'
            };

            const toast = document.createElement('div');
            toast.className = `gm-toast toast-${type}`;
            toast.innerHTML = `
                <i class="fa-solid ${icons[type] || 'fa-bell'} toast-icon"></i>
                <div class="toast-content">
                    <div class="toast-title">${title || titles[type]}</div>
                    ${message ? `<div class="toast-msg">${message}</div>` : ''}
                </div>
                <button type="button" class="toast-close" aria-label="Đóng">&times;</button>
            `;

            container.appendChild(toast);

            const removeToast = () => {
                toast.style.animation = 'toastFadeOut 0.25s forwards';
                setTimeout(() => toast.remove(), 250);
            };

            toast.querySelector('.toast-close').addEventListener('click', removeToast);
            setTimeout(removeToast, duration);
        },

        // --- 2. RENDER EMPTY STATE ---
        renderEmpty: function(container, {
            icon = 'fa-inbox',
            title = 'Không tìm thấy dữ liệu',
            desc = 'Không có kết quả nào phù hợp với yêu cầu tìm kiếm hiện tại.',
            actionText = '',
            actionHref = '#',
            actionClick = null
        } = {}) {
            const target = typeof container === 'string' ? document.getElementById(container) : container;
            if (!target) return;

            let actionHtml = '';
            if (actionText) {
                actionHtml = `<a href="${actionHref}" class="btn btn-teal rounded-pill px-4 fw-semibold mt-2" id="emptyActionBtn">${actionText}</a>`;
            }

            target.innerHTML = `
                <div class="state-empty" data-aos="fade-up">
                    <div class="state-empty-icon">
                        <i class="fa-solid ${icon}"></i>
                    </div>
                    <div class="state-empty-title">${title}</div>
                    <div class="state-empty-desc">${desc}</div>
                    ${actionHtml}
                </div>
            `;

            if (actionClick && target.querySelector('#emptyActionBtn')) {
                target.querySelector('#emptyActionBtn').addEventListener('click', function(e) {
                    if (actionHref === '#') e.preventDefault();
                    actionClick(e);
                });
            }
        },

        // --- 3. RENDER LOADING STATE (SPINNER & SKELETON) ---
        renderLoading: function(container, text = 'Đang tải dữ liệu...') {
            const target = typeof container === 'string' ? document.getElementById(container) : container;
            if (!target) return;

            target.innerHTML = `
                <div class="state-loading">
                    <div class="loading-spinner-teal"></div>
                    <div class="loading-text">${text}</div>
                </div>
            `;
        },

        renderSkeletons: function(container, count = 3) {
            const target = typeof container === 'string' ? document.getElementById(container) : container;
            if (!target) return;

            let html = '<div class="row g-4">';
            for (let i = 0; i < count; i++) {
                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="skeleton-card">
                            <div class="skeleton-shimmer skeleton-img"></div>
                            <div class="skeleton-body">
                                <div class="skeleton-shimmer skeleton-line title"></div>
                                <div class="skeleton-shimmer skeleton-line half"></div>
                                <div class="skeleton-shimmer skeleton-line quarter"></div>
                            </div>
                        </div>
                    </div>
                `;
            }
            html += '</div>';
            target.innerHTML = html;
        },

        // --- 4. RENDER ERROR STATE ---
        renderError: function(container, {
            title = 'Không thể nạp dữ liệu',
            desc = 'Đã có lỗi xảy ra trong quá trình kết nối tới máy chủ. Vui lòng thử lại.',
            retryText = 'Thử lại ngay',
            onRetry = null
        } = {}) {
            const target = typeof container === 'string' ? document.getElementById(container) : container;
            if (!target) return;

            target.innerHTML = `
                <div class="state-error" data-aos="fade-up">
                    <div class="state-error-icon">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div class="state-error-title">${title}</div>
                    <div class="state-error-desc">${desc}</div>
                    ${retryText ? `<button class="btn btn-outline-danger rounded-pill px-4 fw-semibold" id="btnStateRetry"><i class="fa-solid fa-rotate-right me-2"></i>${retryText}</button>` : ''}
                </div>
            `;

            if (onRetry && target.querySelector('#btnStateRetry')) {
                target.querySelector('#btnStateRetry').addEventListener('click', onRetry);
            }
        },

        // --- 5. BUTTON LOADING / DISABLED STATE ---
        setButtonLoading: function(btn, loadingText = 'Đang xử lý...') {
            const button = typeof btn === 'string' ? document.getElementById(btn) : btn;
            if (!button) return;

            if (!button.dataset.originalHtml) {
                button.dataset.originalHtml = button.innerHTML;
            }
            button.disabled = true;
            button.classList.add('btn-is-loading');
            button.innerHTML = `<span class="btn-spinner"></span>${loadingText}`;
        },

        resetButton: function(btn) {
            const button = typeof btn === 'string' ? document.getElementById(btn) : btn;
            if (!button) return;

            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
            }
            button.disabled = false;
            button.classList.remove('btn-is-loading');
        }
    };

    window.GodmintonUI = GodmintonUI;
})(window);
