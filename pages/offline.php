<?php
header('Cache-Control: no-cache, no-store, must-revalidate');
?>
<!DOCTYPE html>
<html lang="vi" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bạn Đang Ngoại Tuyến | GODMINTON</title>
    <meta name="theme-color" content="#0d9488">
    <link rel="manifest" href="manifest.json">
    <link rel="icon" type="image/png" href="assets/img/icons/favicon.png">
    <link rel="apple-touch-icon" href="assets/img/icons/apple-touch-icon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
            background: linear-gradient(135deg, #f0fdfa 0%, #f8fafc 50%, #e6fffa 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            color: #1e293b;
        }
        .offline-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(13, 148, 136, 0.18);
            box-shadow: 0 20px 40px -15px rgba(13, 148, 136, 0.15), 0 0 1px 1px rgba(0,0,0,0.03);
            border-radius: 1.75rem;
            max-width: 520px;
            width: 100%;
            padding: 2.75rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .offline-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 1rem;
            border-radius: 9999px;
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #dc2626;
            box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7);
            animation: pulse 1.8s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(220, 38, 38, 0); }
            100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }
        .offline-icon-wrap {
            width: 96px;
            height: 96px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, #ccfbf1 0%, #99f6e4 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #0d9488;
            box-shadow: 0 8px 24px -6px rgba(13, 148, 136, 0.25);
        }
        .btn-retry {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: #ffffff;
            font-weight: 700;
            padding: 0.75rem 1.75rem;
            border-radius: 9999px;
            border: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.35);
        }
        .btn-retry:hover {
            background: linear-gradient(135deg, #0f766e 0%, #115e59 100%);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(13, 148, 136, 0.45);
        }
        .offline-tips {
            background: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 1rem;
            padding: 1rem;
            text-align: left;
            font-size: 0.88rem;
            margin-top: 1.75rem;
            color: #64748b;
        }
        .online-toast {
            display: none;
            background: #10b981;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 9999px;
            font-weight: 700;
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
            margin-bottom: 1.25rem;
            animation: bounce 0.5s;
        }

        /* Modal Lịch Ngoại Tuyến */
        .text-teal { color: #0d9488 !important; }
        .btn-teal {
            background-color: #0d9488 !important;
            border-color: #0d9488 !important;
            color: #ffffff !important;
        }
        .btn-teal:hover {
            background-color: #0f766e !important;
            border-color: #0f766e !important;
            color: #ffffff !important;
        }
        .btn-outline-teal {
            color: #0d9488;
            border: 1.5px solid #0d9488;
            background: transparent;
            transition: all 0.2s ease;
        }
        .btn-outline-teal:hover {
            background: #0d9488;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
        }

        .offline-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.68);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            z-index: 1050;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.2s ease-out;
        }
        .offline-modal-dialog {
            background: #ffffff;
            border-radius: 1.5rem;
            max-width: 620px;
            width: 100%;
            max-height: 88vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.28);
            border: 1px solid rgba(13, 148, 136, 0.25);
            text-align: left;
            animation: modalSlideUp 0.25s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes modalSlideUp {
            from { opacity: 0; transform: translateY(24px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .offline-modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #f8fafc;
        }
        .offline-modal-body {
            padding: 1.25rem 1.5rem;
            overflow-y: auto;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.85rem;
            -webkit-overflow-scrolling: touch;
        }
        .offline-modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            background: #f8fafc;
        }
        .btn-close-modal {
            background: transparent;
            border: none;
            font-size: 1.85rem;
            line-height: 1;
            color: #64748b;
            cursor: pointer;
            padding: 0;
            transition: color 0.15s;
        }
        .btn-close-modal:hover { color: #0f172a; }

        .offline-booking-item {
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            border-radius: 1rem;
            padding: 1rem 1.15rem;
            transition: all 0.18s ease;
        }
        .offline-booking-item:hover {
            border-color: #0d9488;
            box-shadow: 0 4px 14px rgba(13, 148, 136, 0.08);
        }
        .offline-code-chip {
            background: rgba(13, 148, 136, 0.1);
            color: #0d9488;
            font-family: monospace;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 0.5rem;
            font-size: 0.95rem;
            display: inline-flex;
            align-items: center;
            letter-spacing: 0.5px;
        }

        /* Dark mode */
        [data-bs-theme="dark"] body {
            background: linear-gradient(135deg, #042f2e 0%, #0f172a 60%, #021a19 100%);
            color: #f1f5f9;
        }
        [data-bs-theme="dark"] .offline-card {
            background: rgba(15, 23, 42, 0.92);
            border-color: rgba(45, 212, 191, 0.25);
            color: #f1f5f9;
        }
        [data-bs-theme="dark"] .offline-card h2 { color: #f8fafc !important; }
        [data-bs-theme="dark"] .offline-tips {
            background: rgba(30, 41, 59, 0.8);
            border-color: rgba(255, 255, 255, 0.1);
            color: #94a3b8;
        }
        [data-bs-theme="dark"] .offline-tips .text-dark { color: #f1f5f9 !important; }
        [data-bs-theme="dark"] .offline-modal-dialog {
            background: #0f172a;
            color: #f1f5f9;
            border-color: rgba(45, 212, 191, 0.3);
        }
        [data-bs-theme="dark"] .offline-modal-header,
        [data-bs-theme="dark"] .offline-modal-footer {
            background: #1e293b;
            border-color: #334155;
        }
        [data-bs-theme="dark"] .offline-booking-item {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }
        [data-bs-theme="dark"] .offline-booking-item h6 { color: #f8fafc !important; }
        [data-bs-theme="dark"] .btn-close-modal { color: #94a3b8; }
        [data-bs-theme="dark"] .btn-close-modal:hover { color: #ffffff; }
    </style>
</head>
<body>

    <div class="online-toast" id="onlineToast">
        <i class="fa-solid fa-circle-check me-2"></i> Đã có kết nối Internet! Đang tải lại...
    </div>

    <div class="offline-card">
        <div class="offline-badge">
            <span class="pulse-dot"></span>
            Không có kết nối mạng
        </div>

        <div class="offline-icon-wrap">
            <i class="fa-solid fa-wifi-slash"></i>
        </div>

        <h2 class="fw-extrabold mb-2" style="color:#0f172a; font-size:1.6rem;">Bạn Đang Ngoại Tuyến</h2>
        <p class="text-muted mb-4" style="font-size:0.95rem; line-height:1.6;">
            Thiết bị của bạn hiện không thể kết nối tới máy chủ <strong>GODMINTON</strong>. Đừng lo lắng, dữ liệu trang bạn đã truy cập trước đó vẫn được lưu trong bộ nhớ máy.
        </p>

        <div class="d-flex flex-wrap gap-2 justify-content-center mb-2">
            <button class="btn btn-retry" onclick="retryConnection()" id="btnRetry">
                <i class="fa-solid fa-rotate-right me-2"></i>Thử kết nối lại
            </button>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-house me-1"></i> Trang chủ
            </a>
            <button type="button" class="btn btn-outline-teal rounded-pill px-4 fw-bold" id="btnOfflineSchedule" onclick="openOfflineScheduleModal()">
                <i class="fa-solid fa-calendar-check me-1"></i> <span id="btnOfflineScheduleText">Lịch của tôi</span>
            </button>
        </div>

        <div class="offline-tips">
            <div class="fw-bold text-dark mb-1"><i class="fa-solid fa-lightbulb text-warning me-1"></i> Mẹo khắc phục nhanh:</div>
            <ul class="mb-0 ps-3">
                <li>Kiểm tra lại kết nối Wifi hoặc dữ liệu di động (4G/5G).</li>
                <li>Tắt và bật lại chế độ máy bay trên điện thoại.</li>
                <li>Ứng dụng sẽ tự động tải lại ngay khi nhận được tín hiệu mạng.</li>
            </ul>
        </div>
    </div>

    <!-- Modal Xem Lịch Thi Đấu Ngoại Tuyến -->
    <div id="offlineScheduleModal" class="offline-modal-backdrop" style="display:none;" onclick="if(event.target===this)closeOfflineScheduleModal()" role="dialog" aria-modal="true" aria-labelledby="offlineModalTitle">
        <div class="offline-modal-dialog">
            <div class="offline-modal-header">
                <div>
                    <h5 class="fw-extrabold mb-1 d-flex align-items-center" id="offlineModalTitle" style="font-size:1.15rem; color:#0f172a;">
                        <i class="fa-solid fa-calendar-check text-teal me-2"></i>Lịch Thi Đấu Ngoại Tuyến
                    </h5>
                    <div class="small text-muted" id="offlineSyncTimestamp" style="font-size:0.75rem;">
                        <i class="fa-solid fa-clock-rotate-left me-1"></i>Đang tải dữ liệu lưu trữ...
                    </div>
                </div>
                <button type="button" class="btn-close-modal" onclick="closeOfflineScheduleModal()" aria-label="Đóng">&times;</button>
            </div>

            <div class="px-3 pt-3 pb-2 border-bottom" style="background:rgba(13, 148, 136, 0.04);">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" id="offlineSearchInput" class="form-control border-start-0" placeholder="Tìm theo tên sân, thảm đấu, mã đặt sân hoặc ngày..." oninput="filterOfflineBookings(this.value)">
                </div>
            </div>

            <div class="offline-modal-body" id="offlineBookingsContainer">
                <!-- Nội dung danh sách đơn được render bằng JS -->
            </div>

            <div class="offline-modal-footer">
                <span class="small text-muted" style="font-size:0.78rem;">
                    <i class="fa-solid fa-shield-halved text-teal me-1"></i>Xuất trình mã đặt sân tại quầy để nhận sân
                </span>
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-4 fw-bold" onclick="closeOfflineScheduleModal()">Đóng</button>
            </div>
        </div>
    </div>

    <p class="small text-muted mt-4">
        &copy; 2026 GODMINTON PWA — Trải nghiệm liền mạch cả khi mất kết nối.
    </p>

    <script>
        const STATUS_CONFIG = {
            pending: { cls: 'bg-warning text-dark', label: '<i class="fa-regular fa-clock me-1"></i>Chờ cọc' },
            confirmed: { cls: 'bg-info-subtle text-info-emphasis border border-info-subtle', label: '<i class="fa-solid fa-circle-check me-1"></i>Đã cọc' },
            checked_in: { cls: 'bg-primary text-white shadow-sm', label: '<i class="fa-solid fa-person-running me-1"></i>Đang chơi' },
            completed: { cls: 'bg-success text-white fw-bold shadow-sm', label: '<i class="fa-solid fa-circle-check me-1"></i>Hoàn thành' },
            no_show: { cls: 'bg-warning-subtle text-warning-emphasis border border-warning-subtle', label: '<i class="fa-solid fa-user-xmark me-1"></i>Bùng sân' },
            cancelled: { cls: 'bg-danger text-white fw-bold shadow-sm', label: '<i class="fa-solid fa-ban me-1"></i>Đã hủy' }
        };

        let cachedBookingsList = [];

        function escapeHtml(str) {
            return String(str ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        function formatMoney(amount) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(Number(amount || 0));
        }

        function getOfflineBookingsData() {
            try {
                const raw = localStorage.getItem('godminton_offline_bookings');
                return raw ? JSON.parse(raw) : null;
            } catch (e) {
                return null;
            }
        }

        function updateOfflineButtonBadge() {
            const data = getOfflineBookingsData();
            const count = (data && Array.isArray(data.bookings)) ? data.bookings.length : 0;
            const btnText = document.getElementById('btnOfflineScheduleText');
            if (btnText && count > 0) {
                btnText.innerHTML = `Lịch của tôi <span class="badge bg-teal text-white rounded-pill ms-1" style="font-size:0.75rem;">${count}</span>`;
            }
        }

        function openOfflineScheduleModal() {
            const modal = document.getElementById('offlineScheduleModal');
            if (!modal) return;
            const data = getOfflineBookingsData();
            cachedBookingsList = (data && Array.isArray(data.bookings)) ? data.bookings : [];
            const syncEl = document.getElementById('offlineSyncTimestamp');
            if (syncEl) {
                syncEl.innerHTML = data?.updated_at
                    ? `<i class="fa-solid fa-clock-rotate-left me-1 text-teal"></i>Đồng bộ lần cuối: <strong>${new Date(data.updated_at).toLocaleString('vi-VN')}</strong>`
                    : '<i class="fa-solid fa-circle-info me-1"></i>Chưa có lịch sử đồng bộ trên máy';
            }
            renderOfflineBookingsList(cachedBookingsList);
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeOfflineScheduleModal() {
            const modal = document.getElementById('offlineScheduleModal');
            if (modal) modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function filterOfflineBookings(query) {
            const q = String(query || '').trim().toLowerCase();
            if (!q) {
                renderOfflineBookingsList(cachedBookingsList);
                return;
            }
            const filtered = cachedBookingsList.filter(b => {
                return String(b.booking_code || '').toLowerCase().includes(q)
                    || String(b.court_name || '').toLowerCase().includes(q)
                    || String(b.court_address || '').toLowerCase().includes(q)
                    || String(b.sub_court_name || '').toLowerCase().includes(q)
                    || String(b.booking_date || '').toLowerCase().includes(q);
            });
            renderOfflineBookingsList(filtered);
        }

        function copyBookingCode(code, btn) {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(code).then(() => {
                    const old = btn.innerHTML;
                    btn.innerHTML = '<i class="fa-solid fa-check text-success"></i>';
                    setTimeout(() => { btn.innerHTML = old; }, 1600);
                }).catch(() => {});
            }
        }

        function renderOfflineBookingsList(list) {
            const container = document.getElementById('offlineBookingsContainer');
            if (!container) return;

            if (!list || !list.length) {
                container.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="fa-solid fa-calendar-xmark fs-1 text-secondary opacity-50 mb-3 d-block"></i>
                        <h6 class="fw-bold text-dark">Chưa Có Lịch Đặt Sân Ngoại Tuyến</h6>
                        <p class="small text-muted mb-3" style="line-height:1.6;">
                            Thiết bị này chưa lưu lịch thi đấu nào.<br>
                            Khi có kết nối mạng, hãy mở mục <strong>"Lịch của tôi"</strong> một lần, hệ thống sẽ tự động lưu bản sao vào máy để bạn xem ngoại tuyến bất cứ khi nào.
                        </p>
                        <button class="btn btn-sm btn-teal rounded-pill px-4 fw-bold" onclick="retryConnection()">
                            <i class="fa-solid fa-rotate-right me-1"></i> Thử kết nối lại
                        </button>
                    </div>
                `;
                return;
            }

            container.innerHTML = list.map(b => {
                const conf = STATUS_CONFIG[b.booking_status] || { cls: 'bg-secondary text-white', label: escapeHtml(b.booking_status || 'Không rõ') };
                const depositStr = formatMoney(b.deposit_amount);
                const totalStr = formatMoney(b.total_amount);
                const remainingStr = formatMoney(b.remaining_amount);
                const hasRemaining = b.remaining_amount > 0 && !['completed', 'cancelled', 'no_show'].includes(b.booking_status);

                return `
                    <div class="offline-booking-item ${b.booking_status === 'checked_in' ? 'border-success' : ''}">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="offline-code-chip" title="Mã đặt sân"><i class="fa-solid fa-receipt me-1"></i>${escapeHtml(b.booking_code)}</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2" style="font-size:0.75rem;" onclick="copyBookingCode('${escapeHtml(b.booking_code)}', this)" title="Sao chép mã">
                                    <i class="fa-regular fa-copy"></i>
                                </button>
                            </div>
                            <span class="badge ${conf.cls} px-2 py-1 rounded-pill" style="font-size:0.78rem;">${conf.label}</span>
                        </div>
                        <h6 class="fw-bold mb-1 text-dark">${escapeHtml(b.court_name || 'Sân cầu lông')}</h6>
                        <div class="small text-muted mb-2"><i class="fa-solid fa-location-dot text-danger me-1"></i>${escapeHtml(b.court_address || 'Địa chỉ đang cập nhật')}</div>
                        <div class="p-2 rounded-3 bg-light d-flex align-items-center justify-content-between flex-wrap gap-2 small mb-2">
                            <div><strong class="text-teal">${escapeHtml(b.sub_court_name || 'Thảm đấu')}</strong> · <span>${escapeHtml(b.booking_date || '—')}</span></div>
                            <div class="fw-bold text-dark"><i class="fa-regular fa-clock text-teal me-1"></i>${escapeHtml(b.time_range || '—')}</div>
                        </div>
                        <div class="small text-muted d-flex align-items-center justify-content-between flex-wrap gap-2 pt-1 border-top">
                            <span>Tổng: <strong>${totalStr}</strong> · Cọc: <strong class="text-teal">${depositStr}</strong></span>
                            ${hasRemaining ? `<span class="text-danger fw-bold">Còn thu tại quầy: ${remainingStr}</span>` : ''}
                        </div>
                        ${b.notes ? `<div class="small text-muted mt-1 fst-italic">Ghi chú: ${escapeHtml(b.notes)}</div>` : ''}
                    </div>
                `;
            }).join('');
        }

        // Tự động đóng modal bằng phím ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeOfflineScheduleModal();
        });

        // Khởi tạo trạng thái ban đầu
        document.addEventListener('DOMContentLoaded', () => {
            updateOfflineButtonBadge();
            if (window.location.hash === '#schedule' || window.location.search.includes('schedule') || document.referrer.includes('history')) {
                openOfflineScheduleModal();
            }
        });

        function retryConnection() {
            const btn = document.getElementById('btnRetry');
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang kiểm tra...';
            btn.disabled = true;
            
            fetch('index.php', { method: 'HEAD', cache: 'no-store' })
                .then(() => {
                    window.location.reload();
                })
                .catch(() => {
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fa-solid fa-rotate-right me-2"></i>Thử kết nối lại';
                        btn.disabled = false;
                    }, 800);
                });
        }

        // Tự động reload khi có mạng trở lại
        window.addEventListener('online', () => {
            const toast = document.getElementById('onlineToast');
            toast.style.display = 'inline-block';
            setTimeout(() => {
                window.location.reload();
            }, 1200);
        });
    </script>
</body>
</html>
