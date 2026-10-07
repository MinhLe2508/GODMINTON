/**
 * GODMINTON — Auth JavaScript Helper
 * Phụ trách: P3 (Trương Minh Triết)
 * Cập nhật Task #6: Kiểm tra form đăng ký 3 trường phía client, gỡ chọn role.
 */

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

/* ---- Tab switch ---- */
function switchTab(tab) {
    document.getElementById('panelLogin')?.classList.toggle('active', tab === 'login');
    document.getElementById('panelRegister')?.classList.toggle('active', tab === 'register');
    document.getElementById('tabLoginBtn')?.classList.toggle('active', tab === 'login');
    document.getElementById('tabRegisterBtn')?.classList.toggle('active', tab === 'register');
    history.replaceState(null, '', tab === 'register' ? '#register' : '#login');
}

/* Check URL hash on load */
if (window.location.hash === '#register') switchTab('register');

/* ---- Password eye toggle ---- */
function toggleEye(inputId, btn) {
    const inp = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (!inp) return;
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

/* Helper hiển thị lỗi từng trường */
function showFieldError(inputEl, errorEl, message) {
    if (inputEl) inputEl.classList.add('is-invalid-field');
    if (errorEl) {
        errorEl.innerHTML = `<i class="fa-solid fa-circle-exclamation me-1"></i>${message}`;
        errorEl.classList.remove('d-none');
    }
}

function clearFieldError(inputEl, errorEl) {
    if (inputEl) inputEl.classList.remove('is-invalid-field');
    if (errorEl) {
        errorEl.innerHTML = '';
        errorEl.classList.add('d-none');
    }
}

/* ---- Login submit ---- */
document.getElementById('loginForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('btnLoginSubmit');
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;

    if (window.GodmintonUI) {
        GodmintonUI.setButtonLoading(btn, 'Đang xác thực...');
    } else {
        btn.disabled = true;
    }

    setTimeout(() => {
        const result = (typeof MockDB !== 'undefined' && MockDB.login)
            ? MockDB.login(email, password)
            : { status: 'success', data: { user: { name: 'Người dùng', role: 'customer' } } };

        if (result.status === 'success') {
            if (window.GodmintonUI) {
                GodmintonUI.toast('success', 'Đăng nhập thành công', `Chào mừng trở lại, ${result.data.user.name}!`);
            }
            Swal.fire({
                icon: 'success',
                title: `Chào mừng, ${result.data.user.name}!`,
                timer: 1200, showConfirmButton: false
            }).then(() => {
                const role = result.data.user.role;
                const fallback = (role === 'owner' || role === 'admin') ? 'admin-dashboard.php' : 'index.php';
                const redirect = new URLSearchParams(window.location.search).get('redirect') || fallback;
                window.location.href = redirect.replace('.html', '.php');
            });
        } else {
            if (window.GodmintonUI) {
                GodmintonUI.toast('error', 'Sai thông tin đăng nhập', result.message || 'Email hoặc mật khẩu không chính xác.');
                GodmintonUI.resetButton(btn);
            } else {
                Swal.fire({ icon: 'error', title: 'Sai thông tin', text: result.message, confirmButtonColor: '#0d9488' });
                btn.disabled = false;
            }
        }
    }, 600);
});

/* ---- Register submit (Task #6: 3 trường chuẩn hóa) ---- */
document.getElementById('registerForm')?.addEventListener('submit', function(e) {
    e.preventDefault();

    const emailInp = document.getElementById('regEmail');
    const nameInp = document.getElementById('regName');
    const passInp = document.getElementById('regPassword');
    const confirmInp = document.getElementById('regConfirmPassword');

    const emailErr = document.getElementById('regEmailError');
    const nameErr = document.getElementById('regNameError');
    const passErr = document.getElementById('regPasswordError');
    const confirmErr = document.getElementById('regConfirmPasswordError');

    // Reset lỗi cũ
    clearFieldError(emailInp, emailErr);
    clearFieldError(nameInp, nameErr);
    clearFieldError(passInp, passErr);
    clearFieldError(confirmInp, confirmErr);

    let hasError = false;

    // 1. Kiểm tra Email
    const email = (emailInp?.value || '').trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email) {
        showFieldError(emailInp, emailErr, 'Vui lòng nhập địa chỉ email.');
        hasError = true;
    } else if (!emailRegex.test(email)) {
        showFieldError(emailInp, emailErr, 'Email không hợp lệ (ví dụ: name@example.com).');
        hasError = true;
    }

    // 2. Kiểm tra Tên hiển thị
    const name = (nameInp?.value || '').trim();
    if (!name) {
        showFieldError(nameInp, nameErr, 'Vui lòng nhập tên hiển thị.');
        hasError = true;
    } else if (name.length < 2) {
        showFieldError(nameInp, nameErr, 'Tên hiển thị phải có ít nhất 2 ký tự.');
        hasError = true;
    }

    // 3. Kiểm tra Mật khẩu & Xác nhận
    const password = passInp?.value || '';
    const confirmPassword = confirmInp?.value || '';

    if (!password) {
        showFieldError(passInp, passErr, 'Vui lòng nhập mật khẩu.');
        hasError = true;
    } else if (password.length < 6) {
        showFieldError(passInp, passErr, 'Mật khẩu phải chứa ít nhất 6 ký tự.');
        hasError = true;
    }

    if (!confirmPassword) {
        showFieldError(confirmInp, confirmErr, 'Vui lòng nhập lại mật khẩu.');
        hasError = true;
    } else if (password !== confirmPassword) {
        showFieldError(confirmInp, confirmErr, 'Mật khẩu xác nhận không trùng khớp.');
        hasError = true;
    }

    if (hasError) return;

    // Gửi form khi đã pass kiểm tra client
    const btn = document.getElementById('btnRegisterSubmit');
    if (window.GodmintonUI) {
        GodmintonUI.setButtonLoading(btn, 'Đang tạo tài khoản...');
    } else {
        btn.disabled = true;
    }

    setTimeout(() => {
        // Luôn cấp role là 'customer' theo quy định QD01, QD03
        const result = (typeof MockDB !== 'undefined' && MockDB.register)
            ? MockDB.register({ name, email, password, role: 'customer' })
            : { status: 'success', data: { user: { name, role: 'customer' } } };

        if (result.status === 'success') {
            if (window.GodmintonUI) {
                GodmintonUI.toast('success', 'Đăng ký thành công', 'Tài khoản khách hàng đã được khởi tạo!');
            }
            Swal.fire({
                icon: 'success',
                title: 'Đăng ký thành công!',
                text: 'Chào mừng bạn đến với GODMINTON. Bạn đã được cấp tài khoản Khách hàng.',
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                window.location.href = 'index.php';
            });
        } else {
            showFieldError(emailInp, emailErr, result.message || 'Email này đã tồn tại trên hệ thống.');
            if (window.GodmintonUI) {
                GodmintonUI.resetButton(btn);
            } else {
                btn.disabled = false;
            }
        }
    }, 700);
});