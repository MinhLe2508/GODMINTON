/**
 * assets/js/explore.js
 * Quản lý giao diện Khám Phá — Lọc tab, tìm kiếm, phân trang và render thẻ bài viết.
 */

document.addEventListener('DOMContentLoaded', () => {
    const gridEl = document.getElementById('exploreGrid');
    const loadingEl = document.getElementById('exploreLoading');
    const emptyEl = document.getElementById('exploreEmpty');
    const paginationNav = document.getElementById('explorePaginationNav');
    const paginationEl = document.getElementById('explorePagination');
    const searchInput = document.getElementById('exploreSearchInput');
    const tabButtons = document.querySelectorAll('.explore-tab');

    const { escape, image, request } = ContentClient;

    // Lấy trạng thái ban đầu từ URL params
    const urlParams = new URLSearchParams(window.location.search);
    let currentType = urlParams.get('tab') || '';
    let currentPage = parseInt(urlParams.get('page') || '1', 10);
    let currentSearch = urlParams.get('q') || '';

    if (currentSearch && searchInput) {
        searchInput.value = currentSearch;
    }

    // Đánh dấu tab đang active từ URL
    function syncActiveTabButton() {
        tabButtons.forEach(btn => {
            const btnType = btn.dataset.type || '';
            const isActive = btnType === currentType;
            btn.classList.toggle('active', isActive);
            btn.setAttribute('aria-pressed', String(isActive));
        });
    }

    syncActiveTabButton();

    // Định dạng ngày tháng
    function formatDate(val) {
        if (!val) return '';
        const d = new Date(val.replace(' ', 'T'));
        if (isNaN(d.getTime())) return val;
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()}`;
    }

    // Định dạng tiền tệ VNĐ
    function formatVND(val) {
        const num = Number(val || 0);
        return new Intl.NumberFormat('vi-VN').format(num) + ' đ';
    }

    // Cập nhật URL trình duyệt không reload trang
    function updateBrowserUrl() {
        const params = new URLSearchParams();
        if (currentType) params.set('tab', currentType);
        if (currentPage > 1) params.set('page', String(currentPage));
        if (currentSearch) params.set('q', currentSearch);

        const newQuery = params.toString() ? '?' + params.toString() : window.location.pathname;
        window.history.replaceState(null, '', newQuery);
    }

    // Tải bài viết từ API
    async function fetchPosts() {
        if (loadingEl) loadingEl.style.display = 'block';
        if (gridEl) gridEl.style.display = 'none';
        if (emptyEl) emptyEl.style.display = 'none';
        if (paginationNav) paginationNav.style.display = 'none';

        const params = new URLSearchParams({
            type: currentType,
            search: currentSearch,
            page: String(currentPage),
            limit: '9'
        });

        try {
            const data = await request(`api/posts.php?${params.toString()}`);
            const items = data.items || [];
            const totalPages = data.total_pages || 1;

            if (loadingEl) loadingEl.style.display = 'none';

            if (!items.length) {
                if (emptyEl) emptyEl.style.display = 'block';
                return;
            }

            renderGrid(items);
            renderPagination(totalPages);
            updateBrowserUrl();
        } catch (err) {
            if (loadingEl) loadingEl.style.display = 'none';
            if (emptyEl) {
                emptyEl.style.display = 'block';
                emptyEl.innerHTML = `
                    <i class="fa-solid fa-circle-exclamation text-danger fs-1 mb-3"></i>
                    <h5 class="fw-bold mb-2">Không thể tải danh sách bài viết</h5>
                    <p class="text-muted small mb-3">${escape(err.message)}</p>
                    <button class="btn btn-outline-teal rounded-pill px-4 btn-sm" id="btnRetry">Thử lại</button>
                `;
                document.getElementById('btnRetry')?.addEventListener('click', fetchPosts);
            }
        }
    }

    // Render danh sách thẻ bài viết
    function renderGrid(posts) {
        if (!gridEl) return;

        const html = posts.map(item => {
            const isPinned = Number(item.is_pinned) === 1;
            const cover = item.cover_image ? image(item.cover_image) : '';
            const type = item.type || 'announcement';

            // Chip thông tin loại bài
            let chipHtml = '';
            let metaHtml = '';

            if (type === 'event') {
                chipHtml = '<span class="post-type-chip chip-event"><i class="fa-solid fa-trophy"></i> Sự kiện</span>';
                if (item.event_start) {
                    metaHtml = `<span class="post-meta-text"><i class="fa-regular fa-calendar-check me-1 text-teal"></i>Khởi tranh: ${escape(formatDate(item.event_start))}</span>`;
                }
            } else if (type === 'membership') {
                chipHtml = '<span class="post-type-chip chip-membership"><i class="fa-solid fa-id-card"></i> Gói hội viên</span>';
                if (item.price !== null) {
                    metaHtml = `<span class="post-meta-text fw-bold text-teal"><i class="fa-solid fa-tag me-1"></i>${escape(formatVND(item.price))}${item.duration_days ? ` / ${escape(item.duration_days)} ngày` : ''}</span>`;
                }
            } else {
                chipHtml = '<span class="post-type-chip chip-announcement"><i class="fa-solid fa-bell"></i> Thông báo</span>';
                metaHtml = `<span class="post-meta-text"><i class="fa-regular fa-clock me-1"></i>${escape(formatDate(item.created_at))}</span>`;
            }

            const detailUrl = `post-detail.php?slug=${encodeURIComponent(item.slug || '')}`;

            return `
                <div class="col-md-6 col-lg-4" data-aos="fade-up">
                    <article class="post-card">
                        ${isPinned ? '<div class="post-pinned-badge"><i class="fa-solid fa-thumbtack"></i> Ghim đầu</div>' : ''}
                        <div class="post-card-img-wrap">
                            ${cover ? `<img class="post-card-img" src="${cover}" alt="${escape(item.title)}" loading="lazy">` : `
                                <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-teal bg-teal-subtle">
                                    <i class="fa-solid fa-newspaper fs-1 mb-2"></i>
                                    <span class="small fw-semibold">GODMINTON</span>
                                </div>
                            `}
                        </div>
                        <div class="post-card-body">
                            <div class="mb-2 d-flex align-items-center justify-content-between flex-wrap gap-1">
                                ${chipHtml}
                                <span class="post-meta-text text-muted">${escape(item.author_name || 'Admin')}</span>
                            </div>
                            <h3 class="post-card-title">
                                <a href="${detailUrl}" class="text-decoration-none text-reset">${escape(item.title)}</a>
                            </h3>
                            <p class="post-card-summary">${escape(item.summary || 'Xem nội dung chi tiết bài viết.')}</p>
                            <div class="post-card-footer">
                                ${metaHtml}
                                <a href="${detailUrl}" class="btn btn-teal btn-sm rounded-pill px-3 fw-bold">
                                    Xem chi tiết <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            `;
        }).join('');

        gridEl.innerHTML = html;
        gridEl.style.display = 'flex';
    }

    // Render thanh phân trang
    function renderPagination(totalPages) {
        if (!paginationNav || !paginationEl || totalPages <= 1) {
            if (paginationNav) paginationNav.style.display = 'none';
            return;
        }

        let pagesHtml = '';

        // Nút trang trước
        pagesHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <button class="page-link" data-page="${currentPage - 1}" aria-label="Trang trước">&laquo;</button>
            </li>
        `;

        for (let i = 1; i <= totalPages; i++) {
            pagesHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <button class="page-link" data-page="${i}">${i}</button>
                </li>
            `;
        }

        // Nút trang sau
        pagesHtml += `
            <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                <button class="page-link" data-page="${currentPage + 1}" aria-label="Trang sau">&raquo;</button>
            </li>
        `;

        paginationEl.innerHTML = pagesHtml;
        paginationNav.style.display = 'block';

        paginationEl.querySelectorAll('.page-link').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const p = parseInt(btn.dataset.page || '1', 10);
                if (p >= 1 && p <= totalPages && p !== currentPage) {
                    currentPage = p;
                    fetchPosts();
                    window.scrollTo({ top: 250, behavior: 'smooth' });
                }
            });
        });
    }

    // Sự kiện chuyển Tab
    tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const newType = btn.dataset.type || '';
            if (newType === currentType) return;

            currentType = newType;
            currentPage = 1;
            syncActiveTabButton();
            fetchPosts();
        });
    });

    // Sự kiện tìm kiếm (debounce 350ms)
    let searchDebounceTimer = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            currentSearch = searchInput.value.trim();
            currentPage = 1;
            fetchPosts();
        }, 350);
    });

    // Tải dữ liệu ban đầu
    fetchPosts();
});
