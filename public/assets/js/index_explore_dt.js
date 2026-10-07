/**
 * GODMINTON — index_explore_dt.js
 * Phụ trách: P3 (Trương Minh Triết)
 * Cập nhật Task #6: Gỡ sự kiện, voucher, tin tức, rating và chuyển link sang .php
 */

/* ---- Explore feed data (Đã gỡ event và voucher theo Task #6) ---- */
const EXPLORE_DATA = [
    {
        type: 'membership', tag: 'Hội viên', tagColor: '#f59e0b', tagBg: 'rgba(245,158,11,0.1)',
        icon: 'fa-id-card',
        img: 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&auto=format&fit=crop',
        title: 'Gói Hội Viên Tháng — Tiết Kiệm 30%',
        desc: 'Đặt sân định kỳ trong tháng với giá ưu đãi cố định. Bao gồm 2 giờ tập luyện linh hoạt mỗi tuần.',
        author: 'GODMINTON', authorInit: 'G',
        date: 'Tháng này', meta: '⭐ Gói phổ biến',
        ctaText: 'Xem sân áp dụng', ctaUrl: 'courts.php'
    },
    {
        type: 'notice', tag: 'Thông báo', tagColor: '#ef4444', tagBg: 'rgba(239,68,68,0.1)',
        icon: 'fa-fire',
        img: 'assets/img/gmdiscount.png',
        title: '🔥 Khung Giờ Vàng Đang Trống — Đặt Ngay',
        desc: 'Nhiều khung giờ tối từ 18:00 - 20:00 tại Quận 1 và Chợ Lớn vừa được mở thêm.',
        author: 'GODMINTON Live', authorInit: 'L',
        date: 'Hôm nay', meta: '⏱ Cập nhật thời gian thực',
        ctaText: 'Đặt sân ngay', ctaUrl: 'courts.php'
    },
    {
        type: 'class', tag: 'Khóa học', tagColor: '#0ea5e9', tagBg: 'rgba(14,165,233,0.1)',
        icon: 'fa-person-chalkboard',
        img: 'https://images.unsplash.com/photo-1541534741688-6078c6bfb5c5?w=600&auto=format&fit=crop',
        title: 'Lớp Cầu Lông Cơ Bản & Nâng Cao Kỹ Thuật',
        desc: 'Lớp học kỹ thuật đập cầu, di chuyển bước chân với HLV chuyên nghiệp tại các cụm sân trung tâm.',
        author: 'Ban Huấn Luyện', authorInit: 'H',
        date: 'Khai giảng hàng tuần', meta: '👥 Nhóm 6 học viên',
        ctaText: 'Khám phá sân', ctaUrl: 'courts.php'
    },
    {
        type: 'pass', tag: 'Pass sân', tagColor: '#8b5cf6', tagBg: 'rgba(139,92,246,0.1)',
        icon: 'fa-ticket',
        img: 'assets/img/logo.jpg',
        title: 'Thẻ Pass Đặt Sân Linh Hoạt 10 Buổi',
        desc: 'Tiện lợi cho các hội nhóm cầu lông, sử dụng linh hoạt mọi ngày trong tuần không lo hết giờ.',
        author: 'GODMINTON Club', authorInit: 'C',
        date: 'Còn hiệu lực', meta: '✅ Linh hoạt',
        ctaText: 'Chọn sân đặt', ctaUrl: 'courts.php'
    }
];

let activeExploreFilter = 'all';

function filterExplore(btn, type) {
    document.querySelectorAll('.explore-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    activeExploreFilter = type;
    renderExploreFeed();
}

function renderExploreFeed() {
    const feed = document.getElementById('exploreFeed');
    if (!feed) return;
    const items = activeExploreFilter === 'all'
        ? EXPLORE_DATA
        : EXPLORE_DATA.filter(d => d.type === activeExploreFilter);

    if (!items.length) {
        feed.innerHTML = `<div class="col-12 text-center py-5 text-muted">
            <i class="fa-regular fa-compass fs-1 mb-2 d-block"></i>
            Chưa có nội dung trong mục này.
        </div>`;
        return;
    }

    feed.innerHTML = items.map(item => `
    <div class="col-md-6 col-lg-4">
        <div class="feed-card">
            <img class="feed-card-img" src="${item.img}" alt="${item.title}"
                    onerror="this.style.background='#1e293b'; this.style.height='160px';">
            <div class="feed-card-body">
                <div class="feed-author-row">
                    <div class="feed-avatar">${item.authorInit}</div>
                    <div>
                        <div style="font-size:0.78rem; font-weight:700;">${item.author}</div>
                        <div style="font-size:0.68rem; color:var(--text-secondary);">${item.date}</div>
                    </div>
                    <span class="feed-tag ms-auto" style="color:${item.tagColor}; background:${item.tagBg};">
                        <i class="fa-solid ${item.icon}"></i> ${item.tag}
                    </span>
                </div>
                <h6>${item.title}</h6>
                <p class="small text-muted mb-0" style="flex:1; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;">${item.desc}</p>
                <div class="feed-card-footer">
                    <span class="small text-muted">${item.meta}</span>
                    <a href="${item.ctaUrl}" class="btn btn-teal btn-sm rounded-pill px-3 fw-bold" style="font-size:0.78rem;">
                        ${item.ctaText} <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>`).join('');
}

// Run on load
document.addEventListener('DOMContentLoaded', () => {
    renderExploreFeed();

    const user = (typeof MockDB !== 'undefined' && MockDB.getUser) ? MockDB.getUser() : null;
    const navHistoryEl = document.getElementById('navHistoryItem');
    if (user && navHistoryEl) navHistoryEl.style.display = '';

    // Render Featured Courts Swiper
    const allCourts = (typeof MockDB !== 'undefined' && MockDB.getCourts) 
        ? MockDB.getCourts() 
        : (typeof INITIAL_COURTS !== 'undefined' ? INITIAL_COURTS : []);
    const courts = allCourts.slice(0, 6);
    const wrapper = document.getElementById('featuredCourtsWrapper');
    if (wrapper && courts.length > 0) {
        wrapper.innerHTML = courts.map(court => `
        <div class="swiper-slide">
            <div class="court-card">
                <div class="court-img-wrapper">
                    <img src="${court.image_url || 'assets/img/gmdiscount.png'}" alt="${court.name}" class="court-img" loading="lazy">
                    <div class="court-badge-price">${court.price_formatted || (typeof MockDB !== 'undefined' ? MockDB.formatVND(court.min_price || court.price_per_hour) : (court.min_price + ' đ'))} <span class="small opacity-75">/ giờ</span></div>
                </div>
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-teal-subtle text-teal">
                            <i class="fa-solid fa-location-dot me-1"></i>${court.district || court.ward_name || 'TP.HCM'}
                        </span>
                    </div>
                    <h5 class="fw-bold mb-2 text-truncate">${court.name}</h5>
                    <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">${(court.description || '').substring(0, 110)}...</p>
                    <div class="d-flex gap-2">
                        <a href="court-detail.php?id=${court.id}" class="btn btn-outline-secondary btn-sm rounded-pill flex-grow-1">Chi tiết</a>
                        <a href="booking.php?court_id=${court.id}" class="btn btn-teal btn-sm rounded-pill flex-grow-1 fw-bold">Đặt sân ngay</a>
                    </div>
                </div>
            </div>
        </div>`).join('');
    }

    // Init Swiper
    if (typeof Swiper !== 'undefined' && document.querySelector('.featuredCourtsSwiper')) {
        new Swiper('.featuredCourtsSwiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            pagination: { el: '.swiper-pagination', clickable: true },
            breakpoints: {
                640: { slidesPerView: 2, spaceBetween: 20 },
                1024: { slidesPerView: 3, spaceBetween: 24 }
            }
        });
    }
});

function handleHomeSearch(e) {
    e.preventDefault();
    const district = document.getElementById('homeDistrict')?.value || '';
    const maxPrice = document.getElementById('homeMaxPrice')?.value || '';
    const amenity = document.getElementById('homeAmenity')?.value || '';
    let url = 'courts.php?';
    if (district) url += `district=${encodeURIComponent(district)}&`;
    if (maxPrice) url += `max_price=${maxPrice}&`;
    if (amenity) url += `amenity=${amenity}&`;
    if (typeof window.godmintonNavigate === 'function') {
        window.godmintonNavigate(url, 'right');
    } else {
        window.location.href = url;
    }
}