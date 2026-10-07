/**
 * BADMINTON HUB - REAL-TIME BOOKING ENGINE (Static Version)
 * Sử dụng MockDB.LocalStorage thay vì Fetch API thực
 */

class BookingEngine {
    constructor(options) {
        this.courtId = options.courtId;
        this.selectedDate = options.selectedDate || new Date().toISOString().split('T')[0];
        this.containerId = options.containerId || 'slotContainer';
        this.pollInterval = options.pollInterval || 5000;
        
        this.timerInterval = null;
        this.pollTimer = null;
        this.myHeldSlotId = null;
        this.holdExpireTimestamp = null;

        this.init();
    }

    init() {
        const dateInput = document.getElementById('bookingDateInput');
        if (dateInput) {
            dateInput.value = this.selectedDate;
            dateInput.addEventListener('change', (e) => {
                this.selectedDate = e.target.value;
                this.fetchAvailability(true);
            });
        }

        this.fetchAvailability(true);
        this.startPolling();

        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.fetchAvailability(false);
            }
        });
    }

    startPolling() {
        if (this.pollTimer) clearInterval(this.pollTimer);
        this.pollTimer = setInterval(() => {
            this.fetchAvailability(false);
        }, this.pollInterval);
    }

    stopPolling() {
        if (this.pollTimer) clearInterval(this.pollTimer);
    }

    /**
     * Thay thế Fetch API bằng MockDB.getSlotsForCourt()
     */
    fetchAvailability(showLoading = false) {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        if (showLoading && !container.hasChildNodes()) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-teal" role="status"></div>
                    <div class="mt-2 text-muted">Đang kiểm tra tình trạng sân thời gian thực...</div>
                </div>`;
        }

        // Lấy từ LocalStorage (mock real-time)
        const slots = MockDB.getSlotsForCourt(this.courtId, this.selectedDate);
        this.renderSlots(slots);
    }

    renderSlots(slots) {
        const container = document.getElementById(this.containerId);
        if (!container) return;

        if (!slots || slots.length === 0) {
            container.innerHTML = `
                <div class="alert alert-info border-0 rounded-4 text-center py-4">
                    <i class="fa-regular fa-calendar-xmark fs-2 mb-2"></i>
                    <h5>Chưa có khung giờ mở bán</h5>
                    <p class="mb-0 text-muted">Chủ sân chưa tạo khung giờ cho ngày ${this.selectedDate}. Vui lòng chọn ngày khác.</p>
                </div>`;
            this.updateHoldBanner(null);
            return;
        }

        // Nhóm slot theo tên thảm sân
        const groups = {};
        let myCurrentHeldSlot = null;

        slots.forEach(slot => {
            const groupName = slot.sub_court_name || 'Sân chính';
            if (!groups[groupName]) groups[groupName] = [];
            groups[groupName].push(slot);

            if (slot.is_my_hold) {
                myCurrentHeldSlot = slot;
            }
        });

        this.updateHoldBanner(myCurrentHeldSlot);

        let html = '';
        for (const [courtName, courtSlots] of Object.entries(groups)) {
            html += `
            <div class="sub-court-section">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h6 class="fw-bold mb-0 text-teal">
                        <i class="fa-solid fa-vector-square me-2"></i>${courtName}
                    </h6>
                    <span class="badge bg-light text-dark border">${courtSlots.length} khung giờ</span>
                </div>
                <div class="slot-grid">`;

            courtSlots.forEach(slot => {
                let statusClass = 'slot-available';
                let statusText = 'Còn trống';
                let icon = 'fa-regular fa-circle-check';
                let clickAction = `onclick="window.bookingEngine.handleSlotClick(${slot.id})"`;

                if (slot.status === 'booked') {
                    statusClass = 'slot-booked';
                    statusText = 'Đã đặt';
                    icon = 'fa-solid fa-ban';
                    clickAction = 'onclick="window.bookingEngine.handleBookedClick()"';
                } else if (slot.status === 'blocked') {
                    statusClass = 'slot-booked';
                    statusText = 'Ngừng nhận';
                    icon = 'fa-solid fa-lock';
                    clickAction = 'onclick="window.bookingEngine.handleBookedClick()"';
                } else if (slot.is_my_hold) {
                    statusClass = 'slot-my-hold';
                    statusText = 'Bạn đang giữ';
                    icon = 'fa-solid fa-user-lock';
                    clickAction = `onclick="window.bookingEngine.handleMyHoldClick(${slot.id})"`;
                } else if (slot.status === 'holding') {
                    statusClass = 'slot-holding';
                    statusText = 'Đang giữ';
                    icon = 'fa-solid fa-stopwatch';
                    clickAction = 'onclick="window.bookingEngine.handleHoldingClick()"';
                }

                html += `
                    <div class="slot-card ${statusClass}" ${clickAction} id="slot-${slot.id}" data-id="${slot.id}">
                        <div class="slot-time">${slot.time_range}</div>
                        <div class="slot-price">${slot.price_formatted}</div>
                        <div class="slot-badge-status">
                            <i class="${icon}"></i> ${statusText}
                        </div>
                    </div>`;
            });

            html += `
                </div>
            </div>`;
        }

        container.innerHTML = html;
    }

    /**
     * Xử lý khi bấm vào Slot còn trống - Dùng MockDB.holdSlot()
     */
    handleSlotClick(slotId) {
        const slotEl = document.getElementById(`slot-${slotId}`);
        if (slotEl) slotEl.classList.add('slot-selected');

        const result = MockDB.holdSlot(this.courtId, this.selectedDate, slotId);

        // TRƯỜNG HỢP 1: GIỮ CHỖ THÀNH CÔNG
        if (result.status === 'success' && result.code === 200) {
            this.myHeldSlotId = slotId;
            this.holdExpireTimestamp = Date.now() + (result.data.expires_in * 1000);

            Swal.fire({
                icon: 'success',
                title: 'Giữ chỗ thành công!',
                html: `
                    <p class="mb-2">Bạn có <strong>5 phút</strong> để hoàn tất đặt cọc và xác nhận đơn.</p>
                    <div class="p-3 bg-light rounded-3 text-start small">
                        <div><strong>Sân:</strong> ${result.data.sub_court_name}</div>
                        <div><strong>Giờ:</strong> ${result.data.start_time} - ${result.data.end_time}</div>
                        <div><strong>Giá:</strong> ${result.data.price_formatted}</div>
                    </div>`,
                showCancelButton: true,
                confirmButtonText: '<i class="fa-solid fa-arrow-right me-1"></i> Xác nhận & Đặt cọc',
                cancelButtonText: 'Chọn thêm / Giữ lại',
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#64748b'
            }).then((res) => {
                if (res.isConfirmed) {
                    window.location.href = `confirm.html?slot_id=${slotId}&court_id=${this.courtId}&date=${this.selectedDate}`;
                }
            });

            this.fetchAvailability(false);
            return;
        }

        // TRƯỜNG HỢP 2: TRANH CHẤP RACE CONDITION (409)
        if (result.code === 409) {
            Swal.fire({
                icon: 'warning',
                title: 'Khung giờ vừa có người giữ!',
                text: result.message,
                confirmButtonColor: '#0d9488',
                confirmButtonText: 'Đồng ý'
            });
            this.fetchAvailability(false);
            return;
        }

        // TRƯỜNG HỢP 3: CHƯA ĐĂNG NHẬP (401)
        if (result.code === 401) {
            Swal.fire({
                icon: 'info',
                title: 'Yêu cầu đăng nhập',
                text: 'Vui lòng đăng nhập để tiến hành giữ chỗ và đặt sân.',
                showCancelButton: true,
                confirmButtonText: 'Đăng nhập ngay',
                cancelButtonText: 'Để sau',
                confirmButtonColor: '#0d9488'
            }).then((r) => {
                if (r.isConfirmed) window.location.href = 'login.html';
            });
            if (slotEl) slotEl.classList.remove('slot-selected');
            return;
        }

        // Lỗi khác
        Swal.fire({
            icon: 'error',
            title: 'Không thể giữ chỗ',
            text: result.message || 'Đã có lỗi xảy ra. Vui lòng thử lại.',
            confirmButtonColor: '#0d9488'
        });
        if (slotEl) slotEl.classList.remove('slot-selected');
    }

    handleBookedClick() {
        Swal.fire({
            icon: 'info',
            title: 'Khung giờ đã được đặt',
            text: 'Khung giờ này đã có khách đặt thành công. Vui lòng chọn các khung giờ màu xanh còn trống.',
            confirmButtonColor: '#0d9488'
        });
    }

    handleHoldingClick() {
        Swal.fire({
            icon: 'warning',
            title: 'Đang trong phiên giữ chỗ',
            text: 'Một khách hàng khác đang giữ slot này trong 5 phút. Nếu họ không xác nhận đặt cọc, slot sẽ tự động mở lại.',
            confirmButtonColor: '#0d9488'
        });
    }

    handleMyHoldClick(slotId) {
        Swal.fire({
            icon: 'question',
            title: 'Khung giờ bạn đang giữ chỗ',
            text: 'Bạn muốn tiếp tục xác nhận đơn đặt sân này hay muốn hủy giữ chỗ?',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: 'Xác nhận & Đặt cọc',
            denyButtonText: 'Hủy giữ chỗ',
            cancelButtonText: 'Đóng',
            confirmButtonColor: '#0d9488',
            denyButtonColor: '#ef4444'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `confirm.html?slot_id=${slotId}&court_id=${this.courtId}&date=${this.selectedDate}`;
            } else if (result.isDenied) {
                this.cancelHold(slotId);
            }
        });
    }

    cancelHold(slotId) {
        const result = MockDB.cancelSlot(this.courtId, this.selectedDate, slotId);
        if (result.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: 'Đã hủy giữ chỗ',
                text: 'Khung giờ đã được giải phóng.',
                timer: 1500,
                showConfirmButton: false
            });
            this.myHeldSlotId = null;
            this.fetchAvailability(false);
        }
    }

    /**
     * Cập nhật Banner đếm ngược 5:00
     */
    updateHoldBanner(slot) {
        const bannerContainer = document.getElementById('holdBannerContainer');
        if (!bannerContainer) return;

        if (!slot || !slot.is_my_hold || slot.hold_seconds_left <= 0) {
            bannerContainer.innerHTML = '';
            if (this.timerInterval) clearInterval(this.timerInterval);
            return;
        }

        let secondsLeft = slot.hold_seconds_left;

        bannerContainer.innerHTML = `
            <div class="countdown-box mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="fs-1 text-warning">
                        <i class="fa-solid fa-stopwatch"></i>
                    </div>
                    <div>
                        <div class="fw-bold fs-5">Bạn đang giữ chỗ: ${slot.sub_court_name} (${slot.time_range})</div>
                        <div class="small opacity-75">Vui lòng hoàn tất thanh toán cọc trong thời gian đếm ngược</div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="text-center px-3 py-1 bg-black bg-opacity-25 rounded-3 border border-warning">
                        <div class="small text-warning text-uppercase fw-bold" style="font-size:0.65rem;">Thời gian còn lại</div>
                        <div id="holdCountdownDisplay" class="fs-4 fw-extrabold text-warning font-monospace">--:--</div>
                    </div>
                    <a href="confirm.html?slot_id=${slot.id}&court_id=${this.courtId}&date=${this.selectedDate}" class="btn btn-warning fw-bold text-dark rounded-pill px-4 shadow">
                        <i class="fa-solid fa-credit-card me-1"></i> Đặt Cọc Ngay
                    </a>
                    <button onclick="window.bookingEngine.cancelHold(${slot.id})" class="btn btn-outline-light rounded-pill btn-sm" title="Hủy">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>`;

        if (this.timerInterval) clearInterval(this.timerInterval);

        const display = document.getElementById('holdCountdownDisplay');
        const updateDisplay = () => {
            if (secondsLeft <= 0) {
                clearInterval(this.timerInterval);
                bannerContainer.innerHTML = '';
                Swal.fire({
                    icon: 'warning',
                    title: 'Hết thời gian giữ chỗ!',
                    text: 'Thời gian 5 phút giữ chỗ đã hết. Khung giờ đã được mở lại cho khách khác.',
                    confirmButtonColor: '#0d9488'
                });
                this.fetchAvailability(false);
                return;
            }
            const mins = Math.floor(secondsLeft / 60);
            const secs = secondsLeft % 60;
            if (display) {
                display.textContent = `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            }
            secondsLeft--;
        };

        updateDisplay();
        this.timerInterval = setInterval(updateDisplay, 1000);
    }
}

window.BookingEngine = BookingEngine;
