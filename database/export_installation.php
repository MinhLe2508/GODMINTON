<?php
/** CLI: export the current schema and non-private installation reference data. */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once __DIR__.'/../config/db.php';
$options=getopt('',['output-dir:']);
$directory=$options['output-dir'] ?? __DIR__;
if(!is_dir($directory))throw new RuntimeException('Output directory must already exist.');
$tables=[];
foreach($pdo->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $row){
    if($row[1]==='BASE TABLE'){
        if(!preg_match('/^[a-z0-9_]+$/i',$row[0]))throw new RuntimeException('Unexpected table name');
        $tables[]=$row[0];
    }
}
$schema="-- GODMINTON schema, exported ".date('Y-m-d H:i:s')." +07:00\n-- Select an EMPTY database before importing. No DROP/USE/CREATE DATABASE statements.\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
foreach($tables as $table){
    $ddl=$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1];
    $schema.=preg_replace('/\sAUTO_INCREMENT=\d+/','',$ddl).";\n\n";
}
$schema.="SET FOREIGN_KEY_CHECKS=1;\n";
$seed="-- GODMINTON installation reference data, exported ".date('Y-m-d H:i:s')." +07:00\n-- Import AFTER schema.sql into a new database only.\n-- Development accounts are generated, not copied from live users.\n-- No bookings, slots, contacts, device subscriptions or private accounts are exported.\nSET NAMES utf8mb4;\nSTART TRANSACTION;\n\n";
function installation_insert(PDO $pdo,string $table,array $rows):string{
    if(!$rows)return '-- '.$table.": empty at export time.\n\n";
    $columns=array_keys($rows[0]);$sql='';
    foreach(array_chunk($rows,100) as $chunk){
        $values=[];
        foreach($chunk as $row){$values[]='('.implode(',',array_map(fn($key)=>$row[$key]===null?'NULL':$pdo->quote((string)$row[$key]),$columns)).')';}
        $sql.='INSERT INTO `'.$table.'` (`'.implode('`,`',$columns).'`) VALUES' ."\n".implode(",\n",$values).";\n\n";
    }
    return $sql;
}
$users=[
    1=>['id'=>1,'name'=>'Khách phát triển','email'=>'khach1@gmail.com','phone'=>'0900000001','password_hash'=>password_hash('Khach@123',PASSWORD_DEFAULT),'role'=>'customer','is_verified'=>1],
    2=>['id'=>2,'name'=>'Quản trị phát triển','email'=>'admin@godminton.vn','phone'=>'0900000002','password_hash'=>password_hash('Admin@123',PASSWORD_DEFAULT),'role'=>'admin','is_verified'=>1],
    3=>['id'=>3,'name'=>'Chủ sân phát triển','email'=>'owner.q1@godminton.vn','phone'=>'0900000003','password_hash'=>password_hash('Owner@123',PASSWORD_DEFAULT),'role'=>'owner','is_verified'=>1]
];
foreach($pdo->query('SELECT DISTINCT owner_id FROM courts')->fetchAll(PDO::FETCH_COLUMN) as $owner){
    $owner=(int)$owner;
    if(!isset($users[$owner]))$users[$owner]=['id'=>$owner,'name'=>'Chủ sân phát triển #'.$owner,'email'=>'owner-'.$owner.'@example.test','phone'=>null,'password_hash'=>password_hash('Owner@123',PASSWORD_DEFAULT),'role'=>'owner','is_verified'=>1];
    elseif($users[$owner]['role']==='customer')$users[$owner]['role']='owner';
}
$seed.=installation_insert($pdo,'users',array_values($users));
$counts=[];
foreach(['wards','amenities','courts','court_images','court_amenities','news'] as $table){
    $rows=$pdo->query('SELECT * FROM `'.$table.'` ORDER BY 1')->fetchAll();
    $counts[$table]=count($rows);$seed.=installation_insert($pdo,$table,$rows);
}
$seed.="COMMIT;\n";
foreach(['schema.sql'=>$schema,'seed.sql'=>$seed] as $name=>$content){
    if(file_put_contents($directory.'/'.$name,$content)===false)throw new RuntimeException('Cannot write '.$name);
}
echo json_encode(['schema_tables'=>count($tables),'reference_rows'=>$counts,'development_users'=>count($users),'private_business_rows_exported'=>0],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE),"\n";
