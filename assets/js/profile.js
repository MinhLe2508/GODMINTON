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
        await loadMyVouchers();
        if (window.PushClient && typeof PushClient.initBellButton === 'function') {
            PushClient.initBellButton();
        }
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


document.getElementById('btnDeleteAccount')?.addEventListener('click',async()=>{
    const button=document.getElementById('btnDeleteAccount');
    if(!window.Swal){await BookingAccount.notify('Vui lòng tải lại trang để mở bước xác nhận.',true);return;}
    const reply=await Swal.fire({icon:'warning',titleText:'Yêu cầu xóa tài khoản?',
        text:'Nhập mật khẩu hiện tại. Chúng tôi sẽ gửi email xác nhận; tài khoản chỉ bị xóa khi bạn mở liên kết và xác nhận. Thao tác không thể hoàn tác.',
        input:'password',inputPlaceholder:'Nhập mật khẩu hiện tại',inputAttributes:{autocomplete:'current-password'},
        showCancelButton:true,confirmButtonText:'Gửi email xác nhận',cancelButtonText:'Giữ tài khoản',confirmButtonColor:'#b83240',
        showLoaderOnConfirm:true,allowOutsideClick:()=>!Swal.isLoading(),
        preConfirm:async password=>{
            if(!password){Swal.showValidationMessage('Vui lòng nhập mật khẩu hiện tại.');return false;}
            try{return await BookingAccount.request('api/auth/delete-account.php',{method:'POST',body:JSON.stringify({password,confirm_delete:true})});}
            catch(error){Swal.showValidationMessage(error.message);return false;}
        }});
    if(reply.isConfirmed)await Swal.fire({icon:'info',titleText:'Kiểm tra email của bạn',text:reply.value.message||'Đã gửi liên kết xác nhận. Liên kết có hiệu lực 15 phút.'});
});

async function loadMyVouchers() {
    const listEl = document.getElementById('profileVouchersList');
    const statEl = document.getElementById('statVouchersCount');
    if (!listEl) return;

    try {
        const vouchers = await BookingAccount.request('api/vouchers/my-vouchers.php');
        const usableCount = vouchers.filter(v => v.is_usable).length;
        if (statEl) statEl.textContent = usableCount;

        if (!vouchers.length) {
            listEl.innerHTML = `
                <div class="col-12 text-center py-5 text-muted">
                    <i class="fa-solid fa-ticket fs-1 d-block mb-3 text-secondary opacity-50"></i>
                    <p class="mb-1 fw-semibold">Hiện chưa có mã giảm giá nào khả dụng.</p>
                    <small>Các chương trình ưu đãi mới sẽ xuất hiện tại đây!</small>
                </div>
            `;
            return;
        }

        listEl.innerHTML = vouchers.map(v => {
            const maxText = v.max_discount_text ? `Tối đa ${v.max_discount_text}` : 'Không giới hạn mức giảm';
            const minText = v.min_order > 0 ? `Đơn từ ${v.min_order_text}` : 'Mọi giá trị đơn';
            const endText = v.end_at ? v.end_at.substring(0, 10).split('-').reverse().join('/') : '--';
            const scopeText = v.court_name;
            const badgeTag = v.first_booking_only ? '<span class="badge bg-info-subtle text-info border border-info-subtle ms-1" style="font-size:0.65rem;">Khách mới</span>' : '';

            return `
                <div class="col-md-6">
                    <div class="voucher-card p-3 h-100 d-flex flex-column justify-content-between position-relative shadow-sm" style="background:linear-gradient(135deg, rgba(13,148,136,0.06) 0%, rgba(20,184,166,0.14) 100%); border: 1.5px dashed var(--brand-teal, #0d9488); border-radius: 1rem;">
                        <div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge bg-teal text-white px-2 py-1" style="font-size:0.8rem; letter-spacing:0.5px;">
                                    <i class="fa-solid fa-bolt me-1"></i>GIẢM ${v.discount_percent}%
                                </span>
                                ${badgeTag}
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="fs-5 fw-extrabold text-uppercase text-dark tracking-wide font-monospace">${BookingAccount.escape(v.code)}</div>
                            </div>
                            <div class="small text-muted mb-2">
                                <div><i class="fa-solid fa-shield-halved me-1 text-teal"></i> ${maxText} · ${minText}</div>
                                <div><i class="fa-solid fa-location-dot me-1 text-teal"></i> ${BookingAccount.escape(scopeText)}</div>
                                <div><i class="fa-regular fa-clock me-1 text-teal"></i> Hạn dùng: ${endText}</div>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-2 pt-2 border-top">
                            <button type="button" class="btn btn-sm btn-outline-teal rounded-pill flex-grow-1 fw-bold btn-copy-voucher" data-code="${v.code}">
                                <i class="fa-regular fa-copy me-1"></i> Sao chép mã
                            </button>
                            <a href="courts.php" class="btn btn-sm btn-teal rounded-pill px-3 fw-bold" title="Đặt sân để dùng mã">
                                Dùng ngay <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        // Gắn sự kiện copy
        listEl.querySelectorAll('.btn-copy-voucher').forEach(btn => {
            btn.addEventListener('click', async () => {
                const code = btn.dataset.code;
                try {
                    await navigator.clipboard.writeText(code);
                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: `Đã sao chép mã ${code}!`,
                            text: 'Hãy dán vào ô giảm giá khi thanh toán đặt sân.',
                            showConfirmButton: false,
                            timer: 2500,
                            timerProgressBar: true
                        });
                    } else {
                        alert(`Đã sao chép mã ${code}! Hãy dán vào ô giảm giá khi thanh toán.`);
                    }
                } catch (_) {
                    alert(`Mã giảm giá: ${code}`);
                }
            });
        });

    } catch (e) {
        console.warn('Lỗi tải mã giảm giá:', e);
        listEl.innerHTML = `<div class="col-12 text-center text-muted py-4">Chưa có mã ưu đãi nào hoặc database chưa nâng cấp voucher.</div>`;
    }
}

