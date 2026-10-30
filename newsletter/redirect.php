<?php
include_once __DIR__ . '/../includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT id FROM NEWSLETTER WHERE id = :id");
    $stmt->execute([':id' => $id]);
    if ($stmt->fetch()) {
        header('Location: ' . SITE_URL . '/newsletter/index.php?id=' . $id);
        exit();
    }
}

header('Location: ' . SITE_URL . '/forum/index.php');
exit();
