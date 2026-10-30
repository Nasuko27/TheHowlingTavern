<?php
include_once __DIR__ . '/../includes/protect_admin.php';
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['status']) || (string)$_SESSION['status'] !== '1') {
    http_response_code(403);
    die('<p style="font-family:sans-serif;color:red;padding:2rem">Acc&egrave;s refus&eacute;.</p>');
}

$status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;
$users = [];
$error = null;

try {
    if ($status_filter >= 0) {
        $stmt = $pdo->prepare("SELECT id, login, email, status, nom, prenom, avatar FROM USER WHERE status = :s ORDER BY id ASC");
        $stmt->execute([':s' => $status_filter]);
    } else {
        $stmt = $pdo->query("SELECT id, login, email, status, nom, prenom, avatar FROM USER ORDER BY id ASC");
    }
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/adminnav.php';
log_action('Administration', 'Liste utilisateurs');
?>

<h1>Registre des Aventuriers</h1>
<p style="color:#aaa;font-family:'Texturina',serif;font-size:.95rem;margin-top:-.8rem;margin-bottom:1.2rem">Liste officielle des membres inscrits dans la guilde</p>

<?php if ($error): ?>
    <div class="alert alert-danger" style="max-width:1100px;width:100%"><?= htmlspecialchars($error) ?></div>
<?php elseif (empty($users) && $status_filter < 0): ?>
    <p class="empty">Aucun aventurier inscrit pour le moment.</p>
<?php else: ?>

    <div class="filters" style="max-width:1100px;width:100%">
        <label>
            Recherche
            <input type="text" id="searchInput" placeholder="Pseudo, nom, email&hellip;">
        </label>
        <form method="GET" action="listuser.php" style="display:contents">
            <label>
                Statut
                <select name="status" onchange="this.form.submit()">
                    <option value="" <?= $status_filter < 0  ? 'selected' : '' ?>>Tous</option>
                    <option value="0" <?= $status_filter === 0 ? 'selected' : '' ?>>User</option>
                    <option value="1" <?= $status_filter === 1 ? 'selected' : '' ?>>Admin</option>
                    <option value="2" <?= $status_filter === 2 ? 'selected' : '' ?>>Banni</option>
                </select>
            </label>
            <a href="listuser.php" class="btn-reset">R&eacute;initialiser</a>
        </form>
    </div>
    <p class="meta" style="max-width:1100px;width:100%;margin-bottom:.6rem">
        <?= count($users) ?> aventurier<?= count($users) > 1 ? 's' : '' ?>
        <?php if ($status_filter >= 0): ?>
            <a href="listuser.php" style="color:#e8a87c;font-size:.8rem;margin-left:.5rem">Tout afficher</a>
        <?php endif; ?>
    </p>

    <div class="wrap" style="max-width:1100px;width:100%">
        <table class="table table-striped-columns table-bordered">
            <thead>
                <tr>
                    <th></th>
                    <th>Pseudo</th>
                    <th>Pr&eacute;nom</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="userTableBody">
            <?php foreach ($users as $user): ?>
                <tr>
                    <?php $uAv = $user['avatar'] ? '../assets/img/avatars/' . htmlspecialchars($user['avatar']) : '../assets/img/usrplaceholder.png'; ?>
                    <td><img src="<?= $uAv ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover"></td>
                    <td><strong><?= htmlspecialchars($user['login']) ?></strong></td>
                    <td><?= htmlspecialchars($user['prenom']) ?></td>
                    <td><?= htmlspecialchars($user['nom']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <?php if ((string)$user['status'] === '1'): ?>
                            <span style="background-color:#0682ef;padding:2px 6px;border-radius:4px;color:#fff">Admin</span>
                        <?php elseif ((string)$user['status'] === '2'): ?>
                            <span style="background-color:#bc2626;padding:2px 6px;border-radius:4px;color:#fff">Banni</span>
                        <?php else: ?>
                            <span style="background-color:#26bc26;padding:2px 6px;border-radius:4px;color:#fff">User</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="edituser.php?id=<?= (int)$user['id'] ?>" class="btn btn-sm btn-outline-warning">Configurer</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<?php endif; ?>

<script src="../assets/js/recherche.js"></script>

</body>
</html>
