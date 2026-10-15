<?php
/** CLI only; upgrades current database without deleting bookings. Re-runnable. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Chạy từ terminal.');}
require_once __DIR__.'/../config/db.php';
try {
 foreach(['booking_code'=>'VARCHAR(30) NULL','payment_method'=>'VARCHAR(30) NULL','notes'=>'TEXT NULL']as $column=>$definition){
  $s=$pdo->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');$s->execute(['bookings',$column]);
  if(!$s->fetchColumn())$pdo->exec("ALTER TABLE bookings ADD COLUMN `$column` $definition");
 }
 $pdo->exec('ALTER TABLE bookings MODIFY booking_code VARCHAR(30) NULL');
 $pdo->exec("UPDATE bookings b SET b.booking_code=CONCAT('GDM-',DATE_FORMAT(COALESCE((SELECT MIN(s.slot_date) FROM booking_slots bs JOIN time_slots s ON s.id=bs.slot_id WHERE bs.booking_id=b.id),b.created_at),'%Y%m%d'),'-',LPAD(b.id,GREATEST(4,LENGTH(b.id)),'0')) WHERE b.booking_code IS NULL OR b.booking_code=''");
 $s=$pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='bookings' AND INDEX_NAME='uq_booking_code'");
 if(!$s->fetchColumn())$pdo->exec('ALTER TABLE bookings ADD UNIQUE KEY uq_booking_code(booking_code)');
 echo 'Migration tuần 3 thành công trên DB: '.$pdo->query('SELECT DATABASE()')->fetchColumn().PHP_EOL;
 echo 'Không xóa dữ liệu. DDL MySQL tự commit; sao lưu trước khi chạy trên DB nhóm.'.PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,'Migration thất bại: '.$e->getMessage().PHP_EOL);exit(1);}
