<?php
ob_start();
include_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['save_fiche'])) {
    header('Location: tables.php');
    exit();
}

$uid = (int)$_SESSION['id'];
$fdpId = (int)($_POST['id_fdp'] ?? 0);
$tableId = (int)($_POST['id_table'] ?? 0);
$isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';

if (!$fdpId) { header('Location: tables.php'); exit(); }

$stmt = $pdo->prepare("SELECT id_user FROM FDP WHERE id = :fdp");
$stmt->execute([':fdp' => $fdpId]);
$owner = $stmt->fetch();
if (!$owner) { header('Location: tables.php'); exit(); }

$isOwner = (int)$owner['id_user'] === $uid;
$isMJ = false;
if ($tableId) {
    $stmt2 = $pdo->prepare("SELECT id_maitrejeu FROM TABLEDEJEU WHERE id = :tid");
    $stmt2->execute([':tid' => $tableId]);
    $t = $stmt2->fetch();
    $isMJ = $t && (int)$t['id_maitrejeu'] === $uid;
}

if (!$isOwner && !$isMJ && !$isAdmin) {
    header('Location: tables.php');
    exit();
}

$hp = (int)($_POST['hp'] ?? 0);
$niveau = max(1, (int)($_POST['niveau'] ?? 1));
$initiative = (int)($_POST['initiative'] ?? 0);

$pdo->prepare("UPDATE FDP SET hp = :hp, niveau = :niv, initiative = :init WHERE id = :id")
    ->execute([':hp' => $hp, ':niv' => $niveau, ':init' => $initiative, ':id' => $fdpId]);

$caracs = $pdo->prepare("SELECT id FROM CARACTERISTIQUE WHERE id IN (SELECT id_caracteristique FROM stats WHERE id_fdp = :fdp)");
$caracs->execute([':fdp' => $fdpId]);
foreach ($caracs->fetchAll() as $c) {
    $key = 'carac_' . $c['id'];
    if (isset($_POST[$key])) {
        $pdo->prepare("UPDATE stats SET valeur = :val WHERE id_fdp = :fdp AND id_caracteristique = :cid")
            ->execute([':val' => (int)$_POST[$key], ':fdp' => $fdpId, ':cid' => $c['id']]);
    }
}

if ($tableId) {
    header('Location: tablejeu.php?id=' . $tableId);
} else {
    header('Location: fiche.php?id=' . $fdpId);
}
exit();
