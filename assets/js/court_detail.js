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
        const res  = await fetch(`api/court-detail.php?id=${courtId}`);
        const json = await res.json();

        if (json.status !== 'success' || !json.data) {
            window.location.href = 'courts.php';
            return;
        }

        const court = json.data;

        // Cập nhật tiêu đề trang
        document.title = `${court.name} - Thông Tin Chi Tiết | GODMINTON`;

        // Amenity map — icon & label mở rộng
        const amenityMap = {
            aircon:  { icon: 'fa-snowflake',       color: 'text-info',    label: 'Hệ Thống Làm Mát',  sub: 'Điều hòa / Thông gió' },
            parking: { icon: 'fa-square-parking',   color: 'text-primary', label: 'Bãi Đỗ Xe',          sub: 'Ô tô & Xe máy rộng' },
            shower:  { icon: 'fa-shower',           color: 'text-success', label: 'Phòng Tắm',          sub: 'Nóng lạnh sạch sẽ' },
            wifi:    { icon: 'fa-wifi',             color: 'text-warning', label: 'Wifi Miễn Phí',       sub: 'Tốc độ cao' },
            cafe:    { icon: 'fa-shop',             color: 'text-danger',  label: 'Căn Tin & Nước',      sub: 'Giải khát & Đồ ăn nhẹ' },
            rental:  { icon: 'fa-medal',            color: 'text-teal',    label: 'Thuê Dụng Cụ',        sub: 'Vợt, Giày, Quả cầu' },
            locker:  { icon: 'fa-lock',             color: 'text-secondary', label: 'Tủ Khóa',          sub: 'An toàn, tiện lợi' },
            indoor:  { icon: 'fa-warehouse',        color: 'text-info',    label: 'Sân Trong Nhà',       sub: 'Không bị ảnh hưởng thời tiết' }
        };

        // Build amenities HTML
        const amenitiesHtml = (court.amenity_codes || []).map(code => {
            const a = amenityMap[code];
            if (!a) return '';
            return `
            <div class="col-sm-6 col-md-4">
                <div class="p-3 rounded-3 bg-body-tertiary border d-flex align-items-center gap-3">
                    <i class="fa-solid ${a.icon} ${a.color} fs-4"></i>
                    <div>
                        <div class="fw-bold small">${a.label}</div>
                        <div class="text-muted small">${a.sub}</div>
                    </div>
                </div>
            </div>`;
        }).join('');

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

                        <h5 class="fw-bold mb-3 border-start border-4 border-teal ps-3">Mô Tả Cụm Sân</h5>
                        <p class="text-secondary lh-lg mb-4">${court.description}</p>

                        <h5 class="fw-bold mb-3 border-start border-4 border-teal ps-3">Tiện Ích & Dịch Vụ</h5>
                        <div class="row g-3 mb-4">${amenitiesHtml}</div>
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
                    <div class="small text-muted text-uppercase fw-bold mb-1">Giá Thuê Tiêu Chuẩn</div>
                    <div class="d-flex align-items-baseline gap-2 mb-3">
                        <span class="fs-2 fw-extrabold text-teal">${formatVND(court.price_per_hour)}</span>
                        <span class="text-muted">/ 1 giờ</span>
                    </div>

                    <div class="p-3 bg-body-tertiary rounded-3 mb-4 small">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><i class="fa-solid fa-location-dot me-1 text-teal"></i> Khu vực:</span>
                            <strong class="text-body">${court.ward_name} (${court.old_area_label})</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted"><i class="fa-solid fa-phone me-1 text-teal"></i> Hotline sân:</span>
                            <strong class="text-teal">${court.owner_phone || '1900 8888'}</strong>
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
                        Bảo đảm giữ slot không bị đặt trùng với công nghệ Transaction Locking.
                    </div>
                </div>
            </div>
        </div>`;

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