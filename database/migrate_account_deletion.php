<?php
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require_once __DIR__.'/../config/db.php';
$columns=$pdo->query('SHOW COLUMNS FROM users')->fetchAll(PDO::FETCH_COLUMN);
if(!in_array('deleted_at',$columns,true))$pdo->exec('ALTER TABLE users ADD deleted_at DATETIME NULL DEFAULT NULL');
$s=$pdo->query("SHOW COLUMNS FROM auth_tokens LIKE 'purpose'")->fetch();
if(!str_contains($s['Type'],"'delete'"))$pdo->exec("ALTER TABLE auth_tokens MODIFY purpose ENUM('verify','reset','delete') NOT NULL");
echo "Account deletion migration OK.\n";
