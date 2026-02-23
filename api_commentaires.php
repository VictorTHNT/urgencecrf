<?php
/**
 * API commentaires de fin de mission (intervention_commentaires)
 * GET : lecture des commentaires pour une intervention_id
 * POST : enregistrement (INSERT ou UPDATE)
 */

require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$intervention_id = isset($_GET['intervention_id']) ? (int)$_GET['intervention_id'] : 0;
if (!$intervention_id && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $intervention_id = isset($input['intervention_id']) ? (int)$input['intervention_id'] : 0;
}

if (!$intervention_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID d\'intervention manquant']);
    exit;
}

// Vérifier que l'intervention existe
$stmt = $pdo->prepare("SELECT id FROM interventions WHERE id = ?");
$stmt->execute([$intervention_id]);
if (!$stmt->fetch()) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Intervention introuvable']);
    exit;
}

// --- GET : retourner les commentaires existants ---
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $empty = [
        'points_positifs' => '',
        'points_negatifs' => '',
        'problemes_internes_crf' => '',
        'problemes_externes_crf' => '',
        'zone_libre' => ''
    ];
    try {
        $stmt = $pdo->prepare("SELECT points_positifs, points_negatifs, problemes_internes_crf, problemes_externes_crf, zone_libre FROM intervention_commentaires WHERE intervention_id = ?");
        $stmt->execute([$intervention_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['status' => 'success', 'data' => $row ?: $empty]);
    } catch (PDOException $e) {
        // Table absente : retourner des champs vides pour que la modale reste utilisable
        echo json_encode(['status' => 'success', 'data' => $empty]);
    }
    exit;
}

// --- POST : enregistrer (INSERT ou UPDATE) ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (empty($input)) {
    $input = $_POST;
}

$points_positifs = isset($input['points_positifs']) ? trim($input['points_positifs']) : '';
$points_negatifs = isset($input['points_negatifs']) ? trim($input['points_negatifs']) : '';
$problemes_internes_crf = isset($input['problemes_internes_crf']) ? trim($input['problemes_internes_crf']) : '';
$problemes_externes_crf = isset($input['problemes_externes_crf']) ? trim($input['problemes_externes_crf']) : '';
$zone_libre = isset($input['zone_libre']) ? trim($input['zone_libre']) : '';

try {
    $stmt = $pdo->prepare("SELECT id FROM intervention_commentaires WHERE intervention_id = ?");
    $stmt->execute([$intervention_id]);
    $existant = $stmt->fetch();

    if ($existant) {
        $stmt = $pdo->prepare("UPDATE intervention_commentaires SET points_positifs = ?, points_negatifs = ?, problemes_internes_crf = ?, problemes_externes_crf = ?, zone_libre = ? WHERE intervention_id = ?");
        $stmt->execute([$points_positifs, $points_negatifs, $problemes_internes_crf, $problemes_externes_crf, $zone_libre, $intervention_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO intervention_commentaires (intervention_id, points_positifs, points_negatifs, problemes_internes_crf, problemes_externes_crf, zone_libre) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$intervention_id, $points_positifs, $points_negatifs, $problemes_internes_crf, $problemes_externes_crf, $zone_libre]);
    }

    echo json_encode(['status' => 'success', 'message' => 'Commentaires enregistrés']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur base de données : ' . $e->getMessage()]);
}
