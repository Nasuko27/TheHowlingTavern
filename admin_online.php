<!DOCTYPE html>
<html lang="fr">
<head>
    <title>The Howling tavern</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=New+Rocker&family=Texturina:ital,opsz,wght@0,12..72,100..900;1,12..72,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="img/logo.png">
    <link href="styles.css" rel="stylesheet">
</head>

<?php
include_once 'logger.php';
log_action('Administration', 'Utilisateurs connectés');
include_once 'protect.php';
include_once 'config.php';
include_once 'adminnav.php';

if (session_status() == PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 1) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;color:red;padding:2rem">Accès refusé.</p>');
}

$seuil = 5;
$stmt = $pdo->prepare(
    "SELECT id, login, nom, prenom, status, last_co
     FROM USER
     WHERE last_co >= NOW() - INTERVAL :seuil MINUTE
     ORDER BY last_co DESC"
);
$stmt->bindValue(':seuil', $seuil, PDO::PARAM_INT);
$stmt->execute();
$online = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<body>

<h1>Aventuriers connectés <span id="online-count" style="font-size:.9rem;color:#e8a87c">(<?= count($online) ?>)</span></h1>

<?php if (empty($online)): ?>
    <p class="meta" id="online-empty">Aucun aventurier connecté en ce moment.</p>
<?php else: ?>
    <p class="meta" id="online-empty" style="display:none">Aucun aventurier connecté en ce moment.</p>
<?php endif; ?>

<div class="wrap">
    <table class="table table-striped-columns table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Pseudo</th>
                <th>Prénom Nom</th>
                <th>Rôle</th>
                <th>Dernière activité</th>
            </tr>
        </thead>
        <tbody id="online-tbody">
        <?php foreach ($online as $u): ?>
            <tr>
                <td><?= (int)$u['id'] ?></td>
                <td><strong><?= htmlspecialchars($u['login']) ?></strong></td>
                <td><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
                <td>
                    <?php if ($u['status'] == 1): ?>
                        <span style="background-color:#0682ef;padding:2px 6px;border-radius:4px;color:#fff">Admin</span>
                    <?php elseif ($u['status'] == 2): ?>
                        <span style="background-color:#bc2626;padding:2px 6px;border-radius:4px;color:#fff">Banni</span>
                    <?php else: ?>
                        <span style="background-color:#26bc26;padding:2px 6px;border-radius:4px;color:#fff">User</span>
                    <?php endif; ?>
                </td>
                <td style="color:orange;font-size:.85rem"><?= htmlspecialchars($u['last_co']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
function roleBadge(status) {
    if (status === 1) return '<span style="background-color:#0682ef;padding:2px 6px;border-radius:4px;color:#fff">Admin</span>';
    if (status === 2) return '<span style="background-color:#bc2626;padding:2px 6px;border-radius:4px;color:#fff">Banni</span>';
    return '<span style="background-color:#26bc26;padding:2px 6px;border-radius:4px;color:#fff">User</span>';
}

function refreshOnline() {
    fetch('admin_online_poll.php')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var tbody = document.getElementById('online-tbody');
            var countEl = document.getElementById('online-count');
            var emptyEl = document.getElementById('online-empty');

            countEl.textContent = '(' + data.count + ')';

            if (data.users.length === 0) {
                tbody.innerHTML = '';
                emptyEl.style.display = '';
                return;
            }

            emptyEl.style.display = 'none';
            var html = '';
            for (var i = 0; i < data.users.length; i++) {
                var u = data.users[i];
                html += '<tr>'
                    + '<td>' + u.id + '</td>'
                    + '<td><strong>' + u.login + '</strong></td>'
                    + '<td>' + u.nom + '</td>'
                    + '<td>' + roleBadge(u.status) + '</td>'
                    + '<td style="color:orange;font-size:.85rem">' + u.last_co + '</td>'
                    + '</tr>';
            }
            tbody.innerHTML = html;
        });
}

setInterval(refreshOnline, 5000);
</script>

</body>
</html>
