<?php
include_once __DIR__ . '/../includes/protect.php';
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['status']) || (string)$_SESSION['status'] !== '1') {
    http_response_code(403);
    die('<p style="font-family:sans-serif;color:red;padding:2rem">Acc&egrave;s refus&eacute;.</p>');
}

$users = [];
$error = null;

try {
    $stmt  = $pdo->query("SELECT id, login, email, status, nom, prenom, avatar FROM USER WHERE status = 1 ORDER BY id ASC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = $e->getMessage();
}

include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/adminnav.php';
log_action('Administration', 'Liste administrateurs');
?>

<h1>Registre des Administrateurs</h1>
<p style="color:#aaa;font-family:'Texturina',serif;font-size:.95rem;margin-top:-.8rem;margin-bottom:1.2rem">Liste officielle des gardiens et ma&icirc;tres de la guilde</p>

<?php if ($error): ?>
    <div class="alert alert-danger" style="max-width:900px;width:100%"><?= htmlspecialchars($error) ?></div>
<?php elseif (empty($users)): ?>
    <p class="empty">Aucun administrateur enregistr&eacute; pour le moment.</p>
<?php else: ?>

    <div class="filters" style="max-width:900px;width:100%">
        <label>
            Recherche
            <input type="text" id="searchInput" data-status="1" data-tbody="admin-tbody" placeholder="Pseudo, email&hellip;">
        </label>
        <a href="listadmin.php" class="btn-reset">R&eacute;initialiser</a>
    </div>
    <p class="meta" id="admin-count" style="max-width:900px;width:100%;margin-bottom:.6rem">
        <?= count($users) ?> administrateur<?= count($users) > 1 ? 's' : '' ?>
    </p>

    <div class="wrap" style="max-width:900px;width:100%">
        <table class="table table-striped-columns table-bordered">
            <thead>
                <tr>
                    <th></th>
                    <th>Pseudo</th>
                    <th>Pr&eacute;nom</th>
                    <th>Nom</th>
                    <th>Email</th>
                    <th>R&ocirc;le</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="admin-tbody">
            <?php foreach ($users as $user): ?>
                <tr>
                    <?php $uAv = $user['avatar'] ? '../assets/img/avatars/' . htmlspecialchars($user['avatar']) : '../assets/img/usrplaceholder.png'; ?>
                    <td><img src="<?= $uAv ?>" alt="" style="width:32px;height:32px;border-radius:50%;object-fit:cover"></td>
                    <td><strong><?= htmlspecialchars($user['login']) ?></strong></td>
                    <td><?= htmlspecialchars($user['prenom']) ?></td>
                    <td><?= htmlspecialchars($user['nom']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td>
                        <span style="background-color:#0682ef;padding:2px 6px;border-radius:4px;color:#fff">Admin</span>
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
