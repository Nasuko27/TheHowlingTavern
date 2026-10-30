<?php
include_once "protect.php";
include_once "config.php";

$table = filter_input(INPUT_GET, 'table');

$sql = "SELECT COLUMN_NAME FROM information_schema.`COLUMNS` WHERE TABLE_NAME='".$table."'";
$stmt = $pdo->query($sql);
$noms = $stmt->fetchAll();

foreach ($noms as $nom) {
    if($nom['COLUMN_NAME'] !== "id") {
        echo "<div class='d-flex justify-content-between gap-3 m-2'><label for='".$nom["COLUMN_NAME"]."'>".$nom["COLUMN_NAME"]."</label><input type='text' id='".$nom["COLUMN_NAME"]."' name='".$nom["COLUMN_NAME"]."'></div>";
    }
}
echo "<input value='".$table."' type='hidden' name='table'>";
echo "<button class='btn btn-success' type='submit' id='submit'>Créer l'article</button>";
