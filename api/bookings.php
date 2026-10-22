<?php
/** GET own history; POST keeps week 2 payment/confirm compatibility; supports optional voucher. */
require_once __DIR__.'/../includes/booking-service.php';
require_once __DIR__.'/../includes/voucher-service.php';

try {
 $user=booking_account_user();
 if($_SERVER['REQUEST_METHOD']==='GET')booking_account_out(200,booking_list($user));
 if($_SERVER['REQUEST_METHOD']!=='POST')booking_account_out(405,null,'Chỉ hỗ trợ GET/POST.');
 if(isset($_SERVER['HTTP_ORIGIN'])){
  $origin=parse_url($_SERVER['HTTP_ORIGIN']);$host=parse_url('http://'.($_SERVER['HTTP_HOST']??''));$scheme=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'https':'http';
  if(!$origin||strtolower($origin['host']??'')!==strtolower($host['host']??'')||($origin['scheme']??'')!==$scheme||($origin['port']??($scheme==='https'?443:80))!==($host['port']??($scheme==='https'?443:80)))booking_account_out(403,null,'Nguồn gửi không hợp lệ.');
 }
 $in=booking_account_input();$ids=$in['slot_ids']??(isset($in['slot_id'])?[$in['slot_id']]:[]);
 if(!is_array($ids)||!$ids||count($ids)>48)booking_account_out(422,null,'Chọn từ 1 đến 48 khung giờ.');
 $ids=array_values(array_unique(array_map('booking_account_id',$ids)));sort($ids);$courtId=isset($in['court_id'])?booking_account_id($in['court_id']):null;$method=$in['payment_method']??'VietQR';$notes=$in['notes']??'';
 $voucherCode=isset($in['voucher_code'])?trim((string)$in['voucher_code']):'';
 if(!is_string($method)||mb_strlen($method)>30||!is_string($notes)||mb_strlen($notes)>2000)booking_account_out(422,null,'Phương thức thanh toán hoặc ghi chú quá dài.');
 session_write_close();
 $data=booking_account_transaction(function()use($pdo,$user,$ids,$courtId,$method,$notes,$voucherCode){
  $accountLock=$pdo->prepare('SELECT is_verified,deleted_at,account_status FROM users WHERE id=? FOR UPDATE');$accountLock->execute([$user['id']]);$accountState=$accountLock->fetch();if(!$accountState||!$accountState['is_verified']||$accountState['deleted_at']!==null||$accountState['account_status']!=='active')throw new BookingError(401,'Tài khoản không còn hoạt động.');
  $s=$pdo->prepare("SELECT s.*,c.name AS court_name,c.price_per_hour,c.status AS court_status,(s.hold_expires_at>NOW()) AS hold_valid,(TIMESTAMP(s.slot_date,s.start_time)>NOW()) AS future_start FROM time_slots s JOIN courts c ON c.id=s.court_id WHERE s.id IN (".implode(',',array_fill(0,count($ids),'?')).") AND c.approval_status='approved' AND c.booking_open=1 AND EXISTS(SELECT 1 FROM users venue_owner WHERE venue_owner.id=c.owner_id AND venue_owner.account_status='active' AND venue_owner.deleted_at IS NULL AND venue_owner.is_verified=1) ORDER BY s.id FOR UPDATE");$s->execute($ids);$slots=$s->fetchAll();
  if(count($slots)!==count($ids))throw new BookingError(404,'Không tìm thấy đầy đủ khung giờ.');
  usort($slots,fn($a,$b)=>strcmp($a['start_time'],$b['start_time']));$first=$slots[0];$last=end($slots);$courtAmount=0;$previous=null;
  foreach($slots as $slot){
   if($courtId!==null&&(int)$slot['court_id']!==$courtId)throw new BookingError(422,'court_id không khớp khung giờ.');
   if($slot['court_id']!==$first['court_id']||$slot['slot_date']!==$first['slot_date']||$slot['sub_court_name']!==$first['sub_court_name'])throw new BookingError(422,'Khung giờ phải cùng sân, thảm và ngày.');
   if($previous!==null&&$previous!==$slot['start_time'])throw new BookingError(422,'Các khung giờ phải liên tiếp.');
   if($slot['court_status']!=='active'||!$slot['future_start'])throw new BookingError(409,'Sân ngừng hoạt động hoặc khung giờ đã bắt đầu.');
   if($slot['status']!=='holding')throw new BookingError(409,'Khung giờ chưa được giữ hoặc đã được đặt.');
   if((int)$slot['held_by']!==$user['id'])throw new BookingError(403,'Khung giờ thuộc phiên giữ của tài khoản khác.');
   if(!$slot['hold_valid'])throw new BookingError(410,'Phiên giữ chỗ đã hết hạn.');
   $h=(int)substr($slot['start_time'],0,2);$courtAmount+=(int)round($slot['price_per_hour']/2)+(($h>=17&&$h<=21)?7500:0);$previous=$slot['end_time'];
  }

  $voucher=null;
  if($voucherCode!==''){
   $voucher=voucher_validate_and_get($pdo,$voucherCode,(int)$user['id'],(int)$first['court_id'],$courtAmount,true);
  }
  $pricing=voucher_calculate_pricing($courtAmount,$voucher);
  $totalPrice=$pricing['total_amount'];
  $deposit=$pricing['deposit_amount'];

  if($voucher){
   $pdo->prepare('UPDATE vouchers SET used_count=used_count+1 WHERE id=?')->execute([(int)$voucher['id']]);
  }

  $pdo->prepare("INSERT INTO bookings(user_id,court_id,voucher_id,court_amount,discount_amount,discount_funded_by,platform_fee,owner_amount,total_price,deposit_amount,status,payment_method,notes) VALUES(?,?,?,?,?,?,?,?,?,?,'confirmed',?,?)")->execute([
   $user['id'],
   $first['court_id'],
   $pricing['voucher_id'],
   $pricing['court_amount'],
   $pricing['discount_amount'],
   $pricing['discount_funded_by'],
   $pricing['platform_fee'],
   $pricing['owner_amount'],
   $totalPrice,
   $deposit,
   trim($method),
   trim($notes)
  ]);
  $id=(int)$pdo->lastInsertId();

  if($voucher){
   $pdo->prepare('INSERT INTO voucher_usages(voucher_id,user_id,booking_id,discount_amount) VALUES(?,?,?,?)')->execute([
    (int)$voucher['id'],
    $user['id'],
    $id,
    $pricing['discount_amount']
   ]);
  }

  $code='GDM-'.str_replace('-','',$first['slot_date']).'-'.str_pad((string)$id,4,'0',STR_PAD_LEFT);$pdo->prepare('UPDATE bookings SET booking_code=? WHERE id=?')->execute([$code,$id]);
  $link=$pdo->prepare('INSERT INTO booking_slots(booking_id,slot_id) VALUES(?,?)');$update=$pdo->prepare("UPDATE time_slots SET status='booked',held_by=NULL,hold_expires_at=NULL WHERE id=?");foreach($ids as $sid){$link->execute([$id,$sid]);$update->execute([$sid]);}
  return [
   'booking_id'=>$id,
   'booking_code'=>$code,
   'court_id'=>(int)$first['court_id'],
   'court_name'=>$first['court_name'],
   'sub_court_name'=>$first['sub_court_name'],
   'booking_date'=>$first['slot_date'],
   'start_time'=>substr($first['start_time'],0,5),
   'end_time'=>substr($last['end_time'],0,5),
   'time_range'=>substr($first['start_time'],0,5).' - '.substr($last['end_time'],0,5),
   'court_amount'=>$pricing['court_amount'],
   'discount_amount'=>$pricing['discount_amount'],
   'voucher_code'=>$pricing['voucher_code'],
   'platform_fee'=>$pricing['platform_fee'],
   'total_price'=>$totalPrice,
   'deposit_amount'=>$deposit,
   'deposit_paid'=>$deposit,
   'remain_amount'=>$totalPrice-$deposit,
   'owner_amount'=>$pricing['owner_amount'],
   'payment_method'=>$method,
   'booking_status'=>'confirmed',
   'created_at'=>date('Y-m-d H:i:s'),
   'payment_is_demo'=>true
  ];
 });
 booking_account_out(201,$data,'Đã lưu đơn và cọc mô phỏng. Chưa xác thực giao dịch qua cổng thanh toán.');
}catch(VoucherError $e){
 http_response_code($e->httpCode);
 echo json_encode([
  'status'=>'error',
  'ok'=>false,
  'code'=>$e->errorCode,
  'message'=>$e->getMessage(),
  'meta'=>(object)$e->meta
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 exit;
}catch(Throwable $e){
 booking_account_error($e);
}
