<?php
ob_start();
include_once __DIR__ . '/includes/protect_verified.php';
include_once __DIR__ . '/includes/logger.php';
log_action('Création FDP');

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/nav.php';

$flash = null;
if (isset($_SESSION['systeme_flash'])) {
    $flash = $_SESSION['systeme_flash'];
    unset($_SESSION['systeme_flash']);
}

$isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isAdmin) {
    if (isset($_POST['proposer_systeme'])) {
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($nom !== '' && mb_strlen($nom) <= 100) {
            $pdo->prepare("INSERT INTO SYSTEME (nom, description, valide) VALUES (:nom, :desc, 0)")
                ->execute([':nom' => $nom, ':desc' => $description]);
            log_action('Administration', 'Proposition système : ' . $nom);
            $_SESSION['systeme_flash'] = ['type' => 'success', 'msg' => 'Système soumis à validation.'];
        }
    } elseif (isset($_POST['delete_systeme'])) {
        $sid = (int)($_POST['id_systeme'] ?? 0);
        if ($sid) {
            $stmt = $pdo->prepare("SELECT nom FROM SYSTEME WHERE id = :id");
            $stmt->execute([':id' => $sid]);
            $sysNom = $stmt->fetchColumn();
            $pdo->prepare("DELETE FROM SYSTEME WHERE id = :id")->execute([':id' => $sid]);
            log_action('Administration', 'Suppression système : ' . $sysNom);
            $_SESSION['systeme_flash'] = ['type' => 'danger', 'msg' => 'Système "' . $sysNom . '" supprimé.'];
        }
    } elseif (isset($_POST['edit_systeme'])) {
        $sid = (int)($_POST['id_systeme'] ?? 0);
        $nom = trim($_POST['nom'] ?? '');
        $description = trim($_POST['description'] ?? '');
        if ($sid && $nom !== '' && mb_strlen($nom) <= 100) {
            $pdo->prepare("UPDATE SYSTEME SET nom = :nom, description = :desc WHERE id = :id")
                ->execute([':nom' => $nom, ':desc' => $description, ':id' => $sid]);
            log_action('Administration', 'Modification système #' . $sid);
            $_SESSION['systeme_flash'] = ['type' => 'success', 'msg' => 'Système modifié.'];
        }
    }
    header('Location: systemeFiche.php');
    exit();
}

$sql = "SELECT * FROM SYSTEME WHERE valide = 1 ORDER BY nom ASC";
$stmt = $pdo->query($sql);
$systemes = $stmt->fetchAll();

?>
<body class="container-fluid">

<div class="forum-container">

    <h1>Choisir un syst&egrave;me</h1>

    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>" style="font-family:'Texturina',serif"><?= htmlspecialchars($flash['msg']) ?></div>
    <?php endif; ?>

    <?php $editId = ($isAdmin && isset($_GET['edit'])) ? (int)$_GET['edit'] : 0; ?>
    <?php foreach ($systemes as $systeme): ?>
    <?php if ($editId === (int)$systeme['id']): ?>
    <form method="POST" class="form-card" style="margin-bottom:.8rem">
        <input type="hidden" name="edit_systeme" value="1">
        <input type="hidden" name="id_systeme" value="<?= (int)$systeme['id'] ?>">
        <input type="text" name="nom" value="<?= htmlspecialchars($systeme['nom']) ?>" required maxlength="100">
        <textarea name="description" rows="2"><?= htmlspecialchars($systeme['description']) ?></textarea>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-family:'New Rocker',system-ui">Sauvegarder</button>
            <a href="systemeFiche.php" class="btn btn-sm" style="background:#333;color:#fff;border:none;font-family:'New Rocker',system-ui">Annuler</a>
        </div>
    </form>
    <?php else: ?>
    <div class="d-flex justify-content-between align-items-center" style="background:rgb(36 9 2 / 0.88);border:1px solid #3d0d0d;border-radius:6px;padding:1rem 1.2rem;margin-bottom:.8rem">
        <div>
            <h2 style="margin:0;font-size:1.3rem"><?= htmlspecialchars($systeme['nom']) ?></h2>
            <p style="margin:0;color:#aaa;font-size:.85rem"><?= htmlspecialchars($systeme['description']) ?></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if ($isAdmin): ?>
                <a href="systemeFiche.php?edit=<?= (int)$systeme['id'] ?>" class="btn btn-sm" style="background:#e67e22;color:#fff;border:none;font-family:'New Rocker',system-ui;white-space:nowrap">Modifier</a>
                <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer ce système ?')">
                    <input type="hidden" name="delete_systeme" value="1">
                    <input type="hidden" name="id_systeme" value="<?= (int)$systeme['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="background:#bc2626;color:#fff;border:none;font-family:'New Rocker',system-ui;white-space:nowrap">Supprimer</button>
                </form>
            <?php endif; ?>
            <a href="createfdp.php?systeme=<?= (int)$systeme['id'] ?>" class="btn btn-sm" style="background:#26bc26;color:#fff;border:none;font-family:'New Rocker',system-ui;white-space:nowrap">Choisir</a>
        </div>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>

    <?php if (empty($systemes)): ?>
        <p style="color:#888;text-align:center;padding:2rem">Aucun syst&egrave;me disponible pour le moment.</p>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <div class="form-card" style="margin-top:2rem">
        <h2>Ajouter un syst&egrave;me</h2>
        <form method="POST">
            <input type="text" name="nom" placeholder="Nom du système (ex: Pathfinder 2e)" required maxlength="100">
            <textarea name="description" placeholder="Description du système..." rows="3"></textarea>
            <button type="submit" name="proposer_systeme" class="btn btn-sm" style="background:#620D14;color:#fff;border:1px solid #8b1a1a;font-family:'New Rocker',system-ui">Ajouter</button>
        </form>
    </div>
    <?php endif; ?>

</div>


<?php 
include __DIR__ . '/includes/footer.php'; ?>