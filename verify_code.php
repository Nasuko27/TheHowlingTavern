<?php
ob_start();
include_once 'config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$error   = "";
$success = "";

if (!isset($_SESSION['pending_email'])) {
    header('Location: createuser.php');
    exit();
}

$email = $_SESSION['pending_email'];
$fallbackCode = $_SESSION['fallback_code'] ?? null;
unset($_SESSION['fallback_code']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = trim($_POST['code'] ?? '');

    if (!preg_match('/^\d{6}$/', $submitted)) {
        $error = "Le code doit être composé de 6 chiffres.";
    } else {
        $sql  = "SELECT id, token, login, status FROM USER WHERE email = :email AND isVerified = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Aucun compte en attente de vérification pour cette adresse.";
        } elseif ($user['token'] !== $submitted) {
            $error = "Code incorrect. Vérifie le mail reçu.";
        } else {
            $sql  = "UPDATE USER SET isVerified = 1, token = NULL WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', $user['id'], PDO::PARAM_INT);
            $stmt->execute();

            unset($_SESSION['pending_email'], $_SESSION['mail_sent'], $_SESSION['mail_error']);
            $_SESSION['id']     = $user['id'];
            $_SESSION['login']  = $user['login'];
            $_SESSION['status'] = $user['status'];

            header('Location: index.php');
            exit();
        }
    }
}

include_once 'header.php';
log_action('Vérification de compte');
?>
<body>

<?php include 'nav.php'; ?>

<div class="centered-popup" style="background-image: url(img/background.jpg); background-size: 100% 100%; font-family: 'New Rocker', system-ui; background-attachment: fixed;">
    <div class="popup p-5">
        <h1 class="display-1 text-center">Vérification du compte</h1>

        <?php if ($success): ?>
            <div class="alert alert-success text-center mt-3" role="alert">
                <?= htmlspecialchars($success) ?>
            </div>
            <div class="text-center mt-3">
                <a href="login.php" class="btn btn-primary">Se connecter</a>
            </div>
        <?php else: ?>
            <?php if (isset($_SESSION['mail_sent']) && $_SESSION['mail_sent'] === false): ?>
                <div class="alert alert-warning text-center mt-3" role="alert">
                    <strong>L'envoi du mail a échoué.</strong>
                    <?php if (!empty($_SESSION['mail_error'])): ?>
                        <br><small><?= htmlspecialchars($_SESSION['mail_error']) ?></small>
                    <?php endif; ?>
                </div>
                <?php if (!empty($fallbackCode)): ?>
                    <div class="alert alert-info text-center mt-2" role="alert">
                        Ton code de vérification : <strong style="font-size:1.5rem;letter-spacing:.4rem"><?= htmlspecialchars($fallbackCode) ?></strong>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <p class="text-center mt-3">
                    Un code à 6 chiffres a été envoyé à <strong><?= htmlspecialchars($email) ?></strong>.<br>
                    Saisis-le ci-dessous pour activer ton compte.
                </p>
            <?php endif; ?>

            <form action="verify_code.php" method="POST" class="px-5 mt-4">
                <div class="mb-3">
                    <label for="code" class="form-label">Code de vérification</label>
                    <input type="text" class="form-control text-center fs-3 fw-bold"
                           id="code" name="code" maxlength="6" pattern="\d{6}"
                           placeholder="______" autocomplete="one-time-code" required>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="d-flex justify-content-between pt-2">
                    <a href="createuser.php" class="btn">Retour</a>
                    <button type="submit" class="btn btn-primary">Vérifier</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
