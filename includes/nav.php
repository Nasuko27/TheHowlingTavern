<nav class="d-flex justify-content-between align-items-center p-3" style="background-color: #240902; border-bottom: 2px solid #620D14;">
    <div class="d-inline-flex">
        <ul class="d-inline-flex  align-items-center m-0 p-0">
            <li class="list-group-item">
                <a href="<?= BASE ?>index.php" class="link-light link-underline-opacity-0">
                    <img src="<?= BASE ?>assets/img/logo.png" alt="" height="80"/>
                </a>
            </li>
            <li class="list-group-item px-3 text-light">
                <a href="<?= BASE ?>index.php" class="link-light link-underline-opacity-0">Accueil</a>
            </li>
            <li class="list-group-item px-3 text-light">
                <div class="dropdown">
                    <button class="dropbtn">Fiches</button>
                    <div class="dropdown-content">
                        <a href="<?= BASE ?>fiches/mes_fiches.php" class="link-light link-underline-opacity-0">Mes fiches</a>
                        <a href="<?= BASE ?>fiches/systemes.php" class="link-light link-underline-opacity-0">Créer sa fiche</a>
                    </div>
                </div>
            </li>
            <li class="list-group-item px-3 text-light">
                <div class="dropdown">
                    <button class="dropbtn">Tables</button>
                    <div class="dropdown-content">
                        <a href="<?= BASE ?>tables/index.php" class="link-light link-underline-opacity-0">Mes tables</a>
                        <a href="<?= BASE ?>tables/index.php?join=1" class="link-light link-underline-opacity-0">Rejoindre une table</a>
                        <a href="<?= BASE ?>tables/index.php?new=1" class="link-light link-underline-opacity-0">Cr&eacute;er une table</a>
                    </div>
                </div>
            </li>
            <li class="list-group-item px-3 text-light">
                <div class="dropdown">
                    <button class="dropbtn">Actualités</button>
                    <div class="dropdown-content">
                        <a href="<?= BASE ?>newsletter/index.php" class="link-light link-underline-opacity-0">Newsletter</a>
                    </div>
                </div>
            </li>
            <li class="list-group-item px-3 text-light">
                <div class="dropdown">
                    <button class="dropbtn">Forum</button>
                    <div class="dropdown-content">
                        <a href="<?= BASE ?>forum/index.php" class="link-light link-underline-opacity-0">La Taverne</a>
                        <a href="<?= BASE ?>forum/conversations.php" class="link-light link-underline-opacity-0">Mes conversations</a>
                    </div>
                </div>
            </li>
        </ul>
    </div>
    <?php if (empty($hideNavAuth)): ?>
    <div class="d-inline-flex align-items-center gap-3">
        <?php
            if(isset($_SESSION['login'])){
                if(isset($_SESSION['status']) && (string)$_SESSION['status'] === '1') {
                    echo "<a href='" . BASE . "admin/index.php' class='btn btn-outline-warning btn-sm'>Admin</a>";
                }
                echo "<a href='" . BASE . "compte/logout.php' class='btn btn-outline-danger btn-sm'>Déconnexion</a>";
            } else {
                echo "<a href='" . BASE . "compte/login.php'><button class='btn btn-primary'>Se connecter / Créer un compte</button></a>";
            }
        ?>
        <?php
            $navAv = BASE . 'assets/img/usrplaceholder.png';
            if (isset($_SESSION['id'])) {
                if (file_exists(dirname(__DIR__) . '/assets/img/avatars/' . $_SESSION['id'] . '.jpg')) $navAv = BASE . 'assets/img/avatars/' . $_SESSION['id'] . '.jpg';
                elseif (file_exists(dirname(__DIR__) . '/assets/img/avatars/' . $_SESSION['id'] . '.png')) $navAv = BASE . 'assets/img/avatars/' . $_SESSION['id'] . '.png';
            }
        ?>
        <a href="<?= isset($_SESSION['id']) ? BASE . 'compte/profil.php' : BASE . 'compte/login.php' ?>"><img src="<?= $navAv ?>" alt="" height="80" style="border-radius:50%;object-fit:cover;width:80px"/></a>
    </div>
    <?php endif; ?>
</nav>
<?php if (isset($_SESSION['id'])): ?>
<script>setInterval(function(){ fetch('<?= BASE ?>api/presence.php'); }, 120000);</script>
<?php endif; ?>
