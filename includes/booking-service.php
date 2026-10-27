<?php
require_once __DIR__.'/booking-account-api.php';
require_once __DIR__.'/court-resolver.php';
function booking_code(array $b,?string $date=null):string{return $b['booking_code']?:'GDM-'.str_replace('-','',substr($date?:$b['created_at'],0,10)).'-'.str_pad((string)$b['id'],4,'0',STR_PAD_LEFT);}
function booking_list(array $user,bool $admin=false):array{
 global $pdo;$where=[];$args=[];
 if(!$admin){$where[]='b.user_id=?';$args[]=$user['id'];}elseif($user['role']==='owner'){$where[]='c.owner_id=?';$args[]=$user['id'];}
 $status=$_GET['status']??'';if(!is_string($status)||!in_array($status,['','pending','confirmed','checked_in','completed','no_show','cancelled'],true))throw new BookingError(422,'Bộ lọc trạng thái không hợp lệ.');if($status!==''){$where[]='b.status=?';$args[]=$status;}
 $query=$_GET['q']??'';if(!is_string($query)||mb_strlen($query)>150)throw new BookingError(422,'Từ khóa tìm kiếm không hợp lệ.');$q=trim($query);
 if($q!==''){$where[]='(b.booking_code LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR c.name LIKE ?)';foreach(range(1,4)as $_)$args[]='%'.$q.'%';}
 $sql='SELECT b.*,c.name AS court_name,c.address AS court_address,c.status AS court_status,c.owner_id,u.name AS user_name,u.phone AS user_phone,u.email AS user_email,v.code AS voucher_code,um.tier AS membership_tier,um.discount_percent AS membership_discount_percent FROM bookings b JOIN courts c ON c.id=b.court_id JOIN users u ON u.id=b.user_id LEFT JOIN vouchers v ON v.id=b.voucher_id LEFT JOIN user_memberships um ON um.id=b.user_membership_id';
 if($where)$sql.=' WHERE '.implode(' AND ',$where);$s=$pdo->prepare($sql.' ORDER BY b.created_at DESC,b.id DESC');$s->execute($args);$rows=$s->fetchAll();if(!$rows)return [];
 $ids=array_column($rows,'id');$in=implode(',',array_fill(0,count($ids),'?'));
 $s=$pdo->prepare("SELECT bs.booking_id,s.id,s.court_id,s.sub_court_name,s.slot_date,s.start_time,s.end_time,(TIMESTAMP(s.slot_date,s.start_time)>NOW()) AS future_start,(TIMESTAMP(s.slot_date,s.end_time)<=NOW()) AS ended FROM booking_slots bs JOIN time_slots s ON s.id=bs.slot_id WHERE bs.booking_id IN ($in) ORDER BY s.slot_date,s.start_time,s.id");$s->execute($ids);$groups=[];foreach($s->fetchAll()as $slot)$groups[$slot['booking_id']][]=$slot;
 foreach($rows as &$b){$slots=$groups[$b['id']]??[];$first=$slots[0]??null;$last=$slots?end($slots):null;
 $b['rebook_court_id']=court_rebooking_target($pdo,(int)$b['court_id'],$b['court_status']);$b['can_rebook']=$b['rebook_court_id']!==null;
 $b['id']=(int)$b['id'];$b['court_id']=(int)$b['court_id'];$b['user_id']=(int)$b['user_id'];$b['booking_code']=booking_code($b,$first['slot_date']??null);$b['booking_status']=$b['status'];$b['total_amount']=(int)$b['total_price'];$b['deposit_amount']=(int)$b['deposit_amount'];
 $b['remaining_amount']=max(0, $b['total_amount'] - $b['deposit_amount']);
 $b['remaining_paid']=(int)($b['remaining_paid'] ?? 0);
 $b['checked_in_at']=$b['checked_in_at'] ?? null;
 $b['completed_at']=$b['completed_at'] ?? null;
 $b['payment_method']=$b['payment_method'] ?? 'VietQR';
 $b['notes']=$b['notes'] ?? '';
 $b['voucher_code']=$b['voucher_code']??null;
 $b['court_amount']=(int)($b['court_amount']??$b['total_price']);
 $b['discount_amount']=(int)($b['discount_amount']??0);
 $b['membership_discount_amount']=(int)($b['membership_discount_amount']??0);
 $b['membership_tier']=$b['membership_tier']??null;
 $b['membership_discount_percent']=isset($b['membership_discount_percent'])?(int)$b['membership_discount_percent']:null;
 $b['voucher_discount_amount']=max(0, $b['discount_amount'] - $b['membership_discount_amount']);
 if (!$b['voucher_id'] && !$b['voucher_code']) {
     $b['voucher_discount_amount']=0;
 }
 $b['discount_funded_by']=$b['discount_funded_by']??null;
 $b['platform_fee']=(int)($b['platform_fee']??0);
 $b['owner_amount']=(int)($b['owner_amount']??$b['total_price']);
 $b['booking_date']=$first['slot_date']??null;$b['start_time']=$first?substr($first['start_time'],0,5):'';$b['end_time']=$last?substr($last['end_time'],0,5):'';$b['time_range']=$b['start_time'].' - '.$b['end_time'];$b['sub_court_name']=implode(', ',array_unique(array_column($slots,'sub_court_name')));
 
 // Quyền thao tác theo luồng vận hành chuyên nghiệp (Option 2)
 $b['is_deposit_refunded'] = (int)($b['is_deposit_refunded'] ?? 0);
 $b['refund_note'] = $b['refund_note'] ?? null;
 $earliestSlotTs = null;
 foreach($slots as $s) {
     $ts = strtotime($s['slot_date'] . ' ' . $s['start_time']);
     if ($earliestSlotTs === null || $ts < $earliestSlotTs) {
         $earliestSlotTs = $ts;
     }
 }
 $diffMin = $earliestSlotTs ? ($earliestSlotTs - time()) / 60 : 0;
 $hasMemberTier = !empty($b['membership_tier']) || !empty($b['user_membership_id']);
 $cancelThreshold = $hasMemberTier ? 30 : 120;
 $b['is_member'] = $hasMemberTier;
 $b['cancel_threshold_minutes'] = $cancelThreshold;
 $b['can_checkin']=$admin && $b['status']==='confirmed';
 $b['can_complete']=$admin && ($b['status']==='checked_in' || $b['status']==='confirmed');
 $b['can_noshow']=$admin && $b['status']==='confirmed';
 $b['can_cancel']=($admin && in_array($b['status'],['pending','confirmed'],true)) || (!$admin && in_array($b['status'],['pending','confirmed'],true)&&count($slots)>0&&$diffMin>=$cancelThreshold);

 $b['slots']=array_map(fn($s)=>['id'=>(int)$s['id'],'court_id'=>(int)$s['court_id'],'sub_court_name'=>$s['sub_court_name'],'slot_date'=>$s['slot_date'],'start_time'=>substr($s['start_time'],0,5),'end_time'=>substr($s['end_time'],0,5)],$slots);
 }unset($b);return $rows;
}
function booking_transition(int $id,string $target,array $user,bool $admin=false):array{
 global $pdo;return booking_account_transaction(function()use($pdo,$id,$target,$user,$admin){
 $sql='SELECT b.*,c.owner_id FROM bookings b JOIN courts c ON c.id=b.court_id WHERE b.id=?';$args=[$id];if(!$admin){$sql.=' AND b.user_id=?';$args[]=$user['id'];}elseif($user['role']==='owner'){$sql.=' AND c.owner_id=?';$args[]=$user['id'];}
 $s=$pdo->prepare($sql.' FOR UPDATE');$s->execute($args);$b=$s->fetch();if(!$b)throw new BookingError(404,'Không tìm thấy đơn thuộc phạm vi quản lý.');if($b['status']===$target)return ['booking_id'=>$id,'booking_status'=>$target];

 // Kiểm tra tính hợp lệ của luồng chuyển trạng thái
 if($target==='checked_in'){
  if(!$admin||$b['status']!=='confirmed')throw new BookingError(409,'Chỉ đơn đã xác nhận (đã cọc) mới có thể Check-in nhận sân.');
  $pdo->prepare('UPDATE bookings SET status=?, checked_in_at=NOW() WHERE id=?')->execute(['checked_in',$id]);
 }
 elseif($target==='completed'){
  if(!$admin||!in_array($b['status'],['checked_in','confirmed'],true))throw new BookingError(409,'Đơn phải đang nhận sân hoặc đã xác nhận mới có thể hoàn thành.');
  $remain = max(0, (int)$b['total_price'] - (int)$b['deposit_amount']);
  $pdo->prepare('UPDATE bookings SET status=?, completed_at=NOW(), remaining_paid=? WHERE id=?')->execute(['completed',$remain,$id]);
 }
 elseif($target==='no_show'){
  if(!$admin||$b['status']!=='confirmed')throw new BookingError(409,'Chỉ đơn đã xác nhận mới có thể ghi nhận bùng sân.');

  // Kiểm tra khách có phải là Hội viên không
  $memStmt = $pdo->prepare("
      SELECT um.* 
      FROM user_memberships um 
      WHERE um.user_id = ? AND um.status = 'active' AND um.start_date <= NOW() AND um.end_date >= NOW()
      ORDER BY um.id DESC LIMIT 1
  ");
  $memStmt->execute([(int)$b['user_id']]);
  $member = $memStmt->fetch();
  if (!$member && !empty($b['user_membership_id'])) {
      $memStmt2 = $pdo->prepare("SELECT * FROM user_memberships WHERE id = ?");
      $memStmt2->execute([(int)$b['user_membership_id']]);
      $member = $memStmt2->fetch();
  }

  $isRefunded = 0;
  $refundNote = 'Khách bùng sân: Giữ 100% tiền cọc bồi thường cho chủ sân.';
  $returnMessage = 'Đã ghi nhận khách bùng sân (giữ tiền cọc) và mở lại khung giờ cho khách vãng lai.';

  if ($member) {
      $tier = strtolower($member['tier'] ?? 'silver');
      // Quota cứu cọc theo hạng thẻ (Option 2): Silver: 1, Gold: 2, Platinum: 3
      $quota = $tier === 'platinum' ? 3 : ($tier === 'gold' ? 2 : 1);
      $tierName = $tier === 'platinum' ? 'Bạch kim' : ($tier === 'gold' ? 'Vàng' : 'Bạc');

      // Đếm số lần bùng sân đã được cứu cọc trong tháng hiện tại
      $countStmt = $pdo->prepare("
          SELECT COUNT(*) 
          FROM bookings 
          WHERE user_id = ? 
            AND status = 'no_show' 
            AND is_deposit_refunded = 1 
            AND MONTH(created_at) = MONTH(NOW()) 
            AND YEAR(created_at) = YEAR(NOW())
      ");
      $countStmt->execute([(int)$b['user_id']]);
      $usedGraceCount = (int)$countStmt->fetchColumn();

      if ($usedGraceCount < $quota) {
          // KÍCH HOẠT OPTION 2: GRACE PASS CỨU CỌC
          $isRefunded = 1;
          $curTurn = $usedGraceCount + 1;
          $refundNote = "Đặc quyền Hội viên {$tierName}: Áp dụng Grace Pass cứu cọc lượt {$curTurn}/{$quota} trong tháng (Hoàn cọc 100%).";
          $returnMessage = "Đã ghi nhận bùng sân. Khách hàng là Hội viên ({$tierName}) và được áp dụng lượt cứu cọc ({$curTurn}/{$quota} trong tháng) — tiền cọc được hoàn lại cho hội viên.";
      } else {
          // Đã hết quota trong tháng
          $isRefunded = 0;
          $refundNote = "Hội viên {$tierName} đã sử dụng hết quota cứu cọc ({$quota}/{$quota} lượt trong tháng). Giữ 100% tiền cọc.";
          $returnMessage = "Đã ghi nhận bùng sân. Hội viên đã dùng hết hạn mức cứu cọc tháng này ({$quota}/{$quota} lần) — giữ 100% tiền cọc cho chủ sân.";
      }
  }

  $pdo->prepare('UPDATE bookings SET status=?, is_deposit_refunded=?, refund_note=? WHERE id=?')
      ->execute(['no_show', $isRefunded, $refundNote, $id]);

  // Khách bùng sân: Tự động giải phóng khung giờ cho khách vãng lai
  $s=$pdo->prepare('SELECT bs.slot_id FROM booking_slots bs WHERE bs.booking_id=? FOR UPDATE');$s->execute([$id]);
  $slotIds=$s->fetchAll(PDO::FETCH_COLUMN,0);
  if($slotIds){
   $q=$pdo->prepare("UPDATE time_slots s SET s.status='available',s.held_by=NULL,s.hold_expires_at=NULL WHERE s.id=? AND s.status='booked' AND NOT EXISTS(SELECT 1 FROM booking_slots bs JOIN bookings b ON b.id=bs.booking_id WHERE bs.slot_id=s.id AND b.status IN ('pending','confirmed','checked_in','completed'))");
   foreach($slotIds as $sid)$q->execute([$sid]);
  }
 }
 elseif($target==='cancelled'){
  if(!in_array($b['status'],['confirmed','pending','checked_in'],true))throw new BookingError(409,'Đơn này không thể hủy.');
  $s=$pdo->prepare('SELECT s.*,(TIMESTAMP(s.slot_date,s.start_time)>NOW()) AS future_start FROM booking_slots bs JOIN time_slots s ON s.id=bs.slot_id WHERE bs.booking_id=? ORDER BY s.id FOR UPDATE');
  $s->execute([$id]);$slots=$s->fetchAll();

  // Tìm thời điểm bắt đầu sớm nhất
  $earliestTs = null;
  foreach($slots as $slot) {
      $ts = strtotime($slot['slot_date'] . ' ' . $slot['start_time']);
      if ($earliestTs === null || $ts < $earliestTs) {
          $earliestTs = $ts;
      }
  }

  // Kiểm tra Hội viên
  $memStmt = $pdo->prepare("
      SELECT um.* 
      FROM user_memberships um 
      WHERE um.user_id = ? AND um.status = 'active' AND um.start_date <= NOW() AND um.end_date >= NOW()
      ORDER BY um.id DESC LIMIT 1
  ");
  $memStmt->execute([(int)$b['user_id']]);
  $member = $memStmt->fetch();
  if (!$member && !empty($b['user_membership_id'])) {
      $memStmt2 = $pdo->prepare("SELECT * FROM user_memberships WHERE id = ?");
      $memStmt2->execute([(int)$b['user_membership_id']]);
      $member = $memStmt2->fetch();
  }

  $isMember = (bool)$member;
  $now = time();
  $diffMinutes = $earliestTs ? ($earliestTs - $now) / 60 : 0;

  $isRefunded = 0;
  $refundNote = null;
  $returnMessage = 'Đã hủy đơn đặt sân và giải phóng khung giờ.';

  if (!$admin) {
      // KÍCH HOẠT OPTION 4: Hội viên được hủy trước 30 phút và hoàn 100% cọc
      if ($isMember) {
          if ($diffMinutes < 30) {
              throw new BookingError(409, 'Đặc quyền hủy khẩn cấp của Hội viên chỉ áp dụng trước giờ bắt đầu ít nhất 30 phút. Trận đấu sắp diễn ra, vui lòng liên hệ hotline chủ sân!');
          }
          $isRefunded = 1;
          $tier = strtoupper($member['tier'] ?? 'SILVER');
          $refundNote = "Đặc quyền Hội viên {$tier}: Hủy khẩn cấp 1 chạm (>30p trước giờ đấu) - Hoàn cọc 100%.";
          $returnMessage = "Đã hủy đơn theo đặc quyền Hội viên ({$tier}) trước 30 phút. Tiền cọc được bảo đảm hoàn 100% và khung giờ đã được mở lại!";
      } else {
          // Khách thường: Phải hủy trước ít nhất 120 phút (2 giờ)
          if ($diffMinutes < 120) {
              throw new BookingError(409, 'Khách thường chỉ được hủy trước giờ bắt đầu ít nhất 2 tiếng (120 phút). Vui lòng liên hệ chủ sân để được hỗ trợ.');
          }
          $isRefunded = 0;
          $refundNote = 'Khách thường hủy đơn: Tiền cọc được xử lý theo quy định của sân.';
          $returnMessage = 'Đã hủy đơn đặt sân và mở lại khung giờ. Tiền cọc sẽ được xử lý theo quy định của sân.';
      }
  } else {
      // Admin / Chủ sân hủy
      $isRefunded = 1;
      $refundNote = 'Chủ sân / Quản trị viên hủy đơn: Hoàn tiền cọc.';
      $returnMessage = 'Chủ sân / Quản trị viên đã hủy đơn đặt sân và giải phóng khung giờ.';
  }

  $pdo->prepare('UPDATE bookings SET status=?, is_deposit_refunded=?, refund_note=? WHERE id=?')
      ->execute(['cancelled', $isRefunded, $refundNote, $id]);

  // Giải phóng khung giờ
  $q=$pdo->prepare("UPDATE time_slots s SET s.status='available',s.held_by=NULL,s.hold_expires_at=NULL WHERE s.id=? AND s.status='booked' AND NOT EXISTS(SELECT 1 FROM booking_slots bs JOIN bookings b ON b.id=bs.booking_id WHERE bs.slot_id=s.id AND b.status IN ('pending','confirmed','checked_in','completed'))");
  foreach($slots as $slot)$q->execute([$slot['id']]);

  // Hoàn lại voucher nếu hủy trước 24h
  $isOver24h = $earliestTs && ($earliestTs - $now) >= 86400;
  if($isOver24h && !empty($b['voucher_id'])){
   $vuStmt=$pdo->prepare('SELECT id,voucher_id FROM voucher_usages WHERE booking_id=? AND released_at IS NULL FOR UPDATE');
   $vuStmt->execute([$id]);
   $vu=$vuStmt->fetch();
   if($vu){
    $pdo->prepare('UPDATE voucher_usages SET released_at=NOW() WHERE id=?')->execute([$vu['id']]);
    $pdo->prepare('UPDATE vouchers SET used_count=GREATEST(0,used_count-1) WHERE id=?')->execute([$vu['voucher_id']]);
   }
  }
 } else {
  throw new BookingError(422,'Trạng thái đích không hợp lệ.');
 }

 return ['booking_id'=>$id,'booking_status'=>$target,'is_deposit_refunded'=>$isRefunded??0,'refund_note'=>$refundNote??null,'message'=>$returnMessage??null];});
}
