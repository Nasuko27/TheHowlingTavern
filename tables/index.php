<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
include_once __DIR__ . '/../includes/protect_verified.php';
include_once __DIR__ . '/../includes/logger.php';

$isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';

function generate_invite_code($pdo) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $stmt = $pdo->prepare("SELECT 1 FROM TABLEDEJEU WHERE code_invite = :code");
        $stmt->execute([':code' => $code]);
    } while ($stmt->fetch());
    return $code;
}

$systemes = $pdo->query("SELECT id, nom FROM SYSTEME WHERE valide = 1 ORDER BY nom")->fetchAll();

$flash = null;
if (isset($_SESSION['tables_flash'])) {
    $flash = $_SESSION['tables_flash'];
    unset($_SESSION['tables_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_table'])) {
    $nom = trim($_POST['nom'] ?? '');
    if ($nom === '') {
        $_SESSION['tables_flash'] = ['type' => 'danger', 'msg' => 'Le nom ne peut pas être vide.'];
        header('Location: index.php?new=1');
        exit();
    }
    if (mb_strlen($nom) > 100) $nom = mb_substr($nom, 0, 100);
    $sid = (int)($_POST['id_systeme'] ?? 0);
    if (!$sid) {
        $_SESSION['tables_flash'] = ['type' => 'danger', 'msg' => 'Veuillez choisir un système de jeu.'];
        header('Location: index.php?new=1');
        exit();
    }
    $code = generate_invite_code($pdo);
    $pdo->prepare(
        "INSERT INTO TABLEDEJEU (nom, id_maitrejeu, code_invite, date_creation, id_systeme) VALUES (:nom, :uid, :code, NOW(), :sid)"
    )->execute([':nom' => $nom, ':uid' => $_SESSION['id'], ':code' => $code, ':sid' => $sid]);
    $newId = (int)$pdo->lastInsertId();
    $pdo->prepare(
        "INSERT INTO TABLE_MEMBRE (id_table, id_user, role) VALUES (:tid, :uid, 'mj')"
    )->execute([':tid' => $newId, ':uid' => $_SESSION['id']]);
    log_action('Tables', 'Creation #' . $newId);
    header('Location: jeu.php?id=' . $newId);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_table'])) {
    $code = strtoupper(trim($_POST['code_invite'] ?? ''));
    if (!preg_match('/^[A-Z2-9]{6}$/', $code)) {
        $_SESSION['tables_flash'] = ['type' => 'danger', 'msg' => 'Code invalide (6 caractères attendus).'];
        header('Location: index.php?join=1');
        exit();
    }
    $stmt = $pdo->prepare("SELECT id FROM TABLEDEJEU WHERE code_invite = :code");
    $stmt->execute([':code' => $code]);
    $table = $stmt->fetch();
    if (!$table) {
        $_SESSION['tables_flash'] = ['type' => 'danger', 'msg' => 'Aucune table trouvée avec ce code.'];
        header('Location: index.php?join=1');
        exit();
    }
    $tid = (int)$table['id'];
    $check = $pdo->prepare("SELECT 1 FROM TABLE_MEMBRE WHERE id_table = :tid AND id_user = :uid");
    $check->execute([':tid' => $tid, ':uid' => $_SESSION['id']]);
    if ($check->fetch()) {
        header('Location: jeu.php?id=' . $tid);
        exit();
    }
    $pdo->prepare(
        "INSERT INTO TABLE_MEMBRE (id_table, id_user, role) VALUES (:tid, :uid, 'joueur')"
    )->execute([':tid' => $tid, ':uid' => $_SESSION['id']]);
    log_action('Tables', 'Rejoint #' . $tid);
    header('Location: jeu.php?id=' . $tid);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_table'])) {
    $tid = (int)($_POST['table_id'] ?? 0);
    if ($tid) {
        $stmt = $pdo->prepare("SELECT id_maitrejeu FROM TABLEDEJEU WHERE id = :id");
        $stmt->execute([':id' => $tid]);
        $t = $stmt->fetch();
        if ($t && ((int)$t['id_maitrejeu'] === (int)$_SESSION['id'] || $isAdmin)) {
            $pdo->prepare("DELETE FROM joueur WHERE id_table = :id")->execute([':id' => $tid]);
            $pdo->prepare("DELETE FROM TABLE_CHAT WHERE id_table = :id")->execute([':id' => $tid]);
            $pdo->prepare("DELETE FROM TABLE_MEMBRE WHERE id_table = :id")->execute([':id' => $tid]);
            $pdo->prepare("DELETE FROM TABLEDEJEU WHERE id = :id")->execute([':id' => $tid]);
            log_action('Tables', 'Suppression #' . $tid);
        }
    }
    header('Location: index.php');
    exit();
}

$stmt = $pdo->prepare(
    "SELECT t.id, t.nom, t.code_invite, t.date_creation, t.id_maitrejeu,
            u.login AS mj_login, u.avatar AS mj_avatar,
            (SELECT COUNT(*) FROM TABLE_MEMBRE WHERE id_table = t.id) AS nb_membres
     FROM TABLEDEJEU t
     JOIN USER u ON t.id_maitrejeu = u.id
     JOIN TABLE_MEMBRE tm ON tm.id_table = t.id AND tm.id_user = :uid
     ORDER BY t.date_creation DESC"
);
$stmt->execute([':uid' => $_SESSION['id']]);
$tables = $stmt->fetchAll();

$showNew  = isset($_GET['new']);
$showJoin = isset($_GET['join']);

log_action('Tables', 'Liste');
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<body>
<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="forum-container">

    <?php if ($flash): ?>
    <div class="alert" style="border:1px solid #620D14;background:rgb(36 9 2 / 0.92);color:<?= $flash['type'] === 'danger' ? '#ff6b6b' : '#6bcf6b' ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <div class="forum-header">
        <h1>Mes Tables</h1>
        <div class="d-flex gap-2">
            <a href="index.php?new=1" class="btn-forum-new">Cr&eacute;er</a>
            <a href="index.php?join=1" class="btn-forum-new" style="background:#333;border-color:#555">Rejoindre</a>
        </div>
    </div>

    <?php if ($showNew): ?>
    <div class="form-card">
        <h2>Cr&eacute;er une table</h2>
        <form method="POST">
            <input type="hidden" name="create_table" value="1">
            <input type="text" name="nom" placeholder="Nom de la table" maxlength="100" required>
            <select name="id_systeme" required style="width:100%;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.6rem .8rem;font-family:'Texturina',serif;font-size:.95rem;margin-bottom:.8rem">
                <option value="">-- Syst&egrave;me de jeu --</option>
                <?php foreach ($systemes as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="d-flex gap-2">
                <button type="submit" class="btn-forum-new">Cr&eacute;er</button>
                <a href="index.php" class="btn-forum-new" style="background:#333;border-color:#555">Annuler</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($showJoin): ?>
    <div class="form-card">
        <h2>Rejoindre une table</h2>
        <form method="POST">
            <input type="hidden" name="join_table" value="1">
            <input type="text" name="code_invite" placeholder="Code d'invitation" maxlength="6" required>
            <div class="d-flex gap-2" style="margin-top:.5rem">
                <button type="submit" class="btn-forum-new">Rejoindre</button>
                <a href="index.php" class="btn-forum-new" style="background:#333;border-color:#555">Annuler</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($tables)): ?>
        <p style="color:#888;text-align:center;margin-top:3rem">Aucune table pour le moment. Cr&eacute;e-en une ou rejoins-en une avec un code !</p>
    <?php else: ?>
        <?php foreach ($tables as $t): ?>
        <?php
            $isMJ = (int)$t['id_maitrejeu'] === (int)$_SESSION['id'];
            $mjAvSrc = $t['mj_avatar'] ? '../assets/img/avatars/' . htmlspecialchars($t['mj_avatar']) : '../assets/img/usrplaceholder.png';
        ?>
        <div class="post-card">
            <div class="post-body">
                <a href="jeu.php?id=<?= (int)$t['id'] ?>" class="post-title"><?= htmlspecialchars($t['nom']) ?></a>
                <div class="post-meta">
                    <a href="../compte/profil.php?id=<?= (int)$t['id_maitrejeu'] ?>"><img src="<?= $mjAvSrc ?>" alt="" style="width:20px;height:20px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:.2rem"></a>
                    MJ : <strong><a href="../compte/profil.php?id=<?= (int)$t['id_maitrejeu'] ?>" style="color:#e8a87c;text-decoration:none"><?= htmlspecialchars($t['mj_login']) ?></a></strong>
                    &middot; <?= (int)$t['nb_membres'] ?> membre<?= $t['nb_membres'] > 1 ? 's' : '' ?>
                    <?php if ($t['date_creation']): ?>
                        &middot; <?= date('d/m/Y', strtotime($t['date_creation'])) ?>
                    <?php endif; ?>
                </div>
                <div class="post-actions">
                    <?php if ($isMJ): ?>
                        <span class="invite-code"><?= htmlspecialchars($t['code_invite']) ?></span>
                        <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($t['code_invite']) ?>')" class="btn-forum-new" style="font-size:.7rem;padding:.2rem .5rem">Copier</button>
                        <form method="POST" style="display:inline;margin-left:.5rem" onsubmit="return confirm('Supprimer cette table et tout son contenu ?')">
                            <input type="hidden" name="delete_table" value="1">
                            <input type="hidden" name="table_id" value="<?= (int)$t['id'] ?>">
                            <button type="submit" class="btn-delete">Supprimer</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>

<?php 
include __DIR__ . '/../includes/footer.php'; ?>