/**
 * assets/js/court-map.js
 * Quản lý khởi tạo bản đồ Leaflet + OpenStreetMap và hiển thị marker sân cầu lông
 */

(function (window, document) {
    'use strict';

    // Phạm vi địa lý bao quát toàn bộ khu vực đô thị Sài Gòn & vùng lân cận (maxBounds)
    const HCMC_BOUNDS = {
        southWest: [10.3500, 106.3000],
        northEast: [11.2000, 107.0500]
    };
    const DEFAULT_CENTER = [10.7769, 106.7009]; // Quận 1, TP.HCM
    const DEFAULT_ZOOM = 12;
    const MIN_ZOOM = 10;
    const MAX_ZOOM = 18;

    // Chỉ hiển thị tọa độ trong dữ liệu.
    let map = null;
    let markersLayer = null;
    let currentCourts = [];
    let pendingCourts = null;
    let routeLayer = null;
    let userMarker = null;
    let userLocationMarker = null;
    let routePanelEl = null;
    let lastKnownUserPos = null;

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
            const imageUrl = court.image_url || 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&auto=format&fit=crop';

            const popupContent = `
                <div class="court-map-popup">
                    <div class="popup-img-wrapper">
                        <img src="${escapeHtml(imageUrl)}" alt="${escapeHtml(court.name)}" class="popup-thumb" onerror="this.onerror=null;this.src='https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&auto=format&fit=crop';">
                    </div>
                    <div class="popup-title">${escapeHtml(court.name)}</div>
                    <div class="popup-address">
                        <i class="fa-solid fa-location-dot text-teal me-1"></i>${escapeHtml(court.address || 'Đang cập nhật địa chỉ')}
                    </div>
                    <div class="popup-footer">
                        <div class="popup-price">${escapeHtml(priceText)}</div>
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn-popup-route" onclick="if(window.CourtMap) window.CourtMap.routeToCourt(${lat}, ${lng}, '${escapeHtml(court.name)}', '${escapeHtml(court.address || '')}')" title="Chỉ đường từ vị trí của bạn">
                                <i class="fa-solid fa-diamond-turn-right me-1"></i> Chỉ đường
                            </button>
                            <a href="${detailUrl}" class="btn btn-sm btn-teal rounded-pill px-2 py-1" style="font-size:0.75rem; text-decoration:none;">
                                Xem sân <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;

            marker.bindPopup(popupContent, { maxWidth: 300 });
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

    // Bật/tắt chế độ toàn màn hình
    function toggleFullscreen() {
        const wrapper = document.querySelector('.court-map-wrapper');
        if (!wrapper) return;

        const isFullscreen = document.fullscreenElement ||
                             document.webkitFullscreenElement ||
                             document.mozFullScreenElement ||
                             document.msFullscreenElement ||
                             wrapper.classList.contains('court-map-is-fullscreen');

        if (!isFullscreen) {
            if (wrapper.requestFullscreen) {
                wrapper.requestFullscreen().catch(() => {
                    wrapper.classList.add('court-map-is-fullscreen');
                    syncFullscreenUI(true);
                });
            } else if (wrapper.webkitRequestFullscreen) {
                wrapper.webkitRequestFullscreen();
            } else if (wrapper.msRequestFullscreen) {
                wrapper.msRequestFullscreen();
            } else {
                wrapper.classList.add('court-map-is-fullscreen');
                syncFullscreenUI(true);
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen().catch(() => {
                    wrapper.classList.remove('court-map-is-fullscreen');
                    syncFullscreenUI(false);
                });
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            } else {
                wrapper.classList.remove('court-map-is-fullscreen');
                syncFullscreenUI(false);
            }
        }

        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 200);
    }

    function syncFullscreenUI(isFs) {
        // Cập nhật nút toolbar
        const iconState = document.querySelector('.icon-fullscreen-state');
        const textState = document.querySelector('.text-fullscreen-state');
        if (iconState) {
            iconState.className = isFs ? 'fa-solid fa-compress me-1 icon-fullscreen-state' : 'fa-solid fa-expand me-1 icon-fullscreen-state';
        }
        if (textState) {
            textState.textContent = isFs ? 'Thu nhỏ' : 'Toàn màn hình';
        }

        // Cập nhật floating button
        const iconExpand = document.querySelector('.court-map-floating-fs-btn .fs-icon-expand');
        const iconCompress = document.querySelector('.court-map-floating-fs-btn .fs-icon-compress');
        if (iconExpand && iconCompress) {
            if (isFs) {
                iconExpand.classList.add('d-none');
                iconCompress.classList.remove('d-none');
            } else {
                iconExpand.classList.remove('d-none');
                iconCompress.classList.add('d-none');
            }
        }

        const floatingTexts = document.querySelectorAll('.court-map-floating-fs-btn .fs-floating-text');
        floatingTexts.forEach(el => {
            el.textContent = isFs ? 'Thu nhỏ' : 'Toàn màn hình';
        });

        setTimeout(() => {
            if (map) map.invalidateSize();
        }, 150);
    }

    // ==========================================
    // TÍNH NĂNG CHỈ ĐƯỜNG ĐẾN SÂN (DIRECTIONS / ROUTING)
    // ==========================================

    // Tính khoảng cách Haversine (km)
    function calcHaversine(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) * Math.sin(dLat / 2) +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return parseFloat((R * c).toFixed(1));
    }

    // Hiển thị bảng thông tin lộ trình nổi trên bản đồ
    function showRoutePanel(destLat, destLng, courtName, distanceKm, durationMin, userLat, userLng) {
        clearRoutePanel();
        const container = map ? map.getContainer() : null;
        if (!container) return;

        routePanelEl = document.createElement('div');
        routePanelEl.className = 'court-map-route-panel';
        const gmapsUrl = `https://www.google.com/maps/dir/?api=1&origin=${userLat},${userLng}&destination=${destLat},${destLng}`;

        routePanelEl.innerHTML = `
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <div class="d-flex align-items-center gap-2" style="min-width: 200px;">
                    <div class="route-badge-icon"><i class="fa-solid fa-diamond-turn-right"></i></div>
                    <div>
                        <div class="fw-bold small text-truncate" style="max-width: 240px;" title="${escapeHtml(courtName)}">
                            Đến: ${escapeHtml(courtName)}
                        </div>
                        <div class="small text-muted">
                            Khoảng cách: <strong class="text-teal">${distanceKm} km</strong> ${durationMin ? `· ~${durationMin} phút` : ''}
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <a href="${gmapsUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-teal rounded-pill px-3 py-1 fw-semibold" style="font-size:0.75rem; text-decoration:none;" title="Mở lộ trình trên Google Maps">
                        <i class="fa-brands fa-google me-1"></i> Google Maps
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle d-flex align-items-center justify-content-center" style="width:30px;height:30px;padding:0;" onclick="if(window.CourtMap) window.CourtMap.clearRoute();" title="Đóng chỉ đường">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        `;
        container.appendChild(routePanelEl);
    }

    function clearRoutePanel() {
        if (routePanelEl && routePanelEl.parentNode) {
            routePanelEl.parentNode.removeChild(routePanelEl);
        }
        routePanelEl = null;
    }

    // Xóa đường đi và vị trí người dùng
    function clearRoute() {
        if (routeLayer && map) {
            map.removeLayer(routeLayer);
            routeLayer = null;
        }
        if (userMarker && map) {
            map.removeLayer(userMarker);
            userMarker = null;
        }
        clearRoutePanel();
    }

    // Tạo icon hiển thị vị trí của người dùng với hiệu ứng sóng lan toả
    function createUserMarkerIcon(isHome) {
        return L.divIcon({
            className: 'court-user-location-wrapper',
            html: `
                <div class="court-user-pulse-marker" title="${isHome ? 'Vị trí của bạn' : 'Điểm xuất phát'}">
                    <div class="user-pulse-wave"></div>
                    <div class="user-pulse-dot"><i class="fa-solid ${isHome ? 'fa-location-crosshairs' : 'fa-person-walking'}"></i></div>
                </div>
            `,
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });
    }

    // TÍNH NĂNG ĐỊNH VỊ VỊ TRÍ CỦA TÔI (LOCATE ME)
    function locateUser() {
        if (!map) return;

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Đang định vị vị trí của bạn...',
                showConfirmButton: false,
                timer: 2000
            });
        }

        if (!navigator.geolocation) {
            handleLocateFallback('Trình duyệt không hỗ trợ định vị GPS.');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const userLat = pos.coords.latitude;
                const userLng = pos.coords.longitude;
                lastKnownUserPos = { lat: userLat, lng: userLng };

                if (userLocationMarker && map) {
                    userLocationMarker.setLatLng([userLat, userLng]);
                } else {
                    userLocationMarker = L.marker([userLat, userLng], {
                        icon: createUserMarkerIcon(true),
                        zIndexOffset: 1200
                    }).addTo(map);
                }
                userLocationMarker.bindPopup(`<b>Vị trí hiện tại của bạn</b><br><small class="text-success"><i class="fa-solid fa-circle-check"></i> Đã định vị chính xác vị trí của bạn</small>`).openPopup();

                map.flyTo([userLat, userLng], 15, { duration: 1.2 });

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'success',
                        title: 'Đã định vị vị trí của bạn thành công!',
                        showConfirmButton: false,
                        timer: 2500
                    });
                }
            },
            (err) => {
                console.warn('[CourtMap] Không thể định vị GPS:', err);
                const msg = err.code === 1 ? 'Bạn chưa cho phép cấp quyền vị trí trên trình duyệt.'
                          : err.code === 2 ? 'Không thể lấy thông tin GPS từ thiết bị.'
                          : 'Hết thời gian chờ lấy vị trí.';
                handleLocateFallback(msg);
            },
            {
                enableHighAccuracy: false,
                timeout: 8000,
                maximumAge: 60000
            }
        );
    }

    function handleLocateFallback(msg) {
        const fallbackLat = DEFAULT_CENTER[0];
        const fallbackLng = DEFAULT_CENTER[1];
        lastKnownUserPos = { lat: fallbackLat, lng: fallbackLng };

        if (userLocationMarker && map) {
            userLocationMarker.setLatLng([fallbackLat, fallbackLng]);
        } else {
            userLocationMarker = L.marker([fallbackLat, fallbackLng], {
                icon: createUserMarkerIcon(true),
                zIndexOffset: 1200
            }).addTo(map);
        }
        userLocationMarker.bindPopup(`<b>Vị trí tham chiếu (Trung tâm TP.HCM)</b><br><small class="text-muted">${escapeHtml(msg || 'Cấp quyền vị trí để định vị chuẩn xác')}</small>`).openPopup();

        map.flyTo([fallbackLat, fallbackLng], 14, { duration: 1 });

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'warning',
                title: msg || 'Chưa định vị được GPS, dùng vị trí trung tâm TP.HCM',
                showConfirmButton: false,
                timer: 3500
            });
        }
    }

    // Vẽ đường đi trực tiếp trên bản đồ Leaflet
    async function drawRouteOnMap(startLat, startLng, destLat, destLng, courtName, isFallbackStart) {
        clearRoute();

        // Tạo marker xuất phát
        const userIcon = createUserMarkerIcon(false);
        userMarker = L.marker([startLat, startLng], { icon: userIcon, zIndexOffset: 1000 }).addTo(map);
        userMarker.bindPopup(`<b>${isFallbackStart ? 'Điểm xuất phát (Trung tâm TP.HCM)' : 'Vị trí hiện tại của bạn'}</b><br><small class="text-muted">${isFallbackStart ? 'Chưa bật GPS thực tế' : 'Điểm xuất phát'}</small>`);

        let distanceKm = 0;
        let durationMin = 0;
        let routeCoords = [];

        try {
            const osrmUrl = `https://router.project-osrm.org/route/v1/driving/${startLng},${startLat};${destLng},${destLat}?overview=full&geometries=geojson`;
            const res = await fetch(osrmUrl);
            if (res.ok) {
                const data = await res.json();
                if (data && data.routes && data.routes.length > 0) {
                    const r = data.routes[0];
                    distanceKm = parseFloat((r.distance / 1000).toFixed(1));
                    durationMin = Math.max(1, Math.round(r.duration / 60));
                    routeCoords = r.geometry.coordinates.map(c => [c[1], c[0]]);
                }
            }
        } catch (err) {
            console.warn('[CourtMap] Không thể kết nối OSRM, dùng fallback đường thẳng:', err);
        }

        // Nếu OSRM không trả về route hợp lệ, dùng đường thẳng Haversine
        if (routeCoords.length === 0) {
            routeCoords = [[startLat, startLng], [destLat, destLng]];
            distanceKm = calcHaversine(startLat, startLng, destLat, destLng);
            durationMin = Math.max(1, Math.round((distanceKm / 30) * 60)); // ước tính tốc độ 30km/h
        }

        // Vẽ lộ trình trên bản đồ với 2 lớp đường (đường viền nền + đường chính xanh ngọc rực rỡ)
        const bgLine = L.polyline(routeCoords, {
            color: '#042f2e',
            weight: 7,
            opacity: 0.5,
            lineJoin: 'round'
        });
        const mainLine = L.polyline(routeCoords, {
            color: '#0d9488',
            weight: 5,
            opacity: 0.95,
            lineJoin: 'round'
        });

        routeLayer = L.featureGroup([bgLine, mainLine]).addTo(map);

        // Fit bounds hiển thị cả người dùng và sân
        try {
            map.fitBounds(routeLayer.getBounds(), { padding: [60, 60], maxZoom: 16 });
        } catch (_) {}

        // Hiển thị bảng lộ trình nổi ngay trên bản đồ
        showRoutePanel(destLat, destLng, courtName || 'Sân cầu lông', distanceKm, durationMin, startLat, startLng);

        if (isFallbackStart && typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Đang chỉ đường từ Trung tâm TP.HCM (Bật định vị để chỉ từ chỗ bạn)',
                showConfirmButton: false,
                timer: 4000
            });
        }
    }

    // Chỉ đường từ vị trí của khách đến sân (Vẽ trực tiếp trên Leaflet, không ép chuyển sang Google)
    function routeToCourt(destLat, destLng, courtName, courtAddress) {
        destLat = parseFloat(destLat);
        destLng = parseFloat(destLng);
        if (isNaN(destLat) || isNaN(destLng) || !map) {
            alert('Không tìm thấy tọa độ hợp lệ của sân.');
            return;
        }

        // Đóng popup đang mở
        map.closePopup();

        // 1. Nếu đã có tọa độ định vị từ trước, dùng ngay tọa độ đó để vẽ
        if (lastKnownUserPos && !isNaN(lastKnownUserPos.lat) && !isNaN(lastKnownUserPos.lng)) {
            drawRouteOnMap(lastKnownUserPos.lat, lastKnownUserPos.lng, destLat, destLng, courtName, false);
            return;
        }

        // 2. Nếu không hỗ trợ Geolocation, vẽ ngay lộ trình từ trung tâm TP.HCM trực tiếp trên Leaflet
        if (!navigator.geolocation) {
            drawRouteOnMap(DEFAULT_CENTER[0], DEFAULT_CENTER[1], destLat, destLng, courtName, true);
            return;
        }

        // Toast thông báo đang lấy vị trí
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'info',
                title: 'Đang xác định vị trí của bạn để chỉ đường...',
                showConfirmButton: false,
                timer: 1800
            });
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const userLat = pos.coords.latitude;
                const userLng = pos.coords.longitude;
                lastKnownUserPos = { lat: userLat, lng: userLng };
                drawRouteOnMap(userLat, userLng, destLat, destLng, courtName, false);
            },
            (err) => {
                console.warn('[CourtMap] Không lấy được GPS, tự động dùng trung tâm TP.HCM làm điểm xuất phát:', err);
                // Vẽ ngay lộ trình trên Leaflet bản đồ, TUYỆT ĐỐI KHÔNG TỰ ĐỘNG CHUYỂN TRANG GOOGLE MAPS
                drawRouteOnMap(DEFAULT_CENTER[0], DEFAULT_CENTER[1], destLat, destLng, courtName, true);
            },
            {
                enableHighAccuracy: false,
                timeout: 6000,
                maximumAge: 60000
            }
        );
    }

    // Lắng nghe sự kiện thay đổi fullscreen toàn cục
    ['fullscreenchange', 'webkitfullscreenchange', 'mozfullscreenchange', 'MSFullscreenChange'].forEach(ev => {
        document.addEventListener(ev, () => {
            const isFs = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
            syncFullscreenUI(isFs);
        });
    });

    // Lắng nghe phím ESC cho CSS fallback
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const wrapper = document.querySelector('.court-map-wrapper.court-map-is-fullscreen');
            if (wrapper) {
                wrapper.classList.remove('court-map-is-fullscreen');
                syncFullscreenUI(false);
            }
        }
    });

    // Xuất API công khai
    window.CourtMap = {
        init: initCourtMap,
        updateMarkers: updateMarkers,
        resetView: resetView,
        locateUser: locateUser,
        toggleFullscreen: toggleFullscreen,
        routeToCourt: routeToCourt,
        clearRoute: clearRoute,
        getMap: function () { return map; }
    };

})(window, document);
