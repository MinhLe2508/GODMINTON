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
            <a href="history.php" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                <i class="fa-solid fa-calendar-check me-1"></i> Lịch của tôi
            </a>
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

    <p class="small text-muted mt-4">
        &copy; 2026 GODMINTON PWA — Trải nghiệm liền mạch cả khi mất kết nối.
    </p>

    <script>
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
