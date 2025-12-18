<?php
/**
 * API pour ajouter un message à la main courante
 * Reçoit les requêtes AJAX et insère le message en base de données
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

$intervention_id = isset($input['intervention_id']) ? (int)$input['intervention_id'] : 0;
$expediteur = isset($input['expediteur']) ? trim($input['expediteur']) : '';
$destinataire = isset($input['destinataire']) ? trim($input['destinataire']) : '';
$message = isset($input['message']) ? trim($input['message']) : '';
$moyen_com = isset($input['moyen_com']) ? trim($input['moyen_com']) : '';
$operateur = isset($_SESSION['user']['nom']) ? trim($_SESSION['user']['nom']) : 'Inconnu';

// Validation
if (!$intervention_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID d\'intervention manquant']);
    exit;
}

if (empty($expediteur) || empty($destinataire) || empty($message)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tous les champs sont requis']);
    exit;
}

try {
    // Vérifier que l'intervention existe
    $stmt = $pdo->prepare("SELECT id FROM interventions WHERE id = ?");
    $stmt->execute([$intervention_id]);
    $intervention = $stmt->fetch();
    
    if (!$intervention) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Intervention introuvable']);
        exit;
    }
    
    // Insérer le message
    $stmt = $pdo->prepare("INSERT INTO main_courante (intervention_id, expediteur, destinataire, message, moyen_com, operateur) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$intervention_id, $expediteur, $destinataire, $message, $moyen_com, $operateur]);
    
    // Récupérer le message inséré
    $message_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM main_courante WHERE id = ?");
    $stmt->execute([$message_id]);
    $message_insere = $stmt->fetch();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Message enregistré',
        'data' => $message_insere
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur base de données : ' . $e->getMessage()]);
}
?>

