document.addEventListener('DOMContentLoaded', () => {
    const user = MockDB.getUser();
    const el = document.getElementById('navHistoryItem');
    if (user && el) el.style.display = '';

    const newsList = MockDB.getNews();
    const container = document.getElementById('newsContainer');

    container.innerHTML = newsList.map((news, index) => `
    <div class="${index === 0 ? 'col-lg-12' : 'col-md-6'}" id="news-${news.id}" data-aos="fade-up">
        <div class="card border rounded-4 overflow-hidden shadow-sm h-100 ${index === 0 ? 'd-md-flex flex-md-row' : ''}">
            <img src="${news.image_url}" alt="${news.title}"
                style="${index === 0 ? 'width:45%; min-height:280px; object-fit:cover;' : 'height:220px; object-fit:cover;'}">
            <div class="p-4 d-flex flex-column justify-content-between flex-grow-1">
                <div>
                    <div class="small text-muted mb-2">
                        <i class="fa-regular fa-clock me-1 text-teal"></i> ${news.created_at} • Ban Biên Tập
                    </div>
                    <h4 class="fw-bold mb-2">${news.title}</h4>
                    <p class="text-secondary small mb-3">${news.summary}</p>
                    <div class="news-content small text-muted d-none" id="content-${news.id}">
                        ${news.content}
                    </div>
                </div>
                <div>
                    <button class="btn btn-outline-teal btn-sm rounded-pill px-3" onclick="toggleNews(${news.id}, this)">
                        Đọc chi tiết <i class="fa-solid fa-chevron-down ms-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>`).join('');
});

function toggleNews(id, btn) {
    const content = document.getElementById('content-' + id);
    if (content) {
        if (content.classList.contains('d-none')) {
            content.classList.remove('d-none');
            btn.innerHTML = 'Thu gọn <i class="fa-solid fa-chevron-up ms-1"></i>';
        } else {
            content.classList.add('d-none');
            btn.innerHTML = 'Đọc chi tiết <i class="fa-solid fa-chevron-down ms-1"></i>';
        }
    }
}