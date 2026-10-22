<?php
/** Call inside a transaction after locking the user row. Never delete booking history. */
function god_auth_deletion_blocker(PDO $pdo,array $user): ?string {
    if($user['role']==='admin')return 'Tài khoản quản trị cần được bàn giao quyền trước. Vui lòng liên hệ quản trị hệ thống.';
    $s=$pdo->prepare('SELECT id FROM courts WHERE owner_id=? FOR UPDATE');$s->execute([$user['id']]);
    if($s->fetchColumn())return 'Bạn còn sân đang sở hữu. Vui lòng chuyển giao hoặc làm việc với quản trị viên trước khi xóa tài khoản.';
    $s=$pdo->prepare("SELECT id FROM bookings WHERE user_id=? AND (status IN ('pending','confirmed') OR (status='cancelled' AND deposit_amount>0)) FOR UPDATE");$s->execute([$user['id']]);
    if($s->fetchColumn())return 'Bạn còn đơn chưa hoàn tất hoặc đơn đã hủy có tiền cọc cần được xử lý. Vui lòng liên hệ sân trước khi xóa tài khoản.';
    return null;
}
function god_auth_delete_account(PDO $pdo,int $id): void {
    $email='deleted-'.$id.'-'.bin2hex(random_bytes(10)).'@example.invalid';
    $pdo->prepare("UPDATE users SET name='Tài khoản đã xóa',pending_email=NULL,email=?,phone=NULL,avatar_url=NULL,password_hash=?,role='customer',is_verified=0,session_version=session_version+1,deleted_at=NOW() WHERE id=? AND deleted_at IS NULL")
        ->execute([$email,god_auth_password_hash(bin2hex(random_bytes(32))),$id]);
    $pdo->prepare('DELETE FROM push_subscriptions WHERE user_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM contacts WHERE user_id=?')->execute([$id]);
    $pdo->prepare('DELETE FROM account_notifications WHERE user_id=?')->execute([$id]);
    $pdo->prepare("UPDATE owner_requests SET representative_name='Tài khoản đã xóa',representative_phone='',evidence_note=NULL,evidence_url=NULL,payload='{}',status=IF(status IN ('draft','pending','needs_info','rejected'),'withdrawn',status),revision=revision+1 WHERE user_id=?")->execute([$id]);
    $pdo->prepare('UPDATE bookings SET notes=NULL WHERE user_id=?')->execute([$id]);
    $pdo->prepare("UPDATE time_slots SET status='available',held_by=NULL,hold_expires_at=NULL WHERE held_by=? AND status='holding'")->execute([$id]);
    $pdo->prepare('UPDATE auth_tokens SET used_at=NOW() WHERE user_id=? AND used_at IS NULL')->execute([$id]);
}
