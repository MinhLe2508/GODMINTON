let savedProfile = null;
function fillSelect(id, entries, selected) {
    const el=document.getElementById(id);if(!el)return;
    const first=el.options[0];el.innerHTML='';el.appendChild(first);
    entries.forEach(([value,label])=>{const o=document.createElement('option');o.value=value;o.textContent=label;el.appendChild(o);});
    el.value=selected||'';
}
function setProfileOptions(options) {
    if(!options)return;profileOptions=options;
}
let profileOptions=null;
function resetProfileForm() {
    if(!savedProfile)return;
    if(profileOptions){
        fillSelect('inpDistrict',(profileOptions.areas||[]).map(a=>[a,a]),savedProfile.area_label);
        fillSelect('inpLevel',Object.entries(profileOptions.levels||{}),savedProfile.skill_level);
        fillSelect('inpPreferredTime',Object.entries(profileOptions.times||{}),savedProfile.preferred_time);
    }
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
        const result=await BookingAccount.request('api/profile.php');setProfileOptions(result.options);showProfile(result.user);save.disabled=false;
        const bookings=await BookingAccount.request('api/bookings.php');document.getElementById('statBookingsCount').textContent=bookings.length;
        const area=document.getElementById('profileBookingsList');
        area.innerHTML=bookings.length?bookings.slice(0,6).map(b=>`<div class="card p-3">${BookingAccount.badge(b.booking_status)}<h6 class="mt-2">${BookingAccount.escape(b.court_name)}</h6><p>${BookingAccount.escape(b.booking_code)} · ${BookingAccount.escape(b.booking_date)} · ${BookingAccount.escape(b.time_range)}</p><strong>${BookingAccount.money(b.total_amount)}</strong><a href="history.php">Xem lịch sử đầy đủ</a></div>`).join(''):'<p>Bạn chưa có đơn đặt sân.</p>';
        await loadMyVouchers();
        await loadMyMembership();
        initRenewMembershipModal();
        if (window.PushClient && typeof PushClient.initBellButton === 'function') {
            PushClient.initBellButton();
        }
    }catch(e){await BookingAccount.notify(e.message,true);}
    update.addEventListener('submit',async event=>{
        event.preventDefault();save.disabled=true;
        try {const result=await BookingAccount.request('api/profile.php',{method:'POST',body:JSON.stringify({name:document.getElementById('inpFullName').value.trim(),email:document.getElementById('inpEmail').value.trim(),phone:document.getElementById('inpPhone').value.trim(),area_label:document.getElementById('inpDistrict').value,skill_level:document.getElementById('inpLevel').value,preferred_time:document.getElementById('inpPreferredTime').value})});setProfileOptions(result.options);showProfile(result.user);await renderNavbarUser();await BookingAccount.notify('Hồ sơ đã lưu vào MySQL.');}
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

async function loadMyMembership() {
    const badgeEl = document.getElementById('profileMembershipBadge');
    const statEl = document.getElementById('statMembershipTier');
    const activeBox = document.getElementById('profileMembershipActiveBox');
    const historyList = document.getElementById('profileMembershipHistoryList');

    try {
        const res = await BookingAccount.request('api/memberships/my-membership.php');
        const active = res.active_membership;
        const pending = res.pending_membership;
        const history = res.history || [];

        // 1. Badge hiển thị ngay dưới họ tên
        if (badgeEl) {
            let pendingPkgName = 'Gói Hội Viên';
            if (pending) {
                if (pending.tier === 'silver') {
                    pendingPkgName = 'Gói Hội Viên Bạc';
                } else if (pending.tier === 'gold') {
                    pendingPkgName = 'Gói Hội Viên Vàng';
                } else if (pending.tier === 'platinum') {
                    pendingPkgName = 'Gói Hội Viên Bạch Kim';
                } else if (pending.package_title) {
                    pendingPkgName = pending.package_title.split('(')[0].split('–')[0].split('-')[0].trim();
                    if (!pendingPkgName.toLowerCase().startsWith('gói')) {
                        pendingPkgName = 'Gói ' + pendingPkgName;
                    }
                }
            }

            if (active && Number(active.discount_percent) > 0) {
                let tierLabel = 'Hội viên Bạc';
                let tierGradient = 'linear-gradient(135deg, #475569 0%, #64748b 50%, #94a3b8 100%)';
                let tierIcon = 'fa-solid fa-shield-halved';
                let tierBoxShadow = 'rgba(100, 116, 139, 0.45)';

                if (active.tier === 'gold') {
                    tierLabel = 'Hội viên Vàng';
                    tierGradient = 'linear-gradient(135deg, #b45309 0%, #d97706 50%, #f59e0b 100%)';
                    tierIcon = 'fa-solid fa-crown';
                    tierBoxShadow = 'rgba(217, 119, 6, 0.45)';
                } else if (active.tier === 'platinum') {
                    tierLabel = 'Hội viên Bạch Kim';
                    tierGradient = 'linear-gradient(135deg, #090d16 0%, #1e293b 50%, #334155 100%)';
                    tierIcon = 'fa-solid fa-gem';
                    tierBoxShadow = 'rgba(15, 23, 42, 0.55)';
                }

                let pendingNote = '';
                if (pending) {
                    pendingNote = `<span class="badge bg-warning text-dark ms-2 px-2 py-1 rounded-pill fw-bold" style="font-size:0.75rem;"><i class="fa-solid fa-clock-rotate-left me-1"></i>Đang duyệt: ${BookingAccount.escape(pendingPkgName)}</span>`;
                }

                badgeEl.innerHTML = `
                    <div class="d-inline-flex align-items-center flex-wrap gap-1">
                        <span class="badge px-3 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-2 shadow-sm"
                              style="background: ${tierGradient}; color: #ffffff; font-size: 0.82rem; border: 1px solid rgba(255,255,255,0.4); box-shadow: 0 3px 10px ${tierBoxShadow};">
                            <i class="${tierIcon}"></i>
                            <span>${tierLabel}</span>
                        </span>
                        ${pendingNote}
                    </div>
                `;
            } else if (pending) {
                badgeEl.innerHTML = `
                    <span class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-2 shadow-sm" style="font-size: 0.82rem;">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        <span>Đang duyệt: ${BookingAccount.escape(pendingPkgName)}</span>
                    </span>
                `;
            } else {
                badgeEl.innerHTML = `
                    <a href="explore.php?tab=membership" class="badge bg-white bg-opacity-25 text-white border border-white border-opacity-25 px-2 py-1 rounded-pill text-decoration-none fw-semibold" style="font-size:0.78rem;">
                        <i class="fa-solid fa-crown text-warning me-1"></i> Chưa là Hội viên · Đăng ký nhận giảm 5% – 15%
                    </a>
                `;
            }
        }

        // 2. Stat box ở bảng tóm tắt nhanh (Chỉ hiển thị tên hạng: Bạc, Vàng, Bạch Kim)
        if (statEl) {
            if (active && Number(active.discount_percent) > 0) {
                const tierMap = { silver: 'Bạc', gold: 'Vàng', platinum: 'Bạch Kim' };
                statEl.textContent = tierMap[active.tier] || 'Vàng';
            } else {
                statEl.textContent = 'Chưa có';
            }
        }

        // 3. Tab Gói Hội Viên - Khối thẻ thành viên đang hoạt động
        if (activeBox) {
            let pendingBannerHtml = '';
            if (pending) {
                pendingBannerHtml = `
                    <div class="p-3 rounded-4 mb-3 border border-warning bg-warning bg-opacity-10 d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <div class="icon-circle bg-warning text-dark flex-shrink-0" style="width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </div>
                            <div>
                                <strong class="text-dark d-block">Yêu cầu đăng ký / gia hạn đang chờ Admin xác nhận:</strong>
                                <span class="small text-muted">${BookingAccount.escape(pending.package_title)} · ${BookingAccount.money(pending.price_paid || 0)} · Phương thức: ${BookingAccount.escape(pending.payment_method || 'VietQR')}</span>
                            </div>
                        </div>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold" style="font-size:0.8rem;">
                            <i class="fa-solid fa-hourglass-half me-1"></i>Chờ Admin duyệt
                        </span>
                    </div>
                `;
            }

            if (active && Number(active.discount_percent) > 0) {
                let cardGradient = 'linear-gradient(135deg, #475569 0%, #334155 100%)';
                let shortName = 'Gói Hội Viên Bạc';

                if (active.tier === 'gold') {
                    cardGradient = 'linear-gradient(135deg, #d97706 0%, #b45309 100%)';
                    shortName = 'Gói Hội Viên Vàng';
                } else if (active.tier === 'platinum') {
                    cardGradient = 'linear-gradient(135deg, #1e293b 0%, #0f172a 100%)';
                    shortName = 'Gói Hội Viên Bạch Kim';
                }

                // Lấy danh sách quyền lợi chi tiết của gói
                let benefitsList = [];
                if (active.benefits && typeof active.benefits === 'string') {
                    benefitsList = active.benefits.split(/\r\n|\r|\n/).map(s => s.trim()).filter(Boolean);
                }
                if (!benefitsList.length) {
                    if (active.tier === 'gold') {
                        benefitsList = [
                            'Giảm 10% trực tiếp trên tổng tiền sân cho mọi lượt đặt lịch',
                            'Ưu tiên giữ khung giờ vàng trước 7 ngày',
                            'Tặng 01 ống cầu lông thi đấu chuẩn khi kích hoạt',
                            'Miễn phí đổi lịch hoặc hủy trước 6 tiếng'
                        ];
                    } else if (active.tier === 'platinum') {
                        benefitsList = [
                            'Giảm 15% tối đa trên tổng hóa đơn đặt sân toàn hệ thống',
                            'Ưu tiên giữ lịch cố định tháng và đặt trước 14 ngày',
                            'Miễn phí hủy đơn trước 4 tiếng',
                            'Vé VIP tham dự các giải đấu mở rộng do GODMINTON tổ chức',
                            'Được tặng áo đấu độc quyền và quà tri ân sinh nhật'
                        ];
                    } else {
                        benefitsList = [
                            'Giảm 5% trực tiếp trên tổng tiền sân cho mọi lượt đặt lịch',
                            'Ưu tiên giữ slot trước 3 ngày so với khách thông thường',
                            'Miễn phí đổi lịch trước 12 tiếng không tính phí',
                            'Hỗ trợ tư vấn đặt sân và kiểm tra lịch nhanh qua hotline'
                        ];
                    }
                }

                const endFormatted = active.end_date ? active.end_date.substring(0, 10).split('-').reverse().join('/') : '--';
                const startFormatted = active.start_date ? active.start_date.substring(0, 10).split('-').reverse().join('/') : '--';

                activeBox.innerHTML = `
                    ${pendingBannerHtml}
                    <div class="card border-0 rounded-4 p-4 text-white shadow-sm overflow-hidden" style="background: ${cardGradient};">
                        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <span class="badge px-3 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-2 shadow-sm"
                                      style="background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(255, 255, 255, 0.3); font-size: 0.8rem;">
                                    <span style="color: #5eead4; font-weight: 800; letter-spacing: 0.8px; font-size: 0.82rem;">GODMINTON</span>
                                    <span class="badge bg-warning text-dark px-2 py-0 rounded-pill fw-extrabold" style="font-size: 0.65rem; letter-spacing: 0.5px;">VIP</span>
                                </span>
                                <span class="badge bg-success text-white px-3 py-1 rounded-pill fw-semibold shadow-sm text-nowrap" style="font-size:0.78rem;">
                                    <i class="fa-solid fa-circle-check me-1"></i>Đang kích hoạt
                                </span>
                            </div>
                            <div class="fs-4 fw-extrabold text-warning">
                                GIẢM ${active.discount_percent}% TIỀN SÂN
                            </div>
                        </div>

                        <h3 class="fw-bold mb-3">${shortName}</h3>

                        <!-- Danh sách quyền lợi chi tiết -->
                        <div class="membership-benefits-box mb-4 p-3 rounded-4" style="background: rgba(15, 23, 42, 0.32); border: 1px solid rgba(255, 255, 255, 0.15);">
                            <div class="d-flex align-items-center gap-2 mb-2 pb-2 border-bottom border-white border-opacity-15">
                                <i class="fa-solid fa-gift text-warning"></i>
                                <span class="small fw-bold text-uppercase" style="letter-spacing: 0.5px; font-size: 0.76rem; color: #f1f5f9;">
                                    Danh sách đặc quyền hội viên của bạn:
                                </span>
                            </div>
                            <div class="row g-2">
                                ${benefitsList.map(item => `
                                    <div class="col-md-6 col-12">
                                        <div class="d-flex align-items-start gap-2" style="color: #f8fafc; font-size: 0.84rem; line-height: 1.45;">
                                            <i class="fa-solid fa-circle-check text-warning mt-1 flex-shrink-0" style="font-size: 0.85rem;"></i>
                                            <span>${BookingAccount.escape(item)}</span>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top border-white border-opacity-20">
                            <div>
                                <div class="small opacity-75">Hạn sử dụng gói:</div>
                                <div class="fw-bold fs-6">
                                    Đến ${endFormatted}
                                    <span class="badge bg-warning text-dark ms-2 fw-bold">Còn ${active.days_left || 0} ngày</span>
                                </div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm text-dark btn-open-renew-membership">
                                    <i class="fa-solid fa-arrows-rotate me-1"></i> Gia hạn gói
                                </button>
                            </div>
                        </div>
                    </div>
                `;

                // Gắn sự kiện mở modal gia hạn
                const renewBtn = activeBox.querySelector('.btn-open-renew-membership');
                if (renewBtn) {
                    renewBtn.addEventListener('click', () => {
                        const modalEl = document.getElementById('modalRenewMembership');
                        if (!modalEl) return;
                        if (typeof showRenewStep1 === 'function') showRenewStep1();
                        document.getElementById('renewPackageTitle').textContent = shortName;
                        document.getElementById('renewDiscountBadge').textContent = `-${active.discount_percent}% Tiền Sân`;
                        document.getElementById('renewCurrentEnd').textContent = endFormatted;
                        document.getElementById('renewAddDays').textContent = `+1 tháng (cộng dồn)`;

                        const priceNum = Math.round(Number(active.price_paid) || 0);
                        const priceText = BookingAccount.money(priceNum);
                        document.getElementById('renewPriceText').textContent = priceText;
                        const qrPriceEl = document.getElementById('renewQrPriceText');
                        if (qrPriceEl) qrPriceEl.textContent = priceText;

                        const userId = (savedProfile && savedProfile.id) ? savedProfile.id : 'VIP';
                        const renewRef = 'GIAHAN' + userId + 'P' + active.post_id;
                        const refEl = document.getElementById('renewTransferRef');
                        if (refEl) refEl.textContent = renewRef;

                        const qrUrl = `https://api.vietqr.io/image/970422-0783547289-compact2.jpg?amount=${priceNum}&addInfo=${encodeURIComponent(renewRef)}&accountName=TRUONG%20MINH%20TRIET`;
                        const qrImg = document.getElementById('renewQrImg');
                        if (qrImg) qrImg.src = qrUrl;

                        const btnCopyPrice = document.getElementById('btnCopyRenewPrice');
                        if (btnCopyPrice) {
                            btnCopyPrice.onclick = () => copyRenewText(priceNum.toString(), 'Số tiền');
                        }
                        const btnCopyRef = document.getElementById('btnCopyRenewRef');
                        if (btnCopyRef) {
                            btnCopyRef.onclick = () => copyRenewText(renewRef, 'Nội dung chuyển khoản');
                        }

                        window.__CURRENT_ACTIVE_POST_ID__ = active.post_id;
                        const instance = bootstrap.Modal.getOrCreateInstance(modalEl);
                        instance.show();
                    });
                }
            } else {
                activeBox.innerHTML = `
                    ${pendingBannerHtml}
                    <div class="p-4 rounded-4 text-center border-2 border-dashed position-relative" style="background: rgba(13, 148, 136, 0.03); border-color: rgba(13, 148, 136, 0.25);">
                        <div class="icon-circle bg-teal-subtle text-teal mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                            <i class="fa-solid fa-crown text-warning"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Bạn chưa có Gói Hội Viên nào đang hoạt động</h5>
                        <p class="text-muted small mx-auto mb-4" style="max-width: 520px;">
                            Đăng ký gói hội viên GODMINTON để nhận đặc quyền <strong>giảm trực tiếp 5% – 15%</strong> toàn bộ tiền sân cho mọi lượt đặt lịch, ưu tiên giữ sân giờ vàng và nhiều quà tặng hấp dẫn!
                        </p>
                        <div class="d-flex justify-content-center">
                            <a href="explore.php?tab=membership" class="btn btn-teal rounded-pill px-4 py-2 fw-bold shadow-sm">
                                <i class="fa-solid fa-crown me-2 text-warning"></i> Đăng ký gói hội viên ngay
                            </a>
                        </div>
                    </div>
                `;
            }
        }

        // 4. Tab Gói Hội Viên - Lịch sử đăng ký (Tinh gọn, rõ ràng)
        if (historyList) {
            if (!history.length) {
                historyList.innerHTML = `
                    <div class="text-center py-4 text-muted border rounded-3 bg-light">
                        <i class="fa-regular fa-folder-open fs-3 d-block mb-2 text-secondary opacity-50"></i>
                        <p class="small mb-0">Bạn chưa có lịch sử đăng ký gói hội viên nào.</p>
                    </div>
                `;
            } else {
                const tierTitles = {
                    silver: 'Gói Hội Viên Bạc',
                    gold: 'Gói Hội Viên Vàng',
                    platinum: 'Gói Hội Viên Bạch Kim'
                };

                historyList.innerHTML = history.map(item => {
                    const start = item.start_date ? item.start_date.substring(0, 10).split('-').reverse().join('/') : '--';
                    const end = item.end_date ? item.end_date.substring(0, 10).split('-').reverse().join('/') : '--';
                    const isCurrent = item.status === 'active';

                    let statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Đã hết hạn</span>';
                    if (item.status === 'pending') {
                        statusBadge = '<span class="badge bg-warning text-dark border border-warning rounded-pill fw-bold"><i class="fa-solid fa-clock-rotate-left me-1"></i>Chờ admin xác nhận</span>';
                    } else if (item.status === 'rejected') {
                        statusBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">Đã từ chối</span>';
                    } else if (isCurrent) {
                        statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Đang kích hoạt</span>';
                    } else if (item.status === 'cancelled') {
                        statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Đã chuyển gói</span>';
                    }

                    const discountPercent = item.discount_percent || (item.tier === 'platinum' ? 15 : (item.tier === 'gold' ? 10 : 5));
                    const discountBadge = `<span class="badge bg-warning-subtle text-dark border border-warning-subtle rounded-pill fw-bold">-${discountPercent}% Sân</span>`;
                    const cleanTitle = tierTitles[item.tier] || item.package_title || 'Gói Hội Viên';

                    return `
                        <div class="p-3 rounded-3 border bg-surface d-flex align-items-center justify-content-between flex-wrap gap-2 ${isCurrent ? 'border-teal shadow-sm' : ''} ${item.status === 'pending' ? 'border-warning' : ''}">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <strong class="fs-6 fw-bold">${cleanTitle}</strong>
                                    ${discountBadge}
                                    ${statusBadge}
                                </div>
                                <div class="small text-muted">
                                    <i class="fa-regular fa-calendar me-1 text-teal"></i> ${start} – ${end} (${item.duration_days} ngày)
                                </div>
                            </div>
                            <div class="text-sm-end">
                                <div class="fw-bold fs-6 text-teal">${BookingAccount.money(item.price_paid || 0)}</div>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        }

        // 5. Kiểm tra nếu URL có ?tab=membership thì tự động kích hoạt tab Hội Viên
        const tabParam = new URLSearchParams(window.location.search).get('tab');
        if (tabParam === 'membership') {
            const btn = document.getElementById('tabBtnMembership');
            if (btn && typeof bootstrap !== 'undefined') {
                const tabInstance = bootstrap.Tab.getOrCreateInstance(btn);
                tabInstance.show();
            }
        }

    } catch (e) {
        console.warn('Lỗi tải thông tin gói hội viên:', e);
        if (badgeEl) badgeEl.innerHTML = '';
        if (statEl) statEl.textContent = '—';
        if (activeBox) {
            activeBox.innerHTML = `<div class="p-3 text-muted small text-center">Không thể tải thông tin gói hội viên.</div>`;
        }
    }
}

// Điều hướng 2 bước & sao chép thông tin thanh toán VietQR cho Modal Gia Hạn
window.showRenewQrStep = function() {
    const s1 = document.getElementById('renewStep1');
    const s2 = document.getElementById('renewStep2');
    if (s1) s1.style.display = 'none';
    if (s2) s2.style.display = 'block';
};

window.showRenewStep1 = function() {
    const s1 = document.getElementById('renewStep1');
    const s2 = document.getElementById('renewStep2');
    if (s1) s1.style.display = 'block';
    if (s2) s2.style.display = 'none';
};

window.copyRenewText = function(text, label) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(() => {
            if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: `Đã chép ${label}!`,
                    showConfirmButton: false,
                    timer: 1500
                });
            } else {
                alert(`Đã sao chép ${label}: ${text}`);
            }
        }).catch(() => {
            alert(`${label}: ${text}`);
        });
    } else {
        alert(`${label}: ${text}`);
    }
};

function initRenewMembershipModal() {
    const btn = document.getElementById('btnConfirmRenew');
    if (!btn || btn.dataset.bound === 'true') return;
    btn.dataset.bound = 'true';

    btn.addEventListener('click', async () => {
        const postId = window.__CURRENT_ACTIVE_POST_ID__;
        if (!postId) return;

        const text = document.getElementById('btnRenewText');
        const spinner = document.getElementById('btnRenewSpinner');

        btn.disabled = true;
        if (text) text.style.display = 'none';
        if (spinner) spinner.style.display = 'inline-block';

        try {
            const res = await fetch('api/memberships/subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ post_id: postId, payment_method: 'VietQR' })
            });
            const json = await res.json();

            if (res.ok && json.status === 'success') {
                const modalEl = document.getElementById('modalRenewMembership');
                if (modalEl && typeof bootstrap !== 'undefined') {
                    const instance = bootstrap.Modal.getInstance(modalEl);
                    if (instance) instance.hide();
                }

                if (window.Swal) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'Đã Gửi Xác Nhận Chuyển Khoản!',
                        html: `Yêu cầu gia hạn gói <strong>${json.data.package_title}</strong> của bạn đã được ghi nhận và <strong>đang chờ Quản trị viên (Admin) đối soát, kích hoạt</strong>.<br>Hệ thống sẽ cộng dồn hạn sử dụng ngay sau khi Admin xác nhận.`,
                        confirmButtonText: 'Đã hiểu',
                        confirmButtonColor: '#0d9488'
                    });
                } else {
                    alert('Yêu cầu gia hạn đã gửi, đang chờ Admin xác nhận!');
                }

                await loadMyMembership();
            } else {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gia hạn thất bại',
                        text: json.message || 'Không thể gia hạn lúc này. Vui lòng thử lại sau.',
                        confirmButtonColor: '#0d9488'
                    });
                } else {
                    alert(json.message || 'Gia hạn thất bại.');
                }
            }
        } catch (e) {
            console.error('Lỗi gia hạn:', e);
            if (window.Swal) {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi kết nối',
                    text: 'Không thể kết nối máy chủ để gia hạn.',
                    confirmButtonColor: '#0d9488'
                });
            } else {
                alert('Không thể kết nối máy chủ để gia hạn.');
            }
        } finally {
            btn.disabled = false;
            if (text) text.style.display = 'inline-block';
            if (spinner) spinner.style.display = 'none';
        }
    });
}


