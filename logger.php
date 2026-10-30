<?php
include_once 'config.php';
date_default_timezone_set('Europe/Paris');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('LOG_DIR', 'logs');
define('LOG_FILE', LOG_DIR . '/logs.txt');

if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0755, true);
}

if (!file_exists(LOG_FILE) || filesize(LOG_FILE) === 0) {
    $header = ['datetime', 'id', 'login', 'role', 'page', 'action', 'detail', 'ip_address'];
    file_put_contents(LOG_FILE, implode('|', $header) . PHP_EOL, FILE_APPEND | LOCK_EX);
}

// Détermine le rôle depuis status
function get_role() {
    $status = (string)($_SESSION['status'] ?? '0');
    if ($status === '1') return 'admin';
    if ($status === '2') return 'banni';
    return 'user';
}

function update_last_co() {
    if (empty($_SESSION['id'])) return;
    global $pdo;
    $stmt = $pdo->prepare("UPDATE USER SET last_co = NOW() WHERE id = :id");
    $stmt->bindValue(':id', $_SESSION['id'], PDO::PARAM_INT);
    $stmt->execute();
}

update_last_co();

function log_action($action = 'visite', $detail = '') {
    $row = [
        date('Y-m-d H:i:s'),
        $_SESSION['id'] ?? 'N/A',
        $_SESSION['login'] ?? 'N/A',
        get_role(),
        $_SERVER['PHP_SELF'] ?? 'N/A',
        $action,
        $detail,
        $_SERVER['REMOTE_ADDR'] ?? 'N/A',
    ];

    $cleaned = array_map(
        function($valeur) { return str_replace(["\r", "\n", '|'], ' ', trim((string)$valeur)); },
        $row
    );

    file_put_contents(LOG_FILE, implode('|', $cleaned) . PHP_EOL, FILE_APPEND | LOCK_EX);
}
