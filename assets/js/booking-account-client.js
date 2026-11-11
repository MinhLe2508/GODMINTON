/* Shared client helpers for bookings, history and account/profile pages. */
window.BookingAccount = (() => {
    let token = '';
    let identityPromise;
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const money = value => new Intl.NumberFormat('vi-VN', {style:'currency',currency:'VND'}).format(Number(value || 0));
    async function request(url, options = {}) {
        const method = options.method || 'GET';
        if (method !== 'GET' && !token) await identity();
        let body = options.body;
        if (body !== undefined && body !== null && typeof body === 'object' && !(body instanceof FormData)) {
            body = JSON.stringify(body);
        }
        const isFormData = body instanceof FormData;
        const defaultHeaders = method !== 'GET'
            ? (isFormData ? {'X-GODMINTON-CSRF':token} : {'Content-Type':'application/json','X-GODMINTON-CSRF':token})
            : {};
        const response = await fetch(url, {
            ...options,
            body,
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {'Accept':'application/json', ...defaultHeaders, ...options.headers}
        });
        let result;
        try { result = await response.json(); } catch { throw new Error('Máy chủ không trả JSON. Kiểm tra log PHP và migration DB.'); }
        if (!response.ok || result.status !== 'success') {
            if (response.status === 401) location.href = 'auth.php?redirect=' + encodeURIComponent(location.pathname + location.search);
            throw new Error(result.message || 'Thao tác thất bại.');
        }
        if (result.data?.csrf_token) token = result.data.csrf_token;
        if (url.includes('api/bookings.php') && Array.isArray(result.data)) {
            saveOfflineBookings(result.data);
        }
        return result.data;
    }
    function getOfflineBookings() {
        try {
            const raw = localStorage.getItem('godminton_offline_bookings');
            return raw ? JSON.parse(raw) : null;
        } catch(e) { return null; }
    }
    function saveOfflineBookings(list) {
        try {
            localStorage.setItem('godminton_offline_bookings', JSON.stringify({
                updated_at: new Date().toISOString(),
                bookings: Array.isArray(list) ? list : []
            }));
        } catch(e) {}
    }
    function identity() {
        if (!identityPromise) identityPromise = request('api/auth/me.php').catch(e => { identityPromise = null; throw e; });
        return identityPromise;
    }
    async function notify(message, error = false) {
        if (window.Swal) await Swal.fire({icon:error?'error':'success',title:error?'Không thể thực hiện':'Thành công',text:message});
        else alert(message);
    }
    async function confirm(message) {
        if (!window.Swal) return window.confirm(message);
        return (await Swal.fire({icon:'warning',title:message,showCancelButton:true,confirmButtonText:'Xác nhận',cancelButtonText:'Giữ nguyên'})).isConfirmed;
    }
    const status = {
        pending: ['bg-warning text-dark', '<i class="fa-regular fa-clock me-1"></i>Chờ cọc'],
        confirmed: ['bg-info-subtle text-info-emphasis border border-info-subtle', '<i class="fa-solid fa-circle-check me-1"></i>Đã cọc'],
        checked_in: ['bg-primary text-white shadow-sm', '<i class="fa-solid fa-person-running me-1"></i>Đang chơi'],
        completed: ['bg-success text-white fw-bold shadow-sm', '<i class="fa-solid fa-circle-check me-1"></i>Hoàn thành'],
        no_show: ['bg-warning-subtle text-warning-emphasis border border-warning-subtle', '<i class="fa-solid fa-user-xmark me-1"></i>Bùng sân'],
        cancelled: ['bg-danger text-white fw-bold shadow-sm', '<i class="fa-solid fa-ban me-1"></i>Đã hủy']
    };
    function badge(value) {
        const s = status[value] || ['bg-secondary', escape(value) || 'Không rõ'];
        return `<span class="badge ${s[0]} px-2 py-1 rounded-pill">${s[1]}</span>`;
    }
    async function logout() {
        try { localStorage.removeItem('godminton_offline_bookings'); } catch(e) {}
        await request('api/auth/logout.php',{method:'POST',body:'{}'});
        location.href='auth.php';
    }
    return {request,identity,escape,money,notify,confirm,badge,logout,getOfflineBookings,saveOfflineBookings};
})();
