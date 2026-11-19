<?php
require_once dirname(__DIR__) . '/includes/auth-check.php';
header('Cache-Control: no-store, max-age=0');
$selection = array_intersect_key($_GET, array_flip(['court_id', 'date', 'slot_ids', 'slot_id']));
header('Location: payment.php' . ($selection ? '?' . http_build_query($selection) : ''), true, 302);
exit;
