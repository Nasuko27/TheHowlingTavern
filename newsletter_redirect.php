<?php
include_once 'config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT id FROM NEWSLETTER WHERE id = :id");
    $stmt->execute([':id' => $id]);
    if ($stmt->fetch()) {
        header('Location: ' . SITE_URL . '/newsletter.php?id=' . $id);
        exit();
    }
}

header('Location: ' . SITE_URL . '/forum.php');
exit();
