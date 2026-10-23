/* ================================================================
   CONFIRM BOOKING PAGE LOGIC - Real-time Multi-Slot API Version
   GODMINTON — Tuần 2: Hỗ trợ 1 tiếng / 1.5 tiếng / 2 tiếng trở lên
   ================================================================ */

(() => {
const escapeConfirmText = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#39;'}[character]));
const formatVND = amount => new Intl.NumberFormat('vi-VN').format(amount) + ' đ';

document.addEventListener('DOMContentLoaded', async () => {
    const urlParams = new URLSearchParams(window.location.search);
    const courtId = parseInt(urlParams.get('court_id')) || 1;
    const dateStr = urlParams.get('date') || new Date().toISOString().split('T')[0];
    const confirmContent = document.getElementById('confirmContent');

    if (!confirmContent) return;
    try {
    // Hỗ trợ cả slot_ids (mảng) hoặc slot_id (đơn)
    let slotIds = [];
    if (urlParams.get('slot_ids')) {
        slotIds = urlParams.get('slot_ids').split(',').map(s => parseInt(s.trim())).filter(n => !isNaN(n) && n > 0);
    } else if (urlParams.get('slot_id')) {
        const singleId = parseInt(urlParams.get('slot_id'));
        if (singleId > 0) slotIds = [singleId];
    }

    if (slotIds.length === 0) {
        window.location.href = 'booking.php?court_id=' + courtId;
        return;
    }

    // 1. Nạp danh sách slots thật từ MySQL qua API
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 12000);
    let slots;
    try {
        const res = await fetch(`api/slots.php?court_id=${courtId}&date=${encodeURIComponent(dateStr)}`, {
            signal: controller.signal,
            credentials: 'same-origin',
            cache: 'no-store'
        });
        if (!res.ok) throw new Error('Không tải được thông tin giữ chỗ. Vui lòng thử lại.');
        const data = await res.json();
        if (data.status !== 'success' || !Array.isArray(data.data)) {
            throw new Error('Máy chủ trả dữ liệu giữ chỗ không hợp lệ. Vui lòng thử lại.');
        }
        slotIds = [...new Set(slotIds)];
        slots = data.data.filter(slot => slotIds.includes(Number(slot.id)));
    } finally {
        clearTimeout(timeoutId);
    }

    // Sắp xếp các slot theo thứ tự thời gian
    slots.sort((a, b) => a.start_time.localeCompare(b.start_time));

    // 2. Kiểm tra tính hợp lệ: Tất cả các slot phải đang holding và do chính user giữ
    const allValidHolding = slots.length === slotIds.length && slots.every(s => s.status === 'holding' && s.is_my_hold);

    if (!allValidHolding) {
        confirmContent.innerHTML = `
        <div class="card border rounded-4 p-5 text-center shadow-sm">
            <i class="fa-solid fa-triangle-exclamation fs-1 text-warning mb-3"></i>
            <h4 class="fw-bold">Phiên Giữ Chỗ Đã Hết Hạn</h4>
            <p class="text-muted">
                Khung giờ bạn chọn đã quá thời hạn 5 phút giữ chỗ hoặc chưa được giữ đúng quy trình.
            </p>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <a href="booking.php?court_id=${courtId}&date=${dateStr}" class="btn btn-teal rounded-pill px-4">Chọn lại khung giờ</a>
                <a href="courts.php" class="btn btn-outline-secondary rounded-pill px-4">Danh sách sân</a>
            </div>
        </div>`;
        return;
    }

    const firstSlot = slots[0];
    const lastSlot = slots[slots.length - 1];
    const durationHours = slots.length * 0.5;
    const durationLabel = durationHours === 0.5 ? '30 phút' : (durationHours === 1 ? '1 tiếng' : (durationHours === 1.5 ? '1 tiếng 30 phút' : `${durationHours} tiếng`));
    const timeRangeStr = `${firstSlot.start_time} – ${lastSlot.end_time}`;

    let totalPrice = 0;
    slots.forEach(s => { totalPrice += (s.price || 50000); });
    const depositAmount = Math.round(totalPrice * 0.5);
    const remainAmount = totalPrice - depositAmount;

    // Lấy thông tin user
    const rawUser = window.__GODMINTON_USER__ || {};
    const user = Object.fromEntries(['name', 'email', 'phone'].map(key => [key, escapeConfirmText(rawUser[key])]));

    // 3. Render giao diện xác nhận
    confirmContent.innerHTML = `
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-extrabold mb-1">Xác Nhận Đặt Sân & Đặt Cọc</h3>
            <p class="text-muted small mb-0">Hoàn tất thủ tục giữ sân và thanh toán cọc bảo đảm</p>
        </div>
        <a href="booking.php?court_id=${courtId}&date=${dateStr}" class="btn btn-outline-secondary btn-sm rounded-pill">
            <i class="fa-solid fa-arrow-left me-1"></i> Quay lại
        </a>
    </div>

    <div class="row g-4">
        <!-- Cột trái: Tóm tắt đơn hàng -->
        <div class="col-md-7">
            <div class="card border rounded-4 p-4 shadow-sm mb-4">
                <h5 class="fw-bold mb-3 text-teal">
                    <i class="fa-solid fa-receipt me-2"></i>Thông Tin Lịch Đặt
                </h5>

                <div class="p-3 bg-body-tertiary rounded-3 mb-3">
                    <h6 class="fw-bold mb-1">${escapeConfirmText(firstSlot.sub_court_name)}</h6>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge bg-teal-subtle text-teal">${timeRangeStr}</span>
                        <span class="badge bg-warning text-dark">${durationLabel}</span>
                        <span class="badge bg-dark">${firstSlot.slot_date}</span>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2 border-bottom pb-3 mb-3 small">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Khung giờ thi đấu:</span>
                        <strong class="text-body">${timeRangeStr} (${durationLabel})</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Người đặt:</span>
                        <strong class="text-body">${user.name} (${user.phone || 'N/A'})</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Email nhận vé:</span>
                        <strong class="text-body">${user.email}</strong>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted">Ghi chú cho chủ sân (Tùy chọn)</label>
                    <textarea id="bookingNotes" class="form-control rounded-3" rows="2" placeholder="Ví dụ: Cần mượn thêm 2 vợt, chuẩn bị nước suối..."></textarea>
                </div>

                <!-- Lựa chọn phương thức thanh toán -->
                <h6 class="fw-bold mb-2">Phương Thức Thanh Toán Cọc</h6>
                <div class="d-flex flex-column gap-2 mb-3">
                    <div class="form-check p-3 border rounded-3 bg-body-tertiary">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="payVnpay" value="VNPay" checked>
                        <label class="form-check-label fw-bold small ms-2" for="payVnpay">
                            <i class="fa-solid fa-qrcode text-primary me-1"></i> Cổng thanh toán VNPAY-QR / ATM nội địa
                        </label>
                    </div>
                    <div class="form-check p-3 border rounded-3 bg-body-tertiary">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="payMomo" value="MoMo">
                        <label class="form-check-label fw-bold small ms-2" for="payMomo">
                            <i class="fa-solid fa-mobile-screen text-danger me-1"></i> Ví điện tử MoMo
                        </label>
                    </div>
                    <div class="form-check p-3 border rounded-3 bg-body-tertiary">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="payZalopay" value="ZaloPay">
                        <label class="form-check-label fw-bold small ms-2" for="payZalopay">
                            <i class="fa-solid fa-wallet text-info me-1"></i> Ví điện tử ZaloPay
                        </label>
                    </div>
                    <div class="form-check p-3 border rounded-3 bg-body-tertiary">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="payQr" value="Chuyển khoản VietQR">
                        <label class="form-check-label fw-bold small ms-2" for="payQr">
                            <i class="fa-solid fa-building-columns text-teal me-1"></i> Chuyển khoản Ngân hàng VietQR 24/7
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cột phải: Chi tiết cọc & Nút xác nhận -->
        <div class="col-md-5">
            <div class="card border rounded-4 p-4 shadow-sm sticky-top" style="top:85px;">
                <h5 class="fw-bold mb-3">Chi Phí Đặt Sân</h5>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Tổng giá thuê (${durationLabel}):</span>
                    <strong>${formatVND(totalPrice)}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2 text-warning fw-bold">
                    <span>Tiền đặt cọc trước (50%):</span>
                    <span>${formatVND(depositAmount)}</span>
                </div>
                <div class="d-flex justify-content-between mb-3 text-muted small">
                    <span>Số tiền còn lại thanh toán tại sân:</span>
                    <span>${formatVND(remainAmount)}</span>
                </div>

                <hr class="my-3">

                <!-- QR Code Box -->
                <div id="qrMockBox" class="p-3 rounded-3 bg-light text-center mb-3">
                    <div class="small fw-bold text-muted mb-2">Mã QR Thanh Toán Cọc</div>
                    <img src="assets/img/vnpay-payment.png" class="rounded-3 border p-1 shadow-sm mb-2" style="max-width:160px;" alt="QR Thanh Toán">
                    <div class="small text-muted" style="font-size:0.75rem;">
                        MB Bank • STK / Ví: 0783547289 • Số tiền: ${formatVND(depositAmount)}
                    </div>
                </div>

                <button id="btnConfirmBooking" class="btn btn-teal btn-lg w-100 rounded-pill fw-bold shadow">
                    <i class="fa-solid fa-check-double me-1"></i> Hoàn Tất Đặt Sân (${durationLabel})
                </button>

                <button id="btnCancelHold" class="btn btn-outline-danger btn-sm w-100 rounded-pill mt-2">
                    <i class="fa-solid fa-xmark me-1"></i> Hủy phiên giữ chỗ này
                </button>

                <div class="mt-3 text-center small text-secondary">
                    <i class="fa-solid fa-shield-check text-teal me-1"></i>
                    Hệ thống tự động lưu vào MySQL và chuyển các khung giờ sang Đã đặt.
                </div>
            </div>
        </div>
    </div>`;

    // 4. Xử lý nút hoàn tất: Gọi API POST api/bookings.php với mảng slot_ids
    document.getElementById('btnConfirmBooking').addEventListener('click', async () => {
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value || 'VNPay';
        const notes = document.getElementById('bookingNotes')?.value.trim() || '';
        const btn = document.getElementById('btnConfirmBooking');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý giao dịch...';

        try {
            const response = await fetch('api/bookings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    court_id: courtId,
                    slot_ids: slotIds,
                    payment_method: paymentMethod,
                    notes: notes
                })
            });

            const result = await response.json();

            if ((response.status === 200 || response.status === 201) && result.status === 'success') {
                const d = result.data;
                Swal.fire({
                    icon: 'success',
                    title: 'Đặt Sân Thành Công!',
                    html: `
                        <p class="mb-3">Mã đơn đặt sân: <strong class="text-teal fs-5">${d.booking_code}</strong></p>
                        <div class="p-3 bg-light rounded-3 text-start small mb-2">
                            <div><strong>Sân:</strong> ${d.court_name} (${d.sub_court_name})</div>
                            <div><strong>Ngày:</strong> ${d.booking_date}</div>
                            <div><strong>Khung giờ:</strong> ${d.time_range} (${durationLabel})</div>
                            <div><strong>Tổng tiền:</strong> ${formatVND(d.total_price)}</div>
                            <div><strong>Đã đặt cọc:</strong> ${formatVND(d.deposit_amount)}</div>
                            <div><strong>Hình thức:</strong> ${d.payment_method}</div>
                        </div>
                        <p class="small text-muted mb-0">Hệ thống đã lưu vào cơ sở dữ liệu và lịch sử đơn hàng của bạn.</p>`,
                    confirmButtonText: 'Xem lịch sử đặt sân',
                    confirmButtonColor: '#0d9488'
                }).then(() => {
                    window.location.href = 'history.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể xác nhận đặt sân',
                    text: result.message || 'Phiên giữ chỗ của bạn có thể đã hết hạn. Vui lòng chọn lại khung giờ.',
                    confirmButtonColor: '#0d9488'
                });
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Hoàn Tất Đặt Sân';
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi kết nối',
                text: 'Không thể kết nối tới máy chủ API. Vui lòng kiểm tra lại kết nối XAMPP!',
                confirmButtonColor: '#0d9488'
            });
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Hoàn Tất Đặt Sân';
        }
    });

    // 5. Nút hủy giữ chỗ: Giải phóng toàn bộ các slot đang giữ
    document.getElementById('btnCancelHold').addEventListener('click', async () => {
        const confirmResult = await Swal.fire({
            icon: 'question',
            title: 'Hủy giữ chỗ?',
            text: 'Các khung giờ này sẽ lập tức được mở lại cho các khách hàng khác trên hệ thống.',
            showCancelButton: true,
            confirmButtonText: 'Đúng, hủy ngay',
            cancelButtonText: 'Không, giữ lại',
            confirmButtonColor: '#ef4444'
        });

        if (confirmResult.isConfirmed) {
            try {
                await fetch('api/hold.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ slot_ids: slotIds, action: 'release' })
                });
            } catch (e) {}
            window.location.href = `booking.php?court_id=${courtId}&date=${dateStr}`;
        }
    });
    } catch (error) {
        console.error('Lỗi khởi tạo trang xác nhận:', error);
        const message = error.name === 'AbortError'
            ? 'Máy chủ phản hồi quá lâu. Vui lòng thử tải lại.'
            : 'Không thể tải thông tin xác nhận. Vui lòng thử lại hoặc chọn lại khung giờ.';
        confirmContent.innerHTML = `
            <div class="card border rounded-4 p-5 text-center shadow-sm" role="alert">
                <h4 class="fw-bold">Không thể xác minh phiên giữ chỗ</h4>
                <p class="text-muted">${message}</p>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <a href="booking.php?court_id=${courtId}&date=${encodeURIComponent(dateStr)}" class="btn btn-teal rounded-pill px-4">Quay lại chọn giờ</a>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" onclick="window.location.reload()">Thử lại</button>
                </div>
            </div>`;
    }
});
})();
