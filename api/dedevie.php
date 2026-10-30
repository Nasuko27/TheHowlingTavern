<?php
include __DIR__ . '/../includes/protect.php';

if(!isset($_GET['classe'])) {
    die();
} else {
    include __DIR__ . '/../includes/config.php';

    $classe = $_GET['classe'];

    $sql = "SELECT * FROM CLASSE
            WHERE id = :classe";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':classe', $classe);
    $stmt->execute();
    $classe = $stmt->fetch();
    $output = json_encode($classe);

    echo $output;
}?>