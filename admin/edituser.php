<?php
include_once __DIR__ . '/../includes/protect_admin.php';
include_once __DIR__ . '/../includes/config.php';
include_once __DIR__ . '/../includes/header.php';
include_once __DIR__ . '/../includes/adminnav.php';
 
$user    = null;
$error   = null;
$success = null;
 
$id = isset($_GET['id']) ? (int) trim($_GET['id']) : 0;
 
if ($id <= 0) {
    $error = "ID invalide.";
}
 
if (!$error && isset($_GET['action']) && $_GET['action'] === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM USER WHERE id = :id");
    $stmt->execute([':id' => $id]);
    header("Location: listuser.php");
    exit();
}

if (!$error && isset($_GET['action']) && $_GET['action'] === 'reset_avatar') {
    $stmt = $pdo->prepare("SELECT avatar FROM USER WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $oldAvatar = $stmt->fetchColumn();

    if ($oldAvatar && file_exists(__DIR__ . '/../assets/img/avatars/' . $oldAvatar)) {
        unlink(__DIR__ . '/../assets/img/avatars/' . $oldAvatar);
    }

    $pdo->prepare("UPDATE USER SET avatar = NULL WHERE id = :id")->execute([':id' => $id]);
    header("Location: edituser.php?id=" . $id);
    exit();
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $login  = trim($_POST['login']  ?? '');
    $status = trim($_POST['status'] ?? '');

    $bio = trim($_POST['bio'] ?? '');
    if (mb_strlen($bio) > 500) $bio = mb_substr($bio, 0, 500);

    if ($login === '') {
        $error = "Le pseudo ne peut pas être vide.";
    } else {
        $stmt = $pdo->prepare("UPDATE USER SET login = :login, status = :status, bio = :bio WHERE id = :id");
        $stmt->execute([':login' => $login, ':status' => $status, ':bio' => $bio, ':id' => $id]);
        $success = "Modifications enregistrées.";
        }
}
 
if (!$error) {
    $stmt = $pdo->prepare("SELECT id, login, email, status, nom, prenom, bio, avatar FROM USER WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
 
    if (!$user) {
        $error = "Utilisateur introuvable.";
    } else {
        $avatarSrc = $user['avatar'] ? '../assets/img/avatars/' . htmlspecialchars($user['avatar']) : '../assets/img/usrplaceholder.png';
    }
}

log_action('Administration');

?>
 
<h1>Configuration du membre</h1>
<p style="color:#aaa;font-family:'Texturina',serif;font-size:.95rem;margin-top:-.8rem;margin-bottom:1.2rem">Modifier les informations et le statut de l&rsquo;aventurier</p>

<a href="listuser.php" class="btn btn-sm btn-outline-secondary mb-3">&larr; Retour à la liste</a>

<div style="max-width:600px;width:100%">

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php else: ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="form-card">
            <h2><?= htmlspecialchars($user['login']) ?></h2>
            <form method="post" action="edituser.php?id=<?= $id ?>">
                <div class="wrap">
                    <table class="table table-bordered mb-0">
                        <tbody>
                            <tr>
                                <th style="width:140px">ID</th>
                                <td><?= (int)$user['id'] ?></td>
                            </tr>

                            <tr>
                                <th>Avatar</th>
                                <td>
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <img src="<?= $avatarSrc ?>" alt="Avatar" class="rounded-circle" width="70" height="70">
                                        <?php if ($user['avatar']): ?>
                                            <a href="edituser.php?id=<?= $id ?>&action=reset_avatar" class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('R&eacute;initialiser l\'avatar de <?= htmlspecialchars($user['login'], ENT_QUOTES) ?> ?')">Réinitialiser l&rsquo;avatar</a>
                                        <?php else: ?>
                                            <span class="text-muted fst-italic">Avatar par défaut</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>Pseudo</th>
                                <td><input type="text" name="login" value="<?= htmlspecialchars($user['login']) ?>"></td>
                            </tr>
                            <tr>
                                <th>Pr&eacute;nom</th>
                                <td><?= htmlspecialchars($user['prenom']) ?></td>
                            </tr>
                            <tr>
                                <th>Nom</th>
                                <td><?= htmlspecialchars($user['nom']) ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?= htmlspecialchars($user['email'] ?? '&mdash;') ?></td>
                            </tr>
                            <tr>
                                <th>Statut</th>
                                <td>
                                    <div class="status-options">
                                        <label class="status-option">
                                            <input type="radio" name="status" value="0" <?= $user['status'] == 0 ? 'checked' : '' ?>>
                                            <span style="background:#26bc26;padding:2px 8px;border-radius:4px;color:#fff;font-size:.8rem">User</span>
                                        </label>
                                        <label class="status-option">
                                            <input type="radio" name="status" value="1" <?= $user['status'] == 1 ? 'checked' : '' ?>>
                                            <span style="background:#0682ef;padding:2px 8px;border-radius:4px;color:#fff;font-size:.8rem">Admin</span>
                                        </label>
                                        <label class="status-option">
                                            <input type="radio" name="status" value="2" <?= $user['status'] == 2 ? 'checked' : '' ?>>
                                            <span style="background:#bc2626;padding:2px 8px;border-radius:4px;color:#fff;font-size:.8rem">Banni</span>
                                        </label>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th>Bio</th>
                                <td>
                                    <textarea name="bio" rows="3" maxlength="500" class="form-control"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-sm btn-outline-warning">Enregistrer les modifications</button>
                    <a href="edituser.php?id=<?= $id ?>&action=delete" class="btn btn-sm btn-outline-danger"
                       onclick="return confirm('Supprimer <?= htmlspecialchars($user['login'], ENT_QUOTES) ?> ? Pas de retour en arri&egrave;re !')">Supprimer cet utilisateur</a>
                </div>
            </form>
        </div>

    <?php endif; ?>

</div>
  
</body>
</html>
