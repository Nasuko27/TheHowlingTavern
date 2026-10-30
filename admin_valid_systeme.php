<?php
ob_start();
include_once 'protect.php';
include_once 'header.php';
include_once 'config.php';
include_once 'logger.php';
include_once 'adminnav.php';

if (!isset($_SESSION['status']) || (string)$_SESSION['status'] !== '1') {
    http_response_code(403);
    die('<p style="font-family:sans-serif;color:red;padding:2rem">Acc&egrave;s refus&eacute;.</p>');
}

log_action('Administration', 'Systèmes à valider');

$flash = null;
if (isset($_SESSION['valid_systeme_flash'])) {
    $flash = $_SESSION['valid_systeme_flash'];
    unset($_SESSION['valid_systeme_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id_systeme'])) {
    $sid = (int)$_POST['id_systeme'];
    $stmt = $pdo->prepare("SELECT nom FROM SYSTEME WHERE id = :id");
    $stmt->execute([':id' => $sid]);
    $sysNom = $stmt->fetchColumn();
    if ($_POST['action'] === 'valider') {
        $pdo->prepare("UPDATE SYSTEME SET valide = 1 WHERE id = :id")->execute([':id' => $sid]);
        log_action('Administration', 'Système validé #' . $sid);
        $_SESSION['valid_systeme_flash'] = ['type' => 'success', 'msg' => 'Système "' . $sysNom . '" validé.'];
    } elseif ($_POST['action'] === 'refuser') {
        $pdo->prepare("DELETE FROM SYSTEME WHERE id = :id AND valide = 0")->execute([':id' => $sid]);
        log_action('Administration', 'Système refusé #' . $sid);
        $_SESSION['valid_systeme_flash'] = ['type' => 'danger', 'msg' => 'Système "' . $sysNom . '" refusé et supprimé.'];
    }
    header('Location: admin_valid_systeme.php');
    exit();
}

$pending = $pdo->query(
    "SELECT id, nom, description FROM SYSTEME WHERE valide = 0 ORDER BY id DESC"
);
$systemes = $pending ? $pending->fetchAll() : [];
?>

<div class="forum-container">

    <h1>Syst&egrave;mes &agrave; valider</h1>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" style="font-family:'Texturina',serif"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <?php if (empty($systemes)): ?>
        <div class="dash-section" style="text-align:center;padding:3rem">
            <p style="color:#888;font-size:1.1rem;margin:0">Aucun syst&egrave;me en attente de validation.</p>
        </div>
    <?php else: ?>
        <?php foreach ($systemes as $s): ?>
        <div class="nl-card">
            <div class="nl-header">
                <div class="nl-title"><?= htmlspecialchars($s['nom']) ?></div>
            </div>
            <div class="nl-content"><?= htmlspecialchars($s['description']) ?></div>
            <div class="d-flex gap-2 mt-3">
                <form method="POST">
                    <input type="hidden" name="id_systeme" value="<?= (int)$s['id'] ?>">
                    <input type="hidden" name="action" value="valider">
                    <button type="submit" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-family:'New Rocker',system-ui">Valider</button>
                </form>
                <form method="POST" onsubmit="return confirm('Refuser et supprimer ce système ?')">
                    <input type="hidden" name="id_systeme" value="<?= (int)$s['id'] ?>">
                    <input type="hidden" name="action" value="refuser">
                    <button type="submit" class="btn btn-sm" style="background:#bc2626;color:#fff;border:none;font-family:'New Rocker',system-ui">Refuser</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>
