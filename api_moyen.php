<?php
/**
 * API pour gérer les changements de statut des moyens
 * Reçoit les requêtes AJAX et met à jour la base de données
 */

require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

// Vérifier que la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
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
$new_status = isset($input['new_status']) ? $input['new_status'] : '';

// Validation
if (!$moyen_id || !$intervention_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
    exit;
}

if (!in_array($new_status, ['dispo', 'engage'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Statut invalide']);
    exit;
}

try {
    // Vérifier que le moyen appartient bien à l'intervention
    $stmt = $pdo->prepare("SELECT * FROM moyens WHERE id = ? AND intervention_id = ?");
    $stmt->execute([$moyen_id, $intervention_id]);
    $moyen = $stmt->fetch();
    
    if (!$moyen) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Moyen non trouvé']);
        exit;
    }
    
    // Mettre à jour le statut
    $stmt = $pdo->prepare("UPDATE moyens SET status = ? WHERE id = ? AND intervention_id = ?");
    $stmt->execute([$new_status, $moyen_id, $intervention_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Statut mis à jour avec succès',
        'moyen_id' => $moyen_id,
        'new_status' => $new_status
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erreur base de données : ' . $e->getMessage()]);
}
?>

