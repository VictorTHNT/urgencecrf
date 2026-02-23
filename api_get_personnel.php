<?php
/**
 * API pour récupérer le personnel d'un moyen
 * Reçoit un moyen_id et retourne toutes les lignes de moyen_personnel associées
 */

require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

// Vérifier que la requête est en GET ou POST
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer le moyen_id
$moyen_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $moyen_id = isset($_GET['moyen_id']) ? (int)$_GET['moyen_id'] : 0;
} else {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input)) {
        $input = $_POST;
    }
    $moyen_id = isset($input['moyen_id']) ? (int)$input['moyen_id'] : 0;
}

// Validation
if (!$moyen_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'moyen_id manquant']);
    exit;
}

try {
    // Récupérer le personnel du moyen
    $stmt = $pdo->prepare("SELECT id, role, nom_prenom FROM moyen_personnel WHERE moyen_id = ? ORDER BY role, nom_prenom");
    $stmt->execute([$moyen_id]);
    $personnel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => $personnel
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur base de données : ' . $e->getMessage()]);
}
?>


