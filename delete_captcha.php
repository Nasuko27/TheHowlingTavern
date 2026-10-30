<?php
include_once "protect.php";
include_once "config.php";

if (!isset($_GET['chemin']) || empty($_GET['chemin'])) {
    header("Location: captcha.php");
    exit;
}

$chemin = $_GET['chemin'];

$sql = "DELETE FROM CAPCHAT WHERE chemin = :chemin";
$stmt = $pdo->prepare($sql);
$stmt->execute([':chemin' => $chemin]);

$filePath = "img/captcha/" . $chemin;
if (file_exists($filePath)) {
    unlink($filePath);
}

header("Location: captcha.php");
exit;
?>
