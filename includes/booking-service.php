<?php
require_once __DIR__.'/booking-account-api.php';
function booking_code(array $b,?string $date=null):string{return $b['booking_code']?:'GDM-'.str_replace('-','',substr($date?:$b['created_at'],0,10)).'-'.str_pad((string)$b['id'],4,'0',STR_PAD_LEFT);}
function booking_list(array $user,bool $admin=false):array{
 global $pdo;$where=[];$args=[];
 if(!$admin){$where[]='b.user_id=?';$args[]=$user['id'];}elseif($user['role']==='owner'){$where[]='c.owner_id=?';$args[]=$user['id'];}
 $status=$_GET['status']??'';if(!is_string($status)||!in_array($status,['','pending','confirmed','completed','cancelled'],true))throw new BookingError(422,'Bộ lọc trạng thái không hợp lệ.');if($status!==''){$where[]='b.status=?';$args[]=$status;}
 $query=$_GET['q']??'';if(!is_string($query)||mb_strlen($query)>150)throw new BookingError(422,'Từ khóa tìm kiếm không hợp lệ.');$q=trim($query);
 if($q!==''){$where[]='(b.booking_code LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR c.name LIKE ?)';foreach(range(1,4)as $_)$args[]='%'.$q.'%';}
 $sql='SELECT b.*,c.name AS court_name,c.address AS court_address,c.owner_id,u.name AS user_name,u.phone AS user_phone,u.email AS user_email FROM bookings b JOIN courts c ON c.id=b.court_id JOIN users u ON u.id=b.user_id';
 if($where)$sql.=' WHERE '.implode(' AND ',$where);$s=$pdo->prepare($sql.' ORDER BY b.created_at DESC,b.id DESC');$s->execute($args);$rows=$s->fetchAll();if(!$rows)return [];
 $ids=array_column($rows,'id');$in=implode(',',array_fill(0,count($ids),'?'));
 $s=$pdo->prepare("SELECT bs.booking_id,s.id,s.court_id,s.sub_court_name,s.slot_date,s.start_time,s.end_time,(TIMESTAMP(s.slot_date,s.start_time)>NOW()) AS future_start,(TIMESTAMP(s.slot_date,s.end_time)<=NOW()) AS ended FROM booking_slots bs JOIN time_slots s ON s.id=bs.slot_id WHERE bs.booking_id IN ($in) ORDER BY s.slot_date,s.start_time,s.id");$s->execute($ids);$groups=[];foreach($s->fetchAll()as $slot)$groups[$slot['booking_id']][]=$slot;
 foreach($rows as &$b){$slots=$groups[$b['id']]??[];$first=$slots[0]??null;$last=$slots?end($slots):null;
 $b['id']=(int)$b['id'];$b['court_id']=(int)$b['court_id'];$b['user_id']=(int)$b['user_id'];$b['booking_code']=booking_code($b,$first['slot_date']??null);$b['booking_status']=$b['status'];$b['total_amount']=(int)$b['total_price'];$b['deposit_amount']=(int)$b['deposit_amount'];
 $b['booking_date']=$first['slot_date']??null;$b['start_time']=$first?substr($first['start_time'],0,5):'';$b['end_time']=$last?substr($last['end_time'],0,5):'';$b['time_range']=$b['start_time'].' - '.$b['end_time'];$b['sub_court_name']=implode(', ',array_unique(array_column($slots,'sub_court_name')));
 $b['can_cancel']=in_array($b['status'],['pending','confirmed'],true)&&count($slots)>0&&!array_filter($slots,fn($s)=>!(bool)$s['future_start']);$b['can_complete']=$admin&&$b['status']==='confirmed'&&count($slots)>0&&!array_filter($slots,fn($s)=>!(bool)$s['ended']);
 $b['slots']=array_map(fn($s)=>['id'=>(int)$s['id'],'court_id'=>(int)$s['court_id'],'sub_court_name'=>$s['sub_court_name'],'slot_date'=>$s['slot_date'],'start_time'=>substr($s['start_time'],0,5),'end_time'=>substr($s['end_time'],0,5)],$slots);
 }unset($b);return $rows;
}
function booking_transition(int $id,string $target,array $user,bool $admin=false):array{
 global $pdo;return booking_account_transaction(function()use($pdo,$id,$target,$user,$admin){
 $sql='SELECT b.*,c.owner_id FROM bookings b JOIN courts c ON c.id=b.court_id WHERE b.id=?';$args=[$id];if(!$admin){$sql.=' AND b.user_id=?';$args[]=$user['id'];}elseif($user['role']==='owner'){$sql.=' AND c.owner_id=?';$args[]=$user['id'];}
 $s=$pdo->prepare($sql.' FOR UPDATE');$s->execute($args);$b=$s->fetch();if(!$b)throw new BookingError(404,'Không tìm thấy đơn thuộc phạm vi quản lý.');if($b['status']===$target)return ['booking_id'=>$id,'booking_status'=>$target];
 if($target==='completed'&&(!$admin||$b['status']!=='confirmed'))throw new BookingError(409,'Chỉ đơn đã xác nhận mới được hoàn thành.');if($target==='cancelled'&&!in_array($b['status'],['confirmed','pending'],true))throw new BookingError(409,'Đơn này không thể hủy.');
 $s=$pdo->prepare('SELECT s.*,(TIMESTAMP(s.slot_date,s.start_time)>NOW()) AS future_start,(TIMESTAMP(s.slot_date,s.end_time)<=NOW()) AS ended FROM booking_slots bs JOIN time_slots s ON s.id=bs.slot_id WHERE bs.booking_id=? ORDER BY s.id FOR UPDATE');$s->execute([$id]);$slots=$s->fetchAll();if(!$slots)throw new BookingError(409,'Đơn không có khung giờ hợp lệ.');
 foreach($slots as $slot){if($target==='cancelled'&&!$slot['future_start'])throw new BookingError(409,'Chỉ được hủy trước giờ bắt đầu.');if($target==='completed'&&!$slot['ended'])throw new BookingError(409,'Chỉ hoàn thành sau khi mọi khung giờ kết thúc.');}
 $pdo->prepare('UPDATE bookings SET status=? WHERE id=?')->execute([$target,$id]);
 if($target==='cancelled'){$q=$pdo->prepare("UPDATE time_slots s SET s.status='available',s.held_by=NULL,s.hold_expires_at=NULL WHERE s.id=? AND s.status='booked' AND NOT EXISTS(SELECT 1 FROM booking_slots bs JOIN bookings b ON b.id=bs.booking_id WHERE bs.slot_id=s.id AND b.status IN ('pending','confirmed','completed'))");foreach($slots as $slot)$q->execute([$slot['id']]);}
 return ['booking_id'=>$id,'booking_status'=>$target];});
}
