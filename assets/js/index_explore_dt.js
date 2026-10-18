let activeExploreFilter='all';
let exploreNews=[];
function filterExplore(button,type) {
    document.querySelectorAll('.explore-tab').forEach(tab=>tab.classList.remove('active'));
    button.classList.add('active');activeExploreFilter=type;renderExploreFeed();
}
function renderExploreFeed() {
    const feed=document.getElementById('exploreFeed');if(!feed)return;
    const {escape,image}=ContentClient;
    const items=activeExploreFilter==='all'?exploreNews:exploreNews.filter(item=>item.category===activeExploreFilter);
    if(!items.length){feed.innerHTML='<p class="col-12 text-center text-muted py-4">Chưa có nội dung được đăng trong mục này.</p>';return;}
    feed.innerHTML=items.slice(0,6).map(item=>`<div class="col-md-6 col-lg-4"><article class="feed-card">
        ${item.image_url && image(item.image_url)?`<img class="feed-card-img" src="${image(item.image_url)}" alt="" loading="lazy">`:''}
        <div class="feed-card-body"><p class="small text-muted">${escape(item.created_at)}</p><h6>${escape(item.title)}</h6><p class="small text-muted">${escape(item.summary)}</p>
        <div class="feed-card-footer"><a href="news.php#news-${Number(item.id)}" class="btn btn-teal btn-sm rounded-pill">Đọc chi tiết</a></div></div></article></div>`).join('');
}
document.addEventListener('DOMContentLoaded',async()=>{
    const feed=document.getElementById('exploreFeed');if(!feed)return;
    async function load(){
        feed.textContent='Đang tải nội dung...';
        try{exploreNews=await ContentClient.request('api/news.php');if(!Array.isArray(exploreNews))throw new Error('Dữ liệu không hợp lệ.');renderExploreFeed();}
        catch(error){feed.textContent=error.message;const retry=document.createElement('button');retry.className='btn btn-outline-secondary';retry.textContent='Thử lại';retry.onclick=load;feed.append(retry);}
    }
    await load();
});
