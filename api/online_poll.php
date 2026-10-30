<?php
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['status']) || (string)$_SESSION['status'] !== '1') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit();
}

$seuil = 5;
$stmt = $pdo->prepare(
    "SELECT id, login, nom, prenom, status, last_co
     FROM USER
     WHERE last_co >= NOW() - INTERVAL :seuil MINUTE
     ORDER BY last_co DESC"
);
$stmt->bindValue(':seuil', $seuil, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$users = [];
foreach ($rows as $u) {
    $users[] = [
        'id'      => (int)$u['id'],
        'login'   => htmlspecialchars($u['login']),
        'nom'     => htmlspecialchars($u['prenom'] . ' ' . $u['nom']),
        'status'  => (int)$u['status'],
        'last_co' => htmlspecialchars($u['last_co']),
    ];
}

echo json_encode(['users' => $users, 'count' => count($users)]);
