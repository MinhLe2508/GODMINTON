<?php
/** Shared session lifecycle; call after config/db.php. */
function god_auth_start(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        ini_set('session.use_strict_mode','1');
        ini_set('session.use_only_cookies','1');
        session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','path'=>'/',
            'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off']);
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32));
}
function god_auth_clear(): void {
    $_SESSION=[];
    session_regenerate_id(true);
    $_SESSION['csrf_token']=bin2hex(random_bytes(32));
}
function god_auth_user(PDO $pdo): ?array {
    god_auth_start();
    $id=(int)($_SESSION['user_id']??0);
    if (!$id) return null;
    $s=$pdo->prepare('SELECT id,name,email,phone,role,avatar_url,created_at,is_verified,session_version FROM users WHERE id=?');
    $s->execute([$id]); $u=$s->fetch(); $now=time();
    if (!$u || !$u['is_verified'] || (int)$u['session_version']!==(int)($_SESSION['auth_version']??0)
        || $now-(int)($_SESSION['last_activity']??$now)>1800
        || $now-(int)($_SESSION['authenticated_at']??$now)>43200) {
        god_auth_clear(); return null;
    }
    $_SESSION['auth_version']=(int)($_SESSION['auth_version']??0);
    $_SESSION['last_activity']=$now;
    $_SESSION['authenticated_at']??=$now;
    foreach (['name','email','phone','role'] as $key) $_SESSION[$key]=$u[$key];
    unset($u['session_version'],$u['is_verified']); $u['id']=(int)$u['id'];
    return $u;
}
function god_auth_login(array $user): void {
    god_auth_clear();
    $_SESSION['user_id']=(int)$user['id'];
    $_SESSION['auth_version']=(int)$user['session_version'];
    $_SESSION['authenticated_at']=$_SESSION['last_activity']=time();
    foreach (['name','email','phone','role'] as $key) $_SESSION[$key]=$user[$key]??'';
}
