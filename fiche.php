<?php
ob_start();
include_once 'protect_verified.php';
include_once 'logger.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: mes_fiches.php'); exit(); }

$isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';

$stmt = $pdo->prepare(
    "SELECT f.*,
            c.nom AS classe_nom, c.dedevie AS classe_dv, c.caracmagie,
            r.nom AS race_nom,
            u.login AS joueur_login
     FROM FDP f
     JOIN USER u ON u.id = f.id_user
     LEFT JOIN CLASSE c ON c.id = f.id_classe
     LEFT JOIN RACE r ON r.id = f.id_race
     WHERE f.id = :id"
);
$stmt->execute([':id' => $id]);
$fiche = $stmt->fetch();
if (!$fiche) { header('Location: mes_fiches.php'); exit(); }

$isOwner = (int)$fiche['id_user'] === (int)$_SESSION['id'];
if (!$isOwner && !$isAdmin) { header('Location: mes_fiches.php'); exit(); }

$stmt = $pdo->prepare(
    "SELECT ca.id AS id_caracteristique, ca.sigle, s.valeur, COALESCE(br.valeur, 0) AS bonus
     FROM stats s
     JOIN CARACTERISTIQUE ca ON ca.id = s.id_caracteristique
     LEFT JOIN FDP f ON f.id = s.id_fdp
     LEFT JOIN bonus_racial br ON br.id_race = f.id_race AND br.id_caracteristique = s.id_caracteristique
     WHERE s.id_fdp = :fdp
     ORDER BY ca.id"
);
$stmt->execute([':fdp' => $id]);
$stats = $stmt->fetchAll();

$mods = [];
foreach ($stats as $s) {
    $total = (int)$s['valeur'] + (int)$s['bonus'];
    $mod = floor($total / 2 - 5);
    $mods[$s['sigle']] = $mod;
}

$contactMod = $mods['FOR'] ?? 0;
$distanceMod = $mods['DEX'] ?? 0;
$magiqueMod = 0;
if ($fiche['caracmagie'] && isset($mods[$fiche['caracmagie']])) {
    $magiqueMod = $mods[$fiche['caracmagie']];
}

$stArmes = $pdo->prepare("SELECT ARME.* FROM ARME INNER JOIN inv_arme ON ARME.id = inv_arme.id_arme WHERE id_fdp = :fdp");
$stArmes->execute([':fdp' => $id]);
$armes = $stArmes->fetchAll();

$stArmures = $pdo->prepare("SELECT ARMURE.* FROM ARMURE INNER JOIN inv_armure ON ARMURE.id = inv_armure.id_armure WHERE id_fdp = :fdp");
$stArmures->execute([':fdp' => $id]);
$armures = $stArmures->fetchAll();

$bonusArmure = 0;
foreach ($armures as $arm) { $bonusArmure += (int)$arm['defense']; }
$fiche['defense'] = 10 + ($mods['DEX'] ?? 0) + $bonusArmure;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_portrait']) && $isOwner) {
    if (isset($_FILES['portrait']) && $_FILES['portrait']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];
        $mime = mime_content_type($_FILES['portrait']['tmp_name']);
        if (isset($allowed[$mime]) && $_FILES['portrait']['size'] <= 2 * 1024 * 1024) {
            $dir = __DIR__ . '/img/fiches';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ext = $allowed[$mime];
            foreach (['.jpg', '.png'] as $old) {
                if (file_exists($dir . '/' . $id . $old)) unlink($dir . '/' . $id . $old);
            }
            move_uploaded_file($_FILES['portrait']['tmp_name'], $dir . '/' . $id . $ext);
            log_action('Fiche', 'Portrait #' . $id);
        }
    }
    header('Location: fiche.php?id=' . $id);
    exit();
}

log_action('Fiche', 'Vue #' . $id);

include 'header.php';
include 'nav.php';
?>

<div class="forum-container" style="max-width:1000px">

    <?php $editMode = isset($_GET['edit']) && $isOwner; ?>
    <?php
    $flash = null;
    if (isset($_SESSION['fiche_flash'])) {
        $flash = $_SESSION['fiche_flash'];
        unset($_SESSION['fiche_flash']);
    }
    ?>
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" style="font-family:'Texturina',serif"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>
    <div class="d-flex justify-content-between align-items-center" style="margin-bottom:1rem">
        <a href="mes_fiches.php" class="back-link" style="margin-bottom:0">&larr; Mes fiches</a>
        <div class="d-flex gap-2">
            <?php if ($isOwner && !$editMode): ?>
                <a href="fiche.php?id=<?= (int)$id ?>&edit=1" class="btn btn-sm" style="background:#e67e22;color:#fff;border:none;font-family:'New Rocker',system-ui">Modifier</a>
            <?php endif; ?>
            <a href="fiche_pdf.php?id=<?= (int)$id ?>" class="btn btn-sm" style="background:#620D14;color:#fff;border:1px solid #8b1a1a;font-family:'New Rocker',system-ui">T&eacute;l&eacute;charger PDF</a>
        </div>
    </div>

    <?php if ($editMode): ?>
    <form method="POST" action="fiche_update.php" class="fiche-sheet">
        <input type="hidden" name="save_fiche" value="1">
        <input type="hidden" name="id_fdp" value="<?= (int)$id ?>">
        <input type="hidden" name="initiative" value="<?= (int)($fiche['initiative'] ?? 0) ?>">
    <?php else: ?>
    <div class="fiche-sheet">
    <?php endif; ?>

        <div class="fiche-top">
            <div class="fiche-portrait">
                <?php
                $portrait = 'img/usrplaceholder.png';
                if (file_exists(__DIR__ . '/img/fiches/' . $id . '.jpg')) $portrait = 'img/fiches/' . $id . '.jpg';
                elseif (file_exists(__DIR__ . '/img/fiches/' . $id . '.png')) $portrait = 'img/fiches/' . $id . '.png';
                ?>
                <img src="<?= $portrait ?>" alt="">
                <?php if ($isOwner && !$editMode): ?>
                <form method="POST" enctype="multipart/form-data" style="margin-top:.4rem;text-align:center">
                    <input type="hidden" name="upload_portrait" value="1">
                    <label class="btn btn-sm" style="background:#620D14;color:#fff;border:1px solid #8b1a1a;font-family:'New Rocker',system-ui;font-size:.75rem;cursor:pointer">
                        Changer l'image
                        <input type="file" name="portrait" accept="image/jpeg,image/png" style="display:none" onchange="this.form.submit()">
                    </label>
                </form>
                <?php endif; ?>
            </div>
            <div class="fiche-identity">
                <h1><?= htmlspecialchars($fiche['nom'] ?? 'Sans nom') ?></h1>
                <div class="fiche-tags">
                    <?php if ($fiche['race_nom']): ?>
                        <span class="fiche-tag"><?= htmlspecialchars($fiche['race_nom']) ?></span>
                    <?php endif; ?>
                    <?php if ($fiche['classe_nom']): ?>
                        <span class="fiche-tag fiche-tag-classe"><?= htmlspecialchars($fiche['classe_nom']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="fiche-infos">
                    <span>Niveau <?php if ($editMode): ?><input type="number" name="niveau" value="<?= (int)$fiche['niveau'] ?>" min="1" style="width:45px;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;text-align:center;font-size:.85rem"><?php else: ?><strong><?= (int)$fiche['niveau'] ?></strong><?php endif; ?></span>
                    <span>Joueur : <strong style="color:#e8a87c"><?= htmlspecialchars($fiche['joueur_login']) ?></strong></span>
                    <?php if ($fiche['classe_dv']): ?>
                        <span>D&eacute; de vie : <strong style="color:#e8a87c"><?= htmlspecialchars($fiche['classe_dv']) ?></strong></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="fiche-grid">

            <div class="fiche-block">
                <h2>Caract&eacute;ristiques</h2>
                <table class="fiche-stats-table">
                    <thead><tr><th>Carac.</th><th>Val.</th><th>Racial</th><th>Mod.</th></tr></thead>
                    <tbody>
                    <?php foreach ($stats as $s):
                        $total = (int)$s['valeur'] + (int)$s['bonus'];
                        $mod = floor($total / 2 - 5);
                        $modStr = ($mod >= 0 ? '+' : '') . $mod;
                        $bonusStr = (int)$s['bonus'] !== 0 ? ((int)$s['bonus'] > 0 ? '+' : '') . (int)$s['bonus'] : '0';
                    ?>
                    <tr>
                        <td class="fiche-carac-sigle"><?= htmlspecialchars($s['sigle']) ?></td>
                        <td><?php if ($editMode): ?><input type="number" name="carac_<?= (int)$s['id_caracteristique'] ?>" value="<?= (int)$s['valeur'] ?>" min="1" max="30" style="width:45px;background:#0e0301;border:1px solid #3d0d0d;color:#fff;border-radius:4px;text-align:center;font-size:.85rem"><?php else: ?><?= (int)$s['valeur'] ?><?php endif; ?></td>
                        <td style="color:<?= (int)$s['bonus'] > 0 ? '#26bc26' : ((int)$s['bonus'] < 0 ? '#c0392b' : '#666') ?>"><?= $bonusStr ?></td>
                        <td class="fiche-mod"><?= $modStr ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="fiche-block">
                <h2>Combat</h2>
                <div class="fiche-combat-grid">
                    <div class="fiche-combat-item">
                        <span class="fiche-combat-label">PV</span>
                        <?php if ($editMode): ?>
                        <input type="number" name="hp" value="<?= (int)$fiche['hp'] ?>" style="width:60px;background:#0e0301;border:1px solid #620D14;color:#c0392b;border-radius:4px;text-align:center;font-size:1.2rem;font-family:'New Rocker',system-ui">
                        <?php else: ?>
                        <span class="fiche-combat-value" style="color:#c0392b;font-size:1.4rem"><?= (int)$fiche['hp'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="fiche-combat-item">
                        <span class="fiche-combat-label">D&eacute;fense</span>
                        <span class="fiche-combat-value" style="color:#6ca0dc"><?= (int)$fiche['defense'] ?></span>
                    </div>
                    <div class="fiche-combat-item">
                        <span class="fiche-combat-label">Initiative</span>
                        <span class="fiche-combat-value"><?= (int)$fiche['initiative'] ?></span>
                    </div>
                    <div class="fiche-combat-item">
                        <span class="fiche-combat-label">Contact</span>
                        <span class="fiche-combat-value"><?= ($contactMod >= 0 ? '+' : '') . $contactMod ?></span>
                    </div>
                    <div class="fiche-combat-item">
                        <span class="fiche-combat-label">Distance</span>
                        <span class="fiche-combat-value"><?= ($distanceMod >= 0 ? '+' : '') . $distanceMod ?></span>
                    </div>
                    <div class="fiche-combat-item">
                        <span class="fiche-combat-label">Magique</span>
                        <span class="fiche-combat-value"><?= ($magiqueMod >= 0 ? '+' : '') . $magiqueMod ?></span>
                    </div>
                </div>
            </div>

        </div>

        <?php if (!empty($armes) || !empty($armures)): ?>
        <div class="fiche-grid" style="margin-top:1rem">
            <?php if (!empty($armes)): ?>
            <div class="fiche-block">
                <h2>Armes</h2>
                <?php foreach ($armes as $arme): ?>
                <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.3rem 0;border-bottom:1px solid rgba(98,13,20,.2)">
                    <span style="color:#e8a87c"><?= htmlspecialchars($arme['nom']) ?></span>
                    <span style="color:#fff"><?= htmlspecialchars($arme['degat']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            <?php if (!empty($armures)): ?>
            <div class="fiche-block">
                <h2>Armures</h2>
                <?php foreach ($armures as $armure): ?>
                <div style="display:flex;justify-content:space-between;font-size:.85rem;padding:.3rem 0;border-bottom:1px solid rgba(98,13,20,.2)">
                    <span style="color:#e8a87c"><?= htmlspecialchars($armure['nom']) ?></span>
                    <span style="color:#6ca0dc">+<?= (int)$armure['defense'] ?> DEF</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    <?php if ($editMode): ?>
        <div style="display:flex;justify-content:space-between;margin-top:1rem">
            <a href="fiche.php?id=<?= (int)$id ?>" class="btn btn-sm" style="background:#333;color:#fff;border:none;font-family:'New Rocker',system-ui">Annuler</a>
            <button type="submit" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-family:'New Rocker',system-ui;padding:.4rem 1.5rem">Sauvegarder</button>
        </div>
    </form>
    <?php else: ?>
    </div>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
