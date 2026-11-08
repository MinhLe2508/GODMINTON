document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('bookingListContainer');
    let currentBookings = [];
    async function load() {
        container.textContent = 'Đang tải lịch sử từ MySQL...';
        try {
            const bookings = await BookingAccount.request('api/bookings.php');
            currentBookings = Array.isArray(bookings) ? bookings : [];
            if (!currentBookings.length) {container.innerHTML='<div class="card p-5 text-center"><h5>Bạn chưa có đơn đặt sân</h5><a href="courts.php">Tìm sân để đặt</a></div>';return;}
            container.innerHTML = currentBookings.map(b => {
                const depositStr = BookingAccount.money(b.deposit_amount);
                let statusAlert = '';
                if (b.booking_status === 'checked_in') {
                    statusAlert = `<div class="alert alert-success py-2 px-3 small my-2"><i class="fa-solid fa-person-running me-1"></i>Bạn đã nhận sân và đang thi đấu tại <strong>${BookingAccount.escape(b.sub_court_name)}</strong>. Vui lòng thanh toán số tiền còn lại (${BookingAccount.money(b.remaining_amount)}) tại quầy khi trả sân.</div>`;
                } else if (b.booking_status === 'no_show') {
                    if (b.is_deposit_refunded) {
                        if (b.payment_status === 'refunded') {
                            statusAlert = `<div class="alert alert-success py-2 px-3 small my-2"><i class="fa-solid fa-circle-check me-1"></i><strong>Đã hoàn cọc 100% (${depositStr}):</strong> Chủ sân đã hoàn trả tiền cọc theo đặc quyền Grace Pass Hội viên.</div>`;
                        } else {
                            statusAlert = `<div class="alert alert-info py-2 px-3 small my-2"><i class="fa-solid fa-gift me-1"></i><strong>Đặc quyền Hội viên (Chờ hoàn cọc ${depositStr}):</strong> Đơn đã kích hoạt Grace Pass cứu cọc. Chủ sân đang xử lý chuyển khoản hoàn tiền cho bạn.</div>`;
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
                            statusAlert = `<div class="alert alert-info py-2 px-3 small my-2"><i class="fa-solid fa-clock-rotate-left text-teal me-1"></i><strong>Chờ hoàn cọc 100% (${depositStr}):</strong> Đơn hủy hợp lệ được bảo đảm hoàn cọc. Chủ sân sẽ liên hệ SĐT của bạn để chuyển khoản hoàn lại.${b.refund_note ? `<div class="mt-1 text-muted">${BookingAccount.escape(b.refund_note)}</div>` : ''}</div>`;
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

                return `<div class="card border rounded-4 p-4 mb-3 shadow-sm ${b.booking_status === 'checked_in' ? 'border-success' : ''}">
                    <div class="d-flex justify-content-between flex-wrap gap-2"><strong>${BookingAccount.escape(b.booking_code)}</strong>${BookingAccount.badge(b.booking_status)}</div>
                    <h5 class="mt-3 mb-1">${BookingAccount.escape(b.court_name)}</h5>
                    <p class="text-muted small mb-2">${BookingAccount.escape(b.court_address)}</p>
                    <p class="mb-2"><strong class="text-teal">${BookingAccount.escape(b.sub_court_name)}</strong> · ${BookingAccount.escape(b.booking_date)} · <span class="fw-bold">${BookingAccount.escape(b.time_range)}</span></p>
                    <p class="mb-2">Tổng tiền: <strong>${BookingAccount.money(b.total_amount)}</strong> · Tiền cọc: <strong class="text-teal">${depositStr}</strong> ${b.remaining_amount > 0 && !['completed','cancelled','no_show'].includes(b.booking_status) ? `· Còn thu tại quầy: <strong class="text-danger">${BookingAccount.money(b.remaining_amount)}</strong>` : ''}</p>
                    <p class="small text-muted mb-2">Phương thức: ${BookingAccount.escape(b.payment_method || 'VietQR')}<br>Ghi chú: ${BookingAccount.escape(b.notes || '—')}</p>
                    ${statusAlert}
                    <div class="mt-2">${b.can_cancel ? `<button class="btn btn-outline-danger btn-sm rounded-pill" data-cancel="${b.id}"><i class="fa-solid fa-ban me-1"></i>${cancelBtnLabel}</button>` : (b.can_rebook === false ? '<span class="small text-muted">Sân đã ngừng nhận đặt</span>' : `<a class="btn btn-outline-secondary btn-sm rounded-pill" href="booking.php?court_id=${Number(b.rebook_court_id || b.court_id)}"><i class="fa-solid fa-rotate-right me-1"></i>Đặt lại</a>`)}</div>
                </div>`;
            }).join('');
        } catch (e) {container.textContent=e.message;const retry=document.createElement('button');retry.textContent='Thử lại';retry.className='btn btn-outline-secondary m-2';retry.onclick=load;container.append(retry);}
    }
    container.addEventListener('click', async event => {
        const button=event.target.closest('[data-cancel]');if(!button)return;
        const bookingId = Number(button.dataset.cancel);
        const b = currentBookings.find(x => x.id === bookingId);
        const depositStr = b ? BookingAccount.money(b.deposit_amount) : '';
        let confirmMsg = 'Xác nhận hủy đơn đặt sân này?';
        if (b) {
            if (b.cancel_policy_type === 'unpaid') {
                confirmMsg = 'Xác nhận hủy đơn chờ thanh toán này? Khung giờ đang giữ sẽ được mở lại ngay.';
            } else if (b.cancel_policy_type === 'member_free') {
                confirmMsg = `Xác nhận hủy khẩn cấp đơn ${b.booking_code}? Theo đặc quyền Hội viên, bạn được hoàn 100% tiền cọc (${depositStr}) và khung giờ sẽ được mở lại ngay.`;
            } else if (b.cancel_policy_type === 'grace_15m') {
                confirmMsg = `Bạn đang hủy đơn ${b.booking_code} trong 15 phút ân hạn sau khi đặt. Bạn được hoàn 100% tiền cọc (${depositStr}). Xác nhận hủy đơn?`;
            } else if (b.cancel_policy_type === 'early_6h') {
                confirmMsg = `Đơn ${b.booking_code} còn cách giờ thi đấu trên 6 tiếng. Bạn được hoàn 100% tiền cọc (${depositStr}). Xác nhận hủy đơn?`;
            } else {
                confirmMsg = `CẢNH BÁO MẤT CỌC: Đơn ${b.booking_code} chỉ còn dưới 6 tiếng trước giờ thi đấu (và đã qua 15 phút ân hạn sau khi đặt). Nếu hủy lúc này, bạn sẽ KHÔNG được hoàn lại tiền cọc (${depositStr}). Bạn vẫn chắc chắn muốn hủy?`;
            }
        }
        if (!await BookingAccount.confirm(confirmMsg)) return;
        button.disabled=true;
        try {
            const res = await BookingAccount.request('api/cancel.php',{method:'POST',body:JSON.stringify({booking_id:Number(button.dataset.cancel)})});
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
