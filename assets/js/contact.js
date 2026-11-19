document.addEventListener('DOMContentLoaded', () => {
    const form=document.getElementById('contactForm'), button=document.getElementById('btnContactSubmit'), success=document.getElementById('alertSuccess');
    const errorBox=document.createElement('div');errorBox.className='contact-feedback contact-feedback-error d-none';errorBox.setAttribute('role','alert');errorBox.setAttribute('aria-live','assertive');form.prepend(errorBox);
    let submitting=false;
    form.addEventListener('submit',async event=>{
        event.preventDefault();if(submitting || !form.reportValidity())return;
        submitting=true;button.disabled=true;success.classList.add('d-none');errorBox.classList.add('d-none');
        const original=button.innerHTML;button.textContent='Đang gửi...';
        try {
            const data=Object.fromEntries(['name','phone','email','subject','message'].map(key=>[key,document.getElementById('contact'+key[0].toUpperCase()+key.slice(1)).value.trim()]));
            data.subject ||= 'Liên hệ từ website';
            const {csrf_token}=await ContentClient.request('api/contact.php');
            await ContentClient.request('api/contact.php',{method:'POST',headers:{'Content-Type':'application/json','X-GODMINTON-CSRF':csrf_token},body:JSON.stringify(data)});
            success.classList.remove('d-none');form.reset();success.scrollIntoView({behavior:'smooth',block:'nearest'});
        } catch(error) {errorBox.textContent=error.message;errorBox.classList.remove('d-none');}
        finally {submitting=false;button.disabled=false;button.innerHTML=original;}
    });
});
