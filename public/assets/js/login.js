/* ---- Theme ---- */
(function () {
    const saved = localStorage.getItem('bm_theme') || 'light';
    document.documentElement.setAttribute('data-bs-theme', saved);
    const btn = document.getElementById('themeToggle');
    if (btn) btn.innerHTML = saved === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
})();

document.getElementById('themeToggle').addEventListener('click', function () {
    const cur = document.documentElement.getAttribute('data-bs-theme');
    const next = cur === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-bs-theme', next);
    localStorage.setItem('bm_theme', next);
    this.innerHTML = next === 'dark' ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>';
});

/* ---- Password toggle ---- */
document.getElementById('btnEye').addEventListener('click', function () {
    const inp = document.getElementById('loginPassword');
    const icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type = 'text';
        icon.className = 'fa-regular fa-eye-slash';
    } else {
        inp.type = 'password';
        icon.className = 'fa-regular fa-eye';
    }
});

/* ---- Demo fill ---- */
function fillAccount(email, pass) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = pass;
}

/* ---- Form initialization (Không redirect cưỡng bức) ---- */
document.addEventListener('DOMContentLoaded', () => {
    // Để trống - không tự động chuyển hướng để trang đăng nhập luôn truy cập được
});


/* ---- Login submit ---- */
document.getElementById('loginForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    const btn = document.getElementById('btnLoginSubmit');

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
                const fallback = (role === 'owner' || role === 'admin') ? 'admin-dashboard.php' : 'index.php';
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