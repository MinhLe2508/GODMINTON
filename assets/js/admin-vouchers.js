/**
 * assets/js/admin-vouchers.js
 * Quản lý mã giảm giá trên giao diện Kênh Quản Trị / Kênh Chủ Sân.
 */

document.addEventListener('DOMContentLoaded', async () => {
    const tableBody = document.getElementById('vouchersTableBody');
    const emptyEl   = document.getElementById('vouchersEmpty');
    const modalEl   = document.getElementById('voucherModal');
    const modal     = modalEl ? new bootstrap.Modal(modalEl) : null;
    const form      = document.getElementById('voucherForm');

    let currentUser = null;
    let cachedVouchers = [];
    let myCourts = [];

    try {
        const { user } = await BookingAccount.identity();
        if (!user || !['owner', 'admin'].includes(user.role)) {
            throw new Error('Bạn không có quyền truy cập trang này.');
        }
        currentUser = user;
        window.__GODMINTON_USER__ = user;
        AdminApp.renderShell(
            'vouchers',
            'Quản lý mã giảm giá',
            (user.role === 'admin' ? 'Quản trị hệ thống' : 'Kênh chủ sân') + ' / Quản lý mã giảm giá'
        );
    } catch (e) {
        if (emptyEl) {
            emptyEl.textContent = e.message;
            emptyEl.style.display = 'block';
        }
        return;
    }

    // Nạp danh sách sân cho select box
    try {
        const res = await AdminApp.fetchCourts();
        myCourts = res.courts || [];
        const courtSelect = document.getElementById('formCourtId');
        if (courtSelect) {
            courtSelect.innerHTML = `<option value="">Tất cả sân ${currentUser.role === 'owner' ? 'do tôi quản lý' : 'trên hệ thống'}</option>`;
            myCourts.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id;
                opt.textContent = `${c.name} (${c.district || c.address})`;
                courtSelect.appendChild(opt);
            });
        }
    } catch (e) {
        console.warn('Không thể nạp danh sách sân:', e);
    }

    // Format ngày giờ hiển thị
    function formatDateTime(dtStr) {
        if (!dtStr) return '--';
        const d = new Date(dtStr.replace(' ', 'T'));
        if (isNaN(d.getTime())) return dtStr;
        const pad = n => String(n).padStart(2, '0');
        return `${pad(d.getDate())}/${pad(d.getMonth() + 1)}/${d.getFullYear()} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    // Nạp danh sách voucher
    async function loadVouchers() {
        const q = document.getElementById('searchVoucher')?.value.trim() || '';
        const status = document.getElementById('filterStatus')?.value || '';
        const params = new URLSearchParams({ q, status });

        if (emptyEl) {
            emptyEl.style.display = 'block';
            emptyEl.textContent = 'Đang tải dữ liệu...';
        }

        try {
            const data = await BookingAccount.request('api/admin/vouchers.php?' + params.toString());
            cachedVouchers = Array.isArray(data) ? data : [];

            if (cachedVouchers.length === 0) {
                if (tableBody) tableBody.innerHTML = '';
                if (emptyEl) {
                    emptyEl.style.display = 'block';
                    emptyEl.textContent = 'Không có mã giảm giá nào phù hợp.';
                }
                return;
            }

            if (emptyEl) emptyEl.style.display = 'none';
            renderTable(cachedVouchers);
        } catch (e) {
            if (tableBody) tableBody.innerHTML = '';
            if (emptyEl) {
                emptyEl.style.display = 'block';
                emptyEl.textContent = e.message || 'Lỗi tải danh sách mã.';
            }
        }
    }

    function renderTable(list) {
        if (!tableBody) return;
        const isAdmin = currentUser.role === 'admin';

        tableBody.innerHTML = list.map(v => {
            const isUsed = v.used_count > 0;
            const statusBadge = v.status === 'active'
                ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Đang bật</span>'
                : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Đã tắt</span>';

            const fundedBadge = v.funded_by === 'platform'
                ? '<span class="badge bg-primary-subtle text-primary border border-primary-subtle">Nền tảng</span>'
                : '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">Chủ sân</span>';

            const usageText = v.usage_limit
                ? `<strong>${v.used_count}</strong> / ${v.usage_limit}`
                : `<strong>${v.used_count}</strong> / ∞`;

            const maxDiscText = v.max_discount ? BookingAccount.money(v.max_discount) : 'Không giới hạn';
            const minOrderText = v.min_order > 0 ? BookingAccount.money(v.min_order) : '0 đ';

            const extraCols = isAdmin ? `
                <td><small class="text-muted">${BookingAccount.escape(v.creator_name)}</small></td>
                <td>${fundedBadge}</td>
            ` : '';

            const toggleBtnText = v.status === 'active' ? 'Tắt' : 'Bật';
            const toggleBtnClass = v.status === 'active' ? 'btn-outline-warning' : 'btn-outline-success';

            return `
                <tr>
                    <td>
                        <strong class="text-teal text-uppercase">${BookingAccount.escape(v.code)}</strong>
                        ${v.first_booking_only ? '<span class="badge bg-info-subtle text-info d-block mt-1" style="font-size:0.65rem;">Đơn đầu tiên</span>' : ''}
                    </td>
                    <td><span class="fw-bold">${v.discount_percent}%</span></td>
                    <td>${maxDiscText}</td>
                    <td>${minOrderText}</td>
                    <td>
                        <div class="small text-nowrap"><i class="fa-regular fa-clock me-1 text-muted"></i>${formatDateTime(v.start_at)}</div>
                        <div class="small text-nowrap text-muted"><i class="fa-solid fa-hourglass-end me-1 text-muted"></i>${formatDateTime(v.end_at)}</div>
                    </td>
                    <td>${usageText}</td>
                    <td><small class="text-truncate d-inline-block" style="max-width:140px;">${BookingAccount.escape(v.court_name)}</small></td>
                    ${extraCols}
                    <td>${statusBadge}</td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm ${toggleBtnClass} me-1 btn-toggle-status" data-id="${v.id}" data-status="${v.status}">
                            ${toggleBtnText}
                        </button>
                        <button class="btn btn-sm btn-outline-primary me-1 btn-edit-voucher" data-id="${v.id}" title="Chỉnh sửa">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger btn-delete-voucher" data-id="${v.id}" data-used="${v.used_count}" title="Xóa mã">
                            <i class="fa-regular fa-trash-can"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Tìm kiếm & lọc
    let searchTimer;
    document.getElementById('searchVoucher')?.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(loadVouchers, 250);
    });
    document.getElementById('filterStatus')?.addEventListener('change', loadVouchers);

    // Mở modal tạo mới
    document.getElementById('btnAddVoucher')?.addEventListener('click', () => {
        if (!form) return;
        form.reset();
        document.getElementById('voucherId').value = '';
        document.getElementById('voucherModalLabel').textContent = 'Tạo mã giảm giá mới';
        document.getElementById('formCode').disabled = false;
        document.getElementById('formPercent').disabled = false;
        document.getElementById('formMaxDiscount').disabled = false;
        document.getElementById('formCourtId').disabled = false;

        // Điền ngày mặc định: bắt đầu từ hiện tại, kết thúc sau 30 ngày
        const now = new Date();
        const nextMonth = new Date(now.getTime() + 30 * 24 * 3600 * 1000);
        const toLocalISO = d => new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);

        document.getElementById('formStartAt').value = toLocalISO(now);
        document.getElementById('formEndAt').value = toLocalISO(nextMonth);
        modal?.show();
    });

    // Mở modal chỉnh sửa
    tableBody?.addEventListener('click', (e) => {
        const editBtn = e.target.closest('.btn-edit-voucher');
        if (editBtn) {
            const id = parseInt(editBtn.dataset.id);
            const v = cachedVouchers.find(item => item.id === id);
            if (!v) return;

            form.reset();
            document.getElementById('voucherId').value = v.id;
            document.getElementById('voucherModalLabel').textContent = `Chỉnh sửa mã ${v.code}`;

            document.getElementById('formCode').value = v.code;
            document.getElementById('formPercent').value = v.discount_percent;
            document.getElementById('formMaxDiscount').value = v.max_discount || '';
            document.getElementById('formMinOrder').value = v.min_order || 0;
            document.getElementById('formUsageLimit').value = v.usage_limit || '';
            document.getElementById('formPerUserLimit').value = v.per_user_limit || 1;
            document.getElementById('formCourtId').value = v.court_id || '';

            const firstCheck = document.getElementById('formFirstBookingOnly');
            if (firstCheck) firstCheck.checked = !!v.first_booking_only;

            const toLocalISO = dtStr => {
                const d = new Date(dtStr.replace(' ', 'T'));
                return new Date(d.getTime() - d.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
            };
            document.getElementById('formStartAt').value = toLocalISO(v.start_at);
            document.getElementById('formEndAt').value = toLocalISO(v.end_at);

            // Nếu mã đã có lượt dùng: KHÔNG cho sửa %, max_discount, phạm vi; chỉ cho gia hạn ngày kết thúc
            const isUsed = v.used_count > 0;
            document.getElementById('formCode').disabled = true;
            document.getElementById('formPercent').disabled = isUsed;
            document.getElementById('formMaxDiscount').disabled = isUsed;
            document.getElementById('formCourtId').disabled = isUsed;

            modal?.show();
        }
    });

    // Xử lý Bật / Tắt trạng thái
    tableBody?.addEventListener('click', async (e) => {
        const toggleBtn = e.target.closest('.btn-toggle-status');
        if (!toggleBtn) return;

        const id = parseInt(toggleBtn.dataset.id);
        const currentStatus = toggleBtn.dataset.status;
        const targetStatus = currentStatus === 'active' ? 'disabled' : 'active';
        const actionLabel = targetStatus === 'active' ? 'kích hoạt lại' : 'tạm tắt';

        const confirmed = await BookingAccount.confirm(`Bạn có chắc chắn muốn ${actionLabel} mã giảm giá này?`);
        if (!confirmed) return;

        toggleBtn.disabled = true;
        try {
            await BookingAccount.request('api/admin/vouchers.php', {
                method: 'PATCH',
                body: JSON.stringify({ voucher_id: id, status: targetStatus })
            });
            await BookingAccount.notify(`Đã ${actionLabel} mã giảm giá thành công.`);
            await loadVouchers();
        } catch (err) {
            await BookingAccount.notify(err.message, true);
        } finally {
            toggleBtn.disabled = false;
        }
    });

    // Xử lý Xóa mã
    tableBody?.addEventListener('click', async (e) => {
        const delBtn = e.target.closest('.btn-delete-voucher');
        if (!delBtn) return;

        const id = parseInt(delBtn.dataset.id);
        const used = parseInt(delBtn.dataset.used || '0');

        if (used > 0) {
            await BookingAccount.notify('Mã này đã có lượt sử dụng trong lịch sử đặt sân, không thể xóa. Bạn có thể chọn Tắt mã.', true);
            return;
        }

        const confirmed = await BookingAccount.confirm('Bạn có chắc chắn muốn xóa vĩnh viễn mã giảm giá chưa sử dụng này?');
        if (!confirmed) return;

        delBtn.disabled = true;
        try {
            await BookingAccount.request(`api/admin/vouchers.php?id=${id}`, {
                method: 'DELETE'
            });
            await BookingAccount.notify('Đã xóa mã giảm giá thành công.');
            await loadVouchers();
        } catch (err) {
            await BookingAccount.notify(err.message, true);
        } finally {
            delBtn.disabled = false;
        }
    });

    // Submit form Tạo / Sửa
    form?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const idVal = document.getElementById('voucherId').value;
        const isEdit = idVal !== '';

        const code = document.getElementById('formCode').value.trim().toUpperCase();
        const percent = parseInt(document.getElementById('formPercent').value);
        const maxDiscountVal = document.getElementById('formMaxDiscount').value;
        const minOrder = parseInt(document.getElementById('formMinOrder').value) || 0;
        const startAt = document.getElementById('formStartAt').value;
        const endAt = document.getElementById('formEndAt').value;
        const usageLimitVal = document.getElementById('formUsageLimit').value;
        const perUserLimit = parseInt(document.getElementById('formPerUserLimit').value) || 1;
        const courtIdVal = document.getElementById('formCourtId').value;
        const firstBookingOnly = document.getElementById('formFirstBookingOnly')?.checked || false;

        if (!code || !startAt || !endAt || isNaN(percent)) {
            await BookingAccount.notify('Vui lòng điền đầy đủ các trường bắt buộc (*).', true);
            return;
        }

        if (new Date(endAt) <= new Date(startAt)) {
            await BookingAccount.notify('Thời gian kết thúc phải sau thời gian bắt đầu.', true);
            return;
        }

        if (currentUser.role === 'admin' && (!maxDiscountVal || parseInt(maxDiscountVal) <= 0)) {
            await BookingAccount.notify('Mã do Quản trị viên tạo bắt buộc phải có mức giảm tối đa (VNĐ).', true);
            return;
        }

        if (currentUser.role === 'owner' && percent > 50) {
            await BookingAccount.notify('Chủ sân chỉ được thiết lập mức giảm giá tối đa 50%.', true);
            return;
        }

        const payload = {
            code,
            discount_percent: percent,
            max_discount: maxDiscountVal ? parseInt(maxDiscountVal) : null,
            min_order: minOrder,
            start_at: startAt.replace('T', ' ') + ':00',
            end_at: endAt.replace('T', ' ') + ':00',
            usage_limit: usageLimitVal ? parseInt(usageLimitVal) : null,
            per_user_limit: perUserLimit,
            court_id: courtIdVal ? parseInt(courtIdVal) : null,
            first_booking_only: firstBookingOnly ? 1 : 0
        };

        const btnSave = document.getElementById('btnSaveVoucher');
        const spinner = document.getElementById('btnSaveVoucherSpinner');
        const textEl  = document.getElementById('btnSaveVoucherText');

        if (btnSave) btnSave.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';
        if (textEl) textEl.textContent = 'Đang lưu...';

        try {
            if (isEdit) {
                payload.voucher_id = parseInt(idVal);
                await BookingAccount.request('api/admin/vouchers.php', {
                    method: 'PATCH',
                    body: JSON.stringify(payload)
                });
                await BookingAccount.notify('Cập nhật mã giảm giá thành công.');
            } else {
                await BookingAccount.request('api/admin/vouchers.php', {
                    method: 'POST',
                    body: JSON.stringify(payload)
                });
                await BookingAccount.notify('Tạo mã giảm giá mới thành công.');
            }
            modal?.hide();
            await loadVouchers();
        } catch (err) {
            await BookingAccount.notify(err.message, true);
        } finally {
            if (btnSave) btnSave.disabled = false;
            if (spinner) spinner.style.display = 'none';
            if (textEl) textEl.textContent = 'Lưu mã';
        }
    });

    loadVouchers();
});
