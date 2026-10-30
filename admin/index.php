<?php
include_once __DIR__ . '/../includes/protect_admin.php';
include __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/logger.php';

log_action('Administration');

include __DIR__ . '/../includes/adminnav.php';

$totalUsers   = (int)$pdo->query("SELECT COUNT(*) FROM USER")->fetchColumn();
$totalAdmins  = (int)$pdo->query("SELECT COUNT(*) FROM USER WHERE status = 1")->fetchColumn();
$totalBanned  = (int)$pdo->query("SELECT COUNT(*) FROM USER WHERE status = 2")->fetchColumn();
$unverified   = (int)$pdo->query("SELECT COUNT(*) FROM USER WHERE isVerified = 0")->fetchColumn();

$totalTables  = (int)$pdo->query("SELECT COUNT(*) FROM TABLEDEJEU")->fetchColumn();
$totalMembers = (int)$pdo->query("SELECT COUNT(DISTINCT id_user) FROM TABLE_MEMBRE")->fetchColumn();

$totalPosts    = (int)$pdo->query("SELECT COUNT(*) FROM FILLDISCUSSION")->fetchColumn();
$totalComments = (int)$pdo->query("SELECT COUNT(*) FROM MESSAGE")->fetchColumn();

$totalNl       = (int)$pdo->query("SELECT COUNT(*) FROM NEWSLETTER")->fetchColumn();
$pendingNl     = (int)$pdo->query("SELECT COUNT(*) FROM NEWSLETTER WHERE statut = 'standby'")->fetchColumn();
$draftNl       = (int)$pdo->query("SELECT COUNT(*) FROM NEWSLETTER WHERE statut = 'draft'")->fetchColumn();

$totalSystems = (int)$pdo->query("SELECT COUNT(*) FROM SYSTEME")->fetchColumn();
$totalFdp     = (int)$pdo->query("SELECT COUNT(*) FROM FDP")->fetchColumn();

$seuil = 5;
$stmtOnline = $pdo->prepare(
    "SELECT id, login, status, last_co FROM USER WHERE last_co >= NOW() - INTERVAL :seuil MINUTE ORDER BY last_co DESC LIMIT 10"
);
$stmtOnline->bindValue(':seuil', $seuil, PDO::PARAM_INT);
$stmtOnline->execute();
$onlineUsers = $stmtOnline->fetchAll();

$recentUsers = $pdo->query(
    "SELECT id, login, email, status FROM USER ORDER BY id DESC LIMIT 8"
)->fetchAll();

$unverifiedUsers = $pdo->query(
    "SELECT id, login, email FROM USER WHERE isVerified = 0 ORDER BY id DESC"
)->fetchAll();

$tickets = $pdo->query("SELECT id, titre, mail, statut FROM TICKET ORDER BY id DESC LIMIT 5")->fetchAll();
$totalTickets = (int)$pdo->query("SELECT COUNT(*) FROM TICKET")->fetchColumn();
$newTickets = (int)$pdo->query("SELECT COUNT(*) FROM TICKET WHERE statut = 'nouveau'")->fetchColumn();

$pendingNewsletters = $pdo->query(
    "SELECT n.id, n.titre, n.date_soumise, u.login AS admin_login
     FROM NEWSLETTER n
     LEFT JOIN USER u ON u.id = n.id_admin
     WHERE n.statut = 'standby'
     ORDER BY n.date_soumise ASC
     LIMIT 5"
)->fetchAll();

$recentTables = $pdo->query(
    "SELECT t.id, t.nom, t.date_creation, u.login AS mj_login,
            (SELECT COUNT(*) FROM TABLE_MEMBRE WHERE id_table = t.id) AS nb_membres
     FROM TABLEDEJEU t
     JOIN USER u ON u.id = t.id_maitrejeu
     ORDER BY t.date_creation DESC
     LIMIT 5"
)->fetchAll();

$lastPage = [];
$logs = [];
$logFile = dirname(__DIR__) . '/logs/logs.txt';
if (file_exists($logFile)) {
    $allLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    array_shift($allLines);
    foreach (array_reverse($allLines) as $line) {
        $row = explode('|', $line);
        if (count($row) < 8) continue;
        $login = $row[2];
        if (!isset($lastPage[$login])) {
            $lastPage[$login] = basename($row[4]);
        }
    }
    $lines = array_slice(array_reverse($allLines), 0, 10);
    foreach ($lines as $line) {
        $row = explode('|', $line);
        if (count($row) < 8) continue;
        $logs[] = [
            'datetime' => $row[0],
            'login'    => $row[2],
            'role'     => $row[3],
            'page'     => $row[4],
            'action'   => $row[5],
            'detail'   => $row[6],
        ];
    }
}
?>

<div class="dashboard">

    <h1>Tableau de bord</h1>

    <div class="dash-grid">
        <div class="stat-card">
            <span class="stat-value"><?= $totalUsers ?></span>
            <span class="stat-label">Utilisateurs</span>
            <span class="stat-sub"><?= $totalAdmins ?> admin<?= $totalAdmins > 1 ? 's' : '' ?> &middot; <?= $totalBanned ?> banni<?= $totalBanned > 1 ? 's' : '' ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalTables ?></span>
            <span class="stat-label">Tables de jeu</span>
            <span class="stat-sub"><?= $totalMembers ?> joueur<?= $totalMembers > 1 ? 's' : '' ?> actif<?= $totalMembers > 1 ? 's' : '' ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalPosts ?></span>
            <span class="stat-label">Posts forum</span>
            <span class="stat-sub"><?= $totalComments ?> commentaire<?= $totalComments > 1 ? 's' : '' ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalFdp ?></span>
            <span class="stat-label">Fiches de perso</span>
            <span class="stat-sub"><?= $totalSystems ?> syst&egrave;me<?= $totalSystems > 1 ? 's' : '' ?> de jeu</span>
        </div>
        <div class="stat-card">
            <span class="stat-value"><?= $totalNl ?></span>
            <span class="stat-label">Newsletters</span>
            <span class="stat-sub"><?= $pendingNl ?> en attente &middot; <?= $draftNl ?> brouillon<?= $draftNl > 1 ? 's' : '' ?></span>
        </div>
        <div class="stat-card" style="border-top:3px solid #c0392b">
            <span class="stat-value"><?= $newTickets ?></span>
            <span class="stat-label">Nouveaux</span>
            <span class="stat-sub">tickets</span>
        </div>
        <div class="stat-card" style="border-top:3px solid #e67e22">
            <span class="stat-value"><?= (int)$pdo->query("SELECT COUNT(*) FROM TICKET WHERE statut = 'en_cours'")->fetchColumn() ?></span>
            <span class="stat-label">En cours</span>
            <span class="stat-sub">tickets</span>
        </div>
        <div class="stat-card" style="border-top:3px solid #26bc26">
            <span class="stat-value"><?= (int)$pdo->query("SELECT COUNT(*) FROM TICKET WHERE statut = 'traite'")->fetchColumn() ?></span>
            <span class="stat-label">Trait&eacute;s</span>
            <span class="stat-sub">tickets</span>
        </div>
    </div>

    <?php if (!empty($unverifiedUsers)): ?>
    <div class="dash-section" style="border-left: 3px solid #e67e22;">
        <h2>Comptes non v&eacute;rifi&eacute;s (<?= count($unverifiedUsers) ?>)</h2>
        <table class="dash-mini-table">
            <thead><tr><th>Pseudo</th><th>Email</th><th>Action</th></tr></thead>
            <tbody>
            <?php foreach ($unverifiedUsers as $uv): ?>
            <tr>
                <td><strong style="color:#fff"><?= htmlspecialchars($uv['login']) ?></strong></td>
                <td style="color:#888;font-size:.85rem"><?= htmlspecialchars($uv['email']) ?></td>
                <td><a href="edituser.php?id=<?= (int)$uv['id'] ?>" class="btn btn-sm btn-outline-warning">Configurer</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($tickets)): ?>
    <div class="dash-section" style="border-left: 3px solid #c0392b;">
        <h2>Tickets <a href="tickets.php">Voir tout</a></h2>
        <table class="dash-mini-table">
            <thead><tr><th>Statut</th><th>Titre</th><th>Email</th></tr></thead>
            <tbody>
            <?php
            $tkColors = ['nouveau' => '#c0392b', 'en_cours' => '#e67e22', 'traite' => '#26bc26'];
            $tkLabels = ['nouveau' => 'Nouveau', 'en_cours' => 'En cours', 'traite' => 'Traité'];
            ?>
            <?php foreach ($tickets as $tk): ?>
            <tr>
                <td><span class="role-badge" style="background:<?= $tkColors[$tk['statut']] ?? '#666' ?>"><?= $tkLabels[$tk['statut']] ?? $tk['statut'] ?></span></td>
                <td><strong style="color:#fff"><?= htmlspecialchars($tk['titre']) ?></strong></td>
                <td style="color:#888;font-size:.85rem"><?= $tk['mail'] ? htmlspecialchars($tk['mail']) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if (!empty($pendingNewsletters)): ?>
    <div class="dash-section" style="border-left: 3px solid #c0392b;">
        <h2>Newsletters en attente d'envoi <a href="../newsletter.php">G&eacute;rer</a></h2>
        <?php foreach ($pendingNewsletters as $nl): ?>
        <div class="nl-pending">
            <div>
                <div class="nl-p-title"><?= htmlspecialchars($nl['titre']) ?></div>
                <div class="nl-p-meta">par <?= htmlspecialchars($nl['admin_login'] ?? '?') ?> &middot; soumise le <?= date('d/m/Y H:i', strtotime($nl['date_soumise'])) ?></div>
            </div>
            <a href="../newsletter.php" class="btn btn-sm" style="background:#620D14;color:#fff;border:1px solid #8b1a1a;font-family:'New Rocker',system-ui;font-size:.8rem">Voir</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="dash-columns">

        <div class="dash-section">
            <h2>En ligne <a href="online.php">Voir tout</a></h2>
            <?php if (empty($onlineUsers)): ?>
                <p style="color:#666;font-size:.85rem">Aucun utilisateur en ligne.</p>
            <?php else: ?>
                <table class="dash-mini-table">
                    <thead><tr><th>Utilisateur</th><th>R&ocirc;le</th><th>Page</th><th>Heure</th></tr></thead>
                    <tbody>
                    <?php foreach ($onlineUsers as $u): ?>
                    <tr>
                        <td><span class="online-dot"></span><a href="edituser.php?id=<?= (int)$u['id'] ?>" style="color:#fff;text-decoration:none"><strong><?= htmlspecialchars($u['login']) ?></strong></a></td>
                        <td>
                            <?php if ($u['status'] == 1): ?>
                                <span class="role-badge" style="background:#0682ef">Admin</span>
                            <?php elseif ($u['status'] == 2): ?>
                                <span class="role-badge" style="background:#bc2626">Banni</span>
                            <?php else: ?>
                                <span class="role-badge" style="background:#26bc26">User</span>
                            <?php endif; ?>
                        </td>
                        <td style="color:#ffb86c;font-family:monospace;font-size:.78rem"><?= htmlspecialchars($lastPage[$u['login']] ?? '—') ?></td>
                        <td style="color:#e8a87c;font-size:.8rem"><?= date('H:i', strtotime($u['last_co'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="dash-section">
            <h2>Derniers inscrits <a href="listuser.php">Voir tout</a></h2>
            <table class="dash-mini-table">
                <thead><tr><th>Pseudo</th><th>R&ocirc;le</th><th>Inscription</th></tr></thead>
                <tbody>
                <?php foreach ($recentUsers as $u): ?>
                <tr>
                    <td>
                        <a href="edituser.php?id=<?= (int)$u['id'] ?>" style="color:#fff;text-decoration:none">
                            <strong><?= htmlspecialchars($u['login']) ?></strong>
                        </a>
                    </td>
                    <td>
                        <?php if ($u['status'] == 1): ?>
                            <span class="role-badge" style="background:#0682ef">Admin</span>
                        <?php elseif ($u['status'] == 2): ?>
                            <span class="role-badge" style="background:#bc2626">Banni</span>
                        <?php else: ?>
                            <span class="role-badge" style="background:#26bc26">User</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:.8rem;color:#888">#<?= (int)$u['id'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div class="dash-columns">

        <div class="dash-section">
            <h2>Derni&egrave;res tables <a href="tables.php">Voir tout</a></h2>
            <?php if (empty($recentTables)): ?>
                <p style="color:#666;font-size:.85rem">Aucune table cr&eacute;&eacute;e.</p>
            <?php else: ?>
                <table class="dash-mini-table">
                    <thead><tr><th>Table</th><th>MJ</th><th>Membres</th><th>Cr&eacute;ation</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentTables as $t): ?>
                    <tr>
                        <td><strong style="color:#fff"><?= htmlspecialchars($t['nom']) ?></strong></td>
                        <td style="color:#e8a87c"><?= htmlspecialchars($t['mj_login']) ?></td>
                        <td style="text-align:center"><?= (int)$t['nb_membres'] ?></td>
                        <td style="font-size:.8rem;color:#888"><?= date('d/m/Y', strtotime($t['date_creation'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="dash-section">
            <h2>Activit&eacute; r&eacute;cente <a href="logs.php">Tous les logs</a></h2>
            <?php if (empty($logs)): ?>
                <p style="color:#666;font-size:.85rem">Aucun log.</p>
            <?php else: ?>
                <table class="dash-mini-table">
                    <thead><tr><th>Heure</th><th>Utilisateur</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="font-size:.78rem;color:#888;white-space:nowrap"><?= htmlspecialchars(substr($log['datetime'], 5)) ?></td>
                        <td><strong style="color:#fff"><?= htmlspecialchars($log['login']) ?></strong></td>
                        <td>
                            <span style="color:#e8a87c"><?= htmlspecialchars($log['action']) ?></span>
                            <?php if ($log['detail']): ?>
                                <span style="color:#666;font-size:.78rem">&middot; <?= htmlspecialchars(mb_substr($log['detail'], 0, 30)) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    </div>

    <div class="dash-section">
        <h2>Acc&egrave;s rapide</h2>
        <div class="quick-links">
            <a href="listuser.php" class="quick-link"><span class="ql-icon">&#9884;</span> Utilisateurs</a>
            <a href="listadmin.php" class="quick-link"><span class="ql-icon">&#9733;</span> Administrateurs</a>
            <a href="cofulldata.php" class="quick-link"><span class="ql-icon">&#128220;</span> CO Fantasy</a>
            <a href="../newsletter.php" class="quick-link"><span class="ql-icon">&#9993;</span> Newsletters</a>
            <a href="../forum.php" class="quick-link"><span class="ql-icon">&#9881;</span> Forum</a>
            <a href="captcha.php" class="quick-link"><span class="ql-icon">&#128274;</span> Captchas</a>
            <a href="logs.php" class="quick-link"><span class="ql-icon">&#128203;</span> Logs</a>
            <a href="online.php" class="quick-link"><span class="ql-icon">&#128994;</span> Connect&eacute;s</a>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
