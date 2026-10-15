/**
 * assets/js/court-map.js
 * Quản lý khởi tạo bản đồ Leaflet + OpenStreetMap và hiển thị marker sân cầu lông
 * Người phụ trách: P3 (Trương Minh Triết, Tuần 3)
 */

(function (window, document) {
    'use strict';

    // Phạm vi địa lý bao quát toàn bộ khu vực đô thị Sài Gòn & vùng lân cận (maxBounds)
    const HCMC_BOUNDS = {
        southWest: [10.5000, 106.3500],
        northEast: [11.1500, 106.9500]
    };
    const DEFAULT_CENTER = [10.7769, 106.7009]; // Quận 1, TP.HCM
    const DEFAULT_ZOOM = 12;
    const MIN_ZOOM = 10;
    const MAX_ZOOM = 18;

    // Tọa độ dự phòng cho 7 sân mẫu mặc định (phòng trường hợp dữ liệu tĩnh client chưa có lat/lng)
    const DEMO_COORDS = {
        1: [10.7769, 106.7009], // Quận 1 – Lê Thánh Tôn
        2: [10.8020, 106.7120], // Bình Thạnh – Arena Xô Viết
        3: [10.8010, 106.6650], // Tân Bình – TBSport
        4: [10.8230, 106.6830], // Gò Vấp – GV Arena
        5: [10.7290, 106.7180], // Quận 7 – Phú Mỹ Hưng
        6: [10.8510, 106.7720], // Thủ Đức – Thủ Đức Arena
        7: [11.0100, 106.6530]  // Bình Dương – BD Sport Center
    };

    let map = null;
    let markersLayer = null;
    let currentCourts = [];
    let pendingCourts = null;

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
        if (!amount || isNaN(amount)) return '0 đ/h';
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

    // Tạo icon SVG đặc trưng hình ghim bản đồ màu Teal sắc nét (không phụ thuộc font)
    function createCourtMarkerIcon(courtName) {
        if (typeof L === 'undefined') return null;
        return L.divIcon({
            className: 'court-svg-marker-wrapper',
            html: `
                <div class="court-svg-pin" title="${escapeHtml(courtName || 'Sân cầu lông')}">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="30" height="40" style="filter: drop-shadow(0 3px 6px rgba(0,0,0,0.45)); cursor: pointer;">
                        <path fill="#0d9488" stroke="#ffffff" stroke-width="16" d="M384 192C384 279.4 267 435 215.7 499.2C207.8 509.1 192.2 509.1 184.3 499.2C133 435 16 279.4 16 192C16 86 102 0 208 0C314 0 384 86 384 192z"/>
                        <circle cx="192" cy="192" r="72" fill="#ffffff"/>
                        <circle cx="192" cy="192" r="46" fill="#0d9488"/>
                    </svg>
                </div>
            `,
            iconSize: [30, 40],
            iconAnchor: [15, 40],
            popupAnchor: [0, -38]
        });
    }

    // Khởi tạo bản đồ Leaflet
    function initCourtMap(containerId, initialCourts) {
        const container = document.getElementById(containerId || 'courtMapContainer');
        if (!container) {
            console.warn('[CourtMap] Không tìm thấy container:', containerId);
            return null;
        }

        if (typeof L === 'undefined') {
            showMapError(container, 'Không thể tải thư viện Leaflet từ CDN. Vui lòng kiểm tra kết nối mạng.');
            return null;
        }

        try {
            // Giới hạn maxBounds theo toàn vùng đô thị
            const southWest = L.latLng(HCMC_BOUNDS.southWest[0], HCMC_BOUNDS.southWest[1]);
            const northEast = L.latLng(HCMC_BOUNDS.northEast[0], HCMC_BOUNDS.northEast[1]);
            const bounds = L.latLngBounds(southWest, northEast);

            map = L.map(container, {
                center: DEFAULT_CENTER,
                zoom: DEFAULT_ZOOM,
                minZoom: MIN_ZOOM,
                maxZoom: MAX_ZOOM,
                maxBounds: bounds,
                maxBoundsViscosity: 1.0,
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

            // Layer nhóm markers
            markersLayer = L.layerGroup().addTo(map);

            // Ưu tiên nạp danh sách sân chờ (pending) hoặc ban đầu
            const courtsToLoad = (pendingCourts && pendingCourts.length > 0)
                ? pendingCourts
                : (Array.isArray(initialCourts) ? initialCourts : []);

            if (courtsToLoad.length > 0) {
                updateMarkers(courtsToLoad);
                pendingCourts = null;
            }

            // Invalidate size để hiển thị đầy đủ khi DOM render xong
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

    // Cập nhật marker theo danh sách sân
    function updateMarkers(courts) {
        if (!map || !markersLayer) {
            pendingCourts = courts;
            return;
        }

        currentCourts = Array.isArray(courts) ? courts : [];
        markersLayer.clearLayers();

        const validLatLngs = [];

        currentCourts.forEach(court => {
            let lat = parseFloat(court.latitude);
            let lng = parseFloat(court.longitude);

            // Tự động đối soát tọa độ dự phòng nếu sân mẫu thiếu tọa độ
            if ((isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) && court.id && DEMO_COORDS[court.id]) {
                lat = DEMO_COORDS[court.id][0];
                lng = DEMO_COORDS[court.id][1];
            }

            // Bỏ qua nếu sân không có tọa độ hợp lệ
            if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) {
                return;
            }

            // Kiểm tra tọa độ có nằm trong phạm vi hiển thị
            if (lat < HCMC_BOUNDS.southWest[0] || lat > HCMC_BOUNDS.northEast[0] ||
                lng < HCMC_BOUNDS.southWest[1] || lng > HCMC_BOUNDS.northEast[1]) {
                return;
            }

            validLatLngs.push([lat, lng]);

            const customIcon = createCourtMarkerIcon(court.name);
            const marker = L.marker([lat, lng], { icon: customIcon });

            // Popup HTML escape triệt để chống XSS
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

        // Tự động điều chỉnh góc nhìn để thấy trọn các sân
        if (validLatLngs.length > 1) {
            try {
                const groupBounds = L.latLngBounds(validLatLngs);
                map.fitBounds(groupBounds, { padding: [45, 45], maxZoom: 14 });
            } catch (e) {
                // Giữ nguyên view nếu lỗi fit
            }
        } else if (validLatLngs.length === 1) {
            map.setView(validLatLngs[0], 15);
        }
    }

    // Đặt lại góc nhìn về trung tâm
    function resetView() {
        if (!map) return;
        map.setView(DEFAULT_CENTER, DEFAULT_ZOOM);
    }

    // Xuất API công khai
    window.CourtMap = {
        init: initCourtMap,
        updateMarkers: updateMarkers,
        resetView: resetView,
        getMap: function () { return map; }
    };

})(window, document);
