/* ================================================================
   GODMINTON — COURTS JS
   Phụ trách: P3 (Trương Minh Triết)
   Cập nhật Task #6: Gỡ bỏ rating filter, rating badge và link .html
   ================================================================ */

let state = {
    search: '',
    district: '',
    price: '',
    amenities: [],
    bookingTypes: [],
    showFavorites: false,
    sortMode: 'default'
};

/* ================================================================
   DRAWER OPEN / CLOSE
   ================================================================ */
function openFilterDrawer() {
    document.getElementById('filterDrawer').classList.add('open');
    document.getElementById('drawerOverlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeFilterDrawer() {
    document.getElementById('filterDrawer').classList.remove('open');
    document.getElementById('drawerOverlay').classList.remove('open');
    document.body.style.overflow = '';
}

/* ================================================================
   AMENITY CHIP TOGGLE
   ================================================================ */
function toggleAmenityChip(el) {
    el.classList.toggle('selected');
}
function toggleBookingType(el) {
    el.classList.toggle('selected');
}

/* ================================================================
   APPLY DRAWER FILTERS
   ================================================================ */
function applyDrawerFilters() {
    state.district = document.getElementById('filterDistrict').value;
    state.price    = document.getElementById('filterPrice').value;

    state.amenities = [...document.querySelectorAll('#filterDrawer .amenity-chip[data-value][data-label].selected')]
        .map(c => ({ value: c.dataset.value, label: c.dataset.label }));

    closeFilterDrawer();
    updateActiveTagsStrip();
    updateFilterBadge();
    renderCourts();
}

/* ================================================================
   RESET FILTERS
   ================================================================ */
function resetDrawerFilters() {
    document.getElementById('filterDistrict').value = '';
    document.getElementById('filterPrice').value = '';
    document.querySelectorAll('#filterDrawer .amenity-chip').forEach(c => c.classList.remove('selected'));
    state.amenities = [];
    state.district = '';
    state.price = '';
    state.bookingTypes = [];
}

function resetAllFilters() {
    resetDrawerFilters();
    state.search = '';
    state.showFavorites = false;
    document.getElementById('filterSearch').value = '';
    const favBtn = document.getElementById('btnFavorite');
    if (favBtn) {
        favBtn.classList.remove('active');
        favBtn.querySelector('i').className = 'fa-regular fa-heart';
    }
    updateActiveTagsStrip();
    updateFilterBadge();
    renderCourts();
}

/* ================================================================
   ACTIVE TAGS STRIP
   ================================================================ */
function updateActiveTagsStrip() {
    const strip = document.getElementById('activeTagsStrip');
    const container = document.getElementById('activeTagsContainer');
    if (!strip || !container) return;

    const tags = [];
    if (state.district) tags.push({ label: state.district, clear: () => { state.district = ''; document.getElementById('filterDistrict').value = ''; } });
    if (state.price)    tags.push({ label: `≤ ${parseInt(state.price)/1000}k/h`, clear: () => { state.price = ''; document.getElementById('filterPrice').value = ''; } });
    state.amenities.forEach(a => tags.push({ label: a.label, clear: () => {
        state.amenities = state.amenities.filter(x => x.value !== a.value);
        document.querySelectorAll(`.amenity-chip[data-value="${a.value}"]`).forEach(c => c.classList.remove('selected'));
    }}));
    if (state.showFavorites) tags.push({ label: 'Yêu thích', clear: () => { 
        state.showFavorites = false; 
        const favBtn = document.getElementById('btnFavorite');
        if (favBtn) {
            favBtn.classList.remove('active'); 
            favBtn.querySelector('i').className = 'fa-regular fa-heart'; 
        }
    } });

    if (tags.length === 0) {
        strip.classList.remove('visible');
        return;
    }
    strip.classList.add('visible');
    container.innerHTML = tags.map((t, i) => `
        <span class="active-tag">
            ${t.label}
            <button onclick="clearTag(${i})" title="Xóa"><i class="fa-solid fa-xmark"></i></button>
        </span>`).join('');

    window._activeTags = tags;
}

function clearTag(idx) {
    if (window._activeTags && window._activeTags[idx]) {
        window._activeTags[idx].clear();
        updateActiveTagsStrip();
        updateFilterBadge();
        renderCourts();
    }
}

/* ================================================================
   FILTER COUNT BADGE
   ================================================================ */
function updateFilterBadge() {
    const count = (state.district ? 1 : 0)
        + (state.price ? 1 : 0)
        + state.amenities.length;
    const badge = document.getElementById('filterCountBadge');
    if (badge) {
        badge.textContent = count;
        badge.classList.toggle('visible', count > 0);
    }
}

/* ================================================================
   SORT MODE
   ================================================================ */
function setSortMode(btn, mode) {
    document.querySelectorAll('.sort-tab-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    state.sortMode = mode;
    renderCourts();
}

/* ================================================================
   FAVORITES
   ================================================================ */
function getFavorites() {
    return JSON.parse(localStorage.getItem('bm_fav_courts') || '[]');
}
function toggleFavorite(id) {
    let favs = getFavorites();
    if (favs.includes(id)) {
        favs = favs.filter(x => x !== id);
    } else {
        favs.push(id);
    }
    localStorage.setItem('bm_fav_courts', JSON.stringify(favs));
    return favs.includes(id);
}
function toggleFavoriteFilter() {
    state.showFavorites = !state.showFavorites;
    const favBtn = document.getElementById('btnFavorite');
    if (favBtn) {
        favBtn.classList.toggle('active', state.showFavorites);
        favBtn.querySelector('i').className = state.showFavorites ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
    }
    updateActiveTagsStrip();
    renderCourts();
}
function toggleFavCard(id, btn) {
    const isFav = toggleFavorite(id);
    const icon = btn.querySelector('i');
    if (icon) {
        icon.className = isFav ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart';
    }
    if (state.showFavorites && !isFav) {
        renderCourts();
    }
}

/* ================================================================
   RENDER COURTS
   ================================================================ */
function renderCourts() {
    let courts = (typeof MockDB !== 'undefined' && MockDB.getCourts) ? MockDB.getCourts() : [];

    if (state.search) courts = courts.filter(c =>
        c.name.toLowerCase().includes(state.search) || (c.address||'').toLowerCase().includes(state.search));
    if (state.district) courts = courts.filter(c => c.district === state.district || (c.ward_name && c.ward_name === state.district));
    if (state.price)    courts = courts.filter(c => c.min_price <= parseInt(state.price));
    if (state.amenities.length) courts = courts.filter(c =>
        state.amenities.every(a => c.amenities && c.amenities.includes(a.value)));
    if (state.showFavorites) {
        const favs = getFavorites();
        courts = courts.filter(c => favs.includes(c.id));
    }

    if (state.sortMode === 'price_asc')  courts = [...courts].sort((a,b) => a.min_price - b.min_price);
    if (state.sortMode === 'price_desc') courts = [...courts].sort((a,b) => b.min_price - a.min_price);

    const countEl = document.getElementById('courtCountText');
    if (countEl) countEl.textContent = courts.length;

    const container = document.getElementById('courtListContainer');
    if (!container) return;

    if (!courts.length) {
        if (window.GodmintonUI) {
            container.innerHTML = '<div class="col-12" id="courtsEmptyStateHolder"></div>';
            GodmintonUI.renderEmpty('courtsEmptyStateHolder', {
                icon: 'fa-magnifying-glass',
                title: 'Không tìm thấy sân phù hợp',
                desc: 'Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm để khám phá các sân khác.',
                actionText: 'Xóa bộ lọc',
                actionClick: () => resetAllFilters()
            });
        } else {
            container.innerHTML = `
            <div class="col-12 text-center py-5">
                <i class="fa-solid fa-search-minus fs-1 text-muted mb-3 d-block"></i>
                <h5 class="fw-bold">Không tìm thấy sân phù hợp</h5>
                <p class="text-muted small">Thử thay đổi bộ lọc hoặc mở rộng tìm kiếm.</p>
                <button class="btn btn-outline-teal rounded-pill px-4" onclick="resetAllFilters()">
                    <i class="fa-solid fa-rotate-left me-1"></i>Xóa bộ lọc
                </button>
            </div>`;
        }
        return;
    }

    const favs = getFavorites();

    container.innerHTML = courts.map((court, i) => `
    <div class="col-md-6 col-xl-4" data-aos="fade-up" data-aos-delay="${Math.min(i*40,120)}">
        <div class="card border rounded-4 overflow-hidden shadow-sm h-100 court-card">
            <div style="position:relative; overflow:hidden; height:185px;">
                <img src="${court.image_url || 'assets/img/gmdiscount.png'}"
                     class="w-100 h-100" style="object-fit:cover;" alt="${court.name}">
                <div style="position:absolute;top:0.6rem;left:0.6rem; display:flex; gap:0.35rem; flex-wrap:wrap;">
                    ${court.district ? `<span class="badge bg-dark bg-opacity-75 small">${court.district}</span>` : ''}
                </div>
                <button onclick="toggleFavCard(${court.id}, this)" title="Yêu thích"
                    style="position:absolute;top:0.6rem;right:0.6rem;
                           width:30px;height:30px;border-radius:50%;
                           background:rgba(0,0,0,0.55);border:none;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;">
                    <i class="${favs.includes(court.id) ? 'fa-solid fa-heart text-danger' : 'fa-regular fa-heart'}" style="color:#fff;font-size:0.75rem;"></i>
                </button>
            </div>
            <div class="p-3 d-flex flex-column flex-grow-1">
                <h6 class="fw-extrabold mb-1 lh-base">${court.name}</h6>
                <div class="small text-muted mb-2">
                    <i class="fa-solid fa-location-dot text-teal me-1"></i>${court.address || ''}
                </div>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    ${(court.amenities||[]).slice(0,3).map(a => {
                        const ic = {aircon:'fa-snowflake text-info', parking:'fa-square-parking text-primary',
                                    shower:'fa-shower text-success', rental:'fa-medal text-warning'};
                        return `<span class="badge bg-body-tertiary border small fw-semibold">
                            <i class="fa-solid ${ic[a]||'fa-check'} me-1"></i>${a}</span>`;
                    }).join('')}
                </div>
                <div class="mt-auto d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-teal fw-extrabold">${(typeof MockDB !== 'undefined' && MockDB.formatVND) ? MockDB.formatVND(court.min_price) : (court.min_price + ' đ')}</span>
                        <span class="text-muted small">/giờ</span>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="court-detail.php?id=${court.id}" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Xem chi tiết">
                            <i class="fa-solid fa-circle-info"></i>
                        </a>
                        <a href="booking.php?court_id=${court.id}" class="btn btn-teal btn-sm rounded-pill px-3 fw-bold">
                            Đặt sân
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>`).join('');

    if (typeof AOS !== 'undefined') AOS.refresh();
}

/* ================================================================
   INIT
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('filterSearch');
    let timer = null;
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                state.search = searchInput.value.trim().toLowerCase();
                renderCourts();
            }, 250);
        });
    }

    // Đọc URL param (district từ trang chủ)
    const params = new URLSearchParams(window.location.search);
    if (params.get('district')) {
        state.district = params.get('district');
        const sel = document.getElementById('filterDistrict');
        if (sel) sel.value = state.district;
        updateActiveTagsStrip();
        updateFilterBadge();
    }

    renderCourts();
});

document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFilterDrawer(); });