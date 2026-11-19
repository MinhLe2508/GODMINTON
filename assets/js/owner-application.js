document.addEventListener('DOMContentLoaded',async()=>{
 if(document.body.dataset.workflowPage!=='application')return;
 if(window.__GODMINTON_USER__?.role==='admin'){location.replace('admin-owner-requests.php');return;}
 const U=OwnerUI,form=document.getElementById('ownerApplicationForm');let rows=[],map=null,pin=null;
 const value=name=>form.elements[name]?.value||'';
 function kind(){const claim=value('kind')==='claim';document.getElementById('claimFields').hidden=!claim;document.getElementById('newCourtFields').hidden=claim;if(map&&!claim)requestAnimationFrame(()=>map.invalidateSize());}
 function syncPin(){const lat=Number(value('latitude')),lng=Number(value('longitude'));if(!map||!value('latitude')||!value('longitude')||!Number.isFinite(lat)||!Number.isFinite(lng))return;pin.setLatLng([lat,lng]);map.setView([lat,lng],15);}
 if(window.L){map=L.map('ownerPinMap').setView([10.7769,106.7009],12);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',maxZoom:19}).addTo(map);pin=L.marker([10.7769,106.7009],{draggable:true}).addTo(map);const set=latlng=>{form.elements.latitude.value=latlng.lat.toFixed(7);form.elements.longitude.value=latlng.lng.toFixed(7);};map.on('click',event=>{pin.setLatLng(event.latlng);set(event.latlng);});pin.on('dragend',()=>set(pin.getLatLng()));}
 else document.getElementById('ownerPinMap').innerHTML='<p class="p-3">Không tải được bản đồ. Bạn vẫn có thể nhập tọa độ bên dưới.</p>';
 form.elements.kind.onchange=kind;form.elements.latitude.onchange=syncPin;form.elements.longitude.onchange=syncPin;
 function syncImagePreview(url){const wrap=document.getElementById('applicationImagePreviewWrap'),img=document.getElementById('applicationImagePreview');if(!wrap||!img)return;const clean=String(url||'').trim();if(clean){img.src=clean;wrap.classList.remove('d-none');}else{img.removeAttribute('src');wrap.classList.add('d-none');}}
 form.elements.image_url?.addEventListener('input',e=>syncImagePreview(e.target.value));
 document.getElementById('applicationImageFile')?.addEventListener('change',async e=>{
  const file=e.target.files?.[0];if(!file)return;
  const label=document.getElementById('applicationImageUploadLabel'),oldHtml=label?label.innerHTML:'';
  if(label){label.classList.add('disabled');label.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Đang tải...';}
  try{
   const fd=new FormData();fd.append('image',file);
   const data=await BookingAccount.request('api/upload-image.php?type=court',{method:'POST',body:fd});
   if(data?.url){form.elements.image_url.value=data.url;syncImagePreview(data.url);}
   await BookingAccount.notify('Đã tải ảnh đại diện sân lên thành công!');
  }catch(err){await BookingAccount.notify(err.message||'Không thể tải ảnh lên.',true);}
  finally{e.target.value='';if(label){label.classList.remove('disabled');label.innerHTML=oldHtml;}}
 });
 function reset(){form.reset();form.elements.id.value='';form.elements.revision.value='';form.elements.representative_name.value=window.__GODMINTON_USER__.name||'';form.elements.representative_phone.value=window.__GODMINTON_USER__.phone||'';document.getElementById('applicationTitle').textContent='Hồ sơ đăng ký sân';document.getElementById('applicationMessage').textContent='';syncImagePreview('');kind();}
 async function load(){
  const data=await U.request('api/owner-requests.php');rows=data.requests;
  const ward=value('ward_id');document.getElementById('applicationWard').innerHTML='<option value="0">Chọn phường</option>'+data.wards.map(w=>`<option value="${U.e(w.id)}">${U.e(w.name)}${w.old_area_label?' · '+U.e(w.old_area_label):''}</option>`).join('');if(ward)form.elements.ward_id.value=ward;
  const checked=Array.from(form.querySelectorAll('[name="amenities"]:checked')).map(x=>x.value);document.getElementById('applicationAmenities').innerHTML=data.amenities.map(a=>`<label><input type="checkbox" name="amenities" value="${U.e(a.code)}" ${checked.includes(a.code)?'checked':''}>${U.e(a.label)}</label>`).join('');
  document.getElementById('myApplications').innerHTML=rows.length?rows.map(r=>`<article class="wf-request-card">${U.badge(r.status)}<h3>#${U.e(r.id)} · ${U.e(r.payload?.name||'Hồ sơ chưa đặt tên')}</h3><p>${U.e(U.kinds[r.kind])} · ${U.e(r.updated_at)}</p>${r.review_note?`<p>${U.e(r.review_note)}</p>`:''}<div class="d-flex gap-2 flex-wrap">${U.button('Xem',`data-view="${r.id}"`)}${['draft','needs_info','rejected'].includes(r.status)&&r.kind!=='update'?U.button('Chỉnh sửa',`data-edit="${r.id}"`):''}${['draft','pending','needs_info','rejected'].includes(r.status)?U.button('Rút hồ sơ',`data-withdraw="${r.id}"`,'outline-danger'):''}${r.kind==='update'&&['needs_info','rejected'].includes(r.status)?`<a class="btn btn-sm btn-outline-success rounded-pill" href="admin-courts.php?edit=${U.e(r.court_id)}">Bổ sung tại quản lý sân</a>`:''}${r.status==='approved'?'<a class="btn btn-sm btn-teal rounded-pill" href="admin-courts.php">Quản lý sân</a>':''}</div></article>`).join(''):'<p class="text-muted">Bạn chưa có hồ sơ. Lưu nháp hoặc gửi hồ sơ đầu tiên ở bên cạnh.</p>';
  document.querySelectorAll('[data-view]').forEach(b=>b.onclick=()=>detail(b.dataset.view));
  document.querySelectorAll('[data-edit]').forEach(b=>b.onclick=()=>edit(b.dataset.edit));
  document.querySelectorAll('[data-withdraw]').forEach(b=>b.onclick=async()=>{try{const r=rows.find(x=>String(x.id)===b.dataset.withdraw);if(!await BookingAccount.confirm('Rút hồ sơ này?'))return;await U.request('api/owner-requests.php',{id:r.id,revision:r.revision,action:'withdraw'});await load();}catch(error){BookingAccount.notify(error.message,true);}});
 }
 async function detail(id){try{const r=await U.request('api/owner-requests.php?id='+id);const el=document.getElementById('myApplicationDetail');el.hidden=false;el.innerHTML=`<h2 class="h5">Hồ sơ #${U.e(r.id)}</h2>${U.requestInfo(r)}`;el.scrollIntoView({behavior:'smooth',block:'start'});}catch(error){BookingAccount.notify(error.message,true);}}
 async function edit(id){try{const r=await U.request('api/owner-requests.php?id='+id);reset();for(const key of ['id','revision','kind','representative_name','representative_phone','evidence_note','evidence_url'])form.elements[key].value=r[key]??'';for(const [key,val]of Object.entries(r.payload||{}))if(key!=='amenities'&&form.elements[key])form.elements[key].value=val??'';syncImagePreview(r.payload?.image_url||'');form.querySelectorAll('[name="amenities"]').forEach(c=>c.checked=(r.payload?.amenities||[]).includes(c.value));if(r.kind==='claim'){document.getElementById('claimCourt').innerHTML=`<option value="${U.e(r.court_id)}">#${U.e(r.court_id)} · ${U.e(r.payload.name)}</option>`;}document.getElementById('applicationTitle').textContent='Chỉnh sửa hồ sơ #'+id;kind();syncPin();form.scrollIntoView({behavior:'smooth'});}catch(error){BookingAccount.notify(error.message,true);}}
 async function save(action){
  const buttons=[document.getElementById('saveApplication'),document.getElementById('submitApplication')];buttons.forEach(b=>b.disabled=true);
  try{
   const fields=Object.fromEntries(new FormData(form));const payload={};for(const k of ['name','address','ward_id','latitude','longitude','price_per_hour','total_sub_courts','opening_time','closing_time','description','image_url'])payload[k]=fields[k]||(['ward_id','latitude','longitude','price_per_hour','total_sub_courts'].includes(k)?0:'');payload.amenities=Array.from(form.querySelectorAll('[name="amenities"]:checked')).map(c=>c.value);
   const body={action,kind:fields.kind,court_id:fields.court_id,representative_name:fields.representative_name,representative_phone:fields.representative_phone,evidence_note:fields.evidence_note,evidence_url:fields.evidence_url,payload};if(fields.id){body.id=fields.id;body.revision=fields.revision;}
   const result=await U.request('api/owner-requests.php',body);document.getElementById('applicationMessage').textContent=action==='submit'?'Đã gửi hồ sơ, vui lòng chờ Admin xét duyệt.':'Đã lưu nháp.';
   if(result.duplicates?.length)await Swal.fire({icon:'warning',titleText:'Có sân có thể trùng',text:result.duplicates.map(c=>c.name+' — '+c.address).join('\n')+'\nAdmin sẽ kiểm tra trước khi duyệt.'});
   reset();await load();await detail(result.id);
  }catch(error){document.getElementById('applicationMessage').textContent=error.message;await BookingAccount.notify(error.message,true);}finally{buttons.forEach(b=>b.disabled=false);}
 }
 form.onsubmit=event=>{event.preventDefault();save('submit');};document.getElementById('saveApplication').onclick=()=>save('save');document.getElementById('newApplication').onclick=reset;document.getElementById('refreshApplications').onclick=()=>load().catch(error=>BookingAccount.notify(error.message,true));
 document.getElementById('searchExistingCourt').onclick=async()=>{try{const rows=await U.request('api/owner-court-search.php?search='+encodeURIComponent(document.getElementById('claimSearch').value));document.getElementById('claimCourt').innerHTML=rows.length?rows.map(c=>`<option value="${U.e(c.id)}">#${U.e(c.id)} · ${U.e(c.name)} — ${U.e(c.address)}</option>`).join(''):'<option value="">Không tìm thấy sân phù hợp</option>';}catch(error){BookingAccount.notify(error.message,true);}};
 try{await load();reset();}catch(error){document.getElementById('myApplications').textContent=error.message;}
});
