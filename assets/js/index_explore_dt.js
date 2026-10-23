let activeExploreFilter='all';
let exploreNews=[];
// Presentation labels only. All article records are loaded from api/news.php.
const exploreCategories={
    event:{label:'Sự kiện',icon:'fa-trophy'},
    membership:{label:'Hội viên',icon:'fa-id-card'},
    announcement:{label:'Thông báo',icon:'fa-bell'},
    notice:{label:'Thông báo',icon:'fa-bell'},
    general:{label:'Tin tức',icon:'fa-newspaper'}
};
function exploreDate(value){
    const date=String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
    return date?`${date[3]}/${date[2]}/${date[1]}`:'Ngày đăng đang cập nhật';
}
function filterExplore(button,type) {
    document.querySelectorAll('.explore-tab').forEach(tab=>tab.classList.remove('active'));
    button.classList.add('active');activeExploreFilter=type;renderExploreFeed();
    document.querySelectorAll('.explore-tab').forEach(tab=>tab.setAttribute('aria-pressed',String(tab===button)));
}
function renderExploreFeed() {
    const feed=document.getElementById('exploreFeed');if(!feed)return;
    const {escape,image}=ContentClient;
    const items=activeExploreFilter==='all'
        ? exploreNews
        : exploreNews.filter(item => {
            const t = item.type || item.category;
            return t === activeExploreFilter || (activeExploreFilter === 'announcement' && t === 'notice');
        });
    if(!items.length){feed.innerHTML='<div class="col-12"><div class="explore-empty"><i class="fa-regular fa-newspaper" aria-hidden="true"></i><h6>Chưa có bài viết trong mục này</h6><p class="mb-0 small text-muted">Nội dung sẽ xuất hiện khi có bài viết được đăng.</p></div></div>';return;}
    feed.innerHTML=items.map(item=>{
        const rawCategory = item.type || item.category || 'general';
        const category = Object.hasOwn(exploreCategories, rawCategory) ? rawCategory : (rawCategory === 'notice' ? 'announcement' : 'general');
        const meta = exploreCategories[category] || { label: 'Bài viết', icon: 'fa-newspaper' };
        const cover = (item.cover_image || item.image_url) ? image(item.cover_image || item.image_url) : '';
        const author = item.author_name || '';
        const initial = author ? escape(author.trim().charAt(0).toUpperCase()) : '<i class="fa-solid fa-newspaper" aria-hidden="true"></i>';
        const detailLink = item.slug ? `post-detail.php?slug=${encodeURIComponent(item.slug)}` : `news.php#news-${Number(item.id)}`;
        return `<div class="col-md-6 col-lg-4"><article class="feed-card">
            <div class="feed-media">
                ${cover?`<img class="feed-card-img" src="${cover}" alt="${escape(item.title)}" loading="lazy">`:''}
                <div class="feed-image-placeholder" ${cover?'hidden':''}><i class="fa-solid ${meta.icon}" aria-hidden="true"></i><span>${escape(meta.label)}</span></div>
            </div>
            <div class="feed-card-body">
                <div class="feed-author-row">
                    <div class="feed-avatar">${initial}</div>
                    <div class="feed-author-info"><div class="feed-author-name">${escape(author || 'Ban quản trị')}</div><time class="feed-date">${escape(exploreDate(item.created_at || item.publish_at))}</time></div>
                    <span class="feed-tag feed-category-${category}"><i class="fa-solid ${meta.icon}" aria-hidden="true"></i>${escape(meta.label)}</span>
                </div>
                <h3 class="feed-title">${escape(item.title)}</h3>
                <p class="feed-summary">${escape(item.summary || 'Xem nội dung đầy đủ trong bài viết.')}</p>
                <div class="feed-card-footer"><span class="feed-meta"><i class="fa-regular fa-file-lines me-1" aria-hidden="true"></i>${item.type==='membership'?'Gói hội viên':'Bài viết'}</span><a href="${detailLink}" class="btn btn-teal btn-sm rounded-pill px-3 fw-bold">Đọc chi tiết <i class="fa-solid fa-arrow-right ms-1" aria-hidden="true"></i></a></div>
            </div>
        </article></div>`;
    }).join('');
}
document.addEventListener('DOMContentLoaded',async()=>{
    const feed=document.getElementById('exploreFeed');if(!feed)return;
    feed.addEventListener('error',event=>{
        const picture=event.target;
        if(picture instanceof HTMLImageElement && picture.classList.contains('feed-card-img')){
            picture.hidden=true;
            const placeholder=picture.nextElementSibling;if(placeholder)placeholder.hidden=false;
        }
    },true);
    async function load(){
        feed.textContent='Đang tải nội dung...';
        try{
            const res = await ContentClient.request('api/posts.php?limit=50');
            exploreNews = Array.isArray(res?.items) ? res.items : (Array.isArray(res) ? res : []);
            if (!exploreNews.length) {
                try {
                    const fallback = await ContentClient.request('api/news.php');
                    if (Array.isArray(fallback)) exploreNews = fallback;
                } catch (_) {}
            }
            renderExploreFeed();
        } catch(error){
            try {
                const fallback = await ContentClient.request('api/news.php');
                if (Array.isArray(fallback) && fallback.length) {
                    exploreNews = fallback;
                    renderExploreFeed();
                    return;
                }
            } catch (_) {}
            feed.textContent=error.message;
            const retry=document.createElement('button');retry.className='btn btn-outline-secondary';retry.textContent='Thử lại';retry.onclick=load;feed.append(retry);
        }
    }
    await load();
});
