<?php
/**
 * includes/partials/court-map.php
 * Khung HTML và nạp tài nguyên bản đồ OpenStreetMap (Leaflet.js)
 *
 * Cách sử dụng:
 * 1. Danh sách sân (courts.php):
 *    include __DIR__ . '/includes/partials/court-map.php';
 * 2. Chi tiết sân (court-detail.php):
 *    $courtMapData = $court; // hoặc mảng 1 sân
 *    include __DIR__ . '/includes/partials/court-map.php';
 */

$initialMapData = isset($courtMapData) ? (is_array($courtMapData) && isset($courtMapData['id']) ? [$courtMapData] : (array)$courtMapData) : [];
$mapContainerId = 'courtMapContainer_' . uniqid();
$encodedData = json_encode($initialMapData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
?>

<!-- Leaflet 1.9.4 CSS (Ghim cố định phiên bản) -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
<link rel="stylesheet" href="assets/css/court-map.css?v=<?= @filemtime(__DIR__ . '/../../assets/css/court-map.css') ?: time() ?>">

<div class="court-map-wrapper my-4" id="mapSection">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle bg-teal-subtle text-teal d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                <i class="fa-solid fa-map-location-dot fs-5"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-0">Bản Đồ Vị Trí Sân</h5>
                <small class="text-muted">Khu vực hoạt động: Đô thị TP.HCM (OpenStreetMap)</small>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center flex-wrap">
            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3" onclick="if(window.CourtMap && window.CourtMap.locateUser) window.CourtMap.locateUser(); else if(window.CourtMap && window.CourtMap.resetView) window.CourtMap.resetView();" title="Định vị vị trí hiện tại của bạn">
                <i class="fa-solid fa-location-crosshairs me-1"></i> Định vị
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="if(window.CourtMap && window.CourtMap.resetView) window.CourtMap.resetView();" title="Quay về góc nhìn trung tâm">
                <i class="fa-solid fa-crosshairs me-1"></i> Trung tâm
            </button>
            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 btn-court-map-fullscreen" onclick="if(window.CourtMap && window.CourtMap.toggleFullscreen) window.CourtMap.toggleFullscreen();" title="Phóng to toàn màn hình">
                <i class="fa-solid fa-expand me-1 icon-fullscreen-state"></i> <span class="text-fullscreen-state">Toàn màn hình</span>
            </button>
        </div>
    </div>

    <!-- Container bản đồ có chiều cao cố định, responsive -->
    <div id="<?= htmlspecialchars($mapContainerId, ENT_QUOTES, 'UTF-8') ?>" class="court-map-container">
        <div class="court-map-error-state">
            <div class="spinner-border text-teal spinner-border-sm mb-2" role="status"></div>
            <div class="small text-muted">Đang khởi tạo bản đồ OpenStreetMap...</div>
        </div>
    </div>
</div>

<!-- Leaflet 1.9.4 JS (Ghim cố định phiên bản) -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
<script src="assets/js/court-map.js?v=<?= @filemtime(__DIR__ . '/../../assets/js/court-map.js') ?: time() ?>"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var rawData = <?= $encodedData ?>;
    if (window.CourtMap && typeof window.CourtMap.init === 'function') {
        window.CourtMap.init('<?= htmlspecialchars($mapContainerId, ENT_QUOTES, 'UTF-8') ?>', rawData);
    }
});
</script>
