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
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="../assets/css/styles.css" rel="stylesheet" />
</head>

<?php
include_once __DIR__ . '/../includes/logger.php';

log_action('Administration');

include_once __DIR__ . '/../includes/protect.php';
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/adminnav.php';



if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Seul les admins peuvent accéder à cette page (ça va de soi hein mais ça marche pas ducoup pour voir la page )
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 1) {
    http_response_code(403);
    die('<p style="font-family:sans-serif;color:red;padding:2rem">NAN TU DEGAGES !!! ALLEZ ALLEZ OUST</p>');
}

define('LOG_FILE_PATH', dirname(__DIR__) . '/logs/logs.txt');

// Lecture et répartition du fichier de logs (juste pour la beauté)
$logs = [];
if (file_exists(LOG_FILE_PATH)) {
    $lines = file(LOG_FILE_PATH, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    array_shift($lines);

    foreach ($lines as $line) {
        $row = explode('|', $line);
        if (count($row) < 8) continue;
        $logs[] = [
            'datetime' => $row[0],
            'id' => $row[1],
            'login' => $row[2],
            'role' => $row[3],
            'page' => $row[4],
            'action' => $row[5],
            'detail' => $row[6],
            'ip_address' => $row[7],
        ];
    }
    $logs = array_reverse($logs);
}

// Récupération des filtres
$fLogin  = trim($_GET['login'] ?? '');
$fAction = trim($_GET['action'] ?? '');
$fRole = trim($_GET['role'] ?? '');
$fPage   = trim($_GET['page_filter'] ?? '');

// Application des filtres (pas de SQL ici, on fait ça à la mano dans PHP, c'est pas comme si on avait 10k lignes de logs hein)
$filtered = array_filter($logs, function($log) use ($fLogin, $fAction, $fRole, $fPage) {
    if ($fLogin  && stripos($log['login'],  $fLogin)  === false) return false;
    if ($fAction && $log['action'] !== $fAction) return false;
    if ($fRole && $log['role'] !== $fRole) return false;
    if ($fPage   && stripos($log['page'],   $fPage)   === false) return false;
    return true;
});

$filtered = array_values($filtered);

// Pagination
$perPage     = 25;
$total       = count($filtered);
$totalPages  = max(1, (int)ceil($total / $perPage));
$currentPage = max(1, min($totalPages, (int)($_GET['p'] ?? 1)));
$slice       = array_slice($filtered, ($currentPage - 1) * $perPage, $perPage);

// Liste des actions pour le filtre (parcequ'on s'emmerde pas à faire du SQL pour ça)
$actions = array_unique(array_column($logs, 'action'));

function qurl(array $extra = []): string {
    $params = array_merge([
        'login' => $_GET['login'] ?? '',
        'action' => $_GET['action'] ?? '',
        'role' => $_GET['role'] ?? '',
        'page_filter' => $_GET['page_filter'] ?? '',
        'p' => $_GET['p'] ?? 1,
    ], $extra);
    return '?' . http_build_query(array_filter($params, function($valeur) { return $valeur !== ''; }));
}

$actionColors = [
    'Visite' => 'blue',
    'ajout' => 'green',
    'modification'=> 'orange',
    'suppression' => 'red',
];

$roleConfig = [
    'admin' => ['label' => 'Admin', 'bg' => 'blue'],
    'banni' => ['label' => 'Banni', 'bg' => 'red'],
    'user' => ['label' => 'User', 'bg' => 'green'],
];
?>


<body>

<h1>Logs d'activité</h1>

<form class="filters" method="GET" action="">
    <label>Login
        <input type="text" name="login" value="<?= htmlspecialchars($fLogin) ?>" placeholder="ex: elShogun">
    </label>
    <label>Page
        <input type="text" name="page_filter" value="<?= htmlspecialchars($fPage) ?>" placeholder="ex: fdp.php">
    </label>
    <label>Action
        <select name="action">
            <option value="">Toutes</option>
            <?php foreach ($actions as $action): ?>
                <option value="<?= htmlspecialchars($action) ?>" <?= $fAction === $action ? 'selected' : '' ?>>
                    <?= htmlspecialchars($action) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Rôle
        <select name="status">
            <option value="">Tous</option>
            <option value="admin" <?= $fRole === 'admin' ? 'selected' : '' ?>>Admin</option>
            <option value="utilisateur" <?= $fRole === 'utilisateur' ? 'selected' : '' ?>>Utilisateur</option>
        </select>
    </label>
    <label>Banni
        <select name="is_banni">
            <option value="">Tous</option>
            <option value="banni" <?= $fRole === 'banni' ? 'selected' : '' ?>>Banni</option>
            <option value="non banni" <?= $fRole !== 'banni' ? 'selected' : '' ?>>Non banni</option>
        </select>
    </label>
    <button class="btn btn-primary" type="submit">Filtrer</button>
    <a class="btn btn-reset" href="logs.php">Réinitialiser</a>
</form>

<p class="meta"><?= $total ?> entrée<?= $total > 1 ? 's' : '' ?>° page <?= $currentPage ?>/<?= $totalPages ?></p>

<div class="wrap">
    <table class="table table-striped-columns table-bordered">
        <thead class="table-dark">
            <tr>
                <th scope="col">Date / heure</th>
                <th scope="col">Login</th>
                <th scope="col">Rôle</th>
                <th scope="col">Banni</th>
                <th scope="col">Page</th>
                <th scope="col">Action</th>
                <th scope="col">Détail</th>
                <th scope="col">IP</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($slice)): ?>
            <tr><td colspan="8" class="empty">Aucun log trouvé.</td></tr>
        <?php else: ?>
            <?php foreach ($slice as $log): ?>
            <?php $color = $actionColors[$log['action']] ?? 'orange'; ?>
            <tr>
                <td><?= htmlspecialchars($log['datetime']) ?></td>
                <td>
                    <strong><?= htmlspecialchars($log['login']) ?></strong>
                    <span style="color: black;font-size:.78rem"><?= htmlspecialchars($log['id']) ?></span>
                </td>
                <td>
                    <?php if ($log['role'] === 'admin'): ?>
                        <span style="background-color: #0682ef">Admin</span>
                    <?php else: ?>
                        <span style="background-color: #26bc26">User</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($log['role'] === 'banni'): ?>
                        <span class="badge badge-warning">Banni</span>
                    <?php else: ?>
                        <span style="color: grey">—</span>
                    <?php endif; ?>
                </td>
                <td class="page" title="<?= htmlspecialchars($log['page']) ?>"><?= htmlspecialchars($log['page']) ?></td>
                <td>
                    <span class="action-pill" style="background:<?= $color ?>">
                        <?= htmlspecialchars($log['action']) ?>
                    </span>
                </td>
                <td class="detail" title="<?= htmlspecialchars($log['detail']) ?>">
                    <?= $log['detail'] !== '' ? htmlspecialchars($log['detail']) : '<span style="color: grey">—</span>' ?>
                </td>
                <td style="color: orange; font-size:.82rem"><?= htmlspecialchars($log['ip_address']) ?></td>
            </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="pagination">
    <?php if ($currentPage > 1): ?>
        <a href="<?= qurl(['p' => $currentPage - 1]) ?>">Précédent</a>
    <?php else: ?>
        <span class="disabled">Précédent</span>
    <?php endif; ?>

    <?php for ($p = 1; $p <= $totalPages; $p++):
        if ($p === 1 || $p === $totalPages || abs($p - $currentPage) <= 2): ?>
            <?php if ($p === $currentPage): ?>
                <span class="cur"><?= $p ?></span>
            <?php else: ?>
                <a href="<?= qurl(['p' => $p]) ?>"><?= $p ?></a>
            <?php endif; ?>
        <?php elseif (abs($p - $currentPage) === 3): ?>
            <span class="disabled">…</span>
        <?php endif;
    endfor; ?>

    <?php if ($currentPage < $totalPages): ?>
        <a href="<?= qurl(['p' => $currentPage + 1]) ?>">Suivant</a>
    <?php else: ?>
        <span class="disabled">Suivant</span>
    <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>