<?php
/**
 * API pour mettre à jour un moyen
 * Reçoit les requêtes AJAX et met à jour la base de données
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
$type = isset($input['type']) ? trim($input['type']) : '';
$nom_indicatif = isset($input['nom_indicatif']) ? trim($input['nom_indicatif']) : '';
$fonction = isset($input['fonction']) ? trim($input['fonction']) : '';
$nb_pse = isset($input['nb_pse']) ? (int)$input['nb_pse'] : 0;
$nb_ch = isset($input['nb_ch']) ? (int)$input['nb_ch'] : 0;
$nb_ci = isset($input['nb_ci']) ? (int)$input['nb_ci'] : 0;
$nb_cadre_local = isset($input['nb_cadre_local']) ? (int)$input['nb_cadre_local'] : 0;
$nb_cadre_dept = isset($input['nb_cadre_dept']) ? (int)$input['nb_cadre_dept'] : 0;
$nb_logisticien = isset($input['nb_logisticien']) ? (int)$input['nb_logisticien'] : 0;

// Validation
if (!$moyen_id || !$intervention_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID manquant']);
    exit;
}

if (empty($type) || empty($nom_indicatif)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Type et nom indicatif requis']);
    exit;
}

// Validation du type
$types_autorises = ['VPSP', 'VL', 'MINIBUS', 'ETIR', 'BENEVOLE', 'CADRE', 'VPSP_PCPS', 'UMH', 'GROUPE_BSPP', 'Autre'];
if (!in_array($type, $types_autorises)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Type invalide']);
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
    
    // Mettre à jour le moyen
    $stmt = $pdo->prepare("UPDATE moyens SET type = ?, nom_indicatif = ?, fonction = ?, nb_pse = ?, nb_ch = ?, nb_ci = ?, nb_cadre_local = ?, nb_cadre_dept = ?, nb_logisticien = ? WHERE id = ? AND intervention_id = ?");
    $stmt->execute([$type, $nom_indicatif, $fonction, $nb_pse, $nb_ch, $nb_ci, $nb_cadre_local, $nb_cadre_dept, $nb_logisticien, $moyen_id, $intervention_id]);
    
    // Gestion du personnel : suppression
    $personnel_to_delete = isset($input['personnel_to_delete']) ? $input['personnel_to_delete'] : [];
    if (!empty($personnel_to_delete) && is_array($personnel_to_delete)) {
        // Sécuriser les IDs (forcer le typage en entier)
        $ids_to_delete = array_map('intval', $personnel_to_delete);
        $ids_to_delete = array_filter($ids_to_delete, function($id) { return $id > 0; });
        
        if (!empty($ids_to_delete)) {
            // Vérifier que les IDs appartiennent bien au moyen
            $placeholders = implode(',', array_fill(0, count($ids_to_delete), '?'));
            $stmt_check = $pdo->prepare("SELECT id FROM moyen_personnel WHERE id IN ($placeholders) AND moyen_id = ?");
            $params_check = array_merge($ids_to_delete, [$moyen_id]);
            $stmt_check->execute($params_check);
            $valid_ids = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($valid_ids)) {
                $placeholders_delete = implode(',', array_fill(0, count($valid_ids), '?'));
                $stmt_delete = $pdo->prepare("DELETE FROM moyen_personnel WHERE id IN ($placeholders_delete) AND moyen_id = ?");
                $params_delete = array_merge($valid_ids, [$moyen_id]);
                $stmt_delete->execute($params_delete);
            }
        }
    }
    
    // Gestion du personnel : ajout
    $personnel_to_add = isset($input['personnel_to_add']) ? $input['personnel_to_add'] : [];
    if (!empty($personnel_to_add) && is_array($personnel_to_add)) {
        $stmt_insert = $pdo->prepare("INSERT INTO moyen_personnel (moyen_id, role, nom_prenom) VALUES (?, ?, ?)");
        
        foreach ($personnel_to_add as $role => $personnes) {
            if (is_array($personnes)) {
                foreach ($personnes as $nom_prenom) {
                    $nom_prenom = trim($nom_prenom);
                    if (!empty($nom_prenom)) {
                        $stmt_insert->execute([$moyen_id, $role, $nom_prenom]);
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Moyen mis à jour avec succès'
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur base de données : ' . $e->getMessage()]);
}
?>

