<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
include_once __DIR__ . '/includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/includes/logger.php';

$isAdmin  = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';
$loggedIn = isset($_SESSION['id']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : ($loggedIn ? (int)$_SESSION['id'] : 0);
if ($id <= 0) { header('Location: login.php'); exit(); }

$isOwner = $loggedIn && (int)$_SESSION['id'] === $id;

$flash = null;
if (isset($_SESSION['profile_flash'])) {
    $flash = $_SESSION['profile_flash'];
    unset($_SESSION['profile_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar']) && $isOwner) {
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => '.jpg', 'image/png' => '.png'];
        $mime = mime_content_type($_FILES['avatar']['tmp_name']);
        if (isset($allowed[$mime]) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
            $dir = __DIR__ . '/assets/img/avatars';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $ext = $allowed[$mime];
            $filename = $id . $ext;
            $old = $pdo->prepare("SELECT avatar FROM USER WHERE id = :id");
            $old->execute([':id' => $id]);
            $oldAvatar = $old->fetchColumn();
            if ($oldAvatar && file_exists(__DIR__ . '/assets/img/avatars/' . $oldAvatar)) {
                unlink(__DIR__ . '/assets/img/avatars/' . $oldAvatar);
            }
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . '/' . $filename)) {
                $pdo->prepare("UPDATE USER SET avatar = :a WHERE id = :id")
                    ->execute([':a' => $filename, ':id' => $id]);
            }
        }
    }
    header('Location: profile.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_bio']) && $isOwner) {
    $bio = trim($_POST['bio'] ?? '');
    if (mb_strlen($bio) > 500) $bio = mb_substr($bio, 0, 500);
    $pdo->prepare("UPDATE USER SET bio = :b WHERE id = :id")
        ->execute([':b' => $bio, ':id' => $id]);
    header('Location: profile.php?id=' . $id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_infos']) && $isOwner) {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $datenaissance = $_POST['date_naissance'] ?? '';

    if ($pseudo === '') {
        $_SESSION['profile_flash'] = ['type' => 'danger', 'msg' => 'Le pseudo ne peut pas ?tre vide.'];
    } else {
        $check = $pdo->prepare("SELECT id FROM USER WHERE login = :l AND id != :id");
        $check->execute([':l' => $pseudo, ':id' => $id]);
        if ($check->fetch()) {
            $_SESSION['profile_flash'] = ['type' => 'danger', 'msg' => 'Ce pseudo est d?j? utilis?.'];
        } else {
            $pdo->prepare("UPDATE USER SET login = :l, nom = :n, prenom = :p, date_naissance = :d WHERE id = :id")
                ->execute([':l' => $pseudo, ':n' => $nom, ':p' => $prenom, ':d' => $datenaissance, ':id' => $id]);
            $_SESSION['login'] = $pseudo;
            $_SESSION['profile_flash'] = ['type' => 'success', 'msg' => 'Informations mises ? jour.'];
        }
    }
    header('Location: profile.php?id=' . $id . '&settings=1');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_email']) && $isOwner) {
    $newEmail = trim($_POST['new_email'] ?? '');
    if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['profile_flash'] = ['type' => 'danger', 'msg' => 'Adresse email invalide.'];
    } else {
        $check = $pdo->prepare("SELECT id FROM USER WHERE email = :e AND id != :id");
        $check->execute([':e' => $newEmail, ':id' => $id]);
        if ($check->fetch()) {
            $_SESSION['profile_flash'] = ['type' => 'danger', 'msg' => 'Cette adresse email est d?j? utilis?e.'];
        } else {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $_SESSION['email_change_code'] = $code;
            $_SESSION['email_change_new']  = $newEmail;

            include_once __DIR__ . '/includes/mailer.php';
            $result = send_verification_mail($newEmail, $_SESSION['login'], $code);
            if ($result === true) {
                $_SESSION['profile_flash'] = ['type' => 'success', 'msg' => 'Code de v?rification envoy? ? ' . $newEmail];
            } else {
                $_SESSION['profile_flash'] = ['type' => 'warning', 'msg' => 'Erreur d\'envoi. Ton code : ' . $code];
            }
        }
    }
    header('Location: profile.php?id=' . $id . '&settings=1');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_email']) && $isOwner) {
    $code = trim($_POST['email_code'] ?? '');
    if (isset($_SESSION['email_change_code']) && $code === $_SESSION['email_change_code']) {
        $pdo->prepare("UPDATE USER SET email = :e WHERE id = :id")
            ->execute([':e' => $_SESSION['email_change_new'], ':id' => $id]);
        unset($_SESSION['email_change_code'], $_SESSION['email_change_new']);
        $_SESSION['profile_flash'] = ['type' => 'success', 'msg' => 'Adresse email mise ? jour.'];
    } else {
        $_SESSION['profile_flash'] = ['type' => 'danger', 'msg' => 'Code incorrect.'];
    }
    header('Location: profile.php?id=' . $id . '&settings=1');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password']) && $isOwner) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT mdp FROM USER WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $_SESSION['profile_flash'] = ['type' => 'danger', 'msg' => 'Mot de passe actuel incorrect.'];
    } elseif (strlen($new) < 6) {
        $_SESSION['profile_flash'] = ['type' => 'danger', 'msg' => 'Le nouveau mot de passe doit faire au moins 6 caract?res.'];
    } elseif ($new !== $confirm) {
        $_SESSION['profile_flash'] = ['type' => 'danger', 'msg' => 'Les mots de passe ne correspondent pas.'];
    } else {
        $pdo->prepare("UPDATE USER SET mdp = :m WHERE id = :id")
            ->execute([':m' => password_hash($new, PASSWORD_DEFAULT), ':id' => $id]);
        $_SESSION['profile_flash'] = ['type' => 'success', 'msg' => 'Mot de passe mis ? jour.'];
    }
    header('Location: profile.php?id=' . $id . '&settings=1');
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, login, bio, avatar, status, date_naissance, email, nom, prenom FROM USER WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    $stmt = $pdo->prepare("SELECT id, login, status, date_naissance, email, nom, prenom FROM USER WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
}

if (!$user) {
    header('Location: index.php');
    exit();
}

if (!isset($user['bio']))    $user['bio']    = null;
if (!isset($user['avatar'])) $user['avatar'] = null;

$avatarSrc = $user['avatar'] ? 'assets/img/avatars/' . htmlspecialchars($user['avatar']) : 'assets/img/usrplaceholder.png';

$postCount = 0;
$commentCount = 0;
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM FILLDISCUSSION WHERE id_user = :id");
    $stmt->execute([':id' => $id]);
    $postCount = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM MESSAGE WHERE id_user = :id");
    $stmt->execute([':id' => $id]);
    $commentCount = (int)$stmt->fetchColumn();
} catch (PDOException $e) {}

$showSettings = isset($_GET['settings']) && $isOwner;

log_action('Profil', 'Modification #' . $id);
?>
<?php include __DIR__ . '/includes/header.php'; ?>
<body>
<?php include __DIR__ . '/includes/nav.php'; ?>

<div class="forum-container" style="max-width:700px">

    <?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" style="border:1px solid #620D14;background:rgb(36 9 2 / 0.92);color:<?= $flash['type'] === 'danger' ? '#ff6b6b' : ($flash['type'] === 'warning' ? '#ffb347' : '#6bcf6b') ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
    <?php endif; ?>

    <div class="comphero p-4 mb-4" style="border-width:3px">
        <div class="d-flex gap-4 align-items-start">

            <div class="d-flex flex-column align-items-center gap-2" style="min-width:140px">
                <img src="<?= $avatarSrc ?>" alt="Avatar"
                     style="width:130px;height:130px;object-fit:cover;border-radius:50%;border:3px solid #620D14">
                <?php if ($isOwner): ?>
                <form method="POST" enctype="multipart/form-data" class="d-flex flex-column align-items-center gap-1" style="width:100%">
                    <input type="hidden" name="upload_avatar" value="1">
                    <label class="btn-forum-new" style="font-size:.7rem;padding:.25rem .6rem;cursor:pointer;text-align:center">
                        Changer l&rsquo;avatar
                        <input type="file" name="avatar" accept="image/jpeg,image/png" hidden
                               onchange="this.closest('form').submit()">
                    </label>
                </form>
                <?php endif; ?>
            </div>

            <div class="flex-grow-1">
                <h1 style="font-size:1.8rem;margin-bottom:.3rem"><?= htmlspecialchars($user['login']) ?></h1>

                <?php if ((string)$user['status'] === '1'): ?>
                    <span style="background:#0682ef;padding:2px 8px;border-radius:4px;color:#fff;font-size:.75rem;font-family:'New Rocker',system-ui">Admin</span>
                <?php elseif ((string)$user['status'] === '2'): ?>
                    <span style="background:#bc2626;padding:2px 8px;border-radius:4px;color:#fff;font-size:.75rem;font-family:'New Rocker',system-ui">Banni</span>
                <?php endif; ?>
                <?php if ($isAdmin && !$isOwner): ?>
                    <a href="admin/edituser.php?id=<?= (int)$user['id'] ?>" class="btn-forum-new" style="font-size:.7rem;padding:.2rem .6rem;margin-left:.5rem;vertical-align:middle">Configurer</a>
                <?php endif; ?>

                <div style="margin-top:1rem;color:#ccc;font-size:.92rem;line-height:1.6">
                    <?php if ($isOwner && isset($_GET['edit_bio'])): ?>
                    <form method="POST">
                        <input type="hidden" name="update_bio" value="1">
                        <textarea name="bio" maxlength="500" rows="4"
                                  style="width:100%;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.5rem;font-family:'Texturina',serif;font-size:.9rem;resize:vertical"
                        ><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                        <div class="d-flex gap-2 mt-2">
                            <button type="submit" class="btn-forum-new" style="font-size:.8rem;padding:.3rem .8rem">Sauvegarder</button>
                            <a href="profile.php?id=<?= $id ?>" class="btn-forum-new" style="font-size:.8rem;padding:.3rem .8rem;background:#333;border-color:#555">Annuler</a>
                        </div>
                    </form>
                    <?php else: ?>
                        <?php if (!empty($user['bio'])): ?>
                            <p style="margin:0;white-space:pre-line"><?= htmlspecialchars($user['bio']) ?></p>
                        <?php else: ?>
                            <p style="margin:0;color:#666;font-style:italic">Aucune description pour le moment.</p>
                        <?php endif; ?>
                        <?php if ($isOwner): ?>
                        <div class="d-flex gap-3 mt-2">
                            <a href="profile.php?id=<?= $id ?>&edit_bio=1" style="color:#e8a87c;font-size:.8rem;text-decoration:none"><?= empty($user['bio']) ? 'Ajouter une bio' : 'Modifier la bio' ?></a>
                            <a href="profile.php?id=<?= $id ?>&settings=1" style="color:#e8a87c;font-size:.8rem;text-decoration:none">Param&egrave;tres</a>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if ($showSettings): ?>

    <div class="form-card">
        <h2>Informations personnelles</h2>
        <form method="POST">
            <input type="hidden" name="update_infos" value="1">
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Pseudo</label>
            <input type="text" name="pseudo" value="<?= htmlspecialchars($user['login']) ?>" maxlength="50" required>
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Nom</label>
            <input type="text" name="nom" value="<?= htmlspecialchars($user['nom'] ?? '') ?>" maxlength="100">
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Pr&eacute;nom</label>
            <input type="text" name="prenom" value="<?= htmlspecialchars($user['prenom'] ?? '') ?>" maxlength="100">
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Date de naissance</label>
            <input type="date" name="date_naissance" value="<?= htmlspecialchars($user['date_naissance'] ?? '') ?>">
            <button type="submit" class="btn-forum-new" style="margin-top:.5rem">Enregistrer</button>
        </form>
    </div>

    <div class="form-card">
        <h2>Adresse email</h2>
        <p style="color:#aaa;font-size:.85rem;margin-bottom:1rem">
            Email actuel : <strong style="color:#e8a87c"><?= htmlspecialchars($user['email'] ?? '') ?></strong>
        </p>
        <?php if (isset($_SESSION['email_change_code'])): ?>
        <form method="POST">
            <input type="hidden" name="verify_email" value="1">
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Code de v&eacute;rification (6 chiffres)</label>
            <input type="text" name="email_code" maxlength="6" pattern="\d{6}" placeholder="000000" required
                   style="text-align:center;font-size:1.3rem;letter-spacing:.3rem;max-width:200px">
            <div class="d-flex gap-2" style="margin-top:.5rem">
                <button type="submit" class="btn-forum-new">V&eacute;rifier</button>
            </div>
        </form>
        <p style="color:#888;font-size:.75rem;margin-top:.5rem">Code envoy&eacute; &agrave; <?= htmlspecialchars($_SESSION['email_change_new'] ?? '') ?></p>
        <?php else: ?>
        <form method="POST">
            <input type="hidden" name="update_email" value="1">
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Nouvel email</label>
            <input type="email" name="new_email" placeholder="nouveau@email.com" required>
            <button type="submit" class="btn-forum-new" style="margin-top:.5rem">Changer l&rsquo;email</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="form-card">
        <h2>Mot de passe</h2>
        <form method="POST">
            <input type="hidden" name="update_password" value="1">
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Mot de passe actuel</label>
            <input type="password" name="current_password" required>
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Nouveau mot de passe</label>
            <input type="password" name="new_password" minlength="6" required>
            <label style="display:block;color:#aaa;font-size:.8rem;margin-bottom:.2rem">Confirmer le nouveau mot de passe</label>
            <input type="password" name="confirm_password" minlength="6" required>
            <button type="submit" class="btn-forum-new" style="margin-top:.5rem">Changer le mot de passe</button>
        </form>
    </div>

    <p style="text-align:center;margin-bottom:2rem">
        <a href="profile.php?id=<?= $id ?>" class="btn-forum-new" style="background:#333;border-color:#555">&larr; Retour au profil</a>
    </p>

    <?php else: ?>

    <div class="comphero p-4 mb-4" style="border-width:3px">
        <h2 style="font-family:'New Rocker',system-ui;font-size:1.3rem;margin-bottom:1rem">Statistiques</h2>
        <div class="d-flex gap-4 flex-wrap">
            <div style="text-align:center;min-width:100px">
                <div style="font-size:2rem;font-family:'New Rocker',system-ui;color:#e8a87c"><?= $postCount ?></div>
                <div style="font-size:.8rem;color:#aaa">Sujets</div>
            </div>
            <div style="text-align:center;min-width:100px">
                <div style="font-size:2rem;font-family:'New Rocker',system-ui;color:#e8a87c"><?= $commentCount ?></div>
                <div style="font-size:.8rem;color:#aaa">Messages</div>
            </div>
        </div>
    </div>

    <?php endif; ?>

</div>

<?php 
include __DIR__ . '/includes/footer.php'; ?>