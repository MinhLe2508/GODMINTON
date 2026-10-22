<?php
require_once __DIR__.'/auth-security.php';
require_once __DIR__.'/booking-account-api.php';
function wf_fail(int $code,string $message): never {throw new BookingError($code,$message);}
function wf_user(bool $admin=false): array {
 global $pdo;$u=god_auth_user($pdo);
 if(!$u)wf_fail(401,'Vui lòng đăng nhập bằng tài khoản đang hoạt động.');
 if($admin && $u['role']!=='admin')wf_fail(403,'Chỉ quản trị viên được sử dụng chức năng này.');
 return $u;
}
function wf_text(array $data,string $key,int $max=1000):string {
 $value=$data[$key]??'';if(!is_string($value)||mb_strlen($value)>$max)wf_fail(422,'Thông tin '.$key.' không hợp lệ.');return trim($value);
}
function wf_post():array {
 if($_SERVER['REQUEST_METHOD']!=='POST')wf_fail(405,'Chỉ hỗ trợ POST.');
 if((int)($_SERVER['CONTENT_LENGTH']??0)>32768)wf_fail(413,'Dữ liệu quá lớn.');
 booking_account_csrf();return booking_account_input();
}
function wf_active(array|false $u):bool {return $u && $u['deleted_at']===null && $u['account_status']==='active' && (bool)$u['is_verified'];}
function wf_lock_user(int $id):array {
 global $pdo;$s=$pdo->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');$s->execute([$id]);$u=$s->fetch();if(!$u)wf_fail(404,'Không tìm thấy tài khoản.');return $u;
}
function wf_lock_actor(array $actor):array {
 $u=wf_lock_user((int)$actor['id']);
 if(!wf_active($u) || (int)$u['session_version']!==(int)($_SESSION['auth_version']??-1))wf_fail(401,'Phiên đăng nhập đã thay đổi. Vui lòng đăng nhập lại.');
 return $u;
}
function wf_admin_password(array $actor,array $in):void {
 global $pdo;
 if(!god_auth_limit($pdo,'admin-reauth',(string)$actor['id'],10,900))wf_fail(429,'Quá nhiều lần xác thực quản trị. Vui lòng thử lại sau 15 phút.');
 $password=$in['admin_password']??null;
 if(!is_string($password) || !password_verify($password,wf_password_hash((int)$actor['id'])))wf_fail(403,'Mật khẩu quản trị không đúng.');
 $pdo->prepare('DELETE FROM auth_rate_limits WHERE key_hash=?')->execute([hash('sha256','admin-reauth|'.$actor['id'])]);
}
function wf_password_hash(int $id):string {global $pdo;$s=$pdo->prepare('SELECT password_hash FROM users WHERE id=?');$s->execute([$id]);return (string)$s->fetchColumn();}
function wf_lock_admins(array $actor):array {
 global $pdo;
 $admins=$pdo->query("SELECT id FROM users WHERE role='admin' AND account_status='active' AND deleted_at IS NULL AND is_verified=1 ORDER BY id FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
 $u=wf_lock_actor($actor);if($u['role']!=='admin')wf_fail(403,'Quyền quản trị đã thay đổi.');return $admins;
}
function wf_audit(int $actor,?int $target,string $action,string $reason,?int $request=null,?int $court=null,array $details=[]):void {
 global $pdo;$pdo->prepare('INSERT INTO account_audit(actor_id,target_user_id,action,reason,request_id,court_id,details) VALUES(?,?,?,?,?,?,?)')->execute([$actor,$target,$action,$reason,$request,$court,json_encode($details,JSON_UNESCAPED_UNICODE)]);
}
function wf_event(int $request,int $actor,string $action,string $note=''):void {global $pdo;$pdo->prepare('INSERT INTO owner_request_events(request_id,actor_id,action,note) VALUES(?,?,?,?)')->execute([$request,$actor,$action,$note]);}
function wf_notice(int $user,string $title,string $message,?int $request=null):int {
 global $pdo;$pdo->prepare('INSERT INTO account_notifications(user_id,title,message,request_id) VALUES(?,?,?,?)')->execute([$user,$title,$message,$request]);return (int)$pdo->lastInsertId();
}
function wf_deliver(int $notice):bool {
 global $pdo;
 try {
  $s=$pdo->prepare('SELECT n.*,u.name,u.email,u.is_verified,u.deleted_at FROM account_notifications n JOIN users u ON u.id=n.user_id WHERE n.id=?');$s->execute([$notice]);$n=$s->fetch();
  if(!$n || $n['email_sent_at'] || !$n['is_verified'] || $n['deleted_at']!==null)return false;
  $mail=god_auth_mailer();$mail->addAddress($n['email']);$mail->Subject=$n['title'];$mail->isHTML(true);
  $e=fn($v)=>htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');$link=rtrim(getenv('APP_URL'),'/').($n['request_id']?'/owner-application.php':'/profile.php');
  $logo=__DIR__.'/../assets/img/logo.jpg';$image='';if(is_file($logo)){$mail->addEmbeddedImage($logo,'godminton-logo','godminton-logo.jpg');$image='<img src="cid:godminton-logo" width="150" alt="GODMINTON" style="display:block;margin:0 auto 24px;">';}
  $mail->Body='<div style="font-family:Segoe UI,Arial,sans-serif;background:#effaf7;padding:24px;"><div style="max-width:560px;margin:auto;background:white;border:1px solid #d2e9e2;border-radius:16px;padding:28px;">'.$image.'<h2 style="color:#0f766e;">'.$e($n['title']).'</h2><p>Xin chào '.$e($n['name']).',</p><p style="line-height:1.8;">'.nl2br($e($n['message'])).'</p><a href="'.$e($link).'" style="display:inline-block;background:#0d9488;color:white;text-decoration:none;padding:12px 20px;border-radius:8px;">Xem thông tin trên GODMINTON</a></div></div>';
  $mail->AltBody=$n['title']."\n\n".$n['message']."\n\n".$link;$mail->send();
  $pdo->prepare('UPDATE account_notifications SET email_sent_at=NOW() WHERE id=?')->execute([$notice]);return true;
 }catch(Throwable $e){error_log('[GODMINTON workflow mail] '.get_class($e));return false;}
}
function wf_payload(array $data,bool $required):array {
 global $pdo;
 $p=[];
 foreach(['name'=>150,'address'=>255,'description'=>5000,'image_url'=>2000,'opening_time'=>8,'closing_time'=>8] as $key=>$max)$p[$key]=wf_text($data,$key,$max);
 foreach(['ward_id','total_sub_courts'] as $key){$v=$data[$key]??0;if(!is_scalar($v)||filter_var($v,FILTER_VALIDATE_INT)===false)wf_fail(422,'Thông tin '.$key.' không hợp lệ.');$p[$key]=(int)$v;}
 foreach(['latitude','longitude','price_per_hour'] as $key){$v=$data[$key]??0;if(!is_scalar($v)||!is_numeric($v)||!is_finite((float)$v))wf_fail(422,'Thông tin '.$key.' không hợp lệ.');$p[$key]=(float)$v;}
 $codes=$data['amenities']??[];if(!is_array($codes)||count($codes)>30)wf_fail(422,'Danh sách tiện ích không hợp lệ.');
 foreach($codes as $code)if(!is_string($code)||strlen($code)>40)wf_fail(422,'Mã tiện ích không hợp lệ.');
 $p['amenities']=array_values(array_unique($codes));
 if($p['image_url']!=='' && !wf_url($p['image_url']) && !(preg_match('/^assets\/img\/[a-z0-9_\/.-]+\.(jpg|jpeg|png|webp|svg)$/i',$p['image_url']) && !str_contains($p['image_url'],'..')))wf_fail(422,'Ảnh sân phải là URL HTTPS hợp lệ.');
 if($required){
  if(mb_strlen($p['name'])<3 || mb_strlen($p['address'])<8 || $p['price_per_hour']<=0 || $p['price_per_hour']>10000000 || $p['total_sub_courts']<1 || $p['total_sub_courts']>100)wf_fail(422,'Kiểm tra tên sân, địa chỉ, giá và số sân con.');
  if($p['latitude']<10.35 || $p['latitude']>11.2 || $p['longitude']<106.3 || $p['longitude']>107.05)wf_fail(422,'Vị trí sân phải nằm trong khu vực hoạt động TP.HCM.');
  foreach(['opening_time','closing_time'] as $key){if(!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d(?::00)?$/',$p[$key]))wf_fail(422,'Giờ mở/đóng cửa không hợp lệ.');$p[$key]=substr($p[$key],0,5);}
  if($p['opening_time']>=$p['closing_time'])wf_fail(422,'Giờ đóng cửa phải sau giờ mở cửa.');
  $s=$pdo->prepare('SELECT id FROM wards WHERE id=?');$s->execute([$p['ward_id']]);if(!$s->fetchColumn())wf_fail(422,'Chọn phường hợp lệ.');
  $valid=$pdo->query('SELECT code FROM amenities')->fetchAll(PDO::FETCH_COLUMN);if(array_diff($p['amenities'],$valid))wf_fail(422,'Có tiện ích không tồn tại.');
  if(!$p['image_url'])wf_fail(422,'Vui lòng cung cấp ảnh thực tế của sân bằng liên kết HTTPS.');
 }
 return $p;
}
function wf_url(string $url):bool {$p=parse_url($url);return filter_var($url,FILTER_VALIDATE_URL) && ($p['scheme']??'')==='https' && empty($p['user']) && empty($p['pass']);}
function wf_duplicates(array $p,?int $exclude=null):array {
 global $pdo;if(empty($p['name']) || empty($p['address']))return [];
 $s=$pdo->prepare('SELECT id,name,address,latitude,longitude FROM courts WHERE id<>? AND (name=? OR address=? OR (ABS(latitude-?)<0.0009 AND ABS(longitude-?)<0.0009)) ORDER BY id LIMIT 15');
 $s->execute([$exclude??0,$p['name'],$p['address'],$p['latitude']??0,$p['longitude']??0]);return $s->fetchAll();
}
function wf_request(int $id,bool $lock=false):array {
 global $pdo;$s=$pdo->prepare('SELECT * FROM owner_requests WHERE id=?'.($lock?' FOR UPDATE':''));$s->execute([$id]);$r=$s->fetch();if(!$r)wf_fail(404,'Không tìm thấy hồ sơ.');$r['payload']=json_decode($r['payload'],true)?:[];return $r;
}
function wf_request_detail(array $r):array {
 global $pdo;
 $s=$pdo->prepare('SELECT e.id,e.action,e.note,e.created_at,u.name AS actor_name FROM owner_request_events e LEFT JOIN users u ON u.id=e.actor_id WHERE request_id=? ORDER BY e.id');$s->execute([$r['id']]);$r['events']=$s->fetchAll();
 $r['duplicates']=$r['kind']==='new'?wf_duplicates($r['payload']):[];
 return $r;
}
function wf_court_ready(PDO $pdo,int $id):void {
 $s=$pdo->prepare("SELECT c.*,u.account_status,u.deleted_at,u.is_verified,u.role FROM courts c JOIN users u ON u.id=c.owner_id WHERE c.id=? FOR UPDATE");$s->execute([$id]);$c=$s->fetch();
 if(!$c || $c['approval_status']!=='approved' || $c['account_status']!=='active' || $c['deleted_at']!==null || !$c['is_verified'] || !in_array($c['role'],['owner','admin'],true))wf_fail(409,'Sân hoặc tài khoản chủ sân chưa đủ điều kiện mở nhận đặt.');
 if($c['price_per_hour']<=0 || $c['total_sub_courts']<1 || $c['opening_time']>=$c['closing_time'])wf_fail(409,'Hoàn thiện giá, số sân con và giờ hoạt động trước.');
 $s=$pdo->prepare("SELECT COUNT(*) FROM time_slots WHERE court_id=? AND status='available' AND TIMESTAMP(slot_date,start_time)>NOW()");$s->execute([$id]);if(!(int)$s->fetchColumn())wf_fail(409,'Hãy tạo ít nhất một khung giờ trống trong tương lai trước khi mở nhận đặt.');
}
function wf_error(Throwable $e):never {
 global $pdo;if($pdo->inTransaction())$pdo->rollBack();
 if($e instanceof BookingError)booking_account_out($e->httpCode,null,$e->getMessage());
 error_log('[GODMINTON owner workflow] '.get_class($e));booking_account_out(500,null,'Không thể xử lý. Kiểm tra đã import migration_owner_account_management.sql và xem log máy chủ.');
}
