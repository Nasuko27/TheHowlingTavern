<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
include_once 'protect_verified.php';
include_once 'logger.php';

$isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: tables.php'); exit(); }

$stmt = $pdo->prepare("SELECT t.id, t.nom, t.code_invite, t.id_maitrejeu, t.id_systeme, u.login AS mj_login FROM TABLEDEJEU t JOIN USER u ON u.id = t.id_maitrejeu WHERE t.id = :id");
$stmt->execute([':id' => $id]);
$table = $stmt->fetch();
if (!$table) { header('Location: tables.php'); exit(); }

$stmt = $pdo->prepare("SELECT 1 FROM TABLE_MEMBRE WHERE id_table = :tid AND id_user = :uid");
$stmt->execute([':tid' => $id, ':uid' => $_SESSION['id']]);
$isMember = (bool)$stmt->fetch();
if (!$isMember && !$isAdmin) { header('Location: tables.php'); exit(); }

$isMJ = (int)$table['id_maitrejeu'] === (int)$_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_table'])) {
    if (!$isMJ) {
        $pdo->prepare("DELETE FROM TABLE_MEMBRE WHERE id_table = :tid AND id_user = :uid")
            ->execute([':tid' => $id, ':uid' => $_SESSION['id']]);
        log_action('Tables', 'Quitte #' . $id);
    }
    header('Location: tables.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kick_member']) && ($isMJ || $isAdmin)) {
    $kickId = (int)($_POST['kick_id'] ?? 0);
    if ($kickId && $kickId !== (int)$table['id_maitrejeu']) {
        $pdo->prepare("DELETE FROM TABLE_MEMBRE WHERE id_table = :tid AND id_user = :uid")
            ->execute([':tid' => $id, ':uid' => $kickId]);
        log_action('Tables', 'Kick #' . $kickId . ' de table #' . $id);
    }
    header('Location: tablejeu.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unchoose_fiche'])) {
    $pdo->prepare("DELETE FROM joueur WHERE id_table = :tid AND id_fdp IN (SELECT id FROM FDP WHERE id_user = :uid)")
        ->execute([':tid' => $id, ':uid' => $_SESSION['id']]);
    header('Location: tablejeu.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['choose_fiche'])) {
    $fdpId = (int)($_POST['id_fdp'] ?? 0);
    if ($fdpId) {
        $own = $pdo->prepare("SELECT 1 FROM FDP WHERE id = :fdp AND id_user = :uid");
        $own->execute([':fdp' => $fdpId, ':uid' => $_SESSION['id']]);
        if ($own->fetch()) {
            $already = $pdo->prepare("SELECT 1 FROM joueur WHERE id_fdp = :fdp AND id_table = :tid");
            $already->execute([':fdp' => $fdpId, ':tid' => $id]);
            if (!$already->fetch()) {
                $pdo->prepare("DELETE FROM joueur WHERE id_table = :tid AND id_fdp IN (SELECT id FROM FDP WHERE id_user = :uid)")
                    ->execute([':tid' => $id, ':uid' => $_SESSION['id']]);
                $pdo->prepare("INSERT INTO joueur (id_fdp, id_table) VALUES (:fdp, :tid)")
                    ->execute([':fdp' => $fdpId, ':tid' => $id]);
            }
        }
    }
    header('Location: tablejeu.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_arme']) && $isMJ) {
    $fdpId = (int)($_POST['id_fdp'] ?? 0);
    $armeId = (int)($_POST['id_arme'] ?? 0);
    if ($fdpId && $armeId) {
        $pdo->prepare("INSERT INTO inv_arme (id_fdp, id_arme, quantite) VALUES (:fdp, :arme, 1)")
            ->execute([':fdp' => $fdpId, ':arme' => $armeId]);
    }
    header('Location: tablejeu.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_arme']) && $isMJ) {
    $fdpId = (int)($_POST['id_fdp'] ?? 0);
    $armeId = (int)($_POST['id_arme'] ?? 0);
    if ($fdpId && $armeId) {
        $pdo->prepare("DELETE FROM inv_arme WHERE id_fdp = :fdp AND id_arme = :arme LIMIT 1")
            ->execute([':fdp' => $fdpId, ':arme' => $armeId]);
    }
    header('Location: tablejeu.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_armure']) && $isMJ) {
    $fdpId = (int)($_POST['id_fdp'] ?? 0);
    $armureId = (int)($_POST['id_armure'] ?? 0);
    if ($fdpId && $armureId) {
        $pdo->prepare("INSERT INTO inv_armure (id_fdp, id_armure, quantite) VALUES (:fdp, :armure, 1)")
            ->execute([':fdp' => $fdpId, ':armure' => $armureId]);
    }
    header('Location: tablejeu.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_armure']) && $isMJ) {
    $fdpId = (int)($_POST['id_fdp'] ?? 0);
    $armureId = (int)($_POST['id_armure'] ?? 0);
    if ($fdpId && $armureId) {
        $pdo->prepare("DELETE FROM inv_armure WHERE id_fdp = :fdp AND id_armure = :armure LIMIT 1")
            ->execute([':fdp' => $fdpId, ':armure' => $armureId]);
    }
    header('Location: tablejeu.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_fiche'])) {
    $fdpId = (int)($_POST['id_fdp'] ?? 0);
    if ($fdpId) {
        $stmt = $pdo->prepare("SELECT id_user FROM FDP WHERE id = :id");
        $stmt->execute([':id' => $fdpId]);
        $fdpOwner = $stmt->fetch();
        if ($fdpOwner && ((int)$fdpOwner['id_user'] === (int)$_SESSION['id'] || $isMJ || $isAdmin)) {
            $pdo->prepare("DELETE FROM inv_arme WHERE id_fdp = :id")->execute([':id' => $fdpId]);
            $pdo->prepare("DELETE FROM inv_armure WHERE id_fdp = :id")->execute([':id' => $fdpId]);
            $pdo->prepare("DELETE FROM stats WHERE id_fdp = :id")->execute([':id' => $fdpId]);
            $pdo->prepare("DELETE FROM joueur WHERE id_fdp = :id")->execute([':id' => $fdpId]);
            $pdo->prepare("DELETE FROM FDP WHERE id = :id")->execute([':id' => $fdpId]);
            log_action('Tables', 'Suppression fiche #' . $fdpId);
        }
    }
    header('Location: tablejeu.php?id=' . $id);
    exit();
}

$stmt = $pdo->prepare(
    "SELECT u.id, u.login, u.avatar, tm.role
     FROM TABLE_MEMBRE tm
     JOIN USER u ON u.id = tm.id_user
     WHERE tm.id_table = :tid
     ORDER BY tm.role DESC, tm.date_rejoint ASC"
);
$stmt->execute([':tid' => $id]);
$members = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT c.id, c.contenu, c.date_envoi, u.id AS uid, u.login, u.avatar
     FROM TABLE_CHAT c
     JOIN USER u ON u.id = c.id_user
     WHERE c.id_table = :tid
     ORDER BY c.date_envoi DESC
     LIMIT 50"
);
$stmt->execute([':tid' => $id]);
$messages = array_reverse($stmt->fetchAll());

$lastMsgId = $messages ? (int)$messages[count($messages) - 1]['id'] : 0;

$stmt = $pdo->prepare(
    "SELECT f.id, f.nom AS perso_nom, f.hp, f.niveau, f.initiative, f.dedevie, f.id_race,
            c.nom AS classe_nom, c.dedevie AS classe_dv, c.caracmagie,
            r.nom AS race_nom
     FROM joueur j
     JOIN FDP f ON f.id = j.id_fdp AND f.id_user = :uid
     JOIN TABLE_MEMBRE tm ON tm.id_table = j.id_table AND tm.id_user = f.id_user
     LEFT JOIN CLASSE c ON c.id = f.id_classe
     LEFT JOIN RACE r ON r.id = f.id_race
     WHERE j.id_table = :tid
     LIMIT 1"
);
$stmt->execute([':tid' => $id, ':uid' => $_SESSION['id']]);
$perso = $stmt->fetch();

$persoStats = [];
$persoMods = [];
if ($perso) {
    $stmt = $pdo->prepare(
        "SELECT ca.id AS id_caracteristique, ca.sigle, s.valeur, COALESCE(br.valeur, 0) AS bonus
         FROM stats s
         JOIN CARACTERISTIQUE ca ON ca.id = s.id_caracteristique
         LEFT JOIN bonus_racial br ON br.id_race = :rid AND br.id_caracteristique = s.id_caracteristique
         WHERE s.id_fdp = :fdp
         ORDER BY ca.id"
    );
    $stmt->execute([':fdp' => $perso['id'], ':rid' => $perso['id_race'] ?? 0]);
    $persoStats = $stmt->fetchAll();
    foreach ($persoStats as $s) {
        $persoMods[$s['sigle']] = floor(((int)$s['valeur'] + (int)$s['bonus']) / 2 - 5);
    }
    $stArmes = $pdo->prepare(
        "SELECT ARME.* FROM ARME INNER JOIN inv_arme ON ARME.id = inv_arme.id_arme WHERE id_fdp = :fdp"
    );
    $stArmes->execute([':fdp' => $perso['id']]);
    $perso['armes'] = $stArmes->fetchAll();
    $stArmures = $pdo->prepare(
        "SELECT ARMURE.* FROM ARMURE INNER JOIN inv_armure ON ARMURE.id = inv_armure.id_armure WHERE id_fdp = :fdp"
    );
    $stArmures->execute([':fdp' => $perso['id']]);
    $perso['armures'] = $stArmures->fetchAll();
    $bonusArmure = 0;
    foreach ($perso['armures'] as $arm) { $bonusArmure += (int)$arm['defense']; }
    $perso['defense'] = 10 + ($persoMods['DEX'] ?? 0) + $bonusArmure;
}

$allPersos = [];
if ($isMJ || $isAdmin) {
    $stmt = $pdo->prepare(
        "SELECT f.id, f.nom AS perso_nom, f.hp, f.niveau, f.id_race, f.initiative,
                c.nom AS classe_nom, r.nom AS race_nom, u.login
         FROM joueur j
         JOIN FDP f ON f.id = j.id_fdp
         JOIN USER u ON u.id = f.id_user
         JOIN TABLE_MEMBRE tm ON tm.id_table = j.id_table AND tm.id_user = f.id_user
         LEFT JOIN CLASSE c ON c.id = f.id_classe
         LEFT JOIN RACE r ON r.id = f.id_race
         WHERE j.id_table = :tid"
    );
    $stmt->execute([':tid' => $id]);
    $allPersos = $stmt->fetchAll();
    foreach ($allPersos as &$ap) {
        $stAll = $pdo->prepare(
            "SELECT ca.id AS id_caracteristique, ca.sigle, s.valeur, COALESCE(br.valeur, 0) AS bonus
             FROM stats s
             JOIN CARACTERISTIQUE ca ON ca.id = s.id_caracteristique
             LEFT JOIN bonus_racial br ON br.id_race = :rid AND br.id_caracteristique = s.id_caracteristique
             WHERE s.id_fdp = :fdp ORDER BY ca.id"
        );
        $stAll->execute([':fdp' => $ap['id'], ':rid' => $ap['id_race'] ?? 0]);
        $ap['stats'] = $stAll->fetchAll();
        $ap['mods'] = [];
        foreach ($ap['stats'] as $s) {
            $ap['mods'][$s['sigle']] = floor(((int)$s['valeur'] + (int)$s['bonus']) / 2 - 5);
        }
        $ap['contact'] = $ap['mods']['FOR'] ?? 0;
        $ap['distance'] = $ap['mods']['DEX'] ?? 0;
        $stArmes = $pdo->prepare(
            "SELECT ARME.* FROM ARME INNER JOIN inv_arme ON ARME.id = inv_arme.id_arme WHERE id_fdp = :fdp"
        );
        $stArmes->execute([':fdp' => $ap['id']]);
        $ap['armes'] = $stArmes->fetchAll();
        $stArmures = $pdo->prepare(
            "SELECT ARMURE.* FROM ARMURE INNER JOIN inv_armure ON ARMURE.id = inv_armure.id_armure WHERE id_fdp = :fdp"
        );
        $stArmures->execute([':fdp' => $ap['id']]);
        $ap['armures'] = $stArmures->fetchAll();
        $bonusArmure = 0;
        foreach ($ap['armures'] as $arm) { $bonusArmure += (int)$arm['defense']; }
        $ap['defense'] = 10 + ($ap['mods']['DEX'] ?? 0) + $bonusArmure;
    }
    unset($ap);
}

$allArmes = [];
$allArmures = [];
if ($isMJ && $table['id_systeme']) {
    $allArmes = $pdo->prepare("SELECT id, nom FROM ARME WHERE id_systeme = :sid ORDER BY nom");
    $allArmes->execute([':sid' => $table['id_systeme']]);
    $allArmes = $allArmes->fetchAll();
    $allArmures = $pdo->prepare("SELECT id, nom FROM ARMURE WHERE id_systeme = :sid ORDER BY nom");
    $allArmures->execute([':sid' => $table['id_systeme']]);
    $allArmures = $allArmures->fetchAll();
}

function time_ago_table($dt) {
    $past = new DateTime($dt);
    $now  = new DateTime();
    $diff = $now->getTimestamp() - $past->getTimestamp();
    if ($diff < 60)     return 'a l\'instant';
    if ($diff < 3600)   return floor($diff / 60) . ' min';
    if ($diff < 86400)  return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'j';
    return date('d/m/Y', $past->getTimestamp());
}

log_action('Tables', 'Vue #' . $id);
?>
<?php include 'header.php'; ?>
<body>
<?php include 'nav.php'; ?>

<div class="table-container">

    <div class="forum-header">
        <div>
            <h1 style="margin-bottom:.2rem"><?= htmlspecialchars($table['nom']) ?></h1>
            <div style="font-size:.85rem;color:#888">
                MJ : <strong style="color:#e8a87c"><?= htmlspecialchars($table['mj_login']) ?></strong>
                &middot; <?= count($members) ?> membre<?= count($members) > 1 ? 's' : '' ?>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if ($isMJ && $table['code_invite']): ?>
                <span class="invite-code"><?= htmlspecialchars($table['code_invite']) ?></span>
                <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($table['code_invite']) ?>')" class="btn-forum-new" style="font-size:.7rem;padding:.2rem .5rem">Copier</button>
            <?php endif; ?>
            <?php if (!$isMJ): ?>
            <form method="POST" onsubmit="return confirm('Quitter cette table ?')">
                <input type="hidden" name="leave_table" value="1">
                <button type="submit" class="btn-delete">Quitter</button>
            </form>
            <?php endif; ?>
            <a href="tables.php" class="btn-forum-new" style="font-size:.8rem;padding:.3rem .8rem;background:#333;border-color:#555">&larr; Retour</a>
        </div>
    </div>

    <div class="row g-3">

        <div class="col-md-3">
            <div class="form-card">
                <h2 style="font-size:1.1rem">Membres</h2>
                <?php foreach ($members as $m): ?>
                <?php $mAvSrc = $m['avatar'] ? 'img/avatars/' . htmlspecialchars($m['avatar']) : 'img/usrplaceholder.png'; ?>
                <div class="member-card">
                    <a href="profile.php?id=<?= (int)$m['id'] ?>">
                        <img src="<?= $mAvSrc ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover">
                    </a>
                    <div style="flex:1">
                        <a href="profile.php?id=<?= (int)$m['id'] ?>" style="color:#e8a87c;text-decoration:none;font-size:.85rem"><?= htmlspecialchars($m['login']) ?></a>
                        <?php if ($m['role'] === 'mj'): ?>
                            <span style="font-size:.6rem;background:#620D14;color:#fff;padding:1px 5px;border-radius:3px;margin-left:.3rem">MJ</span>
                        <?php endif; ?>
                    </div>
                    <?php if (($isMJ || $isAdmin) && $m['role'] !== 'mj'): ?>
                    <form method="POST" onsubmit="return confirm('Exclure <?= htmlspecialchars($m['login']) ?> ?')">
                        <input type="hidden" name="kick_member" value="1">
                        <input type="hidden" name="kick_id" value="<?= (int)$m['id'] ?>">
                        <button type="submit" class="btn-delete" style="font-size:.6rem;padding:.1rem .3rem">X</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-md-6">
            <?php
            $fichesToShow = [];
            if ($isMJ) {
                $fichesToShow = $allPersos;
            } elseif ($perso) {
                $perso['stats'] = $persoStats;
                $perso['mods'] = $persoMods;
                $perso['login'] = $_SESSION['login'];
                $perso['contact'] = $persoMods['FOR'] ?? 0;
                $perso['distance'] = $persoMods['DEX'] ?? 0;
                $fichesToShow = [$perso];
            }
            ?>

            <?php if (!empty($fichesToShow)): ?>
            <?php foreach ($fichesToShow as $f):
                $canEdit = $isMJ;
                $portrait = 'img/usrplaceholder.png';
                if (file_exists(__DIR__ . '/img/fiches/' . $f['id'] . '.jpg')) $portrait = 'img/fiches/' . $f['id'] . '.jpg';
                elseif (file_exists(__DIR__ . '/img/fiches/' . $f['id'] . '.png')) $portrait = 'img/fiches/' . $f['id'] . '.png';
                $fmtMod = function($v) { return ($v >= 0 ? '+' : '') . $v; };
            ?>
            <form method="POST" action="fiche_update.php" class="fiche-sheet" style="margin-bottom:1rem">
                <input type="hidden" name="save_fiche" value="1">
                <input type="hidden" name="id_fdp" value="<?= (int)$f['id'] ?>">
                <input type="hidden" name="id_table" value="<?= (int)$id ?>">
                <input type="hidden" name="initiative" value="<?= (int)($f['initiative'] ?? 0) ?>">

                <div class="fiche-top">
                    <div class="fiche-portrait">
                        <img src="<?= $portrait ?>" alt="">
                    </div>
                    <div class="fiche-identity">
                        <div style="font-size:.75rem;color:#888"><?= htmlspecialchars($f['login']) ?></div>
                        <h1 style="font-size:1.5rem"><?= htmlspecialchars($f['perso_nom'] ?? 'Sans nom') ?></h1>
                        <div class="fiche-tags">
                            <?php if ($f['race_nom'] ?? null): ?><span class="fiche-tag"><?= htmlspecialchars($f['race_nom']) ?></span><?php endif; ?>
                            <?php if ($f['classe_nom'] ?? null): ?><span class="fiche-tag fiche-tag-classe"><?= htmlspecialchars($f['classe_nom']) ?></span><?php endif; ?>
                        </div>
                        <div class="fiche-infos">
                            <span>Niv. <?php if ($canEdit): ?><input type="number" name="niveau" value="<?= (int)$f['niveau'] ?>" min="1" style="width:45px;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;text-align:center;font-size:.85rem"><?php else: ?><strong><?= (int)$f['niveau'] ?></strong><?php endif; ?></span>
                            <span>PV <?php if ($canEdit): ?><input type="number" name="hp" value="<?= (int)$f['hp'] ?>" style="width:50px;background:#1a0a05;border:1px solid #620D14;color:#c0392b;border-radius:4px;text-align:center;font-size:.85rem"><?php else: ?><strong style="color:#c0392b"><?= (int)$f['hp'] ?></strong><?php endif; ?></span>
                            <span>DEF <strong style="color:#6ca0dc"><?= (int)$f['defense'] ?></strong></span>
                        </div>
                    </div>
                </div>

                <div class="fiche-grid">
                    <div class="fiche-block">
                        <h2>Caract&eacute;ristiques</h2>
                        <table class="fiche-stats-table">
                            <thead><tr><th>Carac.</th><th>Val.</th><th>Bonus</th><th>Mod.</th></tr></thead>
                            <tbody>
                            <?php foreach ($f['stats'] as $st):
                                $mod = $f['mods'][$st['sigle']] ?? 0;
                            ?>
                            <tr>
                                <td class="fiche-carac-sigle"><?= htmlspecialchars($st['sigle']) ?></td>
                                <td><?php if ($canEdit): ?><input type="number" name="carac_<?= (int)$st['id_caracteristique'] ?>" value="<?= (int)$st['valeur'] ?>" min="1" max="30" style="width:45px;background:#0e0301;border:1px solid #3d0d0d;color:#fff;border-radius:4px;text-align:center;font-size:.85rem"><?php else: ?><?= (int)$st['valeur'] ?><?php endif; ?></td>
                                <td style="color:<?= (int)$st['bonus'] > 0 ? '#26bc26' : ((int)$st['bonus'] < 0 ? '#c0392b' : '#666') ?>"><?= (int)$st['bonus'] !== 0 ? ((int)$st['bonus'] > 0 ? '+' : '') . (int)$st['bonus'] : '0' ?></td>
                                <td class="fiche-mod"><?= $fmtMod($mod) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="fiche-block">
                        <h2>Combat</h2>
                        <div class="fiche-combat-grid">
                            <div class="fiche-combat-item"><span class="fiche-combat-label">PV</span><span class="fiche-combat-value" style="color:#c0392b;font-size:1.4rem"><?= (int)$f['hp'] ?></span></div>
                            <div class="fiche-combat-item"><span class="fiche-combat-label">D&eacute;fense</span><span class="fiche-combat-value" style="color:#6ca0dc"><?= (int)$f['defense'] ?></span></div>
                            <div class="fiche-combat-item"><span class="fiche-combat-label">Initiative</span><span class="fiche-combat-value"><?= (int)($f['initiative'] ?? 0) ?></span></div>
                            <div class="fiche-combat-item"><span class="fiche-combat-label">Contact</span><span class="fiche-combat-value"><?= $fmtMod($f['contact']) ?></span></div>
                            <div class="fiche-combat-item"><span class="fiche-combat-label">Distance</span><span class="fiche-combat-value"><?= $fmtMod($f['distance']) ?></span></div>
                            <div class="fiche-combat-item"><span class="fiche-combat-label">Magique</span><span class="fiche-combat-value"><?= $fmtMod($f['magique'] ?? 0) ?></span></div>
                        </div>
                    </div>
                </div>

                <div class="fiche-grid" style="margin-top:.8rem">
                    <div class="fiche-block">
                        <h2>Armes</h2>
                        <?php foreach ($f['armes'] as $arme): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:.85rem;padding:.3rem 0;border-bottom:1px solid rgba(98,13,20,.2)">
                            <span style="color:#e8a87c"><?= htmlspecialchars($arme['nom']) ?></span>
                            <div style="display:flex;align-items:center;gap:.4rem">
                                <span style="color:#fff"><?= htmlspecialchars($arme['degat']) ?></span>
                                <?php if ($canEdit): ?>
                                </form>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="remove_arme" value="1">
                                    <input type="hidden" name="id_fdp" value="<?= (int)$f['id'] ?>">
                                    <input type="hidden" name="id_arme" value="<?= (int)$arme['id'] ?>">
                                    <button type="submit" class="btn-delete" style="font-size:.5rem;padding:.05rem .2rem">X</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($f['armes'])): ?><p style="color:#666;font-size:.8rem;margin:0">Aucune</p><?php endif; ?>
                        <?php if ($canEdit && !empty($allArmes)): ?>
                        </form>
                        <form method="POST" class="d-flex gap-1 mt-2" style="align-items:center">
                            <input type="hidden" name="add_arme" value="1">
                            <input type="hidden" name="id_fdp" value="<?= (int)$f['id'] ?>">
                            <select name="id_arme" style="flex:1;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.2rem;font-size:.75rem">
                                <?php foreach ($allArmes as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-size:.7rem;padding:.15rem .4rem">+</button>
                        </form>
                        <?php endif; ?>
                    </div>
                    <div class="fiche-block">
                        <h2>Armures</h2>
                        <?php foreach ($f['armures'] as $armure): ?>
                        <div style="display:flex;justify-content:space-between;align-items:center;font-size:.85rem;padding:.3rem 0;border-bottom:1px solid rgba(98,13,20,.2)">
                            <span style="color:#e8a87c"><?= htmlspecialchars($armure['nom']) ?></span>
                            <div style="display:flex;align-items:center;gap:.4rem">
                                <span style="color:#6ca0dc">+<?= (int)$armure['defense'] ?> DEF</span>
                                <?php if ($canEdit): ?>
                                <form method="POST" style="display:inline">
                                    <input type="hidden" name="remove_armure" value="1">
                                    <input type="hidden" name="id_fdp" value="<?= (int)$f['id'] ?>">
                                    <input type="hidden" name="id_armure" value="<?= (int)$armure['id'] ?>">
                                    <button type="submit" class="btn-delete" style="font-size:.5rem;padding:.05rem .2rem">X</button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($f['armures'])): ?><p style="color:#666;font-size:.8rem;margin:0">Aucune</p><?php endif; ?>
                        <?php if ($canEdit && !empty($allArmures)): ?>
                        <form method="POST" class="d-flex gap-1 mt-2" style="align-items:center">
                            <input type="hidden" name="add_armure" value="1">
                            <input type="hidden" name="id_fdp" value="<?= (int)$f['id'] ?>">
                            <select name="id_armure" style="flex:1;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.2rem;font-size:.75rem">
                                <?php foreach ($allArmures as $a): ?>
                                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-size:.7rem;padding:.15rem .4rem">+</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:.8rem">
                    <?php if (!$isMJ): ?>
                    <button type="button" class="btn btn-sm" style="background:#e67e22;color:#fff;border:none;font-family:'New Rocker',system-ui;font-size:.8rem" onclick="if(confirm('Changer de fiche ?')){var f=document.createElement('form');f.method='POST';f.innerHTML='<input type=hidden name=unchoose_fiche value=1>';document.body.appendChild(f);f.submit();}">Changer de fiche</button>
                    <?php else: ?>
                    <div></div>
                    <?php endif; ?>
                    <?php if ($canEdit): ?>
                    <button type="submit" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-family:'New Rocker',system-ui;font-size:.85rem;padding:.4rem 1.2rem">Sauvegarder</button>
                    <?php endif; ?>
                </div>
            </form>
            <?php endforeach; ?>

            <?php else: ?>
            <?php
            $myFiches = $pdo->prepare(
                "SELECT f.id, f.nom, c.nom AS classe_nom, r.nom AS race_nom
                 FROM FDP f
                 LEFT JOIN CLASSE c ON c.id = f.id_classe
                 LEFT JOIN RACE r ON r.id = f.id_race
                 WHERE f.id_user = :uid ORDER BY f.id DESC"
            );
            $myFiches->execute([':uid' => $_SESSION['id']]);
            $myFiches = $myFiches->fetchAll();
            ?>
            <div class="form-card" style="text-align:center;padding:2rem">
                <?php if (!empty($myFiches)): ?>
                <h2 style="font-size:1.1rem">Choisir ta fiche pour cette table</h2>
                <?php foreach ($myFiches as $mf): ?>
                <form method="POST" style="display:inline-block;margin:.3rem">
                    <input type="hidden" name="choose_fiche" value="1">
                    <input type="hidden" name="id_fdp" value="<?= (int)$mf['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background:#620D14;color:#fff;border:1px solid #8b1a1a;font-family:'New Rocker',system-ui">
                        <?= htmlspecialchars($mf['nom'] ?? 'Sans nom') ?>
                        <?php if ($mf['race_nom']): ?><span style="font-size:.7rem;color:#ccc"> - <?= htmlspecialchars($mf['race_nom']) ?></span><?php endif; ?>
                        <?php if ($mf['classe_nom']): ?><span style="font-size:.7rem;color:#e8a87c"> <?= htmlspecialchars($mf['classe_nom']) ?></span><?php endif; ?>
                    </button>
                </form>
                <?php endforeach; ?>
                <div style="margin-top:1rem">
                    <a href="systemeFiche.php" class="back-link">Ou cr&eacute;er une nouvelle fiche</a>
                </div>
                <?php else: ?>
                <p style="color:#888;font-size:.9rem">Tu n'as aucune fiche. Cr&eacute;e-en une d'abord !</p>
                <a href="systemeFiche.php" class="btn-forum-new">Cr&eacute;er ma fiche</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="col-md-3">
            <div class="form-card" style="padding:0;display:flex;flex-direction:column">
                <div id="chat-box" style="height:500px;overflow-y:auto;padding:1rem;display:flex;flex-direction:column;gap:.4rem">
                    <?php foreach ($messages as $m): ?>
                    <?php $cAvSrc = $m['avatar'] ? 'img/avatars/' . htmlspecialchars($m['avatar']) : 'img/usrplaceholder.png'; ?>
                    <div class="comment-card" style="margin-bottom:0" data-msg-id="<?= (int)$m['id'] ?>">
                        <div class="comment-author">
                            <img src="<?= $cAvSrc ?>" alt="" style="width:18px;height:18px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:.2rem">
                            <a href="profile.php?id=<?= (int)$m['uid'] ?>" style="color:#e8a87c;text-decoration:none"><?= htmlspecialchars($m['login']) ?></a>
                            <span style="color:#666;font-weight:normal"> &middot; <?= time_ago_table($m['date_envoi']) ?></span>
                        </div>
                        <div class="comment-text"><?= htmlspecialchars($m['contenu']) ?></div>
                        <?php if ($isMJ || $isAdmin || (int)$m['uid'] === (int)$_SESSION['id']): ?>
                        <button onclick="deleteMsg(<?= (int)$m['id'] ?>, this)" class="btn-delete" style="font-size:.55rem;padding:.05rem .3rem;margin-top:.2rem">suppr.</button>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="padding:.8rem;border-top:1px solid #620D14">
                    <form id="chat-form" class="d-flex gap-2">
                        <input type="text" id="chat-input" placeholder="Message..." autocomplete="off"
                               style="flex:1;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.5rem .8rem;font-family:'Texturina',serif;font-size:.85rem">
                        <button type="submit" class="btn-forum-new" style="font-size:.8rem;padding:.4rem .6rem">Envoyer</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>

<script>
var tableId = <?= (int)$id ?>;
var chatBox = document.getElementById('chat-box');
var chatForm = document.getElementById('chat-form');
var chatInput = document.getElementById('chat-input');
var lastMsgId = <?= $lastMsgId ?>;

chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    var msg = chatInput.value.trim();
    if (!msg) return;
    chatInput.value = '';
    fetch('table_send.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_table=' + tableId + '&contenu=' + encodeURIComponent(msg)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) { if (data.ok) pollChat(); });
});

function pollChat() {
    var visible = [];
    chatBox.querySelectorAll('[data-msg-id]').forEach(function(el) { visible.push(el.dataset.msgId); });
    fetch('table_poll.php?id_table=' + tableId + '&after=' + lastMsgId + '&visible=' + visible.join(','))
        .then(function(r) {
            if (r.status === 401) { window.location = 'login.php'; return; }
            return r.json();
        })
        .then(function(data) {
            if (!data) return;
            if (data.deleted) {
                data.deleted.forEach(function(id) {
                    var el = chatBox.querySelector('[data-msg-id="' + id + '"]');
                    if (el) el.remove();
                });
            }
            if (!data.messages) return;
            data.messages.forEach(function(m) {
                var div = document.createElement('div');
                div.className = 'comment-card';
                div.style.marginBottom = '0';
                div.dataset.msgId = m.id;
                var delBtn = (canModerate || m.uid === myUid)
                    ? '<button onclick="deleteMsg(' + m.id + ', this)" class="btn-delete" style="font-size:.55rem;padding:.05rem .3rem;margin-top:.2rem">suppr.</button>'
                    : '';
                div.innerHTML =
                    '<div class="comment-author">' +
                        '<img src="' + m.avatar + '" alt="" style="width:18px;height:18px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:.2rem">' +
                        '<a href="profile.php?id=' + m.uid + '" style="color:#e8a87c;text-decoration:none">' + m.login + '</a>' +
                        '<span style="color:#666;font-weight:normal"> &middot; ' + m.time_ago + '</span>' +
                    '</div>' +
                    '<div class="comment-text">' + m.contenu + '</div>' + delBtn;
                chatBox.appendChild(div);
                lastMsgId = m.id;
            });
            if (data.messages.length) chatBox.scrollTop = chatBox.scrollHeight;
        });
}

var canModerate = <?= ($isMJ || $isAdmin) ? 'true' : 'false' ?>;
var myUid = <?= (int)$_SESSION['id'] ?>;

function deleteMsg(msgId, btn) {
    if (!confirm('Supprimer ce message ?')) return;
    fetch('table_delete_msg.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id_msg=' + msgId
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.ok) btn.closest('.comment-card').remove();
    });
}

setInterval(pollChat, 3000);
chatBox.scrollTop = chatBox.scrollHeight;

setInterval(function() {
    var active = document.activeElement;
    if (active && (active.tagName === 'INPUT' || active.tagName === 'TEXTAREA')) return;
    window.location.reload();
}, 15000);
</script>

<?php 
include "footer.php"; ?>
