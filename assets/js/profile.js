let savedProfile = null;
function resetProfileForm() {
    if(!savedProfile)return;
    document.getElementById('inpFullName').value=savedProfile.name;
    document.getElementById('inpEmail').value=savedProfile.email;
    document.getElementById('inpPhone').value=savedProfile.phone || '';
}
function showProfile(user) {
    savedProfile=user;resetProfileForm();
    const memberSince=document.getElementById('profileMemberSince');
    if(memberSince) memberSince.textContent=user.created_at ? 'Thành viên từ '+user.created_at : 'Chưa cập nhật ngày tham gia';
    document.getElementById('profileHeaderName').textContent=user.name;
    document.getElementById('profileHeaderEmail').textContent=user.email;
    document.getElementById('profileAvatarInitial').textContent=user.name.charAt(0).toUpperCase();
    const role=document.getElementById('profileRoleLabel');if(role)role.textContent={customer:'Khách hàng',owner:'Chủ sân',admin:'Quản trị viên'}[user.role]||user.role;
}
document.addEventListener('DOMContentLoaded', async () => {
    const update=document.getElementById('formUpdateProfile');
    const password=document.getElementById('formChangePassword');
    const save=update.querySelector('[type="submit"]');save.disabled=true;
    try {
        const result=await BookingAccount.request('api/profile.php');showProfile(result.user);save.disabled=false;
        const bookings=await BookingAccount.request('api/bookings.php');document.getElementById('statBookingsCount').textContent=bookings.length;
        const area=document.getElementById('profileBookingsList');
        area.innerHTML=bookings.length?bookings.slice(0,6).map(b=>`<div class="card p-3">${BookingAccount.badge(b.booking_status)}<h6 class="mt-2">${BookingAccount.escape(b.court_name)}</h6><p>${BookingAccount.escape(b.booking_code)} · ${BookingAccount.escape(b.booking_date)} · ${BookingAccount.escape(b.time_range)}</p><strong>${BookingAccount.money(b.total_amount)}</strong><a href="history.php">Xem lịch sử đầy đủ</a></div>`).join(''):'<p>Bạn chưa có đơn đặt sân.</p>';
    }catch(e){await BookingAccount.notify(e.message,true);}
    update.addEventListener('submit',async event=>{
        event.preventDefault();save.disabled=true;
        try {const result=await BookingAccount.request('api/profile.php',{method:'POST',body:JSON.stringify({name:document.getElementById('inpFullName').value.trim(),email:document.getElementById('inpEmail').value.trim(),phone:document.getElementById('inpPhone').value.trim()})});showProfile(result.user);await renderNavbarUser();await BookingAccount.notify('Hồ sơ đã lưu vào MySQL.');}
        catch(e){await BookingAccount.notify(e.message,true);}finally{save.disabled=false;}
    });
    password.addEventListener('submit',async event=>{
        event.preventDefault();const next=document.getElementById('newPass').value;
        if(next!==document.getElementById('confirmPass').value){await BookingAccount.notify('Mật khẩu xác nhận không khớp.',true);return;}
        const button=password.querySelector('[type="submit"]');button.disabled=true;
        try {await BookingAccount.request('api/auth/change-password.php',{method:'POST',body:JSON.stringify({old_password:document.getElementById('oldPass').value,new_password:next})});password.reset();await BookingAccount.notify('Đã đổi mật khẩu.');}
        catch(e){await BookingAccount.notify(e.message,true);}finally{button.disabled=false;}
    });
    document.getElementById('profileLogout')?.addEventListener('click',async()=>{try{await BookingAccount.logout();}catch(e){await BookingAccount.notify(e.message,true);}});
});

