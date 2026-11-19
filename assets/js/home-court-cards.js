window.HomeCourtCards = (() => {
    const icons = {aircon:'fa-snowflake',parking:'fa-square-parking',shower:'fa-shower',rental:'fa-table-tennis-paddle-ball',cafe:'fa-mug-hot',locker:'fa-lock',wifi:'fa-wifi',indoor:'fa-warehouse'};
    const labels = {aircon:'Điều hòa',parking:'Bãi đỗ xe',shower:'Phòng tắm',rental:'Cho thuê vợt',cafe:'Căn tin / Cà phê',locker:'Tủ khóa',wifi:'Wi-Fi',indoor:'Sân trong nhà'};
    function render(courts) {
        const {escape, image} = ContentClient;
        if (!courts.length) return '<div class="swiper-slide"><p class="text-muted text-center py-4">Chưa có sân để hiển thị.</p></div>';
        return courts.map(court => {
            const id = Number(court.id);
            const source = Array.isArray(court.amenities) ? court.amenities : (court.amenity_codes || []).map(code => ({code,label:labels[code] || code}));
            const amenities = [...new Map(source.filter(a => a && a.code).map(a => [a.code,a])).values()];
            const price = new Intl.NumberFormat('vi-VN').format(Number(court.price_per_hour) || 0) + ' đ';
            const isDemo = /\[catalog:[^\]]+\]/i.test(court.description || '');
            const picture = image(court.image_url || 'assets/img/logo-teal.svg');
            return `<div class="swiper-slide" style="height:auto">
                <article class="court-card h-100">
                    <div class="court-img-wrapper" style="height:185px">
                        <img src="${picture}" alt="${escape(court.name)}" class="court-img" loading="lazy">
                        <span class="badge bg-dark bg-opacity-75 position-absolute" style="top:12px;left:12px">${escape(court.old_area_label || court.ward_name || 'Đang cập nhật khu vực')}</span>
                    </div>
                    <div class="p-3 d-flex flex-column flex-grow-1">
                        <h5 class="fw-bold mb-2 lh-base line-clamp-2" style="min-height:3em">${escape(court.name)}</h5>
                        <p class="small text-muted mb-3 line-clamp-2" style="min-height:3em"><i class="fa-solid fa-location-dot text-teal me-1" aria-hidden="true"></i>${escape(court.address || 'Địa chỉ đang cập nhật')}</p>
                        <div class="d-flex flex-wrap align-content-start gap-1 mb-3" style="min-height:50px">
                            ${amenities.length ? amenities.slice(0,3).map(a => `<span class="badge bg-body-tertiary border text-body-secondary fw-semibold"><i class="fa-solid ${icons[a.code] || 'fa-circle-check'} text-teal me-1" aria-hidden="true"></i>${escape(a.label || labels[a.code] || a.code)}</span>`).join('') : '<span class="small text-muted">Tiện ích đang cập nhật</span>'}
                            ${amenities.length>3 ? `<span class="badge bg-body-tertiary border text-body-secondary">+${amenities.length-3} tiện ích</span>` : ''}
                        </div>
                        <div class="mt-auto border-top pt-3">
                            <div class="d-flex align-items-baseline justify-content-between gap-2 mb-3">
                                <span class="text-teal fw-extrabold">${price}<span class="text-muted fw-normal small"> /giờ</span></span>
                                ${isDemo ? '<span class="small text-muted">Giá mô phỏng</span>' : ''}
                            </div>
                            <div class="d-flex gap-2">
                                <a href="court-detail.php?id=${id}" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                                <a href="booking.php?court_id=${id}" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                            </div>
                        </div>
                    </div>
                </article>
            </div>`;
        }).join('');
    }
    return {render};
})();
