<?php
include_once __DIR__ . '/../includes/protect.php';
include_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['id'])) { die(); }

$sid = (int)$_GET['id'];

$races = $pdo->prepare(
    "SELECT id, nom FROM RACE WHERE id_systeme = :sid ORDER BY nom"
);
$races->execute([':sid' => $sid]);

$classes = $pdo->prepare(
    "SELECT id, nom, dedevie, caracmagie FROM CLASSE WHERE id_systeme = :sid ORDER BY nom"
);
$classes->execute([':sid' => $sid]);

$armes = $pdo->prepare(
    "SELECT id, nom, categorie, degat FROM ARME WHERE id_systeme = :sid ORDER BY nom"
);
$armes->execute([':sid' => $sid]);

$armures = $pdo->prepare(
    "SELECT id, nom, type, defense FROM ARMURE WHERE id_systeme = :sid ORDER BY nom"
);
$armures->execute([':sid' => $sid]);

$objets = $pdo->prepare(
    "SELECT id, nom FROM OBJET WHERE id_systeme = :sid ORDER BY nom"
);
$objets->execute([':sid' => $sid]);

echo json_encode([
    'races'   => $races->fetchAll(),
    'classes' => $classes->fetchAll(),
    'armes'   => $armes->fetchAll(),
    'armures' => $armures->fetchAll(),
    'objets'  => $objets->fetchAll()
]);
