/* ---- Explore feed data ---- */
const EXPLORE_DATA = [
    {
        type: 'event', tag: 'Sự kiện', tagColor: '#7c3aed', tagBg: 'rgba(124,58,237,0.1)',
        icon: 'fa-trophy',
        img: 'https://images.baoangiang.com.vn/image/fckeditor/upload/2023/20231123/images/T10-cau-long.jpg',
        title: 'Giải Cầu Lông Mở Rộng TP.HCM Mùa Thu 2026',
        desc: 'Giải đấu phong trào cho mọi trình độ. Đăng ký trước 25/09 để nhận ưu đãi.',
        author: 'Cầu Lông Quận 7 – Phú Mỹ Hưng Sport', authorInit: 'C',
        date: '28/09/2026', meta: '🔥 Còn 12 suất',
        ctaText: 'Đăng ký ngay', ctaUrl: 'news.php'
    },
    {
        type: 'membership', tag: 'Hội viên', tagColor: '#f59e0b', tagBg: 'rgba(245,158,11,0.1)',
        icon: 'fa-id-card',
        img: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQDFT_A1H58pN3vbqd58kHnEZ_OSx-H-r-bL-WcslwHalDYNdtbAHxZd7w&s=10',
        title: 'Gói Hội Viên Tháng — Tiết Kiệm 30%',
        desc: 'Đặt sân không giới hạn trong tháng với giá cố định. Bao gồm 2 giờ miễn phí mỗi tuần.',
        author: 'GODMINTON', authorInit: 'G',
        date: 'Còn 8 ngày', meta: '⭐ Bán chạy nhất',
        ctaText: 'Mua gói ngay', ctaUrl: 'courts.php'
    },
    {
        type: 'notice', tag: 'Thông báo', tagColor: '#ef4444', tagBg: 'rgba(239,68,68,0.1)',
        icon: 'fa-fire',
        img: 'https://sportnet.vn/uploads/clb/236/banner-san-cau-long-68-linh-trungthu-duc-1733763095.jpeg',
        title: '🔥 Sân Trống Hôm Nay — CLB Thể Thao Thủ Đức',
        desc: 'Sân 1 trống khung 17:00–18:00 và 20:30–21:30. Đặt ngay trước khi hết chỗ!',
        author: 'CLB Thể Thao Thủ Đức', authorInit: 'Đ',
        date: 'Hôm nay', meta: '⏱ Cập nhật 5 phút trước',
        ctaText: 'Đặt ngay', ctaUrl: 'booking.php'
    },
    {
        type: 'class', tag: 'Khóa học', tagColor: '#0ea5e9', tagBg: 'rgba(14,165,233,0.1)',
        icon: 'fa-person-chalkboard',
        img: 'https://thethaovanhoa.mediacdn.vn/thumb_w/300/372676912336973824/2023/10/6/01-huyen-thoai-nguyen-tien-minh-1696593429244264020372.png',
        title: 'Khóa Học Cầu Lông Cơ Bản — Trình Mới Bắt Đầu',
        desc: '12 buổi học với HLV Nguyễn Tiến Minh (cựu tuyển thủ quốc gia). Sáng T2-T4-T6.',
        author: 'HLV Nguyễn Tiến Minh', authorInit: 'M',
        date: 'Khai giảng 01/10', meta: '👥 8/12 học viên',
        ctaText: 'Đăng ký học', ctaUrl: 'news.php'
    },
    {
        type: 'promo', tag: 'Ưu đãi', tagColor: '#10b981', tagBg: 'rgba(16,185,129,0.1)',
        icon: 'fa-gift',
        img: '/static/assets/img/gmdiscount.png',
        title: 'Giảm 20% Buổi Đầu Tiên Cho Khách Mới',
        desc: 'Nhập mã GODMINTON20 khi đặt sân lần đầu để được giảm ngay 20% tổng hoá đơn.',
        author: 'GODMINTON', authorInit: 'G',
        date: 'Hết hạn 31/10', meta: '🎁 Dùng 1 lần/tài khoản',
        ctaText: 'Dùng mã ngay', ctaUrl: 'courts.php'
    },
    {
        type: 'pass', tag: 'Pass sân', tagColor: '#8b5cf6', tagBg: 'rgba(139,92,246,0.1)',
        icon: 'fa-ticket',
        img: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTxo40VnFT-aM8qzu3eUJTEi3rDVAOdDkJ99BBVVcjduQ&s=10',
        title: 'Pass 10 Buổi Thanh Xuân Pro — Tiết Kiệm 25%',
        desc: 'Mua trước 10 suất đặt sân tại Thanh Xuân Pro với giá ưu đãi, dùng bất kỳ khung giờ nào trong 3 tháng.',
        author: 'Thanh Xuân Pro', authorInit: 'T',
        date: 'Còn hiệu lực', meta: '✅ Còn 5 pass',
        ctaText: 'Mua pass', ctaUrl: 'courts.php'
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
});



/* ---- index.html ---- */
document.addEventListener('DOMContentLoaded', () => {
    const user = MockDB.getUser();
    const navHistoryEl = document.getElementById('navHistoryItem');
    if (user && navHistoryEl) navHistoryEl.style.display = '';

    // Render Featured Courts Swiper
    const allCourts = (typeof MockDB !== 'undefined') ? MockDB.getCourts() : (typeof INITIAL_COURTS !== 'undefined' ? INITIAL_COURTS : []);
    const courts = allCourts.slice(0, 6);
    const wrapper = document.getElementById('featuredCourtsWrapper');
    if (wrapper) {
        wrapper.innerHTML = courts.map(court => `
        <div class="swiper-slide">
            <div class="court-card">
                <div class="court-img-wrapper">
                    <img src="${court.image_url}" alt="${court.name}" class="court-img" loading="lazy">
                    <div class="court-badge-price">${court.price_formatted} <span class="small opacity-75">/ giờ</span></div>
                </div>
                <div class="p-3 d-flex flex-column flex-grow-1">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge bg-teal-subtle text-teal">
                            <i class="fa-solid fa-location-dot me-1"></i>${court.district}
                        </span>
                        <div class="small text-warning fw-bold">
                            <i class="fa-solid fa-star me-1"></i>${court.avg_rating}
                            <span class="text-muted fw-normal">(${court.total_reviews})</span>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-2 text-truncate">${court.name}</h5>
                    <p class="text-muted small mb-3 flex-grow-1 line-clamp-2">${court.description.substring(0, 110)}...</p>
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
                640: { slidesPerView: 2, spaceTone: 20 },
                1024: { slidesPerView: 3, spaceBetween: 24 }
            }
        });
    }

    // Render News
    const news = (typeof MockDB !== 'undefined' && MockDB.getNews) ? MockDB.getNews().slice(0, 3) : [];
    const newsContainer = document.getElementById('homeNewsContainer');
    if (newsContainer && news.length) {
        newsContainer.innerHTML = news.map(n => `
        <div class="col-md-4" data-aos="fade-up">
            <div class="card h-100 border rounded-4 overflow-hidden shadow-sm">
                <img src="${n.image_url}" class="card-img-top" style="height: 180px; object-fit: cover;" alt="${n.title}">
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title fw-bold mb-2">
                        <a href="news.php#news-${n.id}" class="text-decoration-none text-body">${n.title}</a>
                    </h6>
                    <p class="card-text text-muted small flex-grow-1">${n.summary.substring(0, 95)}...</p>
                    <a href="news.php#news-${n.id}" class="text-teal fw-bold small text-decoration-none mt-2">
                        Đọc tiếp <i class="fa-solid fa-chevron-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>`).join('');
    }
});

function handleHomeSearch(e) {
    e.preventDefault();
    const district = document.getElementById('homeDistrict').value;
    const maxPrice = document.getElementById('homeMaxPrice').value;
    const amenity = document.getElementById('homeAmenity').value;
    let url = 'courts.php?';
    if (district) url += `ward_id=${encodeURIComponent(district)}&`;
    if (maxPrice) url += `max_price=${maxPrice}&`;
    if (amenity) url += `amenity=${amenity}&`;
    if (typeof window.godmintonNavigate === 'function') {
        window.godmintonNavigate(url, 'right');
    } else {
        window.location.href = url;
    }
}