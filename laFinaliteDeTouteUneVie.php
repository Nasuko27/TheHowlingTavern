<?php
include __DIR__ . '/includes/protect.php';
include __DIR__ . '/includes/config.php';

$nom = strval($_POST["nom"]);
$id_race = intval($_POST["id_race"]);
$age = intval($_POST["age"]);
$taille = intval($_POST["taille"]);
$poids = intval($_POST["poids"]);
$id_classe = intval($_POST["id_classe"]);
$sexe = strval($_POST["sexe"]);
$ddv = strval($_POST["ddv"]);
$initiative = intval($_POST["initiative"]);
$hp = intval($_POST["hp"]);
$niveau = intval($_POST["niveau"]);
$id_systeme = $_POST["id_systeme"];
$id_user = intval($_POST["id_user"]);
$FOR = intval($_POST["FOR"]);
$DEX = intval($_POST["DEX"]);
$CON = intval($_POST["CON"]);
$INT = intval($_POST["INT"]);
$SAG = intval($_POST["SAG"]);
$CHA = intval($_POST["CHA"]);

$sql = "INSERT INTO FDP(nom,id_race,age,taille,poids,id_classe,sexe,dedevie,initiative,hp,niveau,id_systeme,id_user) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(1, $nom, PDO::PARAM_STR);
$stmt->bindParam(2, $id_race, PDO::PARAM_INT);
$stmt->bindParam(3, $age, PDO::PARAM_INT);
$stmt->bindParam(4, $taille, PDO::PARAM_INT);
$stmt->bindParam(5, $poids, PDO::PARAM_INT);
$stmt->bindParam(6, $id_classe, PDO::PARAM_INT);
$stmt->bindParam(7, $sexe, PDO::PARAM_STR);
$stmt->bindparam(8, $ddv, PDO::PARAM_STR);
$stmt->bindParam(9, $initiative, PDO::PARAM_INT);
$stmt->bindParam(10, $hp, PDO::PARAM_INT);
$stmt->bindParam(11, $niveau, PDO::PARAM_INT);
$stmt->bindParam(12, $id_systeme, PDO::PARAM_INT);
$stmt->bindParam(13, $id_user, PDO::PARAM_INT);
$stmt->execute();
$id = (int)$pdo->lastInsertId();

$armures = [];
$armes = [];
$carac_values = array($FOR,$DEX,$CON,$INT,$SAG,$CHA);

foreach ($_POST as $key => $data) {
    if(strpos($key, "arme") !== FALSE) array_push($armes, intval($data));
    elseif(strpos($key, "armure") !== FALSE) array_push($armures, intval($data));
}

$i = 1;
foreach ($carac_values as $value) {
    $sql = "INSERT INTO stats(id_fdp,id_caracteristique,valeur) VALUES(?,?,?)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->bindParam(2, $i, PDO::PARAM_INT);
    $stmt->bindParam(3, $value, PDO::PARAM_INT);
    $stmt->execute();
    $i ++;
}

foreach ($armures as $armure) {
    $sql = "INSERT INTO inv_armure(id_fdp,id_armure,quantite) VALUES(?,?,1)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->bindParam(2, $armure, PDO::PARAM_INT);
    $stmt->execute();
}

foreach ($armes as $arme) {
    $sql = "INSERT INTO inv_arme(id_fdp,id_arme,quantite) VALUES(?,?,1)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(1, $id, PDO::PARAM_INT);
    $stmt->bindParam(2, $arme, PDO::PARAM_INT);
    $stmt->execute();
}

header("Location: mes_fiches.php");
die();

?>