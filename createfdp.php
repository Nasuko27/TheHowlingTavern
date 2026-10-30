<?php

include_once 'protect_verified.php';
include_once 'logger.php';
log_action('Création FDP');

include 'header.php';
include 'nav.php';

if (!isset($_GET['systeme'])) {
    header('location: systemeFiche.php');
    die();
} else {
    $systeme = $_GET['systeme'];
}

$sql = "SELECT * FROM CARACTERISTIQUE WHERE id_systeme = :systeme ORDER BY id ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':systeme', $systeme);
$stmt->execute();
$caracteristiques = $stmt->fetchAll();

$sql = "SELECT * FROM RACE WHERE id_systeme = :systeme ORDER BY nom ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':systeme', $systeme);
$stmt->execute();
$races = $stmt->fetchAll();

$sql = "SELECT * FROM CLASSE WHERE id_systeme = :systeme ORDER BY nom ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':systeme', $systeme);
$stmt->execute();
$classes = $stmt->fetchAll();

?>
<script src="functions.js"></script>
<body class="container-fluid">
<form class="comphero d-flex flex-column align-items-center gap-5 p-4 m-4" method="post"
      action="laFinaliteDeTouteUneVie.php">
    <label for="nom" style="font-size: x-large">Nom du personnage :</label>
    <input type="text" required id="nom" name="nom">

    <div class="d-inline-flex gap-3">
        <label for="race">Sélectionner une race : </label>
        <select id="race" name="id_race" class="form-select" required>
        <?php
        foreach ($races as $race) {
            echo "<option value='" . $race['id'] . "'>" . $race['nom'] . "</option>";
        }
        ?>
        </select>
        <script>
            document.getElementById("race").addEventListener("change", function () {
                bonusRacial();
            });
        </script>
    </div>
    <div class="d-flex gap-5">
        <div class="d-flex align-items-center gap-2">
            <label for="age">Age : </label>
            <input id="age" name="age" type="number" required>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="taille">Taille : </label>
            <input id="taille" name="taille" type="number" required>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="poids">Poids : </label>
            <input id="poids" name="poids" type="number" required>
        </div>
        <div class="d-flex align-items-center gap-2">
            <label for="sexe">Sexe : </label>
            <input id="sexe" name="sexe" type="text" required>
        </div>
    </div>
    <div class="d-inline-flex gap-5">
        <select id="classe" name="id_classe" class="form-select" required>
            <?php
            foreach ($classes as $classe) {
                echo "<option value='" . $classe['id'] . "'>" . $classe['nom'] . "</option>";
            }
            ?>
        </select>
        <div class="d-flex flex-column align-items-center">
            <label for="ddv">Dé de vie : </label>
            <input type="text" id="ddv" name="ddv" readonly required>
        </div>
        <script>
            document.getElementById("classe").addEventListener("change", function () {
                classedata();
            });
        </script>
        <div class="d-flex flex-column align-items-center">
            <label for="caracmagie">Caractéristique magique : </label>
            <input type="text" id="caracmagie" readonly required>
        </div>
    </div>
    <div class="container text-center">
        <div class="row">
            <div class="col-4">
                <h2>Caracatéristiques :</h2>
                <table style="width: 100%;">
                    <thead>
                    <tr>
                        <th scope="col"></th>
                        <th scope="col">Valeur</th>
                        <th scope="col">Racial</th>
                        <th scope="col">Mod.</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($caracteristiques as $caracteristique) {
                        echo '<tr>
                        <th scope="row">' . $caracteristique['sigle'] . '.</th>
                        <td><input type="number" value="11" min="6" max="18" id="val_' . $caracteristique['sigle'] . '" name="' . $caracteristique['sigle'] . '" required></td>
                        <td><input type="number" value="0" min="-8" max="8" id="bns_' . $caracteristique['sigle'] . '" readonly></td>
                        <td><input type="number" value="0"  min="-4" max="5" class="finalCarac" id="fnl_' . $caracteristique['sigle'] . '" readonly></td>
                        <script>
                        document.getElementById("val_' . $caracteristique['sigle'] . '").addEventListener("change", function(){
                            let mod = parseInt(this.value) + parseInt(document.getElementById("bns_' . $caracteristique['sigle'] . '").value);
                            mod = (mod / 2) - 5;
                            mod = Math.floor(mod);
                            document.getElementById("fnl_' . $caracteristique['sigle'] . '").value = mod;
                            updateValues();
                        });
                        document.getElementById("bns_' . $caracteristique['sigle'] . '").addEventListener("input", function(){
                            let mod = parseInt(this.value) + parseInt(document.getElementById("val_' . $caracteristique['sigle'] . '").value);
                            mod = (mod / 2) - 5;
                            mod = Math.floor(mod);
                            document.getElementById("fnl_' . $caracteristique['sigle'] . '").value = mod;
                            updateValues();
                        });
                        </script>
                        </tr>';
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            <div class="col-4 d-flex flex-column gap-3">
                <h2>Attaque : </h2>
                <div class="row">
                    <label class="col" for="contact">Contact : </label>
                    <input class="col-6" type="number" id="contact" value="0" readonly required>
                </div>
                <div class="row">
                    <label class="col" for="distance">Distance : </label>
                    <input class="col-6" type="number" id="distance" value="0" readonly required>
                </div>
                <div class="row">
                    <label class="col" for="magique">Magique : </label>
                    <input class="col-6" type="number" id="magique" value="0" readonly required>
                </div>
                <div class="row">
                    <label class="col" for="init">Initiative : </label>
                    <input class="col-6" type="number" id="init" name="initiative" value="10" readonly required>
                </div>

            </div>
            <div class="col-4 d-flex flex-column gap-3">
                <h2>Statistiques : </h2>
                <div class="row">
                    <label class="col" for="pv">PV : </label>
                    <input class="col-6" type="number" id="pv" name="hp" placeholder="0" readonly required>
                </div>
                <br>
                <div class="row">
                    <label class="col" for="def">Défense : </label>
                    <input class="col-6" type="number" id="def" name="def" placeholder="0" readonly required>
                </div>
                <br>
                <div class="row">
                    <label class="col" for="niveau">Niveau : </label>
                    <input class="col-6" type="number" id="niveau" name="niveau" value="1" readonly required>
                </div>
            </div>
        </div>
    </div>
    <div class="container text-start">
        <div class="row d-flex gap-5">
            <div class="col">
                <h2>Armes : </h2>
                <div class="row d-flex gap-2">
                    <button type="button" id="addarme" class="btn btn-outline-secondary" onclick="addItemList('arme','1')">+
                    </button>
                </div>
            </div>
            <div class="col">
                <h2>Armures : </h2>
                <div class="row d-flex gap-2">
                    <button type="button" id="addarmure" class="btn btn-outline-secondary" onclick="addItemList('armure','1')">+
                    </button>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" readonly name="id_systeme" value="<?php echo $systeme ?>">
    <input type="hidden" readonly name="id_user" value="<?php echo $_SESSION['id'] ?>">

    <button class="btn btn-success" type="submit">Créer la fiche</button>
</form>
</body>