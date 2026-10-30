<?php
include_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id'])) {
    header('Location: ' . BASE . 'compte/login.php');
    exit();
}

$stmt = $pdo->prepare("SELECT isVerified, status FROM USER WHERE id = :id");
$stmt->bindValue(':id', $_SESSION['id'], PDO::PARAM_INT);
$stmt->execute();
$u = $stmt->fetch();

if ($u && (int)$u['status'] === 2) {
    header('Location: ' . BASE . 'compte/banni.php');
    exit();
}

if (!$u || !$u['isVerified']) {
    http_response_code(403);
    die('
        <p style="padding:2rem;font-family:sans-serif;color:#c0392b">
            Compte non vérifié. <a href="' . BASE . 'compte/verification.php">Vérifier votre compte</a>
        </p>
    ');
}
