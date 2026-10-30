<?php

date_default_timezone_set('Europe/Paris');

require_once __DIR__ . "/secrets.php";

//définir les constantes
define("SITE_URL", "localhost/the_howling_tavern/");

//essayer de se connecter à la base
try {
    //création du DSN
    $dsn = sprintf("mysql:host=%s;dbname=%s;charset=utf8mb4", DB_HOST, DB_BASE);
    //options
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ];
    //connexion vers la base
    $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, $options);
    $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
} catch (Exception $ex) {
    print $ex->getMessage();
    http_response_code(500);
    die('Erreur 1');
}