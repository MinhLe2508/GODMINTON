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
        await loadMyMembership();
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

async function loadMyMembership() {
    const badgeEl = document.getElementById('profileMembershipBadge');
    const statEl = document.getElementById('statMembershipTier');
    const activeBox = document.getElementById('profileMembershipActiveBox');
    const historyList = document.getElementById('profileMembershipHistoryList');

    try {
        const res = await BookingAccount.request('api/memberships/my-membership.php');
        const active = res.active_membership;
        const history = res.history || [];

        // 1. Badge hiển thị ngay dưới họ tên
        if (badgeEl) {
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

                badgeEl.innerHTML = `
                    <span class="badge px-3 py-1 rounded-pill fw-bold d-inline-flex align-items-center gap-2 shadow-sm"
                          style="background: ${tierGradient}; color: #ffffff; font-size: 0.82rem; border: 1px solid rgba(255,255,255,0.4); box-shadow: 0 3px 10px ${tierBoxShadow};">
                        <i class="${tierIcon}"></i>
                        <span>${tierLabel} (Giảm ${active.discount_percent}% tiền sân)</span>
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

        // 2. Stat box ở bảng tóm tắt nhanh
        if (statEl) {
            if (active && Number(active.discount_percent) > 0) {
                const tierMap = { silver: 'Bạc', gold: 'Vàng', platinum: 'Bạch Kim' };
                statEl.textContent = `${tierMap[active.tier] || 'VIP'} (${active.discount_percent}%)`;
            } else {
                statEl.textContent = 'Chưa có';
            }
        }

        // 3. Tab Gói Hội Viên - Khối thẻ thành viên đang hoạt động
        if (activeBox) {
            if (active && Number(active.discount_percent) > 0) {
                let cardGradient = 'linear-gradient(135deg, #334155 0%, #475569 50%, #64748b 100%)';
                let tierTitle = 'HỘI VIÊN BẠC (SILVER)';
                let iconClass = 'fa-solid fa-shield-halved';
                let accentColor = '#e2e8f0';

                if (active.tier === 'gold') {
                    cardGradient = 'linear-gradient(135deg, #b45309 0%, #d97706 50%, #f59e0b 100%)';
                    tierTitle = 'HỘI VIÊN VÀNG (GOLD)';
                    iconClass = 'fa-solid fa-crown';
                    accentColor = '#fef3c7';
                } else if (active.tier === 'platinum') {
                    cardGradient = 'linear-gradient(135deg, #090d16 0%, #1e293b 50%, #334155 100%)';
                    tierTitle = 'HỘI VIÊN BẠCH KIM (PLATINUM)';
                    iconClass = 'fa-solid fa-gem';
                    accentColor = '#cbd5e1';
                }

                const endFormatted = active.end_date ? active.end_date.substring(0, 10).split('-').reverse().join('/') : '--';
                const startFormatted = active.start_date ? active.start_date.substring(0, 10).split('-').reverse().join('/') : '--';
                const benefitsArray = (active.benefits || '').split('\n').map(b => b.trim()).filter(Boolean);

                activeBox.innerHTML = `
                    <div class="card border-0 rounded-4 p-4 text-white position-relative overflow-hidden shadow-lg"
                         style="background: ${cardGradient}; min-height: 220px;">
                        <div class="position-absolute end-0 bottom-0 opacity-15 me-n3 mb-n3 pointer-events-none" style="font-size: 11rem; line-height: 1; user-select: none;">
                            <i class="${iconClass}"></i>
                        </div>

                        <div class="position-relative z-1 d-flex flex-column justify-content-between h-100">
                            <div>
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-white bg-opacity-20 backdrop-blur px-3 py-1 rounded-pill fw-bold" style="letter-spacing:1px; font-size:0.75rem;">
                                            <i class="fa-solid fa-bolt text-warning me-1"></i>GODMINTON VIP PASS
                                        </span>
                                        <span class="badge bg-success text-white px-2 py-1 rounded-pill fw-bold" style="font-size:0.7rem;">
                                            <i class="fa-solid fa-circle-check me-1"></i>Đang kích hoạt
                                        </span>
                                    </div>
                                    <div class="fs-4 fw-black text-warning">
                                        GIẢM ${active.discount_percent}% TIỀN SÂN
                                    </div>
                                </div>

                                <h3 class="fw-black mb-1 tracking-wide text-uppercase" style="text-shadow: 0 2px 8px rgba(0,0,0,0.3); font-weight: 800;">
                                    ${BookingAccount.escape(active.package_title || tierTitle)}
                                </h3>
                                <div class="small opacity-90 mb-3">
                                    Mã thẻ: <span class="font-monospace fw-bold">#MB-${String(active.id).padStart(5, '0')}</span> · Phương thức: <strong>${BookingAccount.escape(active.payment_method || 'VietQR')}</strong>
                                </div>
                            </div>

                            ${benefitsArray.length ? `
                            <div class="p-3 rounded-3 bg-black bg-opacity-20 backdrop-blur mb-3">
                                <div class="small fw-bold text-uppercase mb-2" style="color: ${accentColor}; font-size:0.75rem;">
                                    <i class="fa-solid fa-gift me-1"></i>Đặc quyền thành viên đang hưởng:
                                </div>
                                <div class="row g-2">
                                    ${benefitsArray.map(b => `
                                        <div class="col-md-6 small d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-check text-warning" style="font-size:0.75rem;"></i>
                                            <span>${BookingAccount.escape(b)}</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>` : ''}

                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pt-3 border-top border-white border-opacity-20">
                                <div>
                                    <div class="small opacity-75">Thời hạn gói hội viên:</div>
                                    <div class="fw-bold fs-6">
                                        ${startFormatted} – ${endFormatted}
                                        <span class="badge bg-warning text-dark ms-2 fw-bold">Còn ${active.days_left || 0} ngày</span>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="courts.php" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" style="color: #0f172a;">
                                        <i class="fa-solid fa-table-tennis-paddle-ball me-1 text-teal"></i> Đặt sân ngay
                                    </a>
                                    <a href="explore.php?tab=membership" class="btn btn-outline-light rounded-pill px-3 fw-bold">
                                        <i class="fa-solid fa-arrows-rotate me-1"></i> Gia hạn / Nâng cấp
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                activeBox.innerHTML = `
                    <div class="p-4 rounded-4 text-center border-2 border-dashed position-relative" style="background: rgba(13, 148, 136, 0.03); border-color: rgba(13, 148, 136, 0.25);">
                        <div class="icon-circle bg-teal-subtle text-teal mx-auto mb-3" style="width: 64px; height: 64px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem;">
                            <i class="fa-solid fa-crown text-warning"></i>
                        </div>
                        <h5 class="fw-bold mb-2">Bạn chưa kích hoạt Gói Hội Viên nào</h5>
                        <p class="text-muted small mx-auto mb-4" style="max-width: 520px;">
                            Đăng ký gói hội viên GODMINTON để nhận đặc quyền <strong>giảm trực tiếp 5% – 15%</strong> toàn bộ tiền sân cho mọi lượt đặt lịch, ưu tiên giữ sân giờ vàng và nhiều quà tặng hấp dẫn!
                        </p>
                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                            <a href="explore.php?tab=membership" class="btn btn-teal rounded-pill px-4 fw-bold shadow-sm">
                                <i class="fa-solid fa-crown me-1 text-warning"></i> Khám phá & Đăng ký gói ngay
                            </a>
                            <a href="courts.php" class="btn btn-outline-secondary rounded-pill px-3">
                                Xem danh sách sân
                            </a>
                        </div>
                    </div>
                `;
            }
        }

        // 4. Tab Gói Hội Viên - Lịch sử đăng ký
        if (historyList) {
            if (!history.length) {
                historyList.innerHTML = `
                    <div class="text-center py-4 text-muted border rounded-3 bg-light">
                        <i class="fa-regular fa-folder-open fs-3 d-block mb-2 text-secondary opacity-50"></i>
                        <p class="small mb-0">Bạn chưa có lịch sử đăng ký gói hội viên nào.</p>
                    </div>
                `;
            } else {
                historyList.innerHTML = history.map(item => {
                    const start = item.start_date ? item.start_date.substring(0, 10).split('-').reverse().join('/') : '--';
                    const end = item.end_date ? item.end_date.substring(0, 10).split('-').reverse().join('/') : '--';
                    const isCurrent = item.status === 'active';

                    let statusBadge = '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill">Đã hết hạn</span>';
                    if (isCurrent) {
                        statusBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Đang kích hoạt</span>';
                    } else if (item.status === 'cancelled') {
                        statusBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Đã chuyển gói</span>';
                    }

                    let tierBadge = '<span class="badge text-white rounded-pill fw-bold" style="background:#64748b;">BẠC (5%)</span>';
                    if (item.tier === 'gold') tierBadge = '<span class="badge bg-warning text-dark rounded-pill fw-bold">VÀNG (10%)</span>';
                    else if (item.tier === 'platinum') tierBadge = '<span class="badge bg-dark text-white rounded-pill fw-bold">BẠCH KIM (15%)</span>';

                    return `
                        <div class="p-3 rounded-3 border bg-surface d-flex align-items-center justify-content-between flex-wrap gap-2 ${isCurrent ? 'border-teal shadow-sm' : ''}">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                    <strong class="fs-6 text-dark">${BookingAccount.escape(item.package_title || 'Gói Hội Viên')}</strong>
                                    ${tierBadge}
                                    ${statusBadge}
                                </div>
                                <div class="small text-muted">
                                    <span><i class="fa-regular fa-calendar me-1 text-teal"></i>${start} – ${end} (${item.duration_days} ngày)</span>
                                    <span class="mx-2">·</span>
                                    <span>Phương thức: <strong>${BookingAccount.escape(item.payment_method || 'VietQR')}</strong></span>
                                </div>
                            </div>
                            <div class="text-sm-end">
                                <div class="fw-bold fs-6 text-teal">${BookingAccount.money(item.price_paid || 0)}</div>
                                <div class="small text-muted">Mã: #MB-${item.id}</div>
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

