/* ================================================================
   PAYMENT PAGE LOGIC - Real-time Multi-Slot API Version
   GODMINTON — Tuần 2: Hỗ trợ 1 tiếng / 1.5 tiếng / 2 tiếng trở lên
   ================================================================ */
const fmt = n => new Intl.NumberFormat('vi-VN').format(n) + ' đ';

// --- Booking state ---
let booking = {
    courtId: 1, 
    slotIds: [],
    date: '', 
    start: '07:00', 
    end: '08:30',
    courtNum: 'Thảm A1', 
    courtName: 'Cầu Lông Quận 1 – Lê Thánh Tôn Sport',
    pricePerHour: 100000,
    duration: 1.5, 
    method: 'vnpay',
    _base: 0, _fee: 0, _total: 0, _deposit: 0
};

/* ---- Init ---- */
document.addEventListener('DOMContentLoaded', async () => {
    const p = new URLSearchParams(window.location.search);
    booking.courtId  = parseInt(p.get('court_id'))  || 1;
    booking.date     = p.get('date')     || new Date().toISOString().split('T')[0];
    booking.start    = p.get('start')    || '07:00';
    booking.end      = p.get('end')      || '08:30';
    booking.courtNum = p.get('court_num')|| 'Thảm A1';

    // Đọc slot_ids hoặc slot_id
    if (p.get('slot_ids')) {
        booking.slotIds = p.get('slot_ids').split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n) && n > 0);
    } else if (p.get('slot_id')) {
        const sid = parseInt(p.get('slot_id'));
        if (sid > 0) booking.slotIds = [sid];
    }

    // 1. Nạp chi tiết các slots từ MySQL qua API
    try {
        const slotsRes = await fetch(`api/slots.php?court_id=${booking.courtId}&date=${booking.date}`);
        const slotsJson = await slotsRes.json();
        if (slotsJson.status === 'success' && slotsJson.data) {
            let matchedSlots = [];
            if (booking.slotIds.length > 0) {
                matchedSlots = slotsJson.data.filter(s => booking.slotIds.includes(s.id));
            } else {
                // Nếu chưa có trong URL, tìm các slot user đang hold
                matchedSlots = slotsJson.data.filter(s => s.is_my_hold);
                if (matchedSlots.length > 0) {
                    booking.slotIds = matchedSlots.map(s => s.id);
                }
            }

            if (matchedSlots.length > 0) {
                matchedSlots.sort((a, b) => a.start_time.localeCompare(b.start_time));
                booking.start = matchedSlots[0].start_time;
                booking.end = matchedSlots[matchedSlots.length - 1].end_time;
                booking.courtNum = matchedSlots[0].sub_court_name;
                booking.duration = matchedSlots.length * 0.5;

                let sumPrice = 0;
                matchedSlots.forEach(s => { sumPrice += (s.price || 50000); });
                booking.pricePerHour = Math.round(sumPrice / booking.duration);
            }
        }
    } catch (e) {
        console.warn('Không thể nạp chi tiết slot:', e);
    }

    // 2. Nạp thông tin sân thật từ MySQL
    try {
        const courtRes = await fetch(`api/court-detail.php?id=${booking.courtId}`);
        const courtJson = await courtRes.json();
        if (courtJson.status === 'success' && courtJson.data) {
            const c = courtJson.data;
            booking.courtName = c.name;
            document.getElementById('courtNameEl').textContent = c.name;
            document.getElementById('courtAddrEl').innerHTML   =
                `<i class="fa-solid fa-location-dot me-1"></i>${c.address || ''}`;
            if (c.image_url) document.getElementById('courtHeroImg').src = c.image_url;
        }
    } catch (e) {
        console.warn('Lỗi nạp thông tin sân:', e);
    }

    // Pre-fill thông tin user
    const user = (typeof window.__GODMINTON_USER__ !== 'undefined' && window.__GODMINTON_USER__) 
                 || (typeof MockDB !== 'undefined' ? MockDB.getUser() : null);
    if (user) {
        if (document.getElementById('billingName'))  document.getElementById('billingName').value  = user.name  || '';
        if (document.getElementById('billingEmail')) document.getElementById('billingEmail').value = user.email || '';
        if (document.getElementById('billingPhone')) document.getElementById('billingPhone').value = user.phone || '';
    }

    // Hiển thị ngày & thời lượng
    const dateObj = new Date(booking.date + 'T00:00:00');
    const days = ['Chủ nhật','Thứ 2','Thứ 3','Thứ 4','Thứ 5','Thứ 6','Thứ 7'];
    const dd = d => String(d).padStart(2, '0');
    const dateStr = `${days[dateObj.getDay()]}, ${dd(dateObj.getDate())}/${dd(dateObj.getMonth()+1)}/${dateObj.getFullYear()}`;
    const durationLabel = booking.duration === 1 ? '1 giờ' : (booking.duration === 1.5 ? '1.5 giờ (1 tiếng 30 phút)' : `${booking.duration} giờ`);

    document.getElementById('bdrDate').textContent     = dateStr;
    document.getElementById('bdrTime').textContent     = `${booking.start} – ${booking.end}`;
    document.getElementById('bdrDuration').textContent = durationLabel;
    document.getElementById('bdrCourt').textContent    = booking.courtNum;

    calcPrices();
    startCountdown(5 * 60); // 5 phút chuẩn phiên giữ chỗ
    updateTransferRef();
});

/* ---- Price calculation ---- */
function calcPrices() {
    const base    = Math.round(booking.pricePerHour * booking.duration);
    const fee     = 0;
    const total   = base + fee;
    const deposit = Math.round(total * 0.5);

    booking._base    = base;
    booking._fee     = fee;
    booking._total   = total;
    booking._deposit = deposit;

    document.getElementById('prBase').textContent    = fmt(base);
    document.getElementById('prFee').textContent     = '0 đ (Miễn phí)';
    document.getElementById('prTotal').textContent   = fmt(total);
    document.getElementById('prDeposit').textContent = fmt(deposit);
    document.getElementById('bankAmountText').textContent = fmt(deposit);

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

/* ---- Countdown: 5 phút ---- */
function startCountdown(seconds) {
    const el   = document.getElementById('countdownEl');
    const wrap = el?.closest('.countdown-wrap');
    let rem = seconds;

    const tick = () => {
        const m = String(Math.floor(rem / 60)).padStart(2, '0');
        const s = String(rem % 60).padStart(2, '0');
        if (el) el.textContent = `${m}:${s}`;

        if (rem <= 60 && wrap) wrap.style.background = 'rgba(239,68,68,0.12)';

        if (rem <= 0) {
            clearInterval(timer);
            Swal.fire({
                icon: 'warning', 
                title: 'Phiên giữ chỗ hết hạn',
                text: 'Thời hạn 5 phút giữ chỗ đã kết thúc. Vui lòng chọn lại khung giờ.',
                confirmButtonText: 'Quay lại chọn giờ',
                confirmButtonColor: '#0d9488',
                allowOutsideClick: false
            }).then(() => window.location.href = `booking.php?court_id=${booking.courtId}`);
        }
        rem--;
    };
    tick();
    const timer = setInterval(tick, 1000);
}

/* ---- Payment submit: Gửi slot_ids lên API POST api/bookings.php ---- */
async function handlePayment() {
    const name  = document.getElementById('billingName').value.trim();
    const phone = document.getElementById('billingPhone').value.trim();
    const email = document.getElementById('billingEmail').value.trim();
    const note  = document.getElementById('billingNote')?.value.trim() || '';

    if (!name || !phone || !email) {
        Swal.fire({ icon: 'warning', title: 'Thiếu thông tin', text: 'Vui lòng điền đầy đủ họ tên, số điện thoại và email nhận vé.', confirmButtonColor: '#0d9488' });
        return;
    }
    if (!/\S+@\S+\.\S+/.test(email)) {
        Swal.fire({ icon: 'warning', title: 'Email không hợp lệ', confirmButtonColor: '#0d9488' });
        return;
    }
    if (!document.getElementById('agreeTerms').checked) {
        Swal.fire({ icon: 'info', title: 'Vui lòng đồng ý điều khoản', confirmButtonColor: '#0d9488' });
        return;
    }

    const btns = ['btnPayMobile','btnPayDesktop'];
    btns.forEach(id => {
        const b = document.getElementById(id);
        if (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý giao dịch...'; }
    });

    const methodNames = {
        vnpay:'VNPay', momo:'MoMo', zalopay:'ZaloPay',
        bank:'Chuyển khoản VietQR', card:'Thẻ quốc tế', cash:'Tiền mặt tại sân'
    };
    const paymentMethodLabel = methodNames[booking.method] || booking.method;

    try {
        const response = await fetch('api/bookings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                court_id: booking.courtId,
                slot_ids: booking.slotIds,
                payment_method: paymentMethodLabel,
                notes: note,
                billing_name: name,
                billing_phone: phone,
                billing_email: email
            })
        });

        const result = await response.json();

        if ((response.status === 200 || response.status === 201) && result.status === 'success') {
            const bData = result.data;

            document.getElementById('successCode').textContent  = bData.booking_code;
            document.getElementById('sdCourt').textContent      = bData.court_name;
            document.getElementById('sdDate').textContent       = document.getElementById('bdrDate').textContent;
            document.getElementById('sdTime').textContent       = `${bData.time_range} (${booking.duration} giờ)`;
            document.getElementById('sdDeposit').textContent    = fmt(bData.deposit_amount);
            document.getElementById('sdMethod').textContent     = paymentMethodLabel;
            document.getElementById('sdEmail').textContent      = email;

            document.getElementById('successOverlay').classList.add('show');
            document.body.style.overflow = 'hidden';
        } else if (response.status === 401 || result.code === 401) {
            Swal.fire({
                icon: 'warning',
                title: 'Yêu cầu đăng nhập',
                text: 'Vui lòng đăng nhập để hoàn tất tạo đơn đặt sân.',
                confirmButtonColor: '#0d9488'
            }).then(() => {
                window.location.href = 'auth.php?redirect=payment.php';
            });
            btns.forEach(id => {
                const b = document.getElementById(id);
                if (b) { b.disabled = false; b.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Xác Nhận & Đặt Cọc'; }
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Không thể tạo đơn đặt sân',
                text: result.message || 'Phiên giữ chỗ có thể đã hết hạn hoặc không hợp lệ. Vui lòng chọn lại khung giờ.',
                confirmButtonColor: '#0d9488'
            });
            btns.forEach(id => {
                const b = document.getElementById(id);
                if (b) { b.disabled = false; b.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Xác Nhận & Đặt Cọc'; }
            });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể kết nối máy chủ.', confirmButtonColor: '#0d9488' });
        btns.forEach(id => {
            const b = document.getElementById(id);
            if (b) { b.disabled = false; b.innerHTML = '<i class="fa-solid fa-shield-halved"></i> Xác Nhận & Đặt Cọc'; }
        });
    }
}