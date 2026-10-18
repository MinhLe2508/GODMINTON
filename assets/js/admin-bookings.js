document.addEventListener('DOMContentLoaded', async () => {
    const body=document.getElementById('bookingsTableBody');
    const empty=document.getElementById('bookingsEmpty');
    try {
        const {user}=await BookingAccount.identity();
        if(!user||!['owner','admin'].includes(user.role))throw new Error('Bạn không có quyền quản lý đơn.');
        window.__GODMINTON_USER__ = user;
        AdminApp.renderShell('bookings', 'Quản lý đơn đặt', 'Kênh chủ sân / Quản lý đơn đặt');
    }catch(e){empty.textContent=e.message;empty.style.display='block';return;}
    let sequence=0;
    async function load() {
        const own=++sequence;const params=new URLSearchParams({q:document.getElementById('searchBooking').value.trim(),status:document.getElementById('filterStatus').value});
        empty.style.display='block';empty.textContent='Đang tải dữ liệu...';
        try {
            const bookings=await BookingAccount.request('api/admin/bookings.php?'+params);if(own!==sequence)return;
            empty.style.display=bookings.length?'none':'block';empty.textContent='Không có đơn phù hợp.';
            body.innerHTML=bookings.map(b=>`<tr><td>${BookingAccount.escape(b.booking_code)}</td><td>${BookingAccount.escape(b.court_name)}<div class="small">${BookingAccount.escape(b.sub_court_name)}</div></td><td>${BookingAccount.escape(b.user_name)}<div class="small">${BookingAccount.escape(b.user_phone)}</div></td><td>${BookingAccount.escape(b.booking_date)}<div class="small">${BookingAccount.escape(b.time_range)}</div></td><td>${BookingAccount.money(b.total_amount)}</td><td>${BookingAccount.badge(b.booking_status)}</td><td>${b.can_complete?`<button class="btn btn-sm btn-outline-primary" data-id="${b.id}" data-status="completed">Hoàn thành</button>`:''} ${b.can_cancel?`<button class="btn btn-sm btn-outline-danger" data-id="${b.id}" data-status="cancelled">Hủy</button>`:''}</td></tr>`).join('');
        }catch(e){if(own!==sequence)return;body.innerHTML='';empty.textContent=e.message;}
    }
    let timer;document.getElementById('searchBooking').addEventListener('input',()=>{sequence++;clearTimeout(timer);timer=setTimeout(load,250);});document.getElementById('filterStatus').addEventListener('change',load);
    body.addEventListener('click',async event=>{const b=event.target.closest('[data-id]');if(!b)return;if(!await BookingAccount.confirm(b.dataset.status==='completed'?'Đánh dấu đơn hoàn thành?':'Hủy đơn và mở lại khung giờ? Hoàn cọc cần xử lý riêng.'))return;b.disabled=true;try{await BookingAccount.request('api/admin/bookings.php',{method:'PATCH',body:JSON.stringify({booking_id:Number(b.dataset.id),status:b.dataset.status})});await load();await BookingAccount.notify('Đã cập nhật đơn trong MySQL.');}catch(e){await BookingAccount.notify(e.message,true);}finally{b.disabled=false;}});
    load();
});
