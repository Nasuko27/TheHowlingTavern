<?php
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit();
}

$uid   = (int)$_SESSION['id'];
$msgId = (int)($_POST['id_msg'] ?? 0);
$isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';

if (!$msgId) {
    echo json_encode(['ok' => false]);
    exit();
}

$stmt = $pdo->prepare(
    "SELECT c.id_table, c.id_user, t.id_maitrejeu
     FROM TABLE_CHAT c
     JOIN TABLEDEJEU t ON t.id = c.id_table
     WHERE c.id = :id"
);
$stmt->execute([':id' => $msgId]);
$msg = $stmt->fetch();

if (!$msg) {
    echo json_encode(['ok' => false]);
    exit();
}

$isMJ = (int)$msg['id_maitrejeu'] === $uid;

if ($isAdmin || $isMJ || (int)$msg['id_user'] === $uid) {
    $pdo->prepare("DELETE FROM TABLE_CHAT WHERE id = :id")->execute([':id' => $msgId]);
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false]);
}
