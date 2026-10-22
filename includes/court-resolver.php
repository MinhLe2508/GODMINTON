<?php
function court_rebooking_target(PDO $pdo, int $courtId, ?string $knownStatus=null): ?int {
    if ($courtId<=0) return null;
    if ($knownStatus===null) {
        $stmt=$pdo->prepare('SELECT status FROM courts WHERE id=?');$stmt->execute([$courtId]);
        $knownStatus=$stmt->fetchColumn();
        if ($knownStatus===false) return null;
    }
    if ($knownStatus==='active') {
        $gate=$pdo->prepare("SELECT c.id FROM courts c JOIN users u ON u.id=c.owner_id WHERE c.id=? AND c.approval_status='approved' AND c.booking_open=1 AND u.account_status='active' AND u.deleted_at IS NULL");$gate->execute([$courtId]);return $gate->fetchColumn()===false?null:$courtId;
    }
    $replacements=require __DIR__.'/../config/court-replacements.php';
    $target=$replacements[$courtId] ?? null;
    if (!$target) return null;
    $stmt=$pdo->prepare("SELECT c.id FROM courts c JOIN users u ON u.id=c.owner_id WHERE c.id=? AND c.status='active' AND c.approval_status='approved' AND c.booking_open=1 AND u.account_status='active' AND u.deleted_at IS NULL");$stmt->execute([$target]);
    $id=$stmt->fetchColumn();
    return $id===false ? null : (int)$id;
}
