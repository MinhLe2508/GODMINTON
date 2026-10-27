<?php
require_once __DIR__.'/../../includes/auth-security.php';
require_once __DIR__.'/../../includes/account-deletion.php';
try {
    $in=god_auth_input();$raw=god_auth_text($in,'token');$mode=god_auth_text($in,'mode');
    if (!in_array($mode,['reset','verify','delete','email_change'],true) || !preg_match('/^[a-f0-9]{64}$/D',$raw)) god_auth_out(422,'Liên kết không hợp lệ.');
    if (!god_auth_limit($pdo,'token-ip',god_auth_ip(),30,900)) god_auth_out(429,'Quá nhiều yêu cầu. Vui lòng thử lại sau.');
    $password=$mode==='reset'?god_auth_text($in,'password'):'';
    if ($mode==='reset' && !god_auth_password_valid($password)) god_auth_out(422,'Mật khẩu cần ít nhất 8 ký tự, có chữ hoa, chữ thường, chữ số và ký tự đặc biệt.');
    $hash=$mode==='reset'?god_auth_password_hash($password):null;
    $pdo->beginTransaction();
    // Same lock order as issuing tokens prevents reset/resend races.
    $s=$pdo->prepare('SELECT user_id FROM auth_tokens WHERE token_hash=? AND purpose=?');$s->execute([hash('sha256',$raw),$mode]);$id=$s->fetchColumn();
    if ($id) { $s=$pdo->prepare('SELECT * FROM users WHERE id=? FOR UPDATE');$s->execute([$id]);$account=$s->fetch(); }
    if(!$id || !$account || ($account['deleted_at']!==null || $account['account_status']!=='active')){$pdo->rollBack();god_auth_out(410,'Liên kết không còn hiệu lực.');}
    $s=$pdo->prepare('SELECT id,user_id FROM auth_tokens WHERE token_hash=? AND purpose=? AND used_at IS NULL AND expires_at>NOW() FOR UPDATE');
    $s->execute([hash('sha256',$raw),$mode]);$token=$s->fetch();
    if (!$token) { $pdo->rollBack();god_auth_out(410,'Liên kết đã hết hạn hoặc đã được sử dụng. Vui lòng yêu cầu email mới.'); }
    if ($mode==='email_change') {
        $email=$account['pending_email']??null;if(!$email||!filter_var($email,FILTER_VALIDATE_EMAIL)){$pdo->rollBack();god_auth_out(410,'Yêu cầu đổi email không còn hiệu lực.');}
        $q=$pdo->prepare('SELECT id FROM users WHERE email=? AND id<>?');$q->execute([$email,$account['id']]);if($q->fetchColumn()){$pdo->rollBack();god_auth_out(409,'Email mới đã được tài khoản khác sử dụng. Liên hệ Admin để tạo yêu cầu mới.');}
        $pdo->prepare('UPDATE users SET email=?,pending_email=NULL,session_version=session_version+1 WHERE id=?')->execute([$email,$account['id']]);
        $pdo->prepare('UPDATE auth_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$account['id']]);
    } elseif ($mode==='delete') {
        if(($in['confirm_delete']??false)!==true){$pdo->rollBack();god_auth_out(422,'Bạn cần xác nhận xóa tài khoản.');}
        $block=god_auth_deletion_blocker($pdo,$account);
        if($block){$pdo->rollBack();god_auth_out(409,$block);}
        god_auth_delete_account($pdo,(int)$token['user_id']);
    } elseif ($mode==='reset') {
        $pdo->prepare('UPDATE users SET password_hash=?,pending_email=NULL,is_verified=1,session_version=session_version+1 WHERE id=?')->execute([$hash,$token['user_id']]);
        $pdo->prepare('UPDATE auth_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$token['user_id']]);
    } else {
        $pdo->prepare('UPDATE users SET is_verified=1 WHERE id=?')->execute([$token['user_id']]);
        $pdo->prepare('UPDATE auth_tokens SET used_at=NOW() WHERE id=?')->execute([$token['id']]);
    }
    $pdo->commit();
    if (in_array($mode,['reset','delete','email_change'],true)) {
        if (god_auth_ensure_remember_schema($pdo)) {
            $pdo->prepare('DELETE FROM remember_tokens WHERE user_id=?')->execute([(int)$token['user_id']]);
        }
        god_auth_clear_saved_credentials();
        if ((int)($_SESSION['user_id']??0)===(int)$token['user_id']) god_auth_clear(true);
    }
    if($mode==='email_change')god_auth_out(200,'Email đăng nhập đã được thay đổi. Vui lòng đăng nhập lại bằng địa chỉ mới.');
    if($mode==='delete')god_auth_out(200,'Tài khoản đã được xóa. Thông tin hồ sơ đã được ẩn danh và mọi phiên đăng nhập đã bị thu hồi.');
    god_auth_out(200,$mode==='reset'?'Đã đổi mật khẩu và đăng xuất các phiên cũ. Vui lòng đăng nhập lại.':'Email đã được xác minh. Bạn có thể đăng nhập.',['next_url'=>$mode==='verify'&&!empty($account['owner_intent'])?'owner-application.php':'auth.php']);
} catch (Throwable $e) { god_auth_failure($e); }
