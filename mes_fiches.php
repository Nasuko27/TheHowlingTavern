<?php
include_once 'protect_verified.php';
include_once 'logger.php';
log_action('Mes fiches');

include 'header.php';
include 'nav.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_fiche'])) {
    $fdpId = (int)($_POST['id_fdp'] ?? 0);
    if ($fdpId) {
        $stmt = $pdo->prepare("SELECT id_user FROM FDP WHERE id = :id");
        $stmt->execute([':id' => $fdpId]);
        $owner = $stmt->fetch();
        if ($owner && (int)$owner['id_user'] === (int)$_SESSION['id']) {
            $pdo->prepare("DELETE FROM inv_arme WHERE id_fdp = :id")->execute([':id' => $fdpId]);
            $pdo->prepare("DELETE FROM inv_armure WHERE id_fdp = :id")->execute([':id' => $fdpId]);
            $pdo->prepare("DELETE FROM stats WHERE id_fdp = :id")->execute([':id' => $fdpId]);
            $pdo->prepare("DELETE FROM joueur WHERE id_fdp = :id")->execute([':id' => $fdpId]);
            $pdo->prepare("DELETE FROM FDP WHERE id = :id")->execute([':id' => $fdpId]);
            log_action('Mes fiches', 'Suppression fiche #' . $fdpId);
        }
    }
    header('Location: mes_fiches.php');
    exit();
}

$stmt = $pdo->prepare(
    "SELECT f.id, f.nom, f.hp, f.niveau,
            c.nom AS classe_nom, r.nom AS race_nom, s.nom AS systeme_nom
     FROM FDP f
     LEFT JOIN CLASSE c ON c.id = f.id_classe
     LEFT JOIN RACE r ON r.id = f.id_race
     LEFT JOIN SYSTEME s ON s.id = f.id_systeme
     WHERE f.id_user = :uid
     ORDER BY f.id DESC"
);
$stmt->execute([':uid' => $_SESSION['id']]);
$fiches = $stmt->fetchAll();
?>

<body class="container-fluid">
<div class="forum-container">

    <h1>Mes fiches</h1>

    <?php if (empty($fiches)): ?>
        <p style="color:#888;text-align:center;padding:2rem">Tu n'as aucune fiche pour le moment.</p>
        <div style="text-align:center">
            <a href="systemeFiche.php" class="btn-forum-new">Cr&eacute;er une fiche</a>
        </div>
    <?php else: ?>
        <?php foreach ($fiches as $f): ?>
        <div class="post-card">
            <div class="post-body">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="fiche.php?id=<?= (int)$f['id'] ?>" class="post-title" style="margin:0"><?= htmlspecialchars($f['nom'] ?? 'Sans nom') ?></a>
                    <form method="POST" onsubmit="return confirm('Supprimer cette fiche ?')">
                        <input type="hidden" name="delete_fiche" value="1">
                        <input type="hidden" name="id_fdp" value="<?= (int)$f['id'] ?>">
                        <button type="submit" class="btn-delete" style="font-size:.7rem">Supprimer</button>
                    </form>
                </div>
                <div class="post-meta">
                    <?php if ($f['race_nom']): ?>
                        <span style="background:#333;padding:1px 6px;border-radius:3px;color:#ccc;font-size:.75rem"><?= htmlspecialchars($f['race_nom']) ?></span>
                    <?php endif; ?>
                    <?php if ($f['classe_nom']): ?>
                        <span style="background:#620D14;padding:1px 6px;border-radius:3px;color:#fff;font-size:.75rem"><?= htmlspecialchars($f['classe_nom']) ?></span>
                    <?php endif; ?>
                    <?php if ($f['systeme_nom']): ?>
                        &middot; <?= htmlspecialchars($f['systeme_nom']) ?>
                    <?php endif; ?>
                </div>
                <div style="display:flex;gap:1.5rem;margin-top:.5rem;font-size:.85rem">
                    <div><span style="color:#888">Niv.</span> <strong style="color:#fff"><?= (int)$f['niveau'] ?></strong></div>
                    <div><span style="color:#888">PV</span> <strong style="color:#c0392b"><?= (int)$f['hp'] ?></strong></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <div style="text-align:center;margin-top:1.5rem">
            <a href="systemeFiche.php" class="btn-forum-new">Cr&eacute;er une nouvelle fiche</a>
        </div>
    <?php endif; ?>

</div>


<?php 
include "footer.php"; ?>