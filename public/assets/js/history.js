document.addEventListener('DOMContentLoaded', () => {
    const user = (typeof MockDB !== 'undefined' && MockDB.getUser) ? MockDB.getUser() : null;
    if (!user) {
        window.location.href = 'auth.php';
        return;
    }

    const container = document.getElementById('bookingListContainer');

    function renderBookings() {
        const bookings = (typeof MockDB !== 'undefined' && MockDB.getBookings) ? MockDB.getBookings() : [];

        if (!bookings || bookings.length === 0) {
            if (window.GodmintonUI) {
                container.innerHTML = '<div class="max-w-600 mx-auto" id="historyEmptyHolder"></div>';
                GodmintonUI.renderEmpty('historyEmptyHolder', {
                    icon: 'fa-calendar-xmark',
                    title: 'Bạn chưa có đơn đặt sân nào',
                    desc: 'Hãy tìm kiếm sân cầu lông ưng ý và đặt ngay hôm nay!',
                    actionText: 'Khám phá các sân',
                    actionHref: 'courts.php'
                });
            } else {
                container.innerHTML = `
                <div class="card border rounded-4 p-5 text-center shadow-sm max-w-600 mx-auto">
                    <i class="fa-solid fa-calendar-xmark fs-1 text-muted mb-3"></i>
                    <h5 class="fw-bold">Bạn chưa có đơn đặt sân nào</h5>
                    <p class="text-muted small">Hãy tìm kiếm sân cầu lông ưng ý và đặt ngay hôm nay!</p>
                    <div>
                        <a href="courts.php" class="btn btn-teal rounded-pill px-4">Khám phá các sân</a>
                    </div>
                </div>`;
            }
            return;
        }

        const statusMap = {
            confirmed: { badge: 'bg-success', icon: 'fa-circle-check', text: 'Đã xác nhận' },
            completed: { badge: 'bg-primary', icon: 'fa-flag-checkered', text: 'Hoàn thành' },
            cancelled: { badge: 'bg-secondary', icon: 'fa-ban', text: 'Đã hủy' }
        };

        container.innerHTML = `<div class="row g-3">` + bookings.map(b => {
            const s = statusMap[b.booking_status] || statusMap.confirmed;
            return `
        <div class="col-12" id="booking-card-${b.id}">
            <div class="card border rounded-4 p-4 shadow-sm">
                <div class="row align-items-center g-3">
                    <div class="col-lg-3 col-md-4">
                        <div class="small text-muted text-uppercase fw-bold">Mã Đơn Đặt</div>
                        <div class="fs-5 fw-extrabold text-teal">${b.booking_code}</div>
                        <div class="small text-secondary">${b.created_at}</div>
                    </div>

                    <div class="col-lg-4 col-md-4">
                        <h6 class="fw-bold mb-1">${b.court_name}</h6>
                        <div class="small text-muted mb-1">
                            <i class="fa-solid fa-location-dot text-teal me-1"></i>${b.court_address || ''}
                        </div>
                        <div class="small">
                            <strong>${b.sub_court_name}:</strong>
                            <span class="badge bg-teal-subtle text-teal">${b.start_time} - ${b.end_time}</span>
                            • Ngày: <strong>${b.booking_date}</strong>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-4">
                        <div class="small text-muted">Tổng tiền: <strong>${typeof MockDB !== 'undefined' ? MockDB.formatVND(b.total_amount) : (b.total_amount + ' đ')}</strong></div>
                        <div class="small text-success">Đã cọc: <strong>${typeof MockDB !== 'undefined' ? MockDB.formatVND(b.deposit_amount) : (b.deposit_amount + ' đ')}</strong></div>
                        <div class="mt-1">
                            <span class="badge ${s.badge}">
                                <i class="fa-solid ${s.icon} me-1"></i>${s.text}
                            </span>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-12 text-lg-end">
                        ${b.booking_status === 'confirmed'
                    ? `<button class="btn btn-outline-danger btn-sm rounded-pill px-3" onclick="cancelBooking(${b.id})">
                                <i class="fa-solid fa-xmark me-1"></i> Hủy đơn
                                </button>`
                    : `<a href="booking.php?court_id=${b.court_id}" class="btn btn-outline-teal btn-sm rounded-pill px-3">Đặt lại</a>`
                }
                    </div>
                </div>
            </div>
        </div>`;
        }).join('') + `</div>`;
    }

    window.cancelBooking = async function (bookingId) {
        const result = await Swal.fire({
            icon: 'warning',
            title: 'Bạn chắc chắn muốn hủy đơn?',
            text: 'Khung giờ đặt sân sẽ được trả lại cho khách hàng khác trên hệ thống.',
            showCancelButton: true,
            confirmButtonText: 'Đúng, hủy đơn',
            cancelButtonText: 'Không, giữ lại',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#64748b'
        });

        if (result.isConfirmed) {
            const res = (typeof MockDB !== 'undefined' && MockDB.cancelBooking)
                ? MockDB.cancelBooking(bookingId)
                : { status: 'success', message: 'Hủy đơn thành công.' };

            if (res.status === 'success') {
                if (window.GodmintonUI) {
                    GodmintonUI.toast('success', 'Đã hủy đơn', 'Đơn đặt sân đã được hủy và slot đã được mở lại.');
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Đã hủy đơn thành công',
                    text: res.message,
                    confirmButtonColor: '#0d9488'
                }).then(() => renderBookings());
            } else {
                Swal.fire({ icon: 'error', title: 'Không thể hủy', text: res.message });
            }
        }
    };

    renderBookings();
});