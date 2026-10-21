/* Live password guidance for registration, reset and profile; passwords stay in the input. */
(() => {
    'use strict';
    const tests={length:value=>Array.from(value).length>=8,upper:value=>/\p{Lu}/u.test(value),
        lower:value=>/\p{Ll}/u.test(value),number:value=>/\p{Nd}/u.test(value),special:value=>/[\p{P}\p{S}]/u.test(value)};
    document.querySelectorAll('[data-password-for]').forEach(list=>{
        const input=document.getElementById(list.dataset.passwordFor);
        const status=document.querySelector('[data-password-status="'+list.dataset.passwordFor+'"]');
        if(!input)return;
        let previousCount=-1;
        const update=()=>{
            const value=input.value;let count=0;
            list.querySelectorAll('[data-password-rule]').forEach(item=>{
                const met=tests[item.dataset.passwordRule](value);if(met)count++;
                item.classList.toggle('is-met',met);
                item.querySelector('.password-rule-icon').textContent=met?'✓':'○';
            });
            input.setCustomValidity(value && (count!==5 || value.includes('\0'))
                ?'Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường, chữ số và ký tự đặc biệt.':'');
            if(status && count!==previousCount){status.textContent=value?'Đã đạt '+count+'/5 điều kiện mật khẩu.':'';previousCount=count;}
        };
        input.addEventListener('input',update);input.addEventListener('change',update);
        input.form?.addEventListener('reset',()=>queueMicrotask(update));
        window.addEventListener('pageshow',update);update();
    });
})();
