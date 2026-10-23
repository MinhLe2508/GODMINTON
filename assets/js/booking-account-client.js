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
        const response = await fetch(url, {
            ...options,
            body,
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {'Accept':'application/json', ...(method !== 'GET' ? {'Content-Type':'application/json','X-GODMINTON-CSRF':token} : {}), ...options.headers}
        });
        let result;
        try { result = await response.json(); } catch { throw new Error('Máy chủ không trả JSON. Kiểm tra log PHP và migration DB.'); }
        if (!response.ok || result.status !== 'success') {
            if (response.status === 401) location.href = 'auth.php?redirect=' + encodeURIComponent(location.pathname + location.search);
            throw new Error(result.message || 'Thao tác thất bại.');
        }
        if (result.data?.csrf_token) token = result.data.csrf_token;
        return result.data;
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
    const status = {pending:['bg-warning text-dark','Chờ xử lý'],confirmed:['bg-success','Đã xác nhận'],completed:['bg-primary','Hoàn thành'],cancelled:['bg-secondary','Đã hủy']};
    function badge(value) {const s=status[value]||['bg-secondary','Không rõ'];return `<span class="badge ${s[0]}">${s[1]}</span>`;}
    async function logout() {await request('api/auth/logout.php',{method:'POST',body:'{}'});location.href='auth.php';}
    return {request,identity,escape,money,notify,confirm,badge,logout};
})();
