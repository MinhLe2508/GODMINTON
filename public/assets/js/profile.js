/**
 * GODMINTON - Profile JS (Trang cá nhân tĩnh)
 */
document.addEventListener('DOMContentLoaded', () => {
    // 1. Tải thông tin người dùng
    const user = (typeof MockDB !== 'undefined' && MockDB.getUser()) ? MockDB.getUser() : {
        id: 4,
        name: "Nguyễn Văn An",
        email: "khach1@gmail.com",
        phone: "0912 345 678",
        role: "customer"
    };

    // Render thông tin lên Header Hero
    const initial = user.name ? user.name.charAt(0).toUpperCase() : 'A';
    const avatarEl = document.getElementById('profileAvatarInitial');
    if (avatarEl) {
        avatarEl.innerHTML = `${initial} <span class="profile-avatar-edit" title="Đổi ảnh đại diện"><i class="fa-solid fa-camera"></i></span>`;
    }
    const headerName = document.getElementById('profileHeaderName');
    if (headerName) headerName.textContent = user.name;
    const headerEmail = document.getElementById('profileHeaderEmail');
    if (headerEmail) headerEmail.innerHTML = `<i class="fa-regular fa-envelope me-1"></i> ${user.email}`;

    // Cập nhật badge vai trò chính xác
    const badgeRole = document.getElementById('profileBadgeRole');
    if (badgeRole) {
        if (user.role === 'admin') {
            badgeRole.className = 'badge bg-danger text-white fw-bold rounded-pill px-2 py-1';
            badgeRole.innerHTML = '<i class="fa-solid fa-shield-halved me-1"></i>Quản trị viên';
        } else if (user.role === 'owner') {
            badgeRole.className = 'badge bg-warning text-dark fw-bold rounded-pill px-2 py-1';
            badgeRole.innerHTML = '<i class="fa-solid fa-crown me-1"></i>Chủ sân';
        } else {
            badgeRole.className = 'badge bg-warning text-dark fw-bold rounded-pill px-2 py-1';
            badgeRole.innerHTML = '<i class="fa-solid fa-user me-1"></i>Thành viên';
        }
    }

    // Cập nhật thông tin mô tả vai trò
    const headerMeta = document.getElementById('profileHeaderMeta');
    if (headerMeta) {
        if (user.role === 'admin') {
            headerMeta.innerHTML = '<i class="fa-solid fa-shield-halved me-1"></i> Quản trị viên hệ thống · GODMINTON';
        } else if (user.role === 'owner') {
            headerMeta.innerHTML = '<i class="fa-solid fa-building me-1"></i> Đối tác chủ sân · TP. Hồ Chí Minh';
        } else {
            headerMeta.innerHTML = '<i class="fa-regular fa-calendar-check me-1"></i> Khách hàng thân thiết · TP. Hồ Chí Minh';
        }
    }

    // Render vào 6 trường của Form Hồ Sơ Cá Nhân
    const inpName = document.getElementById('inpFullName');
    if (inpName) inpName.value = user.name || '';
    const inpPhone = document.getElementById('inpPhone');
    if (inpPhone) inpPhone.value = user.phone || '';
    const inpEmail = document.getElementById('inpEmail');
    if (inpEmail) inpEmail.value = user.email || '';
    const inpDistrict = document.getElementById('inpDistrict');
    if (inpDistrict && user.district) inpDistrict.value = user.district;
    const inpLevel = document.getElementById('inpLevel');
    if (inpLevel && user.level) inpLevel.value = user.level;
    const inpPreferredTime = document.getElementById('inpPreferredTime');
    if (inpPreferredTime && user.preferredTime) inpPreferredTime.value = user.preferredTime;

    // Kiểm tra quyền Admin/Owner mới hiện nút Chuyển trang Admin
    const btnGoAdmin = document.getElementById('btnGoAdmin');
    if (btnGoAdmin) {
        if (user && (user.role === 'admin' || user.role === 'owner')) {
            btnGoAdmin.classList.remove('d-none');
        } else {
            btnGoAdmin.classList.add('d-none');
        }
    }

    // 2. Render danh sách lịch sử đặt sân gần đây
    renderBookingsList();

    // 3. Form cập nhật thông tin cá nhân
    document.getElementById('formUpdateProfile')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const newName = document.getElementById('inpFullName')?.value.trim() || '';
        const newPhone = document.getElementById('inpPhone')?.value.trim() || '';
        const newEmail = document.getElementById('inpEmail')?.value.trim() || '';
        const newDistrict = document.getElementById('inpDistrict')?.value || '';
        const newLevel = document.getElementById('inpLevel')?.value || '';
        const newPreferredTime = document.getElementById('inpPreferredTime')?.value || '';

        const updated = {
            ...user,
            name: newName,
            phone: newPhone,
            email: newEmail,
            district: newDistrict,
            level: newLevel,
            preferredTime: newPreferredTime
        };

        if (typeof MockDB !== 'undefined') {
            MockDB.setUser(updated);
        }

        if (headerName) headerName.textContent = newName;
        if (headerEmail) headerEmail.innerHTML = `<i class="fa-regular fa-envelope me-1"></i> ${newEmail}`;
        if (avatarEl) avatarEl.innerHTML = `${(newName.charAt(0) || 'A').toUpperCase()} <span class="profile-avatar-edit" title="Đổi ảnh đại diện"><i class="fa-solid fa-camera"></i></span>`;

        Swal.fire({
            icon: 'success',
            title: 'Cập nhật thành công!',
            text: 'Thông tin hồ sơ cá nhân đã được lưu.',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // 4. Form đổi mật khẩu
    document.getElementById('formChangePassword')?.addEventListener('submit', (e) => {
        e.preventDefault();
        const newPass = document.getElementById('newPass').value;
        const confirmPass = document.getElementById('confirmPass').value;

        if (newPass !== confirmPass) {
            Swal.fire({
                icon: 'warning',
                title: 'Mật khẩu không khớp',
                text: 'Vui lòng xác nhận mật khẩu mới chính xác.',
                confirmButtonColor: '#0d9488'
            });
            return;
        }

        Swal.fire({
            icon: 'success',
            title: 'Đổi mật khẩu thành công!',
            text: 'Mật khẩu mới của bạn đã được cập nhật an toàn.',
            timer: 1500,
            showConfirmButton: false
        });
        document.getElementById('formChangePassword').reset();
    });
});

function renderBookingsList() {
    const container = document.getElementById('profileBookingsList');
    if (!container) return;

    let bookings = [];
    if (typeof MockDB !== 'undefined') {
        bookings = MockDB.getBookings();
    }

    if (!bookings || bookings.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="fa-regular fa-calendar-xmark fs-2 d-block mb-2"></i>
                Bạn chưa có đơn đặt sân nào.
                <div class="mt-3">
                    <a href="courts.php" class="btn btn-teal rounded-pill px-4">Đặt sân ngay</a>
                </div>
            </div>`;
        return;
    }

    const statusMap = {
        confirmed: { badge: 'bg-success', text: 'Đã xác nhận' },
        completed: { badge: 'bg-primary', text: 'Hoàn thành' },
        cancelled: { badge: 'bg-secondary', text: 'Đã hủy' }
    };

    const countEl = document.getElementById('statBookingsCount');
    if (countEl) countEl.textContent = bookings.length;

    container.innerHTML = bookings.slice(0, 6).map(b => {
        const s = statusMap[b.booking_status] || statusMap.confirmed;
        return `
            <div class="card border rounded-4 p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-start mb-2 flex-wrap gap-2">
                    <div>
                        <span class="badge ${s.badge} rounded-pill px-3 py-1 mb-1">${s.text}</span>
                        <h6 class="fw-bold mb-0 text-truncate" style="max-width: 380px;">${b.court_name}</h6>
                        <div class="small text-muted">${b.sub_court_name || 'Sân tiêu chuẩn'} · Mã đơn: <strong class="text-teal">${b.booking_code || ('GDM-' + b.id)}</strong></div>
                    </div>
                    <div class="text-end">
                        <div class="fw-extrabold text-teal fs-6">${typeof MockDB !== 'undefined' ? MockDB.formatVND(b.total_amount) : (b.total_amount + ' đ')}</div>
                        <div class="small text-muted">Cọc: ${typeof MockDB !== 'undefined' ? MockDB.formatVND(b.deposit_amount) : (b.deposit_amount + ' đ')} (${b.payment_method || 'VNPay'})</div>
                    </div>
                </div>
                <hr class="my-2 border-secondary opacity-25">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 small text-muted">
                    <div>
                        <i class="fa-regular fa-calendar me-1 text-teal"></i>${b.booking_date}
                        <span class="mx-2">·</span>
                        <i class="fa-regular fa-clock me-1 text-teal"></i>${b.time_range}
                    </div>
                    <a href="courts.php" class="btn btn-sm btn-outline-teal rounded-pill px-3 py-1" style="font-size: 0.78rem;">
                        <i class="fa-solid fa-arrow-rotate-right me-1"></i>Đặt lại
                    </a>
                </div>
            </div>`;
    }).join('');
}

function resetProfileForm() {
    const user = (typeof MockDB !== 'undefined' && MockDB.getUser()) ? MockDB.getUser() : null;
    if (user) {
        const inpName = document.getElementById('inpFullName');
        if (inpName) inpName.value = user.name || '';
        const inpPhone = document.getElementById('inpPhone');
        if (inpPhone) inpPhone.value = user.phone || '';
        const inpEmail = document.getElementById('inpEmail');
        if (inpEmail) inpEmail.value = user.email || '';
        const inpDistrict = document.getElementById('inpDistrict');
        if (inpDistrict) inpDistrict.value = user.district || 'Quận 1';
        const inpLevel = document.getElementById('inpLevel');
        if (inpLevel) inpLevel.value = user.level || 'intermediate';
        const inpPreferredTime = document.getElementById('inpPreferredTime');
        if (inpPreferredTime) inpPreferredTime.value = user.preferredTime || 'evening';
    }
}

function copyVoucher(code) {
    navigator.clipboard.writeText(code).then(() => {
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Đã sao chép mã ${code}!`,
            showConfirmButton: false,
            timer: 2000
        });
    });
}
