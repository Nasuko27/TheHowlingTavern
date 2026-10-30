<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
include_once 'protect_verified.php';
include_once 'logger.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: forum.php'); exit(); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote'])) {
    $vote = ((int)$_POST['vote'] === 1) ? 1 : -1;

    $stmt = $pdo->prepare("SELECT vote FROM FORUM_VOTE WHERE post_id=:pid AND user_id=:uid");
    $stmt->execute([':pid' => $id, ':uid' => $_SESSION['id']]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['vote'] == $vote) {
            $pdo->prepare("DELETE FROM FORUM_VOTE WHERE post_id=:pid AND user_id=:uid")
                ->execute([':pid' => $id, ':uid' => $_SESSION['id']]);
        } else {
            $pdo->prepare("UPDATE FORUM_VOTE SET vote=:v WHERE post_id=:pid AND user_id=:uid")
                ->execute([':v' => $vote, ':pid' => $id, ':uid' => $_SESSION['id']]);
        }
    } else {
        $pdo->prepare("INSERT INTO FORUM_VOTE (post_id,user_id,vote) VALUES(:pid,:uid,:v)")
            ->execute([':pid' => $id, ':uid' => $_SESSION['id'], ':v' => $vote]);
    }
    header("Location: forum_post.php?id=$id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $stmt = $pdo->prepare("SELECT id_user FROM FILLDISCUSSION WHERE id = :pid");
    $stmt->execute([':pid' => $id]);
    $target = $stmt->fetch();
    $isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';
    if ($target && ($target['id_user'] == $_SESSION['id'] || $isAdmin)) {
        $pdo->prepare("DELETE FROM FORUM_VOTE WHERE post_id = :pid")->execute([':pid' => $id]);
        $pdo->prepare("DELETE FROM MESSAGE WHERE id_filldiscussion = :pid")->execute([':pid' => $id]);
        $pdo->prepare("DELETE FROM FILLDISCUSSION WHERE id = :pid")->execute([':pid' => $id]);
    }
    header('Location: forum.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_post'])) {
    $titre   = trim($_POST['titre']   ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $stmt = $pdo->prepare("SELECT id_user FROM FILLDISCUSSION WHERE id = :pid");
    $stmt->execute([':pid' => $id]);
    $target = $stmt->fetch();
    if ($target && $target['id_user'] == $_SESSION['id'] && $titre !== '' && $contenu !== '') {
        $pdo->prepare("UPDATE FILLDISCUSSION SET nom = :nom, contenu = :contenu WHERE id = :pid")
            ->execute([':nom' => $titre, ':contenu' => $contenu, ':pid' => $id]);
    }
    header("Location: forum_post.php?id=$id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_message'])) {
    $mid = (int)($_POST['message_id'] ?? 0);
    if ($mid) {
        $stmt = $pdo->prepare("SELECT id_user FROM MESSAGE WHERE id = :mid AND id_filldiscussion = :pid");
        $stmt->execute([':mid' => $mid, ':pid' => $id]);
        $msg = $stmt->fetch();
        $isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';
        if ($msg && ($isAdmin || $msg['id_user'] == $_SESSION['id'])) {
            $pdo->prepare("DELETE FROM MESSAGE WHERE id = :mid")->execute([':mid' => $mid]);
        }
    }
    header("Location: forum_post.php?id=$id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_message'])) {
    $mid = (int)($_POST['message_id'] ?? 0);
    if ($mid) {
        $stmt = $pdo->prepare("SELECT id FROM MESSAGE WHERE id = :mid AND id_filldiscussion = :pid");
        $stmt->execute([':mid' => $mid, ':pid' => $id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("SELECT 1 FROM MESSAGE_VOTE WHERE message_id = :mid AND user_id = :uid");
            $stmt->execute([':mid' => $mid, ':uid' => $_SESSION['id']]);
            if ($stmt->fetch()) {
                $pdo->prepare("DELETE FROM MESSAGE_VOTE WHERE message_id = :mid AND user_id = :uid")
                    ->execute([':mid' => $mid, ':uid' => $_SESSION['id']]);
            } else {
                $pdo->prepare("INSERT INTO MESSAGE_VOTE (message_id, user_id) VALUES (:mid, :uid)")
                    ->execute([':mid' => $mid, ':uid' => $_SESSION['id']]);
            }
        }
    }
    header("Location: forum_post.php?id=$id");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['new_message'])) {
    $contenu = trim($_POST['contenu'] ?? '');
    if ($contenu !== '') {
        $pdo->prepare(
            "INSERT INTO MESSAGE (contenu, id_filldiscussion, id_user) VALUES (:contenu, :pid, :uid)"
        )->execute([':contenu' => $contenu, ':pid' => $id, ':uid' => $_SESSION['id']]);
    }
    header("Location: forum_post.php?id=$id");
    exit();
}

$stmt = $pdo->prepare(
    "SELECT fd.id, fd.id_user, fd.nom, fd.contenu, fd.datecreation, u.login, u.avatar,
            COALESCE(SUM(fv.vote), 0) AS score
     FROM FILLDISCUSSION fd
     JOIN USER u ON fd.id_user = u.id
     LEFT JOIN FORUM_VOTE fv ON fv.post_id = fd.id
     WHERE fd.id = :id
     GROUP BY fd.id, fd.id_user, fd.nom, fd.contenu, fd.datecreation, u.login, u.avatar"
);
$stmt->execute([':id' => $id]);
$post = $stmt->fetch();
if (!$post) { header('Location: forum.php'); exit(); }

$isAdmin   = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';
$canDelete = $isAdmin || $post['id_user'] == $_SESSION['id'];
$canEdit   = $post['id_user'] == $_SESSION['id'];
$editMode  = isset($_GET['edit']) && $canEdit;

$stmt = $pdo->prepare("SELECT vote FROM FORUM_VOTE WHERE post_id=:pid AND user_id=:uid");
$stmt->execute([':pid' => $id, ':uid' => $_SESSION['id']]);
$myVoteRow = $stmt->fetch();
$myVote = $myVoteRow ? (int)$myVoteRow['vote'] : 0;

$stmt = $pdo->prepare(
    "SELECT m.id, m.id_user, m.contenu, m.dateposte,
            COALESCE(u.login, 'Anonyme') AS login, u.avatar,
            COALESCE(lk.likes, 0)        AS likes,
            COALESCE(my.voted, 0)        AS i_liked
     FROM MESSAGE m
     LEFT JOIN USER u ON u.id = m.id_user
     LEFT JOIN (
         SELECT message_id, COUNT(*) AS likes
         FROM MESSAGE_VOTE GROUP BY message_id
     ) lk ON lk.message_id = m.id
     LEFT JOIN (
         SELECT message_id, 1 AS voted
         FROM MESSAGE_VOTE WHERE user_id = :uid
     ) my ON my.message_id = m.id
     WHERE m.id_filldiscussion = :pid
     ORDER BY m.dateposte DESC"
);
$stmt->execute([':uid' => $_SESSION['id'], ':pid' => $id]);
$messages = $stmt->fetchAll();

log_action('Forum', 'Discussion #' . $id);

function time_ago($dt = '') {
    $past = new DateTime($dt, new DateTimeZone('UTC'));
    $diff = time() - $past->getTimestamp();
    if ($diff < 60)     return '&agrave; l\'instant';
    if ($diff < 3600)   return floor($diff / 60) . ' min';
    if ($diff < 86400)  return floor($diff / 3600) . 'h';
    if ($diff < 604800) return floor($diff / 86400) . 'j';
    return date('d/m/Y', $past->getTimestamp());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title><?= htmlspecialchars($post['nom']) ?> - Forum</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=New+Rocker&family=Texturina:ital,opsz,wght@0,12..72,100..900;1,12..72,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" type="image/png" href="img/logo.png">
    <link href="styles.css" rel="stylesheet">
</head>
<body>

<?php include 'nav.php'; ?>

<div class="forum-container">

    <a href="forum.php" class="back-link">Retour au forum</a>

    <?php if ($editMode): ?>
    <div class="form-card">
        <h2>Modifier le post</h2>
        <form method="POST" action="forum_post.php?id=<?= $id ?>">
            <input type="hidden" name="update_post" value="1">
            <input type="text" name="titre" value="<?= htmlspecialchars($post['nom']) ?>" maxlength="150" required>
            <textarea name="contenu" required><?= htmlspecialchars($post['contenu']) ?></textarea>
            <div class="d-flex gap-2">
                <button type="submit" class="btn-forum-new">Enregistrer</button>
                <a href="forum_post.php?id=<?= $id ?>" class="btn-forum-new" style="background:#333;border-color:#555">Annuler</a>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="post-full">
        <div class="post-full-title"><?= htmlspecialchars($post['nom']) ?></div>
        <div class="post-meta" style="font-size:.8rem;color:#888">
            <?php $postAvSrc = $post['avatar'] ? 'img/avatars/' . htmlspecialchars($post['avatar']) : 'img/usrplaceholder.png'; ?>
            <a href="profile.php?id=<?= (int)$post['id_user'] ?>"><img src="<?= $postAvSrc ?>" alt="" style="width:24px;height:24px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:.3rem"></a>
            Post&eacute; par <strong><a href="profile.php?id=<?= (int)$post['id_user'] ?>" style="color:#e8a87c;text-decoration:none"><?= htmlspecialchars($post['login']) ?></a></strong>
            &middot; <?= time_ago($post['datecreation']) ?>
        </div>
        <div class="post-full-content"><?= htmlspecialchars($post['contenu']) ?></div>

        <div class="post-actions" style="margin-top:.8rem">
            <?php if ($canEdit): ?>
            <a href="forum_post.php?id=<?= $id ?>&edit=1" class="btn-edit">Modifier</a>
            <?php endif; ?>
            <?php if ($canDelete): ?>
            <form method="POST" onsubmit="return confirm('Supprimer ce post et tous ses commentaires ?')">
                <input type="hidden" name="delete_post" value="1">
                <button type="submit" class="btn-delete">Supprimer</button>
            </form>
            <?php endif; ?>
        </div>

        <div class="post-vote-row">
            <form method="POST">
                <input type="hidden" name="vote" value="1">
                <button type="submit" class="vote-btn <?= $myVote === 1 ? 'up active' : 'up' ?>">up</button>
            </form>
            <span class="vote-score" style="font-size:1.1rem"><?= (int)$post['score'] ?></span>
            <form method="POST">
                <input type="hidden" name="vote" value="-1">
                <button type="submit" class="vote-btn <?= $myVote === -1 ? 'down active' : 'down' ?>">dwn</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="comments-section">
        <h2><?= count($messages) ?> r&eacute;ponse<?= count($messages) != 1 ? 's' : '' ?></h2>

        <div class="form-card" style="margin-bottom:1.5rem">
            <form method="POST">
                <input type="hidden" name="new_message" value="1">
                <textarea name="contenu" plasceholder="&Eacute;crire une r&eacute;ponse&hellip;" required style="margin-bottom:.6rem"></textarea>
                <button type="submit" class="btn-forum-new">R&eacute;pondre</button>
            </form>
        </div>

        <?php if (empty($messages)): ?>
            <p style="color:#666;font-size:.9rem">Aucune r&eacute;ponse pour l'instant.</p>
        <?php else: ?>
            <?php foreach ($messages as $m): ?>
            <div class="comment-card">
                <div class="comment-author">
                    <?php $mAvSrc = $m['avatar'] ? 'img/avatars/' . htmlspecialchars($m['avatar']) : 'img/usrplaceholder.png'; ?>
                    <a href="profile.php?id=<?= (int)$m['id_user'] ?>"><img src="<?= $mAvSrc ?>" alt="" style="width:18px;height:18px;border-radius:50%;object-fit:cover;vertical-align:middle;margin-right:.2rem"></a>
                    <a href="profile.php?id=<?= (int)$m['id_user'] ?>" style="color:#e8a87c;text-decoration:none"><?= htmlspecialchars($m['login']) ?></a>
                    <span style="color:#666;font-weight:normal"> . <?= time_ago($m['dateposte']) ?></span>
                    <form method="POST" style="display:inline;margin-left:.5rem">
                        <input type="hidden" name="vote_message" value="1">
                        <input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>">
                        <button type="submit" class="btn-msg-vote <?= $m['i_liked'] ? 'liked' : '' ?>">
                            &#9829; <?= (int)$m['likes'] ?>
                        </button>
                    </form>
                    <?php if ($isAdmin || $m['id_user'] == $_SESSION['id']): ?>
                    <form method="POST" style="display:inline;margin-left:.3rem" onsubmit="return confirm('Supprimer cette r&eacute;ponse ?')">
                        <input type="hidden" name="delete_message" value="1">
                        <input type="hidden" name="message_id" value="<?= (int)$m['id'] ?>">
                        <button type="submit" class="btn-delete">Supprimer</button>
                    </form>
                    <?php endif; ?>
                </div>
                <div class="comment-text"><?= htmlspecialchars($m['contenu']) ?></div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>


<?php 
include "footer.php"; ?>
