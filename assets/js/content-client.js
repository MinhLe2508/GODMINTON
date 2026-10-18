window.ContentClient = (() => {
    const escape = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const image = value => {
        try { const url = new URL(value, location.href); return ['http:','https:'].includes(url.protocol) ? escape(url.href) : ''; } catch { return ''; }
    };
    async function request(url, options = {}) {
        const response = await fetch(url, {credentials:'same-origin', cache:'no-store', ...options});
        let result;
        try { result = await response.json(); } catch { throw new Error('Phản hồi máy chủ không hợp lệ.'); }
        if (!response.ok || result.status !== 'success') throw new Error(result.message || 'Không tải được dữ liệu.');
        return result.data;
    }
    return {escape, image, request};
})();
