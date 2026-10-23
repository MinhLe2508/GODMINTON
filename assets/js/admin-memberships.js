/**
 * assets/js/admin-memberships.js
 * Quản lý hội viên GODMINTON cho Kênh Quản Trị Viên (Admin).
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Khởi tạo Shell Admin
    const user = AdminApp.renderShell('memberships', 'Quản lý hội viên', 'Quản trị hệ thống');
    if (!user) return;

    let currentPage = 1;
    const limit = 15;
    let cachedPackages = [];
    let cachedCustomers = [];

    // DOM Elements
    const searchInput = document.getElementById('searchKeyword');
    const filterTier = document.getElementById('filterTier');
    const filterStatus = document.getElementById('filterStatus');
    const btnReset = document.getElementById('btnResetFilters');
    const tableBody = document.getElementById('membershipTableBody');
    const emptyBox = document.getElementById('membershipEmpty');
    const paginationWrap = document.getElementById('paginationWrap');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationUl = document.getElementById('paginationUl');

    // Stat Elements
    const statPending = document.getElementById('statPendingApproval');
    const statActive = document.getElementById('statActiveMembers');
    const statSilver = document.getElementById('statCountSilver');
    const statGold = document.getElementById('statCountGold');
    const statPlatinum = document.getElementById('statCountPlatinum');
    const statRevenue = document.getElementById('statTotalRevenue');

    // Modals
    const grantModalEl = document.getElementById('modalGrantMembership');
    const grantModal = grantModalEl ? bootstrap.Modal.getOrCreateInstance(grantModalEl) : null;
    const formGrant = document.getElementById('formGrantMembership');
    const grantSearchInput = document.getElementById('grantUserSearchInput');
    const grantSearchInputWrap = document.getElementById('grantUserSearchInputWrap');
    const grantHiddenUserId = document.getElementById('grantUserId');
    const grantResultsList = document.getElementById('grantUserResultsList');
    const grantSelectedCard = document.getElementById('grantSelectedUserCard');
    const grantSelectedName = document.getElementById('grantSelectedUserName');
    const grantSelectedDetails = document.getElementById('grantSelectedUserDetails');
    const btnClearSearch = document.getElementById('btnClearUserSearch');
    const btnRemoveUser = document.getElementById('btnRemoveSelectedUser');
    const selectPackage = document.getElementById('grantPostId');
    const inputDuration = document.getElementById('grantDurationDays');
    const inputPrice = document.getElementById('grantPricePaid');

    const extendModalEl = document.getElementById('modalExtendMembership');
    const extendModal = extendModalEl ? bootstrap.Modal.getOrCreateInstance(extendModalEl) : null;
    const formExtend = document.getElementById('formExtendMembership');
    const extendHiddenId = document.getElementById('extendMembershipId');
    const extendName = document.getElementById('extendTargetName');
    const extendTitle = document.getElementById('extendPackageTitle');
    const extendCurrentEnd = document.getElementById('extendCurrentEndDate');
    const extendDaysInput = document.getElementById('extendDaysInput');

    // ========================================================
    // Tải dữ liệu hội viên
    // ========================================================
    async function loadMemberships(page = 1) {
        currentPage = page;
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-4 text-muted">
                    <div class="spinner-border spinner-border-sm text-teal mb-2" role="status"></div>
                    <div>Đang cập nhật danh sách hội viên...</div>
                </td>
            </tr>
        `;
        emptyBox.style.display = 'none';

        try {
            const params = new URLSearchParams({
                page: currentPage,
                limit: limit,
                q: (searchInput?.value || '').trim(),
                tier: filterTier?.value || '',
                status: filterStatus?.value || ''
            });

            const res = await BookingAccount.request(`api/admin/memberships.php?${params.toString()}`);
            const data = res.data || res;

            // 1. Cập nhật Thống kê KPIs
            if (data.stats) {
                if (statPending) statPending.textContent = data.stats.total_pending || 0;
                if (statActive) statActive.textContent = data.stats.total_active || 0;
                if (statSilver) statSilver.textContent = `Bạc: ${data.stats.silver_count || 0}`;
                if (statGold) statGold.textContent = `Vàng: ${data.stats.gold_count || 0}`;
                if (statPlatinum) statPlatinum.textContent = `B.Kim: ${data.stats.platinum_count || 0}`;
                if (statRevenue) statRevenue.textContent = AdminApp.formatVND(data.stats.total_revenue || 0);
            }

            // Lưu danh sách packages và customers cho modal cấp gói
            if (Array.isArray(data.available_packages)) {
                cachedPackages = data.available_packages;
                populatePackageSelect(cachedPackages);
            }
            if (Array.isArray(data.customers)) {
                cachedCustomers = data.customers;
                populateUserSelect(cachedCustomers);
            }

            // 2. Render bảng danh sách
            const items = data.items || [];
            if (!items.length) {
                tableBody.innerHTML = '';
                emptyBox.style.display = 'block';
                if (paginationWrap) paginationWrap.style.setProperty('display', 'none', 'important');
                return;
            }

            tableBody.innerHTML = items.map(item => {
                const startDate = item.start_date ? item.start_date.substring(0, 10).split('-').reverse().join('/') : '--';
                const endDate = item.end_date ? item.end_date.substring(0, 10).split('-').reverse().join('/') : '--';

                let tierBadgeClass = 'tier-badge-silver';
                if (item.tier === 'gold') tierBadgeClass = 'tier-badge-gold';
                else if (item.tier === 'platinum') tierBadgeClass = 'tier-badge-platinum';

                // Trạng thái badge
                let statusBadgeHtml = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Đã hết hạn</span>';
                const isPending = item.status === 'pending';

                if (isPending) {
                    statusBadgeHtml = '<span class="badge bg-warning text-dark border border-warning rounded-pill fw-bold"><i class="fa-solid fa-clock-rotate-left me-1"></i>Chờ xác nhận</span>';
                } else if (item.status === 'active' && !item.is_expired) {
                    statusBadgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Đang kích hoạt</span>';
                } else if (item.status === 'rejected') {
                    statusBadgeHtml = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Đã từ chối</span>';
                } else if (item.status === 'cancelled') {
                    statusBadgeHtml = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Đã hủy / đổi gói</span>';
                }

                // Days left badge
                let daysLeftHtml = '';
                if (item.status === 'active' && !item.is_expired) {
                    const badgeColor = item.days_left <= 7 ? 'bg-danger text-white' : 'bg-warning text-dark';
                    daysLeftHtml = `<span class="badge ${badgeColor} rounded-pill ms-1 fw-bold">Còn ${item.days_left} ngày</span>`;
                }

                // Avatar / Initial
                const initial = AdminApp.escapeHTML((item.user_name || 'U').charAt(0).toUpperCase());
                const avatarHtml = item.user_avatar
                    ? `<img src="${AdminApp.escapeHTML(item.user_avatar)}" class="user-avatar-sm" alt="Avatar">`
                    : `<div class="user-avatar-sm">${initial}</div>`;

                // Cột hành động
                let actionHtml = '';
                if (isPending) {
                    actionHtml = `
                        <div class="d-flex align-items-center justify-content-end gap-1">
                            <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-bold btn-approve-row"
                                    data-id="${item.id}"
                                    data-name="${AdminApp.escapeHTML(item.user_name)}"
                                    data-pkg="${AdminApp.escapeHTML(item.package_title)}">
                                <i class="fa-solid fa-circle-check me-1"></i> Duyệt gói
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2 fw-semibold btn-reject-row"
                                    data-id="${item.id}"
                                    data-name="${AdminApp.escapeHTML(item.user_name)}"
                                    title="Từ chối yêu cầu">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    `;
                } else {
                    actionHtml = `
                        <div class="dropdown d-inline-block">
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill px-3 fw-semibold btn-open-extend" 
                                    data-id="${item.id}" 
                                    data-name="${AdminApp.escapeHTML(item.user_name)}" 
                                    data-pkg="${AdminApp.escapeHTML(item.package_title)}" 
                                    data-end="${endDate}">
                                <i class="fa-solid fa-arrows-rotate me-1"></i> Gia hạn
                            </button>
                            <button class="btn btn-sm btn-light rounded-circle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Tùy chọn khác">
                                <i class="fa-solid fa-ellipsis-vertical"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                ${item.status !== 'active' ? `
                                    <li><a class="dropdown-item py-2 text-success btn-update-status" href="#" data-id="${item.id}" data-status="active"><i class="fa-solid fa-circle-check me-2"></i>Kích hoạt lại gói</a></li>
                                ` : `
                                    <li><a class="dropdown-item py-2 text-secondary btn-update-status" href="#" data-id="${item.id}" data-status="expired"><i class="fa-solid fa-clock-rotate-left me-2"></i>Đánh dấu hết hạn</a></li>
                                    <li><a class="dropdown-item py-2 text-warning btn-update-status" href="#" data-id="${item.id}" data-status="cancelled"><i class="fa-solid fa-ban me-2"></i>Hủy gói hội viên</a></li>
                                `}
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger btn-delete-membership" href="#" data-id="${item.id}"><i class="fa-solid fa-trash me-2"></i>Xóa bản ghi này</a></li>
                            </ul>
                        </div>
                    `;
                }

                return `
                    <tr class="${isPending ? 'table-warning bg-warning bg-opacity-10' : ''}">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                ${avatarHtml}
                                <div>
                                    <div class="fw-bold text-dark">${AdminApp.escapeHTML(item.user_name)}</div>
                                    <div class="small text-muted" style="font-size:0.75rem;">
                                        ${AdminApp.escapeHTML(item.user_email)} ${item.user_phone ? `· ${AdminApp.escapeHTML(item.user_phone)}` : ''}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <span class="badge ${tierBadgeClass} rounded-pill px-2 py-1 fw-bold" style="font-size:0.72rem;">
                                    <i class="fa-solid fa-crown me-1"></i>${item.tier_label}
                                </span>
                            </div>
                            <div class="small fw-semibold text-dark">${AdminApp.escapeHTML(item.package_title)}</div>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark">
                                ${isPending ? `Yêu cầu ngày: ${startDate}` : `${startDate} – ${endDate}`}
                            </div>
                            <div class="small text-muted mt-1" style="font-size:0.75rem;">
                                ${daysLeftHtml}
                                <span class="ms-1">(${item.duration_days} ngày)</span>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold text-teal">${AdminApp.formatVND(item.price_paid || 0)}</div>
                            <div class="small text-muted" style="font-size:0.75rem;">${AdminApp.escapeHTML(item.payment_method || 'VietQR')}</div>
                        </td>
                        <td>
                            ${statusBadgeHtml}
                        </td>
                        <td class="text-end">
                            ${actionHtml}
                        </td>
                    </tr>
                `;
            }).join('');

            // 3. Phân trang
            if (data.pagination && data.pagination.pages > 1) {
                renderPagination(data.pagination);
                if (paginationWrap) paginationWrap.style.removeProperty('display');
            } else {
                if (paginationWrap) paginationWrap.style.setProperty('display', 'none', 'important');
            }

            attachRowActionListeners();

        } catch (e) {
            console.error('Lỗi tải danh sách hội viên:', e);
            tableBody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-4 text-danger">
                        <i class="fa-solid fa-triangle-exclamation fs-3 d-block mb-2"></i>
                        <div>Có lỗi xảy ra khi tải dữ liệu: ${AdminApp.escapeHTML(e.message || 'Không thể kết nối máy chủ')}</div>
                        <button class="btn btn-sm btn-outline-teal rounded-pill mt-2" onclick="location.reload()">Thử lại</button>
                    </td>
                </tr>
            `;
        }
    }

    // ========================================================
    // Gắn sự kiện các nút trong dòng bảng
    // ========================================================
    function attachRowActionListeners() {
        // Nút Duyệt gói
        document.querySelectorAll('.btn-approve-row').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const pkg = btn.getAttribute('data-pkg');

                const ok = await AdminApp.confirmAction(
                    'Duyệt & kích hoạt gói hội viên?',
                    `Bạn có chắc chắn muốn duyệt và kích hoạt gói "${pkg}" cho thành viên "${name}"?`
                );
                if (!ok) return;

                try {
                    const res = await BookingAccount.request('api/admin/memberships.php', {
                        method: 'POST',
                        body: { action: 'approve', id: Number(id) }
                    });
                    AdminApp.toast('success', res.message || 'Đã duyệt gói thành công!');
                    loadMemberships(currentPage);
                } catch (err) {
                    AdminApp.toast('error', err.message || 'Lỗi khi duyệt gói.');
                }
            });
        });

        // Nút Từ chối gói
        document.querySelectorAll('.btn-reject-row').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');

                const ok = await AdminApp.confirmAction(
                    'Từ chối yêu cầu đăng ký?',
                    `Bạn có chắc chắn muốn từ chối yêu cầu đăng ký gói của "${name}"?`
                );
                if (!ok) return;

                try {
                    const res = await BookingAccount.request('api/admin/memberships.php', {
                        method: 'POST',
                        body: { action: 'reject', id: Number(id) }
                    });
                    AdminApp.toast('success', res.message || 'Đã từ chối yêu cầu.');
                    loadMemberships(currentPage);
                } catch (err) {
                    AdminApp.toast('error', err.message || 'Lỗi khi từ chối yêu cầu.');
                }
            });
        });

        // Nút mở modal Gia Hạn
        document.querySelectorAll('.btn-open-extend').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const pkg = btn.getAttribute('data-pkg');
                const end = btn.getAttribute('data-end');

                if (extendHiddenId) extendHiddenId.value = id;
                if (extendName) extendName.textContent = name;
                if (extendTitle) extendTitle.textContent = pkg;
                if (extendCurrentEnd) extendCurrentEnd.textContent = end;
                if (extendDaysInput) extendDaysInput.value = 30;

                if (extendModal) extendModal.show();
            });
        });

        // Nút cập nhật trạng thái
        document.querySelectorAll('.btn-update-status').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const newStatus = btn.getAttribute('data-status');

                const statusTexts = {
                    active: 'kích hoạt lại',
                    expired: 'đánh dấu đã hết hạn',
                    cancelled: 'hủy gói hội viên'
                };

                const ok = await AdminApp.confirmAction(
                    'Xác nhận thay đổi trạng thái?',
                    `Bạn có chắc chắn muốn ${statusTexts[newStatus] || newStatus} cho bản ghi này?`
                );
                if (!ok) return;

                try {
                    await BookingAccount.request('api/admin/memberships.php', {
                        method: 'POST',
                        body: { action: 'update_status', id: Number(id), status: newStatus }
                    });
                    AdminApp.toast('success', 'Đã cập nhật trạng thái gói thành công!');
                    loadMemberships(currentPage);
                } catch (err) {
                    AdminApp.toast('error', err.message || 'Lỗi khi cập nhật trạng thái.');
                }
            });
        });

        // Nút xóa bản ghi
        document.querySelectorAll('.btn-delete-membership').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');

                const ok = await AdminApp.confirmAction(
                    'Xóa bản ghi gói hội viên?',
                    'Hành động này sẽ xóa vĩnh viễn bản ghi hội viên khỏi hệ thống. Bạn có chắc chắn muốn xóa?'
                );
                if (!ok) return;

                try {
                    await BookingAccount.request('api/admin/memberships.php', {
                        method: 'POST',
                        body: { action: 'delete', id: Number(id) }
                    });
                    AdminApp.toast('success', 'Đã xóa bản ghi thành công!');
                    loadMemberships(currentPage);
                } catch (err) {
                    AdminApp.toast('error', err.message || 'Lỗi khi xóa bản ghi.');
                }
            });
        });
    }

    // ========================================================
    // Tìm kiếm & Đổ dữ liệu vào Modal Cấp Gói
    // ========================================================
    function populateUserSelect(customers) {
        if (Array.isArray(customers)) {
            cachedCustomers = customers;
        }
    }

    function selectCustomer(customer) {
        if (!customer) return;
        if (grantHiddenUserId) grantHiddenUserId.value = customer.id;
        if (grantSelectedName) grantSelectedName.textContent = customer.name || 'Khách hàng';
        if (grantSelectedDetails) {
            grantSelectedDetails.textContent = `${customer.email || ''}${customer.phone ? ' · ' + customer.phone : ''}`;
        }
        if (grantSelectedCard) grantSelectedCard.style.setProperty('display', 'flex', 'important');
        if (grantSearchInputWrap) grantSearchInputWrap.style.setProperty('display', 'none', 'important');
        if (grantResultsList) grantResultsList.style.display = 'none';
        if (selectPackage) selectPackage.focus();
    }

    function resetCustomerSelection() {
        if (grantHiddenUserId) grantHiddenUserId.value = '';
        if (grantSearchInput) grantSearchInput.value = '';
        if (grantSelectedCard) grantSelectedCard.style.setProperty('display', 'none', 'important');
        if (grantSearchInputWrap) grantSearchInputWrap.style.setProperty('display', 'flex', 'important');
        if (btnClearSearch) btnClearSearch.style.display = 'none';
        if (grantResultsList) grantResultsList.style.display = 'none';
        if (grantSearchInput) grantSearchInput.focus();
    }

    function renderCustomerSearchResults(list) {
        if (!grantResultsList) return;
        if (!list || !list.length) {
            grantResultsList.innerHTML = `
                <div class="p-3 text-center text-muted small">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Không tìm thấy khách hàng phù hợp
                </div>
            `;
            grantResultsList.style.display = 'block';
            return;
        }

        grantResultsList.innerHTML = list.map(c => `
            <a href="#" class="list-group-item list-group-item-action py-2 px-3 border-0 border-bottom d-flex align-items-center gap-2 user-search-item" data-id="${c.id}">
                <div class="user-avatar-sm flex-shrink-0" style="width: 30px; height: 30px; font-size: 0.75rem; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #0d9488;">
                    ${(c.name || 'K').charAt(0).toUpperCase()}
                </div>
                <div class="overflow-hidden flex-grow-1">
                    <div class="fw-bold text-dark small text-truncate">${AdminApp.escapeHTML(c.name)}</div>
                    <div class="text-muted text-truncate" style="font-size: 0.72rem;">
                        ${AdminApp.escapeHTML(c.email)} ${c.phone ? '· ' + AdminApp.escapeHTML(c.phone) : ''}
                    </div>
                </div>
            </a>
        `).join('');

        grantResultsList.querySelectorAll('.user-search-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const id = Number(item.getAttribute('data-id'));
                const found = list.find(c => Number(c.id) === id) || cachedCustomers.find(c => Number(c.id) === id);
                if (found) {
                    selectCustomer(found);
                }
            });
        });

        grantResultsList.style.display = 'block';
    }

    let searchDebounceTimer = null;
    async function handleCustomerSearch(query) {
        const q = (query || '').trim().toLowerCase();
        if (btnClearSearch) btnClearSearch.style.display = q ? 'inline-block' : 'none';

        if (!q) {
            renderCustomerSearchResults(cachedCustomers.slice(0, 20));
            return;
        }

        const localMatches = cachedCustomers.filter(c => 
            (c.name || '').toLowerCase().includes(q) || 
            (c.email || '').toLowerCase().includes(q) || 
            (c.phone || '').includes(q)
        );

        renderCustomerSearchResults(localMatches);

        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(async () => {
            try {
                const res = await BookingAccount.request(`api/admin/memberships.php?user_search=${encodeURIComponent(q)}`);
                const data = res.data || res;
                if (Array.isArray(data.customers)) {
                    data.customers.forEach(newItem => {
                        if (!cachedCustomers.some(c => c.id === newItem.id)) {
                            cachedCustomers.push(newItem);
                        }
                    });
                    renderCustomerSearchResults(data.customers);
                }
            } catch (err) {
                console.error('Lỗi tìm kiếm khách hàng:', err);
            }
        }, 250);
    }

    grantSearchInput?.addEventListener('input', (e) => {
        handleCustomerSearch(e.target.value);
    });

    grantSearchInput?.addEventListener('focus', () => {
        handleCustomerSearch(grantSearchInput.value);
    });

    btnClearSearch?.addEventListener('click', () => {
        if (grantSearchInput) grantSearchInput.value = '';
        handleCustomerSearch('');
        grantSearchInput?.focus();
    });

    btnRemoveUser?.addEventListener('click', () => {
        resetCustomerSelection();
    });

    document.addEventListener('click', (e) => {
        if (!document.getElementById('grantUserSearchGroup')?.contains(e.target)) {
            if (grantResultsList) grantResultsList.style.display = 'none';
        }
    });

    function populatePackageSelect(packages) {
        if (!selectPackage) return;
        const currentVal = selectPackage.value;
        selectPackage.innerHTML = '<option value="">-- Chọn gói hội viên --</option>' + 
            packages.map(p => `
                <option value="${p.id}" data-days="${p.duration_days}" data-price="${p.price}" data-discount="${p.discount_percent}">
                    ${AdminApp.escapeHTML(p.title)} (Giảm ${p.discount_percent}% - ${p.duration_days} ngày - ${AdminApp.formatVND(p.price)})
                </option>
            `).join('');
        if (currentVal) selectPackage.value = currentVal;
    }

    // Khi chọn gói hội viên, tự động điền số ngày và giá tiền
    selectPackage?.addEventListener('change', () => {
        const opt = selectPackage.selectedOptions[0];
        if (opt && opt.value) {
            const days = opt.getAttribute('data-days');
            const price = opt.getAttribute('data-price');
            if (inputDuration && days) inputDuration.value = days;
            if (inputPrice && price) inputPrice.value = price;
        }
    });

    // ========================================================
    // Xử lý Form Cấp Gói
    // ========================================================
    formGrant?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const userId = Number(grantHiddenUserId?.value || 0);
        const postId = Number(selectPackage?.value || 0);
        const durationDays = Number(inputDuration?.value || 30);
        const pricePaid = Number(inputPrice?.value || 0);
        const paymentMethod = document.getElementById('grantPaymentMethod')?.value || 'Admin cấp / Tiền mặt quầy';

        if (!userId) {
            AdminApp.toast('error', 'Vui lòng tìm và chọn khách hàng.');
            resetCustomerSelection();
            return;
        }
        if (!postId) {
            AdminApp.toast('error', 'Vui lòng chọn gói hội viên.');
            return;
        }

        const submitBtn = document.getElementById('btnSubmitGrant');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const res = await BookingAccount.request('api/admin/memberships.php', {
                method: 'POST',
                body: {
                    action: 'grant',
                    user_id: userId,
                    post_id: postId,
                    duration_days: durationDays,
                    price_paid: pricePaid,
                    payment_method: paymentMethod
                }
            });

            if (grantModal) grantModal.hide();
            formGrant.reset();
            resetCustomerSelection();
            AdminApp.toast('success', res.message || 'Đã cấp gói hội viên thành công!');
            loadMemberships(1);
        } catch (err) {
            AdminApp.toast('error', err.message || 'Lỗi khi cấp gói hội viên.');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });

    // Mở modal Cấp Gói từ nút trên Header
    document.getElementById('btnOpenGrantModal')?.addEventListener('click', () => {
        if (grantModal) {
            formGrant?.reset();
            resetCustomerSelection();
            grantModal.show();
        }
    });

    // ========================================================
    // Xử lý Form Gia Hạn
    // ========================================================
    // Nút chọn nhanh số ngày
    document.querySelectorAll('.btn-preset-days').forEach(btn => {
        btn.addEventListener('click', () => {
            const days = btn.getAttribute('data-days');
            if (extendDaysInput && days) extendDaysInput.value = days;
        });
    });

    formExtend?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const membershipId = Number(extendHiddenId?.value || 0);
        const days = Number(extendDaysInput?.value || 30);

        if (!membershipId || days <= 0) {
            AdminApp.toast('error', 'Số ngày gia hạn không hợp lệ.');
            return;
        }

        const submitBtn = document.getElementById('btnSubmitExtend');
        if (submitBtn) submitBtn.disabled = true;

        try {
            const res = await BookingAccount.request('api/admin/memberships.php', {
                method: 'POST',
                body: {
                    action: 'extend',
                    id: membershipId,
                    days: days
                }
            });

            if (extendModal) extendModal.hide();
            AdminApp.toast('success', res.message || `Đã gia hạn thành công thêm ${days} ngày!`);
            loadMemberships(currentPage);
        } catch (err) {
            AdminApp.toast('error', err.message || 'Lỗi khi gia hạn gói.');
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });

    // ========================================================
    // Phân trang
    // ========================================================
    function renderPagination(pg) {
        if (!paginationUl) return;
        const totalPages = pg.pages || 1;
        const cur = pg.page || 1;

        if (paginationInfo) {
            const start = (cur - 1) * pg.limit + 1;
            const end = Math.min(pg.total, cur * pg.limit);
            paginationInfo.textContent = `Hiển thị ${start} - ${end} trên tổng số ${pg.total} bản ghi`;
        }

        let html = '';
        html += `<li class="page-item ${cur === 1 ? 'disabled' : ''}">
            <button class="page-link" data-page="${cur - 1}">&laquo;</button>
        </li>`;

        for (let p = 1; p <= totalPages; p++) {
            if (p === 1 || p === totalPages || (p >= cur - 1 && p <= cur + 1)) {
                html += `<li class="page-item ${p === cur ? 'active' : ''}">
                    <button class="page-link" data-page="${p}">${p}</button>
                </li>`;
            } else if (p === cur - 2 || p === cur + 2) {
                html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
        }

        html += `<li class="page-item ${cur === totalPages ? 'disabled' : ''}">
            <button class="page-link" data-page="${cur + 1}">&raquo;</button>
        </li>`;

        paginationUl.innerHTML = html;
        paginationUl.querySelectorAll('.page-link[data-page]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetPage = Number(link.getAttribute('data-page'));
                if (targetPage > 0 && targetPage <= totalPages && targetPage !== cur) {
                    loadMemberships(targetPage);
                }
            });
        });
    }

    // ========================================================
    // Lắng nghe sự kiện Bộ Lọc & Tìm kiếm
    // ========================================================
    let searchTimeout = null;
    searchInput?.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadMemberships(1);
        }, 350);
    });

    filterTier?.addEventListener('change', () => loadMemberships(1));
    filterStatus?.addEventListener('change', () => loadMemberships(1));

    btnReset?.addEventListener('click', () => {
        if (searchInput) searchInput.value = '';
        if (filterTier) filterTier.value = '';
        if (filterStatus) filterStatus.value = '';
        loadMemberships(1);
    });

    // Tải dữ liệu lần đầu
    loadMemberships(1);
});
