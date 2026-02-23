<?php
require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

$message = '';
$error = '';

// Détection du mode (création ou modification)
$edit_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$is_edit_mode = $edit_id > 0;
$intervention_to_edit = null;

if ($is_edit_mode) {
    $stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
    $stmt->execute([$edit_id]);
    $intervention_to_edit = $stmt->fetch();

    if (!$intervention_to_edit) {
        $is_edit_mode = false;
        $edit_id = 0;
    }
}

// Titre de page
if ($is_edit_mode) {
    $page_title = "Modifier l'intervention #{$edit_id} - Gestion des Opérations CRF";
} else {
    $page_title = "Création d'Intervention - Gestion des Opérations CRF";
}

// Calcul du numéro d'intervention automatique (format YYYYMMDD + N) pour le mode création
$annee_courante = date('Y');
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM interventions WHERE YEAR(date_creation) = ?");
$stmt->execute([$annee_courante]);
$result = $stmt->fetch();
$numero_auto = date('Ymd') . ($result['total'] + 1);

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
        
        // Nouveaux champs additionnels
        $adresse = trim($_POST['adresse'] ?? '');
        $is_drm = isset($_POST['is_drm']) ? 1 : 0;
        $drm_numero = trim($_POST['drm_numero'] ?? '');
        $adresse_chu = trim($_POST['adresse_chu'] ?? '');
        $adresse_prm = trim($_POST['adresse_prm'] ?? '');
        $plan_aramis = isset($_POST['plan_aramis']) ? (int)$_POST['plan_aramis'] : 0;
        
        // Validation
        if (empty($commune) || empty($adresse_pma) || empty($demandeur) || empty($type_event)) {
            throw new Exception('Veuillez remplir tous les champs obligatoires.');
        }
        
        if ($is_edit_mode) {
            // Mise à jour en base de données
            $stmt = $pdo->prepare("
                UPDATE interventions SET
                    commune = ?, adresse = ?, adresse_pma = ?, adresse_cai = ?, adresse_chu = ?, adresse_prm = ?,
                    demandeur = ?, type_event = ?, is_acel = ?, is_cot = ?, is_drm = ?, drm_numero = ?, plan_aramis = ?,
                    numero_intervention = ?, cadres_astreinte = ?, description = ?, cadre_permanence = ?, cadre_astreinte = ?,
                    dtus_permanence = ?, logisticien_astreinte = ?, aide_regulateur = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $commune, $adresse, $adresse_pma, $adresse_cai, $adresse_chu, $adresse_prm, $demandeur, $type_event,
                $is_acel, $is_cot, $is_drm, $drm_numero, $plan_aramis, $numero_intervention, $cadres_astreinte,
                $description, $cadre_permanence, $cadre_astreinte, $dtus_permanence, $logisticien_astreinte, $aide_regulateur,
                $edit_id
            ]);

            // Redirection vers le dashboard
            header("Location: dashboard.php?id=" . $edit_id);
            exit;
        } else {
            // Insertion en base de données
            $stmt = $pdo->prepare("
                INSERT INTO interventions 
                (commune, adresse, adresse_pma, adresse_cai, adresse_chu, adresse_prm, demandeur, type_event, 
                 is_acel, is_cot, is_drm, drm_numero, plan_aramis, numero_intervention, 
                 cadres_astreinte, description, cadre_permanence, cadre_astreinte, dtus_permanence, 
                 logisticien_astreinte, aide_regulateur)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $commune, $adresse, $adresse_pma, $adresse_cai, $adresse_chu, $adresse_prm, $demandeur, $type_event,
                $is_acel, $is_cot, $is_drm, $drm_numero, $plan_aramis, $numero_intervention, $cadres_astreinte, 
                $description, $cadre_permanence, $cadre_astreinte, $dtus_permanence, $logisticien_astreinte, $aide_regulateur
            ]);
            
            $intervention_id = $pdo->lastInsertId();
            
            // Redirection vers le dashboard
            header("Location: dashboard.php?id=" . $intervention_id);
            exit;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Préparation des valeurs par défaut pour le formulaire
function field_value($name, $intervention_to_edit, $default = '') {
    if (isset($_POST[$name])) {
        return trim($_POST[$name]);
    }
    if ($intervention_to_edit && isset($intervention_to_edit[$name])) {
        return $intervention_to_edit[$name];
    }
    return $default;
}

function field_checked($name, $intervention_to_edit) {
    if (isset($_POST[$name])) {
        return true;
    }
    if ($intervention_to_edit && !empty($intervention_to_edit[$name])) {
        return (bool)$intervention_to_edit[$name];
    }
    return false;
}

// Valeurs pour les champs
$val_cadre_permanence = field_value('cadre_permanence', $intervention_to_edit);
$val_cadre_astreinte = field_value('cadre_astreinte', $intervention_to_edit);
$val_dtus_permanence = field_value('dtus_permanence', $intervention_to_edit);
$val_logisticien_astreinte = field_value('logisticien_astreinte', $intervention_to_edit);
$val_aide_regulateur = field_value('aide_regulateur', $intervention_to_edit);
$val_cadres_astreinte = field_value('cadres_astreinte', $intervention_to_edit);

$val_commune = field_value('commune', $intervention_to_edit);
$val_adresse = field_value('adresse', $intervention_to_edit);
$val_demandeur = field_value('demandeur', $intervention_to_edit);
$val_type_event = field_value('type_event', $intervention_to_edit);

// Numéro d'intervention : automatique en création, valeur BDD en édition
$val_numero_intervention = field_value('numero_intervention', $intervention_to_edit, $numero_auto);

$val_description = field_value('description', $intervention_to_edit);

$val_adresse_pma = field_value('adresse_pma', $intervention_to_edit);
$val_adresse_cai = field_value('adresse_cai', $intervention_to_edit);
$val_adresse_chu = field_value('adresse_chu', $intervention_to_edit);
$val_adresse_prm = field_value('adresse_prm', $intervention_to_edit);

$checked_is_acel = field_checked('is_acel', $intervention_to_edit);
$checked_is_cot = field_checked('is_cot', $intervention_to_edit);
$checked_is_drm = field_checked('is_drm', $intervention_to_edit);

$val_drm_numero = field_value('drm_numero', $intervention_to_edit);
$val_plan_aramis = field_value('plan_aramis', $intervention_to_edit, '0');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php require_once __DIR__ . '/includes/head.php'; ?>
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
                        <h4 class="mb-0 text-danger">
                            <i class="bi bi-plus-circle"></i>
                            <?php echo $is_edit_mode ? "Modifier l'Intervention" : "Créer une Nouvelle Intervention"; ?>
                        </h4>
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
                                                   name="cadre_permanence" placeholder="Nom et coordonnées"
                                                   value="<?php echo htmlspecialchars($val_cadre_permanence); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cadre_astreinte" class="form-label">Cadre Astreinte</label>
                                            <input type="text" class="form-control" id="cadre_astreinte" 
                                                   name="cadre_astreinte" placeholder="Nom et coordonnées"
                                                   value="<?php echo htmlspecialchars($val_cadre_astreinte); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="dtus_permanence" class="form-label">DTUS Permanence</label>
                                            <input type="text" class="form-control" id="dtus_permanence" 
                                                   name="dtus_permanence" placeholder="Nom et coordonnées"
                                                   value="<?php echo htmlspecialchars($val_dtus_permanence); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="logisticien_astreinte" class="form-label">Logisticien Astreinte</label>
                                            <input type="text" class="form-control" id="logisticien_astreinte" 
                                                   name="logisticien_astreinte" placeholder="Nom et coordonnées"
                                                   value="<?php echo htmlspecialchars($val_logisticien_astreinte); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="aide_regulateur" class="form-label">Aide Régulateur</label>
                                            <input type="text" class="form-control" id="aide_regulateur" 
                                                   name="aide_regulateur" placeholder="Nom et coordonnées"
                                                   value="<?php echo htmlspecialchars($val_aide_regulateur); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cadres_astreinte" class="form-label">Autres Cadres d'Astreinte</label>
                                            <textarea class="form-control" id="cadres_astreinte" name="cadres_astreinte" rows="2" 
                                                      placeholder="Autres cadres et coordonnées"><?php echo htmlspecialchars($val_cadres_astreinte); ?></textarea>
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
                                        <div class="col-md-4">
                                            <label for="commune" class="form-label">Commune <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="commune" name="commune" required
                                                   value="<?php echo htmlspecialchars($val_commune); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="adresse" class="form-label">Adresse</label>
                                            <input type="text" class="form-control" id="adresse" name="adresse" 
                                                   placeholder="Adresse précise"
                                                   value="<?php echo htmlspecialchars($val_adresse); ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="demandeur" class="form-label">Demandeur <span class="text-danger">*</span></label>
                                            <select class="form-select" id="demandeur" name="demandeur" required>
                                                <option value="">-- Sélectionner --</option>
                                                <?php
                                                $options_demandeur = ['SAMU', 'VIGIE 92', 'BSPP', 'Préfecture', 'PC CRF', 'Autre'];
                                                foreach ($options_demandeur as $opt) {
                                                    $selected = ($val_demandeur === $opt) ? 'selected' : '';
                                                    echo "<option value=\"" . htmlspecialchars($opt) . "\" {$selected}>" . htmlspecialchars($opt) . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="type_event" class="form-label">Type d'Événement <span class="text-danger">*</span></label>
                                            <select class="form-select" id="type_event" name="type_event" required>
                                                <option value="">-- Sélectionner --</option>
                                                <?php
                                                $options_event = ['Incendie', 'Inondation', 'Accident', 'Attentat', "Événement Public", 'Autre'];
                                                foreach ($options_event as $opt) {
                                                    $selected = ($val_type_event === $opt) ? 'selected' : '';
                                                    echo "<option value=\"" . htmlspecialchars($opt) . "\" {$selected}>" . htmlspecialchars($opt) . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="numero_intervention" class="form-label">Numéro d'Intervention</label>
                                            <input type="text" class="form-control" id="numero_intervention" 
                                                   name="numero_intervention"
                                                   value="<?php echo htmlspecialchars($val_numero_intervention); ?>">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="is_drm" name="is_drm" value="1"
                                                    <?php echo $checked_is_drm ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_drm">
                                                    DRM
                                                </label>
                                            </div>
                                            <div id="drm_numero_group" style="display: none;">
                                                <label for="drm_numero" class="form-label">Numéro DRM</label>
                                                <input type="text" class="form-control" id="drm_numero" name="drm_numero" 
                                                       placeholder="Numéro DRM" pattern="[0-9]*" inputmode="numeric"
                                                       value="<?php echo htmlspecialchars($val_drm_numero); ?>">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                        <textarea class="form-control" id="description" name="description" rows="4" 
                                                  placeholder="Détails de la situation, contexte, besoins identifiés..." required><?php echo htmlspecialchars($val_description); ?></textarea>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_acel" name="is_acel" value="1"
                                                    <?php echo $checked_is_acel ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_acel">
                                                    ACEL (Accident Catastrophique à Effet Limité)
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="is_cot" name="is_cot" value="1"
                                                    <?php echo $checked_is_cot ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="is_cot">
                                                    Centre Opérationnel Territorial (COT)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" id="plan_aramis" name="plan_aramis" value="<?php echo htmlspecialchars($val_plan_aramis); ?>">
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
                                                   placeholder="Adresse du Point de Médical Avancé" required
                                                   value="<?php echo htmlspecialchars($val_adresse_pma); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="adresse_cai" class="form-label">Adresse CAI</label>
                                            <input type="text" class="form-control" id="adresse_cai" name="adresse_cai" 
                                                   placeholder="Adresse du Centre d'Accueil et d'Information"
                                                   value="<?php echo htmlspecialchars($val_adresse_cai); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="adresse_chu" class="form-label">Adresse CHU</label>
                                            <input type="text" class="form-control" id="adresse_chu" name="adresse_chu" 
                                                   placeholder="Adresse du Centre Hospitalier Universitaire"
                                                   value="<?php echo htmlspecialchars($val_adresse_chu); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="adresse_prm" class="form-label">Adresse PRM</label>
                                            <input type="text" class="form-control" id="adresse_prm" name="adresse_prm" 
                                                   placeholder="Adresse du Poste de Régulation Médicale"
                                                   value="<?php echo htmlspecialchars($val_adresse_prm); ?>">
                                        </div>
                                    </div>
                                </div>
                            </fieldset>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-danger btn-lg">
                                    <i class="bi bi-check-circle"></i>
                                    <?php echo $is_edit_mode ? "Enregistrer les modifications" : "Créer l'Intervention"; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Modale pour le Plan ARAMIS -->
    <div class="modal fade" id="modalAramis" tabindex="-1" aria-labelledby="modalAramisLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAramisLabel">Plan ARAMIS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Est-ce dans le cadre du plan ARAMIS ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" onclick="setPlanAramis(1)" data-bs-dismiss="modal">Oui</button>
                    <button type="button" class="btn btn-secondary" onclick="setPlanAramis(0)" data-bs-dismiss="modal">Non</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Gestion de l'affichage du champ DRM numéro
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxDrm = document.getElementById('is_drm');
            const drmNumeroGroup = document.getElementById('drm_numero_group');
            
            function updateDrmVisibility() {
                if (checkboxDrm.checked) {
                    drmNumeroGroup.style.display = 'block';
                } else {
                    drmNumeroGroup.style.display = 'none';
                }
            }

            checkboxDrm.addEventListener('change', function() {
                updateDrmVisibility();
                if (!this.checked) {
                    document.getElementById('drm_numero').value = '';
                }
            });

            // Appliquer l'état initial (création / édition)
            updateDrmVisibility();
            
            // Gestion de la modale ARAMIS quand COT est coché
            const checkboxCot = document.getElementById('is_cot');
            const modalAramis = new bootstrap.Modal(document.getElementById('modalAramis'));
            
            checkboxCot.addEventListener('change', function() {
                if (this.checked) {
                    // Ouvrir la modale
                    modalAramis.show();
                } else {
                    // Si on décoche COT, remettre plan_aramis à 0
                    document.getElementById('plan_aramis').value = 0;
                }
            });
        });
        
        // Fonction pour définir le plan ARAMIS
        function setPlanAramis(value) {
            document.getElementById('plan_aramis').value = value;
        }
    </script>
</body>
</html>

