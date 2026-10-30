<?php
include_once "protect.php";
include_once "header.php";
include_once "config.php";
include_once "adminnav.php";

log_action('Administration');

?>

<div class="container text-center">
    <div class="row d-flex g-2">
        <div class="col d-flex flex-column align-items-center gap-3" style="width: 50%">
            <img alt="Choisissez une image à ajouter à la base de données des captcha" src="" id="displayImage"
                 style="display: block; object-fit: contain; max-height: 400px; max-width: 400px; height: auto; width: auto">
            <form class="d-flex flex-column align-items-center gap-3" id="imgForm" enctype="multipart/form-data"
                  action="upload.php" method="post">
                <input type="file" name="Upload" id="addCaptcha" accept=".png, .jpg, .jpeg">
                <!--<label for="addCaptcha"> Choisissez une image à ajouter à la bse de données des captcha</label>-->
                <!--<input type="text" value="" id="imgName" hidden>-->
                <input id="nomCaptcha" name="nomCaptcha" type="text" required>
                <label for="nomCaptcha">Nom du fichier</label>

                <button class="btn btn-success" type="submit"> Envoyer</button>
            </form>
        </div>
        <!--<div class="row row-cols-1 row-cols-md-2" style="width: 50%">-->
        <div class="d-flex flex-wrap justify-content-center" style="width: 50%">
<?php
    $sql = "SELECT chemin FROM CAPCHAT ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    $captchas = $stmt->fetchAll();

    foreach ($captchas as $captcha) {
        print '<div class="card py-1 px-1 my-1 mx-1" style="width: 45%; ">';
        print '<img src="img/captcha/'.$captcha["chemin"].'" style="height: 18rem" class="card-img-top object-fit-cover" alt="">';
        print '<div class="card-body">';
        print '<h2 class="card-title h5">'.$captcha["chemin"].'</h2>';
        print '<p><a href="delete_captcha.php?chemin=' . urlencode($captcha["chemin"]) . '" onclick="return confirm(\'Supprimer cette image ?\')">Supprimer</a></p></div></div>';
    }
?>
        </div>
        <script>
            const displayImage = document.getElementById("displayImage");
            const fileInput = document.getElementById("addCaptcha");
            const imgForm = document.getElementById("imgForm");

            fileInput.addEventListener("change", (e) => {
                if (e?.target?.files && e?.target?.files[0]) {
                    displayImage.src = URL.createObjectURL(e.target.files[0]);
                    //document.getElementById('imgName').value = e.target.files[0].name ;
                }
            })
        </script>

    </div>
</div>
</body>