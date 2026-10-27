/**
 * assets/js/push-client.js — Quản lý Web Push Notification phía client
 * Người phụ trách: Triết (Tuần 4)
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

    // Tạo YouTube Notification Dropdown nếu chưa tồn tại
    function ensureBellDropdown() {
        let dropdown = document.getElementById('pushBellDropdown');
        if (dropdown) return dropdown;

        dropdown = document.createElement('div');
        dropdown.id = 'pushBellDropdown';
        dropdown.className = 'yt-bell-dropdown';
        dropdown.setAttribute('role', 'menu');
        dropdown.setAttribute('aria-label', 'Tùy chọn thông báo');
        dropdown.innerHTML = `
            <div class="yt-bell-item" data-action="enable" role="menuitem" tabindex="0">
                <span class="yt-bell-icon"><i class="fa-solid fa-bell"></i></span>
                <span class="yt-bell-label">Bật thông báo</span>
                <span class="yt-bell-check"><i class="fa-solid fa-check"></i></span>
            </div>
            <div class="yt-bell-item" data-action="none" role="menuitem" tabindex="0">
                <span class="yt-bell-icon"><i class="fa-solid fa-bell-slash"></i></span>
                <span class="yt-bell-label">Không nhận thông báo</span>
                <span class="yt-bell-check"><i class="fa-solid fa-check"></i></span>
            </div>
            <div class="yt-bell-divider"></div>
            <div class="yt-bell-item yt-bell-test" data-action="test" role="menuitem" tabindex="0">
                <span class="yt-bell-icon"><i class="fa-solid fa-paper-plane"></i></span>
                <span class="yt-bell-label">Gửi thông báo</span>
                <span class="yt-bell-check"></span>
            </div>
        `;

        document.body.appendChild(dropdown);

        // Xử lý sự kiện click trên từng item
        dropdown.querySelectorAll('.yt-bell-item').forEach(item => {
            item.addEventListener('click', async (e) => {
                e.stopPropagation();
                const action = item.getAttribute('data-action');
                await handleDropdownAction(action);
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
        const dropHeight = dropdown.offsetHeight || 160;
        const dropWidth = dropdown.offsetWidth || 250;
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
        if (rightOffset < 10) rightOffset = 10;
        if (rightOffset + dropWidth > vw - 10) {
            rightOffset = Math.max(10, vw - dropWidth - 10);
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

        dropdown.querySelectorAll('.yt-bell-item').forEach(item => {
            const action = item.getAttribute('data-action');
            if (action === 'enable' && (mode === 'enabled' || mode === 'all' || mode === 'personalized') && permission === 'granted' && isSubscribed) {
                item.classList.add('active');
            } else if (action === 'none' && (mode === 'none' || mode === 'unsubscribed' || !isSubscribed || permission !== 'granted')) {
                item.classList.add('active');
            } else {
                item.classList.remove('active');
            }
        });
    }

    async function handleDropdownAction(action) {
        closeBellDropdown();

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
        const lblHtml = isSbBtn ? '<span class="sb-lbl">Thông báo</span>' : '';

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
        handleBellClick
    };
})();
