/* ================================================================
   PAYMENT PAGE LOGIC - Real-time Multi-Slot API Version
   GODMINTON — Hỗ trợ VNPay, MoMo, ZaloPay & Chuyển khoản VietQR
   ================================================================ */
const fmt = n => new Intl.NumberFormat('vi-VN').format(n) + ' đ';

let paymentReady = false;
let paymentSubmitting = false;
let paymentComplete = false;
let countdownTimer;
let holdRemaining = 0;

// Cấu hình chi tiết cho 4 phương thức thanh toán trên Web
const PAYMENT_GATEWAY_CONFIG = {
    vnpay: {
        label: 'VNPay',
        summaryLabel: 'VNPay (VNPAY-QR)',
        modalTitle: 'Quét Mã VNPAY-QR',
        checkoutTitle: 'Cổng Thanh Toán VNPAY-QR',
        checkoutSub: 'Hỗ trợ 40+ Ngân hàng & Ví VNPAY • Bảo mật PCI-DSS',
        providerName: 'Cổng VNPAY / MB Bank',
        accLabel: 'Mã Terminal / TK:',
        accValue: '0783547289',
        ownerName: 'TRUONG MINH TRIET',
        qrImg: 'assets/img/vnpay-payment.png',
        downloadName: 'VNPAY-QR-GODMINTON.png',
        logoUrl: 'https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-VNPAY-QR.png',
        headerClass: ''
    },
    momo: {
        label: 'MoMo',
        summaryLabel: 'Ví điện tử MoMo',
        modalTitle: 'Quét Mã QR Ví MoMo',
        checkoutTitle: 'Cổng Thanh Toán Ví MoMo',
        checkoutSub: 'Siêu ứng dụng MoMo • Xác nhận tức thì 24/7',
        providerName: 'Ví điện tử MoMo',
        accLabel: 'SĐT Ví MoMo:',
        accValue: '0783547289',
        ownerName: 'TRUONG MINH TRIET',
        qrImg: 'assets/img/momo-payment.png',
        downloadName: 'MoMo-QR-GODMINTON.png',
        logoUrl: 'https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png',
        headerClass: 'momo-header'
    },
    zalopay: {
        label: 'ZaloPay',
        summaryLabel: 'Ví điện tử ZaloPay',
        modalTitle: 'Quét Mã QR Ví ZaloPay',
        checkoutTitle: 'Cổng Thanh Toán ZaloPay',
        checkoutSub: 'Quét mã trực tiếp từ ứng dụng Zalo hoặc ZaloPay',
        providerName: 'Ví điện tử ZaloPay',
        accLabel: 'SĐT / TK ZaloPay:',
        accValue: '0783547289',
        ownerName: 'TRUONG MINH TRIET',
        qrImg: 'assets/img/zalopay-payment.png',
        downloadName: 'ZaloPay-QR-GODMINTON.png',
        logoUrl: 'https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png',
        headerClass: 'zalopay-header'
    },
    bank: {
        label: 'Chuyển khoản VietQR',
        summaryLabel: 'Chuyển khoản VietQR',
        modalTitle: 'Quét Mã VietQR MB Bank',
        checkoutTitle: 'Chuyển Khoản Ngân Hàng VietQR',
        checkoutSub: 'Tự động xác nhận 24/7 qua hệ thống NAPAS 247',
        providerName: 'MB Bank (Quân Đội)',
        accLabel: 'Số TK:',
        accValue: '0783547289',
        ownerName: 'TRUONG MINH TRIET',
        qrImg: 'assets/img/vietqr-payment.png',
        downloadName: 'VietQR-GODMINTON.png',
        logoUrl: 'assets/img/logo-teal.svg',
        headerClass: ''
    }
};

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
    method: 'bank',
    vnpayBank: 'MB Bank',
    gatewayVerified: false,
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
        if (document.getElementById('momoUserPhone') && user.phone) document.getElementById('momoUserPhone').value = user.phone;
        if (document.getElementById('zalopayUserPhone') && user.phone) document.getElementById('zalopayUserPhone').value = user.phone;
        if (document.getElementById('vnpayCardHolder') && user.name) {
            document.getElementById('vnpayCardHolder').value = removeVietnameseTones(user.name).toUpperCase();
        }
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
    startCountdown(holdRemaining);
    updateTransferRef();
    syncPaymentPanels();
});

function removeVietnameseTones(str) {
    return String(str || '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/đ/g, 'd')
        .replace(/Đ/g, 'D');
}

/* ---- Price calculation ---- */
function calcPrices() {
    const rawBase = Math.round(booking.pricePerHour * booking.duration);
    let base = rawBase;
    let memberDiscount = 0;
    let voucherDiscount = 0;
    let fee = 0;
    let total = base;

    // 1. Kiểm tra Hội viên của người dùng
    const user = (typeof window.__GODMINTON_USER__ !== 'undefined' && window.__GODMINTON_USER__);
    const membership = user?.membership;

    const bannerEl = document.getElementById('memberVipBanner');
    const bannerTier = document.getElementById('memberBannerTier');
    const bannerPercent = document.getElementById('memberBannerPercent');
    const prowMembership = document.getElementById('prowMembership');
    const prMemberPercent = document.getElementById('prMemberPercent');
    const prMemberDiscount = document.getElementById('prMemberDiscount');

    const bannerIcon = document.getElementById('memberBannerIcon');
    const bannerText = document.getElementById('memberBannerText');

    if (membership && Number(membership.discount_percent) > 0) {
        const mPercent = Number(membership.discount_percent);
        memberDiscount = Math.round((base * mPercent) / 100);

        let tierName = 'Hội Viên';
        let bannerBg = 'linear-gradient(135deg, rgba(245,158,11,0.12), rgba(217,119,6,0.18))';
        let bannerBorder = '#f59e0b';
        let bannerTextColor = '#92400e';
        let iconClass = 'fa-solid fa-crown text-warning';
        let rowColor = '#0d9488';

        if (membership.tier === 'silver') {
            tierName = 'Bạc (Silver)';
            bannerBg = 'linear-gradient(135deg, rgba(148,163,184,0.22), rgba(226,232,240,0.45))';
            bannerBorder = '#64748b';
            bannerTextColor = '#1e293b';
            iconClass = 'fa-solid fa-shield-halved text-secondary';
            rowColor = '#475569';
        } else if (membership.tier === 'gold') {
            tierName = 'Vàng (Gold)';
            bannerBg = 'linear-gradient(135deg, rgba(245,158,11,0.15), rgba(251,191,36,0.28))';
            bannerBorder = '#f59e0b';
            bannerTextColor = '#92400e';
            iconClass = 'fa-solid fa-crown text-warning';
            rowColor = '#d97706';
        } else if (membership.tier === 'platinum') {
            tierName = 'Bạch Kim (Platinum)';
            bannerBg = 'linear-gradient(135deg, rgba(51,65,85,0.18), rgba(100,116,139,0.3))';
            bannerBorder = '#334155';
            bannerTextColor = '#0f172a';
            iconClass = 'fa-solid fa-gem text-primary';
            rowColor = '#1e293b';
        }

        if (bannerEl) {
            bannerEl.style.display = 'flex';
            bannerEl.style.background = bannerBg;
            bannerEl.style.borderLeft = `4px solid ${bannerBorder}`;
            bannerEl.style.color = bannerTextColor;
        }
        if (bannerIcon) {
            bannerIcon.className = `${iconClass} fs-5`;
        }
        if (bannerText) {
            bannerText.style.color = bannerTextColor;
        }
        if (bannerTier) bannerTier.textContent = tierName.toUpperCase();
        if (bannerPercent) bannerPercent.textContent = `${mPercent}%`;

        if (prowMembership) {
            prowMembership.style.display = 'flex';
            prowMembership.style.color = rowColor;
        }
        const prMemberLabel = document.getElementById('prMemberLabel');
        if (prMemberLabel) {
            prMemberLabel.innerHTML = `<i class="${iconClass} me-1"></i>Ưu đãi Hội viên ${tierName} (<span id="prMemberPercent">${mPercent}%</span>)`;
        } else if (prMemberPercent) {
            prMemberPercent.textContent = `${mPercent}%`;
        }
        if (prMemberDiscount) prMemberDiscount.textContent = `- ${fmt(memberDiscount)}`;
    } else {
        if (bannerEl) bannerEl.style.display = 'none';
        if (prowMembership) prowMembership.style.display = 'none';
    }

    // 2. Kiểm tra Voucher áp dụng
    const prowDiscount = document.getElementById('prowDiscount');
    const prDiscount = document.getElementById('prDiscount');
    const prDiscountPercent = document.getElementById('prDiscountPercent');
    const prFeeLabel = document.getElementById('prFeeLabel');

    if (booking.appliedVoucher && booking.appliedVoucher.pricing) {
        const pr = booking.appliedVoucher.pricing;
        base = pr.court_amount;
        memberDiscount = pr.membership_discount_amount || memberDiscount;
        voucherDiscount = Math.max(0, pr.discount_amount - memberDiscount);
        fee = pr.platform_fee;
        total = pr.total_amount;

        if (prowDiscount) prowDiscount.style.display = 'flex';
        if (prDiscount) prDiscount.textContent = `- ${fmt(voucherDiscount)}`;
        if (prDiscountPercent) prDiscountPercent.textContent = `${booking.appliedVoucher.voucher.discount_percent}%`;
        if (prFeeLabel) prFeeLabel.textContent = 'Phí dịch vụ (5%)';
        document.getElementById('prFee').textContent = fmt(fee);
    } else {
        if (prowDiscount) prowDiscount.style.display = 'none';
        
        // Khi không có voucher, phí sàn tính theo tiền sau giảm hội viên (nếu có giảm)
        if (memberDiscount > 0) {
            const afterDiscount = Math.max(0, base - memberDiscount);
            fee = Math.round(afterDiscount * 0.05);
            total = afterDiscount + fee;
            if (prFeeLabel) prFeeLabel.textContent = 'Phí dịch vụ (5%)';
            document.getElementById('prFee').textContent = fmt(fee);
        } else {
            fee = 0;
            total = base;
            if (prFeeLabel) prFeeLabel.textContent = 'Phí dịch vụ';
            document.getElementById('prFee').textContent = '0 đ (Miễn phí)';
        }
    }

    const deposit = Math.round(total * 0.5);

    booking._base    = base;
    booking._discount= memberDiscount + voucherDiscount;
    booking._fee     = fee;
    booking._total   = total;
    booking._deposit = deposit;

    document.getElementById('prBase').textContent    = fmt(base);
    document.getElementById('prTotal').textContent   = fmt(total);
    document.getElementById('prDeposit').textContent = fmt(deposit);

    // Cập nhật số tiền đặt cọc trên tất cả các panel thanh toán (VNPay, MoMo, ZaloPay, Chuyển khoản)
    document.querySelectorAll('.js-deposit-amount').forEach(el => {
        el.textContent = fmt(deposit);
    });
    const modalDepositEl = document.getElementById('modalDepositAmount');
    if (modalDepositEl) modalDepositEl.textContent = fmt(deposit);
    const gwModalAmountEl = document.getElementById('gwModalAmount');
    if (gwModalAmountEl) gwModalAmountEl.textContent = fmt(deposit);

    updateCtaLabels();
}

function updateCtaLabels() {
    const cfg = PAYMENT_GATEWAY_CONFIG[booking.method] || PAYMENT_GATEWAY_CONFIG.vnpay;
    const ctaText = `Xác Nhận & Đặt Cọc (${cfg.label})`;
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

/* ---- Payment method select (VNPay, MoMo, ZaloPay, Chuyển khoản) ---- */
function selectPM(el) {
    document.querySelectorAll('.pm-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    el.blur(); // Loại bỏ focus ring mặc định của trình duyệt
    booking.method = el.dataset.method;
    syncPaymentPanels();
}

function syncPaymentPanels() {
    const panels = {
        vnpay: document.getElementById('vnpayPanel'),
        momo: document.getElementById('momoPanel'),
        zalopay: document.getElementById('zalopayPanel'),
        bank: document.getElementById('bankPanel')
    };
    Object.keys(panels).forEach(key => {
        if (panels[key]) {
            panels[key].classList.toggle('show', booking.method === key);
        }
    });

    const cfg = PAYMENT_GATEWAY_CONFIG[booking.method] || PAYMENT_GATEWAY_CONFIG.vnpay;
    const bdrMethodEl = document.getElementById('bdrMethod');
    if (bdrMethodEl) bdrMethodEl.textContent = cfg.summaryLabel;
    updateCtaLabels();
}

/* ---- Chuyển tab con trong từng cổng (Quét QR vs Thanh toán Web/ATM/SĐT) ---- */
function switchGatewayTab(gateway, tabType, btnEl) {
    const panel = document.getElementById(`${gateway}Panel`);
    if (!panel) return;
    panel.querySelectorAll('.gw-mode-tab').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');

    const qrPane = document.getElementById(`${gateway}-tab-qr`);
    const webPane = document.getElementById(`${gateway}-tab-web`);
    if (qrPane) qrPane.classList.toggle('show', tabType === 'qr');
    if (webPane) webPane.classList.toggle('show', tabType === 'web');
}

function selectVnpayBank(btnEl, bankName) {
    const grid = document.getElementById('vnpayBankGrid');
    if (grid) {
        grid.querySelectorAll('.gw-bank-item').forEach(b => b.classList.remove('active'));
    }
    if (btnEl) btnEl.classList.add('active');
    booking.vnpayBank = bankName;
    const labelEl = document.getElementById('vnpaySelectedBankLabel');
    if (labelEl) labelEl.textContent = bankName;
}

function formatAtmCard(inp) {
    inp.value = inp.value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim();
}

function formatIssueDate(inp) {
    let v = inp.value.replace(/\D/g, '');
    if (v.length >= 2) v = v.slice(0, 2) + '/' + v.slice(2, 4);
    inp.value = v;
}

/* ---- Xác thực trực tiếp trên tab Web của VNPay / MoMo / ZaloPay ---- */
async function verifyInlineGateway(gateway) {
    const cfg = PAYMENT_GATEWAY_CONFIG[gateway] || PAYMENT_GATEWAY_CONFIG.vnpay;

    if (gateway === 'vnpay') {
        const cardNum = document.getElementById('vnpayCardNumber')?.value.replace(/\s+/g, '') || '';
        const holder = document.getElementById('vnpayCardHolder')?.value.trim() || '';
        if (cardNum.length < 8 || !holder) {
            Swal.fire({
                icon: 'warning',
                title: 'Thiếu thông tin thẻ nội địa',
                text: `Vui lòng nhập số thẻ/số tài khoản ${booking.vnpayBank} và tên chủ thẻ để xác thực qua cổng VNPAY.`,
                confirmButtonColor: '#0d9488'
            });
            return;
        }
    } else if (gateway === 'momo') {
        const phone = document.getElementById('momoUserPhone')?.value.trim() || '';
        if (!/^0\d{8,10}$/.test(phone.replace(/\s+/g, ''))) {
            Swal.fire({
                icon: 'warning',
                title: 'Số điện thoại MoMo chưa hợp lệ',
                text: 'Vui lòng nhập số điện thoại đăng ký Ví MoMo (10 chữ số).',
                confirmButtonColor: '#0d9488'
            });
            return;
        }
    } else if (gateway === 'zalopay') {
        const phone = document.getElementById('zalopayUserPhone')?.value.trim() || '';
        if (!/^0\d{8,10}$/.test(phone.replace(/\s+/g, ''))) {
            Swal.fire({
                icon: 'warning',
                title: 'Số điện thoại ZaloPay chưa hợp lệ',
                text: 'Vui lòng nhập số điện thoại liên kết Zalo / ZaloPay (10 chữ số).',
                confirmButtonColor: '#0d9488'
            });
            return;
        }
    }

    const { value: otp } = await Swal.fire({
        title: `Xác thực OTP ${cfg.label}`,
        html: `<p class="small text-muted mb-2">Mã OTP xác nhận giao dịch đặt cọc <strong>${fmt(booking._deposit)}</strong> (${booking._transferRef}) đã được gửi tới thiết bị của bạn.</p>`,
        input: 'text',
        inputPlaceholder: 'Nhập mã OTP 6 số (VD: 123456)',
        inputValue: '123456',
        showCancelButton: true,
        confirmButtonText: 'Xác nhận thanh toán',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#0d9488',
        inputValidator: (value) => {
            if (!value || value.trim().length < 4) {
                return 'Vui lòng nhập mã OTP hợp lệ!';
            }
        }
    });

    if (otp) {
        booking.gatewayVerified = true;
        const badge = document.getElementById('onlineVerifiedBadge');
        if (badge) badge.style.display = 'inline-flex';
        toast(`Đã xác thực giao dịch ${cfg.label} thành công!`);
    }
}

/* ---- Mở Cổng Thanh Toán Web (Popup Checkout Gateway) ---- */
function openGatewayCheckout(gateway) {
    const cfg = PAYMENT_GATEWAY_CONFIG[gateway] || PAYMENT_GATEWAY_CONFIG.vnpay;
    const header = document.getElementById('gwModalHeader');
    if (header) {
        header.className = 'gw-modal-header ' + (cfg.headerClass || '');
    }
    const logo = document.getElementById('gwModalLogo');
    if (logo) logo.src = cfg.logoUrl;
    const title = document.getElementById('gwCheckoutLabel');
    if (title) title.textContent = cfg.checkoutTitle;
    const sub = document.getElementById('gwCheckoutSub');
    if (sub) sub.textContent = cfg.checkoutSub;
    const ref = document.getElementById('gwModalRef');
    if (ref) ref.textContent = booking._transferRef || 'DATSAN-...';
    const amt = document.getElementById('gwModalAmount');
    if (amt) amt.textContent = fmt(booking._deposit);
    const qrImg = document.getElementById('gwModalQrImg');
    if (qrImg) qrImg.src = cfg.qrImg;

    const modalEl = document.getElementById('gatewayCheckoutModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function confirmGatewayCheckout() {
    booking.gatewayVerified = true;
    const badge = document.getElementById('onlineVerifiedBadge');
    if (badge) {
        badge.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>Đã xác thực trực tuyến';
        badge.style.display = 'inline-flex';
    }

    const btnBank = document.getElementById('btnConfirmBankTransfer');
    if (btnBank && booking.method === 'bank') {
        btnBank.classList.add('confirmed');
        btnBank.innerHTML = '<i class="fa-solid fa-check-double text-white me-1"></i> Đã xác nhận chuyển tiền';
    }

    const modalEl = document.getElementById('gatewayCheckoutModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    const agreeTerms = document.getElementById('agreeTerms');
    if (agreeTerms && !agreeTerms.checked) {
        agreeTerms.checked = true;
    }

    const name  = document.getElementById('billingName')?.value.trim();
    const phone = document.getElementById('billingPhone')?.value.trim();
    const email = document.getElementById('billingEmail')?.value.trim();
    if (name && phone && email) {
        handlePayment();
    } else {
        toast('Đã ghi nhận thanh toán! Vui lòng kiểm tra thông tin người đặt để hoàn tất.');
        highlightCtaButton();
    }
}

/* ---- Xác nhận chuyển khoản ngân hàng VietQR ---- */
function confirmBankTransfer() {
    markBankTransferConfirmed();

    Swal.fire({
        icon: 'success',
        title: 'Đã ghi nhận chuyển khoản!',
        html: `
            <div class="text-start p-3 rounded-3 bg-light mb-3" style="font-size:0.85rem;">
                <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                    <span class="text-muted">Mã thanh toán:</span>
                    <strong class="font-monospace text-teal fs-6">${booking._transferRef}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                    <span class="text-muted">Số tiền cọc:</span>
                    <strong class="text-warning fs-6">${fmt(booking._deposit)}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Trạng thái:</span>
                    <span class="badge bg-success-subtle text-success border border-success-subtle">
                        <i class="fa-solid fa-check me-1"></i>Đã ghi nhận chuyển tiền
                    </span>
                </div>
            </div>
            <p class="small text-muted mb-0">Hệ thống đã ghi nhận thông tin chuyển khoản. Bạn hãy nhấn nút <strong>"Xác Nhận & Đặt Cọc"</strong> để hoàn tất và nhận hóa đơn đặt sân!</p>
        `,
        confirmButtonText: 'Đến nút Xác Nhận & Đặt Cọc <i class="fa-solid fa-arrow-right ms-1"></i>',
        confirmButtonColor: '#0d9488'
    }).then(() => {
        highlightCtaButton();
    });
}

function markBankTransferConfirmed() {
    booking.gatewayVerified = true;
    const btn = document.getElementById('btnConfirmBankTransfer');
    if (btn) {
        btn.classList.add('confirmed');
        btn.innerHTML = '<i class="fa-solid fa-check-double text-white me-1"></i> Đã xác nhận chuyển tiền';
    }
    const badge = document.getElementById('onlineVerifiedBadge');
    if (badge) {
        badge.innerHTML = '<i class="fa-solid fa-circle-check me-1"></i>Đã xác nhận chuyển khoản';
        badge.style.display = 'inline-flex';
    }
    toast('Đã ghi nhận chuyển khoản thành công!');
}

function highlightCtaButton() {
    const isMobile = window.innerWidth < 992;
    const targetBtn = document.getElementById(isMobile ? 'btnPayMobile' : 'btnPayDesktop');
    if (targetBtn) {
        targetBtn.classList.remove('pulse-cta');
        void targetBtn.offsetWidth;
        targetBtn.classList.add('pulse-cta');
        targetBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

/* ---- Bank & E-Wallet transfer reference content ---- */
function updateTransferRef() {
    const suffix = Date.now().toString().slice(-5);
    const ref = `DATSAN-${booking.courtId}-${suffix}`;
    const vnpCode = `VNP-${booking.courtId}${suffix}`;

    document.querySelectorAll('.js-transfer-ref').forEach(el => {
        el.textContent = ref;
    });
    const vnpayTransCodeEl = document.getElementById('vnpayTransCode');
    if (vnpayTransCodeEl) vnpayTransCodeEl.textContent = vnpCode;

    booking._transferRef = ref;
    booking._vnpayCode = vnpCode;
}

function copyText(txt) {
    navigator.clipboard?.writeText(txt).then(() => toast('Đã sao chép!'));
}
function copyById(id) {
    const el = document.getElementById(id);
    if (el) copyText(el.textContent.trim());
}
function copyTransferRef() {
    copyText(booking._transferRef || document.getElementById('transferRef')?.textContent || '');
}

function openQrModal(methodKey) {
    const key = methodKey || booking.method || 'bank';
    const cfg = PAYMENT_GATEWAY_CONFIG[key] || PAYMENT_GATEWAY_CONFIG.bank;

    const titleEl = document.getElementById('qrModalTitleText');
    if (titleEl) titleEl.textContent = cfg.modalTitle;

    const imgEl = document.getElementById('qrModalImg');
    if (imgEl) imgEl.src = cfg.qrImg;

    const ownerEl = document.getElementById('modalOwnerName');
    if (ownerEl) ownerEl.textContent = cfg.ownerName;

    const accLabelEl = document.getElementById('modalAccLabel');
    if (accLabelEl) accLabelEl.textContent = cfg.accLabel;

    const accValEl = document.getElementById('modalAccValue');
    if (accValEl) accValEl.textContent = cfg.accValue;

    const providerEl = document.getElementById('modalProviderName');
    if (providerEl) providerEl.textContent = cfg.providerName;

    const depositEl = document.getElementById('modalDepositAmount');
    if (depositEl) depositEl.textContent = fmt(booking._deposit);

    const modalRef = document.getElementById('modalTransferRef');
    if (modalRef) {
        modalRef.textContent = booking._transferRef || 'DATSAN-...';
    }

    const downloadBtn = document.getElementById('qrModalDownloadBtn');
    if (downloadBtn) {
        downloadBtn.href = cfg.qrImg;
        downloadBtn.setAttribute('download', cfg.downloadName);
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

    // Với Chuyển khoản ngân hàng: Nếu chưa xác nhận chuyển khoản trước đó
    if (booking.method === 'bank' && !booking.gatewayVerified) {
        const confirmResult = await Swal.fire({
            icon: 'question',
            title: 'Xác nhận chuyển khoản ngân hàng',
            html: `
                <div class="text-start p-3 rounded-3 bg-light mb-3" style="font-size:0.85rem;">
                    <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                        <span class="text-muted">Mã thanh toán:</span>
                        <strong class="font-monospace text-teal fs-6">${booking._transferRef}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                        <span class="text-muted">Số tiền cần cọc:</span>
                        <strong class="text-warning fs-6">${fmt(booking._deposit)}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Ngân hàng:</span>
                        <strong>MB Bank (Quân Đội)</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-muted">Số TK:</span>
                        <strong class="text-teal font-monospace">0783547289</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Chủ TK:</span>
                        <strong>TRUONG MINH TRIET</strong>
                    </div>
                </div>
                <p class="small text-muted mb-0">Bạn đã hoàn tất chuyển khoản theo mã thanh toán trên ứng dụng ngân hàng chưa?</p>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-circle-check me-1"></i> Tôi đã chuyển khoản thành công',
            cancelButtonText: 'Để tôi kiểm tra lại',
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#64748b',
            reverseButtons: true
        });

        if (!confirmResult.isConfirmed) {
            return;
        }

        markBankTransferConfirmed();
    }

    paymentSubmitting = true;
    const btns = ['btnPayMobile','btnPayDesktop'];
    btns.forEach(id => {
        const b = document.getElementById(id);
        if (b) { b.disabled = true; b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý giao dịch...'; }
    });

    const methodNames = {
        vnpay: 'VNPay',
        momo: 'MoMo',
        zalopay: 'ZaloPay',
        bank: 'Chuyển khoản VietQR'
    };
    const paymentMethodLabel = methodNames[booking.method] || 'VNPay';

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
            updateCtaLabels();
        } else if (result.code && typeof result.code === 'string' && result.code.startsWith('VOUCHER_')) {
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
            updateCtaLabels();
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
            updateCtaLabels();
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể kết nối máy chủ.', confirmButtonColor: '#0d9488' });
        btns.forEach(id => {
            const b = document.getElementById(id);
            if (b) { b.disabled = false; b.innerHTML = `<span id="${id}Text">Xác Nhận & Đặt Cọc</span>`; }
        });
        updateCtaLabels();
    } finally {
        paymentSubmitting = false;
        setPaymentButtons(!paymentReady || paymentComplete);
    }
}
