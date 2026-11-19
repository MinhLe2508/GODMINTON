window.OwnerUI=(()=>{
 const e=BookingAccount.escape;
 const statuses={active:'Hoạt động',locked:'Bị khóa',deleted:'Đã xóa',pending:'Chờ duyệt',draft:'Nháp',needs_info:'Cần bổ sung',approved:'Đã duyệt',rejected:'Từ chối',withdrawn:'Đã rút'};
 const kinds={new:'Sân mới',claim:'Nhận quản lý',update:'Cập nhật sân'};
 const badge=s=>`<span class="wf-status ${e(s)}">${e(statuses[s]||s)}</span>`;
 const field=(label,value)=>`<div class="wf-field"><small>${e(label)}</small><div>${e(value??'—')}</div></div>`;
 const table=(heads,rows)=>`<div class="table-responsive"><table class="table wf-table"><thead><tr>${heads.map(h=>`<th>${e(h)}</th>`).join('')}</tr></thead><tbody>${rows.join('')||`<tr><td colspan="${heads.length}" class="text-center text-muted py-4">Không có dữ liệu phù hợp.</td></tr>`}</tbody></table></div>`;
 const button=(label,attrs='',style='outline-secondary')=>`<button type="button" class="btn btn-sm btn-${style} rounded-pill" ${attrs}>${e(label)}</button>`;
 async function sensitive(title,reason=''){
  const result=await Swal.fire({titleText:title,html:'<label class="d-block text-start small">Lý do / ghi chú<textarea id="wfReason" class="form-control mt-2" rows="3" maxlength="1000"></textarea></label><label class="d-block text-start small mt-3">Mật khẩu Admin<input id="wfAdminPassword" type="password" class="form-control mt-2" autocomplete="current-password"></label>',
   didOpen:()=>{document.getElementById('wfReason').value=reason;},showCancelButton:true,confirmButtonText:'Xác nhận',cancelButtonText:'Hủy',
   preConfirm:()=>{const r=document.getElementById('wfReason').value.trim(),p=document.getElementById('wfAdminPassword').value;if(r.length<5||!p){Swal.showValidationMessage('Nhập lý do ít nhất 5 ký tự và mật khẩu Admin.');return false;}return {reason:r,admin_password:p};}});
  return result.isConfirmed?result.value:null;
 }
 const request=(url,body)=>BookingAccount.request(url,body?{method:'POST',body:JSON.stringify(body)}:{});
 function events(rows){return rows.map(r=>`<div class="wf-log"><small>${e(r.created_at)} · ${e(r.actor_name||'Hệ thống')}</small><p><strong>${e(r.action)}</strong></p><p>${e(r.note||r.reason||'')}</p></div>`).join('')||'<p class="text-muted">Chưa có thao tác.</p>';}
 function requestInfo(r){
  const p=r.payload||{};
  const url=r.evidence_url?`<p><a href="${e(r.evidence_url)}" target="_blank" rel="noopener noreferrer">Mở liên kết xác minh do người đăng ký cung cấp ↗</a></p>`:'';
  return `<div class="wf-grid">${field('Loại hồ sơ',kinds[r.kind])}${field('Trạng thái',statuses[r.status])}${field('Tên sân',p.name)}${field('Địa chỉ',p.address)}${field('Người đại diện',r.representative_name)}${field('Số liên hệ',r.representative_phone)}${r.kind!=='claim'?field('Giá / giờ',BookingAccount.money(p.price_per_hour))+field('Số sân con',p.total_sub_courts)+field('Giờ hoạt động',`${p.opening_time||''} – ${p.closing_time||''}`)+field('Tọa độ',`${p.latitude??''}, ${p.longitude??''}`):''}</div><h3>Thông tin xác nhận quyền quản lý</h3><p class="wf-note">${e(r.evidence_note||'Chưa cung cấp')}</p>${url}${p.image_url?`<a href="${e(p.image_url)}" target="_blank" rel="noopener noreferrer">Xem ảnh sân ↗</a>`:''}${r.review_note?`<div class="alert alert-info mt-3"><strong>Phản hồi của Admin</strong><p class="mb-0 wf-note">${e(r.review_note)}</p></div>`:''}${r.duplicates?.length?`<div class="alert alert-warning mt-3"><strong>Có sân có thể trùng</strong>${r.duplicates.map(c=>`<p class="mb-1">#${e(c.id)} · ${e(c.name)} — ${e(c.address)}</p>`).join('')}</div>`:''}<h3>Lịch sử hồ sơ</h3>${events(r.events||[])}`;
 }
 function pager(id,total,page,load){const max=Math.max(1,Math.ceil(total/20));document.getElementById(id).innerHTML=`${button('← Trước','data-page="prev"') }<span>Trang ${page}/${max} · ${total} kết quả</span>${button('Sau →','data-page="next"')}`;document.querySelector(`#${id} [data-page="prev"]`).disabled=page<=1;document.querySelector(`#${id} [data-page="next"]`).disabled=page>=max;document.getElementById(id).onclick=ev=>{const t=ev.target.closest('[data-page]');if(t)load(page+(t.dataset.page==='prev'?-1:1));};}
 function openDetail(html){const detail=document.getElementById('workflowDetail');detail.innerHTML=html;detail.hidden=false;detail.focus({preventScroll:true});detail.scrollIntoView({behavior:'smooth',block:'start'});return detail;}
 async function showUser(id){
  const u=await request('api/admin/users.php?id='+id);const deleted=Boolean(u.deleted_at),role={customer:'Khách hàng',owner:'Chủ sân',admin:'Admin'}[u.role];
  const d=openDetail(`<div class="wf-heading"><h2>${e(u.name)} · #${e(u.id)}</h2>${badge(deleted?'deleted':u.account_status)}</div><div class="wf-grid">${field('Email',u.email)}${field('Số điện thoại',u.phone)}${field('Vai trò',role)}${field('Xác minh',Number(u.is_verified)?'Đã xác minh':'Chưa xác minh')}${field('Đăng ký',u.created_at)}${field('Lý do khóa',u.locked_reason)}</div>${!deleted?`<div class="wf-actions">${button(u.account_status==='locked'?'Mở khóa':'Khóa tài khoản',`data-user-action="${u.account_status==='locked'?'unlock':'lock'}"`,'outline-danger')}${button('Sửa tên / điện thoại','data-user-action="edit"')}${button('Gửi đặt lại mật khẩu','data-user-action="send_reset"')}${button('Yêu cầu đổi email','data-user-action="request_email_change"')}${u.role==='owner'?button('Thu hồi quyền chủ sân','data-user-action="revoke_owner"','outline-danger'):''}</div>`:''}<p class="wf-help">Đổi email cần xác minh hộp thư mới trước khi thay đổi. Admin không xem hoặc đặt mật khẩu thay người dùng.</p><h3>Các sân đang quản lý</h3>${table(['Sân','Trạng thái'],u.courts.map(c=>`<tr><td>#${e(c.id)} · ${e(c.name)}</td><td>${e(c.status==='active'?'Hoạt động':'Tạm ngừng')}</td></tr>`))}<h3>Đơn đặt gần đây</h3>${table(['Mã đơn','Sân','Trạng thái','Tổng tiền'],u.bookings.map(b=>`<tr><td>${e(b.booking_code||b.id)}</td><td>${e(b.court_name)}</td><td>${BookingAccount.badge(b.status)}</td><td>${e(BookingAccount.money(b.total_price))}</td></tr>`))}<h3>Nhật ký quản trị</h3>${events(u.audit)}`);
  d.querySelectorAll('[data-user-action]').forEach(b=>b.onclick=async()=>{
   try{
    const action=b.dataset.userAction;let fields={};
    if(action==='edit'){
     const edit=await Swal.fire({titleText:'Cập nhật thông tin liên hệ',html:'<label class="d-block text-start">Họ tên<input id="wfName" class="form-control mt-2" maxlength="150"></label><label class="d-block text-start mt-3">Số điện thoại<input id="wfPhone" class="form-control mt-2" maxlength="30"></label>',didOpen:()=>{document.getElementById('wfName').value=u.name;document.getElementById('wfPhone').value=u.phone||'';},showCancelButton:true,confirmButtonText:'Tiếp tục',cancelButtonText:'Hủy',preConfirm:()=>({name:document.getElementById('wfName').value.trim(),phone:document.getElementById('wfPhone').value.trim()})});
     if(!edit.isConfirmed)return;fields=edit.value;
    }
    if(action==='request_email_change'){
     const reply=await Swal.fire({titleText:'Email đăng nhập mới',input:'email',inputPlaceholder:'Email của người dùng',text:'Gửi liên kết xác nhận đến địa chỉ mới. Email hiện tại chưa thay đổi.',showCancelButton:true,confirmButtonText:'Tiếp tục',cancelButtonText:'Hủy'});if(!reply.isConfirmed||!reply.value)return;fields={new_email:reply.value.trim()};
    }
    const auth=await sensitive(`${b.textContent}: ${u.name}`);if(!auth)return;b.disabled=true;
    await request('api/admin/users.php',{id:u.id,action,...fields,...auth});await BookingAccount.notify('Đã xử lý và ghi nhật ký.');await showUser(id);await loadUsers(usersPage);
   }catch(error){await BookingAccount.notify(error.message,true);}finally{b.disabled=false;}
  });
 }
 let usersPage=1,requestsPage=1;
 async function loadUsers(page=1){usersPage=page;const qs=new URLSearchParams(new FormData(document.getElementById('usersFilters')));qs.set('page',page);const data=await request('api/admin/users.php?'+qs);document.getElementById('usersResult').innerHTML=table(['Tài khoản','Vai trò','Email','Trạng thái','Hồ sơ chờ',''],data.items.map(u=>`<tr><td><strong>${e(u.name)}</strong><small class="d-block text-muted">${e(u.email)}</small></td><td>${e({customer:'Khách hàng',owner:'Chủ sân',admin:'Admin'}[u.role])}</td><td>${Number(u.is_verified)?'Đã xác minh':'Chưa xác minh'}</td><td>${badge(u.deleted_at?'deleted':u.account_status)}</td><td>${Number(u.pending_requests)}</td><td>${button('Chi tiết',`data-user="${e(u.id)}"`)}</td></tr>`));pager('usersPager',data.total,page,loadUsers);document.querySelectorAll('[data-user]').forEach(b=>b.onclick=()=>showUser(b.dataset.user).catch(error=>BookingAccount.notify(error.message,true)));}
 async function showRequest(id){
  const r=await request('api/admin/owner-requests.php?id='+id);
  const d=openDetail(`<div class="wf-heading"><h2>Hồ sơ #${e(r.id)}</h2>${badge(r.status)}</div><p>${e(r.applicant.name)} · ${e(r.applicant.email)}</p>${r.current_court?`<div class="alert alert-secondary">Sân #${e(r.current_court.id)} đang thuộc tài khoản #${e(r.current_court.owner_id)} — ${e(r.current_court.owner_name)}.</div>`:''}${requestInfo(r)}${r.status==='pending'?`<div class="wf-actions">${button('Duyệt hồ sơ','data-review="approve"','success')}${r.kind==='claim'?button('Xác nhận chuyển giao và duyệt','data-review="transfer_approve"','outline-danger'):''}${button('Yêu cầu bổ sung','data-review="needs_info"','outline-warning')}${button('Từ chối','data-review="reject"','outline-danger')}</div>`:''}`);
  d.querySelectorAll('[data-review]').forEach(b=>b.onclick=async()=>{
   try{
    const action=b.dataset.review;let duplicate_confirmed=false,transfer_confirmed=false;
    if(action==='approve' && r.duplicates?.length){duplicate_confirmed=await BookingAccount.confirm('Đã kiểm tra các sân có thể trùng và xác nhận đây là địa điểm riêng biệt?');if(!duplicate_confirmed)return;}
    if(action==='transfer_approve'){transfer_confirmed=await BookingAccount.confirm('Chỉ tiếp tục khi đã xác nhận việc bàn giao với các bên. Sân sẽ tạm ngừng nhận đặt và chỉ chuyển khi không còn đơn/giữ chỗ đang hoạt động.');if(!transfer_confirmed)return;}
    const auth=await sensitive(`${b.textContent} #${r.id}`);if(!auth)return;b.disabled=true;
    const result=await request('api/admin/owner-requests.php',{id:r.id,revision:r.revision,action,duplicate_confirmed,transfer_confirmed,...auth});
    await BookingAccount.notify(result.email_sent===false?'Đã lưu kết quả. Email chưa gửi được; người dùng có thể xem kết quả trong hồ sơ.':'Đã xử lý hồ sơ.');await showRequest(id);await loadRequests(requestsPage);
   }catch(error){await BookingAccount.notify(error.message,true);}finally{b.disabled=false;}
  });
 }
 async function loadRequests(page=1){requestsPage=page;const qs=new URLSearchParams(new FormData(document.getElementById('requestFilters')));qs.set('page',page);const data=await request('api/admin/owner-requests.php?'+qs);document.getElementById('requestsResult').innerHTML=table(['Hồ sơ / sân','Người đăng ký','Loại','Trạng thái',''],data.items.map(r=>`<tr><td><strong>#${e(r.id)} · ${e(r.payload?.name||'Chưa đặt tên')}</strong><small class="d-block text-muted">${e(r.updated_at)}</small></td><td>${e(r.name)}<small class="d-block text-muted">${e(r.email)}</small></td><td>${e(kinds[r.kind])}</td><td>${badge(r.status)}</td><td>${button('Xem hồ sơ',`data-request="${e(r.id)}"`)}</td></tr>`));pager('requestsPager',data.total,page,loadRequests);document.querySelectorAll('[data-request]').forEach(b=>b.onclick=()=>showRequest(b.dataset.request).catch(error=>BookingAccount.notify(error.message,true)));}
 document.addEventListener('DOMContentLoaded',async()=>{
  const page=document.body.dataset.workflowPage;if(!['users','owner-requests'].includes(page))return;
  AdminApp.renderShell(page,page==='users'?'Quản lý tài khoản':'Yêu cầu chủ sân','Kênh quản trị');
  const form=document.getElementById(page==='users'?'usersFilters':'requestFilters'),load=page==='users'?loadUsers:loadRequests;
  form.addEventListener('submit',event=>{event.preventDefault();load().catch(error=>BookingAccount.notify(error.message,true));});
  form.addEventListener('reset',()=>queueMicrotask(()=>load().catch(error=>BookingAccount.notify(error.message,true))));
  try{await load();}catch(error){document.getElementById(page==='users'?'usersResult':'requestsResult').textContent=error.message;}
 });
 return {e,badge,field,table,button,events,requestInfo,request,kinds,sensitive};
})();
