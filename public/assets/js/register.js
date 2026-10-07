document.addEventListener('DOMContentLoaded', () => {
    // Không tự động chuyển hướng để trang đăng ký luôn truy cập được


    const form = document.getElementById('registerForm');
    const submitBtn = document.getElementById('btnRegisterSubmit');

    form.addEventListener('submit', (e) => {
        e.preventDefault();

        const name = document.getElementById('regName').value.trim();
        const email = document.getElementById('regEmail').value.trim();
        const phone = document.getElementById('regPhone').value.trim();
        const password = document.getElementById('regPassword').value;
        const passwordConfirm = document.getElementById('regConfirmPassword').value;
        const role = document.getElementById('regRole').value;

        if (password !== passwordConfirm) {
            Swal.fire({
                icon: 'warning',
                title: 'Mật khẩu không khớp',
                text: 'Vui lòng kiểm tra lại mật khẩu xác nhận.',
                confirmButtonColor: '#0d9488'
            });
            return;
        }

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang tạo tài khoản...';

        setTimeout(() => {
            const result = MockDB.register({ name, email, phone, password, role });

            if (result.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'Đăng ký thành công!',
                    text: 'Tài khoản của bạn đã được kích hoạt.',
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => {
                    window.location.href = (role === 'owner') ? 'admin-dashboard.php' : 'index.php';
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Đăng ký thất bại',
                    text: result.message || 'Vui lòng kiểm tra lại thông tin.',
                    confirmButtonColor: '#0d9488'
                });
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa-solid fa-user-plus me-1"></i> Đăng Ký Tài Khoản';
            }
        }, 800);
    });
});