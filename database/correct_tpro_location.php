<?php
if (PHP_SAPI !== 'cli') {http_response_code(403);exit('CLI only');}
require_once __DIR__.'/../config/db.php';
$options=getopt('',['apply','backup-dir:']);
$pdo->beginTransaction();
try {
    $stmt=$pdo->prepare('SELECT * FROM courts WHERE id IN (?,?) FOR UPDATE');$stmt->execute([75,76]);
    $rows=[];foreach($stmt->fetchAll() as $row)$rows[(int)$row['id']]=$row;
    if(!isset($rows[75],$rows[76]) || stripos($rows[75]['name'],'TPro')===false)throw new RuntimeException('Court IDs changed; review before applying.');
    if(!array_key_exists('apply',$options)){$pdo->rollBack();echo "Dry run: update TPro #75 only; T12 #76 stays unchanged.\n";exit;}
    $directory=$options['backup-dir'] ?? '';
    if(!is_dir($directory))throw new RuntimeException('Provide an existing backup directory outside the web root.');
    $directory=realpath($directory);
    if(str_starts_with(strtolower($directory),strtolower(realpath(__DIR__.'/..'))))throw new RuntimeException('Backup must be outside web root.');
    $backup=$directory.'/tpro-before-correction-'.date('Ymd-His').'.json';
    if(file_put_contents($backup,json_encode(array_values($rows),JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))===false)throw new RuntimeException('Backup failed.');
    $description=$rows[75]['description'] ?? '';
    $note='Tên, địa chỉ và tọa độ được người dùng đính chính ngày 19/10/2026 theo ảnh Google Maps cung cấp; chưa kiểm tra thực địa. TPro và T12 là hai địa điểm riêng.';
    $lines=explode("\n",$description);
    $lines=array_values(array_filter($lines,fn($line)=>!preg_match('/^(Tọa độ|Tên, địa chỉ)/u',trim($line))));
    $lines[]=$note;
    $update=$pdo->prepare('UPDATE courts SET name=?,address=?,latitude=?,longitude=?,description=? WHERE id=?');
    $update->execute(['Học viện cầu lông TPro','1k Ngô Văn Sở, Xóm Chiếu, Hồ Chí Minh, Việt Nam',10.762291,106.710475,implode("\n",$lines),75]);
    $stmt=$pdo->prepare('SELECT * FROM courts WHERE id=?');$stmt->execute([76]);
    if($stmt->fetch()!==$rows[76])throw new RuntimeException('T12 changed unexpectedly.');
    $pdo->commit();echo "Updated TPro #75; T12 #76 preserved. Backup: ",$backup,"\n";
}catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,$error->getMessage()."\n");exit(1);}
