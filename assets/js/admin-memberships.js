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

    let currentMemberships = [];
    const invoiceModalEl = document.getElementById('membershipInvoiceModal');
    const invoiceModal = invoiceModalEl ? bootstrap.Modal.getOrCreateInstance(invoiceModalEl) : null;

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
                if (statSilver) statSilver.textContent = data.stats.silver_count || 0;
                if (statGold) statGold.textContent = data.stats.gold_count || 0;
                if (statPlatinum) statPlatinum.textContent = data.stats.platinum_count || 0;
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
            currentMemberships = items;
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
                const isPaid = Boolean(item.is_paid);

                if (isPending) {
                    statusBadgeHtml = '<span class="badge bg-warning text-dark border border-warning rounded-pill fw-bold"><i class="fa-solid fa-clock-rotate-left me-1"></i>Chờ thanh toán QR</span>';
                } else if (item.status === 'active' && !item.is_expired) {
                    statusBadgeHtml = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Đang kích hoạt</span>';
                } else if (item.status === 'rejected') {
                    statusBadgeHtml = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Đã từ chối</span>';
                } else if (item.status === 'cancelled') {
                    statusBadgeHtml = isPaid
                        ? '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Đã hủy / đổi gói</span>'
                        : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Hủy (Chưa TT)</span>';
                }

                // Cột Thời hạn gói
                let durationBadgeHtml = '';
                if (isPending) {
                    durationBadgeHtml = `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning rounded-pill px-3 py-1 fw-bold">Chờ thanh toán</span>`;
                } else if (item.status === 'active' && !item.is_expired) {
                    const badgeColor = item.days_left <= 7 ? 'bg-danger text-white' : 'bg-warning text-dark';
                    durationBadgeHtml = `<span class="badge ${badgeColor} rounded-pill px-3 py-1 fw-bold" style="font-size:0.8rem;">Còn ${item.days_left} ngày</span>`;
                } else if (item.status === 'rejected') {
                    durationBadgeHtml = `<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 fw-medium">Đã từ chối</span>`;
                } else if (item.status === 'cancelled') {
                    durationBadgeHtml = isPaid
                        ? `<span class="badge bg-secondary-subtle text-muted rounded-pill px-3 py-1 fw-medium">Đã hủy gói</span>`
                        : `<span class="badge bg-secondary-subtle text-muted rounded-pill px-3 py-1 fw-medium">Chưa kích hoạt</span>`;
                } else {
                    durationBadgeHtml = `<span class="badge bg-secondary text-white rounded-pill px-3 py-1 fw-bold">Đã hết hạn</span>`;
                }

                // Cột Thanh toán: Phân biệt rõ Đã thanh toán vs Chờ quét QR vs Chưa thanh toán
                let paymentColHtml = '';
                if (isPaid) {
                    paymentColHtml = `
                        <div class="fw-bold text-teal">${AdminApp.formatVND(item.price_paid || 0)}</div>
                        <div class="small text-success fw-semibold" style="font-size:0.74rem;"><i class="fa-solid fa-check me-1"></i>Đã thanh toán · ${AdminApp.escapeHTML(item.payment_method || 'VietQR')}</div>
                    `;
                } else if (isPending) {
                    paymentColHtml = `
                        <div class="fw-bold text-warning-emphasis">${AdminApp.formatVND(item.price_paid || 0)}</div>
                        <div class="small text-warning-emphasis" style="font-size:0.74rem;"><i class="fa-regular fa-clock me-1"></i>Chưa chuyển khoản</div>
                    `;
                } else {
                    paymentColHtml = `
                        <div class="fw-semibold text-muted text-decoration-line-through" style="font-size:0.85rem;">${AdminApp.formatVND(item.price_paid || 0)}</div>
                        <div class="small text-danger fw-semibold" style="font-size:0.74rem;"><i class="fa-solid fa-xmark me-1"></i>Chưa thanh toán (0 đ)</div>
                    `;
                }

                // Avatar / Initial
                const initial = AdminApp.escapeHTML((item.user_name || 'U').charAt(0).toUpperCase());
                const avatarHtml = item.user_avatar
                    ? `<img src="${AdminApp.escapeHTML(item.user_avatar)}" class="user-avatar-sm" alt="Avatar">`
                    : `<div class="user-avatar-sm">${initial}</div>`;

                // Cột thao tác: Chặn tuyệt đối Kích hoạt lại / Gia hạn đối với đơn chưa thanh toán hoặc đã từ chối
                let itemsHtml = '';
                if (isPending) {
                    itemsHtml = `
                        <li><button type="button" class="dropdown-item action-gradient-item action-checkin btn-approve-row" data-id="${item.id}" data-name="${AdminApp.escapeHTML(item.user_name)}" data-pkg="${AdminApp.escapeHTML(item.package_title)}">Xác nhận đã thu tiền &amp; Kích hoạt</button></li>
                        <li><button type="button" class="dropdown-item action-gradient-item action-view btn-view-invoice" data-id="${item.id}">Chi tiết đơn đăng ký</button></li>
                        <li><button type="button" class="dropdown-item action-gradient-item action-cancel btn-reject-row" data-id="${item.id}" data-name="${AdminApp.escapeHTML(item.user_name)}">Từ chối / Hủy yêu cầu</button></li>
                    `;
                } else {
                    const isCurrentlyActive = (item.status === 'active' && !item.is_expired);
                    const canExtend = Boolean(item.can_extend);
                    const canReactivate = Boolean(item.can_reactivate);

                    itemsHtml = `
                        ${canExtend ? `
                            <li><button type="button" class="dropdown-item action-gradient-item action-extend btn-open-extend" 
                                    data-id="${item.id}" 
                                    data-name="${AdminApp.escapeHTML(item.user_name)}" 
                                    data-pkg="${AdminApp.escapeHTML(item.package_title)}" 
                                    data-end="${endDate}">Gia hạn gói hội viên</button></li>
                        ` : ''}
                        ${isCurrentlyActive ? `
                            <li><button type="button" class="dropdown-item action-gradient-item action-cancel btn-cancel-membership" data-id="${item.id}" data-name="${AdminApp.escapeHTML(item.user_name)}">Hủy gói hội viên</button></li>
                        ` : ''}
                        ${canReactivate ? `
                            <li><button type="button" class="dropdown-item action-gradient-item action-checkin btn-update-status" data-id="${item.id}" data-status="active">Kích hoạt lại gói</button></li>
                        ` : ''}
                        <li><button type="button" class="dropdown-item action-gradient-item action-view btn-view-invoice" data-id="${item.id}">Chi tiết hóa đơn</button></li>
                        ${!isPaid ? `
                            <li><button type="button" class="dropdown-item action-gradient-item action-cancel btn-delete-membership" data-id="${item.id}">Xóa bản ghi chưa TT</button></li>
                        ` : ''}
                    `;
                }

                const actionHtml = `
                    <div class="dropdown d-inline-block text-end">
                        <button class="btn btn-sm booking-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                            Chọn thao tác
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm booking-action-menu">
                            ${itemsHtml}
                        </ul>
                    </div>
                `;

                return `
                    <tr class="${isPending ? 'row-pending-approval' : ''}">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                ${avatarHtml}
                                <div>
                                    <div class="fw-bold admin-text-primary">${AdminApp.escapeHTML(item.user_name)}</div>
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
                            <div class="small fw-semibold admin-text-primary">${AdminApp.escapeHTML(item.package_title)}</div>
                        </td>
                        <td>
                            ${durationBadgeHtml}
                        </td>
                        <td>
                            ${paymentColHtml}
                        </td>
                        <td>
                            ${statusBadgeHtml}
                        </td>
                        <td class="text-end text-nowrap">
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
    // Các hàm xử lý Thao Tác Hội Viên & Hóa Đơn
    // ========================================================
    function openExtendModal(id, name, pkg, end) {
        if (extendHiddenId) extendHiddenId.value = id;
        if (extendName) extendName.textContent = name;
        if (extendTitle) extendTitle.textContent = pkg;
        if (extendCurrentEnd) extendCurrentEnd.textContent = end;
        if (extendDaysInput) extendDaysInput.value = 30;

        if (extendModal) extendModal.show();
    }

    async function cancelMembership(id, name) {
        const ok = await AdminApp.confirmAction(
            'Hủy gói hội viên?',
            `Bạn có chắc chắn muốn hủy gói hội viên của thành viên "${name}"? Khách hàng sẽ không còn được hưởng ưu đãi giảm giá tiền sân.`
        );
        if (!ok) return;

        try {
            await BookingAccount.request('api/admin/memberships.php', {
                method: 'POST',
                body: JSON.stringify({ action: 'update_status', id: Number(id), status: 'cancelled' })
            });
            AdminApp.toast('success', 'Đã hủy gói hội viên thành công!');
            loadMemberships(currentPage);
        } catch (err) {
            AdminApp.toast('error', err.message || 'Lỗi khi hủy gói hội viên.');
        }
    }

    function showMembershipInvoice(item) {
        if (!item) return;

        const subEl = document.getElementById('modalInvoiceSub');
        if (subEl) {
            const createdDate = item.created_at ? item.created_at.substring(0, 16).replace('T', ' ') : '—';
            subEl.textContent = `Mã đăng ký: #MB-${String(item.id).padStart(6, '0')} · Ngày tạo: ${createdDate}`;
        }

        const badgeEl = document.getElementById('modalInvoiceStatusBadge');
        if (badgeEl) {
            if (item.status === 'pending') {
                badgeEl.innerHTML = '<span class="badge bg-warning text-dark border border-warning rounded-pill fw-bold px-3 py-1"><i class="fa-solid fa-clock-rotate-left me-1"></i>Chờ xác nhận</span>';
            } else if (item.status === 'active' && !item.is_expired) {
                badgeEl.innerHTML = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fw-bold px-3 py-1"><i class="fa-solid fa-circle-check me-1"></i>Đang kích hoạt</span>';
            } else if (item.status === 'rejected') {
                badgeEl.innerHTML = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1">Đã từ chối</span>';
            } else if (item.status === 'cancelled') {
                badgeEl.innerHTML = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-3 py-1">Đã hủy / đổi gói</span>';
            } else {
                badgeEl.innerHTML = '<span class="badge bg-secondary text-white rounded-pill px-3 py-1">Đã hết hạn</span>';
            }
        }

        let tierBadgeClass = 'tier-badge-silver';
        if (item.tier === 'gold') tierBadgeClass = 'tier-badge-gold';
        else if (item.tier === 'platinum') tierBadgeClass = 'tier-badge-platinum';

        const startDateFormatted = item.start_date ? item.start_date.substring(0, 10).split('-').reverse().join('/') : '--';
        const endDateFormatted = item.end_date ? item.end_date.substring(0, 10).split('-').reverse().join('/') : '--';

        const bodyEl = document.getElementById('modalInvoiceBody');
        if (bodyEl) {
            bodyEl.innerHTML = `
                <!-- 1. THÔNG TIN KHÁCH HÀNG & GÓI HỘI VIÊN -->
                <div class="card p-3 rounded-4 border bg-body-tertiary mb-3">
                    <div class="row g-3">
                        <!-- Cột trái: Khách hàng -->
                        <div class="col-md-6 border-end-md">
                            <div class="text-uppercase fw-bold text-muted small mb-2"><i class="fa-solid fa-user me-1 text-teal"></i>Khách hàng</div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Họ và tên:</span>
                                <span class="fw-semibold text-dark">${AdminApp.escapeHTML(item.user_name)}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Điện thoại:</span>
                                <a href="tel:${AdminApp.escapeHTML(item.user_phone)}" class="fw-semibold text-teal text-decoration-none">
                                    ${AdminApp.escapeHTML(item.user_phone || '—')}
                                </a>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Email:</span>
                                <span class="fw-semibold text-dark">${AdminApp.escapeHTML(item.user_email || '—')}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 small">
                                <span class="text-secondary">Mã tài khoản:</span>
                                <span class="fw-semibold text-dark">UID#${item.user_id}</span>
                            </div>
                        </div>

                        <!-- Cột phải: Thông tin gói hội viên -->
                        <div class="col-md-6">
                            <div class="text-uppercase fw-bold text-muted small mb-2"><i class="fa-solid fa-crown me-1 text-warning"></i>Gói hội viên</div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Gói đăng ký:</span>
                                <span class="fw-semibold text-teal">${AdminApp.escapeHTML(item.package_title)}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Hạng thẻ:</span>
                                <span class="badge ${tierBadgeClass} rounded-pill px-2 py-1 fw-bold" style="font-size:0.75rem;">
                                    <i class="fa-solid fa-crown me-1"></i>${item.tier_label}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Ưu đãi tiền sân:</span>
                                <span class="fw-bold text-success">Giảm ${item.discount_percent}%</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 small">
                                <span class="text-secondary">Thời hạn hiệu lực:</span>
                                <span class="fw-semibold text-dark">${item.duration_days} ngày</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. THỜI GIAN HIỆU LỰC & CHI TIẾT HÓA ĐƠN -->
                <div class="row g-3">
                    <!-- Cột trái: Thời hạn sử dụng -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 border bg-body-tertiary h-100">
                            <div class="text-uppercase fw-bold text-muted small mb-2"><i class="fa-solid fa-calendar-check me-1 text-teal"></i>Thời gian hiệu lực</div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Ngày bắt đầu:</span>
                                <span class="fw-semibold text-dark">${startDateFormatted}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Hạn kết thúc:</span>
                                <span class="fw-semibold text-danger">${endDateFormatted} (23:59:59)</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 small">
                                <span class="text-secondary">Tình trạng ngày:</span>
                                <span class="badge ${item.status === 'active' && !item.is_expired ? 'bg-warning text-dark' : 'bg-secondary text-white'} rounded-pill fw-bold px-2 py-1">
                                    ${item.status === 'active' && !item.is_expired ? `Còn ${item.days_left} ngày` : 'Hết hạn'}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải: Chi tiết thanh toán -->
                    <div class="col-md-6">
                        <div class="p-3 rounded-4 border bg-body-tertiary h-100">
                            <div class="text-uppercase fw-bold text-muted small mb-2"><i class="fa-solid fa-credit-card me-1 text-teal"></i>Chi tiết thanh toán</div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Phương thức:</span>
                                <span class="fw-semibold text-dark">${AdminApp.escapeHTML(item.payment_method || 'VietQR')}</span>
                            </div>
                            <div class="d-flex justify-content-between py-1 border-bottom small">
                                <span class="text-secondary">Trạng thái thanh toán:</span>
                                <span class="fw-bold ${item.is_paid ? 'text-success' : (item.status === 'pending' ? 'text-warning' : 'text-danger')}">
                                    ${item.is_paid ? 'Đã thanh toán thành công' : (item.status === 'pending' ? 'Chưa thanh toán (Chờ khách quét QR)' : 'Chưa thanh toán (Đã từ chối / Hủy)')}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between pt-2">
                                <span class="fw-bold text-dark">${item.is_paid ? 'Tổng tiền đã thanh toán:' : 'Số tiền yêu cầu:'}</span>
                                <span class="fs-5 fw-extrabold ${item.is_paid ? 'text-teal' : 'text-muted'}">${AdminApp.formatVND(item.price_paid || 0)}</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        const footerEl = document.getElementById('modalInvoiceFooterActions');
        if (footerEl) {
            let extraBtn = '';
            if (item.status === 'active' && !item.is_expired && item.is_paid) {
                extraBtn = `
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 btn-invoice-cancel" data-id="${item.id}">
                        <i class="fa-solid fa-ban me-1"></i> Hủy gói
                    </button>
                    <button type="button" class="btn btn-sm btn-warning text-dark rounded-pill px-3 fw-bold btn-invoice-extend" 
                            data-id="${item.id}" data-name="${AdminApp.escapeHTML(item.user_name)}" data-pkg="${AdminApp.escapeHTML(item.package_title)}" data-end="${endDateFormatted}">
                        <i class="fa-solid fa-arrows-rotate me-1"></i> Gia hạn
                    </button>
                `;
            }
            footerEl.innerHTML = `
                ${extraBtn}
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Đóng</button>
            `;

            const btnInvCancel = footerEl.querySelector('.btn-invoice-cancel');
            if (btnInvCancel) {
                btnInvCancel.addEventListener('click', () => {
                    if (invoiceModal) invoiceModal.hide();
                    cancelMembership(item.id, item.user_name);
                });
            }
            const btnInvExtend = footerEl.querySelector('.btn-invoice-extend');
            if (btnInvExtend) {
                btnInvExtend.addEventListener('click', () => {
                    if (invoiceModal) invoiceModal.hide();
                    openExtendModal(item.id, item.user_name, item.package_title, endDateFormatted);
                });
            }
        }

        const modalEl = document.getElementById('membershipInvoiceModal');
        if (modalEl) {
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        }
    }

    // ========================================================
    // Gắn sự kiện các nút trong dòng bảng
    // ========================================================
    function attachRowActionListeners() {
        // Nút Duyệt gói
        document.querySelectorAll('.btn-approve-row').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const pkg = btn.getAttribute('data-pkg');

                const ok = await AdminApp.confirmAction(
                    'Xác nhận đã thu tiền & kích hoạt gói?',
                    `Đơn này chưa được SePay tự động xác nhận. Bạn chỉ nên duyệt tay khi đã kiểm tra sao kê ngân hàng hoặc đã nhận tiền mặt cho gói "${pkg}" của "${name}". Tiếp tục kích hoạt?`
                );
                if (!ok) return;

                try {
                    const res = await BookingAccount.request('api/admin/memberships.php', {
                        method: 'POST',
                        body: JSON.stringify({ action: 'approve', id: Number(id) })
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
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
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
                        body: JSON.stringify({ action: 'reject', id: Number(id) })
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
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                const pkg = btn.getAttribute('data-pkg');
                const end = btn.getAttribute('data-end');
                openExtendModal(id, name, pkg, end);
            });
        });

        // Nút Hủy gói hội viên
        document.querySelectorAll('.btn-cancel-membership').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');
                cancelMembership(id, name);
            });
        });

        // Nút Xem chi tiết hóa đơn
        document.querySelectorAll('.btn-view-invoice').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = Number(btn.getAttribute('data-id'));
                const item = currentMemberships.find(x => Number(x.id) === id);
                if (item) {
                    showMembershipInvoice(item);
                } else {
                    console.warn('Không tìm thấy bản ghi hội viên ID:', id);
                }
            });
        });

        // Nút cập nhật trạng thái khác (ví dụ: Kích hoạt lại)
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
                        body: JSON.stringify({ action: 'update_status', id: Number(id), status: newStatus })
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
                        body: JSON.stringify({ action: 'delete', id: Number(id) })
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
                <div class="user-avatar-sm flex-shrink-0" style="width: 30px; height: 30px; font-size: 0.75rem;">
                    ${(c.name || 'K').charAt(0).toUpperCase()}
                </div>
                <div class="overflow-hidden flex-grow-1">
                    <div class="fw-bold admin-text-primary small text-truncate">${AdminApp.escapeHTML(c.name)}</div>
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
        const paymentMethod = document.getElementById('grantPaymentMethod')?.value || 'Chuyển khoản';

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
                body: JSON.stringify({
                    action: 'grant',
                    user_id: userId,
                    post_id: postId,
                    duration_days: durationDays,
                    price_paid: pricePaid,
                    payment_method: paymentMethod
                })
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

    grantModalEl?.addEventListener('shown.bs.modal', () => {
        if (grantSearchInputWrap && grantSearchInputWrap.style.display !== 'none') {
            grantSearchInput?.focus();
            handleCustomerSearch('');
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
                body: JSON.stringify({
                    action: 'extend',
                    id: membershipId,
                    days: days
                })
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
