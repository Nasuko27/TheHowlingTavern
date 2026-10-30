<?php

include __DIR__ . '/includes/header.php';

log_action('Visite');

?>
<body class="gap-3" style="margin-top: 20px;">

<?php
include __DIR__ . '/includes/nav.php';
?>

   
    
<div class="comphero d-inline-flex gap-5 py-4 px-5 mb-5 mx-5">
    <div class="col d-flex flex-column justify-content-center">
        <img src="assets/img/fdpcollage.png" alt="" class="img-fluid object-fit-fill"/>
    </div>
    <div class="col d-flex flex-column justify-content-center gap-4">
        <h2 class="text-light">C'est ici que démarre ta prochaine aventure</h2>
        <h3 class="text-light">Bienvenue sur The Howling Tavern, le lieu de rendez-vous de tout bon roliste.
            Ici, tu pourras créer tes personnages, rejoindre des tables de jeux avec tes amis
            ou des inconnus, discuter de tes aventures ou encore créer des cartes de jeu. 
            Alors, que tu sois débutant ou vétérans, joueur inconditionnel ou Maître du jeu malgré toi,
            prêt pour une longue campagne ou un one shot inoubliable, tu trouveras forcément
            une place au coin du feu en attendant ta prochaine aventure.</h3>
    </div>
</div>


<div class="comphero d-inline-flex gap-5 py-4 px-5 mb-5 mx-5">
    <div class="col d-flex flex-column justify-content-center gap-4">
        <h2 class="text-light">Toute bonne aventure commence par un bon personnage </h2>
        <h3 class="text-light">Plutôt adepte de la magie ou maître d'armes ? 
            Plutôt race mystique de la forêt ou classique humain ? 
            A toi de créer celui que tu veux être. 
            Créer ton propre personnage en puisant dans le système de jeu de ton choix.</h3>
        <div class="d-flex flex-row-reverse">
            <a href="createfdp.php" class="link-light link-underline-opacity-0 link-underline-opacity-0-hover">
                <div class="d-flex px-3 py-2 align-items-center" style="background-color: #620D14; border-radius: 25px">
                    <h2 class="m-0">Créer sa fiche</h2>
                </div>
            </a>
        </div>
    </div>
    <div class="col d-flex flex-column justify-content-center">
        <img src="assets/img/fdpcollage.png" alt="" class="img-fluid object-fit-fill"/>
    </div>
</div>

<div class="comphero d-inline-flex gap-5 py-4 px-5 mb-5 mx-5">
    <div class="col d-flex flex-column justify-content-center">
        <img src="assets/img/fdpcollage.png" alt="" class="img-fluid object-fit-fill"/>
    </div>
    <div class="col d-flex flex-column justify-content-center gap-4">
        <h2 class="text-light">L'aventure se passe rarement seul</h2>
        <h3 class="text-light">Tu as fait ton personnage ? Il n'y a plus qu'à le mettre à l'épreuve. 
            Rejoins une table de jeu de la communauté ou encore celle de tes amis et démarre ta première quête.</h3>
        <div class="d-flex flex-row-reverse">
            <a href="..." class="link-light link-underline-opacity-0 link-underline-opacity-0-hover">
                <div class="d-flex px-3 py-2 align-items-center" style="background-color: #620D14; border-radius: 25px">
                    <h2 class="m-0">Rejoindre une table</h2>
                </div>
            </a>
        </div>
    </div>
</div>

<div class="comphero d-inline-flex gap-5 py-4 px-5 mb-5 mx-5">
    <div class="col d-flex flex-column justify-content-center">
        <img src="assets/img/fdpcollage.png" alt="" class="img-fluid object-fit-fill"/>
    </div>
    <div class="col d-flex flex-column justify-content-center gap-4">
        <h2 class="text-light">Au delà du jeu de rôle</h2>
        <h3 class="text-light">Des rolistes, c'est avant tout des gens avec des avis, des envies et des choses à partager. 
            Viens donc voir ce qu'il s'en dis sur notre forum, que ce soit pour trouver des gens pour une nouvelle campagne, 
            proposer des systèmes de jeu ou voir les meilleurs cartes de la communauté.</h3>
        <div class="d-flex flex-row-reverse">
            <a href="forum.php" class="link-light link-underline-opacity-0 link-underline-opacity-0-hover">
                <div class="d-flex px-3 py-2 align-items-center" style="background-color: #620D14; border-radius: 25px">
                    <h2 class="m-0">Vers le forum</h2>
                </div>
            </a>
        </div>
    </div>
</div>

<div class="comphero d-inline-flex gap-5 py-4 px-5 mb-5 mx-5">
    <div class="col d-flex flex-column justify-content-center gap-4">
        <h2 class="text-light">Pour une envie d'aller plus loin</h2>
        <h3 class="text-light">Tu te sens limité dans tes possibilités ? Envie de plus de tables de jeux, 
            de fiches de personnages et de systèmes de jeu ? Deviens membre premium et accède à pleins de nouvelles possibilités.</h3>
        <div class="d-flex flex-row-reverse">
            <a href="login.php" class="link-light link-underline-opacity-0 link-underline-opacity-0-hover">
                <div class="d-flex px-3 py-2 align-items-center" style="background-color: #620D14; border-radius: 25px">
                    <h2 class="m-0">Devenir membre</h2>
                </div>
            </a>
        </div>
    </div>
    <div class="col d-flex flex-column justify-content-center">
        <img src="assets/img/fdpcollage.png" alt="" class="img-fluid object-fit-fill"/>
    </div>
</div>


<?php 
include __DIR__ . '/includes/footer.php'; ?>