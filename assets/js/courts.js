/* ================================================================
   GODMINTON — courts.js
   Trang danh sách sân (courts.php) — Tuần 1: chuyển sang AJAX thật.
   Thay MockDB.getCourts() bằng fetch('api/courts.php').
   Giữ nguyên giao diện và luồng UX đã có.
   ================================================================ */

/* ================================================================
   STATE
   ================================================================ */
let state = {
    search: '',
    ward_id: '',
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
    state.ward_id  = document.getElementById('filterDistrict').value;
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
    state.ward_id = '';
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

    // Lấy label hiển thị cho ward_id
    if (state.ward_id) {
        const sel = document.getElementById('filterDistrict');
        const label = sel.options[sel.selectedIndex]?.textContent || state.ward_id;
        tags.push({ label: label, clear: () => { state.ward_id = ''; document.getElementById('filterDistrict').value = ''; } });
    }
    if (state.price) tags.push({ label: `≤ ${parseInt(state.price)/1000}k/h`, clear: () => { state.price = ''; document.getElementById('filterPrice').value = ''; } });
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
    const count = (state.ward_id ? 1 : 0) + (state.price ? 1 : 0)
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
   FORMAT VND (client-side helper)
   ================================================================ */
function formatVND(amount) {
    return new Intl.NumberFormat('vi-VN').format(amount) + ' đ';
}

/* ================================================================
   FETCH COURTS FROM API (AJAX)
   ================================================================ */
async function fetchCourts() {
    const params = new URLSearchParams();

    if (state.search)   params.set('search', state.search);
    if (state.ward_id)  params.set('ward_id', state.ward_id);
    if (state.price)    params.set('max_price', state.price);

    // Gom amenity codes thành chuỗi phân cách dấu phẩy
    if (state.amenities.length > 0) {
        params.set('amenities', state.amenities.map(a => a.value).join(','));
    }

    const url = `api/courts.php?${params.toString()}`;

    const res  = await fetch(url);
    const json = await res.json();

    if (json.status !== 'success') {
        throw new Error(json.message || 'Lỗi khi tải danh sách sân.');
    }

    return json.data || [];
}

/* ================================================================
   RENDER COURTS — gọi API thật qua fetch(), giữ nguyên giao diện
   ================================================================ */
async function renderCourts() {
    const container = document.getElementById('courtListContainer');

    // Hiển thị loading spinner
    container.innerHTML = `
        <div class="text-center py-5 col-12">
            <div class="spinner-border text-teal" role="status"></div>
            <div class="mt-2 text-muted">Đang nạp danh sách sân...</div>
        </div>`;

    try {
        let courts = await fetchCourts();

        // --- Lọc thêm phía client: rating (chưa có trong DB) ---
        // TODO (Tuần 2+): Thêm bảng reviews trong DB, hiện tại bỏ qua lọc rating
        // if (state.minRating) courts = courts.filter(c => (c.avg_rating || 0) >= state.minRating);

        // --- Sắp xếp phía client ---
        if (state.sortMode === 'price_asc') {
            courts = [...courts].sort((a, b) => a.price_per_hour - b.price_per_hour);
        }
        // TODO: sort by rating khi có bảng reviews
        // if (state.sortMode === 'rating') courts = [...courts].sort((a,b) => (b.avg_rating||0) - (a.avg_rating||0));

        document.getElementById('courtCountText').textContent = courts.length;

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

        // Amenity icon map
        const amenityIconMap = {
            aircon:  'fa-snowflake text-info',
            parking: 'fa-square-parking text-primary',
            shower:  'fa-shower text-success',
            rental:  'fa-medal text-warning',
            cafe:    'fa-mug-hot text-danger',
            locker:  'fa-lock text-secondary',
            wifi:    'fa-wifi text-warning',
            indoor:  'fa-warehouse text-info'
        };

        container.innerHTML = courts.map((court, i) => `
        <div class="col-md-6 col-xl-4" data-aos="fade-up" data-aos-delay="${Math.min(i*40,120)}">
            <div class="card border rounded-4 overflow-hidden shadow-sm h-100 court-card">
                <div style="position:relative; overflow:hidden; height:185px;">
                    <img src="${court.image_url || 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=600&auto=format&fit=crop'}"
                         class="w-100 h-100" style="object-fit:cover;" alt="${court.name}">
                    <div style="position:absolute;top:0.6rem;left:0.6rem; display:flex; gap:0.35rem; flex-wrap:wrap;">
                        ${court.old_area_label ? `<span class="badge bg-dark bg-opacity-75 small">${court.old_area_label}</span>` : ''}
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
                        ${(court.amenity_codes || []).slice(0, 3).map(code => {
                            const ic = amenityIconMap[code] || 'fa-check text-muted';
                            const label = (court.amenities || []).find(a => a.code === code)?.label || code;
                            return `<span class="badge bg-body-tertiary border small fw-semibold">
                                <i class="fa-solid ${ic} me-1"></i>${label}</span>`;
                        }).join('')}
                    </div>
                    <div class="mt-auto d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-teal fw-extrabold">${formatVND(court.price_per_hour)}</span>
                            <span class="text-muted small">/giờ</span>
                        </div>
                        <div class="d-flex gap-1">
                            <a href="court-detail.php?id=${court.id}" class="btn btn-outline-secondary btn-sm rounded-pill px-2">
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

    } catch (err) {
        console.error('[courts.js] Lỗi fetch:', err);
        container.innerHTML = `
        <div class="col-12 text-center py-5">
            <i class="fa-solid fa-triangle-exclamation fs-1 text-danger mb-3 d-block"></i>
            <h5 class="fw-bold">Lỗi kết nối</h5>
            <p class="text-muted small">${err.message}</p>
            <button class="btn btn-outline-teal rounded-pill px-4" onclick="renderCourts()">
                <i class="fa-solid fa-rotate-right me-1"></i>Thử lại
            </button>
        </div>`;
    }
}

function toggleFavCard(id, btn) {
    const icon = btn.querySelector('i');
    const isActive = icon.className.includes('fa-solid');
    icon.className = isActive ? 'fa-regular fa-heart' : 'fa-solid fa-heart';
    icon.style.color = isActive ? '#fff' : '#f43f5e';
}

/* ================================================================
   LOAD WARDS INTO FILTER DROPDOWN (AJAX)
   ================================================================ */
async function loadWardsDropdown() {
    try {
        const res = await fetch('api/wards.php');
        const json = await res.json();
        if (json.status !== 'success') return;

        const sel = document.getElementById('filterDistrict');
        // Giữ lại option "Tất cả"
        sel.innerHTML = '<option value="">Tất cả khu vực TP.HCM</option>';
        json.data.forEach(w => {
            const opt = document.createElement('option');
            opt.value = w.id;
            opt.textContent = `${w.name} (${w.old_area_label})`;
            sel.appendChild(opt);
        });

        // Nếu có ward_id từ URL, set lại
        if (state.ward_id) {
            sel.value = state.ward_id;
        }
    } catch (e) {
        console.warn('[courts.js] Không thể tải danh sách phường:', e);
    }
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
    if (p.get('ward_id')) {
        state.ward_id = p.get('ward_id');
    }
    if (p.get('q')) {
        state.search = p.get('q').toLowerCase();
        searchInput.value = p.get('q');
    }
    if (p.get('max_price')) {
        state.price = p.get('max_price');
        const priceSelect = document.getElementById('filterPrice');
        if (priceSelect) priceSelect.value = state.price;
    }
    if (p.get('amenity')) {
        state.amenities = [{ value: p.get('amenity'), label: p.get('amenity') }];
    }

    // Load wards dropdown from API
    loadWardsDropdown();

    updateFilterBadge();
    renderCourts();
});

// ESC key closes drawer
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeFilterDrawer(); });