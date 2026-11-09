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

        // Khung đánh giá — nội dung được nạp bởi initReviews() sau khi render
        const reviewsHtml = '<div class="text-center text-muted py-3"><div class="spinner-border spinner-border-sm text-teal me-2" role="status"></div>Đang tải đánh giá...</div>';

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
                            ${(court.status !== 'active' || String(court.booking_open) === '0') ? `
                            <span class="badge bg-secondary fs-6 px-3 py-2 rounded-pill">
                                <i class="fa-solid fa-pause me-1"></i>Tạm ngưng nhận đặt
                            </span>
                            ` : `
                            <span class="badge bg-success-subtle text-success fs-6 px-3 py-2 rounded-pill">
                                <i class="fa-solid fa-circle-check me-1"></i>Đang hoạt động
                            </span>
                            `}
                        </div>

                        <h2 class="fw-extrabold mb-3">${court.name}</h2>
                        <p class="text-muted mb-2"><i class="fa-solid fa-map-location-dot me-2 text-teal"></i>${court.address}</p>
                        <div class="mb-4">
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 py-1 fw-semibold" onclick="if(window.CourtMap) { window.CourtMap.routeToCourt(${court.latitude}, ${court.longitude}, '${escapeText(court.name)}', '${escapeText(court.address || '')}'); document.getElementById('courtDetailMapWrapper')?.scrollIntoView({behavior:'smooth', block:'center'}); }">
                                <i class="fa-solid fa-diamond-turn-right me-1"></i> Chỉ đường đến sân
                            </button>
                        </div>

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
                        <div class="d-flex gap-2 align-items-center flex-wrap">
                            <button type="button" class="btn btn-sm btn-teal rounded-pill px-3 fw-semibold" onclick="if(window.CourtMap) window.CourtMap.routeToCourt(${court.latitude}, ${court.longitude}, '${escapeText(court.name)}', '${escapeText(court.address || '')}');" title="Chỉ đường từ vị trí của bạn">
                                <i class="fa-solid fa-diamond-turn-right me-1"></i> Chỉ đường
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3" onclick="if(window.CourtMap && window.CourtMap.locateUser) window.CourtMap.locateUser(); else if(window.CourtMap && window.CourtMap.resetView) window.CourtMap.resetView();" title="Định vị vị trí hiện tại của tôi">
                                <i class="fa-solid fa-location-crosshairs me-1"></i> Định vị
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 btn-court-map-fullscreen" onclick="if(window.CourtMap && window.CourtMap.toggleFullscreen) window.CourtMap.toggleFullscreen();" title="Phóng to toàn màn hình">
                                <i class="fa-solid fa-expand me-1 icon-fullscreen-state"></i> <span class="text-fullscreen-state">Toàn màn hình</span>
                            </button>
                        </div>
                    </div>
                    <div id="courtDetailMapContainer" class="court-map-container" style="height:350px;">
                        <div class="court-map-error-state">
                            <div class="spinner-border text-teal spinner-border-sm mb-2" role="status"></div>
                            <div class="small text-muted">Đang khởi tạo bản đồ OpenStreetMap...</div>
                        </div>
                    </div>
                </div>

                <!-- Đánh Giá -->
                <div class="card border rounded-4 p-4 shadow-sm mb-4">
                    <h5 class="fw-bold mb-4"><i class="fa-solid fa-comments text-teal me-2"></i>Đánh Giá Từ Vợt Thủ</h5>
                    <div id="courtReviews" class="d-flex flex-column gap-3">${reviewsHtml}</div>
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

                    ${(court.status !== 'active' || String(court.booking_open) === '0') ? `
                    <div class="alert alert-warning d-flex align-items-center rounded-3 mb-3 p-3">
                        <i class="fa-solid fa-triangle-exclamation fs-4 me-3 text-warning"></i>
                        <div>
                            <div class="fw-bold">Sân hiện đang tạm ngưng nhận đặt</div>
                            <div class="small text-muted">Sân này đang tạm ngừng nhận đơn mới. Quý khách vui lòng chọn sân khác hoặc quay lại sau.</div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <button class="btn btn-secondary btn-lg rounded-pill fw-bold shadow opacity-75" disabled>
                            <i class="fa-solid fa-ban me-2"></i>Sân Đang Tạm Ngưng
                        </button>
                        <a href="courts.php" class="btn btn-outline-teal rounded-pill">
                            <i class="fa-solid fa-arrow-left me-1"></i> Quay lại tìm sân khác
                        </a>
                    </div>
                    ` : `
                    <div class="d-grid gap-2">
                        <a href="booking.php?court_id=${court.id}" class="btn btn-teal btn-lg rounded-pill fw-bold shadow">
                            <i class="fa-solid fa-calendar-check me-2"></i>Chọn Giờ Đặt Sân Ngay
                        </a>
                        <a href="courts.php" class="btn btn-outline-secondary rounded-pill">
                            <i class="fa-solid fa-arrow-left me-1"></i> Quay lại tìm sân khác
                        </a>
                    </div>
                    `}
                </div>
            </div>
        </div>`;

        initReviews(court.id);

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

/* ------------------------------------------------------------------
 * Đánh Giá Từ Vợt Thủ
 *  - Ai cũng xem được; chưa đăng nhập thấy nút "Đăng nhập để đánh giá".
 *  - Chỉ khách đã từng đặt sân mới có form (chọn 1-5 sao + bình luận).
 *  - Mỗi người 1 đánh giá / sân và có thể sửa lại.
 * ------------------------------------------------------------------ */
function initReviews(courtId) {
    const box = document.getElementById('courtReviews');
    if (!box) return;
    const esc = v => String(v ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]));
    const api = window.BookingAccount;
    const loginUrl = 'auth.php?redirect=' + encodeURIComponent(location.pathname + location.search);
    let state = null;
    let draftRating = 0;
    let editing = false;

    const stars = (n, cls = '') => [1,2,3,4,5].map(i =>
        `<i class="fa-${i <= Math.round(n) ? 'solid' : 'regular'} fa-star review-star-static ${cls}" aria-hidden="true"></i>`).join('');
    const fmtDate = iso => { const d = new Date(String(iso).replace(' ', 'T')); return isNaN(d) ? '' : d.toLocaleDateString('vi-VN'); };
    const initial = name => esc((String(name || '?').trim().split(/\s+/).pop() || '?').charAt(0).toUpperCase());

    function summaryHtml() {
        const {average, total, distribution} = state.summary;
        if (!total) return '<p class="text-muted mb-0">Chưa có đánh giá nào cho sân này.</p>';
        const rows = [5,4,3,2,1].map(n => `
            <div class="d-flex align-items-center gap-2 small">
                <span style="width:14px">${n}</span><i class="fa-solid fa-star text-warning" aria-hidden="true" style="font-size:.7rem"></i>
                <div class="progress flex-grow-1" style="height:6px"><div class="progress-bar bg-warning" style="width:${total ? distribution[n] / total * 100 : 0}%"></div></div>
                <span class="text-muted" style="width:24px;text-align:right">${distribution[n]}</span>
            </div>`).join('');
        return `
            <div class="row g-3 align-items-center">
                <div class="col-sm-4 text-center">
                    <div class="display-5 fw-extrabold text-teal" id="reviewAverage">${average.toFixed(1)}</div>
                    <div class="mb-1" aria-label="${average.toFixed(1)} trên 5 sao">${stars(average)}</div>
                    <div class="small text-muted">${total} đánh giá</div>
                </div>
                <div class="col-sm-8 d-flex flex-column gap-1">${rows}</div>
            </div>`;
    }

    function formHtml() {
        const mine = state.viewer.my_review;
        if (mine && !editing) {
            return `<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border rounded-3 p-3 bg-body-tertiary">
                <span class="small"><i class="fa-solid fa-circle-check text-teal me-2" aria-hidden="true"></i>Bạn đã đánh giá sân này. Bạn có thể sửa hoặc xóa lại bất cứ lúc nào.</span>
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3" data-review-edit><i class="fa-solid fa-pen me-1"></i>Sửa đánh giá</button>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-review-delete="${mine.id}"><i class="fa-regular fa-trash-can me-1"></i>Xóa bình luận</button>
                </div>
            </div>`;
        }
        const text = mine ? mine.comment : '';
        return `<form id="reviewForm" class="border rounded-3 p-3 bg-body-tertiary" novalidate>
            <div class="fw-semibold mb-2">${mine ? 'Sửa đánh giá của bạn' : 'Chia sẻ trải nghiệm của bạn'}</div>
            <div class="review-star-input mb-2" role="radiogroup" aria-label="Chọn số sao">
                ${[1,2,3,4,5].map(i => `<button type="button" class="review-star-btn" role="radio" data-star="${i}" aria-checked="${i === draftRating}" aria-label="${i} sao"><i class="fa-solid fa-star" aria-hidden="true"></i></button>`).join('')}
                <span class="small text-muted ms-2" id="reviewStarHint"></span>
            </div>
            <textarea class="form-control mb-1" id="reviewComment" rows="3" maxlength="1000" placeholder="Mặt sân, ánh sáng, dịch vụ, giá cả... (tối thiểu 5 ký tự)">${esc(text)}</textarea>
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <span class="small text-muted"><span id="reviewCount">${text.length}</span>/1000</span>
                <div class="d-flex gap-2">
                    ${mine ? `<button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3" data-review-delete="${mine.id}"><i class="fa-regular fa-trash-can me-1"></i>Xóa</button>` : ''}
                    ${mine ? '<button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-review-cancel>Hủy</button>' : ''}
                    <button type="submit" class="btn btn-sm btn-teal rounded-pill px-4 fw-semibold" id="reviewSubmit">${mine ? 'Cập nhật đánh giá' : 'Gửi đánh giá'}</button>
                </div>
            </div>
        </form>`;
    }

    function actionHtml() {
        const v = state.viewer;
        if (!v.logged_in) {
            return `<div class="d-flex align-items-center justify-content-between flex-wrap gap-2 border rounded-3 p-3 bg-body-tertiary">
                <span class="small text-muted">Đăng nhập để chia sẻ trải nghiệm của bạn tại sân này.</span>
                <a href="${loginUrl}" class="btn btn-sm btn-teal rounded-pill px-3 fw-semibold"><i class="fa-solid fa-right-to-bracket me-1"></i>Đăng nhập để đánh giá</a>
            </div>`;
        }
        if (!v.can_review && !v.my_review) {
            return `<div class="d-flex align-items-start gap-2 border rounded-3 p-3 bg-body-tertiary small text-muted">
                <i class="fa-solid fa-circle-info text-teal mt-1" aria-hidden="true"></i>
                <span>Chỉ khách đã từng đặt sân này mới có thể đánh giá. <a class="text-teal fw-semibold" href="booking.php?court_id=${courtId}">Đặt sân ngay</a></span>
            </div>`;
        }
        return formHtml();
    }

    function renderReviewAvatar(r) {
        if (r.avatar_url) {
            return `<span class="review-avatar flex-shrink-0 overflow-hidden p-0" style="position:relative;">
                <img src="${esc(r.avatar_url)}" alt="${esc(r.name)}" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;" onerror="this.remove()">
                ${initial(r.name)}
            </span>`;
        }
        return `<span class="review-avatar flex-shrink-0">${initial(r.name)}</span>`;
    }

    function listHtml() {
        if (!state.reviews.length) return '';
        return `<div class="d-flex flex-column gap-3">${state.reviews.map(r => `
            <article class="d-flex gap-3 ${r.is_mine ? 'review-mine' : ''}">
                ${renderReviewAvatar(r)}
                <div class="flex-grow-1" style="min-width:0">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <strong>${esc(r.name)}</strong>
                            ${r.is_mine ? '<span class="badge bg-teal-subtle text-teal rounded-pill">Của bạn</span>' : ''}
                            <span>${stars(r.rating)}</span>
                            <span class="small text-muted">${fmtDate(r.created_at)}${r.edited ? ' · đã chỉnh sửa' : ''}</span>
                        </div>
                        ${(r.is_mine || state.viewer?.is_admin) ? `
                        <div class="d-flex align-items-center gap-2">
                            ${r.is_mine ? `<button type="button" class="btn btn-sm btn-link text-teal text-decoration-none p-0 small" data-review-edit title="Sửa bình luận"><i class="fa-solid fa-pen me-1"></i>Sửa</button>` : ''}
                            <button type="button" class="btn btn-sm btn-link text-danger text-decoration-none p-0 small" data-review-delete="${r.id}" title="Xóa bình luận"><i class="fa-regular fa-trash-can me-1"></i>Xóa</button>
                        </div>` : ''}
                    </div>
                    <p class="mb-0 mt-1 text-body-secondary" style="white-space:pre-line;overflow-wrap:anywhere">${esc(r.comment)}</p>
                </div>
            </article>`).join('')}</div>`;
    }

    function paintStars() {
        box.querySelectorAll('[data-star]').forEach(b => {
            const n = +b.dataset.star;
            b.classList.toggle('active', n <= draftRating);
            b.setAttribute('aria-checked', String(n === draftRating));
        });
        const hint = box.querySelector('#reviewStarHint');
        if (hint) hint.textContent = draftRating ? ['', 'Rất tệ', 'Tệ', 'Bình thường', 'Tốt', 'Tuyệt vời'][draftRating] : 'Chọn số sao';
    }

    function render() {
        box.innerHTML = summaryHtml() + '<div id="reviewAction">' + actionHtml() + '</div>' + listHtml();
        paintStars();
    }

    async function submit() {
        const comment = box.querySelector('#reviewComment').value.trim();
        if (!draftRating) return api.notify('Vui lòng chọn số sao từ 1 đến 5.', true);
        if (comment.length < 5) return api.notify('Bình luận cần ít nhất 5 ký tự.', true);
        const btn = box.querySelector('#reviewSubmit');
        btn.disabled = true;
        try {
            const data = await api.request('api/reviews.php', {method: 'POST', body: JSON.stringify({court_id: courtId, rating: draftRating, comment})});
            state = data; editing = false;
            render();   // bình luận mới và điểm trung bình cập nhật ngay, không cần tải lại trang
        } catch (e) {
            btn.disabled = false;
            api.notify(e.message, true);
        }
    }

    async function deleteReview(reviewId) {
        const ok = await api.confirm('Bạn có chắc chắn muốn xóa bình luận đánh giá này không?');
        if (!ok) return;
        try {
            const data = await api.request('api/reviews.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'delete', court_id: courtId, review_id: Number(reviewId) || 0 })
            });
            state = data;
            editing = false;
            draftRating = data.viewer.my_review ? data.viewer.my_review.rating : 0;
            render();
            await api.notify('Đã xóa bình luận đánh giá.');
        } catch (e) {
            await api.notify(e.message || 'Không thể xóa bình luận.', true);
        }
    }

    box.addEventListener('click', e => {
        const star = e.target.closest('[data-star]');
        if (star) { draftRating = +star.dataset.star; paintStars(); return; }
        const delBtn = e.target.closest('[data-review-delete]');
        if (delBtn) { deleteReview(delBtn.dataset.reviewDelete); return; }
        if (e.target.closest('[data-review-edit]')) { editing = true; draftRating = state.viewer.my_review.rating; render(); box.querySelector('#reviewComment')?.focus(); return; }
        if (e.target.closest('[data-review-cancel]')) { editing = false; render(); }
    });
    box.addEventListener('input', e => { if (e.target.id === 'reviewCount' || e.target.id === 'reviewComment') box.querySelector('#reviewCount').textContent = e.target.value.length; });
    box.addEventListener('submit', e => { e.preventDefault(); submit(); });

    api.request('api/reviews.php?court_id=' + courtId).then(data => {
        state = data;
        draftRating = data.viewer.my_review ? data.viewer.my_review.rating : 0;
        render();
    }).catch(err => {
        box.innerHTML = `<p class="text-muted mb-0"><i class="fa-solid fa-triangle-exclamation me-2"></i>${esc(err.message)}</p>`;
    });
}
