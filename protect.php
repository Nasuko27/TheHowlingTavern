<?php

session_start();
if(!isset($_SESSION['id']) && !isset($_SESSION['login']) && !isset($_SESSION['status'])) {
    if($_SESSION['status'] !== 1) {
        http_response_code(403);
        header("Location: index.php");
        exit();
    }
}

?>