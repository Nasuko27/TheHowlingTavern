<?php
include_once 'protect_verified.php';
include_once 'config.php';
require_once __DIR__ . '/vendor/autoload.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: mes_fiches.php'); exit(); }

$isAdmin = isset($_SESSION['status']) && (string)$_SESSION['status'] === '1';

$stmt = $pdo->prepare(
    "SELECT f.*,
            c.nom AS classe_nom, c.dedevie AS classe_dv, c.caracmagie,
            r.nom AS race_nom,
            u.login AS joueur_login
     FROM FDP f
     JOIN USER u ON u.id = f.id_user
     LEFT JOIN CLASSE c ON c.id = f.id_classe
     LEFT JOIN RACE r ON r.id = f.id_race
     WHERE f.id = :id"
);
$stmt->execute([':id' => $id]);
$fiche = $stmt->fetch();
if (!$fiche) { header('Location: mes_fiches.php'); exit(); }

$isOwner = (int)$fiche['id_user'] === (int)$_SESSION['id'];
if (!$isOwner && !$isAdmin) { header('Location: mes_fiches.php'); exit(); }

$stmt = $pdo->prepare(
    "SELECT ca.sigle, s.valeur, COALESCE(br.valeur, 0) AS bonus
     FROM stats s
     JOIN CARACTERISTIQUE ca ON ca.id = s.id_caracteristique
     LEFT JOIN FDP f ON f.id = s.id_fdp
     LEFT JOIN bonus_racial br ON br.id_race = f.id_race AND br.id_caracteristique = s.id_caracteristique
     WHERE s.id_fdp = :fdp
     ORDER BY ca.id"
);
$stmt->execute([':fdp' => $id]);
$stats = $stmt->fetchAll();

$mods = [];
foreach ($stats as $s) {
    $total = (int)$s['valeur'] + (int)$s['bonus'];
    $mods[$s['sigle']] = floor($total / 2 - 5);
}

$contactMod = $mods['FOR'] ?? 0;
$distanceMod = $mods['DEX'] ?? 0;
$magiqueMod = 0;
if ($fiche['caracmagie'] && isset($mods[$fiche['caracmagie']])) {
    $magiqueMod = $mods[$fiche['caracmagie']];
}
$fiche['defense'] = 10 + ($mods['DEX'] ?? 0);

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 10, 'FEUILLE DE PERSONNAGE', 0, 1, 'C');
$pdf->Ln(2);

$portrait = '';
if (file_exists(__DIR__ . '/img/fiches/' . $id . '.jpg')) $portrait = __DIR__ . '/img/fiches/' . $id . '.jpg';
elseif (file_exists(__DIR__ . '/img/fiches/' . $id . '.png')) $portrait = __DIR__ . '/img/fiches/' . $id . '.png';
if ($portrait) {
    $pdf->Image($portrait, 10, $pdf->GetY(), 40, 50, '', '', '', true, 150, '', false, false, 1);
}

$xInfo = $portrait ? 55 : 10;
$yInfo = $pdf->GetY();

$pdf->SetFont('helvetica', 'B', 16);
$pdf->SetXY($xInfo, $yInfo);
$pdf->Cell(0, 8, $fiche['nom'] ?? 'Sans nom', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetX($xInfo);
$pdf->Cell(40, 6, 'Joueur : ' . ($fiche['joueur_login'] ?? ''), 0, 0);
$pdf->Cell(40, 6, 'Niveau : ' . (int)$fiche['niveau'], 0, 1);

$pdf->SetX($xInfo);
$pdf->Cell(40, 6, 'Race : ' . ($fiche['race_nom'] ?? '-'), 0, 0);
$pdf->Cell(40, 6, 'Classe : ' . ($fiche['classe_nom'] ?? '-'), 0, 1);

$pdf->SetX($xInfo);
$pdf->Cell(40, 6, 'De de vie : ' . ($fiche['classe_dv'] ?? '-'), 0, 0);
$pdf->Cell(40, 6, 'PV : ' . (int)$fiche['hp'], 0, 1);

$pdf->SetX($xInfo);
$pdf->Cell(40, 6, 'Defense : ' . (int)$fiche['defense'], 0, 0);
$pdf->Cell(40, 6, 'Initiative : ' . (int)$fiche['initiative'], 0, 1);

$pdf->SetY(max($pdf->GetY(), $yInfo + 52));
$pdf->Ln(5);

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'CARACTERISTIQUES', 0, 1);

$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(98, 13, 20);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(25, 7, 'Carac.', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Valeur', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Racial', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Mod.', 1, 0, 'C', true);
$pdf->Cell(25, 7, 'Test', 1, 1, 'C', true);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
foreach ($stats as $s) {
    $total = (int)$s['valeur'] + (int)$s['bonus'];
    $mod = floor($total / 2 - 5);
    $modStr = ($mod >= 0 ? '+' : '') . $mod;
    $bonusStr = (int)$s['bonus'] !== 0 ? ((int)$s['bonus'] > 0 ? '+' : '') . (int)$s['bonus'] : '0';

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(25, 7, $s['sigle'], 1, 0, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(25, 7, (int)$s['valeur'], 1, 0, 'C');
    $pdf->Cell(25, 7, $bonusStr, 1, 0, 'C');
    $pdf->Cell(25, 7, $modStr, 1, 0, 'C');
    $pdf->Cell(25, 7, $modStr, 1, 1, 'C');
}

$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'COMBAT', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$fmtMod = function($v) { return ($v >= 0 ? '+' : '') . $v; };
$pdf->Cell(45, 7, 'Attaque Contact : ' . $fmtMod($contactMod), 1, 0);
$pdf->Cell(45, 7, 'Attaque Distance : ' . $fmtMod($distanceMod), 1, 0);
$pdf->Cell(45, 7, 'Attaque Magique : ' . $fmtMod($magiqueMod), 1, 1);

$pdf->Cell(45, 7, 'Initiative : ' . (int)$fiche['initiative'], 1, 0);
$pdf->Cell(45, 7, 'Defense : ' . (int)$fiche['defense'], 1, 0);
$pdf->Cell(45, 7, 'PV : ' . (int)$fiche['hp'], 1, 1);

$filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fiche['nom'] ?? 'fiche') . '.pdf';
$pdf->Output($filename, 'D');
