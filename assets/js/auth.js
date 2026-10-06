(function() {
    const saved = localStorage.getItem('bm_theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', saved);
    const btn = document.getElementById('themeToggle');
    if (btn) btn.innerHTML = saved === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
})();

document.getElementById('themeToggle')?.addEventListener('click', function() {
    const cur = document.documentElement.getAttribute('data-bs-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('bm_theme', next);
    this.innerHTML = next === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
});

/* ---- Tab switch (Không redirect cưỡng bức) ---- */
function switchTab(tab) {
    document.getElementById('panelLogin')?.classList.toggle('active', tab === 'login');
    document.getElementById('panelRegister')?.classList.toggle('active', tab === 'register');
    document.getElementById('tabLoginBtn')?.classList.toggle('active', tab === 'login');
    document.getElementById('tabRegisterBtn')?.classList.toggle('active', tab === 'register');
    // URL hash for shareability
    history.replaceState(null, '', tab === 'register' ? '#register' : '#login');
}

/* Check URL hash on load */
if (window.location.hash === '#register') switchTab('register');

/* ---- Password eye toggle ---- */
function toggleEye(inputId, btn) {
    const inp = document.getElementById(inputId);
    const icon = btn.querySelector('i');
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
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = pass;
    switchTab('login');
}

/* ---- Login submit ---- */
document.getElementById('loginForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const btn = document.getElementById('btnLoginSubmit');
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang xác thực...';

    setTimeout(() => {
        const result = MockDB.login(email, password);
        if (result.status === 'success') {
            Swal.fire({
                icon: 'success',
                title: `Chào mừng, ${result.data.user.name}!`,
                timer: 1200, showConfirmButton: false
            }).then(() => {
                const role = result.data.user.role;
                const fallback = (role === 'owner' || role === 'admin') ? 'admin-dashboard.html' : 'index.html';
                const redirect = new URLSearchParams(window.location.search).get('redirect') || fallback;
                window.location.href = redirect;
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Sai thông tin', text: result.message, confirmButtonColor: '#0d9488' });
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-right-to-bracket me-2"></i>Đăng Nhập';
        }
    }, 700);
});

/* ---- Register submit ---- */
document.getElementById('registerForm')?.addEventListener('submit', e => {
    e.preventDefault();
    const password = document.getElementById('regPassword').value;
    const passwordConfirm = document.getElementById('regConfirmPassword').value;
    if (password !== passwordConfirm) {
        Swal.fire({ icon: 'warning', title: 'Mật khẩu không khớp', confirmButtonColor: '#0d9488' });
        return;
    }
    const btn = document.getElementById('btnRegisterSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang tạo tài khoản...';

    setTimeout(() => {
        const result = MockDB.register({
            name: document.getElementById('regName').value.trim(),
            email: document.getElementById('regEmail').value.trim(),
            phone: document.getElementById('regPhone').value.trim(),
            password,
            role: document.getElementById('regRole').value
        });
        if (result.status === 'success') {
            Swal.fire({
                icon: 'success', title: 'Đăng ký thành công!',
                text: 'Tài khoản của bạn đã được kích hoạt.',
                timer: 1500, showConfirmButton: false
            }).then(() => {
                const role = result.data.user.role;
                window.location.href = (role === 'owner' || role === 'admin') ? 'admin-dashboard.html' : 'index.html';
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Đăng ký thất bại', text: result.message, confirmButtonColor: '#0d9488' });
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-user-plus me-2"></i>Tạo Tài Khoản Miễn Phí';
        }
    }, 800);
});