<?php
include_once __DIR__ . '/../includes/protect_admin.php';
include __DIR__ . '/../includes/config.php';

$table = $_POST['table'];
$sql = "SELECT COLUMN_NAME FROM information_schema.`COLUMNS` WHERE TABLE_NAME='".$table."'";
$stmt = $pdo->query($sql);
$noms = $stmt->fetchAll();
$col = '';
$values = '';
$cpt = 0;
foreach ($noms as $nom) {
    if (!empty(trim($_POST[$nom['COLUMN_NAME']]))) {
        if ($cpt === 0) {
            $col = $nom['COLUMN_NAME'];
            $cpt = 1;
            if
            (!is_numeric($_POST[$nom['COLUMN_NAME']])) {
                $values .= "'" . $_POST[$nom['COLUMN_NAME']] . "'";
            } else {
                $values .= $_POST[$nom['COLUMN_NAME']];
            }
        } else {
            $col .= ", " . $nom['COLUMN_NAME'];
            if (!is_numeric($_POST[$nom['COLUMN_NAME']])) {
                $values .= ", '" . $_POST[$nom['COLUMN_NAME']] . "'";
            } else {
                $values .= ", " . $_POST[$nom['COLUMN_NAME']];
            }
        }
    }
}

$sql = "INSERT INTO ".$table."(".$col.") VALUES(".$values.");";
$stmt = $pdo->query($sql);
header("Location:edititem.php");