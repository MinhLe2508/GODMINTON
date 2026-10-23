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

    let csrfToken = '';
    async function getCsrfToken() {
        if (csrfToken) return csrfToken;
        const response = await fetch('api/push/subscribe.php', { credentials: 'same-origin', cache: 'no-store' });
        const result = await response.json();
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
        const result = await response.json();
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
        const json = await res.json();
        if (!res.ok || json.status !== 'success' || !json.publicKey) {
            throw new Error('Không thể lấy VAPID Public Key từ máy chủ.');
        }
        return json.publicKey;
    }

    async function getSubscription() {
        if (!isSupported()) return null;
        try {
            const reg = await Promise.race([
                navigator.serviceWorker.ready,
                new Promise(resolve => setTimeout(() => resolve(null), 1000))
            ]) || (await navigator.serviceWorker.getRegistration());
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
        const reg = await navigator.serviceWorker.ready;

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
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        if (sub) {
            const endpoint = sub.endpoint;
            await postSubscription('unsubscribe', endpoint);
            await sub.unsubscribe();
        }
        return true;
    }

    async function sendTestNotification(customPayload = {}) {
        if (!isSupported()) {
            throw new Error('Trình duyệt không hỗ trợ Web Push Notification.');
        }

        // 1. Gọi API gửi thông báo
        let subscription = await getSubscription();
        if (!subscription) throw new Error('Thiết bị chưa bật thông báo. Hãy bật nhận thông báo trước.');
        const currentKey = urlBase64ToUint8Array(await getVapidPublicKey());
        if (subscription.options?.applicationServerKey &&
            !sameKey(subscription.options.applicationServerKey, currentKey)) {
            await subscribe();
            subscription = await getSubscription();
            if (!subscription) throw new Error('Không thể cập nhật đăng ký thông báo của thiết bị.');
        }
        const res = await fetch('api/push/send.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-GODMINTON-CSRF': await getCsrfToken() },
            body: JSON.stringify({
                action: 'test',
                endpoint: subscription.endpoint
            })
        });

        const result = await res.json();
        if (!res.ok || result.status !== 'success') {
            throw new Error(result.message || 'Không thể gửi thông báo thử nghiệm.');
        }

        return result.data;
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
                    title: 'GODMINTON – Nhắc lịch thi đấu',
                    body: 'Trận cầu của bạn tại Thảm A1 sắp bắt đầu lúc 18:00. Khởi động kỹ và sẵn sàng vào sân!',
                    url: 'history.php'
                });

                if (window.Swal) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Đã gửi thông báo!',
                        text: 'Kiểm tra thông báo đẩy xuất hiện trên màn hình máy tính hoặc điện thoại của bạn.',
                        confirmButtonColor: '#0d9488',
                        timer: 3000
                    });
                }
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

    function positionDropdown() {
        const btn = document.getElementById('pushBellToggle');
        const dropdown = document.getElementById('pushBellDropdown');
        if (!btn || !dropdown) return;
        const rect = btn.getBoundingClientRect();
        dropdown.style.setProperty('top', `${Math.round(rect.bottom + 8)}px`, 'important');
        const rightOffset = Math.max(12, Math.round(window.innerWidth - rect.right));
        dropdown.style.setProperty('right', `${rightOffset}px`, 'important');
        dropdown.style.setProperty('left', 'auto', 'important');
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
                    title: 'GODMINTON – Nhắc lịch thi đấu',
                    body: 'Trận cầu của bạn tại Thảm A1 sắp bắt đầu lúc 18:00. Khởi động kỹ và sẵn sàng vào sân!',
                    url: 'history.php'
                });
                showToast('success', 'Đã gửi thông báo thành công!');
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

        if (!isSupported()) {
            btn.innerHTML = '<i class="fa-solid fa-bell-slash"></i>';
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
            btn.innerHTML = '<i class="fa-solid fa-bell-slash"></i>';
            btn.title = 'Thông báo bị chặn trong cài đặt trình duyệt';
            btn.classList.remove('active');
        } else if (isSubscribed && permission === 'granted' && (mode === 'enabled' || mode === 'all' || mode === 'personalized')) {
            btn.innerHTML = '<i class="fa-solid fa-bell"></i><span class="bell-active-dot" aria-hidden="true"></span>';
            btn.title = 'Thông báo: Đang bật';
            btn.classList.add('active');
        } else if (mode === 'none' || mode === 'unsubscribed') {
            btn.innerHTML = '<i class="fa-solid fa-bell-slash"></i>';
            btn.title = 'Thông báo: Không nhận thông báo';
            btn.classList.remove('active');
        } else {
            btn.innerHTML = '<i class="fa-regular fa-bell"></i>';
            btn.title = 'Bật nhận thông báo';
            btn.classList.remove('active');
        }

        syncDropdownActiveState();
    }

    // Xử lý sự kiện khi nhấn nút chuông: mở/đóng dropdown menu kiểu YouTube
    function handleBellClick() {
        toggleBellDropdown();
    }

    // Khởi tạo nút chuông thông báo nổi cạnh nút theme
    async function initBellButton() {
        if (typeof document !== 'undefined') {
            if (document.querySelector('.admin-layout') || document.querySelector('.admin-topbar') || window.location.pathname.includes('admin-')) {
                const existingBtn = document.getElementById('pushBellToggle');
                if (existingBtn) existingBtn.remove();
                return null;
            }
        }
        let btn = document.getElementById('pushBellToggle');
        if (!btn) {
            btn = document.createElement('button');
            btn.id = 'pushBellToggle';
            btn.type = 'button';
            btn.className = 'bnav-theme-float bnav-bell-float';
            btn.setAttribute('aria-label', 'Thông báo đẩy PWA');
            btn.title = 'Thông báo đẩy PWA';
            btn.innerHTML = '<i class="fa-regular fa-bell"></i>';

            const themeBtn = document.getElementById('themeToggle');
            if (themeBtn && themeBtn.parentNode) {
                themeBtn.parentNode.insertBefore(btn, themeBtn);
            } else {
                document.body.appendChild(btn);
            }
        } else {
            if (!btn.innerHTML.trim()) {
                btn.innerHTML = '<i class="fa-regular fa-bell"></i>';
            }
        }

        if (!btn.dataset.bellBound) {
            btn.dataset.bellBound = 'push-client';
            btn.addEventListener('click', handleBellClick);
        }

        try {
            await updateBellUI();
        } catch (_) {}
        return btn;
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
        renderPushControls,
        initBellButton,
        updateBellUI,
        handleBellClick
    };
})();
