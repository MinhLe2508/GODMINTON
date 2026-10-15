/**
 * assets/js/court-map.js
 * Quản lý khởi tạo bản đồ Leaflet + OpenStreetMap và hiển thị marker sân cầu lông
 * Người phụ trách: P3 (Trương Minh Triết, Tuần 3)
 */

(function (window, document) {
    'use strict';

    // Cấu hình phạm vi địa lý đô thị lõi TP.HCM (maxBounds)
    const HCMC_BOUNDS = {
        southWest: [10.6500, 106.5500],
        northEast: [10.9500, 106.8500]
    };
    const DEFAULT_CENTER = [10.7769, 106.7009]; // Quận 1, TP.HCM
    const DEFAULT_ZOOM = 13;
    const MIN_ZOOM = 11;
    const MAX_ZOOM = 18;

    let map = null;
    let markersLayer = null;
    let currentCourts = [];

    // Helper: Escape chuỗi HTML chống tấn công XSS trong popup
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Helper: Định dạng tiền tệ VND
    function formatVND(amount) {
        if (!amount || isNaN(amount)) return '0 đ';
        return new Intl.NumberFormat('vi-VN').format(amount) + ' đ/h';
    }

    // Hiển thị trạng thái lỗi nếu thư viện Leaflet không nạp được
    function showMapError(container, message) {
        if (!container) return;
        container.innerHTML = `
            <div class="court-map-error-state">
                <i class="fa-solid fa-triangle-exclamation fs-1 text-warning mb-2"></i>
                <div class="fw-bold fs-6">Không thể nạp bản đồ</div>
                <div class="small text-muted mb-3">${escapeHtml(message || 'Vui lòng kiểm tra kết nối mạng hoặc thử lại sau.')}</div>
                <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3" onclick="window.location.reload();">
                    <i class="fa-solid fa-rotate-right me-1"></i> Tải lại trang
                </button>
            </div>
        `;
    }

    // Tạo icon SVG đặc trưng cho sân cầu lông (tông Teal)
    function createCourtMarkerIcon() {
        if (typeof L === 'undefined') return null;
        return L.divIcon({
            className: 'court-custom-marker',
            html: `
                <div class="court-marker-pin" title="Sân cầu lông">
                    <i class="fa-solid fa-volleyball"></i>
                </div>
            `,
            iconSize: [36, 36],
            iconAnchor: [18, 36],
            popupAnchor: [0, -32]
        });
    }

    // Khởi tạo bản đồ Leaflet
    function initCourtMap(containerId, initialCourts) {
        const container = document.getElementById(containerId || 'courtMapContainer');
        if (!container) {
            console.warn('[CourtMap] Không tìm thấy container:', containerId);
            return null;
        }

        // Kiểm tra thư viện Leaflet (L)
        if (typeof L === 'undefined') {
            showMapError(container, 'Không thể tải thư viện Leaflet từ CDN. Vui lòng kiểm tra kết nối Internet.');
            return null;
        }

        try {
            // Giới hạn maxBounds theo vùng đô thị TP.HCM
            const southWest = L.latLng(HCMC_BOUNDS.southWest[0], HCMC_BOUNDS.southWest[1]);
            const northEast = L.latLng(HCMC_BOUNDS.northEast[0], HCMC_BOUNDS.northEast[1]);
            const bounds = L.latLngBounds(southWest, northEast);

            // Khởi tạo map instance
            map = L.map(container, {
                center: DEFAULT_CENTER,
                zoom: DEFAULT_ZOOM,
                minZoom: MIN_ZOOM,
                maxZoom: MAX_ZOOM,
                maxBounds: bounds,
                maxBoundsViscosity: 1.0, // Ngăn chặn hoàn toàn kéo/zoom ra ngoài phạm vi
                scrollWheelZoom: true
            });

            // Tile layer OpenStreetMap kèm ATTRIBUTION BẮT BUỘC theo giấy phép ODbL
            const tileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
                maxZoom: MAX_ZOOM,
                minZoom: MIN_ZOOM
            });

            tileLayer.on('tileerror', function () {
                console.warn('[CourtMap] Một số tile OpenStreetMap không tải được.');
            });

            tileLayer.addTo(map);

            // Layer nhóm các markers
            markersLayer = L.layerGroup().addTo(map);

            // Cập nhật danh sách sân ban đầu nếu có
            if (Array.isArray(initialCourts) && initialCourts.length > 0) {
                updateMarkers(initialCourts);
            }

            // Đảm bảo kích thước hiển thị đúng sau khi DOM hoàn tất
            setTimeout(() => {
                if (map) map.invalidateSize();
            }, 300);

            return map;

        } catch (err) {
            console.error('[CourtMap] Lỗi khởi tạo Leaflet:', err);
            showMapError(container, 'Lỗi khởi tạo bản đồ: ' + err.message);
            return null;
        }
    }

    // Cập nhật marker theo danh sách sân (gọi tự động khi lọc/tìm kiếm)
    function updateMarkers(courts) {
        if (!map || !markersLayer) return;

        currentCourts = Array.isArray(courts) ? courts : [];
        markersLayer.clearLayers();

        const customIcon = createCourtMarkerIcon();
        const validLatLngs = [];

        currentCourts.forEach(court => {
            const lat = parseFloat(court.latitude);
            const lng = parseFloat(court.longitude);

            // Bỏ qua nếu sân chưa có tọa độ hợp lệ (không để bản đồ lỗi)
            if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                return;
            }

            // Kiểm tra tọa độ có nằm trong phạm vi TP.HCM không
            if (lat < HCMC_BOUNDS.southWest[0] || lat > HCMC_BOUNDS.northEast[0] ||
                lng < HCMC_BOUNDS.southWest[1] || lng > HCMC_BOUNDS.northEast[1]) {
                return;
            }

            validLatLngs.push([lat, lng]);

            const marker = L.marker([lat, lng], { icon: customIcon });

            // Popup HTML được ESCAPE triệt để chống XSS
            const detailUrl = `court-detail.php?id=${encodeURIComponent(court.id)}`;
            const priceText = court.price_formatted || formatVND(court.price_per_hour);

            const popupContent = `
                <div class="court-map-popup">
                    <div class="popup-title">${escapeHtml(court.name)}</div>
                    <div class="popup-address">
                        <i class="fa-solid fa-location-dot text-teal me-1"></i>${escapeHtml(court.address || 'Đang cập nhật địa chỉ')}
                    </div>
                    <div class="popup-footer">
                        <div class="popup-price">${escapeHtml(priceText)}</div>
                        <a href="${detailUrl}" class="btn btn-sm btn-teal rounded-pill px-3 py-1" style="font-size:0.75rem; text-decoration:none;">
                            Xem sân <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent, { maxWidth: 280 });
            markersLayer.addLayer(marker);
        });

        // Tự động điều chỉnh góc nhìn để vừa các sân đang hiển thị
        if (validLatLngs.length > 1) {
            try {
                const groupBounds = L.latLngBounds(validLatLngs);
                map.fitBounds(groupBounds, { padding: [40, 40], maxZoom: 15 });
            } catch (e) {
                // Giữ nguyên view nếu lỗi fit bounds
            }
        } else if (validLatLngs.length === 1) {
            map.setView(validLatLngs[0], 15);
        }
    }

    // Đặt lại góc nhìn về trung tâm TP.HCM
    function resetView() {
        if (!map) return;
        map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
    }

    // Xuất API công khai ra window.CourtMap
    window.CourtMap = {
        init: initCourtMap,
        updateMarkers: updateMarkers,
        resetView: resetView,
        getMap: function () { return map; }
    };

})(window, document);
