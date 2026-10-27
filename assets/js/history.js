document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('bookingListContainer');
    async function load() {
        container.textContent = 'Đang tải lịch sử từ MySQL...';
        try {
            const bookings = await BookingAccount.request('api/bookings.php');
            if (!bookings.length) {container.innerHTML='<div class="card p-5 text-center"><h5>Bạn chưa có đơn đặt sân</h5><a href="courts.php">Tìm sân để đặt</a></div>';return;}
            container.innerHTML = bookings.map(b => {
                let statusAlert = '';
                if (b.booking_status === 'checked_in') {
                    statusAlert = `<div class="alert alert-success py-2 px-3 small my-2"><i class="fa-solid fa-person-running me-1"></i>Bạn đã nhận sân và đang thi đấu tại <strong>${BookingAccount.escape(b.sub_court_name)}</strong>. Vui lòng thanh toán số tiền còn lại (${BookingAccount.money(b.remaining_amount)}) tại quầy khi trả sân.</div>`;
                } else if (b.booking_status === 'no_show') {
                    if (b.is_deposit_refunded) {
                        statusAlert = `<div class="alert alert-success py-2 px-3 small my-2"><i class="fa-solid fa-gift me-1"></i><strong>Đặc quyền Hội viên:</strong> Đơn bùng sân đã được kích hoạt Grace Pass (Cứu cọc). Tiền cọc được hoàn trả/bảo lưu theo chính sách Hội viên.</div>`;
                    } else {
                        statusAlert = `<div class="alert alert-warning py-2 px-3 small my-2"><i class="fa-solid fa-user-xmark me-1"></i>Đơn đặt ghi nhận vắng mặt. Tiền cọc đã được giữ lại theo quy định sân.</div>`;
                    }
                } else if (b.booking_status === 'cancelled') {
                    if (b.is_deposit_refunded) {
                        statusAlert = `<div class="alert alert-info py-2 px-3 small my-2"><i class="fa-solid fa-circle-check text-teal me-1"></i><strong>Đặc quyền Hội viên:</strong> Đã hủy đơn khẩn cấp thành công và được bảo đảm hoàn 100% tiền cọc.</div>`;
                    } else {
                        statusAlert = `<div class="alert alert-light border py-2 px-3 small my-2 text-muted"><i class="fa-solid fa-ban text-secondary me-1"></i>Đơn đặt sân đã được hủy.</div>`;
                    }
                } else if (b.booking_status === 'completed') {
                    statusAlert = `<div class="alert alert-light border py-2 px-3 small my-2 text-muted"><i class="fa-solid fa-circle-check text-success me-1"></i>Lượt chơi đã kết thúc thành công. Cảm ơn bạn đã thi đấu tại GODMINTON!</div>`;
                }

                return `<div class="card border rounded-4 p-4 mb-3 shadow-sm ${b.booking_status === 'checked_in' ? 'border-success' : ''}">
                    <div class="d-flex justify-content-between flex-wrap gap-2"><strong>${BookingAccount.escape(b.booking_code)}</strong>${BookingAccount.badge(b.booking_status)}</div>
                    <h5 class="mt-3 mb-1">${BookingAccount.escape(b.court_name)}</h5>
                    <p class="text-muted small mb-2">${BookingAccount.escape(b.court_address)}</p>
                    <p class="mb-2"><strong class="text-teal">${BookingAccount.escape(b.sub_court_name)}</strong> · ${BookingAccount.escape(b.booking_date)} · <span class="fw-bold">${BookingAccount.escape(b.time_range)}</span></p>
                    <p class="mb-2">Tổng tiền: <strong>${BookingAccount.money(b.total_amount)}</strong> · Đã cọc: <strong class="text-teal">${BookingAccount.money(b.deposit_amount)}</strong> ${b.remaining_amount > 0 && b.booking_status !== 'completed' ? `· Còn thu tại quầy: <strong class="text-danger">${BookingAccount.money(b.remaining_amount)}</strong>` : ''}</p>
                    <p class="small text-muted mb-2">Phương thức: ${BookingAccount.escape(b.payment_method || 'VietQR')}<br>Ghi chú: ${BookingAccount.escape(b.notes || '—')}</p>
                    ${statusAlert}
                    <div class="mt-2">${b.can_cancel ? `<button class="btn btn-outline-danger btn-sm rounded-pill" data-cancel="${b.id}" data-is-member="${b.is_member ? 1 : 0}"><i class="fa-solid fa-ban me-1"></i>${b.is_member ? 'Hủy khẩn cấp (Hội viên)' : 'Hủy đơn'}</button>` : (b.can_rebook === false ? '<span class="small text-muted">Sân đã ngừng nhận đặt</span>' : `<a class="btn btn-outline-secondary btn-sm rounded-pill" href="booking.php?court_id=${Number(b.rebook_court_id || b.court_id)}"><i class="fa-solid fa-rotate-right me-1"></i>Đặt lại</a>`)}</div>
                </div>`;
            }).join('');
        } catch (e) {container.textContent=e.message;const retry=document.createElement('button');retry.textContent='Thử lại';retry.className='btn btn-outline-secondary m-2';retry.onclick=load;container.append(retry);}
    }
    container.addEventListener('click', async event => {
        const button=event.target.closest('[data-cancel]');if(!button)return;
        const isMem = button.dataset.isMember === '1';
        const confirmMsg = isMem 
            ? 'Xác nhận hủy khẩn cấp đơn đặt sân này? Theo đặc quyền Hội viên, bạn sẽ được hoàn 100% tiền cọc và khung giờ sẽ được mở lại ngay.'
            : 'Xác nhận hủy đơn trước giờ bắt đầu? Việc xử lý hoàn tiền cọc sẽ theo quy định của sân.';
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
