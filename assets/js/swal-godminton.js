/** GODMINTON: branded SweetAlert2 defaults with clean CDN vector icons. */
(function () {
    'use strict';
    const OriginalSwal = window.Swal;
    if (!OriginalSwal || typeof OriginalSwal.mixin !== 'function') return;
    if (OriginalSwal.godmintonDefaultsApplied) return;

    // Bộ icon vector chuẩn FontAwesome từ CDN (không bao giờ bị méo mó, lệch vạch hay lỗi hình khối)
    const iconHtmlMap = {
        success: '<i class="fa-solid fa-circle-check"></i>',
        warning: '<i class="fa-solid fa-triangle-exclamation"></i>',
        error: '<i class="fa-solid fa-circle-xmark"></i>',
        info: '<i class="fa-solid fa-circle-info"></i>',
        question: '<i class="fa-solid fa-circle-question"></i>'
    };

    const BrandedSwal = OriginalSwal.mixin({
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#94a3b8',
        denyButtonColor: '#ef4444',
        buttonsStyling: true,
        showClass: { popup: 'swal2-show', backdrop: 'swal2-backdrop-show', icon: 'swal2-icon-show' },
        hideClass: { popup: 'swal2-hide', backdrop: 'swal2-backdrop-hide', icon: 'swal2-icon-hide' }
    });

    // Ngăn Bootstrap 5 Modal FocusTrap cướp focus của các ô input/textarea bên trong SweetAlert2
    document.addEventListener('focusin', function (e) {
        if (e.target && typeof e.target.closest === 'function' && e.target.closest('.swal2-container')) {
            e.stopImmediatePropagation();
        }
    }, true);

    const originalFire = BrandedSwal.fire.bind(BrandedSwal);

    BrandedSwal.fire = function (...args) {
        let opts = {};
        if (args.length === 1 && typeof args[0] === 'object' && args[0] !== null) {
            opts = { ...args[0] };
        } else if (typeof args[0] === 'string') {
            opts.title = args[0];
            if (typeof args[1] === 'string') opts.html = args[1];
            if (typeof args[2] === 'string') opts.icon = args[2];
        }

        // Tự động gán iconHtml chuẩn vector nếu chưa có iconHtml riêng
        if (opts.icon && iconHtmlMap[opts.icon] && !opts.iconHtml) {
            opts.iconHtml = iconHtmlMap[opts.icon];
        }

        if (!opts.toast) {
            const userDidOpen = opts.didOpen;
            const userDidClose = opts.didClose;
            opts.didOpen = function (popup) {
                if (window.bootstrap && window.bootstrap.Modal) {
                    document.querySelectorAll('.modal.show').forEach(function (m) {
                        const inst = window.bootstrap.Modal.getInstance(m);
                        if (inst && inst._focustrap && typeof inst._focustrap.deactivate === 'function') {
                            inst._focustrap.deactivate();
                        }
                    });
                }
                if (typeof userDidOpen === 'function') userDidOpen(popup);
            };
            opts.didClose = function () {
                if (window.bootstrap && window.bootstrap.Modal) {
                    document.querySelectorAll('.modal.show').forEach(function (m) {
                        const inst = window.bootstrap.Modal.getInstance(m);
                        if (inst && inst._focustrap && typeof inst._focustrap.activate === 'function') {
                            inst._focustrap.activate();
                        }
                    });
                }
                if (typeof userDidClose === 'function') userDidClose();
            };
        }

        return originalFire(opts);
    };

    Object.defineProperty(BrandedSwal, 'godmintonDefaultsApplied', { value: true });
    window.Swal = BrandedSwal;
    window.GodmintonSwal = BrandedSwal;
    if (window.swal === OriginalSwal) window.swal = BrandedSwal;
})();
