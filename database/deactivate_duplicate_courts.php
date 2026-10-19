<?php
// CLI only. Retire reviewed old-address duplicates without deleting booking history.
if (PHP_SAPI !== 'cli') {http_response_code(403);exit('CLI only');}
require_once __DIR__ . '/../config/db.php';
$options = getopt('', ['apply','backup-dir:']);
$pairs = [1=>65, 3=>100, 4=>103, 5=>81, 6=>109];
function duplicate_distance(array $a, array $b): float {
    $lat1=deg2rad((float)$a['latitude']);$lat2=deg2rad((float)$b['latitude']);
    $dy=deg2rad((float)$b['longitude']-(float)$a['longitude']);
    return 6371000*2*asin(min(1,sqrt(pow(sin(($lat2-$lat1)/2),2)+cos($lat1)*cos($lat2)*pow(sin($dy/2),2))));
}
$pdo->beginTransaction();
try {
    $ids=array_merge(array_keys($pairs),array_values($pairs));
    $stmt=$pdo->prepare('SELECT * FROM courts WHERE id IN ('.implode(',',array_fill(0,count($ids),'?')).') FOR UPDATE');
    $stmt->execute($ids);$rows=[];
    foreach($stmt->fetchAll() as $row)$rows[(int)$row['id']]=$row;
    foreach($pairs as $old=>$keep) {
        if(!isset($rows[$old],$rows[$keep]))throw new RuntimeException('Missing court in reviewed pair '.$old.'/'.$keep);
        if($rows[$keep]['status']!=='active')throw new RuntimeException('Replacement court is not active: '.$keep);
        if(duplicate_distance($rows[$old],$rows[$keep])>20)throw new RuntimeException('Location changed; review pair '.$old.'/'.$keep.' again.');
    }
    $counts=['bookings'=>(int)$pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn(),'time_slots'=>(int)$pdo->query('SELECT COUNT(*) FROM time_slots')->fetchColumn()];
    if(!array_key_exists('apply',$options)) {
        $pdo->rollBack();echo json_encode(['dry_run'=>true,'retire_to_keep'=>$pairs,'counts'=>$counts],JSON_PRETTY_PRINT),"\n";exit;
    }
    $backupDir=$options['backup-dir'] ?? '';
    if(!is_string($backupDir)||!is_dir($backupDir))throw new RuntimeException('Provide an existing --backup-dir outside the web root.');
    $webRoot=realpath(__DIR__.'/..');$destination=realpath($backupDir);
    if(str_starts_with(strtolower($destination),strtolower($webRoot)))throw new RuntimeException('Backup must be outside the web root.');
    $backup=$destination.'/courts-before-retire-duplicates-'.date('Ymd-His').'-'.getmypid().'.json';
    if(file_put_contents($backup,json_encode(['pairs'=>$pairs,'rows'=>array_values($rows),'counts'=>$counts],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))===false)throw new RuntimeException('Backup failed.');
    $update=$pdo->prepare("UPDATE courts SET status='inactive' WHERE id=? AND status='active'");
    $changed=[];
    foreach($pairs as $old=>$keep){$update->execute([$old]);if($update->rowCount())$changed[]=$old;}
    foreach($counts as $table=>$count)if((int)$pdo->query('SELECT COUNT(*) FROM '.$table)->fetchColumn()!==$count)throw new RuntimeException('Related row counts changed.');
    $pdo->commit();
    echo json_encode(['retired_ids'=>$changed,'kept_ids'=>array_values($pairs),'history_preserved'=>true,'backup'=>$backup],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT),"\n";
} catch(Throwable $error){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,$error->getMessage()."\n");exit(1);}
