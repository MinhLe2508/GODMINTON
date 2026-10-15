<?php
require_once __DIR__.'/../../includes/booking-account-api.php';
try{if($_SERVER['REQUEST_METHOD']!=='GET')booking_account_out(405,null,'Chỉ hỗ trợ GET.');booking_account_out(200,['user'=>booking_account_user(true),'csrf_token'=>$_SESSION['csrf_token']]);}catch(Throwable $e){booking_account_error($e);}
