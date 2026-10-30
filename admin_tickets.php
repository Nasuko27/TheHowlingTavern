<?php
include_once 'protect.php';
include_once 'header.php';
include_once 'config.php';
include_once 'logger.php';
include_once 'adminnav.php';

if (!isset($_SESSION['status']) || (string)$_SESSION['status'] !== '1') {
    http_response_code(403);
    die('Accès refusé.');
}

log_action('Administration', 'Tickets');

$flash = null;
if (isset($_SESSION['ticket_flash'])) {
    $flash = $_SESSION['ticket_flash'];
    unset($_SESSION['ticket_flash']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_ticket'])) {
        $tid = (int)($_POST['ticket_id'] ?? 0);
        if ($tid) {
            $pdo->prepare("DELETE FROM TICKET WHERE id = :id")->execute([':id' => $tid]);
            log_action('Administration', 'Suppression ticket #' . $tid);
            $_SESSION['ticket_flash'] = ['type' => 'danger', 'msg' => 'Ticket supprimé.'];
        }
    } elseif (isset($_POST['prendre_ticket'])) {
        $tid = (int)($_POST['ticket_id'] ?? 0);
        if ($tid) {
            $pdo->prepare("UPDATE TICKET SET id_admin = :aid, statut = 'en_cours' WHERE id = :id")
                ->execute([':aid' => $_SESSION['id'], ':id' => $tid]);
            log_action('Administration', 'Prise en charge ticket #' . $tid);
            $_SESSION['ticket_flash'] = ['type' => 'success', 'msg' => 'Ticket pris en charge.'];
        }
    } elseif (isset($_POST['change_statut'])) {
        $tid = (int)($_POST['ticket_id'] ?? 0);
        $statut = $_POST['statut'] ?? '';
        if ($tid && in_array($statut, ['nouveau', 'en_cours', 'traite'])) {
            $pdo->prepare("UPDATE TICKET SET statut = :s WHERE id = :id")->execute([':s' => $statut, ':id' => $tid]);
            log_action('Administration', 'Ticket #' . $tid . ' → ' . $statut);
        }
    } elseif (isset($_POST['repondre_ticket'])) {
        $tid = (int)($_POST['ticket_id'] ?? 0);
        $reponse = trim($_POST['reponse'] ?? '');
        if ($tid && $reponse !== '') {
            $pdo->prepare("UPDATE TICKET SET reponse = :rep, statut = 'traite' WHERE id = :id")
                ->execute([':rep' => $reponse, ':id' => $tid]);
            log_action('Administration', 'Réponse ticket #' . $tid);
            $_SESSION['ticket_flash'] = ['type' => 'success', 'msg' => 'Réponse enregistrée.'];
        }
    }
    header('Location: admin_tickets.php');
    exit();
}

$filtre = $_GET['statut'] ?? '';
if ($filtre && in_array($filtre, ['nouveau', 'en_cours', 'traite'])) {
    $stmt = $pdo->prepare("SELECT t.*, u.login AS admin_login FROM TICKET t LEFT JOIN USER u ON u.id = t.id_admin WHERE t.statut = :s ORDER BY t.id DESC");
    $stmt->execute([':s' => $filtre]);
} else {
    $stmt = $pdo->query("SELECT t.*, u.login AS admin_login FROM TICKET t LEFT JOIN USER u ON u.id = t.id_admin ORDER BY t.id DESC");
}
$tickets = $stmt->fetchAll();

$statutConfig = [
    'nouveau' => ['label' => 'Nouveau', 'bg' => '#c0392b'],
    'en_cours' => ['label' => 'En cours', 'bg' => '#e67e22'],
    'traite' => ['label' => 'Traité', 'bg' => '#26bc26'],
];
?>

<h1>Tickets</h1>

<div class="d-flex gap-2 align-items-center" style="margin-bottom:1rem;max-width:1100px;width:100%">
    <a href="admin_tickets.php" class="btn btn-sm" style="background:<?= !$filtre ? '#620D14' : '#333' ?>;color:#fff;border:none;font-family:'New Rocker',system-ui">Tous (<?= (int)$pdo->query("SELECT COUNT(*) FROM TICKET")->fetchColumn() ?>)</a>
    <?php foreach ($statutConfig as $key => $cfg): ?>
        <?php $cnt = (int)$pdo->prepare("SELECT COUNT(*) FROM TICKET WHERE statut = ?"); $pdo->prepare("SELECT COUNT(*) FROM TICKET WHERE statut = ?")->execute([$key]); ?>
        <a href="admin_tickets.php?statut=<?= $key ?>" class="btn btn-sm" style="background:<?= $filtre === $key ? $cfg['bg'] : '#333' ?>;color:#fff;border:none;font-family:'New Rocker',system-ui"><?= $cfg['label'] ?></a>
    <?php endforeach; ?>
</div>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] ?>" style="font-family:'Texturina',serif;max-width:1100px;width:100%"><?= htmlspecialchars($flash['msg']) ?></div>
<?php endif; ?>

<p class="meta" style="max-width:1100px;width:100%"><?= count($tickets) ?> ticket<?= count($tickets) > 1 ? 's' : '' ?></p>

<?php if (empty($tickets)): ?>
    <p class="empty">Aucun ticket.</p>
<?php else: ?>
    <?php foreach ($tickets as $t): ?>
    <?php $cfg = $statutConfig[$t['statut']] ?? $statutConfig['nouveau']; ?>
    <div class="nl-card" style="max-width:1100px;width:100%;border-left:4px solid <?= $cfg['bg'] ?>">
        <div class="nl-header">
            <div class="d-flex align-items-center gap-2">
                <span class="role-badge" style="background:<?= $cfg['bg'] ?>"><?= $cfg['label'] ?></span>
                <div class="nl-title"><?= htmlspecialchars($t['titre']) ?></div>
            </div>
            <div class="nl-meta">
                <?php if ($t['mail']): ?>
                    <strong><?= htmlspecialchars($t['mail']) ?></strong>
                <?php else: ?>
                    <span style="color:#666">Pas d'email</span>
                <?php endif; ?>
                <?php if ($t['admin_login']): ?>
                    &middot; pris en charge par <strong style="color:#0682ef"><?= htmlspecialchars($t['admin_login']) ?></strong>
                <?php endif; ?>
            </div>
        </div>
        <div class="nl-content"><?= nl2br(htmlspecialchars($t['contenu'])) ?></div>

        <?php if ($t['reponse']): ?>
        <div style="background:rgb(26 4 2 / 0.6);border-left:3px solid #26bc26;border-radius:0 4px 4px 0;padding:.6rem .8rem;margin-top:.6rem">
            <div style="font-size:.75rem;color:#26bc26;margin-bottom:.2rem">R&eacute;ponse admin</div>
            <div style="color:#ccc;font-size:.9rem"><?= nl2br(htmlspecialchars($t['reponse'])) ?></div>
        </div>
        <?php endif; ?>

        <div class="d-flex gap-2 mt-3 align-items-center flex-wrap">
            <?php if (!$t['id_admin']): ?>
            <form method="POST" style="display:inline">
                <input type="hidden" name="prendre_ticket" value="1">
                <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                <button type="submit" class="btn btn-sm" style="background:#0682ef;color:#fff;border:none;font-family:'New Rocker',system-ui">Prendre en charge</button>
            </form>
            <?php endif; ?>
            <form method="POST" style="display:inline">
                <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                <input type="hidden" name="change_statut" value="1">
                <select name="statut" onchange="this.form.submit()" style="background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.3rem .5rem;font-size:.8rem;font-family:'Texturina',serif">
                    <?php foreach ($statutConfig as $key => $c): ?>
                    <option value="<?= $key ?>" <?= $t['statut'] === $key ? 'selected' : '' ?>><?= $c['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php if ($t['mail']): ?>
                <a href="mailto:<?= htmlspecialchars($t['mail']) ?>?subject=Re: <?= rawurlencode($t['titre']) ?>" class="btn btn-sm" style="background:#0682ef;color:#fff;border:none;font-family:'New Rocker',system-ui">Mail</a>
            <?php endif; ?>
            <?php if (!$t['reponse']): ?>
                <button onclick="document.getElementById('reply-<?= (int)$t['id'] ?>').style.display='block';this.style.display='none'" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-family:'New Rocker',system-ui">R&eacute;pondre</button>
            <?php endif; ?>
            <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce ticket ?')">
                <input type="hidden" name="delete_ticket" value="1">
                <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
                <button type="submit" class="btn btn-sm" style="background:#bc2626;color:#fff;border:none;font-family:'New Rocker',system-ui">Supprimer</button>
            </form>
        </div>

        <?php if (!$t['reponse']): ?>
        <form method="POST" id="reply-<?= (int)$t['id'] ?>" style="display:none;margin-top:.8rem">
            <input type="hidden" name="repondre_ticket" value="1">
            <input type="hidden" name="ticket_id" value="<?= (int)$t['id'] ?>">
            <textarea name="reponse" placeholder="Votre réponse..." rows="3" style="width:100%;background:#1a0a05;border:1px solid #620D14;color:#fff;border-radius:4px;padding:.6rem;font-family:'Texturina',serif;font-size:.9rem;margin-bottom:.5rem"></textarea>
            <button type="submit" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-family:'New Rocker',system-ui">Envoyer</button>
        </form>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include 'footer.php'; ?>
