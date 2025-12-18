<?php
require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

$message = '';
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $commune = trim($_POST['commune'] ?? '');
        $adresse_pma = trim($_POST['adresse_pma'] ?? '');
        $adresse_cai = trim($_POST['adresse_cai'] ?? '');
        $demandeur = trim($_POST['demandeur'] ?? '');
        $type_event = trim($_POST['type_event'] ?? '');
        $is_acel = isset($_POST['is_acel']) ? 1 : 0;
        $is_cot = isset($_POST['is_cot']) ? 1 : 0;
        $numero_intervention = trim($_POST['numero_intervention'] ?? '');
        $cadres_astreinte = trim($_POST['cadres_astreinte'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        // Nouveaux champs
        $cadre_permanence = trim($_POST['cadre_permanence'] ?? '');
        $cadre_astreinte = trim($_POST['cadre_astreinte'] ?? '');
        $dtus_permanence = trim($_POST['dtus_permanence'] ?? '');
        $logisticien_astreinte = trim($_POST['logisticien_astreinte'] ?? '');
        $aide_regulateur = trim($_POST['aide_regulateur'] ?? '');
        
        // Validation
        if (empty($commune) || empty($adresse_pma) || empty($demandeur) || empty($type_event)) {
            throw new Exception('Veuillez remplir tous les champs obligatoires.');
        }
        
        // Insertion en base de données
        $stmt = $pdo->prepare("
            INSERT INTO interventions 
            (commune, adresse_pma, adresse_cai, demandeur, type_event, is_acel, is_cot, numero_intervention, 
             cadres_astreinte, description, cadre_permanence, cadre_astreinte, dtus_permanence, 
             logisticien_astreinte, aide_regulateur)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $commune, $adresse_pma, $adresse_cai, $demandeur, $type_event,
            $is_acel, $is_cot, $numero_intervention, $cadres_astreinte, $description,
            $cadre_permanence, $cadre_astreinte, $dtus_permanence, $logisticien_astreinte, $aide_regulateur
        ]);
        
        $intervention_id = $pdo->lastInsertId();
        
        // Redirection vers le dashboard
        header("Location: dashboard.php?id=" . $intervention_id);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création d'Intervention - Gestion des Opérations CRF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-light bg-light border-bottom">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1 text-danger">
                <i class="bi bi-heart-pulse-fill"></i> Croix-Rouge Française
            </span>
            <div>
                <a href="history.php" class="btn btn-outline-secondary btn-sm me-2">
                    <i class="bi bi-clock-history"></i> Voir l'Historique
                </a>
                <span class="text-muted">
                    <?php echo htmlspecialchars($_SESSION['user']['nom']); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-bottom">
                        <h4 class="mb-0 text-danger"><i class="bi bi-plus-circle"></i> Créer une Nouvelle Intervention</h4>
                    </div>
                    <div class="card-body bg-light">
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <!-- Groupe 1 : Qui gère ? -->
                            <fieldset class="card mb-4 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0 text-secondary"><i class="bi bi-people"></i> Qui gère ?</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="cadre_permanence" class="form-label">Cadre Permanence</label>
                                            <input type="text" class="form-control" id="cadre_permanence" 
                                                   name="cadre_permanence" placeholder="Nom et coordonnées">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cadre_astreinte" class="form-label">Cadre Astreinte</label>
                                            <input type="text" class="form-control" id="cadre_astreinte" 
                                                   name="cadre_astreinte" placeholder="Nom et coordonnées">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="dtus_permanence" class="form-label">DTUS Permanence</label>
                                            <input type="text" class="form-control" id="dtus_permanence" 
                                                   name="dtus_permanence" placeholder="Nom et coordonnées">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="logisticien_astreinte" class="form-label">Logisticien Astreinte</label>
                                            <input type="text" class="form-control" id="logisticien_astreinte" 
                                                   name="logisticien_astreinte" placeholder="Nom et coordonnées">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="aide_regulateur" class="form-label">Aide Régulateur</label>
                                            <input type="text" class="form-control" id="aide_regulateur" 
                                                   name="aide_regulateur" placeholder="Nom et coordonnées">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cadres_astreinte" class="form-label">Autres Cadres d'Astreinte</label>
                                            <textarea class="form-control" id="cadres_astreinte" name="cadres_astreinte" rows="2" 
                                                      placeholder="Autres cadres et coordonnées"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Groupe 2 : Où et Quoi ? -->
                            <fieldset class="card mb-4 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0 text-secondary"><i class="bi bi-geo-alt"></i> Où et Quoi ?</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="commune" class="form-label">Commune <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="commune" name="commune" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="demandeur" class="form-label">Demandeur <span class="text-danger">*</span></label>
                                            <select class="form-select" id="demandeur" name="demandeur" required>
                                                <option value="">-- Sélectionner --</option>
                                                <option value="SAMU">SAMU</option>
                                                <option value="VIGIE 92">VIGIE 92</option>
                                                <option value="CODIS">CODIS</option>
                                                <option value="Préfecture">Préfecture</option>
                                                <option value="PC CRF">PC CRF</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="type_event" class="form-label">Type d'Événement <span class="text-danger">*</span></label>
                                            <select class="form-select" id="type_event" name="type_event" required>
                                                <option value="">-- Sélectionner --</option>
                                                <option value="Incendie">Incendie</option>
                                                <option value="Inondation">Inondation</option>
                                                <option value="Accident">Accident</option>
                                                <option value="Attentat">Attentat</option>
                                                <option value="Événement Public">Événement Public</option>
                                                <option value="Autre">Autre</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="numero_intervention" class="form-label">Numéro d'Intervention</label>
                                            <input type="text" class="form-control" id="numero_intervention" 
                                                   name="numero_intervention" placeholder="Ex: INT-2024-001">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="description" name="description" rows="4" 
                                                  placeholder="Détails de la situation, contexte, besoins identifiés..." required></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_acel" name="is_acel" value="1">
                                                <label class="form-check-label" for="is_acel">
                                                    ACEL (Accident Catastrophique à Effet Limité)
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_cot" name="is_cot" value="1">
                                                <label class="form-check-label" for="is_cot">
                                                    Centre Opérationnel Territorial (COT)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Groupe 3 : Logistique -->
                            <fieldset class="card mb-4 shadow-sm">
                                <div class="card-header bg-white">
                                    <h5 class="mb-0 text-secondary"><i class="bi bi-building"></i> Logistique</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="adresse_pma" class="form-label">Adresse PMA <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="adresse_pma" name="adresse_pma" 
                                                   placeholder="Adresse du Point de Médical Avancé" required>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="adresse_cai" class="form-label">Adresse CAI</label>
                                            <input type="text" class="form-control" id="adresse_cai" name="adresse_cai" 
                                                   placeholder="Adresse du Centre d'Accueil et d'Information">
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="bi bi-check-circle"></i> Créer l'Intervention
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

