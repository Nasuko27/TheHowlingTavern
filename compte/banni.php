<?php
ob_start();
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id']) || !isset($_SESSION['status']) || (int)$_SESSION['status'] !== 2) {
    header('Location: ../index.php');
    exit();
}

$flash = null;
if (isset($_SESSION['ban_flash'])) {
    $flash = $_SESSION['ban_flash'];
    unset($_SESSION['ban_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_message'])) {
    $contenu = trim($_POST['contenu'] ?? '');
    $mail = trim($_POST['mail'] ?? '');
    if ($contenu !== '') {
        $titre = 'Demande de débannissement - ' . ($_SESSION['login'] ?? 'Utilisateur #' . $_SESSION['id']);
        $pdo->prepare("INSERT INTO TICKET (titre, contenu, mail) VALUES (:titre, :contenu, :mail)")
            ->execute([':titre' => $titre, ':contenu' => $contenu, ':mail' => $mail]);
        $_SESSION['ban_flash'] = ['type' => 'success', 'msg' => 'Message envoyé. Un administrateur examinera votre demande.'];
    }
    header('Location: banni.php');
    exit();
}

include __DIR__ . '/../includes/header.php';
?>

<body>
<div class="centered-popup" style="background-image: url(../assets/img/background.jpg); background-size: 100% 100%; font-family: 'New Rocker', system-ui; background-attachment: fixed;">
    <div class="popup p-5" style="max-width:600px;width:90%">
        <h1 style="text-align:center;color:#c0392b;font-size:2rem">Compte banni</h1>
        <p style="text-align:center;color:#aaa;font-family:'Texturina',serif;margin-bottom:1.5rem">
            Votre compte a &eacute;t&eacute; suspendu. Si vous pensez qu'il s'agit d'une erreur, vous pouvez contacter un administrateur.
        </p>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>" style="font-family:'Texturina',serif"><?= htmlspecialchars($flash['msg']) ?></div>
        <?php endif; ?>

        <form method="POST">
            <textarea name="contenu" placeholder="Expliquez votre situation..." rows="4" style="width:100%;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.6rem;font-family:'Texturina',serif;font-size:.9rem;margin-bottom:.8rem;box-sizing:border-box" required></textarea>
            <input type="email" name="mail" placeholder="Votre email (optionnel)" style="width:100%;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.6rem;font-family:'Texturina',serif;font-size:.9rem;margin-bottom:.8rem;box-sizing:border-box">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <a href="logout.php" class="btn btn-outline-danger btn-sm">D&eacute;connexion</a>
                <button type="submit" name="envoyer_message" class="btn btn-sm" style="background:#620D14;color:#fff;border:1px solid #8b1a1a;font-family:'New Rocker',system-ui">Contacter un admin</button>
            </div>
        </form>
    </div>
</div>
</body>
</html>
