<nav class="d-flex justify-content-between align-items-center p-3" style="background-color: #240902; border-bottom: 2px solid #620D14;">
    <ul class="d-inline-flex align-items-center m-0 p-0" style="list-style:none">
        <li class="px-2">
            <a href="<?= BASE ?>index.php"><img src="<?= BASE ?>assets/img/logo.png" alt="" height="80"/></a>
        </li>
        <li class="px-2">
            <a href="<?= BASE ?>admin/index.php" class="dropbtn" style="text-decoration:none">&#9776; Dashboard</a>
        </li>
        <li class="px-2">
            <a href="<?= BASE ?>index.php" class="dropbtn" style="text-decoration:none">&#9876; Accueil</a>
        </li>
        <li class="px-2">
            <div class="dropdown">
                <button class="dropbtn">&#9884; Utilisateurs</button>
                <div class="dropdown-content">
                    <a href="<?= BASE ?>admin/listuser.php">Liste des utilisateurs</a>
                    <a href="<?= BASE ?>admin/listadmin.php">Liste des administrateurs</a>
                    <a href="<?= BASE ?>admin/tickets.php">Tickets</a>
                </div>
            </div>
        </li>
        <li class="px-2">
            <div class="dropdown">
                <button class="dropbtn">&#128220; Syst&egrave;mes</button>
                <div class="dropdown-content">
                    <a href="<?= BASE ?>admin/DnDfulldata.php">DnD 5e</a>
                    <a href="<?= BASE ?>admin/cofulldata.php">CO Fantasy</a>
                    <a href="<?= BASE ?>systemeFiche.php">G&eacute;rer les syst&egrave;mes</a>
                </div>
            </div>
        </li>
        <li class="px-2">
            <div class="dropdown">
                <button class="dropbtn">&#9733; Communaut&eacute;</button>
                <div class="dropdown-content">
                    <a href="<?= BASE ?>admin/tables.php">Tables de jeu</a>
                    <a href="<?= BASE ?>newsletter.php">Newsletter</a>
                    <a href="<?= BASE ?>forum.php">Forum</a>
                </div>
            </div>
        </li>
        <li class="px-2">
            <div class="dropdown">
                <button class="dropbtn">&#9878; Mod&eacute;ration</button>
                <div class="dropdown-content">
                    <a href="<?= BASE ?>admin/captcha.php">Captcha</a>
                    <a href="<?= BASE ?>admin/valid_systeme.php">Syst&egrave;mes &agrave; valider</a>
                    <a href="<?= BASE ?>admin/logs.php">Logs</a>
                    <a href="<?= BASE ?>admin/online.php">Connect&eacute;s</a>
                </div>
            </div>
        </li>
    </ul>
    <div class="d-inline-flex align-items-center gap-3">
        <a href="<?= BASE ?>logout.php" class="btn btn-outline-danger btn-sm">D&eacute;connexion</a>
        <?php
            $navAv = BASE . 'assets/img/usrplaceholder.png';
            if (isset($_SESSION['id'])) {
                if (file_exists(dirname(__DIR__) . '/assets/img/avatars/' . $_SESSION['id'] . '.jpg')) $navAv = BASE . 'assets/img/avatars/' . $_SESSION['id'] . '.jpg';
                elseif (file_exists(dirname(__DIR__) . '/assets/img/avatars/' . $_SESSION['id'] . '.png')) $navAv = BASE . 'assets/img/avatars/' . $_SESSION['id'] . '.png';
            }
        ?>
        <a href="<?= BASE ?>profile.php"><img src="<?= $navAv ?>" alt="" height="80" style="border-radius:50%;object-fit:cover;width:80px"/></a>
    </div>
</nav>
<script>setInterval(function(){ fetch('<?= BASE ?>api/presence.php'); }, 120000);</script>
<body class="d-flex flex-column align-items-center gap-3" style="background-image:url('<?= BASE ?>assets/img/background.jpg');background-size:cover;background-attachment:fixed;min-height:100vh;font-family:'New Rocker',system-ui;color:#fff;padding-top:115px">
