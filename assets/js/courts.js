/* ================================================================
   STATE
   ================================================================ */
let state = {
    search: '',
    district: '',
    price: '',
    amenities: [],
    bookingTypes: [],
    minRating: 0,
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
   AMENITY CHIP TOGGLE (inside drawer)
   ================================================================ */
function toggleAmenityChip(el) {
    el.classList.toggle('selected');
}
function setRating(el, val) {
    document.querySelectorAll('#ratingGroup .amenity-chip').forEach(c => c.classList.remove('selected'));
    el.classList.toggle('selected');
}
function toggleBookingType(el) {
    el.classList.toggle('selected');
}

/* ================================================================
   APPLY DRAWER FILTERS → update state → rerender
   ================================================================ */
function applyDrawerFilters() {
    state.district = document.getElementById('filterDistrict').value;
    state.price    = document.getElementById('filterPrice').value;

    state.amenities = [...document.querySelectorAll('#filterDrawer .amenity-chip[data-value][data-label].selected')]
        .map(c => ({ value: c.dataset.value, label: c.dataset.label }));

    const ratingChip = document.querySelector('#ratingGroup .amenity-chip.selected');
    state.minRating = ratingChip ? parseFloat(ratingChip.dataset.value) : 0;

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
    state.minRating = 0;
    state.bookingTypes = [];
}

function resetAllFilters() {
    resetDrawerFilters();
    state.search = '';
    state.showFavorites = false;
    document.getElementById('filterSearch').value = '';
    const favBtn = document.getElementById('btnFavorite');
    favBtn.classList.remove('active');
    favBtn.querySelector('i').className = 'fa-regular fa-heart';
    updateActiveTagsStrip();
    updateFilterBadge();
    renderCourts();
}

/* ================================================================
   ACTIVE TAGS STRIP (below search bar)
   ================================================================ */
function updateActiveTagsStrip() {
    const strip = document.getElementById('activeTagsStrip');
    const container = document.getElementById('activeTagsContainer');

    const tags = [];
    if (state.district) tags.push({ label: state.district, clear: () => { state.district = ''; document.getElementById('filterDistrict').value = ''; } });
    if (state.price)    tags.push({ label: `≤ ${parseInt(state.price)/1000}k/h`, clear: () => { state.price = ''; document.getElementById('filterPrice').value = ''; } });
    state.amenities.forEach(a => tags.push({ label: a.label, clear: () => {
        state.amenities = state.amenities.filter(x => x.value !== a.value);
        document.querySelectorAll(`.amenity-chip[data-value="${a.value}"]`).forEach(c => c.classList.remove('selected'));
    }}));
    if (state.minRating) tags.push({ label: `★ ${state.minRating}+`, clear: () => { state.minRating = 0; document.querySelectorAll('#ratingGroup .amenity-chip').forEach(c => c.classList.remove('selected')); } });
    if (state.showFavorites) tags.push({ label: 'Yêu thích', clear: () => { state.showFavorites = false; document.getElementById('btnFavorite').classList.remove('active'); document.getElementById('btnFavorite').querySelector('i').className = 'fa-regular fa-heart'; } });

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
    // store for clearTag
    window._currentTags = tags;
}
function clearTag(i) {
    window._currentTags[i].clear();
    updateActiveTagsStrip();
    updateFilterBadge();
    renderCourts();
}

/* ================================================================
   FILTER COUNT BADGE on button
   ================================================================ */
function updateFilterBadge() {
    const count = (state.district ? 1 : 0) + (state.price ? 1 : 0)
        + state.amenities.length + (state.minRating ? 1 : 0);
    const badge = document.getElementById('filterCountBadge');
    const btn   = document.getElementById('btnOpenFilter');
    if (count > 0) {
        badge.textContent = count;
        badge.style.display = 'flex';
        btn.classList.add('active');
    } else {
        badge.style.display = 'none';
        btn.classList.remove('active');
    }
}

/* ================================================================
   FAVORITE FILTER TOGGLE
   ================================================================ */
function toggleFavoriteFilter() {
    state.showFavorites = !state.showFavorites;
    const btn = document.getElementById('btnFavorite');
    btn.classList.toggle('active', state.showFavorites);
    btn.querySelector('i').className = state.showFavorites ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
    updateActiveTagsStrip();
    renderCourts();
}

/* ================================================================
   SORT MODE
   ================================================================ */
function setSortMode(el, mode) {
    state.sortMode = mode;
    document.querySelectorAll('.sort-tab-btn').forEach(b => b.classList.remove('active'));
    el.classList.add('active');
    renderCourts();
}

/* ================================================================
   RENDER COURTS
   ================================================================ */
function renderCourts() {
    let courts = MockDB.getCourts();

    if (state.search) courts = courts.filter(c =>
        c.name.toLowerCase().includes(state.search) || (c.address||'').toLowerCase().includes(state.search));
    if (state.district) courts = courts.filter(c => c.district === state.district);
    if (state.price)    courts = courts.filter(c => c.min_price <= parseInt(state.price));
    if (state.amenities.length) courts = courts.filter(c =>
        state.amenities.every(a => c.amenities && c.amenities.includes(a.value)));
    if (state.minRating) courts = courts.filter(c => (c.rating || 0) >= state.minRating);

    if (state.sortMode === 'price_asc') courts = [...courts].sort((a,b) => a.min_price - b.min_price);
    if (state.sortMode === 'rating')    courts = [...courts].sort((a,b) => (b.rating||0) - (a.rating||0));

    document.getElementById('courtCountText').textContent = courts.length;
    const container = document.getElementById('courtListContainer');

    if (!courts.length) {
        container.innerHTML = `
        <div class="col-12 text-center py-5">
            <i class="fa-solid fa-search-minus fs-1 text-muted mb-3 d-block"></i>
            <h5 class="fw-bold">Không tìm thấy sân phù hợp</h5>
            <p class="text-muted small">Thử thay đổi bộ lọc hoặc mở rộng tìm kiếm.</p>
            <button class="btn btn-outline-teal rounded-pill px-4" onclick="resetAllFilters()">
                <i class="fa-solid fa-rotate-left me-1"></i>Xóa bộ lọc
            </button>
        </div>`;
        return;
    }

    container.innerHTML = courts.map((court, i) => `
    <div class="col-md-6 col-xl-4" data-aos="fade-up" data-aos-delay="${Math.min(i*40,120)}">
        <div class="card border rounded-4 overflow-hidden shadow-sm h-100 court-card">
            <div style="position:relative; overflow:hidden; height:185px;">
                <img src="${court.image_url || 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&auto=format&fit=crop'}"
                     class="w-100 h-100" style="object-fit:cover;" alt="${court.name}">
                <div style="position:absolute;top:0.6rem;left:0.6rem; display:flex; gap:0.35rem; flex-wrap:wrap;">
                    ${court.district ? `<span class="badge bg-dark bg-opacity-75 small">${court.district}</span>` : ''}
                    ${court.rating  ? `<span class="badge" style="background:rgba(13,148,136,0.9);"><i class="fa-solid fa-star" style="font-size:0.6rem;"></i> ${court.rating}</span>` : ''}
                </div>
                <button onclick="toggleFavCard(${court.id}, this)" title="Yêu thích"
                    style="position:absolute;top:0.6rem;right:0.6rem;
                           width:30px;height:30px;border-radius:50%;
                           background:rgba(0,0,0,0.55);border:none;cursor:pointer;
                           display:flex;align-items:center;justify-content:center;">
                    <i class="fa-regular fa-heart" style="color:#fff;font-size:0.75rem;"></i>
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
                        <span class="text-teal fw-extrabold">${MockDB.formatVND(court.min_price)}</span>
                        <span class="text-muted small">/giờ</span>
                    </div>
                    <div class="d-flex gap-1">
                        <a href="court-detail.html?id=${court.id}" class="btn btn-outline-secondary btn-sm rounded-pill px-2">
                            <i class="fa-solid fa-circle-info"></i>
                        </a>
                        <a href="booking.html?court_id=${court.id}" class="btn btn-teal btn-sm rounded-pill px-3 fw-bold">
                            Đặt sân
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>`).join('');

    if (typeof AOS !== 'undefined') AOS.refresh();
}

function toggleFavCard(id, btn) {
    const icon = btn.querySelector('i');
    const isActive = icon.className.includes('fa-solid');
    icon.className = isActive ? 'fa-regular fa-heart' : 'fa-solid fa-heart';
    icon.style.color = isActive ? '#fff' : '#f43f5e';
}

/* ================================================================
   INIT
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
    // Realtime text search
    const searchInput = document.getElementById('filterSearch');
    searchInput.addEventListener('input', () => {
        clearTimeout(window._st);
        window._st = setTimeout(() => {
            state.search = searchInput.value.trim().toLowerCase();
            updateActiveTagsStrip();
            renderCourts();
        }, 280);
    });

    // URL params from homepage
    const p = new URLSearchParams(window.location.search);
    if (p.get('district')) {
        state.district = p.get('district');
        document.getElementById('filterDistrict').value = state.district;
    }
    if (p.get('q')) {
        state.search = p.get('q').toLowerCase();
        searchInput.value = p.get('q');
    }

    // Show history nav if logged in
    const user = MockDB.getUser();
    const navHistoryEl = document.getElementById('navHistoryItem');
    if (user && navHistoryEl) navHistoryEl.style.display = '';

    updateFilterBadge();
    renderCourts();
});

// ESC key closes drawer
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFilterDrawer(); });