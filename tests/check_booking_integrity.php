<?php
/** Read-only database diagnostics after migration. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Chạy từ terminal.');}
require_once __DIR__.'/../config/db.php';
try {
 $checks=[];
 $q=$pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND COLUMN_NAME IN('booking_code','payment_method','notes')");$q->execute();$checks['week3_columns_present']=(int)$q->fetchColumn()===3;
 $checks['bookings_without_slots']=(int)$pdo->query('SELECT COUNT(*) FROM bookings b WHERE NOT EXISTS(SELECT 1 FROM booking_slots bs WHERE bs.booking_id=b.id)')->fetchColumn();
 $checks['booking_court_mismatches']=(int)$pdo->query('SELECT COUNT(DISTINCT b.id) FROM bookings b JOIN booking_slots bs ON bs.booking_id=b.id JOIN time_slots s ON s.id=bs.slot_id WHERE b.court_id<>s.court_id')->fetchColumn();
 $checks['slots_with_multiple_active_bookings']=(int)$pdo->query("SELECT COUNT(*) FROM (SELECT bs.slot_id FROM booking_slots bs JOIN bookings b ON b.id=bs.booking_id WHERE b.status IN('pending','confirmed','completed') GROUP BY bs.slot_id HAVING COUNT(*)>1) x")->fetchColumn();
 $checks['booked_slots_without_active_booking']=(int)$pdo->query("SELECT COUNT(*) FROM time_slots s WHERE s.status='booked' AND NOT EXISTS(SELECT 1 FROM booking_slots bs JOIN bookings b ON b.id=bs.booking_id WHERE bs.slot_id=s.id AND b.status IN('pending','confirmed','completed'))")->fetchColumn();
 $ok=$checks['week3_columns_present']&&!$checks['bookings_without_slots']&&!$checks['booking_court_mismatches']&&!$checks['slots_with_multiple_active_bookings']&&!$checks['booked_slots_without_active_booking'];
 echo json_encode(['database'=>$pdo->query('SELECT DATABASE()')->fetchColumn(),'status'=>$ok?'success':'review_required','checks'=>$checks,'note'=>'Chỉ đọc, không sửa. Dữ liệu lỗi từ bản cũ hoặc slot demo cần nhóm kiểm tra trước khi dọn.'],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE).PHP_EOL;exit($ok?0:1);
}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(2);}
