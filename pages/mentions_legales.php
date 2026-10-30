<?php
ob_start();
header('Content-Type: text/html; charset=utf-8');
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();
include_once __DIR__ . '/../includes/logger.php';
log_action('Mentions légales', 'Lecture');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width">
    <title>Mentions l&eacute;gales &ndash; The Howling Tavern</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=New+Rocker&family=Texturina:ital,opsz,wght@0,12..72,100..900;1,12..72,100..900&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <link href="../assets/css/styles.css" rel="stylesheet">
</head>
<body>

<?php include __DIR__ . '/../includes/nav.php'; ?>

<div class="forum-container">

    <div class="forum-header">
        <h1>Mentions l&eacute;gales</h1>
    </div>

    <div class="nl-card">
        <div class="nl-title">1. &Eacute;diteur du site</div>
        <div class="nl-content">
            Le site <strong>The Howling Tavern</strong> est &eacute;dit&eacute; dans le cadre d&rsquo;un projet p&eacute;dagogique r&eacute;alis&eacute; par des &eacute;tudiants de l&rsquo;<strong>ESGI Paris</strong> (&Eacute;cole Sup&eacute;rieure de G&eacute;nie Informatique).<br>
            &Eacute;quipe : <strong>The Howling Tavern &ndash; ESGI Paris</strong><br>
            Contact : <a href="mailto:gunblack91@gmail.com">thehowlingtavern@gmail.com</a>
        </div>
    </div>

    <div class="nl-card">
        <div class="nl-title">2. H&eacute;bergement</div>
        <div class="nl-content">
            Ce site est h&eacute;berg&eacute; par :<br>
            <strong>OVH SAS</strong><br>
            Si&egrave;ge social : 2 rue Kellermann &ndash; 59100 Roubaix &ndash; France<br>
            T&eacute;l&eacute;phone : 1007<br>
            Site web : <a href="https://www.ovhcloud.com" target="_blank" rel="noopener">www.ovhcloud.com</a>
        </div>
    </div>

    <div class="nl-card">
        <div class="nl-title">3. Propri&eacute;t&eacute; intellectuelle</div>
        <div class="nl-content">
            L&rsquo;ensemble du contenu de ce site (textes, images, graphismes, logo) est prot&eacute;g&eacute; par le droit d&rsquo;auteur. Toute reproduction, m&ecirc;me partielle, est interdite sans autorisation pr&eacute;alable &eacute;crite des auteurs.<br>
            Les syst&egrave;mes de jeu r&eacute;f&eacute;renc&eacute;s (Donjons &amp; Dragons, CO Fantasy&hellip;) sont la propri&eacute;t&eacute; de leurs &eacute;diteurs respectifs. The Howling Tavern n&rsquo;est affili&eacute; &agrave; aucun d&rsquo;eux.
        </div>
    </div>

    <div class="nl-card">
        <div class="nl-title">4. Donn&eacute;es personnelles (RGPD)</div>
        <div class="nl-content">
            Lors de votre inscription, nous collectons votre adresse e-mail, pseudo, nom et pr&eacute;nom uniquement dans le but de g&eacute;rer votre compte.<br>
            Ces donn&eacute;es ne sont ni revendues ni transmises &agrave; des tiers.<br>
            Conform&eacute;ment au R&egrave;glement G&eacute;n&eacute;ral sur la Protection des Donn&eacute;es (RGPD), vous disposez d&rsquo;un droit d&rsquo;acc&egrave;s, de rectification et de suppression de vos donn&eacute;es. Pour exercer ces droits, contactez-nous &agrave; l&rsquo;adresse indiqu&eacute;e ci-dessus.
        </div>
    </div>

    <div class="nl-card">
        <div class="nl-title">5. Cookies</div>
        <div class="nl-content">
            Ce site utilise uniquement des cookies de session n&eacute;cessaires au fonctionnement de l&rsquo;authentification. Aucun cookie publicitaire ou de pistage tiers n&rsquo;est utilis&eacute;.
        </div>
    </div>

    <div class="nl-card">
        <div class="nl-title">6. Limitation de responsabilit&eacute;</div>
        <div class="nl-content">
            Ce site &eacute;tant un projet &eacute;tudiant, il peut contenir des erreurs ou &ecirc;tre indisponible ponctuellement. Les auteurs ne sauraient &ecirc;tre tenus responsables de tout dommage direct ou indirect r&eacute;sultant de son utilisation.
        </div>
    </div>

</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
