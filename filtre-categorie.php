<?php
include_once "protect.php";
include_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();
 
if (!isset($_SESSION['status']) || (string)$_SESSION['status'] !== '1') {
    http_response_code(403);
    exit;
}
 

$allowed = [
    'ARME'             => 'Arme',
    'CLASSE'           => 'Classe',
    'ARMURE'           => 'Armure',
    'CARACTERISTIQUE'  => 'Caract&eacute;ristique',
    'OBJET'            => 'Objet',
    'COMPETENCE'       => 'Comp&eacute;tence',
    'RACE'             => 'Race',
    'RESSOURCE'        => 'Ressource',
];
 
$saisie = trim($_GET['q'] ?? '');
$saisieMaj = strtoupper($saisie);
 
if ($saisie === '') {
    $tablesAInterroger = $allowed;
    $rechercheNom = null;
} elseif (isset($allowed[$saisieMaj])) {
    $tablesAInterroger = [$saisieMaj => $allowed[$saisieMaj]];
    $rechercheNom = null;
} else {
    $tablesAInterroger = $allowed;
    $rechercheNom = $saisie;
}
 
foreach ($tablesAInterroger as $nomTable => $label) {
    if ($rechercheNom !== null) {
        $stmt = $pdo->prepare("SELECT id, nom FROM `$nomTable` WHERE nom LIKE :recherche");
        $stmt->execute(['recherche' => '%' . $rechercheNom . '%']);
    } else {
        $stmt = $pdo->query("SELECT id, nom FROM `$nomTable`");
    }
    $items = $stmt->fetchAll();
 
    foreach ($items as $item) {
        echo "<tr data-cat='" . htmlspecialchars($nomTable) . "'>";
        echo "<td>" . htmlspecialchars($item['nom']) . "</td>";
        echo "<td><span class='action-pill' style='background:#3d0d0d;border:1px solid #620D14'>$label</span></td>";
        echo "<td><a href='edititem.php?id=" . (int)$item['id'] . "&table=$nomTable' class='btn-edit'>D&eacute;tails &rsaquo;</a></td>";
        echo "</tr>";
    }
}
 