<?php
ob_start();
include_once 'config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include_once 'logger.php';
log_action('Ticket', 'Formulaire');

$flash = null;
if (isset($_SESSION['ticket_flash'])) {
    $flash = $_SESSION['ticket_flash'];
    unset($_SESSION['ticket_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['envoyer_ticket'])) {
    $titre   = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');
    $mail    = trim($_POST['mail'] ?? '');
    if ($titre !== '' && $contenu !== '') {
        $pdo->prepare("INSERT INTO TICKET (titre, contenu, mail) VALUES (:titre, :contenu, :mail)")
            ->execute([':titre' => $titre, ':contenu' => $contenu, ':mail' => $mail]);
        log_action('Ticket', 'Envoi : ' . $titre);
        $_SESSION['ticket_flash'] = ['type' => 'success', 'msg' => 'Ticket envoyé, merci !'];
    } else {
        $_SESSION['ticket_flash'] = ['type' => 'danger', 'msg' => 'Le titre et le contenu sont obligatoires.'];
    }
    header('Location: ticket.php');
    exit();
}

include 'header.php';
include 'nav.php';
?>

<div class="forum-container" style="max-width:700px">

    <h1>Signaler un bug</h1>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" style="font-family:'Texturina',serif"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <input type="text" name="titre" placeholder="Titre du problème" required maxlength="200">
            <textarea name="contenu" placeholder="Décrivez le bug rencontré..." rows="5" required></textarea>
            <input type="email" name="mail" placeholder="Votre email (optionnel)" value="<?= isset($_SESSION['id']) ? htmlspecialchars($_SESSION['email'] ?? '') : '' ?>">
            <button type="submit" name="envoyer_ticket" class="btn btn-sm" style="background:#620D14;color:#fff;border:1px solid #8b1a1a;font-family:'New Rocker',system-ui">Envoyer</button>
        </form>
    </div>

</div>

<?php include 'footer.php'; ?>
