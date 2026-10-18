<!-- PWA Install Prompt Banner -->
<div id="pwaInstallBanner" class="pwa-install-banner">
    <div class="pwa-install-card">
        <div class="d-flex align-items-center gap-3">
            <img src="assets/img/icons/icon-192.png" alt="GODMINTON Icon" class="pwa-app-icon">
            <div class="flex-grow-1">
                <div class="pwa-title">Cài Đặt App GODMINTON</div>
                <div class="pwa-desc">Đặt sân nhanh chóng, nhận thông báo nhắc lịch chơi!</div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-teal btn-sm rounded-pill px-3 fw-bold" id="btnBannerInstall">
                    <i class="fa-solid fa-download me-1"></i> Cài đặt
                </button>
                <button type="button" class="btn-close pwa-close-btn" id="btnBannerDismiss" aria-label="Đóng"></button>
            </div>
        </div>
    </div>
</div>

<style>
.pwa-install-banner {
    position: fixed;
    bottom: calc(76px + env(safe-area-inset-bottom, 0px));
    left: 16px;
    right: 16px;
    z-index: 1045;
    max-width: 480px;
    margin: 0 auto;
    display: none;
    animation: pwaSlideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
.pwa-install-banner.show {
    display: block !important;
}
@media (min-width: 768px) {
    .pwa-install-banner {
        right: auto;
        left: 24px;
        bottom: 24px;
    }
}
.pwa-install-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(13, 148, 136, 0.25);
    border-radius: 1.25rem;
    padding: 0.85rem 1.15rem;
    box-shadow: 0 16px 36px -10px rgba(13, 148, 136, 0.28), 0 0 1px 1px rgba(0,0,0,0.04);
}
[data-bs-theme="dark"] .pwa-install-card {
    background: rgba(17, 24, 39, 0.95);
    border-color: rgba(45, 212, 191, 0.28);
    box-shadow: 0 16px 36px -10px rgba(0, 0, 0, 0.5);
}
.pwa-app-icon {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    object-fit: cover;
    box-shadow: 0 4px 10px rgba(13, 148, 136, 0.25);
}
.pwa-title {
    font-weight: 800;
    font-size: 0.92rem;
    color: #0f172a;
    line-height: 1.2;
}
[data-bs-theme="dark"] .pwa-title {
    color: #f8fafc;
}
.pwa-desc {
    font-size: 0.76rem;
    color: #64748b;
    margin-top: 2px;
}
[data-bs-theme="dark"] .pwa-desc {
    color: #94a3b8;
}
.pwa-close-btn {
    padding: 0.4rem;
    font-size: 0.75rem;
}
@keyframes pwaSlideUp {
    from { transform: translateY(30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
</style>
