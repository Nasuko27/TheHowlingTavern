<?php
include_once __DIR__ . '/../includes/protect.php';
include_once __DIR__ . '/../includes/config.php';

if (!isset($_GET['chemin']) || empty($_GET['chemin'])) {
    header("Location: captcha.php");
    exit;
}

$chemin = $_GET['chemin'];

$sql = "DELETE FROM CAPCHAT WHERE chemin = :chemin";
$stmt = $pdo->prepare($sql);
$stmt->execute([':chemin' => $chemin]);

$filePath = dirname(__DIR__) . "/assets/img/captcha/" . $chemin;
if (file_exists($filePath)) {
    unlink($filePath);
}

header("Location: captcha.php");
exit;
?>
