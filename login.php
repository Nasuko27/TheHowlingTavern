<?php
include_once "config.php";
include_once "header.php" ;

$sql = "SELECT chemin FROM CAPCHAT ORDER BY id ASC";
$stmt = $pdo->query($sql);
$sources = $stmt->fetchAll();

session_start();

if(isset($_SESSION['id']) && isset($_SESSION['login'])) {
    $sql = "SELECT login FROM USER WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":id", $_SESSION['id']);
    $stmt->execute();
    $session = $stmt->fetch();
    if($_SESSION['login'] === $session['login'] && $_SESSION['status'] === 1) {
        header('Location: admin.php');
        exit();
    } else {
        header('Location: index.php');
        exit();
    }
}
$error = "";

$captcha = filter_input(INPUT_POST, 'verifCaptcha');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($captcha === "OK") {
        $login = filter_input(INPUT_POST, 'login', FILTER_SANITIZE_STRING);
        $pass = filter_input(INPUT_POST, 'mdp', FILTER_SANITIZE_STRING);
        if (!empty($login) and !empty($pass)) {
            $sql = "SELECT id,mdp,login,status FROM USER WHERE login = :login OR email = :mail";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':login', $login, PDO::PARAM_STR);
            $stmt->bindParam(':mail', $login, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetchAll();
            if ($user[1]) {
                $error = "Mauvais mot de passe";
            } else {
                if ($user) {
                    $user = $user[0];
                    if (password_verify($pass, $user['mdp'])) {
                        session_start();
                        $_SESSION['login'] = $user['login'];
                        $_SESSION['id'] = $user['id'];
                        $_SESSION['status'] = $user['status'];
                        if ($_SESSION['status'] === 1) {
                            header('Location: admin.php');
                            exit();
                        } else {
                            header('Location: index.php');
                            exit();
                        }
                    } else {
                        $error = "User non reconnu";
                    }
                } else {
                    $error = "User non existant";
                }
            }
        } else {
            $error = "Entrer les deux infos";
        }
    } else {
        $error = "Veuillez valider le captcha";
    }

}



log_action('Connexion');

?>

<body style="font-family: 'New Rocker', system-ui;">

<script src="captcha.js"></script>
<?php include "nav.php"; ?>

<div class="centered-popup" style="background-image: url(img/background.jpg); background-size: 100% 100%; font-family: 'New Rocker', system-ui; background-attachment: fixed;">
    <div class="popup">
        <h1 class="display-1 text-center">Connexion</h1>
        <form action="login.php" method="POST" class="px-5">
            <div class="d-flex flex-column gap-3">
                <div class="row">
                    <label for="login" class="form-label">Adresse mail/pseudo</label>
                    <input type="text" class="form-control" id="login" name="login">
                </div>
                <div class="row">
                    <label for="mdp" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="mdp" name="mdp">
                </div>
                <?php
                    printf("<button type='button' class='btn btn-primary' data-bs-toggle='modal' data-bs-target='#captcha' id='buttonCaptcha' onmouseup='cutImage(%s)'>"
                    , json_encode($sources));

                ?>
                    <input type="checkbox" name="verifCaptcha" id="verifCaptcha" value="OK" hidden required>
                    <label for="verifCaptcha">Faire le captcha</label>
                </button>
                <?php
                if ($error != "") {
                    echo "<div class='alert alert-danger' >$error</div>";
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

                <div class="d-flex justify-content-between">
                    <button class="btn">
                        <a href="createuser.php">Créer un compte</a>
                    </button>
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </div>
        </form>
    </div>
</div>
</body>