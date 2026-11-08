/**
 * assets/js/admin-bookings.js
 * Quản lý đơn đặt sân — Luồng vận hành sân chuyên nghiệp (Option 2)
 * 5 trạng thái mở rộng: confirmed -> checked_in -> completed / no_show / cancelled
 */
document.addEventListener('DOMContentLoaded', async () => {
    const body = document.getElementById('bookingsTableBody');
    const empty = document.getElementById('bookingsEmpty');
    const modalEl = document.getElementById('bookingDetailModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
    let currentUser = window.__GODMINTON_USER__ || null;
    let currentBookings = [];
    let activeStopwatchInterval = null;

    try {
        const { user } = await BookingAccount.identity();
        if (!user || !['owner', 'admin'].includes(user.role)) {
            throw new Error('Bạn không có quyền quản lý đơn đặt sân.');
        }
        currentUser = user;
        window.__GODMINTON_USER__ = user;
        AdminApp.renderShell('bookings', 'Quản lý đơn đặt', 'Kênh chủ sân / Quản lý đơn đặt');
    } catch (e) {
        empty.textContent = e.message;
        empty.style.display = 'block';
        return;
    }

    let sequence = 0;

    function renderRowActions(b) {
        const id = b.id;
        let itemsHtml = '';

        if (b.booking_status === 'confirmed') {
            itemsHtml = `
                <li><button type="button" class="dropdown-item action-gradient-item action-complete" data-action="complete" data-id="${id}">Thu tiền &amp; Hoàn tất</button></li>
                <li><button type="button" class="dropdown-item action-gradient-item action-view" data-action="view" data-id="${id}">Xem chi tiết đơn</button></li>
                <li><button type="button" class="dropdown-item action-gradient-item action-noshow" data-action="noshow" data-id="${id}">Báo bùng sân</button></li>
                <li><button type="button" class="dropdown-item action-gradient-item action-cancel" data-action="cancel" data-id="${id}">Hủy đơn</button></li>
            `;
        } else if (b.booking_status === 'pending') {
            itemsHtml = `
                <li><button type="button" class="dropdown-item action-gradient-item action-view" data-action="view" data-id="${id}">Xem chi tiết đơn</button></li>
                <li><button type="button" class="dropdown-item action-gradient-item action-cancel" data-action="cancel" data-id="${id}">Hủy đơn</button></li>
            `;
        } else if (b.booking_status === 'checked_in') {
            itemsHtml = `
                <li><button type="button" class="dropdown-item action-gradient-item action-complete" data-action="complete" data-id="${id}">Thu tiền &amp; Hoàn tất</button></li>
                <li><button type="button" class="dropdown-item action-gradient-item action-view" data-action="view" data-id="${id}">Xem chi tiết đơn</button></li>
            `;
        } else if (b.booking_status === 'completed') {
            itemsHtml = `
                <li><button type="button" class="dropdown-item action-gradient-item action-view" data-action="view" data-id="${id}">Xem chi tiết hóa đơn</button></li>
            `;
        } else if (b.can_refund) {
            itemsHtml = `
                <li><button type="button" class="dropdown-item action-gradient-item action-complete" data-action="refund" data-id="${id}">Xác nhận đã hoàn cọc</button></li>
                <li><button type="button" class="dropdown-item action-gradient-item action-view" data-action="view" data-id="${id}">Xem chi tiết đơn</button></li>
            `;
        } else {
            itemsHtml = `
                <li><button type="button" class="dropdown-item action-gradient-item action-view" data-action="view" data-id="${id}">Xem chi tiết đơn</button></li>
            `;
        }

        return `
            <div class="dropdown d-inline-block text-end">
                <button class="btn btn-sm booking-action-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" data-bs-config='{"strategy":"fixed"}' aria-expanded="false">
                    Chọn thao tác
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm booking-action-menu">
                    ${itemsHtml}
                </ul>
            </div>`;
    }

    async function load() {
        const own = ++sequence;
        const searchInput = document.getElementById('searchBooking');
        const filterSelect = document.getElementById('filterStatus');
        const params = new URLSearchParams({
            q: (searchInput ? searchInput.value : '').trim(),
            status: filterSelect ? filterSelect.value : ''
        });

        empty.style.display = 'block';
        empty.textContent = 'Đang tải danh sách đơn đặt...';

        try {
            const bookings = await BookingAccount.request('api/admin/bookings.php?' + params);
            if (own !== sequence) return;
            currentBookings = Array.isArray(bookings) ? bookings : [];

            empty.style.display = currentBookings.length ? 'none' : 'block';
            empty.textContent = 'Không có đơn đặt nào phù hợp.';

            body.innerHTML = currentBookings.map(b => {
                const depositPaid = BookingAccount.money(b.deposit_amount);
                const remainingDue = BookingAccount.money(b.remaining_amount);

                let paymentColHtml = '';
                if (b.booking_status === 'completed') {
                    paymentColHtml = `
                        <div>
                            <div class="small text-teal fw-semibold">Đã cọc: ${depositPaid}</div>
                            <div class="small text-success fw-bold">Đã thu đủ 100%</div>
                        </div>`;
                } else if (b.booking_status === 'no_show') {
                    if (b.is_deposit_refunded) {
                        if (b.payment_status === 'refunded') {
                            paymentColHtml = `
                                <div>
                                    <div class="small text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Đã hoàn cọc: ${depositPaid}</div>
                                    <div class="small text-muted" title="${BookingAccount.escape(b.refund_note || '')}">Cứu cọc Hội viên</div>
                                </div>`;
                        } else {
                            paymentColHtml = `
                                <div>
                                    <div class="small text-warning fw-bold"><i class="fa-solid fa-clock-rotate-left me-1"></i>Chờ hoàn cọc: ${depositPaid}</div>
                                    <div class="small text-danger fw-semibold" title="${BookingAccount.escape(b.refund_note || '')}">Cần CK trả khách</div>
                                </div>`;
                        }
                    } else {
                        paymentColHtml = `
                            <div>
                                <div class="small text-warning fw-bold">Giữ cọc: ${depositPaid}</div>
                                <div class="small text-muted">Khách bùng sân</div>
                            </div>`;
                    }
                } else if (b.booking_status === 'cancelled') {
                    if (b.payment_status === 'unpaid') {
                        paymentColHtml = `
                            <div>
                                <div class="small text-muted">Chưa đặt cọc</div>
                                <div class="small text-muted">Hủy trước khi TT</div>
                            </div>`;
                    } else if (b.is_deposit_refunded) {
                        if (b.payment_status === 'refunded') {
                            paymentColHtml = `
                                <div>
                                    <div class="small text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Đã hoàn cọc: ${depositPaid}</div>
                                    <div class="small text-muted">Đã CK trả khách</div>
                                </div>`;
                        } else {
                            paymentColHtml = `
                                <div>
                                    <div class="small text-warning fw-bold"><i class="fa-solid fa-clock-rotate-left me-1"></i>Chờ hoàn cọc: ${depositPaid}</div>
                                    <div class="small text-danger fw-semibold">Cần CK trả khách</div>
                                </div>`;
                        }
                    } else {
                        paymentColHtml = `
                            <div>
                                <div class="small text-warning fw-bold">Giữ cọc: ${depositPaid}</div>
                                <div class="small text-muted">Khách hủy sát giờ</div>
                            </div>`;
                    }
                } else if (b.booking_status === 'pending') {
                    paymentColHtml = `
                        <div>
                            <div class="small text-warning fw-semibold">Chờ cọc: ${depositPaid}</div>
                            <div class="small text-muted">Đang chờ chuyển khoản</div>
                        </div>`;
                } else {
                    paymentColHtml = `
                        <div>
                            <div class="small text-teal fw-semibold">Đã cọc: ${depositPaid}</div>
                            <div class="small ${b.remaining_amount > 0 ? 'text-danger fw-bold' : 'text-success fw-semibold'}">
                                ${b.remaining_amount > 0 ? `Còn thiếu: ${remainingDue}` : 'Đã trả đủ'}
                            </div>
                        </div>`;
                }

                return `
                    <tr class="${b.booking_status === 'checked_in' ? 'table-success-subtle' : ''}">
                        <td class="ps-3">
                            <strong class="font-monospace text-dark">${BookingAccount.escape(b.booking_code)}</strong>
                        </td>
                        <td>
                            <div class="fw-bold admin-text-primary">${BookingAccount.escape(b.court_name)}</div>
                            <div class="small text-teal fw-semibold"><i class="fa-solid fa-layer-group me-1"></i>${BookingAccount.escape(b.sub_court_name)}</div>
                        </td>
                        <td class="text-nowrap">
                            <div class="fw-semibold admin-text-primary"><i class="fa-regular fa-calendar me-1 text-teal"></i>${BookingAccount.escape(b.booking_date || '')}</div>
                            <div class="small text-muted fw-medium"><i class="fa-regular fa-clock me-1"></i>${BookingAccount.escape(b.time_range || '')}</div>
                        </td>
                        <td>
                            <div class="fw-semibold admin-text-primary">${BookingAccount.escape(b.user_name)}</div>
                            <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i>${BookingAccount.escape(b.user_phone)}</div>
                        </td>
                        <td class="text-nowrap">${paymentColHtml}</td>
                        <td class="text-nowrap">${BookingAccount.badge(b.booking_status)}</td>
                        <td class="text-end pe-3 text-nowrap">${renderRowActions(b)}</td>
                    </tr>`;
            }).join('');
        } catch (e) {
            if (own !== sequence) return;
            body.innerHTML = '';
            empty.textContent = e.message;
        }
    }

    // Modal hiển thị chi tiết & Đồng hồ đếm giờ chơi
    function openOperationalModal(b) {
        if (!modalEl || !modal) return;

        if (activeStopwatchInterval) {
            clearInterval(activeStopwatchInterval);
            activeStopwatchInterval = null;
        }

        document.getElementById('modalBookingTitle').innerHTML = `
            <span>Đơn đặt <span class="font-monospace text-teal">${BookingAccount.escape(b.booking_code)}</span></span>
        `;
        document.getElementById('modalBookingSub').innerHTML = `
            ${BookingAccount.escape(b.court_name)} · <strong>${BookingAccount.escape(b.sub_court_name)}</strong>
        `;
        // Badge chữ sạch, không icon
        document.getElementById('modalBookingBadge').innerHTML = BookingAccount.badge(b.booking_status).replace(/<i[^>]*><\/i>/gi, '').trim();

        const modalBody = document.getElementById('modalBookingBody');
        const remainingVal = Number(b.remaining_amount || 0);
        const isCompleted = b.booking_status === 'completed';

        const tierNames = {
            silver: 'Bạc',
            gold: 'Vàng',
            platinum: 'Bạch kim'
        };

        const memberDiscount = Number(b.membership_discount_amount || 0);
        let voucherDiscount = Number(b.voucher_discount_amount || 0);
        if (!voucherDiscount && (b.voucher_code || b.voucher_id)) {
            voucherDiscount = Math.max(0, Number(b.discount_amount || 0) - memberDiscount);
        } else if (!voucherDiscount && !memberDiscount && Number(b.discount_amount || 0) > 0) {
            voucherDiscount = Number(b.discount_amount);
        }

        const memberTierName = b.membership_tier ? (tierNames[b.membership_tier] || b.membership_tier) : '';
        const memberPercentText = b.membership_discount_percent ? `${b.membership_discount_percent}%` : '';
        const memberDetail = [memberTierName, memberPercentText].filter(Boolean).join(' - ');
        const memberLabel = memberDetail ? `Giảm giá hội viên (${BookingAccount.escape(memberDetail)}):` : 'Giảm giá hội viên:';
        const voucherLabel = b.voucher_code ? `Giảm giá voucher (${BookingAccount.escape(b.voucher_code)}):` : 'Giảm giá voucher:';

        const courtFee = Number(b.court_amount || b.total_amount || 0);
        const totalFee = Number(b.total_amount || b.total_price || 0);
        const hasDiscount = (memberDiscount > 0 || voucherDiscount > 0);
        const showTotalRow = hasDiscount && courtFee > 0 && courtFee !== totalFee;

        modalBody.innerHTML = `
            <!-- 1. THÔNG TIN KHÁCH HÀNG & LỊCH ĐẶT SÂN (2 CỘT ĐỒNG NHẤT CỠ CHỮ) -->
            <div class="card p-3 rounded-4 border bg-body-tertiary mb-3">
                <div class="row g-3">
                    <!-- Cột trái: Khách hàng -->
                    <div class="col-md-6 border-end-md">
                        <div class="modal-card-title text-uppercase fw-bold text-muted mb-2">Khách hàng</div>
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Người đặt:</span>
                            <span class="info-value fw-semibold text-dark">${BookingAccount.escape(b.user_name)}</span>
                        </div>
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Điện thoại:</span>
                            <a href="tel:${BookingAccount.escape(b.user_phone)}" class="info-value fw-semibold text-teal text-decoration-none">
                                ${BookingAccount.escape(b.user_phone)}
                            </a>
                        </div>
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Email:</span>
                            <span class="info-value fw-semibold text-dark">${BookingAccount.escape(b.user_email || '—')}</span>
                        </div>
                        <div class="modal-info-row d-flex justify-content-between py-1">
                            <span class="info-label text-secondary">Ghi chú:</span>
                            <span class="info-value fw-semibold text-dark">${BookingAccount.escape(b.notes || 'Không có')}</span>
                        </div>
                    </div>

                    <!-- Cột phải: Lịch chơi -->
                    <div class="col-md-6">
                        <div class="modal-card-title text-uppercase fw-bold text-muted mb-2">Lịch chơi</div>
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Sân:</span>
                            <span class="info-value fw-semibold text-dark">${BookingAccount.escape(b.court_name)}</span>
                        </div>
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Thảm thi đấu:</span>
                            <span class="info-value fw-semibold text-teal">${BookingAccount.escape(b.sub_court_name)}</span>
                        </div>
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Ngày chơi:</span>
                            <span class="info-value fw-semibold text-dark">${BookingAccount.escape(b.booking_date)}</span>
                        </div>
                        <div class="modal-info-row d-flex justify-content-between py-1">
                            <span class="info-label text-secondary">Khung giờ:</span>
                            <span class="info-value fw-semibold text-teal">${BookingAccount.escape(b.time_range)}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. CHI TIẾT THANH TOÁN & THU TIỀN TẠI QUẦY -->
            <div class="row g-3">
                <div class="col-sm-6">
                    <div class="p-3 rounded-4 border bg-body-tertiary h-100">
                        <div class="modal-card-title text-muted fw-bold text-uppercase mb-2">Chi tiết thanh toán</div>
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Tổng tiền sân:</span>
                            <span class="info-value fw-semibold text-dark">${BookingAccount.money(courtFee || totalFee)}</span>
                        </div>
                        ${memberDiscount > 0 ? `
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">${memberLabel}</span>
                            <span class="info-value fw-semibold text-success">-${BookingAccount.money(memberDiscount)}</span>
                        </div>` : ''}
                        ${voucherDiscount > 0 ? `
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">${voucherLabel}</span>
                            <span class="info-value fw-semibold text-success">-${BookingAccount.money(voucherDiscount)}</span>
                        </div>` : ''}
                        ${showTotalRow ? `
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Tổng thanh toán:</span>
                            <span class="info-value fw-semibold text-dark">${BookingAccount.money(totalFee)}</span>
                        </div>` : ''}
                        <div class="modal-info-row d-flex justify-content-between py-1 border-bottom">
                            <span class="info-label text-secondary">Đã cọc online:</span>
                            <span class="info-value fw-semibold text-teal">${BookingAccount.money(b.deposit_amount)}</span>
                        </div>
                        <div class="modal-info-row d-flex justify-content-between py-1">
                            <span class="info-label text-secondary">Phương thức cọc:</span>
                            <span class="info-value fw-semibold text-dark">${BookingAccount.escape(b.payment_method || 'VietQR')}</span>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6">
                    ${(() => {
                        if (b.booking_status === 'cancelled' || b.booking_status === 'no_show') {
                            if (b.payment_status === 'unpaid') {
                                return `
                                <div class="p-3 rounded-4 border h-100 bg-secondary-subtle border-secondary-subtle d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="modal-card-title fw-bold text-uppercase mb-1 text-secondary">Trạng thái tiền cọc</div>
                                        <div class="fw-bold my-2 text-secondary" style="font-size: 1.45rem; line-height: 1.2;">Chưa đóng cọc</div>
                                    </div>
                                    <div class="modal-info-row text-secondary fw-medium">Đơn đã hủy trước khi khách chuyển khoản cọc.</div>
                                </div>`;
                            }
                            if (b.is_deposit_refunded) {
                                const isDone = b.payment_status === 'refunded';
                                return `
                                <div class="p-3 rounded-4 border h-100 ${isDone ? 'bg-success-subtle border-success-subtle' : 'bg-warning-subtle border-warning-subtle'} d-flex flex-column justify-content-between">
                                    <div>
                                        <div class="modal-card-title fw-bold text-uppercase mb-1 ${isDone ? 'text-success-emphasis' : 'text-warning-emphasis'}">
                                            ${isDone ? 'Đã hoàn trả cọc' : 'Cần hoàn trả cọc cho khách'}
                                        </div>
                                        <div class="fw-bold my-2 ${isDone ? 'text-success' : 'text-warning-emphasis'}" style="font-size: 1.55rem; line-height: 1.2;">
                                            ${BookingAccount.money(b.deposit_amount)}
                                        </div>
                                    </div>
                                    <div class="modal-info-row ${isDone ? 'text-success' : 'text-warning-emphasis'} fw-medium">
                                        ${isDone
                                            ? 'Đã chuyển khoản hoàn 100% tiền cọc cho khách.'
                                            : 'Vui lòng chuyển khoản trả cọc theo SĐT khách rồi bấm "Xác nhận đã hoàn cọc".'}
                                    </div>
                                </div>`;
                            }
                            return `
                            <div class="p-3 rounded-4 border h-100 bg-warning-subtle border-warning-subtle d-flex flex-column justify-content-between">
                                <div>
                                    <div class="modal-card-title fw-bold text-uppercase mb-1 text-warning-emphasis">Chủ sân giữ cọc (100%)</div>
                                    <div class="fw-bold my-2 text-warning-emphasis" style="font-size: 1.55rem; line-height: 1.2;">
                                        ${BookingAccount.money(b.deposit_amount)}
                                    </div>
                                </div>
                                <div class="modal-info-row text-warning-emphasis fw-medium">
                                    Đơn hủy sát giờ / bùng sân — Tiền cọc được giữ lại bù doanh thu cho sân.
                                </div>
                            </div>`;
                        }
                        return `
                        <div class="p-3 rounded-4 border h-100 ${remainingVal > 0 && !isCompleted ? 'bg-danger-subtle border-danger-subtle' : 'bg-success-subtle border-success-subtle'} d-flex flex-column justify-content-between">
                            <div>
                                <div class="modal-card-title fw-bold text-uppercase mb-1 ${remainingVal > 0 && !isCompleted ? 'text-danger-emphasis' : 'text-success-emphasis'}">
                                    ${isCompleted ? 'Thực thu tại quầy' : 'Thu tại quầy'}
                                </div>
                                <div class="fw-bold my-2 ${remainingVal > 0 && !isCompleted ? 'text-danger' : 'text-success'}" style="font-size: 1.55rem; line-height: 1.2;">
                                    ${isCompleted ? BookingAccount.money(b.remaining_paid || b.remaining_amount) : BookingAccount.money(b.remaining_amount)}
                                </div>
                            </div>
                            <div class="modal-info-row ${remainingVal > 0 && !isCompleted ? 'text-danger' : 'text-success'} fw-medium">
                                ${isCompleted 
                                    ? 'Đã thu đủ 100% tiền sân.' 
                                    : (remainingVal > 0 
                                        ? 'Thu trực tiếp tại quầy khi trả sân.' 
                                        : 'Khách đã thanh toán đủ.')}
                            </div>
                        </div>`;
                    })()}
                </div>
            </div>
            ${b.refund_note ? `
            <div class="alert ${b.is_deposit_refunded ? 'alert-success border-success-subtle' : 'alert-warning border-warning-subtle'} rounded-3 p-2 mt-3 mb-0 small">
                <strong><i class="${b.is_deposit_refunded ? 'fa-solid fa-gift' : 'fa-solid fa-shield-halved'} me-1"></i>Chính sách hoàn/giữ cọc:</strong> ${BookingAccount.escape(b.refund_note)}
            </div>` : ''}
        `;

        // Render nút hành động dưới chân Modal
        const actionsContainer = document.getElementById('modalFooterActions');
        const id = b.id;
        if (b.booking_status === 'confirmed' || b.booking_status === 'checked_in') {
            actionsContainer.innerHTML = `
                <button type="button" class="btn btn-outline-danger modal-footer-btn" data-modal-action="noshow" data-id="${id}">
                    Báo bùng sân
                </button>
                <button type="button" class="btn btn-outline-secondary modal-footer-btn" data-modal-action="cancel" data-id="${id}">
                    Hủy đơn
                </button>
                <button type="button" class="btn btn-success modal-footer-btn" data-modal-action="complete" data-id="${id}">
                    Thu tiền &amp; Hoàn thành
                </button>
            `;
        } else if (b.can_refund) {
            actionsContainer.innerHTML = `
                <button type="button" class="btn btn-outline-secondary modal-footer-btn" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-success modal-footer-btn" data-modal-action="refund" data-id="${id}">
                    <i class="fa-solid fa-hand-holding-dollar me-1"></i>Xác nhận đã hoàn cọc
                </button>
            `;
        } else {
            actionsContainer.innerHTML = `
                <button type="button" class="btn btn-outline-secondary modal-footer-btn" data-bs-dismiss="modal">Đóng</button>
            `;
        }

        modal.show();
    }

    // Xử lý chuyển đổi trạng thái nghiệp vụ (Option 2)
    async function handleTransition(bookingId, targetStatus) {
        const b = currentBookings.find(x => x.id === bookingId);
        const code = b ? b.booking_code : `#${bookingId}`;

        let confirmMsg = '';
        let confirmBtnText = 'Xác nhận';
        let confirmBtnColor = '#0d9488';

        if (targetStatus === 'complete') {
            const remain = b ? BookingAccount.money(b.remaining_amount) : '';
            confirmMsg = `Xác nhận thu đủ ${remain} tại quầy và chuyển trạng thái đơn ${code} sang HOÀN THÀNH?`;
            confirmBtnText = 'Thu tiền & Hoàn thành';
            confirmBtnColor = '#10b981';
        } else if (targetStatus === 'noshow') {
            confirmMsg = `Khách không tới sau giờ chơi quy định? Xác nhận ghi nhận "BÙNG SÂN" cho đơn ${code}? Tiền cọc sẽ được giữ lại và thảm đấu sẽ được tự động mở lại cho khách vãng lai thuê.`;
            confirmBtnText = 'Xác nhận bùng sân';
            confirmBtnColor = '#f59e0b';
        } else if (targetStatus === 'cancel') {
            confirmMsg = `Xác nhận chủ sân / quản trị viên chủ động hủy đơn ${code} và giải phóng khung giờ? Khách hàng sẽ được hoàn lại 100% tiền cọc.`;
            confirmBtnText = 'Hủy đơn';
            confirmBtnColor = '#ef4444';
        } else if (targetStatus === 'refund') {
            const dep = b ? BookingAccount.money(b.deposit_amount) : '';
            const cust = b ? `${b.user_name} (${b.user_phone || ''})` : 'khách hàng';
            confirmMsg = `Xác nhận bạn đã chuyển khoản hoàn trả ${dep} tiền cọc cho ${cust} của đơn ${code}?`;
            confirmBtnText = 'Đã hoàn cọc';
            confirmBtnColor = '#10b981';
        }

        const confirmed = await BookingAccount.confirm(confirmMsg);
        if (!confirmed) return;

        const targetMap = {
            checkin: 'checked_in',
            complete: 'completed',
            noshow: 'no_show',
            cancel: 'cancelled',
            refund: 'refunded'
        };
        const statusVal = targetMap[targetStatus] || targetStatus;

        try {
            const res = await BookingAccount.request('api/admin/bookings.php', {
                method: 'PATCH',
                body: JSON.stringify({
                    booking_id: Number(bookingId),
                    status: statusVal
                })
            });

            if (modal) modal.hide();
            await load();
            await BookingAccount.notify('Đã cập nhật trạng thái đơn thành công.');
        } catch (e) {
            await BookingAccount.notify(e.message, true);
        }
    }

    // Lắng nghe sự kiện chọn thao tác từ thanh chọn (Dropdown Select)
    body.addEventListener('change', async event => {
        const select = event.target.closest('.booking-action-select');
        if (!select) return;
        const id = Number(select.dataset.id);
        const action = select.value;
        if (!action) return;

        // Reset thanh chọn về mặc định sau khi chọn
        select.value = '';

        if (action === 'view') {
            const b = currentBookings.find(x => x.id === id);
            if (b) openOperationalModal(b);
            return;
        }

        await handleTransition(id, action);
    });

    // Lắng nghe sự kiện click nếu có
    body.addEventListener('click', async event => {
        const btn = event.target.closest('[data-action]');
        if (!btn) return;
        event.preventDefault();

        const id = Number(btn.dataset.id);
        const action = btn.dataset.action;

        if (action === 'view') {
            const b = currentBookings.find(x => x.id === id);
            if (b) openOperationalModal(b);
            return;
        }

        await handleTransition(id, action);
    });

    // Lắng nghe sự kiện trong Modal
    if (modalEl) {
        modalEl.addEventListener('click', async event => {
            const btn = event.target.closest('[data-modal-action]');
            if (!btn) return;
            event.preventDefault();
            const id = Number(btn.dataset.id);
            const action = btn.dataset.modalAction;
            await handleTransition(id, action);
        });
    }

    // Tìm kiếm và lọc
    let timer;
    const searchEl = document.getElementById('searchBooking');
    if (searchEl) {
        searchEl.addEventListener('input', () => {
            sequence++;
            clearTimeout(timer);
            timer = setTimeout(load, 250);
        });
    }

    const filterEl = document.getElementById('filterStatus');
    if (filterEl) {
        filterEl.addEventListener('change', load);
    }

    const resetBtn = document.getElementById('btnResetBookingFilters');
    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (searchEl) searchEl.value = '';
            if (filterEl) filterEl.value = '';
            load();
        });
    }

    load();
});
