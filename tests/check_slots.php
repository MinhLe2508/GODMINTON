<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, court_id, sub_court_name, slot_date, start_time, end_time, status FROM time_slots WHERE slot_date >= CURDATE() AND start_time >= '12:00:00' AND start_time <= '15:00:00' ORDER BY slot_date, sub_court_name, start_time LIMIT 20");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo "ID: {$row['id']} | Court: {$row['court_id']} | Sub: {$row['sub_court_name']} | Date: {$row['slot_date']} | {$row['start_time']} - {$row['end_time']} | {$row['status']}\n";
}
