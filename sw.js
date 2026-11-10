/**
 * sw.js — Service Worker cho GODMINTON PWA
 * Phạm vi (Scope): Thư mục gốc website
 * Hỗ trợ: Pre-cache, Offline Fallback, Dynamic Network-First, Web Push Notification
 */

const CACHE_VERSION = 'godminton-v1.3.3-bell-scroll';
const CACHE_STATIC  = `godminton-static-${CACHE_VERSION}`;
const CACHE_RUNTIME = `godminton-runtime-${CACHE_VERSION}`;
const OFFLINE_URL   = 'offline.php';

// Danh sách tài nguyên tĩnh thiết yếu cần precache khi cài đặt
const PRECACHE_ASSETS = [
    OFFLINE_URL,
    'manifest.json',
    'assets/css/style.css',
    'assets/img/logo-teal.svg',
    'assets/img/icons/icon-192.png',
    'assets/img/icons/icon-512.png',
    'assets/img/icons/badge-72.png',
    'assets/img/icons/favicon.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
    'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap'
];

/* ================================================================
   1. INSTALL: Pre-cache các file tĩnh cốt lõi & offline.php
   ================================================================ */
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_STATIC).then(async cache => {
            // Thêm từng tài nguyên, bắt lỗi riêng lẻ để không làm fail toàn bộ install
            for (const asset of PRECACHE_ASSETS) {
                try {
                    await cache.add(new Request(asset, { cache: 'reload' }));
                } catch (err) {
                    console.warn('[ServiceWorker] Không thể nạp trước:', asset, err);
                }
            }
        }).then(() => self.skipWaiting())
    );
});

/* ================================================================
   2. ACTIVATE: Dọn dẹp cache cũ & chiếm quyền điều khiển clients
   ================================================================ */
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => {
            return Promise.all(
                keys.filter(key => key !== CACHE_STATIC && key !== CACHE_RUNTIME)
                    .map(key => {
                        console.log('[ServiceWorker] Xóa cache cũ:', key);
                        return caches.delete(key);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

/* ================================================================
   3. FETCH: Chiến lược điều phối tài nguyên
   - /api/ : BẮT BUỘC Network-Only (tránh cache slot ảo/giữ chỗ sai)
   - Navigation (HTML): Network-First → Cache → Fallback offline.php
   - Static assets (CSS/JS/Img/Font): Stale-While-Revalidate
   ================================================================ */
self.addEventListener('fetch', event => {
    const req = event.request;
    const url = new URL(req.url);

    // Bỏ qua các scheme không hỗ trợ (chrome-extension://, v.v.)
    if (!url.protocol.startsWith('http')) return;

    // Never cache authentication or account pages, even when offline.
    if (req.method !== 'GET' || /\/(?:owner-application|auth(?:-action)?|profile|history|payment|confirm|booking|admin-[^/]+)\.php$/i.test(url.pathname)) return;

    // QUY TẮC SỐ 1: API GIAO DỊCH → NETWORK-ONLY TUYỆT ĐỐI
    // Không bao giờ cache api/hold, api/bookings, api/slots, api/auth, v.v.
    if (url.pathname.includes('/api/')) {
        return; // Để trình duyệt xử lý bình thường qua Network
    }

    // QUY TẮC SỐ 2: ĐIỀU HƯỚNG TRANG WEB (HTML NAVIGATION)
    if (req.mode === 'navigate' || (req.method === 'GET' && req.headers.get('accept')?.includes('text/html'))) {
        event.respondWith(
            fetch(req)
                .then(networkRes => {
                    // Lưu bản sao vào cache runtime nếu response hợp lệ
                    if (networkRes && networkRes.status === 200 && !/no-store|private/i.test(networkRes.headers.get('Cache-Control') || '')) {
                        const copy = networkRes.clone();
                        caches.open(CACHE_RUNTIME).then(cache => cache.put(req, copy));
                    }
                    return networkRes;
                })
                .catch(async () => {
                    // Mất mạng: Tìm trong cache trước
                    const cachedRes = await caches.match(req);
                    if (cachedRes) return cachedRes;

                    // Nếu cache chưa có trang này: Trả về trang offline.php chuyên dụng
                    const offlineRes = await caches.match(OFFLINE_URL);
                    if (offlineRes) return offlineRes;

                    return new Response('<h1>Bạn đang ngoại tuyến</h1><p>Vui lòng kết nối mạng để sử dụng GODMINTON.</p>', {
                        headers: { 'Content-Type': 'text/html; charset=utf-8' }
                    });
                })
        );
        return;
    }

    // QUY TẮC SỐ 3: TÀI NGUYÊN TĨNH (CSS, JS, WebFonts, Images)
    // Chiến lược Stale-While-Revalidate
    if (['style', 'script', 'image', 'font'].includes(req.destination) ||
        url.pathname.endsWith('.css') || url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.svg') || url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') || url.pathname.endsWith('.webp')) {

        event.respondWith(
            caches.match(req).then(cached => {
                const fetchPromise = fetch(req).then(networkRes => {
                    if (networkRes && networkRes.status === 200 && !/no-store|private/i.test(networkRes.headers.get('Cache-Control') || '')) {
                        const copy = networkRes.clone();
                        caches.open(CACHE_RUNTIME).then(cache => cache.put(req, copy));
                    }
                    return networkRes;
                }).catch(() => null);

                // Ưu tiên trả về cache ngay lập tức nếu có, đồng thời fetch ngầm cập nhật
                return cached || fetchPromise;
            })
        );
    }
});

/* ================================================================
   4. WEB PUSH NOTIFICATION: Nhận & hiển thị thông báo đẩy
   ================================================================ */
self.addEventListener('push', event => {
    let payload = {
        title: 'GODMINTON – Nhắc lịch thi đấu',
        body: 'Bạn có khung giờ đặt sân cầu lông sắp bắt đầu. Chuẩn bị vợt ngay thôi!',
        icon: 'assets/img/icons/icon-192.png',
        badge: 'assets/img/icons/badge-72.png',
        data: {
            url: 'history.php',
            timestamp: Date.now()
        }
    };

    if (event.data) {
        try {
            const json = event.data.json();
            payload = { ...payload, ...json };
        } catch {
            payload.body = event.data.text() || payload.body;
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon || 'assets/img/icons/icon-192.png',
        badge: payload.badge || 'assets/img/icons/badge-72.png',
        vibrate: [100, 50, 100],
        data: payload.data || { url: 'history.php' },
        actions: [
            { action: 'view', title: 'Xem chi tiết' },
            { action: 'close', title: 'Đóng' }
        ]
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});

/* ================================================================
   5. NOTIFICATION CLICK: Mở trang tương ứng khi click thông báo
   ================================================================ */
self.addEventListener('notificationclick', event => {
    event.notification.close();

    if (event.action === 'close') return;

    const targetUrl = (event.notification.data && event.notification.data.url) ? event.notification.data.url : 'history.php';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(clientList => {
            // Nếu đã có tab đang mở trang này thì focus vào
            for (const client of clientList) {
                if (client.url.includes(targetUrl) && 'focus' in client) {
                    return client.focus();
                }
            }
            // Nếu chưa mở thì mở tab mới
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
