<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../includes/logger.php';

$isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';

include_once __DIR__ . '/../includes/mailer.php';
$standbys = $pdo->query(
    "SELECT * FROM NEWSLETTER WHERE statut = 'standby' AND date_soumise <= NOW() - INTERVAL 6 HOUR"
)->fetchAll();
foreach ($standbys as $nl) {
    $members = $pdo->query(
        "SELECT login, email FROM USER WHERE status != 2 AND email != '' AND email IS NOT NULL"
    )->fetchAll();
    foreach ($members as $m) {
        send_newsletter_mail($m['email'], $m['login'], $nl['titre'], $nl['contenu'], (int)$nl['id']);
    }
    $pdo->prepare(
        "UPDATE NEWSLETTER SET statut = 'published', date_pub = NOW() WHERE id = :id"
    )->execute([':id' => $nl['id']]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_article'])) {
    if (!$isAdmin) { http_response_code(403); exit(); }
    $titre   = trim($_POST['titre']   ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $action  = $_POST['action'] ?? 'draft';
    if ($titre !== '' && $contenu !== '') {
        if ($action === 'publish') {
            $pdo->prepare(
                "INSERT INTO NEWSLETTER (titre, contenu, statut, date_soumise, id_admin) VALUES (:t, :c, 'standby', NOW(), :uid)"
            )->execute([':t' => $titre, ':c' => $contenu, ':uid' => $_SESSION['id']]);
        } else {
            $pdo->prepare(
                "INSERT INTO NEWSLETTER (titre, contenu, statut, id_admin) VALUES (:t, :c, 'draft', :uid)"
            )->execute([':t' => $titre, ':c' => $contenu, ':uid' => $_SESSION['id']]);
        }
    }
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_article'])) {
    if (!$isAdmin) { http_response_code(403); exit(); }
    $aid     = (int)($_POST['article_id'] ?? 0);
    $titre   = trim($_POST['titre']   ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $action  = $_POST['action'] ?? 'save';
    if ($aid && $titre !== '' && $contenu !== '') {
        $stmt = $pdo->prepare("SELECT statut, id_admin FROM NEWSLETTER WHERE id = :id");
        $stmt->execute([':id' => $aid]);
        $row = $stmt->fetch();
        $canEdit = $row && (
            $row['statut'] === 'standby' ||
            ($row['statut'] === 'draft' && (int)$row['id_admin'] === (int)$_SESSION['id'])
        );
        if ($canEdit) {
            if ($row['statut'] === 'draft' && $action === 'publish') {
                $pdo->prepare(
                    "UPDATE NEWSLETTER SET titre = :t, contenu = :c, statut = 'standby', date_soumise = NOW() WHERE id = :id"
                )->execute([':t' => $titre, ':c' => $contenu, ':id' => $aid]);
            } elseif ($row['statut'] === 'standby') {
                $pdo->prepare(
                    "UPDATE NEWSLETTER SET titre = :t, contenu = :c, date_soumise = NOW() WHERE id = :id"
                )->execute([':t' => $titre, ':c' => $contenu, ':id' => $aid]);
            } else {
                $pdo->prepare(
                    "UPDATE NEWSLETTER SET titre = :t, contenu = :c WHERE id = :id"
                )->execute([':t' => $titre, ':c' => $contenu, ':id' => $aid]);
            }
        }
    }
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_article'])) {
    if (!$isAdmin) { http_response_code(403); exit(); }
    $aid = (int)($_POST['article_id'] ?? 0);
    if ($aid) {
        $pdo->prepare("DELETE FROM NEWSLETTER WHERE id = :id")->execute([':id' => $aid]);
    }
    header('Location: index.php');
    exit();
}

$filter_id = isset($_GET['id'])   ? (int)$_GET['id']   : 0;
$edit_id   = ($isAdmin && isset($_GET['edit'])) ? (int)$_GET['edit'] : 0;

if ($filter_id > 0) {
    $cond = $isAdmin ? '' : " AND n.statut = 'published'";
    $stmt = $pdo->prepare(
        "SELECT n.id, n.titre, n.contenu, n.date_pub, n.date_soumise, n.statut, n.id_admin, u.login, u.avatar
         FROM NEWSLETTER n JOIN USER u ON u.id = n.id_admin
         WHERE n.id = :id$cond"
    );
    $stmt->execute([':id' => $filter_id]);
    $articles = $stmt->fetchAll();
} elseif ($isAdmin) {
    $stmt = $pdo->prepare(
        "SELECT n.id, n.titre, n.contenu, n.date_pub, n.date_soumise, n.statut, n.id_admin, u.login, u.avatar
         FROM NEWSLETTER n JOIN USER u ON u.id = n.id_admin
         WHERE n.statut IN ('published','standby') OR (n.statut = 'draft' AND n.id_admin = :uid)
         ORDER BY
             CASE n.statut WHEN 'draft' THEN 0 WHEN 'standby' THEN 1 ELSE 2 END,
             n.id DESC"
    );
    $stmt->execute([':uid' => $_SESSION['id']]);
    $articles = $stmt->fetchAll();
} else {
    $articles = $pdo->query(
        "SELECT n.id, n.titre, n.contenu, n.date_pub, n.date_soumise, n.statut, n.id_admin, u.login, u.avatar
         FROM NEWSLETTER n JOIN USER u ON u.id = n.id_admin
         WHERE n.statut = 'published'
         ORDER BY n.date_pub DESC"
    )->fetchAll();
}

$edit_article = null;
if ($edit_id) {
    $stmt = $pdo->prepare("SELECT id, titre, contenu, statut, id_admin FROM NEWSLETTER WHERE id = :id");
    $stmt->execute([':id' => $edit_id]);
    $row = $stmt->fetch();
    if ($row && (
        $row['statut'] === 'standby' ||
        ($row['statut'] === 'draft' && (int)$row['id_admin'] === (int)$_SESSION['id'])
    )) {
        $edit_article = $row;
    }
}

log_action('Newsletter', 'Lecture');

$showForm = isset($_GET['new']) && $isAdmin && !$edit_article;
?>
<?php include __DIR__ . '/../includes/header.php'; ?>
<body>
<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="forum-container">

    <div class="forum-header">
        <h1>Newsletter</h1>
        <?php if ($isAdmin && !$showForm && !$edit_article): ?>
        <a href="index.php?new=1" class="btn-forum-new">Nouvel article</a>
        <?php endif; ?>
    </div>

    <?php if ($showForm): ?>
    <div class="form-card">
        <h2>Nouvel article</h2>
        <form method="POST" action="index.php">
            <input type="hidden" name="new_article" value="1">
            <input type="text" name="titre" placeholder="Titre" maxlength="255" required>
            <textarea name="contenu" placeholder="Contenu&hellip;" required></textarea>
            <div class="d-flex gap-2">
                <button type="submit" name="action" value="draft" class="btn-forum-new" style="background:#333;border-color:#555">Enregistrer</button>
                <button type="submit" name="action" value="publish" class="btn-forum-new">Soumettre</button>
                <a href="index.php" class="btn-forum-new" style="background:#222;border-color:#444">Annuler</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($edit_article): ?>
    <div class="form-card">
        <h2>Modifier l&rsquo;article</h2>
        <form method="POST" action="index.php">
            <input type="hidden" name="edit_article" value="1">
            <input type="hidden" name="article_id" value="<?= (int)$edit_article['id'] ?>">
            <input type="text" name="titre" value="<?= htmlspecialchars($edit_article['titre']) ?>" maxlength="255" required>
            <textarea name="contenu" required><?= htmlspecialchars($edit_article['contenu']) ?></textarea>
            <div class="d-flex gap-2">
                <?php if ($edit_article['statut'] === 'draft'): ?>
                <button type="submit" name="action" value="save" class="btn-forum-new" style="background:#333;border-color:#555">Enregistrer</button>
                <button type="submit" name="action" value="publish" class="btn-forum-new">Soumettre</button>
                <?php else: ?>
                <button type="submit" name="action" value="save" class="btn-forum-new">Enregistrer</button>
                <?php endif; ?>
                <a href="index.php" class="btn-forum-new" style="background:#222;border-color:#444">Annuler</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($filter_id > 0): ?>
    <p style="margin-bottom:1.2rem">
        <a href="index.php" class="btn-forum-new" style="font-size:.85rem;padding:.4rem .9rem">&larr; Tous les articles</a>
    </p>
    <?php endif; ?>

    <?php if (empty($articles)): ?>
    <p style="color:#888;text-align:center;margin-top:3rem">
        <?php if ($filter_id > 0): ?>
            Cet article n&rsquo;existe plus. <a href="index.php" style="color:#e8a87c">Voir tous les articles</a>.
        <?php else: ?>
            Aucune actualit&eacute; pour le moment.
        <?php endif; ?>
    </p>
    <?php else: ?>
        <?php foreach ($articles as $a): ?>
        <?php
            $isStandby = $a['statut'] === 'standby';
            $isDraft   = $a['statut'] === 'draft';
            $canEdit   = $isAdmin && ($isStandby || ($isDraft && (int)$a['id_admin'] === (int)$_SESSION['id']));

            $hoursLeft = 0;
            if ($isStandby && $a['date_soumise']) {
                $elapsed   = (new DateTime())->getTimestamp() - (new DateTime($a['date_soumise']))->getTimestamp();
                $hoursLeft = max(0, 6 - (int)($elapsed / 3600));
            }
        ?>
        <div class="nl-card" id="article-<?= (int)$a['id'] ?>">
            <div class="nl-header">
                <div class="nl-title">
                    <?= htmlspecialchars($a['titre']) ?>
                    <?php if ($isAdmin && $isDraft): ?>
                        <span style="font-size:.7rem;background:#3a3a3a;color:#aaa;padding:.2rem .5rem;border-radius:4px;margin-left:.5rem;vertical-align:middle">Brouillon</span>
                    <?php elseif ($isAdmin && $isStandby): ?>
                        <span style="font-size:.7rem;background:#4a2e00;color:#ffb347;padding:.2rem .5rem;border-radius:4px;margin-left:.5rem;vertical-align:middle">
                            Standby &mdash; envoi dans <?= $hoursLeft ?>h
                        </span>
                    <?php endif; ?>
                </div>
                <div class="nl-meta">
                    <?php $aSrc = $a['avatar'] ? '../assets/img/avatars/' . htmlspecialchars($a['avatar']) : '../assets/img/usrplaceholder.png'; ?>
                    <a href="../compte/profil.php?id=<?= (int)$a['id_admin'] ?>"><img src="<?= $aSrc ?>" alt="" style="width:22px;height:22px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:.3rem"></a>
                    Par <strong><a href="../compte/profil.php?id=<?= (int)$a['id_admin'] ?>" style="color:#e8a87c;text-decoration:none"><?= htmlspecialchars($a['login']) ?></a></strong>
                    <?php if ($a['statut'] === 'published' && $a['date_pub']): ?>
                        &middot; <?= date('d/m/Y', strtotime($a['date_pub'])) ?>
                    <?php elseif ($isStandby && $a['date_soumise']): ?>
                        &middot; soumis le <?= date('d/m/Y - H\hi', strtotime($a['date_soumise'])) ?>
                    <?php endif; ?>
                    <?php if ($isAdmin): ?>
                    <span style="margin-left:.8rem">
                        <?php if ($canEdit): ?>
                        <a href="index.php?edit=<?= (int)$a['id'] ?>" class="btn-forum-new" style="font-size:.75rem;padding:.2rem .6rem">Modifier</a>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;margin-left:.4rem" onsubmit="return confirm('Supprimer cet article ?')">
                            <input type="hidden" name="delete_article" value="1">
                            <input type="hidden" name="article_id" value="<?= (int)$a['id'] ?>">
                            <button type="submit" class="btn-delete">Supprimer</button>
                        </form>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="nl-content"><?= nl2br(htmlspecialchars($a['contenu'])) ?></div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php 
include __DIR__ . '/../includes/footer.php'; ?>