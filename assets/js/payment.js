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
    bank: {
        label: 'Chuyển khoản VietQR',
        summaryTitle: 'Chuyển khoản VietQR 24/7 (MB Bank)',
        summaryDesc: 'Quét mã VietQR tự động từ hơn 40 ứng dụng ngân hàng & ví điện tử.',
        badgeText: 'Tự động SePay 24/7',
        badgeClass: 'bg-success-subtle text-success border border-success-subtle',
        iconHtml: '<i class="fa-solid fa-building-columns fs-4" style="color:#0d9488;"></i>',
        checkoutTitle: 'Chuyển Khoản Ngân Hàng VietQR',
        checkoutSub: 'Xác nhận tức thì qua cổng SePay 24/7 · MB Bank',
        logoUrl: 'assets/img/logo-teal.svg',
        headerClass: 'bank-header',
    },
    vnpay: {
        label: 'VNPay (VNPAY-QR)',
        summaryTitle: 'Cổng VNPAY (Quét mã VietQR)',
        summaryDesc: 'Mở ứng dụng ngân hàng hoặc ví VNPAY để quét mã thanh toán VietQR.',
        badgeText: 'VietQR / VNPAY-QR',
        badgeClass: 'bg-primary-subtle text-primary border border-primary-subtle',
        iconHtml: '<i class="fa-solid fa-qrcode fs-4" style="color:#005baa;"></i>',
        checkoutTitle: 'Cổng Thanh Toán VNPAY-QR',
        checkoutSub: 'Hỗ trợ quét qua app ngân hàng & ví VNPAY · Tự động 24/7',
        logoUrl: 'https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-VNPAY-QR.png',
        headerClass: 'vnpay-header',
    },
    momo: {
        label: 'MoMo (Quét VietQR)',
        summaryTitle: 'Ví điện tử MoMo',
        summaryDesc: 'Mở ứng dụng MoMo chọn mục "Quét Mã" để quét mã VietQR tự động.',
        badgeText: 'Ví MoMo 24/7',
        badgeClass: 'bg-danger-subtle text-danger border border-danger-subtle',
        iconHtml: '<i class="fa-solid fa-mobile-screen fs-4" style="color:#ae2070;"></i>',
        checkoutTitle: 'Cổng Thanh Toán Ví MoMo',
        checkoutSub: 'Quét VietQR từ siêu ứng dụng MoMo · Tự động 24/7',
        logoUrl: 'https://upload.wikimedia.org/wikipedia/vi/f/fe/MoMo_Logo.png',
        headerClass: 'momo-header',
    },
    zalopay: {
        label: 'ZaloPay (Quét VietQR)',
        summaryTitle: 'Ví điện tử ZaloPay',
        summaryDesc: 'Mở ứng dụng Zalo hoặc ZaloPay quét mã VietQR tiện lợi.',
        badgeText: 'ZaloPay 24/7',
        badgeClass: 'bg-info-subtle text-info border border-info-subtle',
        iconHtml: '<i class="fa-solid fa-wallet fs-4" style="color:#0068ff;"></i>',
        checkoutTitle: 'Cổng Thanh Toán Ví ZaloPay',
        checkoutSub: 'Ví ZaloPay & Ứng dụng Zalo · Tự động 24/7',
        logoUrl: 'https://cdn.haitrieu.com/wp-content/uploads/2022/10/Logo-ZaloPay-Square.png',
        headerClass: 'zalopay-header',
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
    syncPaymentMethodCard();
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

        const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
        if (membership.tier === 'silver') {
            tierName = 'Bạc (Silver)';
            bannerBg = isDark ? 'linear-gradient(135deg, rgba(148,163,184,0.18), rgba(203,213,225,0.25))' : 'linear-gradient(135deg, rgba(148,163,184,0.22), rgba(226,232,240,0.45))';
            bannerBorder = isDark ? '#94a3b8' : '#64748b';
            bannerTextColor = isDark ? '#f1f5f9' : '#1e293b';
            iconClass = 'fa-solid fa-shield-halved text-secondary';
            rowColor = isDark ? '#cbd5e1' : '#475569';
        } else if (membership.tier === 'gold') {
            tierName = 'Vàng (Gold)';
            bannerBg = isDark ? 'linear-gradient(135deg, rgba(245,158,11,0.2), rgba(251,191,36,0.3))' : 'linear-gradient(135deg, rgba(245,158,11,0.15), rgba(251,191,36,0.28))';
            bannerBorder = '#f59e0b';
            bannerTextColor = isDark ? '#fef3c7' : '#92400e';
            iconClass = 'fa-solid fa-crown text-warning';
            rowColor = isDark ? '#fbbf24' : '#d97706';
        } else if (membership.tier === 'platinum') {
            tierName = 'Bạch Kim (Platinum)';
            bannerBg = isDark ? 'linear-gradient(135deg, rgba(14,165,233,0.18), rgba(56,189,248,0.28))' : 'linear-gradient(135deg, rgba(51,65,85,0.18), rgba(100,116,139,0.3))';
            bannerBorder = isDark ? '#38bdf8' : '#0284c7';
            bannerTextColor = isDark ? '#f0f9ff' : '#0369a1';
            iconClass = 'fa-solid fa-gem text-info';
            rowColor = isDark ? '#38bdf8' : '#0284c7';
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

/* ---- SePay Real-time Payment State ---- */
let activePayment = null;
let activeBooking = null;
let paymentPollInterval = null;

/* ---- Payment method selection (VietQR, VNPay, MoMo, ZaloPay) ---- */
function selectPM(el) {
    document.querySelectorAll('.pm-btn').forEach(b => b.classList.remove('selected'));
    el.classList.add('selected');
    el.blur();
    booking.method = el.dataset.method || 'bank';
    syncPaymentMethodCard();
}

function syncPaymentMethodCard() {
    const cfg = PAYMENT_GATEWAY_CONFIG[booking.method] || PAYMENT_GATEWAY_CONFIG.bank;

    const titleEl = document.getElementById('pmSummaryTitle');
    if (titleEl) titleEl.textContent = cfg.summaryTitle;

    const descEl = document.getElementById('pmSummaryDesc');
    if (descEl) descEl.textContent = cfg.summaryDesc;

    const badgeEl = document.getElementById('pmSummaryBadge');
    if (badgeEl) {
        badgeEl.textContent = cfg.badgeText;
        badgeEl.className = 'badge px-2 py-0 ' + cfg.badgeClass;
    }

    const iconEl = document.getElementById('pmSummaryIcon');
    if (iconEl) iconEl.innerHTML = cfg.iconHtml;

    const bdrMethodEl = document.getElementById('bdrMethod');
    if (bdrMethodEl) bdrMethodEl.textContent = cfg.label;

    updateCtaLabels();
}

/* ---- Cập nhật nhãn và trạng thái nút CTA chính ---- */
function updateCtaLabels() {
    const desktopText = document.getElementById('btnPayDesktopText');
    const mobileText = document.getElementById('btnPayMobileText');
    const desktopBtn = document.getElementById('btnPayDesktop');
    const mobileBtn = document.getElementById('btnPayMobile');

    let labelHtml = '';
    if (activePayment && activePayment.status === 'pending') {
        labelHtml = '<i class="fa-solid fa-qrcode me-2"></i> Tiếp tục thanh toán cọc';
    } else {
        labelHtml = '<i class="fa-solid fa-wallet me-2"></i> Thanh Toán & Đặt Cọc';
    }

    if (desktopText) desktopText.innerHTML = labelHtml; else if (desktopBtn) desktopBtn.innerHTML = `<span id="btnPayDesktopText">${labelHtml}</span>`;
    if (mobileText) mobileText.innerHTML = labelHtml; else if (mobileBtn) mobileBtn.innerHTML = `<span id="btnPayMobileText">${labelHtml}</span>`;

    [desktopBtn, mobileBtn].forEach(btn => {
        if (!btn) return;
        if (activePayment && activePayment.status === 'pending') {
            btn.classList.add('pulse-cta');
        } else {
            btn.classList.remove('pulse-cta');
        }
    });
}

/* ---- Mở Popup Cổng Thanh Toán SePay (VietQR / VNPay / MoMo / ZaloPay) ---- */
function openPaymentGatewayModal(gatewayKey) {
    // Chỉ trình bày yêu cầu thanh toán đã được máy chủ tạo.
    if (!activePayment) return;
    const key = gatewayKey || booking.method || 'bank';
    const cfg = PAYMENT_GATEWAY_CONFIG[key] || PAYMENT_GATEWAY_CONFIG.bank;

    const header = document.getElementById('gwModalHeader');
    if (header) {
        header.className = 'gw-modal-header p-3 text-white ' + (cfg.headerClass || '');
    }
    const logo = document.getElementById('gwModalLogo');
    if (logo) logo.src = cfg.logoUrl;

    const title = document.getElementById('gwCheckoutLabel');
    if (title) title.textContent = cfg.checkoutTitle;

    const sub = document.getElementById('gwCheckoutSub');
    if (sub) sub.textContent = cfg.checkoutSub;

    const qrImg = document.getElementById('gwModalQrImg');
    const amt = document.getElementById('gwModalAmount');
    const ref = document.getElementById('gwModalRef');
    const pLabel = document.getElementById('gwModalProviderLabel');
    const pName = document.getElementById('gwModalProviderName');
    const aLabel = document.getElementById('gwModalAccLabel');
    const aVal = document.getElementById('gwModalAccValue');
    const owner = document.getElementById('gwModalOwnerName');
    const dlBtn = document.getElementById('gwModalDownloadQr');
    const devSimWrap = document.getElementById('devSimulateWrap');

    if (qrImg) qrImg.src = activePayment.qr_url;
    if (amt) amt.textContent = fmt(activePayment.amount);
    if (ref) ref.textContent = activePayment.payment_code;
    if (pLabel) pLabel.textContent = 'Ngân hàng thụ hưởng:';
    if (pName) pName.textContent = activePayment.bank.name;
    if (aLabel) aLabel.textContent = 'Số tài khoản:';
    if (aVal) aVal.textContent = activePayment.bank.account_number;
    if (owner) owner.textContent = activePayment.bank.account_name;
    if (dlBtn) {
        dlBtn.href = activePayment.qr_url;
        dlBtn.setAttribute('download', 'VietQR-' + activePayment.payment_code + '.png');
    }
    if (devSimWrap) {
        devSimWrap.style.display = activePayment.simulate_available ? 'block' : 'none';
    }

    const modalEl = document.getElementById('checkoutGatewayModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
}

function openPaymentModalPreview() {
    if (activePayment && activePayment.status === 'pending') {
        openPaymentGatewayModal(booking.method);
        startPaymentPolling(activePayment.payment_code);
    } else {
        handlePaymentCta();
    }
}

/* ---- Bước: Người dùng nhấn "Thanh Toán & Đặt Cọc" ---- */
async function handlePaymentCta() {
    if (!paymentReady || paymentSubmitting || paymentComplete) return;

    // Nếu đã tạo mã thanh toán và còn hiệu lực -> mở modal & tiếp tục poll
    if (activePayment && activePayment.status === 'pending') {
        openPaymentGatewayModal(booking.method);
        startPaymentPolling(activePayment.payment_code);
        return;
    }

    const name  = document.getElementById('billingName')?.value.trim();
    const phone = document.getElementById('billingPhone')?.value.trim();
    const email = document.getElementById('billingEmail')?.value.trim();
    const note  = document.getElementById('billingNote')?.value.trim() || '';
    const agree = document.getElementById('agreeTerms')?.checked;

    if (!name) {
        Swal.fire({
            icon: 'warning',
            title: 'Thiếu thông tin người đặt',
            text: 'Vui lòng nhập họ và tên của bạn.',
            confirmButtonColor: '#0d9488'
        }).then(() => document.getElementById('billingName')?.focus());
        return;
    }
    if (!phone || !/^(0|\+84)\d{9,10}$/.test(phone.replace(/\s+/g, ''))) {
        Swal.fire({
            icon: 'warning',
            title: 'Số điện thoại không hợp lệ',
            text: 'Vui lòng nhập số điện thoại liên hệ (10 chữ số).',
            confirmButtonColor: '#0d9488'
        }).then(() => document.getElementById('billingPhone')?.focus());
        return;
    }
    if (!email || !/\S+@\S+\.\S+/.test(email)) {
        Swal.fire({
            icon: 'warning',
            title: 'Email nhận vé không hợp lệ',
            text: 'Vui lòng nhập đúng địa chỉ email để nhận hóa đơn và thông tin đặt sân.',
            confirmButtonColor: '#0d9488'
        }).then(() => document.getElementById('billingEmail')?.focus());
        return;
    }
    if (!agree) {
        Swal.fire({
            icon: 'info',
            title: 'Điều khoản đặt sân & Hoàn cọc',
            text: 'Vui lòng đánh dấu đồng ý với Điều khoản đặt sân & Chính sách hoàn cọc của GODMINTON trước khi thanh toán.',
            showCancelButton: true,
            confirmButtonText: 'Đồng ý & Tiếp tục',
            cancelButtonText: 'Xem chi tiết điều khoản',
            confirmButtonColor: '#0d9488'
        }).then(res => {
            if (res.isConfirmed) {
                const chk = document.getElementById('agreeTerms');
                if (chk) chk.checked = true;
                handlePaymentCta();
            } else if (res.dismiss === Swal.DismissReason.cancel) {
                openBookingTermsModal();
            }
        });
        return;
    }

    paymentSubmitting = true;
    ['btnPayMobile', 'btnPayDesktop'].forEach(id => {
        const b = document.getElementById(id);
        if (b) {
            b.disabled = true;
            b.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang tạo mã thanh toán SePay...';
        }
    });

    const methodNames = {
        bank: 'Chuyển khoản VietQR',
        vnpay: 'VNPay',
        momo: 'MoMo',
        zalopay: 'ZaloPay'
    };
    const paymentMethodLabel = methodNames[booking.method] || 'Chuyển khoản VietQR';

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
            const bData = result.data;
            activeBooking = bData;

            // Đơn cọc 0đ (giảm 100%) -> Xác nhận ngay
            if (bData.booking_status === 'confirmed') {
                renderBookingSuccess(bData, paymentMethodLabel, email);
                return;
            }

            // Đơn có yêu cầu thanh toán cọc SePay
            if (bData.payment) {
                activePayment = bData.payment;

                // Đồng bộ countdown giữ chỗ theo thời hạn payment SePay
                if (activePayment.expires_in_seconds) {
                    clearInterval(countdownTimer);
                    startCountdown(activePayment.expires_in_seconds);
                }

                updateCtaLabels();
                openPaymentGatewayModal(booking.method);
                startPaymentPolling(activePayment.payment_code);
            }
        } else if (response.status === 401 || result.code === 401) {
            Swal.fire({
                icon: 'warning',
                title: 'Yêu cầu đăng nhập',
                text: 'Vui lòng đăng nhập để hoàn tất tạo đơn đặt sân.',
                confirmButtonColor: '#0d9488'
            }).then(() => {
                window.location.href = 'auth.php?redirect=' + encodeURIComponent('payment.php' + window.location.search);
            });
        } else if (result.code && typeof result.code === 'string' && result.code.startsWith('VOUCHER_')) {
            removeVoucher(true);
            showVoucherError(result.message);
            Swal.fire({
                icon: 'warning',
                title: 'Mã giảm giá không còn hiệu lực',
                text: (result.message || 'Mã đã hết hạn hoặc hết lượt.') + ' Hệ thống đã chuyển về mức giá gốc, vui lòng kiểm tra và xác nhận lại.',
                confirmButtonColor: '#0d9488'
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Không thể tạo đơn đặt sân',
                text: result.message || 'Phiên giữ chỗ có thể đã hết hạn hoặc không hợp lệ. Vui lòng chọn lại khung giờ.',
                confirmButtonColor: '#0d9488'
            });
        }
    } catch (err) {
        console.error('Lỗi tạo booking:', err);
        Swal.fire({ icon: 'error', title: 'Lỗi kết nối', text: 'Không thể kết nối máy chủ.', confirmButtonColor: '#0d9488' });
    } finally {
        paymentSubmitting = false;
        setPaymentButtons(!paymentReady || paymentComplete);
        updateCtaLabels();
    }
}

function handlePayment() {
    handlePaymentCta();
}

/* ---- Polling kiểm tra trạng thái thanh toán từ SePay mỗi 3 giây ---- */
function startPaymentPolling(paymentCode) {
    if (paymentPollInterval) {
        clearInterval(paymentPollInterval);
    }
    if (!paymentCode) return;

    paymentPollInterval = setInterval(async () => {
        if (paymentComplete) {
            clearInterval(paymentPollInterval);
            return;
        }

        try {
            const res = await fetch(`api/payments/status.php?code=${encodeURIComponent(paymentCode)}`, {
                cache: 'no-store'
            });
            if (!res.ok) return;
            const json = await res.json();
            if (json.status !== 'success' || !json.data) return;

            const pData = json.data;
            if (pData.status === 'paid') {
                clearInterval(paymentPollInterval);
                onPaymentSuccessConfirmed(pData);
            } else if (pData.status === 'expired' || pData.booking_status === 'cancelled') {
                clearInterval(paymentPollInterval);
                onPaymentExpired();
            }
        } catch (e) {
            console.warn('Lỗi polling SePay:', e);
        }
    }, 3000);
}

function onPaymentSuccessConfirmed(pData) {
    paymentComplete = true;
    paymentReady = false;
    clearInterval(countdownTimer);

    // Hiệu ứng nhận tiền thành công trong modal
    const liveCard = document.getElementById('gwPaymentStatusCard');
    if (liveCard) {
        liveCard.style.background = '#dcfce7';
        liveCard.style.borderColor = '#4ade80';
        liveCard.innerHTML = `
            <div class="d-flex align-items-center justify-content-center gap-2 text-success fw-bold py-1">
                <i class="fa-solid fa-circle-check fs-4"></i>
                <span>Đã nhận thanh toán thành công! Đang xuất hóa đơn...</span>
            </div>
        `;
    }

    setTimeout(() => {
        const modalEl = document.getElementById('checkoutGatewayModal');
        if (modalEl && typeof bootstrap !== 'undefined') {
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
        }

        renderBookingSuccess(activeBooking || pData, 'Chuyển khoản SePay', document.getElementById('billingEmail')?.value);

        if (window.Swal) {
            Swal.fire({
                icon: 'success',
                title: 'Đặt cọc thành công!',
                text: 'Hệ thống đã nhận diện biến động số dư qua SePay và tự động xác nhận đơn của bạn.',
                timer: 3500,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        }
    }, 1200);
}

function onPaymentExpired() {
    const modalEl = document.getElementById('checkoutGatewayModal');
    if (modalEl && typeof bootstrap !== 'undefined') {
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    Swal.fire({
        icon: 'warning',
        title: 'Hết hạn thanh toán',
        text: 'Đơn đặt cọc đã hết thời gian giữ chỗ (15 phút). Vui lòng chọn lại khung giờ.',
        confirmButtonText: 'Quay lại đặt sân',
        confirmButtonColor: '#0d9488'
    }).then(() => {
        window.location.href = `booking.php?court_id=${booking.courtId}`;
    });
}

function renderBookingSuccess(bData, methodLabel, email) {
    paymentComplete = true;
    paymentReady = false;
    clearInterval(countdownTimer);

    document.getElementById('successCode').textContent  = bData.booking_code || 'GDM';
    document.getElementById('sdCourt').textContent      = bData.court_name || booking.courtName;
    document.getElementById('sdDate').textContent       = document.getElementById('bdrDate')?.textContent || '';
    document.getElementById('sdTime').textContent       = (bData.time_range ? `${bData.time_range} (${booking.duration} giờ)` : document.getElementById('bdrTime')?.textContent);
    document.getElementById('sdDeposit').textContent    = fmt(bData.deposit_amount || booking._deposit);
    document.getElementById('sdMethod').textContent     = methodLabel || 'Chuyển khoản SePay';
    document.getElementById('sdEmail').textContent      = email || (document.getElementById('billingEmail')?.value || '');

    const successOverlay = document.getElementById('successOverlay');
    const paymentNote = document.getElementById('successPaymentNote');
    if (paymentNote) paymentNote.hidden = true; // Thanh toán thật qua SePay -> không hiện note demo!
    if (successOverlay) {
        successOverlay.inert = false;
        successOverlay.ariaHidden = 'false';
        successOverlay.classList.add('show');
        successOverlay.scrollTop = 0;
        successOverlay.focus?.();
    }
    document.body.style.overflow = 'hidden';
}

/* ---- Dev test simulator ---- */
async function devSimulatePayment() {
    if (!activePayment || !activePayment.payment_code) return;
    try {
        toast('Đang gửi tín hiệu SePay giả lập...');
        const res = await fetch('api/payments/simulate.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ code: activePayment.payment_code })
        });
        const json = await res.json();
        if (json.status === 'success') {
            toast('Đã nhận diện tiền vào! Đang cập nhật...');
        } else {
            Swal.fire({ icon: 'warning', title: 'Giả lập lỗi', text: json.message || 'Lỗi giả lập.' });
        }
    } catch (e) {
        console.error('Lỗi giả lập:', e);
    }
}

function copyText(txt) {
    navigator.clipboard?.writeText(txt).then(() => toast('Đã sao chép!'));
}
function copyById(id) {
    const el = document.getElementById(id);
    if (el) copyText(el.textContent.trim());
}
function copyTransferRef() {
    copyById('gwModalRef');
}

function toast(msg) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: msg,
        showConfirmButton: false,
        timer: 1600,
        timerProgressBar: true
    });
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

function setPaymentButtons(disabled) {
    ['btnPayMobile', 'btnPayDesktop'].forEach(id => {
        const button = document.getElementById(id);
        if (button) button.disabled = disabled;
    });
}

/* ---- Hiển thị chi tiết Điều khoản đặt sân & Chính sách hoàn cọc ---- */
function openBookingTermsModal(e) {
    if (e && typeof e.preventDefault === 'function') {
        e.preventDefault();
    }
    if (typeof Swal === 'undefined') return;

    Swal.fire({
        title: 'Điều khoản đặt sân & Chính sách hoàn cọc',
        width: 620,
        html: `
            <div class="text-start small" style="line-height:1.6; color:var(--text-primary,#1e293b);">
                <div class="p-3 rounded-3 mb-2 border" style="background:rgba(13,148,136,0.06); border-color:rgba(13,148,136,0.25) !important;">
                    <div class="fw-bold text-teal mb-1"><i class="fa-solid fa-qrcode me-1"></i>1. Đặt cọc giữ sân (50% giá trị đơn)</div>
                    <div class="text-muted">Khách hàng thanh toán trước <strong>50% tiền cọc</strong> qua mã VietQR / SePay trong thời gian giữ chỗ. <strong>50% còn lại</strong> thanh toán trực tiếp tại sân khi đến nhận sân (Check-in).</div>
                </div>

                <div class="p-3 rounded-3 mb-2 border" style="background:rgba(16,185,129,0.06); border-color:rgba(16,185,129,0.25) !important;">
                    <div class="fw-bold text-success mb-1"><i class="fa-solid fa-circle-check me-1"></i>2. Trường hợp được hoàn 100% tiền cọc</div>
                    <ul class="mb-0 ps-3 text-muted">
                        <li><strong>Ân hạn 15 phút đầu sau khi đặt:</strong> Nếu đặt nhầm ca hoặc đổi ý trong vòng <strong>15 phút</strong> kể từ lúc tạo đơn (miễn thời điểm hủy còn cách giờ vào sân tối thiểu <strong>1 giờ</strong>).</li>
                        <li><strong>Hủy sớm trước giờ thi đấu &ge; 6 giờ:</strong> Chủ động hủy đơn trước giờ bắt đầu ca chơi từ <strong>6 giờ trở lên</strong>.</li>
                    </ul>
                </div>

                <div class="p-3 rounded-3 mb-2 border" style="background:rgba(245,158,11,0.08); border-color:rgba(245,158,11,0.3) !important;">
                    <div class="fw-bold text-warning mb-1"><i class="fa-solid fa-triangle-exclamation me-1"></i>3. Trường hợp giữ cọc &amp; Khóa hủy đơn</div>
                    <ul class="mb-0 ps-3 text-muted">
                        <li><strong>Hủy muộn (từ 2 giờ đến dưới 6 giờ trước giờ chơi):</strong> Đơn vẫn được hủy để giải phóng sân, nhưng khoản cọc 50% <strong>không hoàn lại</strong> nhằm bù chi phí trống sân sát giờ.</li>
                        <li><strong>Dưới 2 giờ trước giờ thi đấu:</strong> Hệ thống khóa chức năng hủy đơn đối với khách thường.</li>
                    </ul>
                </div>

                <div class="p-3 rounded-3 mb-2 border" style="background:rgba(139,92,246,0.07); border-color:rgba(139,92,246,0.28) !important;">
                    <div class="fw-bold mb-1" style="color:#7c3aed;"><i class="fa-solid fa-crown me-1"></i>4. Đặc quyền Hội viên VIP (Silver / Gold / Platinum)</div>
                    <div class="text-muted">Hội viên đang có gói hiệu lực được ưu tiên hủy đơn và <strong>hoàn 100% tiền cọc</strong> đến trước giờ thi đấu <strong>30 phút</strong>.</div>
                </div>

                <div class="p-3 rounded-3 border" style="background:rgba(148,163,184,0.08);">
                    <div class="fw-bold mb-1"><i class="fa-solid fa-building-columns me-1 text-teal"></i>5. Quy trình nhận tiền hoàn cọc tự động qua SePay</div>
                    <div class="text-muted">Khi hủy đơn đủ điều kiện hoàn cọc, bạn chỉ cần nhập <strong>Ngân hàng &amp; Số tài khoản nhận tiền</strong>. Hệ thống tự tạo mã VietQR để Chủ sân/Admin chuyển khoản hoàn cọc và tự động cập nhật trạng thái <strong>"Đã hoàn cọc"</strong> ngay khi giao dịch hoàn tất.</div>
                </div>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa-solid fa-check me-1"></i> Tôi đã hiểu & Đồng ý',
        cancelButtonText: 'Đóng',
        confirmButtonColor: '#0d9488'
    }).then(res => {
        if (res.isConfirmed) {
            const chk = document.getElementById('agreeTerms');
            if (chk) chk.checked = true;
        }
    });
}
