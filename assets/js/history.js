document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('bookingListContainer');
    let currentBookings = [];

    const BANK_OPTIONS = [
        { code: 'MBBank', label: 'MBBank - Ngân hàng Quân Đội' },
        { code: 'Vietcombank', label: 'Vietcombank - Ngoại thương VN' },
        { code: 'Techcombank', label: 'Techcombank - Kỹ thương VN' },
        { code: 'ACB', label: 'ACB - Ngân hàng Á Châu' },
        { code: 'BIDV', label: 'BIDV - Đầu tư & Phát triển VN' },
        { code: 'VietinBank', label: 'VietinBank - Công thương VN' },
        { code: 'VPBank', label: 'VPBank - Việt Nam Thịnh Vượng' },
        { code: 'TPBank', label: 'TPBank - Tiên Phong' },
        { code: 'Sacombank', label: 'Sacombank - Sài Gòn Thương Tín' },
        { code: 'VIB', label: 'VIB - Quốc Tế' },
        { code: 'MSB', label: 'MSB - Hàng Hải VN' },
        { code: 'SHB', label: 'SHB - Sài Gòn - Hà Nội' },
        { code: 'HDBank', label: 'HDBank - Phát triển TP.HCM' },
        { code: 'OCB', label: 'OCB - Phương Đông' },
        { code: 'Agribank', label: 'Agribank - NN & PT Nông thôn' },
        { code: 'LPBank', label: 'LPBank - Lộc Phát VN' },
        { code: 'Eximbank', label: 'Eximbank - Xuất Nhập Khẩu' },
        { code: 'SeABank', label: 'SeABank - Đông Nam Á' }
    ];

    async function promptRefundBankModal(b, isUpdateOnly = false) {
        const depositStr = BookingAccount.money(b.deposit_amount);
        let policyDesc = `Đơn <strong>${BookingAccount.escape(b.booking_code)}</strong> đủ điều kiện được hoàn trả <strong>100% tiền cọc (${depositStr})</strong>.`;
        if (!isUpdateOnly) {
            if (b.cancel_policy_type === 'member_free') {
                policyDesc = `Đặc quyền Hội viên: Hủy khẩn cấp đơn <strong>${BookingAccount.escape(b.booking_code)}</strong> được hoàn <strong>100% tiền cọc (${depositStr})</strong>.`;
            } else if (b.cancel_policy_type === 'grace_15m') {
                policyDesc = `Hủy trong 15 phút ân hạn sau khi đặt: Đơn <strong>${BookingAccount.escape(b.booking_code)}</strong> được hoàn <strong>100% tiền cọc (${depositStr})</strong>.`;
            } else if (b.cancel_policy_type === 'early_6h') {
                policyDesc = `Hủy trước giờ thi đấu trên 6 tiếng: Đơn <strong>${BookingAccount.escape(b.booking_code)}</strong> được hoàn <strong>100% tiền cọc (${depositStr})</strong>.`;
            }
        }

        const curBank = b.refund_bank || 'MBBank';
        const curAcc = b.refund_account_number || '';
        const curName = b.refund_account_name || '';
        const optionsHtml = BANK_OPTIONS.map(o => `<option value="${o.code}" ${o.code === curBank ? 'selected' : ''}>${o.label}</option>`).join('');

        if (window.Swal) {
            const res = await Swal.fire({
                title: isUpdateOnly ? 'Tài khoản nhận hoàn cọc (SePay)' : 'Hủy đơn & Nhận hoàn cọc 100%',
                html: `
                    <div class="text-start">
                        <div class="alert alert-success py-2 px-3 small mb-3">${policyDesc}<br><span class="text-muted">Nhập tài khoản ngân hàng để hệ thống tạo mã VietQR cho chủ sân chuyển trả tự động qua SePay.</span></div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Ngân hàng nhận tiền *</label>
                            <select id="swalRefundBank" class="form-select form-select-sm">${optionsHtml}</select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold mb-1">Số tài khoản nhận hoàn cọc *</label>
                            <input id="swalRefundAccNo" type="text" class="form-control form-control-sm" placeholder="VD: 0987654321" value="${BookingAccount.escape(curAcc)}">
                        </div>
                        <div class="mb-1">
                            <label class="form-label small fw-bold mb-1">Tên chủ tài khoản</label>
                            <input id="swalRefundAccName" type="text" class="form-control form-control-sm text-uppercase" placeholder="VD: NGUYEN VAN A" value="${BookingAccount.escape(curName)}">
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: isUpdateOnly ? 'Lưu tài khoản nhận cọc' : 'Xác nhận hủy & Nhận cọc',
                cancelButtonText: 'Đóng',
                confirmButtonColor: '#0d9488',
                focusConfirm: false,
                preConfirm: () => {
                    const bank = document.getElementById('swalRefundBank')?.value?.trim() || '';
                    const acc = document.getElementById('swalRefundAccNo')?.value?.replace(/\s+/g, '') || '';
                    const name = document.getElementById('swalRefundAccName')?.value?.trim().toUpperCase() || '';
                    if (!bank || !/^[A-Za-z0-9]{5,25}$/.test(acc)) {
                        Swal.showValidationMessage('Vui lòng nhập số tài khoản ngân hàng hợp lệ (5–25 ký tự số/chữ).');
                        return false;
                    }
                    return { refund_bank: bank, refund_account_number: acc, refund_account_name: name };
                }
            });
            return res.isConfirmed ? res.value : null;
        }
        const ok = await BookingAccount.confirm(policyDesc.replace(/<[^>]+>/g, ''));
        return ok ? {} : null;
    }

    async function load() {
        container.textContent = 'Đang tải lịch sử từ MySQL...';
        try {
            const bookings = await BookingAccount.request('api/bookings.php');
            currentBookings = Array.isArray(bookings) ? bookings : [];
            if (!currentBookings.length) {container.innerHTML='<div class="card p-5 text-center"><h5>Bạn chưa có đơn đặt sân</h5><a href="courts.php">Tìm sân để đặt</a></div>';return;}
            container.innerHTML = currentBookings.map(b => {
                const depositStr = BookingAccount.money(b.deposit_amount);
                const refundBankLine = b.refund_account_number
                    ? `<div class="mt-1 small"><strong>TK nhận hoàn cọc:</strong> ${BookingAccount.escape(b.refund_bank)} · <span class="font-monospace fw-bold">${BookingAccount.escape(b.refund_account_number)}</span> ${b.refund_account_name ? `(${BookingAccount.escape(b.refund_account_name)})` : ''} · Mã đối soát SePay: <code>${BookingAccount.escape(b.refund_transfer_code || '')}</code></div>`
                    : `<div class="mt-1 small text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>Bạn chưa cung cấp số tài khoản nhận hoàn cọc. Vui lòng bấm <strong>"Nhập STK nhận hoàn cọc"</strong> bên dưới.</div>`;

                let statusAlert = '';
                if (b.booking_status === 'checked_in') {
                    statusAlert = `<div class="alert alert-success py-2 px-3 small my-2"><i class="fa-solid fa-person-running me-1"></i>Bạn đã nhận sân và đang thi đấu tại <strong>${BookingAccount.escape(b.sub_court_name)}</strong>. Vui lòng thanh toán số tiền còn lại (${BookingAccount.money(b.remaining_amount)}) tại quầy khi trả sân.</div>`;
                } else if (b.booking_status === 'no_show') {
                    if (b.is_deposit_refunded) {
                        if (b.payment_status === 'refunded') {
                            statusAlert = `<div class="alert alert-success py-2 px-3 small my-2"><i class="fa-solid fa-circle-check me-1"></i><strong>Đã hoàn cọc 100% (${depositStr}):</strong> Chủ sân đã hoàn trả tiền cọc theo đặc quyền Grace Pass Hội viên.${b.refund_note ? `<div class="mt-1 text-muted">${BookingAccount.escape(b.refund_note)}</div>` : ''}</div>`;
                        } else {
                            statusAlert = `<div class="alert alert-info py-2 px-3 small my-2"><i class="fa-solid fa-gift me-1"></i><strong>Đặc quyền Hội viên (Chờ hoàn cọc ${depositStr}):</strong> Đơn đã kích hoạt Grace Pass cứu cọc. Chủ sân đang xử lý chuyển khoản hoàn tiền qua SePay.${refundBankLine}</div>`;
                        }
                    } else {
                        statusAlert = `<div class="alert alert-warning py-2 px-3 small my-2"><i class="fa-solid fa-user-xmark me-1"></i>Đơn đặt ghi nhận vắng mặt. Tiền cọc (${depositStr}) được giữ lại theo quy định sân.</div>`;
                    }
                } else if (b.booking_status === 'cancelled') {
                    if (b.payment_status === 'unpaid') {
                        statusAlert = `<div class="alert alert-light border py-2 px-3 small my-2 text-muted"><i class="fa-solid fa-ban text-secondary me-1"></i>Đơn đặt sân đã hủy khi chưa thanh toán tiền cọc.</div>`;
                    } else if (b.is_deposit_refunded) {
                        if (b.payment_status === 'refunded') {
                            statusAlert = `<div class="alert alert-success py-2 px-3 small my-2"><i class="fa-solid fa-circle-check me-1"></i><strong>Đã hoàn cọc 100% (${depositStr}):</strong> Chủ sân đã chuyển khoản hoàn trả tiền cọc cho bạn.${b.refund_note ? `<div class="mt-1 text-muted">${BookingAccount.escape(b.refund_note)}</div>` : ''}</div>`;
                        } else {
                            statusAlert = `<div class="alert alert-info py-2 px-3 small my-2"><i class="fa-solid fa-clock-rotate-left text-teal me-1"></i><strong>Chờ hoàn cọc 100% (${depositStr}):</strong> Đơn hủy hợp lệ được bảo đảm hoàn cọc qua SePay.${b.refund_note ? `<div class="mt-1 text-muted">${BookingAccount.escape(b.refund_note)}</div>` : ''}${refundBankLine}</div>`;
                        }
                    } else {
                        statusAlert = `<div class="alert alert-warning py-2 px-3 small my-2"><i class="fa-solid fa-shield-halved me-1"></i><strong>Khách hủy sát giờ (Giữ cọc ${depositStr}):</strong> Do hủy dưới 6 tiếng trước giờ thi đấu (ngoài 15 phút ân hạn), tiền cọc được giữ lại bù rủi ro trống sân.</div>`;
                    }
                } else if (b.booking_status === 'completed') {
                    statusAlert = `<div class="alert alert-light border py-2 px-3 small my-2 text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i>Lượt chơi đã kết thúc thành công. Cảm ơn bạn đã thi đấu tại GODMINTON!</div>`;
                }

                const cancelBtnLabel = b.is_member
                    ? 'Hủy khẩn cấp (Hội viên)'
                    : (b.will_refund_deposit ? 'Hủy đơn (Hoàn cọc 100%)' : (b.payment_status === 'unpaid' ? 'Hủy đơn' : 'Hủy đơn (Mất cọc)'));

                const showUpdateBankBtn = ['cancelled', 'no_show'].includes(b.booking_status) && b.is_deposit_refunded && b.payment_status !== 'refunded' && b.deposit_amount > 0;

                return `<div class="card border rounded-4 p-4 mb-3 shadow-sm ${b.booking_status === 'checked_in' ? 'border-success' : ''}">
                    <div class="d-flex justify-content-between flex-wrap gap-2"><strong>${BookingAccount.escape(b.booking_code)}</strong>${BookingAccount.badge(b.booking_status)}</div>
                    <h5 class="mt-3 mb-1">${BookingAccount.escape(b.court_name)}</h5>
                    <p class="text-muted small mb-2">${BookingAccount.escape(b.court_address)}</p>
                    <p class="mb-2"><strong class="text-teal">${BookingAccount.escape(b.sub_court_name)}</strong> · ${BookingAccount.escape(b.booking_date)} · <span class="fw-bold">${BookingAccount.escape(b.time_range)}</span></p>
                    <p class="mb-2">Tổng tiền: <strong>${BookingAccount.money(b.total_amount)}</strong> · Tiền cọc: <strong class="text-teal">${depositStr}</strong> ${b.remaining_amount > 0 && !['completed','cancelled','no_show'].includes(b.booking_status) ? `· Còn thu tại quầy: <strong class="text-danger">${BookingAccount.money(b.remaining_amount)}</strong>` : ''}</p>
                    <p class="small text-muted mb-2">Phương thức: ${BookingAccount.escape(b.payment_method || 'VietQR')}<br>Ghi chú: ${BookingAccount.escape(b.notes || '—')}</p>
                    ${statusAlert}
                    <div class="mt-2 d-flex flex-wrap gap-2">
                        ${b.can_cancel ? `<button class="btn btn-outline-danger btn-sm rounded-pill" data-cancel="${b.id}"><i class="fa-solid fa-ban me-1"></i>${cancelBtnLabel}</button>` : (b.can_rebook === false ? '<span class="small text-muted">Sân đã ngừng nhận đặt</span>' : `<a class="btn btn-outline-secondary btn-sm rounded-pill" href="booking.php?court_id=${Number(b.rebook_court_id || b.court_id)}"><i class="fa-solid fa-rotate-right me-1"></i>Đặt lại</a>`)}
                        ${showUpdateBankBtn ? `<button class="btn btn-teal btn-sm rounded-pill text-white" style="background:#0d9488;" data-update-bank="${b.id}"><i class="fa-solid fa-building-columns me-1"></i>${b.refund_account_number ? 'Đổi STK nhận hoàn cọc' : 'Nhập STK nhận hoàn cọc'}</button>` : ''}
                    </div>
                </div>`;
            }).join('');
        } catch (e) {container.textContent=e.message;const retry=document.createElement('button');retry.textContent='Thử lại';retry.className='btn btn-outline-secondary m-2';retry.onclick=load;container.append(retry);}
    }
    container.addEventListener('click', async event => {
        const updateBankBtn = event.target.closest('[data-update-bank]');
        if (updateBankBtn) {
            const bookingId = Number(updateBankBtn.dataset.updateBank);
            const b = currentBookings.find(x => x.id === bookingId);
            if (!b) return;
            const bankPayload = await promptRefundBankModal(b, true);
            if (!bankPayload) return;
            updateBankBtn.disabled = true;
            try {
                const res = await BookingAccount.request('api/cancel.php', {
                    method: 'POST',
                    body: JSON.stringify({ booking_id: bookingId, action: 'save_refund_bank', ...bankPayload })
                });
                await load();
                await BookingAccount.notify(res?.message || 'Đã lưu tài khoản nhận hoàn cọc.');
            } catch (e) {
                await BookingAccount.notify(e.message, true);
            } finally {
                updateBankBtn.disabled = false;
            }
            return;
        }

        const button=event.target.closest('[data-cancel]');if(!button)return;
        const bookingId = Number(button.dataset.cancel);
        const b = currentBookings.find(x => x.id === bookingId);
        const depositStr = b ? BookingAccount.money(b.deposit_amount) : '';
        let extraPayload = {};

        if (b && b.will_refund_deposit) {
            const bankInfo = await promptRefundBankModal(b, false);
            if (!bankInfo) return;
            extraPayload = bankInfo;
        } else {
            let confirmMsg = 'Xác nhận hủy đơn đặt sân này?';
            if (b) {
                if (b.cancel_policy_type === 'unpaid') {
                    confirmMsg = 'Xác nhận hủy đơn chờ thanh toán này? Khung giờ đang giữ sẽ được mở lại ngay.';
                } else {
                    confirmMsg = `CẢNH BÁO MẤT CỌC: Đơn ${b.booking_code} chỉ còn dưới 6 tiếng trước giờ thi đấu (và đã qua 15 phút ân hạn sau khi đặt). Nếu hủy lúc này, bạn sẽ KHÔNG được hoàn lại tiền cọc (${depositStr}). Bạn vẫn chắc chắn muốn hủy?`;
                }
            }
            if (!await BookingAccount.confirm(confirmMsg)) return;
        }

        button.disabled=true;
        try {
            const res = await BookingAccount.request('api/cancel.php',{method:'POST',body:JSON.stringify({booking_id:bookingId, ...extraPayload})});
            await load();
            await BookingAccount.notify(res?.message || 'Đã hủy đơn và mở lại khung giờ.');
        }
        catch(e){await BookingAccount.notify(e.message,true);}finally{button.disabled=false;}
    });
    load();
    document.getElementById('historyLogout')?.addEventListener('click', async () => {
        try {
            await BookingAccount.logout();
        } catch (e) {
            await BookingAccount.notify(e.message, true);
        }
    });
    if (window.PushClient) {
        if (typeof PushClient.initBellButton === 'function') {
            PushClient.initBellButton();
        } else if (document.getElementById('pushNotificationContainer')) {
            PushClient.renderPushControls('pushNotificationContainer');
        }
    }
});
