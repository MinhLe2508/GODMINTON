document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const slotId = parseInt(urlParams.get('slot_id'));
    const courtId = parseInt(urlParams.get('court_id')) || 1;
    const dateStr = urlParams.get('date') || new Date().toISOString().split('T')[0];

    const user = MockDB.getUser();
    if (!user) {
        window.location.href = 'login.html';
        return;
    }

    if (!slotId) {
        window.location.href = 'courts.html';
        return;
    }

    const slot = MockDB.getSlotById(courtId, dateStr, slotId);
    const confirmContent = document.getElementById('confirmContent');

    // Kiểm tra slot có đang được giữ bởi user không
    if (!slot || slot.status !== 'holding' || slot.holding_user_id !== user.id) {
        confirmContent.innerHTML = `
        <div class="card border rounded-4 p-5 text-center shadow-sm">
            <i class="fa-solid fa-triangle-exclamation fs-1 text-warning mb-3"></i>
            <h4 class="fw-bold">Phiên Giữ Chỗ Đã Hết Hạn</h4>
            <p class="text-muted">
                Khung giờ bạn chọn đã quá thời hạn 5 phút giữ chỗ hoặc chưa được giữ đúng quy trình.
            </p>
            <div class="d-flex justify-content-center gap-2 mt-3">
                <a href="booking.html?court_id=${courtId}" class="btn btn-teal rounded-pill px-4">Chọn lại khung giờ</a>
                <a href="courts.html" class="btn btn-outline-secondary rounded-pill px-4">Danh sách sân</a>
            </div>
        </div>`;
        return;
    }

    const depositAmount = Math.min(50000, slot.price);
    const remainAmount = slot.price - depositAmount;

    confirmContent.innerHTML = `
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h3 class="fw-extrabold mb-1">Xác Nhận Đặt Sân & Đặt Cọc</h3>
            <p class="text-muted small mb-0">Hoàn tất thủ tục giữ sân và thanh toán cọc bảo đảm</p>
        </div>
        <a href="booking.html?court_id=${courtId}&date=${dateStr}" class="btn btn-outline-secondary btn-sm rounded-pill">
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
                    <h6 class="fw-bold mb-1">${slot.court_name}</h6>
                    <div class="d-flex gap-2 mt-2">
                        <span class="badge bg-teal-subtle text-teal">${slot.sub_court_name}</span>
                        <span class="badge bg-dark">${slot.slot_date}</span>
                    </div>
                </div>

                <div class="d-flex flex-column gap-2 border-bottom pb-3 mb-3 small">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Khung giờ thi đấu:</span>
                        <strong class="text-body">${slot.start_time} - ${slot.end_time}</strong>
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
                <h6 class="fw-bold mb-2">Phương Thức Thanh Toán Cọc (Mock)</h6>
                <div class="d-flex flex-column gap-2 mb-3">
                    <div class="form-check p-3 border rounded-3 bg-body-tertiary">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="payQr" value="VietQR" checked>
                        <label class="form-check-label fw-bold small ms-2" for="payQr">
                            <i class="fa-solid fa-qrcode text-teal me-1"></i> Chuyển khoản QR Code VietQR / MoMo (Khuyên dùng)
                        </label>
                    </div>
                    <div class="form-check p-3 border rounded-3 bg-body-tertiary">
                        <input class="form-check-input" type="radio" name="paymentMethod" id="payCash" value="Tiền mặt">
                        <label class="form-check-label fw-bold small ms-2" for="payCash">
                            <i class="fa-solid fa-money-bill-wave text-success me-1"></i> Thanh toán tiền mặt tại quầy lễ tân sân
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
                    <span class="text-muted">Giá thuê sân:</span>
                    <strong>${MockDB.formatVND(slot.price)}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2 text-warning fw-bold">
                    <span>Tiền đặt cọc trước:</span>
                    <span>${MockDB.formatVND(depositAmount)}</span>
                </div>
                <div class="d-flex justify-content-between mb-3 text-muted small">
                    <span>Số tiền còn lại tại sân:</span>
                    <span>${MockDB.formatVND(remainAmount)}</span>
                </div>

                <hr class="my-3">

                <!-- Mock QR Code Box -->
                <div id="qrMockBox" class="p-3 rounded-3 bg-light text-center mb-3">
                    <div class="small fw-bold text-muted mb-2">Mã QR Thanh Toán Cọc (Mock)</div>
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=MOCK_PAYMENT_SLOT_${slot.id}" class="rounded-3 border p-1 shadow-sm mb-2" alt="QR Mock">
                    <div class="small text-muted" style="font-size:0.75rem;">
                        Ngân hàng MB Bank • STK: 99998888 • Số tiền: ${MockDB.formatVND(depositAmount)}
                    </div>
                </div>

                <button id="btnConfirmBooking" class="btn btn-teal btn-lg w-100 rounded-pill fw-bold shadow">
                    <i class="fa-solid fa-check-double me-1"></i> Hoàn Tất Đặt Sân
                </button>

                <button id="btnCancelHold" class="btn btn-outline-danger btn-sm w-100 rounded-pill mt-2">
                    <i class="fa-solid fa-xmark me-1"></i> Hủy phiên giữ chỗ này
                </button>

                <div class="mt-3 text-center small text-secondary">
                    <i class="fa-solid fa-shield-check text-teal me-1"></i>
                    Hệ thống sẽ lưu biên nhận vào lịch sử đặt sân của bạn.
                </div>
            </div>
        </div>
    </div>`;

    // Xử lý nút hoàn tất
    document.getElementById('btnConfirmBooking').addEventListener('click', () => {
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked')?.value || 'VietQR';
        const notes = document.getElementById('bookingNotes')?.value.trim() || '';
        const btn = document.getElementById('btnConfirmBooking');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xử lý giao dịch...';

        // Mô phỏng delay xử lý
        setTimeout(() => {
            const result = MockDB.confirmBooking(courtId, dateStr, slotId, paymentMethod, notes);

            if (result.status === 'success') {
                const d = result.data;
                Swal.fire({
                    icon: 'success',
                    title: 'Đặt Sân Thành Công!',
                    html: `
                        <p class="mb-3">Mã đơn đặt sân của bạn là: <strong class="text-teal fs-5">${d.booking_code}</strong></p>
                        <div class="p-3 bg-light rounded-3 text-start small mb-2">
                            <div><strong>Sân:</strong> ${d.court_name} (${d.sub_court_name})</div>
                            <div><strong>Ngày:</strong> ${d.booking_date}</div>
                            <div><strong>Giờ:</strong> ${d.time_range}</div>
                            <div><strong>Đã cọc:</strong> ${d.deposit_formatted}</div>
                        </div>
                        <p class="small text-muted mb-0">Hệ thống đã lưu vào lịch sử đặt sân của bạn.</p>`,
                    confirmButtonText: 'Xem lịch sử đặt sân',
                    confirmButtonColor: '#0d9488'
                }).then(() => {
                    window.location.href = 'history.html';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Không thể xác nhận',
                    text: result.message || 'Đã có lỗi xảy ra. Vui lòng thử lại.',
                    confirmButtonColor: '#0d9488'
                });
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-check-double me-1"></i> Hoàn Tất Đặt Sân';
            }
        }, 1000);
    });

    // Nút hủy giữ chỗ
    document.getElementById('btnCancelHold').addEventListener('click', async () => {
        const confirmResult = await Swal.fire({
            icon: 'question',
            title: 'Hủy giữ chỗ?',
            text: 'Khung giờ này sẽ lập tức được mở lại cho các khách hàng khác.',
            showCancelButton: true,
            confirmButtonText: 'Đúng, hủy ngay',
            cancelButtonText: 'Không, giữ lại',
            confirmButtonColor: '#ef4444'
        });

        if (confirmResult.isConfirmed) {
            MockDB.cancelSlot(courtId, dateStr, slotId);
            window.location.href = `booking.html?court_id=${courtId}`;
        }
    });
});