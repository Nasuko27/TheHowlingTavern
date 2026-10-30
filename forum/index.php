<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
include_once __DIR__ . '/../includes/protect_verified.php';
include_once __DIR__ . '/../includes/logger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_post'])) {
    $titre   = trim($_POST['titre']   ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    if ($titre !== '' && $contenu !== '') {
        $stmt = $pdo->prepare(
            "INSERT INTO FILLDISCUSSION (nom, contenu, datecreation, id_user)
             VALUES (:nom, :contenu, NOW(), :uid)"
        );
        $stmt->execute([':nom' => $titre, ':contenu' => $contenu, ':uid' => $_SESSION['id']]);
    }
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $pid = (int)($_POST['post_id'] ?? 0);
    if ($pid) {
        $stmt = $pdo->prepare("SELECT id_user FROM FILLDISCUSSION WHERE id = :pid");
        $stmt->execute([':pid' => $pid]);
        $target = $stmt->fetch();
        $isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';
        if ($target && ($target['id_user'] == $_SESSION['id'] || $isAdmin)) {
            $pdo->prepare("DELETE FROM FORUM_VOTE WHERE post_id = :pid")->execute([':pid' => $pid]);
            $pdo->prepare("DELETE FROM MESSAGE WHERE id_filldiscussion = :pid")->execute([':pid' => $pid]);
            $pdo->prepare("DELETE FROM FILLDISCUSSION WHERE id = :pid")->execute([':pid' => $pid]);
        }
    }
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $pid  = (int)($_POST['post_id'] ?? 0);
    $vote = ((int)$_POST['vote'] === 1) ? 1 : -1;

    $stmt = $pdo->prepare("SELECT vote FROM FORUM_VOTE WHERE post_id=:pid AND user_id=:uid");
    $stmt->execute([':pid' => $pid, ':uid' => $_SESSION['id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['vote'] == $vote) {
            $pdo->prepare("DELETE FROM FORUM_VOTE WHERE post_id=:pid AND user_id=:uid")
                ->execute([':pid' => $pid, ':uid' => $_SESSION['id']]);
        } else {
            $pdo->prepare("UPDATE FORUM_VOTE SET vote=:v WHERE post_id=:pid AND user_id=:uid")
                ->execute([':v' => $vote, ':pid' => $pid, ':uid' => $_SESSION['id']]);
        }
    } else {
        $pdo->prepare("INSERT INTO FORUM_VOTE (post_id,user_id,vote) VALUES(:pid,:uid,:v)")
            ->execute([':pid' => $pid, ':uid' => $_SESSION['id'], ':v' => $vote]);
    }
    header('Location: index.php');
    exit();
}

$posts = $pdo->query(
    "SELECT fd.id, fd.id_user, fd.nom, fd.datecreation, u.login, u.avatar,
            COALESCE(v.score, 0)       AS score,
            COALESCE(mc.nb_messages, 0) AS nb_messages
     FROM FILLDISCUSSION fd
     JOIN USER u ON fd.id_user = u.id
     LEFT JOIN (
         SELECT post_id, SUM(vote) AS score
         FROM FORUM_VOTE
         GROUP BY post_id
     ) v  ON v.post_id = fd.id
     LEFT JOIN (
         SELECT id_filldiscussion, COUNT(*) AS nb_messages
         FROM MESSAGE
         GROUP BY id_filldiscussion
     ) mc ON mc.id_filldiscussion = fd.id
     ORDER BY score DESC, fd.datecreation DESC"
)->fetchAll();

$myVotes = [];
if ($posts) {
    $ids = implode(',', array_map(function($p) { return (int)$p['id']; }, $posts));
    $rows = $pdo->query(
        "SELECT post_id, vote FROM FORUM_VOTE
         WHERE user_id = {$_SESSION['id']} AND post_id IN ($ids)"
    )->fetchAll();
    foreach ($rows as $r) $myVotes[$r['post_id']] = (int)$r['vote'];
}

log_action('Forum', 'Liste discussions');

function time_ago($dt = '') {
    $past = new DateTime($dt, new DateTimeZone('UTC'));
    $diff = time() - $past->getTimestamp();
    if ($diff < 60)     return '&agrave; l\'instant';
    if ($diff < 3600)   return floor($diff / 60) . ' min';
    if ($diff < 86400)  return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'j';
    return date('d/m/Y', $past->getTimestamp());
}

$showForm = isset($_GET['new']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>Forum &ndash; The Howling Tavern</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=New+Rocker&family=Texturina:ital,opsz,wght@0,12..72,100..900;1,12..72,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>

<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="forum-container">

    <div class="forum-header">
        <h1>La Taverne &mdash; Forum</h1>
        <a href="index.php?new=1" class="btn-forum-new">Nouveau post</a>
    </div>

    <?php if ($showForm): ?>
    <div class="form-card">
        <h2>Cr&eacute;er une discussion</h2>
        <form method="POST" action="index.php">
            <input type="hidden" name="new_post" value="1">
            <input type="text" name="titre" placeholder="Titre" maxlength="150" required>
            <textarea name="contenu" placeholder="&Eacute;cris ton message&hellip;" required></textarea>
            <div class="d-flex gap-2">
                <button type="submit" class="btn-forum-new">Poster</button>
                <a href="index.php" class="btn-forum-new" style="background:#333;border-color:#555">Annuler</a>
            </div>
        </form>
    </div>
    <?php endif; ?>

    <?php if (empty($posts)): ?>
        <p style="color:#888;text-align:center;margin-top:3rem">Aucune discussion pour l'instant. Sois le premier !</p>
    <?php else: ?>
        <?php foreach ($posts as $p): ?>
        <?php
            $myVote    = $myVotes[$p['id']] ?? 0;
            $upClass   = $myVote === 1  ? 'up active'   : 'up';
            $downClass = $myVote === -1 ? 'down active' : 'down';
            $isAdmin   = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';
            $canDelete = $isAdmin || $p['id_user'] == $_SESSION['id'];
            $canEdit   = $p['id_user'] == $_SESSION['id'];
        ?>
        <div class="post-card">
            <div class="vote-col">
                <form method="POST" action="index.php">
                    <input type="hidden" name="vote" value="1">
                    <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="vote-btn <?= $upClass ?>">up</button>
                </form>
                <span class="vote-score"><?= (int)$p['score'] ?></span>
                <form method="POST" action="index.php">
                    <input type="hidden" name="vote" value="-1">
                    <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="vote-btn <?= $downClass ?>">dwn</button>
                </form>
            </div>
            <div class="post-body">
                <a href="post.php?id=<?= (int)$p['id'] ?>" class="post-title">
                    <?= htmlspecialchars($p['nom']) ?>
                </a>
                <div class="post-meta">
                    <?php $pAvSrc = $p['avatar'] ? '../assets/img/avatars/' . htmlspecialchars($p['avatar']) : '../assets/img/usrplaceholder.png'; ?>
                    <a href="../compte/profil.php?id=<?= (int)$p['id_user'] ?>"><img src="<?= $pAvSrc ?>" alt="" style="width:20px;height:20px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:.2rem"></a>
                    Post&eacute; par <strong><a href="../compte/profil.php?id=<?= (int)$p['id_user'] ?>" style="color:#e8a87c;text-decoration:none"><?= htmlspecialchars($p['login']) ?></a></strong>
                    &middot; <?= time_ago($p['datecreation']) ?>
                </div>
                <div class="post-actions">
                    <a href="post.php?id=<?= (int)$p['id'] ?>" class="post-comments">
                        <?= (int)$p['nb_messages'] ?> r&eacute;ponse<?= $p['nb_messages'] != 1 ? 's' : '' ?>
                    </a>
                    <?php if ($canEdit): ?>
                    <a href="post.php?id=<?= (int)$p['id'] ?>&edit=1" class="btn-edit">Modifier</a>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                    <form method="POST" action="index.php" onsubmit="return confirm('Supprimer ce post ?')">
                        <input type="hidden" name="delete_post" value="1">
                        <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
                        <button type="submit" class="btn-delete">Supprimer</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php 
include __DIR__ . '/../includes/footer.php'; ?>
