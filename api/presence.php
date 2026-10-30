<?php
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit();
}

$stmt = $pdo->prepare("UPDATE USER SET last_co = NOW() WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['id'], PDO::PARAM_INT);
$stmt->execute();

echo json_encode(['ok' => true]);
