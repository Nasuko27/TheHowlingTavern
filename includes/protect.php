<?php

include_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Réservé aux utilisateurs connectés
if (!isset($_SESSION['id'])) {
    http_response_code(403);
    header("Location: " . BASE . "index.php");
    exit();
}

?>