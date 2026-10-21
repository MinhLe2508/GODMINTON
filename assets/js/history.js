document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('bookingListContainer');
    async function load() {
        container.textContent = 'Đang tải lịch sử từ MySQL...';
        try {
            const bookings = await BookingAccount.request('api/bookings.php');
            if (!bookings.length) {container.innerHTML='<div class="card p-5 text-center"><h5>Bạn chưa có đơn đặt sân</h5><a href="courts.php">Tìm sân để đặt</a></div>';return;}
            container.innerHTML = bookings.map(b => `<div class="card border rounded-4 p-4 mb-3 shadow-sm">
                <div class="d-flex justify-content-between flex-wrap gap-2"><strong>${BookingAccount.escape(b.booking_code)}</strong>${BookingAccount.badge(b.booking_status)}</div>
                <h5 class="mt-3">${BookingAccount.escape(b.court_name)}</h5><p>${BookingAccount.escape(b.court_address)}</p>
                <p>${BookingAccount.escape(b.sub_court_name)} · ${BookingAccount.escape(b.booking_date)} · ${BookingAccount.escape(b.time_range)}</p>
                <p>Tổng tiền: <strong>${BookingAccount.money(b.total_amount)}</strong> · Cọc mô phỏng: ${BookingAccount.money(b.deposit_amount)}</p>
                <p>Phương thức: ${BookingAccount.escape(b.payment_method || 'Chưa lưu trong bản cũ')}<br>Ghi chú: ${BookingAccount.escape(b.notes || '—')}</p>
                <div>${b.can_cancel ? `<button class="btn btn-outline-danger rounded-pill" data-cancel="${b.id}">Hủy đơn</button>` : (b.can_rebook === false ? '<span class="small text-muted">Sân đã ngừng nhận đặt</span>' : `<a class="btn btn-outline-secondary rounded-pill" href="booking.php?court_id=${Number(b.rebook_court_id || b.court_id)}">Đặt lại</a>`)}</div>
            </div>`).join('');
        } catch (e) {container.textContent=e.message;const retry=document.createElement('button');retry.textContent='Thử lại';retry.className='btn btn-outline-secondary m-2';retry.onclick=load;container.append(retry);}
    }
    container.addEventListener('click', async event => {
        const button=event.target.closest('[data-cancel]');if(!button)return;
        if (!await BookingAccount.confirm('Hủy đơn trước giờ bắt đầu? Việc hoàn tiền cọc cần chủ sân xử lý.'))return;
        button.disabled=true;
        try {await BookingAccount.request('api/cancel.php',{method:'POST',body:JSON.stringify({booking_id:Number(button.dataset.cancel)})});await load();await BookingAccount.notify('Đã hủy đơn và mở lại khung giờ.');}
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
