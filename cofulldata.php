<?php
include_once "protect.php";
include_once "config.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['status']) || (string)$_SESSION['status'] !== '1') {
    http_response_code(403);
    die('<p style="font-family:sans-serif;color:red;padding:2rem">Acc&egrave;s refus&eacute;.</p>');
}

include "header.php";
log_action('Administration', 'CO Fantasy data');

$sql = "SELECT id,nom FROM ARME;"; $stmt = $pdo->query($sql); $armes = $stmt->fetchAll();
$sql = "SELECT id,nom FROM ARMURE;"; $stmt = $pdo->query($sql); $armures = $stmt->fetchAll();
$sql = "SELECT id,nom FROM CLASSE;"; $stmt = $pdo->query($sql); $classes = $stmt->fetchAll();
$sql = "SELECT id,nom FROM CARACTERISTIQUE;"; $stmt = $pdo->query($sql); $caracteristiques = $stmt->fetchAll();
$sql = "SELECT id,nom FROM OBJET;"; $stmt = $pdo->query($sql); $objets = $stmt->fetchAll();
$sql = "SELECT id,nom FROM COMPETENCE;"; $stmt = $pdo->query($sql); $competences = $stmt->fetchAll();
$sql = "SELECT id,nom FROM RACE;"; $stmt = $pdo->query($sql); $races = $stmt->fetchAll();
$sql = "SELECT id,nom FROM RESSOURCE;"; $stmt = $pdo->query($sql); $ressources = $stmt->fetchAll();

include_once "adminnav.php";
?>

<div style="max-width:1000px;width:100%">

    <div class="forum-header">
        <h1>CO Fantasy &mdash; R&eacute;f&eacute;rentiel</h1>
        <a href="edititem.php" class="btn-forum-new">+ Nouvel &eacute;l&eacute;ment</a>
    </div>

    <div class="filters">
        <label>
            Recherche
            <input list="covalues" id="value" name="value" data-endpoint="filtre-categorie.php" placeholder="Cat&eacute;gorie ou nom&hellip;">
            <datalist id="covalues">
                <option value="CLASSE"></option>
                <option value="RACE"></option>
                <option value="OBJET"></option>
                <option value="COMPETENCE"></option>
                <option value="ARME"></option>
                <option value="RESSOURCE"></option>
                <option value="CARACTERISTIQUE"></option>
                <option value="ARMURE"></option>
            </datalist>
        </label>
        <button class="btn-reset" onclick="fetchTable()">Filtrer</button>
    </div>

    <div class="wrap">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Cat&eacute;gorie</th>
                    <th style="width:120px"></th>
                </tr>
            </thead>
            <tbody id="refTableBody">
            <?php
            $groups = [
                ['Arme', $armes, 'ARME'],
                ['Classe', $classes, 'CLASSE'],
                ['Armure', $armures, 'ARMURE'],
                ['Caract&eacute;ristique', $caracteristiques, 'CARACTERISTIQUE'],
                ['Objet', $objets, 'OBJET'],
                ['Comp&eacute;tence', $competences, 'COMPETENCE'],
                ['Race', $races, 'RACE'],
                ['Ressource', $ressources, 'RESSOURCE'],
            ];
            foreach ($groups as $g) {
                list($label, $items, $table) = $g;
                foreach ($items as $item) {
                    echo "<tr data-cat='" . htmlspecialchars($table) . "'>";
                    echo "<td>" . htmlspecialchars($item['nom']) . "</td>";
                    echo "<td><span class='action-pill' style='background:#3d0d0d;border:1px solid #620D14'>$label</span></td>";
                    echo "<td><a href='edititem.php?id=" . (int)$item['id'] . "&table=$table' class='btn-edit'>D&eacute;tails &rsaquo;</a></td>";
                    echo "</tr>";
                }
            }
            ?>
            </tbody>
        </table>
    </div>

</div>

<script src="categorie-filter.js"></script>

</body>
</html>
