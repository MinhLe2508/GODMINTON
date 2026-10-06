document.addEventListener('DOMContentLoaded', () => {
    const user = MockDB.getUser();
    const navHistoryEl = document.getElementById('navHistoryItem');
    if (user && navHistoryEl) navHistoryEl.style.display = '';

    const form = document.getElementById('contactForm');
    const btn = document.getElementById('btnContactSubmit');
    const alertSuccess = document.getElementById('alertSuccess');

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const data = {
            name: document.getElementById('contactName').value.trim(),
            phone: document.getElementById('contactPhone').value.trim(),
            email: document.getElementById('contactEmail').value.trim(),
            subject: document.getElementById('contactSubject').value.trim() || 'Liên hệ từ website',
            message: document.getElementById('contactMessage').value.trim()
        };

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang gửi...';

        setTimeout(() => {
            MockDB.sendContact(data);
            alertSuccess.classList.remove('d-none');
            form.reset();
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi Tin Nhắn';

            // Scroll to success
            alertSuccess.scrollIntoView({ behavior: 'smooth' });
        }, 800);
    });
});