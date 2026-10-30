<?php
include "protect.php";

if(!isset($_GET['race'])) {
    die();
} else {
    include "config.php";

    $race = $_GET['race'];

    $sql = "SELECT sigle,valeur FROM bonus_racial
            INNER JOIN CARACTERISTIQUE 
            ON id_caracteristique=id 
            WHERE id_race = :race";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':race', $race);
    $stmt->execute();
    $bonus_racial = $stmt->fetchAll();
    $output = json_encode($bonus_racial);

    echo $output;
}?>