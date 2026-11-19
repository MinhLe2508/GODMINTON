<!-- GODMINTON install prompt, positioned above the bottom navigation. -->
<div id="pwaInstallBanner" class="pwa-install-banner" role="region" aria-label="Cài ứng dụng GODMINTON">
    <div class="pwa-install-card">
        <img src="assets/img/icons/icon-192.png" alt="" class="pwa-app-icon" width="44" height="44">
        <div class="pwa-install-copy">
            <div class="pwa-title">Cài app GODMINTON</div>
            <div class="pwa-desc">Đặt sân nhanh, nhận nhắc lịch chơi.</div>
        </div>
        <button type="button" class="btn btn-teal btn-sm rounded-pill px-3 fw-bold pwa-install-action" id="btnBannerInstall">
            <i class="fa-solid fa-download me-1" aria-hidden="true"></i> Cài đặt
        </button>
        <button type="button" class="pwa-close-btn" id="btnBannerDismiss" aria-label="Đóng lời mời cài ứng dụng">×</button>
    </div>
</div>

<style>
#pwaInstallBanner {
    position: fixed;
    inset-inline: 16px;
    bottom: calc(104px + env(safe-area-inset-bottom, 0px));
    z-index: 1045;
    max-width: 520px;
    margin-inline: auto;
    display: none;
    background: transparent;
    color: var(--text-primary, #0f172a);
    border: 0;
    box-shadow: none;
}
#pwaInstallBanner.show { display: block !important; }
#pwaInstallBanner .pwa-install-card {
    display: grid;
    grid-template-columns: 44px minmax(0, 1fr) auto 28px;
    align-items: center;
    gap: 12px;
    padding: 16px;
    border: 1px solid rgba(13, 148, 136, .25);
    border-radius: 20px;
    background: rgba(255, 255, 255, .98);
    box-shadow: 0 8px 28px rgba(15, 118, 110, .18);
    backdrop-filter: blur(12px);
}
#pwaInstallBanner .pwa-app-icon { border-radius: 12px; object-fit: cover; }
#pwaInstallBanner .pwa-install-copy { min-width: 0; }
#pwaInstallBanner .pwa-title { color: #0f172a; font-size: 15px; font-weight: 800; line-height: 1.4; }
#pwaInstallBanner .pwa-desc { color: #64748b; font-size: 12px; line-height: 1.5; margin-top: 3px; }
#pwaInstallBanner .pwa-install-action { white-space: nowrap; min-height: 40px; }
#pwaInstallBanner .pwa-close-btn {
    display: grid;
    place-items: center;
    align-self: start;
    width: 28px;
    height: 32px;
    padding: 0;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #64748b;
    font: 26px/1 system-ui, sans-serif;
    cursor: pointer;
}
#pwaInstallBanner .pwa-close-btn:focus-visible { outline: 2px solid #0d9488; outline-offset: 2px; }
[data-bs-theme="dark"] #pwaInstallBanner .pwa-install-card { background: rgba(13, 39, 48, .98); border-color: #27545c; }
[data-bs-theme="dark"] #pwaInstallBanner .pwa-title { color: #f8fafc; }
[data-bs-theme="dark"] #pwaInstallBanner .pwa-desc,
[data-bs-theme="dark"] #pwaInstallBanner .pwa-close-btn { color: #b2c5d3; }
@media (min-width: 768px) {
    #pwaInstallBanner { left: 24px; right: auto; width: calc(100% - 48px); margin: 0; }
}
@media (max-width: 479px) {
    #pwaInstallBanner { inset-inline: 12px; }
    #pwaInstallBanner .pwa-install-card { grid-template-columns: 40px minmax(0, 1fr) 28px; gap: 10px; padding: 14px; }
    #pwaInstallBanner .pwa-app-icon { width: 40px; height: 40px; }
    #pwaInstallBanner .pwa-install-action { grid-column: 1 / 3; grid-row: 2; justify-self: stretch; }
    #pwaInstallBanner .pwa-close-btn { grid-column: 3; grid-row: 1; }
}
@media (prefers-reduced-motion: no-preference) {
    #pwaInstallBanner.show { animation: godmintonInstallAppear .2s ease-out; }
    @keyframes godmintonInstallAppear { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
}
</style>
