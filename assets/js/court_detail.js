/**
 * GODMINTON — court_detail.js
 * Trang chi tiết sân (court-detail.php) — Tuần 1: chuyển sang AJAX thật.
 * Thay dữ liệu giả bằng fetch('api/court-detail.php?id=N').
 * Giữ nguyên giao diện và luồng UX đã có.
 */

document.addEventListener('DOMContentLoaded', async () => {
    const params  = new URLSearchParams(window.location.search);
    const courtId = parseInt(params.get('id')) || 0;

    if (!courtId) {
        window.location.href = 'courts.php';
        return;
    }

    try {
        const res  = await fetch(`api/court-detail.php?id=${courtId}`, {cache: 'no-store'});
        const json = await res.json();

        if (json.status !== 'success' || !json.data) {
            window.location.href = 'courts.php';
            return;
        }

        const court = json.data;

        // Cập nhật tiêu đề trang
        document.title = `${court.name} - Thông Tin Chi Tiết | GODMINTON`;

        const escapeText = value => String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[ch]));
        const description = String(court.description || '').trim();
        const isCatalog = /\[catalog:[^\]]+\]/i.test(description);
        const area = [court.ward_name, court.old_area_label].filter(Boolean).join(' · ');
        const introduction = isCatalog
            ? `${court.name} là địa điểm cầu lông tại ${area || 'TP. Hồ Chí Minh'}. Xem thông tin địa điểm và các tiện ích được cập nhật bên dưới trước khi chọn khung giờ.`
            : description || `Khám phá thông tin địa điểm và lựa chọn khung giờ tại ${court.name}.`;
        const sourceMatch = description.match(/Nguồn:\s*(https?:\/\/[^\s]+)/i);
        let sourceLink = '';
        if (sourceMatch) {
            try {
                const url = new URL(sourceMatch[1]);
                if (['https:', 'http:'].includes(url.protocol)) sourceLink = `<a class="text-teal" href="${escapeText(url.href)}" target="_blank" rel="noopener noreferrer">${escapeText(url.hostname)} <i class="fa-solid fa-arrow-up-right-from-square ms-1" aria-hidden="true"></i></a>`;
            } catch (_) {}
        }
        const retrieved = description.match(/Tra cứu:\s*(\d{4}-\d{2}-\d{2})/);
        const notes = description.split(/\r?\n/).filter(line => line.trim() && !/^\s*(\[catalog:|Tra cứu:|Nguồn:|Giá sân mới)/i.test(line)).join(' ');
        const sourceHtml = isCatalog ? `
            <details class="border rounded-3 p-3 mt-3 small">
                <summary class="text-muted" style="cursor:pointer">Nguồn thông tin địa điểm</summary>
                <div class="pt-2 text-muted lh-lg">
                    ${sourceLink ? `<div>Tham khảo: ${sourceLink}</div>` : ''}
                    ${retrieved ? `<div>Ngày tra cứu: ${escapeText(retrieved[1].split('-').reverse().join('/'))}</div>` : ''}
                    ${notes ? `<p class="mb-0 mt-1">${escapeText(notes)}</p>` : ''}
                </div>
            </details>` : '';

        // Labels come from DB; icons do not imply additional service promises.
        const amenityIcons = {aircon:'fa-snowflake', parking:'fa-square-parking', shower:'fa-shower', wifi:'fa-wifi', cafe:'fa-mug-saucer', rental:'fa-table-tennis-paddle-ball', locker:'fa-lock', indoor:'fa-warehouse'};
        const fallbackLabels = {aircon:'Điều hòa', parking:'Bãi đỗ xe', shower:'Phòng tắm', wifi:'Wi-Fi', cafe:'Căn tin / Cà phê', rental:'Cho thuê vợt', locker:'Tủ khóa', indoor:'Sân trong nhà'};
        const amenities = Array.isArray(court.amenities) ? court.amenities : (court.amenity_codes || []).map(code => ({code, label:fallbackLabels[code] || code}));
        const uniqueAmenities = [...new Map(amenities.filter(a => a && a.code).map(a => [a.code, a])).values()];
        const amenitiesHtml = uniqueAmenities.length ? `
            <div class="row g-2">${uniqueAmenities.map(a => `
                <div class="col-12 col-sm-6">
                    <div class="d-flex align-items-center gap-3 border rounded-3 p-3 h-100 bg-body-tertiary">
                        <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-teal-subtle text-teal flex-shrink-0" style="width:40px;height:40px">
                            <i class="fa-solid ${amenityIcons[a.code] || 'fa-circle-check'}" aria-hidden="true"></i>
                        </span>
                        <span class="fw-semibold small">${escapeText(a.label || fallbackLabels[a.code] || a.code)}</span>
                    </div>
                </div>`).join('')}
            </div>` : `
            <div class="d-flex align-items-start gap-3 border rounded-3 p-3 bg-body-tertiary">
                <i class="fa-solid fa-circle-info text-teal mt-1" aria-hidden="true"></i>
                <div><div class="fw-semibold mb-1">Tiện ích đang được cập nhật</div>
                <p class="small text-muted mb-0">Chưa có danh sách tiện ích cho sân này. Bạn nên liên hệ sân để xác nhận dịch vụ cần sử dụng trước khi đặt.</p></div>
            </div>`;
        const overviewHtml = `
            <section class="mb-4" aria-labelledby="courtOverviewTitle">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="fa-solid fa-circle-info text-teal" aria-hidden="true"></i>
                    <h5 id="courtOverviewTitle" class="fw-bold mb-0">Giới thiệu cụm sân</h5>
                </div>
                <p class="text-body-secondary lh-lg mb-3" style="white-space:pre-line">${escapeText(introduction)}</p>
                <div class="row g-2">
                    <div class="col-sm-6"><div class="border rounded-3 p-3 h-100">
                        <div class="small text-muted mb-1"><i class="fa-solid fa-location-dot text-teal me-2" aria-hidden="true"></i>Địa điểm</div>
                        <div class="small fw-semibold">${escapeText(court.address || 'Đang cập nhật địa chỉ')}</div>
                    </div></div>
                    <div class="col-sm-6"><div class="border rounded-3 p-3 h-100">
                        <div class="small text-muted mb-1"><i class="fa-solid fa-map text-teal me-2" aria-hidden="true"></i>Khu vực</div>
                        <div class="small fw-semibold">${escapeText(area || 'Đang cập nhật khu vực')}</div>
                    </div></div>
                </div>
                ${sourceHtml}
            </section>
            <section class="border-top pt-4 mb-2" aria-labelledby="courtAmenitiesTitle">
                <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
                    <h5 id="courtAmenitiesTitle" class="fw-bold mb-0">Tiện ích & dịch vụ</h5>
                    ${uniqueAmenities.length ? `<span class="badge bg-teal-subtle text-teal rounded-pill">${uniqueAmenities.length} tiện ích</span>` : ''}
                </div>
                ${amenitiesHtml}
            </section>`;

        // Format giá VND
        const formatVND = (amt) => new Intl.NumberFormat('vi-VN').format(amt) + ' đ';

        // Reviews placeholder (chưa có bảng reviews trong DB, sẽ bổ sung tuần 2+)
        const reviewsHtml = '<p class="text-muted mb-0">Tính năng đánh giá sẽ sớm được cập nhật. Hãy là người đầu tiên trải nghiệm!</p>';

        // Render nội dung chính
        document.getElementById('pageContent').innerHTML = `
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php" class="text-teal text-decoration-none">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="courts.php" class="text-teal text-decoration-none">Danh sách sân</a></li>
                <li class="breadcrumb-item active">${court.name}</li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Cột Trái -->
            <div class="col-lg-8">
                <div class="card border rounded-4 overflow-hidden mb-4 shadow-sm">
                    <img src="${court.image_url}" alt="${court.name}" class="w-100" style="max-height:420px; object-fit:cover;">
                    <div class="card-body p-4">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <span class="badge bg-teal-subtle text-teal fs-6 px-3 py-2 rounded-pill">
                                <i class="fa-solid fa-location-dot me-1"></i>${court.ward_name} (${court.old_area_label})
                            </span>
                        </div>

                        <h2 class="fw-extrabold mb-3">${court.name}</h2>
                        <p class="text-muted mb-4"><i class="fa-solid fa-map-location-dot me-2 text-teal"></i>${court.address}</p>

                        ${overviewHtml}
                    </div>
                </div>

                <!-- Bản Đồ Vị Trí Sân (Leaflet.js + OpenStreetMap) -->
                <div class="court-map-wrapper mb-4" id="courtDetailMapWrapper">
                    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle bg-teal-subtle text-teal d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
                                <i class="fa-solid fa-map-location-dot fs-5"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0">Vị Trí Sân Trên Bản Đồ</h5>
                                <small class="text-muted">Khu vực: ${escapeText(area || 'TP. Hồ Chí Minh')} (OpenStreetMap)</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3" onclick="if(window.CourtMap && window.CourtMap.resetView) window.CourtMap.resetView();" title="Quay về góc nhìn trung tâm">
                                <i class="fa-solid fa-crosshairs me-1"></i> Định vị
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 btn-court-map-fullscreen" onclick="if(window.CourtMap && window.CourtMap.toggleFullscreen) window.CourtMap.toggleFullscreen();" title="Phóng to toàn màn hình">
                                <i class="fa-solid fa-expand me-1 icon-fullscreen-state"></i> <span class="text-fullscreen-state">Toàn màn hình</span>
                            </button>
                        </div>
                    </div>
                    <div id="courtDetailMapContainer" class="court-map-container" style="height:350px;">
                        <button type="button" class="court-map-floating-fs-btn" onclick="if(window.CourtMap && window.CourtMap.toggleFullscreen) window.CourtMap.toggleFullscreen();" title="Phóng to / Thu nhỏ toàn màn hình" aria-label="Toàn màn hình">
                            <svg class="fs-icon-expand" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="15 3 21 3 21 9"></polyline>
                                <polyline points="9 21 3 21 3 15"></polyline>
                                <line x1="21" y1="3" x2="14" y2="10"></line>
                                <line x1="3" y1="21" x2="10" y2="14"></line>
                            </svg>
                            <svg class="fs-icon-compress d-none" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="4 14 10 14 10 20"></polyline>
                                <polyline points="20 10 14 10 14 4"></polyline>
                                <line x1="14" y1="10" x2="21" y2="3"></line>
                                <line x1="10" y1="14" x2="3" y2="21"></line>
                            </svg>
                        </button>
                        <div class="court-map-error-state">
                            <div class="spinner-border text-teal spinner-border-sm mb-2" role="status"></div>
                            <div class="small text-muted">Đang khởi tạo bản đồ OpenStreetMap...</div>
                        </div>
                    </div>
                </div>

                <!-- Đánh Giá -->
                <div class="card border rounded-4 p-4 shadow-sm mb-4">
                    <h5 class="fw-bold mb-4"><i class="fa-solid fa-comments text-teal me-2"></i>Đánh Giá Từ Vợt Thủ</h5>
                    <div class="d-flex flex-column gap-3">${reviewsHtml}</div>
                </div>
            </div>

            <!-- Cột Phải: Box Đặt Sân Nhanh -->
            <div class="col-lg-4">
                <div class="card border rounded-4 p-4 shadow-sm sticky-top" style="top:85px;">
                    <div class="small text-muted text-uppercase fw-bold mb-1">${isCatalog ? 'Giá đặt sân mô phỏng' : 'Giá thuê tham khảo'}</div>
                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="fs-2 fw-extrabold text-teal">${formatVND(court.price_per_hour)}</span>
                        <span class="text-muted">/ 1 giờ</span>
                    </div>

                    ${isCatalog ? '<p class="small text-muted mb-3">Giá trên hệ thống dùng để trải nghiệm đặt sân, không phải báo giá thực tế. Liên hệ sân để xác nhận giá và tình trạng hoạt động.</p>' : ''}
                    <div class="p-3 bg-body-tertiary rounded-3 mb-4 small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><i class="fa-solid fa-location-dot me-1 text-teal"></i> Khu vực:</span>
                            <strong class="text-body">${court.ward_name} (${court.old_area_label})</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><i class="fa-solid fa-phone me-1 text-teal"></i> Hotline sân:</span>
                            <strong class="text-teal">${escapeText(court.owner_phone || 'Chưa cập nhật')}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted"><i class="fa-solid fa-user me-1 text-teal"></i> Chủ sân:</span>
                            <strong class="text-body">${court.owner_name || ''}</strong>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a href="booking.php?court_id=${court.id}" class="btn btn-teal btn-lg rounded-pill fw-bold shadow">
                            <i class="fa-solid fa-calendar-check me-2"></i>Chọn Giờ Đặt Sân Ngay
                        </a>
                        <a href="courts.php" class="btn btn-outline-secondary rounded-pill">
                            <i class="fa-solid fa-arrow-left me-1"></i> Quay lại tìm sân khác
                        </a>
                    </div>

                    <div class="mt-4 pt-3 border-top text-center small text-muted">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i>
                        Chọn ngày và khung giờ để xem lịch trống trên hệ thống.
                    </div>
                </div>
            </div>
        </div>`;

        // Khởi tạo bản đồ Leaflet vị trí sân
        if (window.CourtMap && typeof window.CourtMap.init === 'function') {
            const lat = parseFloat(court.latitude);
            const lng = parseFloat(court.longitude);
            if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                window.CourtMap.init('courtDetailMapContainer', [court]);
            } else {
                const container = document.getElementById('courtDetailMapContainer');
                if (container) {
                    container.innerHTML = `
                        <div class="court-map-error-state text-center p-4">
                            <i class="fa-solid fa-location-dot fs-2 text-muted mb-2"></i>
                            <div class="small text-muted">Tọa độ của sân đang được cập nhật.</div>
                        </div>`;
                }
            }
        }

    } catch (err) {
        console.error('[court_detail.js] Lỗi fetch:', err);
        document.getElementById('pageContent').innerHTML = `
        <div class="text-center py-5">
            <i class="fa-solid fa-triangle-exclamation fs-1 text-danger mb-3 d-block"></i>
            <h5 class="fw-bold">Lỗi kết nối</h5>
            <p class="text-muted small">${err.message}</p>
            <a href="courts.php" class="btn btn-outline-teal rounded-pill px-4">
                <i class="fa-solid fa-arrow-left me-1"></i>Quay lại danh sách sân
            </a>
        </div>`;
    }
});