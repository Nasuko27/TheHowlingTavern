<?php
include __DIR__ . '/../includes/protect.php';

if(!isset($_GET['type']) || !isset($_GET['systeme']) || !isset($_GET['nb'])) {
    die();
} else {
    $id = 0;
    $nb = $_GET['nb'];
    $type = strtoupper($_GET['type']);
    $systeme = intval($_GET['systeme']);
    include_once __DIR__ . '/../includes/config.php';

    $sql = "SELECT * FROM ".$type." 
         WHERE id_systeme=:systeme 
         ORDER BY nom ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':systeme', $systeme, PDO::PARAM_INT);
    $stmt->execute();
    $items = $stmt->fetchAll();

    if ($type === 'ARME') {
        echo '<div class="d-flex align-items-center">
        <select id="arme' . $nb .'" class="form-select arme" name="arme' . $nb.'">';
        foreach ($items as $item) {
            echo "<option value=" . $item['id'] . ">" . $item["nom"] . "</option>";
        }
        echo '</select>
    <input id="DM'.$nb.'" type="text" class="col-2" value="0" readonly>
    <label class="col-1" for="DM'.$nb.'">DM</label>
    <button class="btn btn-outline-danger" onclick="this.parentElement.remove()">X</button>
    </div>';
    }

    if ($type === 'ARMURE') {
        echo '<div class="d-flex align-items-center">
        <select id="armure' . $nb .'" class="form-select armure" name="armure' . $nb.'">';
        foreach ($items as $item) {
            echo "<option value=" . $item['id'] . ">" . $item["nom"] . "</option>";
        }
        echo '</select>
    <input id="DEF'.$nb.'" type="text" class="col-1 def" value="0" readonly>
    <label class="col-1" for="DEF'.$nb.'">DM</label>
    <button class="btn btn-outline-danger" onclick="this.parentElement.remove()">X</button>
    </div>';
    }
}