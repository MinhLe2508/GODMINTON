/** GODMINTON: branded SweetAlert2 defaults. Load after SweetAlert2. */
(function () {
    'use strict';
    const OriginalSwal = window.Swal;
    if (!OriginalSwal || typeof OriginalSwal.mixin !== 'function') return;
    if (OriginalSwal.godmintonDefaultsApplied) return;

    // Keep the parent constructor's fire intact. Mixin inherits static fire;
    // overriding the parent fire with a call to the child would recurse forever.
    const BrandedSwal = OriginalSwal.mixin({
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#94a3b8',
        denyButtonColor: '#ef4444',
        buttonsStyling: true,
        showClass: {popup:'swal2-show',backdrop:'swal2-backdrop-show',icon:'swal2-icon-show'},
        hideClass: {popup:'swal2-hide',backdrop:'swal2-backdrop-hide',icon:'swal2-icon-hide'}
    });
    Object.defineProperty(BrandedSwal, 'godmintonDefaultsApplied', {value:true});
    window.Swal = BrandedSwal;
    window.GodmintonSwal = BrandedSwal;
    if (window.swal === OriginalSwal) window.swal = BrandedSwal;
})();
