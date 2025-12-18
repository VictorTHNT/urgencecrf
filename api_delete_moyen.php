<?php
/**
 * API pour supprimer un moyen
 * Reçoit les requêtes AJAX et supprime l'entrée dans la base de données
 */

require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);

// Ou récupérer depuis POST classique
if (empty($input)) {
    $input = $_POST;
}

$moyen_id = isset($input['moyen_id']) ? (int)$input['moyen_id'] : 0;
$intervention_id = isset($input['intervention_id']) ? (int)$input['intervention_id'] : 0;

// Validation
if (!$moyen_id || !$intervention_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID manquant']);
    exit;
}

try {
    // Vérifier que le moyen appartient bien à l'intervention
    $stmt = $pdo->prepare("SELECT * FROM moyens WHERE id = ? AND intervention_id = ?");
    $stmt->execute([$moyen_id, $intervention_id]);
    $moyen = $stmt->fetch();
    
    if (!$moyen) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Moyen non trouvé']);
        exit;
    }
    
    // Supprimer le moyen
    $stmt = $pdo->prepare("DELETE FROM moyens WHERE id = ? AND intervention_id = ?");
    $stmt->execute([$moyen_id, $intervention_id]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Moyen supprimé avec succès'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur base de données : ' . $e->getMessage()]);
}
?>

