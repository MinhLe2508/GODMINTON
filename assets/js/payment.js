/* ================================================================
   PAYMENT PAGE LOGIC - Real-time Multi-Slot API Version
   GODMINTON — Tuần 2: Hỗ trợ 1 tiếng / 1.5 tiếng / 2 tiếng trở lên
   ================================================================ */
const fmt = n => new Intl.NumberFormat('vi-VN').format(n) + ' đ';

let paymentReady = false;
let paymentSubmitting = false;
let paymentComplete = false;
let countdownTimer;
let holdRemaining = 0;

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
    voucherCode: '',
    appliedVoucher: null,
    _base: 0, _discount: 0, _fee: 0, _total: 0, _deposit: 0
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

    setPaymentButtons(true);
    initVoucherHandlers();
    // 1. Nạp chi tiết các slots từ MySQL qua API
    try {
        const slotsRes = await fetch(`api/slots.php?court_id=${booking.courtId}&date=${booking.date}`);
        const slotsJson = await slotsRes.json();
        if (!slotsRes.ok || slotsJson.status !== 'success' || !Array.isArray(slotsJson.data)) throw new Error('Không tải được khung giờ.');
        {
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

            booking.slotIds = [...new Set(booking.slotIds)];
            if (!matchedSlots.length || matchedSlots.length !== booking.slotIds.length ||
                matchedSlots.some(slot => slot.status !== 'holding' || !slot.is_my_hold || Number(slot.hold_seconds_left) <= 0)) {
                throw new Error('Phiên giữ chỗ không còn hợp lệ. Vui lòng quay lại chọn khung giờ.');
            }
            holdRemaining = Math.min(...matchedSlots.map(slot => Number(slot.hold_seconds_left)));
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
        await Swal.fire({icon: 'warning', title: 'Không thể tải phiên giữ chỗ', text: e.message || 'Vui lòng chọn lại khung giờ.'});
        window.location.href = `booking.php?court_id=${booking.courtId}&date=${encodeURIComponent(booking.date)}`;
        return;
    }

    // 2. Nạp thông tin sân thật từ MySQL
    try {
        const courtRes = await fetch(`api/court-detail.php?id=${booking.courtId}`);
        const courtJson = await courtRes.json();
        if (courtJson.status === 'success' && courtJson.data) {
            const c = courtJson.data;
            booking.courtName = c.name;
            document.getElementById('courtNameEl').textContent = c.name;
            document.getElementById('courtAddrEl').textContent = c.address || '';
            if (c.image_url) document.getElementById('courtHeroImg').src = c.image_url;
        }
    } catch (e) {
        console.warn('Lỗi nạp thông tin sân:', e);
    }

    // Pre-fill thông tin user
    const user = (typeof window.__GODMINTON_USER__ !== 'undefined' && window.__GODMINTON_USER__);
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
    const durationLabel = booking.duration === 0.5 ? '30 phút' : (booking.duration === 1 ? '1 giờ' : (booking.duration === 1.5 ? '1.5 giờ (1 tiếng 30 phút)' : `${booking.duration} giờ`));

    document.getElementById('bdrDate').textContent     = dateStr;
    document.getElementById('bdrTime').textContent     = `${booking.start} – ${booking.end}`;
    document.getElementById('bdrDuration').textContent = durationLabel;
    document.getElementById('bdrCourt').textContent    = booking.courtNum;

    calcPrices();
    paymentReady = true;
    setPaymentButtons(false);
    startCountdown(holdRemaining); // Dùng thời hạn còn lại do máy chủ trả về.
    updateTransferRef();
});

/* ---- Price calculation ---- */
function calcPrices() {
    const rawBase = Math.round(booking.pricePerHour * booking.duration);
    let base = rawBase;
    let discount = 0;
    let fee = 0;
    let total = base;
    let deposit = Math.round(total * 0.5);

    const prowDiscount = document.getElementById('prowDiscount');
    const prDiscount = document.getElementById('prDiscount');
    const prDiscountPercent = document.getElementById('prDiscountPercent');
    const prFeeLabel = document.getElementById('prFeeLabel');

    if (booking.appliedVoucher && booking.appliedVoucher.pricing) {
        const pr = booking.appliedVoucher.pricing;
        base = pr.court_amount;
        discount = pr.discount_amount;
        fee = pr.platform_fee;
        total = pr.total_amount;
        deposit = pr.deposit_amount;

        if (prowDiscount) prowDiscount.style.display = 'flex';
        if (prDiscount) prDiscount.textContent = `- ${fmt(discount)}`;
        if (prDiscountPercent) prDiscountPercent.textContent = `${booking.appliedVoucher.voucher.discount_percent}%`;
        if (prFeeLabel) prFeeLabel.textContent = 'Phí dịch vụ (5%)';
        document.getElementById('prFee').textContent = fmt(fee);
    } else {
        if (prowDiscount) prowDiscount.style.display = 'none';
        if (prFeeLabel) prFeeLabel.textContent = 'Phí dịch vụ';
        document.getElementById('prFee').textContent = '0 đ (Miễn phí)';
    }

    booking._base    = base;
    booking._discount= discount;
    booking._fee     = fee;
    booking._total   = total;
    booking._deposit = deposit;

    document.getElementById('prBase').textContent    = fmt(base);
    document.getElementById('prTotal').textContent   = fmt(total);
    document.getElementById('prDeposit').textContent = fmt(deposit);
    document.getElementById('bankAmountText').textContent = fmt(deposit);

    const ctaText = 'Xác Nhận & Đặt Cọc';
    ['btnPayMobileText','btnPayDesktopText'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = ctaText;
    });
}

/* ---- Voucher Handling ---- */
function showVoucherError(msg) {
    const input = document.getElementById('voucherCodeInput');
    const errEl = document.getElementById('voucherError');
    const succEl = document.getElementById('voucherSuccess');
    if (input) {
        input.classList.add('is-invalid');
        input.setAttribute('aria-invalid', 'true');
    }
    if (errEl) {
        errEl.textContent = msg;
        errEl.style.display = 'block';
    }
    if (succEl) succEl.style.display = 'none';
}

function clearVoucherError() {
    const input = document.getElementById('voucherCodeInput');
    const errEl = document.getElementById('voucherError');
    if (input) {
        input.classList.remove('is-invalid');
        input.setAttribute('aria-invalid', 'false');
    }
    if (errEl) {
        errEl.textContent = '';
        errEl.style.display = 'none';
    }
}

function showVoucherSuccess(msg) {
    const succEl = document.getElementById('voucherSuccess');
    const succText = document.getElementById('voucherSuccessText');
    const errEl = document.getElementById('voucherError');
    if (errEl) errEl.style.display = 'none';
    if (succText) succText.textContent = msg;
    if (succEl) succEl.style.display = 'block';
}

async function applyVoucher() {
    const input = document.getElementById('voucherCodeInput');
    const btnApply = document.getElementById('btnApplyVoucher');
    const spinner = document.getElementById('btnApplyVoucherSpinner');
    const btnText = document.getElementById('btnApplyVoucherText');
    const btnRemove = document.getElementById('btnRemoveVoucher');

    if (!input) return;
    const code = input.value.trim().toUpperCase();
    if (!code) {
        showVoucherError('Vui lòng nhập mã giảm giá.');
        return;
    }

    if (btnApply) btnApply.disabled = true;
    if (spinner) spinner.style.display = 'inline-block';
    if (btnText) btnText.textContent = 'Đang kiểm tra...';
    clearVoucherError();

    try {
        const response = await fetch('api/vouchers/preview.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                code: code,
                court_id: booking.courtId,
                slot_ids: booking.slotIds
            })
        });

        const json = await response.json();

        if (response.ok && json.ok && json.data) {
            booking.voucherCode = code;
            booking.appliedVoucher = json.data;
            input.value = code;
            input.disabled = true;
            if (btnApply) btnApply.style.display = 'none';
            if (btnRemove) btnRemove.style.display = 'inline-block';
            showVoucherSuccess(`Đã áp dụng mã ${code} (giảm ${json.data.voucher.discount_percent}%)`);
            calcPrices();
        } else {
            removeVoucher(false);
            showVoucherError(json.message || 'Mã giảm giá không hợp lệ.');
        }
    } catch (e) {
        console.error('Lỗi kiểm tra voucher:', e);
        showVoucherError('Lỗi kết nối khi kiểm tra mã. Vui lòng thử lại.');
    } finally {
        if (btnApply) btnApply.disabled = false;
        if (spinner) spinner.style.display = 'none';
        if (btnText) btnText.textContent = 'Áp dụng';
    }
}

function removeVoucher(resetInput = true) {
    booking.voucherCode = '';
    booking.appliedVoucher = null;

    const input = document.getElementById('voucherCodeInput');
    const btnApply = document.getElementById('btnApplyVoucher');
    const btnRemove = document.getElementById('btnRemoveVoucher');
    const succEl = document.getElementById('voucherSuccess');

    if (input) {
        input.disabled = false;
        if (resetInput) input.value = '';
    }
    if (btnApply) btnApply.style.display = 'inline-block';
    if (btnRemove) btnRemove.style.display = 'none';
    if (succEl) succEl.style.display = 'none';
    clearVoucherError();
    calcPrices();
}

function initVoucherHandlers() {
    const input = document.getElementById('voucherCodeInput');
    const btnApply = document.getElementById('btnApplyVoucher');
    const btnRemove = document.getElementById('btnRemoveVoucher');

    if (input) {
        input.addEventListener('input', () => {
            clearVoucherError();
        });
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                applyVoucher();
            }
        });
    }

    if (btnApply) {
        btnApply.addEventListener('click', (e) => {
            e.preventDefault();
            applyVoucher();
        });
    }

    if (btnRemove) {
        btnRemove.addEventListener('click', (e) => {
            e.preventDefault();
            removeVoucher(true);
        });
    }
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
function openQrModal() {
    const transferRef = document.getElementById('transferRef');
    const modalRef = document.getElementById('modalTransferRef');
    if (transferRef && modalRef) {
        modalRef.textContent = transferRef.textContent;
    }
    const modalEl = document.getElementById('qrModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
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
        if (paymentComplete) return;
        const m = String(Math.floor(rem / 60)).padStart(2, '0');
        const s = String(rem % 60).padStart(2, '0');
        if (el) el.textContent = `${m}:${s}`;

        if (rem <= 60 && wrap) wrap.style.background = 'rgba(239,68,68,0.12)';

        if (rem <= 0) {
            clearInterval(countdownTimer);
            paymentReady = false;
            setPaymentButtons(true);
            if (paymentSubmitting) return;
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
    countdownTimer = setInterval(tick, 1000);
}

/* ---- Payment submit: Gửi slot_ids lên API POST api/bookings.php ---- */
function setPaymentButtons(disabled) {
    ['btnPayMobile', 'btnPayDesktop'].forEach(id => {
        const button = document.getElementById(id);
        if (button) button.disabled = disabled;
    });
}

async function handlePayment() {
    if (!paymentReady || paymentSubmitting || paymentComplete) return;
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

    paymentSubmitting = true;
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
        const payload = {
            court_id: booking.courtId,
            slot_ids: booking.slotIds,
            payment_method: paymentMethodLabel,
            notes: note,
            billing_name: name,
            billing_phone: phone,
            billing_email: email
        };
        if (booking.voucherCode) {
            payload.voucher_code = booking.voucherCode;
        }

        const response = await fetch('api/bookings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });

        const result = await response.json();

        if ((response.status === 200 || response.status === 201) && result.status === 'success') {
            paymentComplete = true;
            paymentReady = false;
            clearInterval(countdownTimer);
            const bData = result.data;

            document.getElementById('successCode').textContent  = bData.booking_code;
            document.getElementById('sdCourt').textContent      = bData.court_name;
            document.getElementById('sdDate').textContent       = document.getElementById('bdrDate').textContent;
            document.getElementById('sdTime').textContent       = `${bData.time_range} (${booking.duration} giờ)`;
            document.getElementById('sdDeposit').textContent    = fmt(bData.deposit_amount);
            document.getElementById('sdMethod').textContent     = paymentMethodLabel;
            document.getElementById('sdEmail').textContent      = email;

            const successOverlay = document.getElementById('successOverlay');
            const paymentNote = document.getElementById('successPaymentNote');
            if (paymentNote) paymentNote.hidden = !bData.payment_is_demo;
            successOverlay.inert = false;
            successOverlay.ariaHidden = 'false';
            successOverlay.classList.add('show');
            successOverlay.scrollTop = 0;
            successOverlay.focus?.();
            document.body.style.overflow = 'hidden';
        } else if (response.status === 401 || result.code === 401) {
            Swal.fire({
                icon: 'warning',
                title: 'Yêu cầu đăng nhập',
                text: 'Vui lòng đăng nhập để hoàn tất tạo đơn đặt sân.',
                confirmButtonColor: '#0d9488'
            }).then(() => {
                window.location.href = 'auth.php?redirect=' + encodeURIComponent('payment.php' + window.location.search);
            });
            btns.forEach(id => {
                const b = document.getElementById(id);
                if (b) { b.disabled = false; b.innerHTML = `<span id="${id}Text">Xác Nhận & Đặt Cọc</span>`; }
            });
        } else if (result.code && typeof result.code === 'string' && result.code.startsWith('VOUCHER_')) {
            // Lỗi mã giảm giá (ví dụ hết hạn giữa lúc xem trước và xác nhận)
            removeVoucher(true);
            showVoucherError(result.message);
            Swal.fire({
                icon: 'warning',
                title: 'Mã giảm giá không còn hiệu lực',
                text: (result.message || 'Mã đã hết hạn hoặc hết lượt.') + ' Hệ thống đã chuyển về mức giá gốc, vui lòng kiểm tra và xác nhận lại.',
                confirmButtonColor: '#0d9488'
            });
            btns.forEach(id => {
                const b = document.getElementById(id);
                if (b) { b.disabled = false; b.innerHTML = `<span id="${id}Text">Xác Nhận & Đặt Cọc</span>`; }
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
                if (b) { b.disabled = false; b.innerHTML = `<span id="${id}Text">Xác Nhận & Đặt Cọc</span>`; }
            });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể kết nối máy chủ.', confirmButtonColor: '#0d9488' });
        btns.forEach(id => {
            const b = document.getElementById(id);
            if (b) { b.disabled = false; b.innerHTML = `<span id="${id}Text">Xác Nhận & Đặt Cọc</span>`; }
        });
    } finally {
        paymentSubmitting = false;
        setPaymentButtons(!paymentReady || paymentComplete);
    }
}
