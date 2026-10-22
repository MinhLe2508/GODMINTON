/**
 * GODMINTON - Authentication Script
 * Xử lý Đăng Nhập, Đăng Ký, Đổi Tab, Theme Toggle
 */

function updateLogoTheme() {
    document.querySelectorAll('.brand-logo-img').forEach(img => {
        img.src = 'assets/img/logo.svg';
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
    if (typeof updateThemeIcon === 'function') {
        updateThemeIcon(saved);
    } else {
        updateThemeButton(saved);
    }
    updateLogoTheme();
})();

// Đảm bảo updateLogoTheme khả dụng cho toggleTheme() trong app.js
window.updateLogoTheme = updateLogoTheme;

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


let authCsrf = null;
async function callAuthApi(url, payload) {
    try {
        if (!authCsrf) {
            const context = await fetch('api/auth/context.php', {credentials:'same-origin', cache:'no-store'});
            const result = await context.json();
            if (!context.ok || !result.data?.csrf_token) throw new Error('context');
            authCsrf = result.data.csrf_token;
        }
        const response = await fetch(url, {method:'POST', credentials:'same-origin', cache:'no-store',
            headers:{'Content-Type':'application/json','X-GODMINTON-CSRF':authCsrf}, body:JSON.stringify(payload)});
        const result = await response.json();
        if (response.status===403 && result.code!=='EMAIL_NOT_VERIFIED') authCsrf=null;
        return result;
    } catch {
        return {status:'error', message:'Không kết nối được máy chủ. Vui lòng thử lại.'};
    }
}
function goAfterAuth(role, ownerIntent=false) {
    const fallback = (role==='owner'||role==='admin') ? 'admin-dashboard.php' : (ownerIntent?'owner-application.php':'index.php');
    const value = new URLSearchParams(location.search).get('redirect');
    let target = new URL(fallback, location.href);
    if (value) {
        try {
            const candidate = new URL(value, location.href);
            const base = new URL('.', location.href).pathname;
            if (candidate.origin===location.origin && candidate.pathname.startsWith(base)
                && !/\/(?:auth|auth-action)\.php$/i.test(candidate.pathname)) target=candidate;
        } catch { /* Keep the role-specific destination. */ }
    }
    location.assign(target.href);
}
document.getElementById('loginForm')?.addEventListener('submit', async event => {
    event.preventDefault();const button=document.getElementById('btnLoginSubmit');const label=button.innerHTML;
    button.disabled=true;button.textContent='Đang xác thực…';
    try {
        const result=await callAuthApi('api/auth/login.php', {email:document.getElementById('loginEmail').value.trim(),password:document.getElementById('loginPassword').value});
        if(result.status==='success') {
            authCsrf=result.data.csrf_token;
            await Swal.fire({icon:'success',titleText:'Đăng nhập thành công',timer:1000,showConfirmButton:false});
            goAfterAuth(result.data.user.role,Boolean(Number(result.data.user.owner_intent)));
        } else {
            const reply=await Swal.fire({icon:'warning',titleText:'Chưa thể đăng nhập',text:result.message,
                showCancelButton:result.code==='EMAIL_NOT_VERIFIED',confirmButtonText:result.code==='EMAIL_NOT_VERIFIED'?'Gửi lại xác minh':'Đóng',cancelButtonText:'Đóng'});
            if(reply.isConfirmed && result.code==='EMAIL_NOT_VERIFIED') await requestAuthEmail('resend-verification');
        }
    } finally {button.disabled=false;button.innerHTML=label;}
});
document.getElementById('registerForm')?.addEventListener('submit',async event=>{
    event.preventDefault();const password=document.getElementById('regPassword').value;
    if(password!==document.getElementById('regConfirmPassword').value) {
        await Swal.fire({icon:'warning',titleText:'Mật khẩu không khớp',text:'Vui lòng nhập lại mật khẩu xác nhận.'});return;
    }
    const button=document.getElementById('btnRegisterSubmit');const label=button.innerHTML;
    button.disabled=true;button.textContent='Đang gửi email xác minh…';
    try {
        const result=await callAuthApi('api/auth/register.php',{
            name:document.getElementById('regName').value.trim(),email:document.getElementById('regEmail').value.trim(),
            phone:document.getElementById('regPhone').value.trim(),password,role:document.getElementById('regRole').value,
            agree_terms:document.getElementById('agreeTerms').checked});
        await Swal.fire({icon:result.status==='success'?'success':'error',titleText:result.status==='success'?'Kiểm tra email của bạn':'Chưa thể đăng ký',text:result.message});
        if(result.status==='success') {document.getElementById('loginEmail').value=document.getElementById('regEmail').value;document.getElementById('registerForm').reset();switchTab('login');}
    } finally {button.disabled=false;button.innerHTML=label;}
});
async function requestAuthEmail(action) {
    const reset=action==='forgot-password';
    const result=await Swal.fire({titleText:reset?'Quên mật khẩu?':'Gửi lại email xác minh',
        text:'Nhập email đăng nhập. Liên kết chỉ dùng một lần và có hạn sử dụng.',input:'email',
        inputValue:document.getElementById('loginEmail')?.value||'',inputPlaceholder:'name@example.com',
        showCancelButton:true,confirmButtonText:'Gửi hướng dẫn',cancelButtonText:'Hủy',showLoaderOnConfirm:true,
        allowOutsideClick:()=>!Swal.isLoading(),
        preConfirm:async email=>{
            if(!email){Swal.showValidationMessage('Vui lòng nhập email.');return false;}
            const response=await callAuthApi('api/auth/'+action+'.php',{email:email.trim()});
            if(response.status!=='success'){Swal.showValidationMessage(response.message);return false;}
            return response;
        }});
    if(result.isConfirmed) await Swal.fire({icon:'info',titleText:'Yêu cầu đã được tiếp nhận',text:result.value.message});
}
function handleForgotPassword(){return requestAuthEmail('forgot-password');}
