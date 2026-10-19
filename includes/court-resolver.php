<?php
function court_rebooking_target(PDO $pdo, int $courtId, ?string $knownStatus=null): ?int {
    if ($courtId<=0) return null;
    if ($knownStatus===null) {
        $stmt=$pdo->prepare('SELECT status FROM courts WHERE id=?');$stmt->execute([$courtId]);
        $knownStatus=$stmt->fetchColumn();
        if ($knownStatus===false) return null;
    }
    if ($knownStatus==='active') return $courtId;
    $replacements=require __DIR__.'/../config/court-replacements.php';
    $target=$replacements[$courtId] ?? null;
    if (!$target) return null;
    $stmt=$pdo->prepare("SELECT id FROM courts WHERE id=? AND status='active'");$stmt->execute([$target]);
    $id=$stmt->fetchColumn();
    return $id===false ? null : (int)$id;
}
