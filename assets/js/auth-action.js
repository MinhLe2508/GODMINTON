'use strict';
const mode=new URLSearchParams(location.search).get('mode');
let token=new URLSearchParams(location.hash.slice(1)).get('token')||'';
// Remove the secret from the address bar without sending it in a GET or referrer.
history.replaceState(null,'',location.pathname+location.search);
const form=document.getElementById('accountAction');
const message=document.getElementById('actionMessage');
const button=document.getElementById('actionSubmit');
if(mode==='reset'){
    document.getElementById('actionTitle').textContent='Đặt mật khẩu mới';
    document.getElementById('actionDescription').textContent='Sau khi đổi mật khẩu, các phiên đăng nhập cũ sẽ được đăng xuất.';
    document.getElementById('passwordFields').hidden=false;
    document.getElementById('newPassword').required=true;
    document.getElementById('confirmPassword').required=true;
    button.textContent='Lưu mật khẩu mới';
}
if(mode==='delete'){
    document.getElementById('actionTitle').textContent='Xóa tài khoản';
    document.getElementById('actionDescription').textContent='Tài khoản sẽ bị vô hiệu vĩnh viễn, thông tin hồ sơ được ẩn danh. Lịch sử giao dịch vẫn được giữ để đối soát. Thao tác không thể hoàn tác.';
    document.getElementById('deleteConfirmation').hidden=false;
    document.getElementById('confirmDeleteAccount').required=true;
    button.textContent='Xác nhận xóa tài khoản';button.classList.add('danger');
}
if(!['reset','verify','delete'].includes(mode)||!/^[a-f0-9]{64}$/.test(token)){
    form.hidden=true;message.className='error';message.textContent='Liên kết không hợp lệ. Vui lòng yêu cầu gửi lại email tại trang đăng nhập.';
}
form.addEventListener('submit',async event=>{
    event.preventDefault();message.textContent='';
    const password=document.getElementById('newPassword').value;
    if(mode==='reset' && password!==document.getElementById('confirmPassword').value){message.className='error';message.textContent='Mật khẩu xác nhận không khớp.';return;}
    button.disabled=true;const label=button.textContent;button.textContent='Đang xử lý…';
    try{
        const context=await fetch('api/auth/context.php',{credentials:'same-origin',cache:'no-store'});
        const data=await context.json();if(!context.ok)throw new Error('Không thể tạo phiên thao tác.');
        const response=await fetch('api/auth/consume-token.php',{method:'POST',credentials:'same-origin',cache:'no-store',
            headers:{'Content-Type':'application/json','X-GODMINTON-CSRF':data.data.csrf_token},body:JSON.stringify({token,mode,password,confirm_delete:document.getElementById('confirmDeleteAccount').checked})});
        const result=await response.json();message.textContent=result.message;
        message.className=response.ok?'success':'error';
        if(response.ok){token='';form.reset();form.hidden=true;}
        else if(response.status===410)form.hidden=true;
    }catch(error){message.className='error';message.textContent=error.message||'Không kết nối được máy chủ.';}
    finally{button.disabled=false;button.textContent=label;}
});
