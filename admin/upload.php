<?php
include_once __DIR__ . '/../includes/protect_admin.php';
include_once __DIR__ . '/../includes/config.php';
$name = filter_input(INPUT_POST, 'nomCaptcha');
$name = trim($name);
$name = str_replace(' ', '_', $name);
$name = str_replace("'", '', $name);
$name = str_replace('"', '', $name);
$name = str_replace('/', '', $name);
$name = str_replace('\\', '', $name);
$name = str_replace(';', '', $name);
$name = str_replace('?', '', $name);
$name = str_replace('(', '', $name);
$name = str_replace(')', '', $name);
$name = str_replace('+', '', $name);
$name = str_replace('-', '', $name);
$name = str_replace('.', '', $name);

$original = basename($_FILES['Upload']['name']);

if(strpos($original, '.png')) {
    $ext = '.png';
} elseif (strpos($original, '.jpg')) {
    $ext = '.jpg';
} elseif (strpos($original, '.jpeg')) {
    $ext = '.jpeg';
}

$dir = dirname(__DIR__) . "/assets/img/captcha/";
$filename = $dir.$name.$ext;
$chemin = $name.$ext;


if (move_uploaded_file($_FILES["Upload"]["tmp_name"], $filename)) {
    $sql = "INSERT INTO CAPCHAT(chemin) VALUES(:chemin)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":chemin", $chemin);
    $stmt->execute();
    header('Location: captcha.php');
    exit();
} else {
    echo "Sorry, there was an error uploading your file.";
}


