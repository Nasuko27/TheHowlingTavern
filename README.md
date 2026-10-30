# The Howling Tavern

Site communautaire de jeu de rôle (PHP / MySQL) : fiches de personnage, tables de jeu, forum et newsletter.

## Structure

```
index.php    page d'accueil
compte/      connexion, inscription, vérification, profil
fiches/      fiches de personnage (création, édition, export PDF)
tables/      tables de jeu et chat de partie
forum/       forum et conversations
newsletter/  articles et redirection depuis les mails
pages/       tickets et mentions légales
admin/       back-office (dashboard, utilisateurs, logs, captchas, données des systèmes)
api/         endpoints appelés en AJAX (polling, recherche, filtres, données de fiche)
includes/    fichiers inclus : config, secrets, protections, logger, mailer, header, nav, footer
assets/      css/, js/ et img/ (dont avatars/, fiches/ et captcha/ pour les uploads)
logs/        logs d'activité (non versionnés)
temp/        fichiers temporaires (non versionnés)
```

## Installation

1. `composer install` pour installer TCPDF (export PDF des fiches)
2. Copier `includes/secrets.example.php` en `includes/secrets.php` et le remplir
3. Adapter `SITE_URL` dans `includes/config.php`
