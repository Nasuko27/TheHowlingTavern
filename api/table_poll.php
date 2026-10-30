<?php
header('Content-Type: application/json; charset=utf-8');
include_once __DIR__ . '/../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non connecte']);
    exit();
}

$uid   = (int)$_SESSION['id'];
$tid   = (int)($_GET['id_table'] ?? 0);
$after = (int)($_GET['after']    ?? 0);

$check = $pdo->prepare("SELECT 1 FROM TABLE_MEMBRE WHERE id_table = :tid AND id_user = :uid");
$check->execute([':tid' => $tid, ':uid' => $uid]);
if (!$tid || !$check->fetch()) {
    echo json_encode(['messages' => []]);
    exit();
}

$stmt = $pdo->prepare(
    "SELECT c.id, c.contenu, c.date_envoi, u.id AS uid, u.login, u.avatar
     FROM TABLE_CHAT c
     JOIN USER u ON u.id = c.id_user
     WHERE c.id_table = :tid AND c.id > :after
     ORDER BY c.date_envoi ASC
     LIMIT 100"
);
$stmt->execute([':tid' => $tid, ':after' => $after]);
$rows = $stmt->fetchAll();

$messages = [];
foreach ($rows as $r) {
    $avSrc = $r['avatar'] ? 'assets/img/avatars/' . $r['avatar'] : 'assets/img/usrplaceholder.png';
    $diff  = (new DateTime())->getTimestamp() - (new DateTime($r['date_envoi']))->getTimestamp();
    if ($diff < 60)          $ago = 'a l\'instant';
    elseif ($diff < 3600)    $ago = floor($diff / 60) . ' min';
    elseif ($diff < 86400)   $ago = floor($diff / 3600) . 'h';
    elseif ($diff < 604800)  $ago = floor($diff / 86400) . 'j';
    else                     $ago = date('d/m/Y', (new DateTime($r['date_envoi']))->getTimestamp());

    $messages[] = [
        'id'       => (int)$r['id'],
        'contenu'  => htmlspecialchars($r['contenu']),
        'uid'      => (int)$r['uid'],
        'login'    => htmlspecialchars($r['login']),
        'avatar'   => $avSrc,
        'time_ago' => $ago
    ];
}

$deleted = [];
$checkIds = $_GET['visible'] ?? '';
if ($checkIds !== '') {
    $ids = array_map('intval', explode(',', $checkIds));
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id FROM TABLE_CHAT WHERE id IN ($placeholders) AND id_table = ?");
        $params = array_merge($ids, [$tid]);
        $stmt->execute($params);
        $existing = array_column($stmt->fetchAll(), 'id');
        $deleted = array_values(array_diff($ids, array_map('intval', $existing)));
    }
}

echo json_encode(['messages' => $messages, 'deleted' => $deleted]);
