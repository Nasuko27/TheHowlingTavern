<?php
header('Content-Type: application/json; charset=utf-8');
include_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecte']);
    exit();
}

$uid     = (int)$_SESSION['id'];
$tid     = (int)($_POST['id_table'] ?? 0);
$contenu = trim($_POST['contenu']   ?? '');

$check = $pdo->prepare("SELECT 1 FROM TABLE_MEMBRE WHERE id_table = :tid AND id_user = :uid");
$check->execute([':tid' => $tid, ':uid' => $uid]);
if (!$tid || !$check->fetch()) {
    echo json_encode(['error' => 'Acces refuse']);
    exit();
}

if ($contenu === '' || mb_strlen($contenu) > 2000) {
    echo json_encode(['error' => 'Message invalide']);
    exit();
}

$pdo->prepare(
    "INSERT INTO TABLE_CHAT (id_table, id_user, contenu) VALUES (:tid, :uid, :msg)"
)->execute([':tid' => $tid, ':uid' => $uid, ':msg' => $contenu]);

echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()]);
