<?php
session_start();
include_once __DIR__ . '/includes/logger.php';
log_action('Déconnexion');
if (!empty($_SESSION['id'])) {
    $pdo->prepare("UPDATE USER SET last_co = NULL WHERE id = :id")->execute([':id' => $_SESSION['id']]);
}
session_destroy();
header('Location: index.php');
exit();
?>