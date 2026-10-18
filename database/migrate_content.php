<?php
// CLI only. Adds content tables without deleting or seeding business records.
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only'); }
require_once __DIR__ . '/../config/db.php';
$sql = file_get_contents(__DIR__ . '/migration_content.sql');
foreach (explode(';', $sql) as $statement) {
    if (trim($statement) !== '') $pdo->exec($statement);
}
// Existing team databases already contain news/contacts with a smaller schema.
// Preserve existing published articles and add only the missing metadata columns.
$newsColumns = array_column($pdo->query('SHOW COLUMNS FROM news')->fetchAll(), 'Field');
if (!in_array('status', $newsColumns, true)) $pdo->exec("ALTER TABLE news ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'published'");
$contactColumns = array_column($pdo->query('SHOW COLUMNS FROM contacts')->fetchAll(), 'Field');
if (!in_array('user_id', $contactColumns, true)) $pdo->exec('ALTER TABLE contacts ADD COLUMN user_id INT UNSIGNED NULL');
// Verify the schema expected by the endpoints, including on a repeated migration.
$pdo->query('SELECT id,title,summary,content,image_url,category,status,published_at FROM news LIMIT 0');
$pdo->query('SELECT id,user_id,name,phone,email,subject,message,status FROM contacts LIMIT 0');
echo "CONTENT_MIGRATION_OK\n";
