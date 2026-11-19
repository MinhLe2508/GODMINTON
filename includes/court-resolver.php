<?php

function court_rebooking_target(PDO $pdo, int $courtId, ?string $knownStatus = null): ?int {
    if ($courtId <= 0)
        return null;
    $stmt = $pdo->prepare('SELECT id, status, booking_open, approval_status FROM courts WHERE id=?');
    $stmt->execute([$courtId]);
    $court = $stmt->fetch();
    if ($court) {
        if ($court['status'] !== 'active' || (int) $court['booking_open'] !== 1 || $court['approval_status'] !== 'approved') {
            return null;
        }

        $gate = $pdo->prepare("SELECT c.id FROM courts c JOIN users u ON u.id=c.owner_id WHERE c.id=? AND u.account_status='active' AND u.deleted_at IS NULL");
        $gate->execute([$courtId]);
        return $gate->fetchColumn() === false ? null : $courtId;
    }

    $replacements = require __DIR__ . '/../config/court-replacements.php';
    $target = $replacements[$courtId] ?? null;
    if (!$target)
        return null;
    $stmt = $pdo->prepare("SELECT c.id FROM courts c JOIN users u ON u.id=c.owner_id WHERE c.id=? AND c.status='active' AND c.approval_status='approved' AND c.booking_open=1 AND u.account_status='active' AND u.deleted_at IS NULL");
    $stmt->execute([$target]);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (int) $id;
}
