/**
 * GODMINTON - Authentication Script
 * Xử lý Đăng Nhập, Đăng Ký, Đổi Tab, Google OAuth mô phỏng, Theme Toggle
 */

function updateLogoTheme() {
    const isLight = document.documentElement.getAttribute('data-bs-theme') === 'light';
    document.querySelectorAll('.brand-logo-img').forEach(img => {
        img.src = isLight ? 'assets/img/logo-teal.svg' : 'assets/img/logo.svg';
    });
}

function updateThemeButton(theme) {
    const btn = document.getElementById('themeToggle');
    if (!btn) return;
    if (theme === 'dark') {
        btn.innerHTML = '<i class="fa-solid fa-sun" style="color:#fbbf24;"></i>';
        btn.setAttribute('title', 'Chuyển sang chế độ Sáng');
    } else {
        btn.innerHTML = '<i class="fa-solid fa-moon"></i>';
        btn.setAttribute('title', 'Chuyển sang chế độ Tối');
    }
}

(function() {
    const saved = localStorage.getItem('bm_theme') || localStorage.getItem('godminton_theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', saved);
    updateThemeButton(saved);
    updateLogoTheme();
})();

document.getElementById('themeToggle')?.addEventListener('click', function() {
    const cur = document.documentElement.getAttribute('data-bs-theme') || 'light';
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('bm_theme', next);
    localStorage.setItem('godminton_theme', next);
    updateThemeButton(next);
    updateLogoTheme();
});

/* ---- Tab switch ---- */
function switchTab(tab) {
    document.getElementById('panelLogin')?.classList.toggle('active', tab === 'login');
    document.getElementById('panelRegister')?.classList.toggle('active', tab === 'register');
    document.getElementById('tabLoginBtn')?.classList.toggle('active', tab === 'login');
    document.getElementById('tabRegisterBtn')?.classList.toggle('active', tab === 'register');
    history.replaceState(null, '', tab === 'register' ? '#register' : '#login');
}

/* Check URL hash on load */
if (window.location.hash === '#register') {
    switchTab('register');
}

/* ---- Password eye toggle ---- */
function toggleEye(inputId, btn) {
    const inp = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!inp || !icon) return;
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
}

/* ---- Demo fill ---- */
function fillLogin(email, pass) {
    const emailEl = document.getElementById('loginEmail');
    const passEl = document.getElementById('loginPassword');
    if (emailEl) emailEl.value = email;
    if (passEl) passEl.value = pass;
    switchTab('login');
}

/* ---- Gọi API PHP ---- */
async function callAuthApi(url, payload) {
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        return await res.json();
    } catch (err) {
        return { status: 'error', message: 'Không kết nối được máy chủ. Hãy kiểm tra Apache/MySQL trong XAMPP.' };
    }
}

function goAfterAuth(role) {
    const fallback = (role === 'owner' || role === 'admin') ? 'admin-dashboard.php' : 'index.php';
    window.location.href = new URLSearchParams(window.location.search).get('redirect') || fallback;
}

/* ---- Login submit ---- */
document.getElementById('loginForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('btnLoginSubmit');
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xác thực...';

    const result = await callAuthApi('api/auth/login.php', { email, password });
    if (result.status === 'success') {
        if (typeof MockDB !== 'undefined' && MockDB.setUser) {
            MockDB.setUser(result.data.user);
        }
        Swal.fire({
            icon: 'success',
            title: `Chào mừng, ${result.data.user.name}!`,
            timer: 1200, showConfirmButton: false
        }).then(() => goAfterAuth(result.data.user.role));
    } else {
        Swal.fire({ icon: 'error', title: 'Sai thông tin', text: result.message, confirmButtonColor: '#0d9488' });
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>ĐĂNG NHẬP';
    }
});

/* ---- Register submit ---- */
document.getElementById('registerForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const password = document.getElementById('regPassword').value;
    const passwordConfirm = document.getElementById('regConfirmPassword').value;
    if (password !== passwordConfirm) {
        Swal.fire({ icon: 'warning', title: 'Mật khẩu không khớp', text: 'Mật khẩu xác nhận phải giống nhau.', confirmButtonColor: '#0d9488' });
        return;
    }
    const btn = document.getElementById('btnRegisterSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang tạo tài khoản...';

    const result = await callAuthApi('api/auth/register.php', {
        name: document.getElementById('regName').value.trim(),
        email: document.getElementById('regEmail').value.trim(),
        phone: document.getElementById('regPhone').value.trim(),
        password,
        role: document.getElementById('regRole').value
    });
    if (result.status === 'success') {
        if (typeof MockDB !== 'undefined' && MockDB.setUser) {
            MockDB.setUser(result.data.user);
        }
        Swal.fire({
            icon: 'success',
            title: 'Đăng ký thành công!',
            text: 'Tài khoản của bạn đã được kích hoạt.',
            timer: 1500, showConfirmButton: false
        }).then(() => goAfterAuth(result.data.user.role));
    } else {
        Swal.fire({ icon: 'error', title: 'Đăng ký thất bại', text: result.message, confirmButtonColor: '#0d9488' });
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>TẠO TÀI KHOẢN MIỄN PHÍ';
    }
});

/* ---- Google Login handler ---- */
function handleGoogleLogin() {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        title: 'Đăng nhập với Google',
        html: `
            <div style="text-align:left; font-size:0.9rem; line-height:1.6;">
                <p class="mb-2">Đăng nhập nhanh và bảo mật qua Google Workspace OAuth.</p>
                <div class="p-2 border rounded bg-light text-dark mb-2 d-flex align-items-center gap-2">
                    <img src="https://www.svgrepo.com/show/475656/google-color.svg" width="22" height="22" alt="Google">
                    <div>
                        <strong style="font-size:0.85rem;">khach1@gmail.com</strong>
                        <div style="font-size:0.75rem; color:#64748b;">Tài khoản Google Demo</div>
                    </div>
                </div>
                <small class="text-muted">Nhấn "Tiếp tục" để xác thực ngay với tài khoản mẫu này.</small>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fa-brands fa-google me-1"></i> Tiếp tục',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            fillLogin('khach1@gmail.com', 'Khach@123');
            document.getElementById('loginForm')?.dispatchEvent(new Event('submit'));
        }
    });
}

/* ---- Forgot password handler ---- */
function handleForgotPassword() {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        title: 'Quên mật khẩu?',
        text: 'Vui lòng nhập địa chỉ email để nhận liên kết đặt lại mật khẩu:',
        input: 'email',
        inputPlaceholder: 'name@example.com',
        inputValue: document.getElementById('loginEmail')?.value || '',
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Gửi yêu cầu',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                icon: 'success',
                title: 'Đã gửi hướng dẫn!',
                text: `Liên kết đặt lại mật khẩu đã được gửi tới ${result.value}. Vui lòng kiểm tra email của bạn.`,
                confirmButtonColor: '#0d9488'
            });
        }
    });
}
