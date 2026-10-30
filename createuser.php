<?php
ob_start();
include_once 'config.php';

$sql = "SELECT chemin FROM CAPCHAT ORDER BY id ASC";
$stmt = $pdo->query($sql);
$sources = $stmt->fetchAll();

$error = "";
try {
    $email         = trim($_POST['email']         ?? '');
    $pseudo        = trim($_POST['pseudo']        ?? '');
    $nom           = trim($_POST['nom']           ?? '');
    $prenom        = trim($_POST['prenom']        ?? '');
    $datenaissance = filter_input(INPUT_POST, 'datenaissance');

    $sql = "SELECT email FROM USER WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email);
    $stmt->execute();
    $testmail = $stmt->fetch();

    $sql = "SELECT login FROM USER WHERE login = :pseudo";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':pseudo', $pseudo);
    $stmt->execute();
    $testpseudo = $stmt->fetch();

    if ($testmail) {
        $error = "L'adresse mail saisie existe déjà. Veuillez en choisir une autre.";
        $email = "";
    } elseif ($testpseudo) {
        $error = "Ce pseudo est déjà utilisé. Veuillez en choisir un autre.";
        $pseudo = "";
    } else {
        if (!empty($email) && !empty($pseudo) && !empty($datenaissance) && !empty($nom) && !empty($prenom)) {
            $password = filter_input(INPUT_POST, 'pass');
            $password = password_hash($password, PASSWORD_DEFAULT);

            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $sql = "INSERT INTO USER (nom, prenom, email, login, mdp, date_naissance, token, isVerified, status)
                    VALUES (:nom, :prenom, :email, :login, :mdp, :datenaissance, :token, 0, 0)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':nom',           $nom,           PDO::PARAM_STR);
            $stmt->bindParam(':prenom',        $prenom,        PDO::PARAM_STR);
            $stmt->bindParam(':email',         $email,         PDO::PARAM_STR);
            $stmt->bindParam(':login',         $pseudo,        PDO::PARAM_STR);
            $stmt->bindParam(':mdp',           $password,      PDO::PARAM_STR);
            $stmt->bindParam(':datenaissance', $datenaissance);
            $stmt->bindParam(':token',         $code,          PDO::PARAM_STR);
            $stmt->execute();

            if (session_status() === PHP_SESSION_NONE) session_start();
            $_SESSION['pending_email'] = $email;

            try {
                include_once 'mailer.php';
                $mailResult = send_verification_mail($email, $pseudo, $code);
                $_SESSION['mail_sent']  = ($mailResult === true);
                $_SESSION['mail_error'] = ($mailResult === true) ? null : $mailResult;
            } catch (Throwable $t) {
                $_SESSION['mail_sent']  = false;
                $_SESSION['mail_error'] = $t->getMessage();
            }

            if (!$_SESSION['mail_sent']) {
                $_SESSION['fallback_code'] = $code;
            }

            header('Location: verify_code.php');
            exit();
        }
    }

} catch (Exception $ex) {
    print 'Erreur : ' . $ex->getMessage();
}

include_once 'header.php';

log_action('Connexion');

?>
<body>

<?php include 'nav.php'; ?>

<div class="centered-popup" style="background-image: url(img/background.jpg); background-size: 100% 100%; font-family: 'New Rocker', system-ui; background-attachment: fixed;">
    <div class="popup p-5">
        <h1 class="display-1 text-center">Créez votre compte</h1>
        <form action="createuser.php" method="POST" class="px-5">
            <div class="col g-3">
                <div class="mb-3 row column-gap-4">
                    <div class="col p-0">
                        <label for="nom" class="form-label">Nom</label>
                        <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($nom ?? '') ?>" required>
                    </div>
                    <div class="col p-0">
                        <label for="prenom" class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?php echo htmlspecialchars($prenom ?? '') ?>" required>
                    </div>
                </div>
                <div class="mb-3 row column-gap-4">
                    <div class="col p-0">
                        <label for="pseudo" class="form-label">Pseudo</label>
                        <input type="text" class="form-control" id="pseudo" name="pseudo" value="<?php echo htmlspecialchars($pseudo ?? '') ?>" required>
                    </div>
                    <div class="col p-0">
                        <label for="datenaissance" class="form-label">Date de naissance</label>
                        <input type="date" class="form-control" id="datenaissance" name="datenaissance" value="<?php echo htmlspecialchars($datenaissance ?? '') ?>" required>
                    </div>
                </div>
                <div class="mb-3 row">
                    <label for="email" class="form-label">Adresse mail</label>
                    <input type="text" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? '') ?>" required>
                </div>
                <div class="mb-3 row">
                    <label for="pass" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="pass" name="pass" required>
                </div>
                <input type="checkbox" name="verifCaptcha" id="verifCaptcha" value="OK" hidden required>
                <?php
                    printf("<button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#captcha' id='buttonCaptcha' onmouseup='cutImage(%s)'>Faire le captcha</button>",
                    json_encode($sources));
                ?>
                <?php
                    if($error != "") {
                        echo '<div class="alert alert-danger" role="alert">'.$error.'</div>';
                    }
                ?>
                <div class="modal fade" id="captcha" tabindex="-1" data-bs-backdrop="static" aria-labelledby="captcha" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h3 class="modal-title" id="captchaLabel">Captcha</h3>
                            </div>
                            <div class="modal-body d-flex flex-column justify-content-center align-items-center gap-3 gap-3" style="padding: 25px;">
                                <canvas id="imgCaptcha" height="150" width="450">La où l'image du captcha se trouvera</canvas>
                                <input type="range" min="0" max="1000" id="posCaptcha" style="width: 400px">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" onclick="testCaptcha()">Confirmer</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-between pt-2">
                    <button class="btn">
                        <a href="login.php">J'ai déjà un compte</a>
                    </button>
                    <button type="submit" class="btn btn-primary">Créer le compte</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="captcha.js"></script>
<script>
document.querySelector('form').addEventListener('submit', function(e) {
    if (!document.getElementById('verifCaptcha').checked) {
        e.preventDefault();
        const existing = document.getElementById('captcha-error');
        if (!existing) {
            const msg = document.createElement('div');
            msg.id = 'captcha-error';
            msg.className = 'alert alert-warning mt-2';
            msg.textContent = 'Veuillez d\'abord compléter le captcha.';
            document.getElementById('buttonCaptcha').insertAdjacentElement('afterend', msg);
        }
    }
});
</script>
</body>