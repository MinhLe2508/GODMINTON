/* ================================================================
   PAYMENT PAGE LOGIC
   ================================================================ */
const fmt = n => typeof MockDB?.formatVND === 'function' ? MockDB.formatVND(n) : n.toLocaleString('vi-VN') + 'đ';

// --- Booking state ---
let booking = {
    courtId: 1, date: '', start: '07:00', end: '08:30',
    courtNum: 'Sân 2', pricePerHour: 80000,
    duration: 1.5, method: 'vnpay',
    _base: 0, _fee: 0, _total: 0, _deposit: 0
};

/* ---- Init ---- */
document.addEventListener('DOMContentLoaded', () => {
    // Guard: redirect nếu chưa đăng nhập
    const user = typeof MockDB !== 'undefined' ? MockDB.getUser() : null;
    if (!user) { window.location.href = 'login.html?redirect=payment.html'; return; }

    // Pre-fill form
    document.getElementById('billingName').value  = user.name  || '';
    document.getElementById('billingEmail').value = user.email || '';
    document.getElementById('billingPhone').value = user.phone || '';

    // URL params
    const p = new URLSearchParams(window.location.search);
    booking.courtId  = parseInt(p.get('court_id'))  || 1;
    booking.date     = p.get('date')     || new Date().toISOString().split('T')[0];
    booking.start    = p.get('start')    || '07:00';
    booking.end      = p.get('end')      || '08:30';
    booking.courtNum = p.get('court_num')|| 'Sân 2';

    // Duration
    const [sh, sm] = booking.start.split(':').map(Number);
    const [eh, em] = booking.end.split(':').map(Number);
    booking.duration = ((eh * 60 + em) - (sh * 60 + sm)) / 60;

    // Court info from MockDB
    const courts = typeof MockDB !== 'undefined' ? MockDB.getCourts() : [];
    const court  = courts.find(c => c.id === booking.courtId) || courts[0];
    if (court) {
        booking.pricePerHour = court.min_price || 80000;
        document.getElementById('courtNameEl').textContent = court.name;
        document.getElementById('courtAddrEl').innerHTML   =
            `<i class="fa-solid fa-location-dot me-1"></i>${court.address || ''}`;
        if (court.image_url) document.getElementById('courtHeroImg').src = court.image_url;
        booking.courtName = court.name;
    }

    // Date display
    const dateObj = new Date(booking.date + 'T00:00:00');
    const days = ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
    const dd = d => String(d).padStart(2, '0');
    const dateStr = `${days[dateObj.getDay()]}, ${dd(dateObj.getDate())}/${dd(dateObj.getMonth()+1)}/${dateObj.getFullYear()}`;
    document.getElementById('bdrDate').textContent     = dateStr;
    document.getElementById('bdrTime').textContent     = `${booking.start} – ${booking.end}`;
    document.getElementById('bdrDuration').textContent = `${booking.duration} giờ`;
    document.getElementById('bdrCourt').textContent    = booking.courtNum;

    calcPrices();
    startCountdown(10 * 60);
    updateTransferRef();
});

/* ---- Price calculation ---- */
function calcPrices() {
    const base    = Math.round(booking.pricePerHour * booking.duration);
    const fee     = Math.round(base * 0.05);
    const total   = base + fee;
    const deposit = Math.round(total * 0.5);

    booking._base    = base;
    booking._fee     = fee;
    booking._total   = total;
    booking._deposit = deposit;

    document.getElementById('prBase').textContent    = fmt(base);
    document.getElementById('prFee').textContent     = fmt(fee);
    document.getElementById('prTotal').textContent   = fmt(total);
    document.getElementById('prDeposit').textContent = fmt(deposit);
    document.getElementById('bankAmountText').textContent = fmt(deposit);

    // Update CTA text
    const ctaText = `Xác Nhận & Đặt Cọc — ${fmt(deposit)}`;
    ['btnPayMobileText','btnPayDesktopText'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = ctaText;
    });
}

/* ---- Payment method select ---- */
function selectPM(el) {
    document.querySelectorAll('.pm-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    booking.method = el.dataset.method;

    document.getElementById('bankPanel').classList.toggle('show', booking.method === 'bank');
    document.getElementById('cardPanel').classList.toggle('show', booking.method === 'card');
}

/* ---- Bank transfer content ---- */
function updateTransferRef() {
    const ref = `DATSAN-${booking.courtId}-${Date.now().toString().slice(-5)}`;
    document.getElementById('transferRef').textContent = ref;
    booking._transferRef = ref;
}

function copyText(txt) {
    navigator.clipboard?.writeText(txt).then(() => toast('Đã sao chép!'));
}
function copyTransferRef() {
    copyText(document.getElementById('transferRef').textContent);
}
function toast(msg) {
    Swal.fire({ toast: true, position: 'top-end', icon: 'success',
        title: msg, showConfirmButton: false, timer: 1600, timerProgressBar: true });
}

/* ---- Card input formatting ---- */
function formatCard(inp) {
    inp.value = inp.value.replace(/\D/g,'').slice(0,16).replace(/(.{4})/g,'$1 ').trim();
}
function formatExpiry(inp) {
    let v = inp.value.replace(/\D/g,'');
    if (v.length >= 2) v = v.slice(0,2) + '/' + v.slice(2,4);
    inp.value = v;
}

/* ---- Countdown ---- */
function startCountdown(seconds) {
    const el   = document.getElementById('countdownEl');
    const wrap = el?.closest('.countdown-wrap');
    let rem = seconds;

    const tick = () => {
        const m = String(Math.floor(rem / 60)).padStart(2, '0');
        const s = String(rem % 60).padStart(2, '0');
        if (el) el.textContent = `${m}:${s}`;

        // Đổi màu đỏ khi < 60s
        if (rem <= 60 && wrap) wrap.style.background = 'rgba(239,68,68,0.12)';

        if (rem <= 0) {
            clearInterval(timer);
            Swal.fire({
                icon: 'warning', title: 'Phiên giữ chỗ hết hạn',
                text: 'Vui lòng chọn lại khung giờ.',
                confirmButtonText: 'Quay lại chọn giờ',
                confirmButtonColor: '#0d9488',
                allowOutsideClick: false
            }).then(() => window.location.href = 'booking.html');
        }
        rem--;
    };
    tick();
    const timer = setInterval(tick, 1000);
}

/* ---- Payment submit ---- */
function handlePayment() {
    // Validate form
    const name  = document.getElementById('billingName').value.trim();
    const phone = document.getElementById('billingPhone').value.trim();
    const email = document.getElementById('billingEmail').value.trim();
    if (!name || !phone || !email) {
        Swal.fire({ icon: 'warning', title: 'Thiếu thông tin',
            text: 'Vui lòng điền đầy đủ họ tên, số điện thoại và email.',
            confirmButtonColor: '#0d9488' });
        return;
    }
    if (!/\S+@\S+\.\S+/.test(email)) {
        Swal.fire({ icon: 'warning', title: 'Email không hợp lệ',
            confirmButtonColor: '#0d9488' });
        return;
    }
    if (!document.getElementById('agreeTerms').checked) {
        Swal.fire({ icon: 'info', title: 'Vui lòng đồng ý điều khoản',
            confirmButtonColor: '#0d9488' });
        return;
    }

    // Set loading state
    const btns = ['btnPayMobile','btnPayDesktop'];
    btns.forEach(id => {
        const b = document.getElementById(id);
        if (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý...'; }
    });

    // Simulate API call
    setTimeout(() => {
        const methodNames = {
            vnpay:'VNPay', momo:'MoMo', zalopay:'ZaloPay',
            bank:'Chuyển khoản', card:'Thẻ quốc tế', cash:'Tiền mặt'
        };

        // Save to MockDB if available
        if (typeof MockDB !== 'undefined' && MockDB.createBooking) {
            MockDB.createBooking({
                court_id:       booking.courtId,
                court_name:     booking.courtName || 'Sân cầu lông',
                booking_date:   booking.date,
                start_time:     booking.start,
                end_time:       booking.end,
                total_price:    booking._total,
                deposit_paid:   booking._deposit,
                payment_method: methodNames[booking.method] || booking.method,
                booking_status: 'confirmed'
            });
        }

        // Booking code
        const code = 'GDM-' + String(Date.now() % 1000000).padStart(6, '0');

        // Fill success screen
        document.getElementById('successCode').textContent  = code;
        document.getElementById('sdCourt').textContent      = booking.courtName || 'Sân cầu lông';
        document.getElementById('sdDate').textContent       = document.getElementById('bdrDate').textContent;
        document.getElementById('sdTime').textContent       = `${booking.start} – ${booking.end}`;
        document.getElementById('sdDeposit').textContent    = fmt(booking._deposit);
        document.getElementById('sdMethod').textContent     = methodNames[booking.method] || booking.method;
        document.getElementById('sdEmail').textContent      = email;

        document.getElementById('successOverlay').classList.add('show');
        document.body.style.overflow = 'hidden';
    }, 1800);
}