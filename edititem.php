<?php
include_once "protect.php";
include "config.php";
include "header.php";

log_action('Gestion systeme de jeu');

include"adminnav.php";
echo "<script src='fetchsql.js'></script>";

$table = filter_input(INPUT_GET, 'table');
$id = filter_input(INPUT_GET, 'id');

if(isset($_GET['id']) && isset($_GET['table'])){

    $sql = "SELECT * FROM ".$table." WHERE id = :id;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $article = $stmt->fetch();

    $sql = "SELECT COLUMN_NAME FROM information_schema.`COLUMNS` WHERE TABLE_NAME='".$table."'";
    $stmt = $pdo->query($sql);
    $noms = $stmt->fetchAll();

    echo "<h2>Modification d'un article</h2>";
    echo "<div class='d-flex flex-column gap-3 align-items-center'><form method='post' action='modifyitem.php'>";
    foreach ($noms as $nom) {
        if($nom["COLUMN_NAME"] !== "id") {
            echo "<div class='d-flex justify-content-between gap-3 m-2'><label for='" . $nom["COLUMN_NAME"] . "'>" . $nom["COLUMN_NAME"] . "</label><input type='text' id='" . $nom["COLUMN_NAME"] . "' name='" . $nom["COLUMN_NAME"] . "' value='" . $article[$nom["COLUMN_NAME"]] . "'></div>";
        }
    };
    echo "<button class='btn btn-success' type='submit' id='submit'>Modifier l'article</button>";
} else {
    echo "
<h2>Ajout d'un article dans le systême de jeu</h2>
<form method='post' action='additem.php'>
<div class='d-flex gap-3 align-items-center'>
<label for='table'>Type d'article : </label><input style='margin: 10px;' list='covalues' id='table' name='table'>
    <button class='btn btn-primary' onmouseup='fetchTable()' type='button'>Sélectionner</button>
    <datalist id='covalues'>
        <option value='CLASSE'>Classe</option>
        <option value='RACE'>Race</option>
        <option value='OBJET'>Objet</option>
        <option value='COMPETENCE'>Compétence</option>
        <option value='ARME'>Arme</option>
        <option value='RESSOURCE'>Ressource</option>
        <option value='CARACTERISTIQUE'>Caractéristique</option>
        <option value='ARMURE'>Armure</option>
    </datalist>
    </div>
    <div class='d-flex flex-column gap-3' id='tableau'>
    </div>    
</form>
";
}
?>


