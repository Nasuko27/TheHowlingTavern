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


$requetes = [
    'ARME'             => "SELECT id, nom FROM ARME WHERE id_systeme = 2",
    'ARMURE'           => "SELECT id, nom FROM ARMURE WHERE id_systeme = 2",
    'CLASSE'           => "SELECT id, nom FROM CLASSE WHERE id_systeme = 2",
    'CARACTERISTIQUE'  => "SELECT id, nom FROM CARACTERISTIQUE WHERE id_systeme = 2",
    'OBJET'            => "SELECT id, nom FROM OBJET WHERE id_systeme = 2",
    'RACE'             => "SELECT id, nom FROM RACE WHERE id_systeme = 2",
    'RESSOURCE'        => "SELECT id, nom FROM RESSOURCE WHERE id_systeme = 2",
    'COMPETENCE'       => "SELECT COMPETENCE.id, COMPETENCE.nom FROM COMPETENCE
                            INNER JOIN CARACTERISTIQUE ON COMPETENCE.id_caracteristique = CARACTERISTIQUE.id
                            WHERE CARACTERISTIQUE.id_systeme = 2",
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
    $baseSql = $requetes[$nomTable];

    if ($rechercheNom !== null) {
        $colonneNom = $nomTable === 'COMPETENCE' ? 'COMPETENCE.nom' : 'nom';
        $stmt = $pdo->prepare($baseSql . " AND $colonneNom LIKE :recherche");
        $stmt->execute(['recherche' => '%' . $rechercheNom . '%']);
    } else {
        $stmt = $pdo->query($baseSql);
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
