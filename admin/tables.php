<?php
include_once __DIR__ . '/../includes/protect_admin.php';
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/logger.php';
include_once __DIR__ . '/../includes/adminnav.php';

if (!isset($_SESSION['status']) || (string)$_SESSION['status'] !== '1') {
    http_response_code(403);
    die('Accès refusé.');
}

log_action('Administration', 'Gestion tables');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    $tid = (int)($_POST['table_id'] ?? 0);
    if ($tid) {
        $pdo->prepare("DELETE FROM joueur WHERE id_table = :id")->execute([':id' => $tid]);
        $pdo->prepare("DELETE FROM TABLE_CHAT WHERE id_table = :id")->execute([':id' => $tid]);
        $pdo->prepare("DELETE FROM TABLE_MEMBRE WHERE id_table = :id")->execute([':id' => $tid]);
        $pdo->prepare("DELETE FROM TABLEDEJEU WHERE id = :id")->execute([':id' => $tid]);
        log_action('Administration', 'Suppression table #' . $tid);
    }
    header('Location: tables.php');
    exit();
}

$tables = $pdo->query(
    "SELECT t.id, t.nom, t.code_invite, t.date_creation, u.login AS mj_login, u.id AS mj_id,
            (SELECT COUNT(*) FROM TABLE_MEMBRE WHERE id_table = t.id) AS nb_membres
     FROM TABLEDEJEU t
     JOIN USER u ON u.id = t.id_maitrejeu
     ORDER BY t.date_creation DESC"
)->fetchAll();
?>

<h1>Gestion des tables</h1>
<p class="meta"><?= count($tables) ?> table<?= count($tables) > 1 ? 's' : '' ?> au total</p>

<div class="wrap">
    <table class="table table-striped-columns table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>MJ</th>
                <th>Membres</th>
                <th>Code</th>
                <th>Cr&eacute;ation</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($tables)): ?>
            <tr><td colspan="7" class="empty">Aucune table.</td></tr>
        <?php else: ?>
            <?php foreach ($tables as $t): ?>
            <tr>
                <td><?= (int)$t['id'] ?></td>
                <td><strong><?= htmlspecialchars($t['nom']) ?></strong></td>
                <td><a href="edituser.php?id=<?= (int)$t['mj_id'] ?>" style="color:#e8a87c;text-decoration:none"><?= htmlspecialchars($t['mj_login']) ?></a></td>
                <td style="text-align:center"><?= (int)$t['nb_membres'] ?></td>
                <td><span class="invite-code"><?= htmlspecialchars($t['code_invite']) ?></span></td>
                <td style="font-size:.85rem;color:#888"><?= date('d/m/Y', strtotime($t['date_creation'])) ?></td>
                <td>
                    <a href="../tablejeu.php?id=<?= (int)$t['id'] ?>" class="btn-edit">Voir</a>
                    <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette table ?')">
                        <input type="hidden" name="delete_table" value="1">
                        <input type="hidden" name="table_id" value="<?= (int)$t['id'] ?>">
                        <button type="submit" class="btn-delete">Supprimer</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
