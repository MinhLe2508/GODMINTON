document.addEventListener('DOMContentLoaded', async () => {
    const container = document.getElementById('newsContainer');
    const {escape, image, request} = ContentClient;
    async function load() {
        container.textContent = 'Đang tải tin tức...';
        try {
            const news = await request('api/news.php');
            if (!Array.isArray(news)) throw new Error('Dữ liệu tin tức không hợp lệ.');
            if (!news.length) {container.innerHTML='<p class="text-muted text-center py-4">Chưa có tin tức được đăng.</p>';return;}
            container.innerHTML = news.map(item => `<article class="col-md-6" id="news-${Number(item.id)}"><div class="card border rounded-4 overflow-hidden h-100">
                ${item.image_url && image(item.image_url) ? `<img src="${image(item.image_url)}" alt="" loading="lazy" style="height:220px;object-fit:cover">` : ''}
                <div class="p-4"><p class="small text-muted">${escape(item.created_at)}</p><h4>${escape(item.title)}</h4><p>${escape(item.summary)}</p>
                <details><summary class="text-teal" style="cursor:pointer">Đọc chi tiết</summary><div class="mt-3 text-body-secondary" style="white-space:pre-line">${escape(item.content)}</div></details></div></div></article>`).join('');
        } catch (error) {
            container.textContent=error.message;
            const retry=document.createElement('button');retry.className='btn btn-outline-secondary';retry.textContent='Thử lại';retry.onclick=load;container.append(retry);
        }
    }
    await load();
});
