<?php
include_once __DIR__ . '/protect.php';

// Réservé aux admins : le statut est relu en base pour qu'un admin rétrogradé ou banni perde l'accès tout de suite
$stmt = $pdo->prepare("SELECT status FROM USER WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['id'], PDO::PARAM_INT);
$stmt->execute();

if ((int)$stmt->fetchColumn() !== 1) {
    http_response_code(403);
    header("Location: " . BASE . "index.php");
    exit();
}
