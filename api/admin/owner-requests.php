<?php
require_once __DIR__.'/../../includes/owner-workflow.php';
try {
 $actor=wf_user(true);
 if($_SERVER['REQUEST_METHOD']==='GET'){
  if(isset($_GET['id'])){$r=wf_request(booking_account_id($_GET['id']));$s=$pdo->prepare('SELECT id,name,email,phone,role,account_status,is_verified,deleted_at FROM users WHERE id=?');$s->execute([$r['user_id']]);$r['applicant']=$s->fetch();if($r['court_id']){$s=$pdo->prepare('SELECT c.id,c.name,c.owner_id,u.name AS owner_name FROM courts c JOIN users u ON u.id=c.owner_id WHERE c.id=?');$s->execute([$r['court_id']]);$r['current_court']=$s->fetch();}booking_account_out(200,wf_request_detail($r));}
  $status=(string)($_GET['status']??'pending');$search=trim((string)($_GET['search']??''));$page=max(1,min(100000,(int)($_GET['page']??1)));$where='1=1';$params=[];
  if($status!=='all'){if(!in_array($status,['draft','pending','needs_info','approved','rejected','withdrawn'],true))wf_fail(422,'Bộ lọc không hợp lệ.');$where.=' AND r.status=?';$params[]=$status;}
  if($search!==''){$where.=' AND (u.name LIKE ? OR u.email LIKE ? OR r.payload LIKE ?)';array_push($params,'%'.$search.'%','%'.$search.'%','%'.$search.'%');}
  $q=$pdo->prepare('SELECT COUNT(*) FROM owner_requests r JOIN users u ON u.id=r.user_id WHERE '.$where);$q->execute($params);$total=(int)$q->fetchColumn();
  $q=$pdo->prepare('SELECT r.id,r.kind,r.status,r.court_id,r.payload,r.updated_at,r.revision,u.name,u.email FROM owner_requests r JOIN users u ON u.id=r.user_id WHERE '.$where.' ORDER BY r.id DESC LIMIT 20 OFFSET '.(($page-1)*20));$q->execute($params);$rows=$q->fetchAll();foreach($rows as &$r)$r['payload']=json_decode($r['payload'],true);unset($r);booking_account_out(200,['items'=>$rows,'total'=>$total,'page'=>$page]);
 }
 $in=wf_post();wf_admin_password($actor,$in);$id=booking_account_id($in['id']??null);$action=wf_text($in,'action',30);$reason=wf_text($in,'reason',1000);
 if(mb_strlen($reason)<5 || !in_array($action,['approve','transfer_approve','needs_info','reject'],true))wf_fail(422,'Chọn thao tác và nhập lý do ít nhất 5 ký tự.');
 $pdo->beginTransaction();wf_lock_admins($actor);
 // Applicant -> request -> court lock order, shared with applicant resubmission.
 $look=wf_request($id);$applicant=wf_lock_user((int)$look['user_id']);$r=wf_request($id,true);
 if($r['status']!=='pending' || (int)($in['revision']??0)!==(int)$r['revision'])wf_fail(409,'Hồ sơ đã được xử lý hoặc thay đổi. Vui lòng tải lại.');
 if(!wf_active($applicant))wf_fail(409,'Tài khoản người đăng ký chưa xác minh, bị khóa hoặc đã xóa.');
 $courtId=$r['court_id']?(int)$r['court_id']:null;$p=$r['payload'];$status=$action==='needs_info'?'needs_info':($action==='reject'?'rejected':'approved');
 if($status==='approved'){
  if($r['kind']==='new'){
   // Serialize duplicate approvals from distinct admins/applicants.
   $pdo->query('SELECT id FROM courts ORDER BY id FOR UPDATE')->fetchAll();$p=wf_payload($p,true);
   $duplicates=wf_duplicates($p);if($duplicates && ($in['duplicate_confirmed']??false)!==true)wf_fail(409,'Có sân cùng tên/địa chỉ hoặc vị trí gần nhau. Kiểm tra trùng và xác nhận rõ trước khi duyệt.');
   if($action==='transfer_approve')wf_fail(422,'Chuyển giao chỉ áp dụng cho sân đã tồn tại.');
   $pdo->prepare("INSERT INTO courts(owner_id,ward_id,name,address,latitude,longitude,price_per_hour,total_sub_courts,opening_time,closing_time,description,status,approval_status,booking_open) VALUES(?,?,?,?,?,?,?,?,?,?,?,'active','approved',1)")->execute([$applicant['id'],$p['ward_id'],$p['name'],$p['address'],$p['latitude'],$p['longitude'],$p['price_per_hour'],$p['total_sub_courts'],$p['opening_time'],$p['closing_time'],$p['description']]);$courtId=(int)$pdo->lastInsertId();
   $pdo->prepare('INSERT INTO court_images(court_id,image_url,sort_order) VALUES(?,?,0)')->execute([$courtId,$p['image_url']]);
   $a=$pdo->prepare('INSERT INTO court_amenities(court_id,amenity_id) SELECT ?,id FROM amenities WHERE code=?');foreach($p['amenities'] as $code)$a->execute([$courtId,$code]);
  }else{
   $s=$pdo->prepare('SELECT * FROM courts WHERE id=? FOR UPDATE');$s->execute([$courtId]);$court=$s->fetch();if(!$court)wf_fail(404,'Sân không còn tồn tại.');
   if($r['kind']==='claim'){
    if((int)$court['owner_id']!==(int)$applicant['id']){
     if($action!=='transfer_approve'||($in['transfer_confirmed']??false)!==true)wf_fail(409,'Sân đang thuộc tài khoản khác. Cần bước xác nhận chuyển giao riêng.');
     $s=$pdo->prepare("SELECT id FROM bookings WHERE court_id=? AND status IN ('pending','confirmed') FOR UPDATE");$s->execute([$courtId]);if($s->fetchColumn())wf_fail(409,'Cần xử lý các đơn đang hoạt động trước khi chuyển giao sân.');
     $s=$pdo->prepare("SELECT id FROM time_slots WHERE court_id=? AND status='holding' AND hold_expires_at>NOW() FOR UPDATE");$s->execute([$courtId]);if($s->fetchColumn())wf_fail(409,'Sân còn phiên giữ chỗ. Chờ hết giữ chỗ trước khi chuyển giao.');
     wf_audit($actor['id'],$applicant['id'],'transfer_court',$reason,$id,$courtId,['previous_owner_id'=>(int)$court['owner_id']]);
    }
    $pdo->prepare("UPDATE courts SET owner_id=?,status='inactive',booking_open=0 WHERE id=?")->execute([$applicant['id'],$courtId]);
   }else{
    if((int)$court['owner_id']!==(int)$applicant['id'])wf_fail(409,'Quyền quản lý sân đã thay đổi. Không thể áp dụng hồ sơ cập nhật.');
    $p=wf_payload($p,true);
    $pdo->prepare("UPDATE courts SET name=?,address=?,ward_id=?,latitude=?,longitude=?,status='inactive',booking_open=0 WHERE id=?")->execute([$p['name'],$p['address'],$p['ward_id'],$p['latitude'],$p['longitude'],$courtId]);
   }
  }
  if($applicant['role']==='customer')$pdo->prepare("UPDATE users SET role='owner',owner_intent=0,session_version=session_version+1 WHERE id=?")->execute([$applicant['id']]);
 }
 $pdo->prepare('UPDATE owner_requests SET status=?,court_id=?,review_note=?,reviewed_by=?,reviewed_at=NOW(),revision=revision+1 WHERE id=?')->execute([$status,$courtId,$reason,$actor['id'],$id]);
 wf_event($id,$actor['id'],$action,$reason);wf_audit($actor['id'],$applicant['id'],'owner_'.$action,$reason,$id,$courtId);
 $label=['approved'=>'đã được duyệt','needs_info'=>'cần bổ sung','rejected'=>'bị từ chối'][$status];$notice=wf_notice($applicant['id'],'Kết quả hồ sơ chủ sân #'.$id,'Hồ sơ #'.$id.' '.$label.'. '.$reason.($status==='approved'?' Vui lòng đăng nhập lại nếu quyền đã thay đổi, hoàn thiện lịch sân và chủ động mở nhận đặt.':''),$id);
 $pdo->commit();$email=wf_deliver($notice);booking_account_out(200,['email_sent'=>$email,'court_id'=>$courtId],'Đã xử lý hồ sơ. '.($email?'Đã gửi email thông báo.':'Kết quả đã lưu trên trang hồ sơ; email chưa gửi được.'));
}catch(Throwable $e){wf_error($e);}
