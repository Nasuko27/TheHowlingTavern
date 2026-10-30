<?php
include __DIR__ . '/../includes/protect.php';

if(!isset($_GET['type']) || !isset($_GET['systeme']) || !isset($_GET['id'])) {
    die();
} else {
    $id = intval($_GET['id']);
    $type = strtoupper($_GET['type']);
    $systeme = intval($_GET['systeme']);
    include_once __DIR__ . '/../includes/config.php';

    $sql = "SELECT * FROM " . $type . " 
         WHERE id_systeme=:systeme 
         AND id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':systeme', $systeme, PDO::PARAM_INT);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch();

    echo json_encode($item);
}