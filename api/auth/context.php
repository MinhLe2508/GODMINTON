<?php

require_once __DIR__ . '/../../includes/auth-security.php';
if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    god_auth_out(405, 'Chỉ hỗ trợ GET.');
god_auth_out(200, '', ['csrf_token' => $_SESSION['csrf_token']]);
