<?php
include_once 'protect.php';
include_once 'config.php';
 
header('Content-Type: application/json');
 
$q      = trim($_GET['q'] ?? '');
$status = isset($_GET['status']) && $_GET['status'] !== '' ? (int)$_GET['status'] : -1;

try {
    if ($q === '') {
        if ($status >= 0) {
            $stmt = $pdo->prepare("SELECT id, login, email, status, nom, prenom, avatar FROM USER WHERE status = :s ORDER BY id ASC");
            $stmt->execute([':s' => $status]);
        } else {
            $stmt = $pdo->query("SELECT id, login, email, status, nom, prenom, avatar FROM USER ORDER BY id ASC");
        }
    } else {
        if ($status >= 0) {
            $stmt = $pdo->prepare("
                SELECT id, login, email, status, nom, prenom, avatar FROM USER
                WHERE status = :s AND (login LIKE :q OR nom LIKE :q OR prenom LIKE :q OR email LIKE :q)
                ORDER BY id ASC
            ");
            $stmt->execute([':s' => $status, ':q' => '%' . $q . '%']);
        } else {
            $stmt = $pdo->prepare("
                SELECT id, login, email, status, nom, prenom, avatar FROM USER
                WHERE login LIKE :q OR nom LIKE :q OR prenom LIKE :q OR email LIKE :q
                ORDER BY id ASC
            ");
            $stmt->execute([':q' => '%' . $q . '%']);
        }
    }
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
