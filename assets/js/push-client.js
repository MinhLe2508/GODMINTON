/**
 * assets/js/push-client.js — Quản lý Web Push Notification phía client
 */

window.PushClient = (() => {
    'use strict';

    function isSupported() {
        return ('serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window);
    }

    function getPermissionState() {
        if (!('Notification' in window)) return 'unsupported';
        return Notification.permission; // 'default' | 'granted' | 'denied'
    }

    async function safeJson(response) {
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (_) {
            const clean = text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim().slice(0, 150);
            throw new Error(clean ? `Máy chủ phản hồi: ${clean}` : 'Máy chủ phản hồi không đúng định dạng JSON.');
        }
    }

    let csrfToken = '';
    async function getCsrfToken() {
        if (csrfToken) return csrfToken;
        const response = await fetch('api/push/subscribe.php', { credentials: 'same-origin', cache: 'no-store' });
        const result = await safeJson(response);
        if (!response.ok || result.status !== 'success' || !result.data?.csrf_token) {
            throw new Error(result.message || 'Không thể xác thực phiên thông báo.');
        }
        csrfToken = result.data.csrf_token;
        return csrfToken;
    }

    async function postSubscription(action, endpoint, keys = null) {
        const headers = { 'Content-Type': 'application/json', 'X-GODMINTON-CSRF': await getCsrfToken() };
        const response = await fetch('api/push/subscribe.php', {
            method: 'POST', credentials: 'same-origin', headers,
            body: JSON.stringify({ action, endpoint, ...(keys ? { keys } : {}) })
        });
        const result = await safeJson(response);
        if (!response.ok || result.status !== 'success') {
            if (response.status === 403) csrfToken = '';
            throw new Error(result.message || 'Không thể cập nhật thiết lập thông báo.');
        }
        return result;
    }

    function sameKey(left, right) {
        if (!left || !right || left.byteLength !== right.byteLength) return false;
        const a = new Uint8Array(left);
        const b = new Uint8Array(right);
        return a.every((value, index) => value === b[index]);
    }

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding)
            .replace(/\-/g, '+')
            .replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    async function getVapidPublicKey() {
        const res = await fetch('api/push/vapid-public-key.php');
        const json = await safeJson(res);
        if (!res.ok || json.status !== 'success' || !json.publicKey) {
            throw new Error('Không thể lấy VAPID Public Key từ máy chủ.');
        }
        return json.publicKey;
    }

    async function ensureServiceWorker() {
        if (!('serviceWorker' in navigator)) return null;
        try {
            let reg = await navigator.serviceWorker.getRegistration();
            if (!reg) {
                reg = await navigator.serviceWorker.register('sw.js');
            }
            if (reg) {
                try {
                    await Promise.race([
                        navigator.serviceWorker.ready,
                        new Promise(resolve => setTimeout(resolve, 800))
                    ]);
                } catch (_) {}
            }
            return reg;
        } catch (e) {
            console.warn('[PushClient] Service Worker registration failed:', e);
            return null;
        }
    }

    async function getSubscription() {
        if (!isSupported()) return null;
        try {
            const reg = (await ensureServiceWorker()) || (await navigator.serviceWorker.ready);
            if (!reg || !reg.pushManager) return null;
            return await reg.pushManager.getSubscription();
        } catch (_) {
            return null;
        }
    }

    async function subscribe() {
        if (!isSupported()) {
            throw new Error('Trình duyệt của bạn không hỗ trợ tính năng Web Push Notification.');
        }

        // 1. Xin quyền người dùng
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            throw new Error('Bạn chưa cấp quyền nhận thông báo. Vui lòng cho phép trong cài đặt trình duyệt.');
        }

        // 2. Chờ Service Worker sẵn sàng
        const reg = (await ensureServiceWorker()) || (await navigator.serviceWorker.ready);
        if (!reg) throw new Error('Không thể khởi tạo Service Worker.');

        // 3. Lấy VAPID public key và đăng ký với Push Service
        const vapidPublicKey = await getVapidPublicKey();
        const convertedKey = urlBase64ToUint8Array(vapidPublicKey);

        let subscription = await reg.pushManager.getSubscription();
        if (subscription && subscription.options?.applicationServerKey &&
            !sameKey(subscription.options.applicationServerKey, convertedKey)) {
            await postSubscription('unsubscribe', subscription.endpoint);
            await subscription.unsubscribe();
            subscription = null;
        }
        if (!subscription) {
            subscription = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: convertedKey
            });
        }

        // 4. Gửi subscription lên máy chủ MySQL
        const subJson = subscription.toJSON();
        await postSubscription('subscribe', subscription.endpoint, subJson.keys || {});

        return { success: true, subscription };
    }

    async function unsubscribe() {
        if (!isSupported()) return false;
        const reg = (await ensureServiceWorker()) || (await navigator.serviceWorker.ready);
        const sub = await reg?.pushManager?.getSubscription();
        if (sub) {
            const endpoint = sub.endpoint;
            await postSubscription('unsubscribe', endpoint);
            await sub.unsubscribe();
        }
        return true;
    }

    // Hiển thị trực tiếp banner thông báo Push ngay trên giao diện web (Floating In-App Push Banner)
    function showInAppPushBanner(payload) {
        let banner = document.getElementById('godmintonInAppPush');
        if (!banner) {
            banner = document.createElement('div');
            banner.id = 'godmintonInAppPush';
            banner.className = 'godminton-inapp-push-banner';
            banner.setAttribute('role', 'alert');
            banner.setAttribute('aria-live', 'assertive');
            document.body.appendChild(banner);
        }

        const iconUrl = payload.icon || 'assets/img/icons/icon-192.png';
        banner.innerHTML = `
            <div class="inapp-push-header">
                <div class="inapp-push-brand">
                    <img src="${iconUrl}" alt="GODMINTON" class="inapp-push-icon" onerror="this.src='assets/img/logo-teal.svg'">
                    <span class="inapp-push-app">GODMINTON PWA</span>
                    <span class="inapp-push-dot">·</span>
                    <span class="inapp-push-time">Vừa xong</span>
                </div>
                <button type="button" class="inapp-push-close" aria-label="Đóng">&times;</button>
            </div>
            <div class="inapp-push-body">
                <div class="inapp-push-title">
                    <i class="fa-solid fa-bell"></i>
                    <span>${payload.title || 'GODMINTON · Nhắc lịch thi đấu (Demo)'}</span>
                </div>
                <p class="inapp-push-text">${payload.body || ''}</p>
            </div>
        `;

        const closeBtn = banner.querySelector('.inapp-push-close');
        const dismiss = (e) => {
            if (e) e.stopPropagation();
            banner.classList.remove('show');
        };
        closeBtn.onclick = dismiss;

        banner.onclick = (e) => {
            if (e.target.closest('.inapp-push-close')) return;
            dismiss();
            const targetUrl = payload.data?.url || 'history.php';
            if (targetUrl && !window.location.pathname.includes(targetUrl)) {
                window.location.href = targetUrl;
            }
        };

        requestAnimationFrame(() => {
            banner.classList.add('show');
        });

        clearTimeout(banner._autoDismissTimer);
        banner._autoDismissTimer = setTimeout(dismiss, 8000);
    }

    async function sendTestNotification(customPayload = {}) {
        if (!isSupported()) {
            throw new Error('Trình duyệt không hỗ trợ Web Push Notification.');
        }

        // 1. Đảm bảo Service Worker được đăng ký và sẵn sàng
        const reg = await ensureServiceWorker();

        // 2. Yêu cầu quyền thông báo nếu chưa cấp
        if (Notification.permission === 'default') {
            const perm = await Notification.requestPermission();
            if (perm !== 'granted') {
                throw new Error('Bạn cần chọn "Cho phép" (Allow) quyền thông báo trong trình duyệt để nhận thông báo.');
            }
        } else if (Notification.permission === 'denied') {
            throw new Error('Trình duyệt đang chặn thông báo. Vui lòng bấm biểu tượng ổ khóa 🔒 ở thanh địa chỉ để Bật thông báo.');
        }

        // 3. Đảm bảo subscription thiết bị tồn tại và đồng bộ
        let subscription = await getSubscription();
        if (!subscription) {
            await subscribe();
            subscription = await getSubscription();
            if (!subscription) throw new Error('Thiết bị chưa thể bật nhận thông báo.');
            try { await updateBellUI(); } catch (_) {}
        }
        const currentKey = urlBase64ToUint8Array(await getVapidPublicKey());
        if (subscription.options?.applicationServerKey &&
            !sameKey(subscription.options.applicationServerKey, currentKey)) {
            await subscribe();
            subscription = await getSubscription();
            if (!subscription) throw new Error('Không thể cập nhật đăng ký thông báo của thiết bị.');
            try { await updateBellUI(); } catch (_) {}
        }

        // 4. Gửi yêu cầu qua backend api/push/send.php
        let result = { status: 'success' };
        try {
            const res = await fetch('api/push/send.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-GODMINTON-CSRF': await getCsrfToken() },
                body: JSON.stringify({
                    action: 'test',
                    endpoint: subscription.endpoint
                })
            });
            result = await safeJson(res);
        } catch (backendErr) {
            console.warn('[PushClient] Server send warning:', backendErr);
        }

        // 5. Nội dung thông báo demo (còn 5 phút trước giờ vào sân)
        const payload = Object.assign({
            title: 'GODMINTON · Nhắc lịch thi đấu (Demo)',
            body: 'Trận cầu của bạn tại Thảm A1 sắp bắt đầu sau 5 phút. Khởi động kỹ và sẵn sàng vào sân!',
            icon: 'assets/img/icons/icon-192.png',
            badge: 'assets/img/icons/badge-72.png',
            data: { url: 'history.php', timestamp: Date.now() }
        }, customPayload || {}, result?.data?.payload || {});

        // 6. HIỂN THỊ BANNER THÔNG BÁO DEMO TRỰC TIẾP TRÊN MÀN HÌNH (IN-APP PUSH BANNER)
        // Giúp người dùng 100% nhìn thấy thông báo demo ngay lập tức bất kể cài đặt Focus Assist của Windows
        showInAppPushBanner(payload);

        // Lưu bản ghi nhắc nhở vào bảng thông báo trong chuông để người dùng xem lại
        try {
            const list = JSON.parse(localStorage.getItem('godminton_active_reminders') || '[]');
            list.unshift({
                id: 'test-' + Date.now(),
                court_name: 'Sân Cầu Lông GODMINTON',
                sub_court: 'Thảm A1 (Demo)',
                booking_code: 'GDM-DEMO',
                time_range: 'Bắt đầu sau 5 phút',
                minutes_left: 5,
                timestamp: Date.now(),
                url: 'history.php'
            });
            localStorage.setItem('godminton_active_reminders', JSON.stringify(list.slice(0, 5)));
            refreshPostFeed();
        } catch (_) {}

        // 7. Đồng thời kích hoạt thông báo hệ điều hành / trình duyệt (OS Push Notification)
        try {
            if (reg && reg.showNotification) {
                await reg.showNotification(payload.title, {
                    body: payload.body,
                    icon: payload.icon || 'assets/img/icons/icon-192.png',
                    badge: payload.badge || 'assets/img/icons/badge-72.png',
                    vibrate: [100, 50, 100],
                    data: payload.data || { url: 'history.php' },
                    tag: 'godminton-demo-reminder',
                    renotify: true
                });
            } else if ('Notification' in window) {
                new Notification(payload.title, {
                    body: payload.body,
                    icon: payload.icon || 'assets/img/icons/icon-192.png'
                });
            }
        } catch (notifyErr) {
            console.warn('[PushClient] OS notification display:', notifyErr);
        }

        return result?.data || { payload };
    }

    // Tự động render khối quản lý thông báo vào giao diện (Profile / History / Settings)
    async function renderPushControls(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (!isSupported()) {
            container.innerHTML = `
                <div class="alert alert-secondary d-flex align-items-center gap-2 rounded-4 py-2 px-3 small">
                    <i class="fa-solid fa-triangle-exclamation text-warning"></i>
                    <span>Trình duyệt hiện tại không hỗ trợ Web Push Notification. Hãy thử trên Google Chrome hoặc Microsoft Edge.</span>
                </div>
            `;
            return;
        }

        const isSubscribed = (await getSubscription()) !== null;
        const permission = getPermissionState();

        container.innerHTML = `
            <div class="card border rounded-4 p-4 shadow-sm mb-4 push-notification-card">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="push-icon-circle">
                            <i class="fa-solid fa-bell text-teal fs-5" style="color:var(--brand-teal,#0d9488);"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0">Thông Báo Đẩy Nhắc Lịch (Web Push)</h6>
                            <div class="small text-muted" id="pushStatusText">
                                ${isSubscribed && permission === 'granted'
                                    ? '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i>Đang bật nhận thông báo</span>'
                                    : '<span class="text-muted">Nhận thông báo nhắc nhở trước giờ chơi cầu lông</span>'}
                            </div>
                        </div>
                    </div>
                    <div class="form-check form-switch fs-4 mb-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="pushToggleSwitch" ${isSubscribed && permission === 'granted' ? 'checked' : ''} style="cursor:pointer;">
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-between pt-2 border-top">
                    <span class="small text-muted">
                        <i class="fa-solid fa-shield-halved me-1 text-teal" style="color:var(--brand-teal,#0d9488);"></i> Có thể nhận khi đóng trang nếu thiết bị đang có Internet và cho phép thông báo.
                    </span>
                    <button type="button" class="btn btn-outline-teal btn-sm rounded-pill px-3 fw-bold" id="btnPushTest">
                        <i class="fa-solid fa-paper-plane me-1"></i> Gửi thử thông báo
                    </button>
                </div>
            </div>
        `;

        // Gắn sự kiện Toggle
        const toggle = document.getElementById('pushToggleSwitch');
        const statusText = document.getElementById('pushStatusText');
        const testBtn = document.getElementById('btnPushTest');

        toggle.addEventListener('change', async () => {
            toggle.disabled = true;
            try {
                if (toggle.checked) {
                    await subscribe();
                    statusText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i>Đang bật nhận thông báo</span>';
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Đã bật thông báo!',
                            text: 'Bạn sẽ nhận được nhắc nhở tự động trước khi trận đấu bắt đầu.',
                            confirmButtonColor: '#0d9488'
                        });
                    }
                } else {
                    await unsubscribe();
                    statusText.innerHTML = '<span class="text-muted">Đã tắt nhận thông báo</span>';
                }
            } catch (err) {
                toggle.checked = !toggle.checked;
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Không thể thay đổi', text: err.message || 'Lỗi thiết lập thông báo.', confirmButtonColor: '#0d9488' });
                } else {
                    alert(err.message);
                }
            } finally {
                toggle.disabled = false;
            }
        });

        // Gắn sự kiện Gửi thử
        testBtn.addEventListener('click', async () => {
            testBtn.disabled = true;
            testBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang gửi...';

            try {
                if (Notification.permission !== 'granted' || !(await getSubscription())) {
                    await subscribe();
                    toggle.checked = true;
                    statusText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i>Đang bật nhận thông báo</span>';
                }

                await sendTestNotification({
                    title: 'GODMINTON · Nhắc lịch thi đấu (Demo)',
                    body: 'Trận cầu của bạn tại Thảm A1 sắp bắt đầu sau 5 phút. Khởi động kỹ và sẵn sàng vào sân!',
                    url: 'history.php'
                });

                showToast('success', 'Đã gửi thông báo demo thành công!');
            } catch (err) {
                if (window.Swal) {
                    Swal.fire({ icon: 'warning', title: 'Thông báo', text: err.message, confirmButtonColor: '#0d9488' });
                } else {
                    alert(err.message);
                }
            } finally {
                testBtn.disabled = false;
                testBtn.innerHTML = '<i class="fa-solid fa-paper-plane me-1"></i> Gửi thử thông báo';
            }
        });
    }

    function showToast(icon, title) {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: icon,
                title: title,
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true
            });
        }
    }

    function escapeHtml(str) {
        return String(str ?? '').replace(/[&<>"']/g, c => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        }[c]));
    }

    function getDismissedPostIds() {
        try {
            const raw = localStorage.getItem('godminton_dismissed_posts_v1');
            return raw ? JSON.parse(raw) : [];
        } catch (_) { return []; }
    }

    function saveDismissedPostIds(ids) {
        try {
            localStorage.setItem('godminton_dismissed_posts_v1', JSON.stringify(ids));
        } catch (_) {}
    }

    function getDismissedReminderIds() {
        try {
            const raw = sessionStorage.getItem('godminton_dismissed_reminders');
            return raw ? JSON.parse(raw) : [];
        } catch (_) { return []; }
    }

    function saveDismissedReminderIds(ids) {
        try {
            sessionStorage.setItem('godminton_dismissed_reminders', JSON.stringify(ids));
        } catch (_) {}
    }

    function getUpcomingCourtReminders() {
        const reminders = [];
        const dismissed = getDismissedReminderIds();

        // 1. Kiểm tra reminders từ push server hoặc nút Gửi thử (godminton_active_reminders)
        try {
            const rawActive = localStorage.getItem('godminton_active_reminders');
            if (rawActive) {
                const list = JSON.parse(rawActive);
                if (Array.isArray(list)) {
                    list.forEach(r => {
                        if (dismissed.includes(String(r.id))) return;
                        const ts = r.timestamp || Date.now();
                        if (Date.now() - ts < 7200000) { // Trong vòng 2 giờ
                            reminders.push({
                                id: String(r.id),
                                type: 'push_reminder',
                                court_name: r.court_name || 'Sân Cầu Lông GODMINTON',
                                sub_court: r.sub_court || 'Thảm thi đấu',
                                booking_code: r.booking_code || 'GDM',
                                time_range: r.time_range || 'Sắp bắt đầu',
                                countdownText: r.minutes_left ? `Còn ${r.minutes_left} phút` : 'Sắp diễn ra',
                                badgeCls: 'bg-danger text-white',
                                badgeText: 'NHẮC LỊCH SÂN',
                                timeColor: 'text-danger',
                                url: r.url || 'history.php'
                            });
                        }
                    });
                }
            }
        } catch (_) {}

        // 2. Tự động quét từ danh sách đơn đặt sân đã lưu trên thiết bị (godminton_offline_bookings)
        try {
            const rawBookings = localStorage.getItem('godminton_offline_bookings');
            if (rawBookings) {
                const data = JSON.parse(rawBookings);
                const bookings = Array.isArray(data.bookings) ? data.bookings : [];
                const now = new Date();

                bookings.forEach(b => {
                    if (!['confirmed', 'checked_in'].includes(b.booking_status)) return;
                    if (!b.booking_date || !b.start_time) return;

                    let startTime = new Date(`${b.booking_date}T${b.start_time.length === 5 ? b.start_time + ':00' : b.start_time}`);
                    if (isNaN(startTime.getTime()) && b.booking_date.includes('/')) {
                        const parts = b.booking_date.split('/');
                        if (parts.length === 3) {
                            startTime = new Date(`${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}T${b.start_time.length === 5 ? b.start_time + ':00' : b.start_time}`);
                        }
                    }
                    if (isNaN(startTime.getTime())) return;

                    const endTimeStr = b.end_time ? (b.end_time.length === 5 ? b.end_time + ':00' : b.end_time) : null;
                    let endTime = endTimeStr ? new Date(`${b.booking_date}T${endTimeStr}`) : new Date(startTime.getTime() + 5400000);
                    if (isNaN(endTime.getTime())) endTime = new Date(startTime.getTime() + 5400000);

                    const diffMs = startTime.getTime() - now.getTime();
                    const diffMinutes = Math.round(diffMs / 60000);

                    const isOngoing = now >= startTime && now <= endTime;
                    const isUpcoming = diffMinutes > 0 && diffMinutes <= 1440; // Trong vòng 24 giờ tới

                    const reminderId = 'booking-' + b.id;
                    if (dismissed.includes(reminderId)) return;

                    if (isOngoing || isUpcoming) {
                        if (!reminders.some(r => r.booking_code === b.booking_code)) {
                            let countdownText = '';
                            let badgeCls = 'bg-teal text-white';
                            let badgeText = 'LỊCH SÂN HÔM NAY';
                            let timeColor = 'text-teal';

                            if (isOngoing) {
                                countdownText = 'Đang diễn ra';
                                badgeCls = 'bg-primary text-white';
                                badgeText = 'ĐANG THI ĐẤU';
                                timeColor = 'text-primary';
                            } else if (diffMinutes <= 60) {
                                countdownText = `Còn ${diffMinutes} phút`;
                                badgeCls = 'bg-danger text-white';
                                badgeText = 'SẮP ĐẾN GIỜ';
                                timeColor = 'text-danger';
                            } else if (diffMinutes <= 180) {
                                countdownText = `Còn ~${Math.round(diffMinutes / 60)} tiếng`;
                                badgeCls = 'bg-warning text-dark';
                                badgeText = 'SẮP THI ĐẤU';
                                timeColor = 'text-dark';
                            } else {
                                countdownText = `Bắt đầu ${b.start_time}`;
                            }

                            reminders.push({
                                id: reminderId,
                                type: 'match_schedule',
                                court_name: b.court_name || 'Sân cầu lông',
                                sub_court: b.sub_court_name || 'Thảm thi đấu',
                                booking_code: b.booking_code || 'GDM',
                                time_range: b.time_range || `${b.start_time} - ${b.end_time}`,
                                countdownText,
                                badgeCls,
                                badgeText,
                                timeColor,
                                minutes_left: diffMinutes,
                                url: 'history.php'
                            });
                        }
                    }
                });
            }
        } catch (_) {}

        reminders.sort((a, b) => (a.minutes_left ?? 9999) - (b.minutes_left ?? 9999));
        return reminders;
    }

    function refreshPostFeed() {
        const feedBox = document.getElementById('bellExploreFeedList');
        if (!feedBox) return;
        const api = window.GodmintonPostNotify;
        const allPosts = (api && typeof api.getLatestPosts === 'function') ? api.getLatestPosts() : [];
        const dismissedIds = getDismissedPostIds();
        const activePosts = allPosts.filter(p => !dismissedIds.includes(Number(p.id)));

        const reminders = getUpcomingCourtReminders();

        if (!reminders.length && !activePosts.length) {
            feedBox.innerHTML = '<div class="px-3 py-4 text-center small text-muted"><div class="mb-1 text-teal opacity-50"><i class="fa-regular fa-bell-slash fs-4"></i></div>Chưa có thông báo mới.</div>';
            return;
        }

        let html = '';

        // 1. Render các thông báo nhắc lịch sân sắp đến giờ (Hiển thị dạng Card nổi bật trên cùng)
        if (reminders.length > 0) {
            html += reminders.map(r => `
                <div class="bell-reminder-card p-2 mx-2 my-2 rounded-3 border position-relative" style="background:rgba(13, 148, 136, 0.05); border-color:rgba(13, 148, 136, 0.22) !important;">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="badge ${r.badgeCls} rounded-pill px-2 py-0" style="font-size:0.62rem; font-weight:700; line-height:1.7;">
                            <i class="fa-solid fa-bell me-1"></i>${r.badgeText}
                        </span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="${r.timeColor} fw-bold" style="font-size:0.72rem;">
                                <i class="fa-regular fa-clock me-1"></i>${r.countdownText}
                            </span>
                            <button type="button" class="btn-close p-1 bell-reminder-dismiss-btn" data-reminder-id="${r.id}" title="Ẩn nhắc lịch này" aria-label="Đóng" style="font-size:0.5rem; opacity:0.5;"></button>
                        </div>
                    </div>
                    <a href="${r.url || 'history.php'}" class="text-decoration-none text-dark d-block bell-reminder-link" style="min-width:0;">
                        <div class="d-flex align-items-center gap-2">
                            <div class="flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle text-teal bg-white shadow-sm border" style="width:26px; height:26px; font-size:0.75rem;">
                                <i class="fa-solid fa-person-running"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width:0;">
                                <div class="fw-bold text-dark text-truncate" style="font-size:0.8rem;" title="${escapeHtml(r.court_name)} · ${escapeHtml(r.sub_court)}">
                                    ${escapeHtml(r.court_name)} · <span class="text-teal">${escapeHtml(r.sub_court)}</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between text-muted mt-0" style="font-size:0.7rem;">
                                    <span class="text-truncate me-1"><i class="fa-regular fa-calendar-check me-1 text-teal"></i>${escapeHtml(r.time_range)}</span>
                                    <span class="badge bg-light text-secondary border font-monospace px-1 py-0 flex-shrink-0" style="font-size:0.63rem;">${escapeHtml(r.booking_code)}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            `).join('');
        }

        // 2. Render các bản tin Khám phá mới (Sự kiện, Gói hội viên, Thông báo)
        if (activePosts.length > 0) {
            html += activePosts.map(p => {
                const unread = api && typeof api.isPostUnread === 'function' ? api.isPostUnread(p) : false;
                const icon = p.type === 'event' ? 'fa-trophy' : (p.type === 'membership' ? 'fa-crown' : 'fa-bullhorn');
                const iconColor = p.type === 'event' ? 'text-purple' : (p.type === 'membership' ? 'text-warning' : 'text-danger');
                const iconBg = p.type === 'event' ? 'rgba(168, 85, 247, 0.12)' : (p.type === 'membership' ? 'rgba(245, 158, 11, 0.12)' : 'rgba(239, 68, 68, 0.12)');
                const url = `post-detail.php?slug=${encodeURIComponent(p.slug || '')}`;
                return `
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom bell-post-item" data-post-id="${p.id}" style="font-size:0.8rem; transition:background 0.15s ease;">
                        <a href="${url}" class="d-flex align-items-center gap-2 text-decoration-none flex-grow-1 bell-post-link" data-post-id="${p.id}" style="color:inherit; min-width:0; overflow:hidden;">
                            <div class="flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle ${iconColor}" style="width:26px; height:26px; font-size:0.75rem; background:${iconBg};">
                                <i class="fa-solid ${icon}"></i>
                            </div>
                            <div class="flex-grow-1" style="min-width:0; overflow:hidden;">
                                <div class="fw-semibold text-dark text-truncate bell-post-title" style="font-size:0.79rem; line-height:1.3;" title="${escapeHtml(p.title)}">${escapeHtml(p.title)}</div>
                                <div class="d-flex align-items-center gap-1 mt-0">
                                    ${unread ? '<span class="badge bg-danger text-white rounded-pill px-1" style="font-size:0.58rem; line-height:1.5;">MỚI</span>' : '<span class="text-muted" style="font-size:0.67rem;">Đã xem</span>'}
                                    ${p.voucher_code ? `<span class="badge bg-teal text-white rounded-pill px-1" style="font-size:0.58rem; line-height:1.5;">🎁 -${escapeHtml(p.voucher_discount_percent)}%</span>` : ''}
                                </div>
                            </div>
                        </a>
                        <button type="button" class="btn-close p-1 ms-1 text-muted flex-shrink-0 bell-post-dismiss-btn" data-dismiss-id="${p.id}" title="Xóa thông báo này" aria-label="Xóa" style="font-size:0.5rem; opacity:0.4;"></button>
                    </div>
                `;
            }).join('');
        }

        feedBox.innerHTML = html;

        // Gắn sự kiện click xem bài viết
        feedBox.querySelectorAll('.bell-post-link').forEach(link => {
            link.addEventListener('click', () => {
                if (api && typeof api.markPostSeen === 'function') {
                    api.markPostSeen(Number(link.dataset.postId));
                }
                closeBellDropdown();
            });
        });

        // Gắn sự kiện click xem nhắc lịch sân
        feedBox.querySelectorAll('.bell-reminder-link').forEach(link => {
            link.addEventListener('click', () => {
                closeBellDropdown();
            });
        });

        // Gắn sự kiện nút xóa đơn lẻ bài viết
        feedBox.querySelectorAll('.bell-post-dismiss-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const postId = Number(btn.dataset.dismissId);
                if (postId) {
                    const dismissed = getDismissedPostIds();
                    if (!dismissed.includes(postId)) {
                        dismissed.push(postId);
                        saveDismissedPostIds(dismissed);
                    }
                    if (api && typeof api.markPostSeen === 'function') {
                        api.markPostSeen(postId);
                    }
                    refreshPostFeed();
                }
            });
        });

        // Gắn sự kiện ẩn nhắc lịch đơn lẻ
        feedBox.querySelectorAll('.bell-reminder-dismiss-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const reminderId = btn.dataset.reminderId;
                if (reminderId) {
                    const dismissed = getDismissedReminderIds();
                    if (!dismissed.includes(reminderId)) {
                        dismissed.push(reminderId);
                        saveDismissedReminderIds(dismissed);
                    }
                    refreshPostFeed();
                }
            });
        });
    }

    // Tạo YouTube Notification Dropdown nếu chưa tồn tại
    function ensureBellDropdown() {
        let dropdown = document.getElementById('pushBellDropdown');
        if (dropdown) return dropdown;

        dropdown = document.createElement('div');
        dropdown.id = 'pushBellDropdown';
        dropdown.className = 'yt-bell-dropdown shadow';
        dropdown.style.setProperty('width', 'min(360px, calc(100vw - 16px))', 'important');
        dropdown.style.setProperty('max-width', 'calc(100vw - 16px)', 'important');
        dropdown.style.setProperty('box-sizing', 'border-box', 'important');
        dropdown.style.setProperty('overflow', 'hidden', 'important');
        dropdown.setAttribute('role', 'menu');
        dropdown.setAttribute('aria-label', 'Bảng thông báo');
        dropdown.innerHTML = `
            <div class="px-3 py-2 d-flex align-items-center justify-content-between border-bottom bell-dropdown-header" style="background:rgba(13, 148, 136, 0.05);">
                <div class="d-flex align-items-center gap-1">
                    <span class="text-teal" style="font-size:0.9rem;"><i class="fa-solid fa-bell"></i></span>
                    <span class="fw-bold text-dark small" style="font-size:0.86rem;">Thông báo</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-light border py-0 px-2 text-muted rounded-pill bell-header-action-btn" id="btnClearSeenFeed" title="Xóa các thông báo đã xem" style="font-size:0.7rem; line-height:1.7;">
                        <i class="fa-regular fa-trash-can me-1"></i>Xóa đã xem
                    </button>
                    <a href="explore.php" class="btn btn-sm text-teal fw-bold py-0 px-2 rounded-pill text-decoration-none bell-header-action-btn" style="font-size:0.7rem; line-height:1.7; background:rgba(13, 148, 136, 0.08);">
                        Khám phá &rarr;
                    </a>
                </div>
            </div>
            <div id="bellExploreFeedList" class="bell-explore-feed-scroll" style="max-height:240px; overflow-y:auto; overflow-x:hidden; overscroll-behavior:contain;"></div>
            <div class="yt-bell-divider my-0"></div>
            <label class="yt-bell-item d-flex align-items-center justify-content-between m-0" for="bellPushSwitchInput" id="bellPushSwitchRow" style="cursor:pointer; padding:9px 16px;">
                <div class="d-flex align-items-center flex-grow-1" style="min-width:0;">
                    <span class="yt-bell-icon flex-shrink-0" id="bellPushSwitchIcon" style="width:26px; font-size:1rem;"><i class="fa-solid fa-bell text-teal"></i></span>
                    <span class="yt-bell-label fw-semibold text-dark" id="bellPushSwitchLabel" style="font-size:0.86rem;">Thông báo</span>
                </div>
                <div class="form-check form-switch m-0 ps-2 d-flex align-items-center flex-shrink-0">
                    <input class="form-check-input m-0 bell-push-switch" type="checkbox" role="switch" id="bellPushSwitchInput" aria-label="Bật hoặc tắt Thông báo" style="width:2.3rem; height:1.2rem; cursor:pointer;">
                </div>
            </label>
            <div class="yt-bell-divider my-0"></div>
            <div class="yt-bell-item yt-bell-test d-flex align-items-center" data-action="test" role="menuitem" tabindex="0" style="padding:9px 16px;">
                <span class="yt-bell-icon flex-shrink-0" style="width:26px; font-size:0.95rem;"><i class="fa-solid fa-paper-plane"></i></span>
                <span class="yt-bell-label fw-medium" style="font-size:0.83rem;">Gửi thử nhắc lịch sân</span>
            </div>
        `;

        document.body.appendChild(dropdown);
        refreshPostFeed();

        // Gắn sự kiện nút "Xóa đã xem"
        const clearBtn = dropdown.querySelector('#btnClearSeenFeed');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                const api = window.GodmintonPostNotify;
                const allPosts = (api && typeof api.getLatestPosts === 'function') ? api.getLatestPosts() : [];
                const dismissed = getDismissedPostIds();
                let clearedCount = 0;

                allPosts.forEach(p => {
                    const unread = api && typeof api.isPostUnread === 'function' ? api.isPostUnread(p) : false;
                    if (!unread) { // Đã xem
                        if (!dismissed.includes(Number(p.id))) {
                            dismissed.push(Number(p.id));
                            clearedCount++;
                        }
                    }
                });

                // Cũng dọn dẹp các demo reminder đã xong nếu có
                try {
                    localStorage.removeItem('godminton_active_reminders');
                } catch(_) {}

                if (clearedCount > 0) {
                    saveDismissedPostIds(dismissed);
                    refreshPostFeed();
                    showToast('success', `Đã xóa ${clearedCount} thông báo đã xem.`);
                } else {
                    showToast('info', 'Không có thông báo đã xem nào để xóa.');
                }
            });
        }

        const switchInput = dropdown.querySelector('#bellPushSwitchInput');
        if (switchInput) {
            switchInput.addEventListener('click', (e) => e.stopPropagation());
            switchInput.addEventListener('change', async (e) => {
                e.stopPropagation();
                switchInput.disabled = true;
                try {
                    await handleDropdownAction(switchInput.checked ? 'enable' : 'none', false);
                } finally {
                    switchInput.disabled = false;
                    await syncDropdownActiveState();
                }
            });
        }

        const switchRow = dropdown.querySelector('#bellPushSwitchRow');
        if (switchRow) {
            switchRow.addEventListener('click', (e) => e.stopPropagation());
        }

        // Xử lý sự kiện click trên từng item có data-action
        dropdown.querySelectorAll('.yt-bell-item[data-action]').forEach(item => {
            item.addEventListener('click', async (e) => {
                e.stopPropagation();
                const action = item.getAttribute('data-action');
                await handleDropdownAction(action, true);
            });
        });

        // Lắng nghe click ngoài để đóng dropdown
        document.addEventListener('click', (e) => {
            const btn = document.getElementById('pushBellToggle');
            if (dropdown.classList.contains('show')) {
                if (!dropdown.contains(e.target) && (!btn || !btn.contains(e.target))) {
                    closeBellDropdown();
                }
            }
        });

        // Lắng nghe phím ESC để đóng
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && dropdown.classList.contains('show')) {
                closeBellDropdown();
            }
        });

        return dropdown;
    }

    let isDropdownEventsBound = false;
    function positionDropdown() {
        const btn = document.getElementById('pushBellToggle');
        const dropdown = document.getElementById('pushBellDropdown');
        if (!btn || !dropdown) return;
        const rect = btn.getBoundingClientRect();
        const dropHeight = dropdown.offsetHeight || 260;
        const dropWidth = dropdown.offsetWidth || 350;
        const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
        const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);

        // Vertical positioning: If button is near the bottom, flip above
        if (rect.bottom + dropHeight + 12 > vh && rect.top > dropHeight + 12) {
            dropdown.style.setProperty('top', `${Math.max(8, Math.round(rect.top - dropHeight - 8))}px`, 'important');
        } else {
            dropdown.style.setProperty('top', `${Math.max(8, Math.round(rect.bottom + 8))}px`, 'important');
        }

        // Horizontal positioning: Ensure it stays well within viewport boundaries
        let rightOffset = Math.round(vw - rect.right);
        if (rightOffset < 8) rightOffset = 8;
        if (rightOffset + dropWidth > vw - 8) {
            rightOffset = Math.max(8, vw - dropWidth - 8);
        }
        dropdown.style.setProperty('right', `${rightOffset}px`, 'important');
        dropdown.style.setProperty('left', 'auto', 'important');

        if (!isDropdownEventsBound) {
            isDropdownEventsBound = true;
            window.addEventListener('resize', () => {
                if (dropdown.classList.contains('show')) positionDropdown();
            }, { passive: true });
            window.addEventListener('scroll', () => {
                if (dropdown.classList.contains('show')) positionDropdown();
            }, { passive: true });
        }
    }

    function openBellDropdown() {
        const dropdown = ensureBellDropdown();
        refreshPostFeed();
        positionDropdown();
        syncDropdownActiveState();
        dropdown.classList.add('show');
    }

    function closeBellDropdown() {
        const dropdown = document.getElementById('pushBellDropdown');
        if (dropdown) {
            dropdown.classList.remove('show');
        }
    }

    function toggleBellDropdown() {
        const dropdown = ensureBellDropdown();
        if (dropdown.classList.contains('show')) {
            closeBellDropdown();
        } else {
            openBellDropdown();
        }
    }

    async function syncDropdownActiveState() {
        const dropdown = ensureBellDropdown();
        const isSubscribed = (await getSubscription()) !== null;
        const permission = getPermissionState();
        let mode = localStorage.getItem('godminton_push_mode');
        if (!mode) {
            mode = (isSubscribed && permission === 'granted') ? 'enabled' : 'none';
        }
        const isEnabled = (mode === 'enabled' || mode === 'all' || mode === 'personalized') && permission === 'granted' && isSubscribed;

        const switchInput = dropdown.querySelector('#bellPushSwitchInput');
        const switchIcon = dropdown.querySelector('#bellPushSwitchIcon');
        if (switchInput) switchInput.checked = isEnabled;
        if (switchIcon) {
            switchIcon.innerHTML = isEnabled
                ? '<i class="fa-solid fa-bell text-teal"></i>'
                : '<i class="fa-solid fa-bell-slash text-muted"></i>';
        }
    }

    async function handleDropdownAction(action, closeMenu = true) {
        if (closeMenu) closeBellDropdown();

        if (action === 'test') {
            if (window.Swal) {
                Swal.fire({
                    title: 'Đang gửi thông báo...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });
            }
            try {
                await sendTestNotification({
                    title: 'GODMINTON · Nhắc lịch thi đấu (Demo)',
                    body: 'Trận cầu của bạn tại Thảm A1 sắp bắt đầu sau 5 phút. Khởi động kỹ và sẵn sàng vào sân!',
                    url: 'history.php'
                });
                if (window.Swal) Swal.close();
                showToast('success', 'Đã gửi thông báo demo thành công!');
            } catch (err) {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Chưa thể gửi thông báo',
                        text: err.message,
                        confirmButtonColor: '#0d9488'
                    });
                } else {
                    alert(err.message);
                }
            }
            return;
        }

        if (action === 'none') {
            try {
                await unsubscribe();
            } catch (err) {
                if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Chưa thể tắt thông báo', text: err.message, confirmButtonColor: '#0d9488' });
                } else {
                    alert(err.message);
                }
                return;
            }
            localStorage.setItem('godminton_push_mode', 'none');
            await updateBellUI();
            showToast('info', 'Đã tắt nhận thông báo.');
            return;
        }

        if (action === 'enable') {
            const permission = getPermissionState();
            if (permission === 'denied') {
                if (window.Swal) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Thông báo bị chặn',
                        html: 'Trình duyệt đang chặn thông báo từ GODMINTON.<br><small class="text-muted">Vui lòng nhấp vào biểu tượng ổ khóa 🔒 ở thanh địa chỉ để bật lại quyền "Thông báo".</small>',
                        confirmButtonColor: '#0d9488'
                    });
                } else {
                    alert('Trình duyệt đang chặn thông báo. Vui lòng cấp lại quyền trong cài đặt trang web.');
                }
                return;
            }

            const isSubscribed = (await getSubscription()) !== null;
            if (!isSubscribed || permission !== 'granted') {
                if (window.Swal) {
                    Swal.fire({
                        title: 'Đang kích hoạt thông báo...',
                        text: 'Vui lòng nhấn "Cho phép" (Allow) nếu trình duyệt hiển thị hộp thoại xin quyền.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading()
                    });
                }
                try {
                    await subscribe();
                    localStorage.setItem('godminton_push_mode', 'enabled');
                    await updateBellUI();
                    showToast('success', 'Đã bật nhận thông báo thành công!');
                } catch (err) {
                    await updateBellUI();
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Chưa thể bật thông báo',
                            text: err.message || 'Lỗi khi kích hoạt thông báo.',
                            confirmButtonColor: '#0d9488'
                        });
                    } else {
                        alert(err.message);
                    }
                }
            } else {
                localStorage.setItem('godminton_push_mode', 'enabled');
                await updateBellUI();
                showToast('success', 'Đã bật nhận thông báo thành công!');
            }
            return;
        }
    }

    // Cập nhật giao diện nút chuông nổi
    async function updateBellUI() {
        const btn = document.getElementById('pushBellToggle');
        if (!btn) return;

        const isSbBtn = btn.classList.contains('sb-btn') || !!btn.closest('.sb-actions');
        const existingBadge = btn.querySelector('.bell-unread-badge');
        const badgeHtml = existingBadge ? existingBadge.outerHTML : '';
        const lblHtml = (isSbBtn ? '<span class="sb-lbl">Thông báo</span>' : '') + badgeHtml;

        if (!isSupported()) {
            btn.innerHTML = '<i class="fa-solid fa-bell-slash"></i>' + lblHtml;
            btn.title = 'Trình duyệt không hỗ trợ Web Push';
            btn.classList.remove('active');
            return;
        }

        const isSubscribed = (await getSubscription()) !== null;
        const permission = getPermissionState();
        let mode = localStorage.getItem('godminton_push_mode');
        if (!mode) {
            mode = (isSubscribed && permission === 'granted') ? 'enabled' : 'none';
        }

        if (permission === 'denied') {
            btn.innerHTML = '<i class="fa-solid fa-bell-slash"></i>' + lblHtml;
            btn.title = 'Thông báo bị chặn trong cài đặt trình duyệt';
            btn.classList.remove('active');
        } else if (isSubscribed && permission === 'granted' && (mode === 'enabled' || mode === 'all' || mode === 'personalized')) {
            btn.innerHTML = '<i class="fa-solid fa-bell"></i><span class="bell-active-dot" aria-hidden="true"></span>' + lblHtml;
            btn.title = 'Thông báo: Đang bật';
            btn.classList.add('active');
        } else if (mode === 'none' || mode === 'unsubscribed') {
            btn.innerHTML = '<i class="fa-solid fa-bell-slash"></i>' + lblHtml;
            btn.title = 'Thông báo: Không nhận thông báo';
            btn.classList.remove('active');
        } else {
            btn.innerHTML = '<i class="fa-regular fa-bell"></i>' + lblHtml;
            btn.title = 'Bật nhận thông báo';
            btn.classList.remove('active');
        }

        syncDropdownActiveState();
    }

    // Xử lý sự kiện khi nhấn nút chuông: mở/đóng dropdown menu kiểu YouTube
    function handleBellClick() {
        toggleBellDropdown();
    }

    // Khởi tạo nút chuông thông báo nổi hoặc tích hợp trên thanh topbar/searchbar
    async function initBellButton() {
        let btn = document.getElementById('pushBellToggle');
        if (window.location.pathname.includes('courts.php')) {
            if (btn) btn.remove();
            return;
        }
        const themeBtn = document.getElementById('themeToggle');
        const isAdmin = !!(document.querySelector('.admin-layout') || document.querySelector('.admin-topbar') || window.location.pathname.includes('admin-'));
        const isSb = !!(themeBtn && (themeBtn.classList.contains('sb-btn') || themeBtn.closest('.sb-actions')));
        const isWf = !!(themeBtn && themeBtn.closest('.wf-public-header'));

        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'pushBellToggle';
            btn.type = 'button';
            if (isSb) {
                btn.className = 'sb-btn';
            } else if (isAdmin) {
                btn.className = (themeBtn && themeBtn.classList.contains('admin-icon-btn'))
                    ? 'admin-icon-btn'
                    : 'btn btn-outline-secondary rounded-circle';
            } else if (isWf) {
                btn.className = 'btn btn-outline-secondary rounded-circle';
            } else {
                btn.className = 'bnav-theme-float bnav-bell-float';
            }
            btn.setAttribute('aria-label', 'Thông báo đẩy PWA');
            btn.title = 'Thông báo đẩy PWA';
            btn.innerHTML = isSb ? '<i class="fa-regular fa-bell"></i><span class="sb-lbl">Thông báo</span>' : '<i class="fa-regular fa-bell"></i>';

            const brandHead = document.querySelector('.app-brand-head');
            if (themeBtn && themeBtn.parentNode) {
                themeBtn.parentNode.insertBefore(btn, themeBtn);
            } else if (brandHead) {
                brandHead.appendChild(btn);
            } else if (isAdmin && document.querySelector('.admin-topbar-actions')) {
                document.querySelector('.admin-topbar-actions').prepend(btn);
            } else {
                document.body.appendChild(btn);
            }
        } else {
            const brandHead = document.querySelector('.app-brand-head');
            if (isSb) {
                btn.classList.remove('bnav-theme-float', 'bnav-bell-float');
                btn.classList.add('sb-btn');
            } else if (isAdmin) {
                btn.classList.remove('bnav-theme-float', 'bnav-bell-float');
                if (!btn.classList.contains('admin-icon-btn') && !btn.classList.contains('rounded-circle')) {
                    btn.classList.add('admin-icon-btn');
                }
            }
            if (brandHead && btn.parentNode !== brandHead) {
                if (themeBtn && themeBtn.parentNode === brandHead) {
                    brandHead.insertBefore(btn, themeBtn);
                } else {
                    brandHead.appendChild(btn);
                }
            } else if (themeBtn && themeBtn.parentNode && btn.parentNode !== themeBtn.parentNode) {
                themeBtn.parentNode.insertBefore(btn, themeBtn);
            }
            if (!btn.innerHTML.trim()) {
                btn.innerHTML = isSb ? '<i class="fa-regular fa-bell"></i><span class="sb-lbl">Thông báo</span>' : '<i class="fa-regular fa-bell"></i>';
            }
        }

        if (!btn.dataset.bellBound) {
            btn.dataset.bellBound = 'push-client';
            btn.addEventListener('click', handleBellClick);
        }

        try {
            await updateBellUI();
            startReminderHeartbeat();
        } catch (_) {}
        return btn;
    }

    let reminderHeartbeatTimer = null;
    async function checkDueReminders() {
        if (!isSupported() || getPermissionState() !== 'granted') return;
        try {
            const sub = await getSubscription();
            if (!sub) return;

            // Đồng bộ user_id với subscription trên server mỗi phiên làm việc
            if (!sessionStorage.getItem('godminton_push_synced')) {
                const subJson = sub.toJSON();
                if (subJson?.keys?.p256dh && subJson?.keys?.auth) {
                    await postSubscription('subscribe', sub.endpoint, subJson.keys);
                    sessionStorage.setItem('godminton_push_synced', '1');
                }
            }

            const res = await fetch('api/push/send.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-GODMINTON-CSRF': await getCsrfToken() },
                body: JSON.stringify({
                    action: 'reminder',
                    endpoint: sub.endpoint
                })
            });
            const result = await safeJson(res);
            const notifications = result?.data?.notifications || [];
            if (Array.isArray(notifications) && notifications.length > 0) {
                const reg = await ensureServiceWorker();
                for (const item of notifications) {
                    showInAppPushBanner(item);
                    try {
                        if (reg && reg.showNotification) {
                            await reg.showNotification(item.title, {
                                body: item.body,
                                icon: item.icon || 'assets/img/icons/icon-192.png',
                                badge: item.badge || 'assets/img/icons/badge-72.png',
                                vibrate: [100, 50, 100],
                                data: item.data || { url: 'history.php' },
                                tag: 'godminton-booking-reminder-' + (item.data?.booking_id || Date.now()),
                                renotify: true
                            });
                        }
                    } catch (_) {}
                }
            }
        } catch (_) {}
    }

    function startReminderHeartbeat() {
        if (reminderHeartbeatTimer) return;
        setTimeout(checkDueReminders, 2500);
        reminderHeartbeatTimer = setInterval(checkDueReminders, 60000);
    }

    // Tự động khởi tạo khi DOM sẵn sàng nếu chưa được gọi
    if (typeof document !== 'undefined') {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => { initBellButton(); });
        } else {
            setTimeout(initBellButton, 50);
        }
    }

    return {
        isSupported,
        getPermissionState,
        getSubscription,
        subscribe,
        unsubscribe,
        sendTestNotification,
        checkDueReminders,
        renderPushControls,
        initBellButton,
        updateBellUI,
        handleBellClick,
        refreshPostFeed
    };
})();
