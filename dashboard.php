<?php
require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

$intervention_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$intervention_id) {
    header("Location: index.php");
    exit;
}

// Récupération de l'intervention
$stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
$stmt->execute([$intervention_id]);
$intervention = $stmt->fetch();

if (!$intervention) {
    die("Intervention introuvable.");
}

// Traitement de l'ajout de moyen
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_moyen') {
    $type = $_POST['type_moyen'] ?? '';
    $nom_indicatif = trim($_POST['nom_indicatif'] ?? '');
    
    // Récupérer les valeurs d'équipage (gestion des valeurs vides : convertit "" en 0)
    $nb_pse = isset($_POST['nb_pse']) && $_POST['nb_pse'] !== '' ? (int)$_POST['nb_pse'] : (isset($_POST['hidden-nb-pse']) && $_POST['hidden-nb-pse'] !== '' ? (int)$_POST['hidden-nb-pse'] : 0);
    $nb_ch = isset($_POST['nb_ch']) && $_POST['nb_ch'] !== '' ? (int)$_POST['nb_ch'] : (isset($_POST['hidden-nb-ch']) && $_POST['hidden-nb-ch'] !== '' ? (int)$_POST['hidden-nb-ch'] : 0);
    $nb_ci = isset($_POST['nb_ci']) && $_POST['nb_ci'] !== '' ? (int)$_POST['nb_ci'] : (isset($_POST['hidden-nb-ci']) && $_POST['hidden-nb-ci'] !== '' ? (int)$_POST['hidden-nb-ci'] : 0);
    $nb_cadre_local = isset($_POST['nb_cadre_local']) && $_POST['nb_cadre_local'] !== '' ? (int)$_POST['nb_cadre_local'] : (isset($_POST['hidden-nb-cadre-local']) && $_POST['hidden-nb-cadre-local'] !== '' ? (int)$_POST['hidden-nb-cadre-local'] : 0);
    $nb_cadre_dept = isset($_POST['nb_cadre_dept']) && $_POST['nb_cadre_dept'] !== '' ? (int)$_POST['nb_cadre_dept'] : (isset($_POST['hidden-nb-cadre-dept']) && $_POST['hidden-nb-cadre-dept'] !== '' ? (int)$_POST['hidden-nb-cadre-dept'] : 0);
    $nb_logisticien = isset($_POST['nb_logisticien']) && $_POST['nb_logisticien'] !== '' ? (int)$_POST['nb_logisticien'] : (isset($_POST['hidden-nb-logisticien']) && $_POST['hidden-nb-logisticien'] !== '' ? (int)$_POST['hidden-nb-logisticien'] : 0);
    
    if ($type && $nom_indicatif) {
        $stmt = $pdo->prepare("INSERT INTO moyens (intervention_id, type, status, nom_indicatif, nb_pse, nb_ch, nb_ci, nb_cadre_local, nb_cadre_dept, nb_logisticien) VALUES (?, ?, 'dispo', ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$intervention_id, $type, $nom_indicatif, $nb_pse, $nb_ch, $nb_ci, $nb_cadre_local, $nb_cadre_dept, $nb_logisticien]);
        header("Location: dashboard.php?id=" . $intervention_id);
        exit;
    }
}

// Traitement de la mise à jour du dénombrement terrain
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_denombrement') {
    $nb_ur = (int)$_POST['nb_ur'];
    $nb_ua = (int)$_POST['nb_ua'];
    $nb_dcd = (int)$_POST['nb_dcd'];
    $nb_impliques = (int)$_POST['nb_impliques'];
    
    $stmt = $pdo->prepare("UPDATE interventions SET nb_ur = ?, nb_ua = ?, nb_dcd = ?, nb_impliques = ? WHERE id = ?");
    $stmt->execute([$nb_ur, $nb_ua, $nb_dcd, $nb_impliques, $intervention_id]);
    header("Location: dashboard.php?id=" . $intervention_id);
    exit;
}

// Traitement du changement de statut
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_statut') {
    $nouveau_statut = $_POST['statut'] ?? '';
    if (in_array($nouveau_statut, ['En cours', 'Cloturé', 'Test'])) {
        $stmt = $pdo->prepare("UPDATE interventions SET statut = ? WHERE id = ?");
        $stmt->execute([$nouveau_statut, $intervention_id]);
        header("Location: dashboard.php?id=" . $intervention_id);
        exit;
    }
}

// Déterminer si l'intervention est verrouillée (statut = 'Clôturé')
$statut_actuel = $intervention['statut'] ?? 'En cours';
$is_locked = ($statut_actuel === 'Cloturé');

// Récupération des moyens
$stmt = $pdo->prepare("SELECT * FROM moyens WHERE intervention_id = ? ORDER BY type, nom_indicatif");
$stmt->execute([$intervention_id]);
$moyens = $stmt->fetchAll();

$moyens_dispo = array_filter($moyens, fn($m) => $m['status'] === 'dispo');
$moyens_engage = array_filter($moyens, fn($m) => $m['status'] === 'engage');

// Récupération de la main courante (derniers messages)
$stmt = $pdo->prepare("SELECT * FROM main_courante WHERE intervention_id = ? ORDER BY horodatage DESC LIMIT 10");
$stmt->execute([$intervention_id]);
$messages_recent = $stmt->fetchAll();

// Récupération de TOUS les presets de messages (catégorie 'message')
$stmt = $pdo->prepare("SELECT texte FROM presets_messages WHERE categorie = 'message' ORDER BY texte");
$stmt->execute();
$presets_messages = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Calcul des totaux
$total_vpsp = count(array_filter($moyens_engage, fn($m) => $m['type'] === 'VPSP'));
$total_vl = count(array_filter($moyens_engage, fn($m) => $m['type'] === 'VL'));

// Calcul du total des bénévoles : somme de toutes les qualifications pour tous les moyens engagés
$total_benevoles = 0;
foreach ($moyens_engage as $moyen) {
    $total_benevoles += (int)($moyen['nb_pse'] ?? 0);
    $total_benevoles += (int)($moyen['nb_ch'] ?? 0);
    $total_benevoles += (int)($moyen['nb_ci'] ?? 0);
    $total_benevoles += (int)($moyen['nb_cadre_local'] ?? 0);
    $total_benevoles += (int)($moyen['nb_cadre_dept'] ?? 0);
    $total_benevoles += (int)($moyen['nb_logisticien'] ?? 0);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Intervention #<?php echo $intervention_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        /* Correction : Padding horizontal aligné avec le reste */
        .zone-info {
            background-color: #e9ecef;
            border-bottom: 1px solid #dee2e6;
            padding: 8px 0;
            font-size: 0.85em;
            min-height: 40px;
        }
        .compteur-bilan {
            font-size: 3rem;
            font-weight: bold;
            color: #212529;
        }
        .btn-compteur {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            border-radius: 50%;
        }
        .moyen-item {
            padding: 10px;
            margin-bottom: 8px;
            background-color: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn-fleche {
            width: 35px;
            height: 35px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        /* Timeline Main Courante */
        .timeline-container {
            position: relative;
            padding-left: 30px;
        }
        .timeline-line {
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #dee2e6;
        }
        .timeline-item {
            position: relative;
            margin-bottom: 15px;
            padding-left: 20px;
        }
        .timeline-bullet {
            position: absolute;
            left: -22px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #D6001C;
            border: 2px solid white;
            box-shadow: 0 0 0 2px #dee2e6;
        }
        .timeline-content {
            background-color: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        .timeline-time {
            font-size: 0.75em;
            color: #6c757d;
            font-weight: 600;
        }
        .timeline-header {
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .timeline-message {
            color: #212529;
            font-size: 0.9em;
        }
        /* Couleurs personnalisées pour le statut Test */
        .bg-purple {
            background-color: #6f42c1 !important;
        }
        .text-purple {
            color: #6f42c1 !important;
        }
        .btn-purple {
            background-color: #6f42c1;
            border-color: #6f42c1;
            color: white;
        }
        .btn-purple:hover {
            background-color: #5a32a3;
            border-color: #5a32a3;
            color: white;
        }
        .alert-locked {
            background-color: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        
        /* Style boutons presets */
        .preset-btn.active {
            background-color: #0d6efd !important;
            color: white !important;
            border-color: #0d6efd !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white border-bottom shadow-sm">
        <div class="container-fluid px-4">
            <span class="navbar-brand mb-0">
                <i class="bi bi-heart-pulse-fill text-danger"></i>
                <span class="text-danger fw-bold">Intervention #<?php echo $intervention_id; ?> - <?php echo htmlspecialchars($intervention['commune']); ?></span>
            </span>
            <div class="d-flex align-items-center gap-2">
                <div class="dropdown">
                    <button class="btn btn-sm dropdown-toggle <?php 
                        if ($statut_actuel === 'En cours') echo 'btn-success';
                        elseif ($statut_actuel === 'Cloturé') echo 'btn-danger';
                        else echo 'btn-purple';
                    ?>" type="button" id="dropdownStatut" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-<?php echo $statut_actuel === 'Cloturé' ? 'lock-fill' : 'circle-fill'; ?>"></i>
                        <?php echo htmlspecialchars($statut_actuel); ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownStatut">
                        <li>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="change_statut">
                                <input type="hidden" name="statut" value="En cours">
                                <button type="submit" class="dropdown-item <?php echo $statut_actuel === 'En cours' ? 'active' : ''; ?>">
                                    <i class="bi bi-circle-fill text-success"></i> En cours
                                </button>
                            </form>
                        </li>
                        <li>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="change_statut">
                                <input type="hidden" name="statut" value="Cloturé">
                                <button type="submit" class="dropdown-item <?php echo $statut_actuel === 'Cloturé' ? 'active' : ''; ?>">
                                    <i class="bi bi-lock-fill text-danger"></i> Clôturé
                                </button>
                            </form>
                        </li>
                        <li>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="action" value="change_statut">
                                <input type="hidden" name="statut" value="Test">
                                <button type="submit" class="dropdown-item <?php echo $statut_actuel === 'Test' ? 'active' : ''; ?>">
                                    <i class="bi bi-circle-fill text-purple"></i> Test
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
                <a href="history.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-clock-history"></i> Historique
                </a>
                <a href="export_pdf.php?id=<?php echo $intervention_id; ?>" class="btn btn-outline-danger btn-sm" target="_blank">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </a>
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-plus-circle"></i> Nouvelle
                </a>
                <span class="text-muted"><?php echo htmlspecialchars($_SESSION['user']['nom']); ?></span>
            </div>
        </div>
    </nav>

    <?php if ($is_locked): ?>
    <div class="alert alert-locked alert-dismissible fade show m-0 border-0 rounded-0" role="alert">
        <i class="bi bi-lock-fill"></i> <strong>Mission Clôturée</strong> - Modification impossible. L'intervention est en lecture seule.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="zone-info">
        <div class="container-fluid px-4">
            <div class="d-flex align-items-center gap-3 flex-wrap" style="min-height: 40px;">
                <span><i class="bi bi-person text-muted"></i> <strong>Cadre Perm:</strong> <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo htmlspecialchars($intervention['cadre_permanence'] ?: 'Non renseigné'); ?>"><?php echo htmlspecialchars($intervention['cadre_permanence'] ?: 'Non renseigné'); ?></span></span>
                <span class="text-muted">|</span>
                <span><i class="bi bi-person-check text-muted"></i> <strong>Cadre Astreinte:</strong> <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo htmlspecialchars($intervention['cadre_astreinte'] ?: 'Non renseigné'); ?>"><?php echo htmlspecialchars($intervention['cadre_astreinte'] ?: 'Non renseigné'); ?></span></span>
                <span class="text-muted">|</span>
                <span><i class="bi bi-building text-muted"></i> <strong>DTUS:</strong> <span class="text-truncate d-inline-block" style="max-width: 120px;" title="<?php echo htmlspecialchars($intervention['dtus_permanence'] ?? 'Non renseigné'); ?>"><?php echo htmlspecialchars($intervention['dtus_permanence'] ?? 'Non renseigné'); ?></span></span>
                <span class="text-muted">|</span>
                <span><i class="bi bi-geo-alt text-muted"></i> <strong>PMA:</strong> <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo htmlspecialchars($intervention['adresse_pma']); ?>"><?php echo htmlspecialchars($intervention['adresse_pma']); ?></span></span>
            </div>
        </div>
    </div>

    <div class="container-fluid mt-3 px-4">
        <div class="row">
            <div class="col-md-5">
                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-secondary"><i class="bi bi-people"></i> Dénombrement terrain</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($is_locked): ?>
                            <div class="row g-2">
                                <div class="col-3">
                                    <label class="form-label text-muted small">UR</label>
                                    <div class="form-control form-control-sm bg-light"><?php echo $intervention['nb_ur'] ?? 0; ?></div>
                                </div>
                                <div class="col-3">
                                    <label class="form-label text-muted small">UA</label>
                                    <div class="form-control form-control-sm bg-light"><?php echo $intervention['nb_ua'] ?? 0; ?></div>
                                </div>
                                <div class="col-3">
                                    <label class="form-label text-muted small">DCD</label>
                                    <div class="form-control form-control-sm bg-light"><?php echo $intervention['nb_dcd'] ?? 0; ?></div>
                                </div>
                                <div class="col-3">
                                    <label class="form-label text-muted small">Impliqués</label>
                                    <div class="form-control form-control-sm bg-light"><?php echo $intervention['nb_impliques'] ?? 0; ?></div>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="" id="form-denombrement">
                                <input type="hidden" name="action" value="update_denombrement">
                                <div class="row g-2 align-items-end">
                                    <div class="col-3">
                                        <label class="form-label text-muted small">UR</label>
                                        <input type="number" class="form-control form-control-sm" name="nb_ur" 
                                               id="input-nb-ur" value="<?php echo $intervention['nb_ur'] ?? 0; ?>" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label text-muted small">UA</label>
                                        <input type="number" class="form-control form-control-sm" name="nb_ua" 
                                               id="input-nb-ua" value="<?php echo $intervention['nb_ua'] ?? 0; ?>" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label text-muted small">DCD</label>
                                        <input type="number" class="form-control form-control-sm" name="nb_dcd" 
                                               id="input-nb-dcd" value="<?php echo $intervention['nb_dcd'] ?? 0; ?>" min="0">
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label text-muted small">Impliqués</label>
                                        <input type="number" class="form-control form-control-sm" name="nb_impliques" 
                                               id="input-nb-impliques" value="<?php echo $intervention['nb_impliques'] ?? 0; ?>" min="0">
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <i class="bi bi-save"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card shadow-sm mb-3">
                    <div class="card-header bg-white border-bottom">
                        <h5 class="mb-0 text-secondary"><i class="bi bi-truck"></i> Gestion des Moyens</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$is_locked): ?>
                        <form method="POST" action="" class="mb-3 pb-3 border-bottom" id="form-ajout-moyen">
                            <input type="hidden" name="action" value="add_moyen">
                            <div class="row g-2">
                                <div class="col-2">
                                    <select class="form-select form-select-sm" name="type_moyen" id="select-type-moyen" required>
                                        <option value="">Type</option>
                                        <option value="VPSP">VPSP</option>
                                        <option value="VL">VL</option>
                                        <option value="MINIBUS">MINIBUS</option>
                                        <option value="ETIR">ETIR</option>
                                        <option value="BENEVOLE">BENEVOLE</option>
                                        <option value="CADRE">CADRE</option>
                                        <option value="VPSP_PCPS">VPSP_PCPS</option>
                                        <option value="UMH">UMH</option>
                                        <option value="GROUPE_BSPP">GROUPE_BSPP</option>
                                        <option value="Autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-3">
                                    <input type="text" class="form-control form-control-sm" name="nom_indicatif" 
                                           placeholder="Nom/Indicatif" required>
                                </div>
                                <div class="col-7" id="groupe-equipage" style="display: none;">
                                    <div class="row g-1">
                                        <div class="col-2">
                                            <input type="number" class="form-control form-control-sm" name="nb_pse" 
                                                   min="0" value="" placeholder="PSE">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control form-control-sm" name="nb_ch" 
                                                   min="0" value="" placeholder="CH">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control form-control-sm" name="nb_ci" 
                                                   min="0" value="" placeholder="CI">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control form-control-sm" name="nb_cadre_local" 
                                                   min="0" value="" placeholder="C.Loc">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control form-control-sm" name="nb_cadre_dept" 
                                                   min="0" value="" placeholder="C.Dep">
                                        </div>
                                        <div class="col-2">
                                            <input type="number" class="form-control form-control-sm" name="nb_logisticien" 
                                                   min="0" value="" placeholder="Log">
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="hidden-nb-pse" id="hidden-nb-pse" value="0">
                                <input type="hidden" name="hidden-nb-ch" id="hidden-nb-ch" value="0">
                                <input type="hidden" name="hidden-nb-ci" id="hidden-nb-ci" value="0">
                                <input type="hidden" name="hidden-nb-cadre-local" id="hidden-nb-cadre-local" value="0">
                                <input type="hidden" name="hidden-nb-cadre-dept" id="hidden-nb-cadre-dept" value="0">
                                <input type="hidden" name="hidden-nb-logisticien" id="hidden-nb-logisticien" value="0">
                                <div class="col-2">
                                    <button type="submit" class="btn btn-danger btn-sm w-100">
                                        <i class="bi bi-plus"></i> Ajouter
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>

                        <div class="mb-3">
                            <h6 class="text-success mb-2"><i class="bi bi-check-circle"></i> Disponibles (<?php echo count($moyens_dispo); ?>)</h6>
                            <div id="liste-dispo">
                                <?php if (empty($moyens_dispo)): ?>
                                    <p class="text-muted small">Aucun moyen disponible</p>
                                <?php else: ?>
                                    <?php foreach ($moyens_dispo as $moyen): 
                                        $nb_pse = (int)($moyen['nb_pse'] ?? 0);
                                        $nb_ch = (int)($moyen['nb_ch'] ?? 0);
                                        $nb_ci = (int)($moyen['nb_ci'] ?? 0);
                                        $nb_cadre_local = (int)($moyen['nb_cadre_local'] ?? 0);
                                        $nb_cadre_dept = (int)($moyen['nb_cadre_dept'] ?? 0);
                                        $nb_logisticien = (int)($moyen['nb_logisticien'] ?? 0);
                                        
                                        // Couleur du badge selon le type
                                        $badge_class = 'bg-primary';
                                        if ($moyen['type'] === 'VL') $badge_class = 'bg-info';
                                        elseif ($moyen['type'] === 'Autre') $badge_class = 'bg-secondary';
                                        elseif ($moyen['type'] === 'BENEVOLE' || $moyen['type'] === 'Benevole') $badge_class = 'bg-warning text-dark';
                                    ?>
                                        <div class="moyen-item" data-moyen-id="<?php echo $moyen['id']; ?>">
                                            <div class="view-mode d-flex align-items-center gap-2" style="flex: 1;">
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($moyen['type']); ?></span>
                                                <strong><?php echo htmlspecialchars($moyen['nom_indicatif']); ?></strong>
                                                <div class="d-flex gap-1">
                                                    <?php if ($nb_pse > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_pse; ?> PSE</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_ch > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_ch; ?> CH</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_ci > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_ci; ?> CI</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_cadre_local > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_cadre_local; ?> C.Loc</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_cadre_dept > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_cadre_dept; ?> C.Dep</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_logisticien > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_logisticien; ?> Log</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="edit-mode d-none d-flex align-items-center gap-2 flex-wrap" style="flex: 1;">
                                                <select class="form-select form-select-sm" name="type" style="width: auto; max-width: 120px;">
                                                    <option value="VPSP" <?php echo $moyen['type'] === 'VPSP' ? 'selected' : ''; ?>>VPSP</option>
                                                    <option value="VL" <?php echo $moyen['type'] === 'VL' ? 'selected' : ''; ?>>VL</option>
                                                    <option value="MINIBUS" <?php echo $moyen['type'] === 'MINIBUS' ? 'selected' : ''; ?>>MINIBUS</option>
                                                    <option value="ETIR" <?php echo $moyen['type'] === 'ETIR' ? 'selected' : ''; ?>>ETIR</option>
                                                    <option value="BENEVOLE" <?php echo ($moyen['type'] === 'BENEVOLE' || $moyen['type'] === 'Benevole') ? 'selected' : ''; ?>>BENEVOLE</option>
                                                    <option value="CADRE" <?php echo $moyen['type'] === 'CADRE' ? 'selected' : ''; ?>>CADRE</option>
                                                    <option value="VPSP_PCPS" <?php echo $moyen['type'] === 'VPSP_PCPS' ? 'selected' : ''; ?>>VPSP_PCPS</option>
                                                    <option value="UMH" <?php echo $moyen['type'] === 'UMH' ? 'selected' : ''; ?>>UMH</option>
                                                    <option value="GROUPE_BSPP" <?php echo $moyen['type'] === 'GROUPE_BSPP' ? 'selected' : ''; ?>>GROUPE_BSPP</option>
                                                    <option value="Autre" <?php echo $moyen['type'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                                </select>
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="nom_indicatif" value="<?php echo htmlspecialchars($moyen['nom_indicatif']); ?>" 
                                                       style="width: auto; min-width: 80px;">
                                                <div class="d-flex gap-1 align-items-end">
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">PSE</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_pse" value="<?php echo $nb_pse; ?>" min="0" 
                                                               style="width: 45px; text-align: center;" placeholder="PSE">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">CH</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_ch" value="<?php echo $nb_ch; ?>" min="0" 
                                                               style="width: 45px; text-align: center;" placeholder="CH">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">CI</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_ci" value="<?php echo $nb_ci; ?>" min="0" 
                                                               style="width: 45px; text-align: center;" placeholder="CI">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">C.Loc</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_cadre_local" value="<?php echo $nb_cadre_local; ?>" min="0" 
                                                               style="width: 50px; text-align: center;" placeholder="C.Loc">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">C.Dep</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_cadre_dept" value="<?php echo $nb_cadre_dept; ?>" min="0" 
                                                               style="width: 50px; text-align: center;" placeholder="C.Dep">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">Log</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_logisticien" value="<?php echo $nb_logisticien; ?>" min="0" 
                                                               style="width: 45px; text-align: center;" placeholder="Log">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex gap-1">
                                                <?php if (!$is_locked): ?>
                                                <button type="button" class="btn btn-sm btn-danger rounded-circle d-none btn-delete-moyen" 
                                                        onclick="supprimerMoyen(<?php echo $moyen['id']; ?>)" 
                                                        title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-moyen" 
                                                        onclick="toggleEditMoyen(<?php echo $moyen['id']; ?>)" 
                                                        title="Modifier">
                                                    <i class="bi bi-gear"></i>
                                                </button>
                                                <button type="button" class="btn btn-success btn-fleche" 
                                                        onclick="changerStatutMoyen(<?php echo $moyen['id']; ?>, 'engage')"
                                                        title="Engager">
                                                    <i class="bi bi-arrow-down"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div>
                            <h6 class="text-danger mb-2"><i class="bi bi-exclamation-triangle"></i> Engagés (<?php echo count($moyens_engage); ?>)</h6>
                            <div id="liste-engage">
                                <?php if (empty($moyens_engage)): ?>
                                    <p class="text-muted small">Aucun moyen engagé</p>
                                <?php else: ?>
                                    <?php foreach ($moyens_engage as $moyen): 
                                        $nb_pse = (int)($moyen['nb_pse'] ?? 0);
                                        $nb_ch = (int)($moyen['nb_ch'] ?? 0);
                                        $nb_ci = (int)($moyen['nb_ci'] ?? 0);
                                        $nb_cadre_local = (int)($moyen['nb_cadre_local'] ?? 0);
                                        $nb_cadre_dept = (int)($moyen['nb_cadre_dept'] ?? 0);
                                        $nb_logisticien = (int)($moyen['nb_logisticien'] ?? 0);
                                        
                                        // Couleur du badge selon le type
                                        $badge_class = 'bg-primary';
                                        if ($moyen['type'] === 'VL') $badge_class = 'bg-info';
                                        elseif ($moyen['type'] === 'Autre') $badge_class = 'bg-secondary';
                                        elseif ($moyen['type'] === 'BENEVOLE' || $moyen['type'] === 'Benevole') $badge_class = 'bg-warning text-dark';
                                    ?>
                                        <div class="moyen-item" data-moyen-id="<?php echo $moyen['id']; ?>">
                                            <div class="view-mode d-flex align-items-center gap-2" style="flex: 1;">
                                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($moyen['type']); ?></span>
                                                <strong><?php echo htmlspecialchars($moyen['nom_indicatif']); ?></strong>
                                                <div class="d-flex gap-1">
                                                    <?php if ($nb_pse > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_pse; ?> PSE</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_ch > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_ch; ?> CH</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_ci > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_ci; ?> CI</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_cadre_local > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_cadre_local; ?> C.Loc</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_cadre_dept > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_cadre_dept; ?> C.Dep</span>
                                                    <?php endif; ?>
                                                    <?php if ($nb_logisticien > 0): ?>
                                                        <span class="badge rounded-pill bg-light text-dark border"><?php echo $nb_logisticien; ?> Log</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <div class="edit-mode d-none d-flex align-items-center gap-2 flex-wrap" style="flex: 1;">
                                                <select class="form-select form-select-sm" name="type" style="width: auto; max-width: 120px;">
                                                    <option value="VPSP" <?php echo $moyen['type'] === 'VPSP' ? 'selected' : ''; ?>>VPSP</option>
                                                    <option value="VL" <?php echo $moyen['type'] === 'VL' ? 'selected' : ''; ?>>VL</option>
                                                    <option value="MINIBUS" <?php echo $moyen['type'] === 'MINIBUS' ? 'selected' : ''; ?>>MINIBUS</option>
                                                    <option value="ETIR" <?php echo $moyen['type'] === 'ETIR' ? 'selected' : ''; ?>>ETIR</option>
                                                    <option value="BENEVOLE" <?php echo ($moyen['type'] === 'BENEVOLE' || $moyen['type'] === 'Benevole') ? 'selected' : ''; ?>>BENEVOLE</option>
                                                    <option value="CADRE" <?php echo $moyen['type'] === 'CADRE' ? 'selected' : ''; ?>>CADRE</option>
                                                    <option value="VPSP_PCPS" <?php echo $moyen['type'] === 'VPSP_PCPS' ? 'selected' : ''; ?>>VPSP_PCPS</option>
                                                    <option value="UMH" <?php echo $moyen['type'] === 'UMH' ? 'selected' : ''; ?>>UMH</option>
                                                    <option value="GROUPE_BSPP" <?php echo $moyen['type'] === 'GROUPE_BSPP' ? 'selected' : ''; ?>>GROUPE_BSPP</option>
                                                    <option value="Autre" <?php echo $moyen['type'] === 'Autre' ? 'selected' : ''; ?>>Autre</option>
                                                </select>
                                                <input type="text" class="form-control form-control-sm" 
                                                       name="nom_indicatif" value="<?php echo htmlspecialchars($moyen['nom_indicatif']); ?>" 
                                                       style="width: auto; min-width: 80px;">
                                                <div class="d-flex gap-1 align-items-end">
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">PSE</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_pse" value="<?php echo $nb_pse; ?>" min="0" 
                                                               style="width: 45px; text-align: center;" placeholder="PSE">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">CH</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_ch" value="<?php echo $nb_ch; ?>" min="0" 
                                                               style="width: 45px; text-align: center;" placeholder="CH">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">CI</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_ci" value="<?php echo $nb_ci; ?>" min="0" 
                                                               style="width: 45px; text-align: center;" placeholder="CI">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">C.Loc</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_cadre_local" value="<?php echo $nb_cadre_local; ?>" min="0" 
                                                               style="width: 50px; text-align: center;" placeholder="C.Loc">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">C.Dep</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_cadre_dept" value="<?php echo $nb_cadre_dept; ?>" min="0" 
                                                               style="width: 50px; text-align: center;" placeholder="C.Dep">
                                                    </div>
                                                    <div class="d-flex flex-column align-items-center me-1">
                                                        <span class="text-muted" style="font-size: 0.65rem; line-height: 1; margin-bottom: 2px;">Log</span>
                                                        <input type="number" class="form-control form-control-sm" 
                                                               name="nb_logisticien" value="<?php echo $nb_logisticien; ?>" min="0" 
                                                               style="width: 45px; text-align: center;" placeholder="Log">
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex gap-1">
                                                <?php if (!$is_locked): ?>
                                                <button type="button" class="btn btn-sm btn-danger rounded-circle d-none btn-delete-moyen" 
                                                        onclick="supprimerMoyen(<?php echo $moyen['id']; ?>)" 
                                                        title="Supprimer">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit-moyen" 
                                                        onclick="toggleEditMoyen(<?php echo $moyen['id']; ?>)" 
                                                        title="Modifier">
                                                    <i class="bi bi-gear"></i>
                                                </button>
                                                <button type="button" class="btn btn-warning btn-fleche" 
                                                        onclick="changerStatutMoyen(<?php echo $moyen['id']; ?>, 'dispo')"
                                                        title="Libérer">
                                                    <i class="bi bi-arrow-up"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mt-3 pt-3 border-top">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-primary fw-bold"><?php echo $total_vpsp; ?></div>
                                    <small class="text-muted">VPSP</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-success fw-bold"><?php echo $total_vl; ?></div>
                                    <small class="text-muted">VL</small>
                                </div>
                                <div class="col-4">
                                    <div class="text-info fw-bold"><?php echo $total_benevoles; ?></div>
                                    <small class="text-muted">Total Bénévoles</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 text-secondary"><i class="bi bi-chat-dots"></i> Main Courante</h5>
                        <div>
                            <?php if (!$is_locked): ?>
                            <button type="button" class="btn btn-outline-secondary btn-sm me-2" id="btn-message-rapide">
                                <i class="bi bi-lightning-charge"></i> Message Rapide
                            </button>
                            <?php endif; ?>
                            <a href="main_courante.php?id=<?php echo $intervention_id; ?>" class="btn btn-outline-danger btn-sm">
                                <i class="bi bi-file-text"></i> Mode Avancé / Historique
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!$is_locked): ?>
                        <div id="form-message-rapide" class="mb-3 pb-3 border-bottom" style="display: none;">
                            <form id="form-ajout-message" onsubmit="return envoyerMessage(event);">
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-light">Expéditeur</span>
                                    <input type="text" class="form-control" id="input-expediteur" 
                                           placeholder="Ex: SAMU" required>
                                </div>
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-light">Destinataire</span>
                                    <input type="text" class="form-control" id="input-destinataire" 
                                           placeholder="Ex: PC CRF" required>
                                </div>
                                <div class="input-group input-group-sm mb-2">
                                    <span class="input-group-text bg-light">Moyen Com.</span>
                                    <select class="form-control" id="select-moyen-com" required>
                                        <option value="">-- Sélectionner --</option>
                                        <option value="Radio">Radio</option>
                                        <option value="Téléphone">Téléphone</option>
                                        <option value="Face à face">Face à face</option>
                                        <option value="Mail">Mail</option>
                                        <option value="Appli">Appli</option>
                                    </select>
                                </div>
                                
                                <input type="hidden" id="hidden-message" name="message" required>
                                
                                <?php if (!empty($presets_messages)): ?>
                                <div class="mb-2">
                                    <small class="text-muted">Sélectionner un message :</small>
                                    <div class="presets-grid mt-1" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 6px; max-height: 200px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px; background-color: #f8f9fa;">
                                            <?php foreach ($presets_messages as $preset): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary preset-btn" 
                                                        data-message="<?php echo htmlspecialchars($preset, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($preset); ?>
                                            </button>
                                            <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="d-flex gap-2 mt-2">
                                    <button type="submit" class="btn btn-danger btn-sm" id="btn-envoyer-message">
                                        <i class="bi bi-send"></i> Envoyer
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="masquerFormulaireMessage()">
                                        Annuler
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>

                        <div id="historique-messages">
                            <?php if (empty($messages_recent)): ?>
                                <p class="text-muted text-center">Aucun message pour le moment</p>
                            <?php else: ?>
                                <div class="timeline-container">
                                    <div class="timeline-line"></div>
                                    <?php foreach ($messages_recent as $msg): ?>
                                        <div class="timeline-item" data-message-id="<?php echo $msg['id']; ?>">
                                            <div class="timeline-bullet"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <div class="timeline-header">
                                                        <strong class="text-danger"><?php echo htmlspecialchars($msg['expediteur']); ?></strong>
                                                        <span class="text-muted"> → </span>
                                                        <strong class="text-success"><?php echo htmlspecialchars($msg['destinataire']); ?></strong>
                                                        <?php if (!empty($msg['moyen_com'])): ?>
                                                            <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($msg['moyen_com']); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="timeline-time">
                                                        <?php echo date('d/m H:i', strtotime($msg['horodatage'])); ?>
                                                    </div>
                                                </div>
                                                <div class="timeline-message"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                                <div class="mt-1">
                                                    <small class="text-muted fst-italic">
                                                        <i class="bi bi-person"></i> Saisi par : <?php echo htmlspecialchars($msg['operateur'] ?? 'Inconnu'); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid px-4 mt-4 mb-4">
        <div class="text-center text-muted small fst-italic border-top pt-3">
            <span class="me-3"><i class="bi bi-info-circle"></i> Légende :</span>
            <span class="me-3"><strong>C.Loc</strong> = Cadre Local</span>
            <span class="me-3"><strong>C.Dep</strong> = Cadre Départemental</span>
            <span><strong>Log</strong> = Logisticien</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const INTERVENTION_ID = <?php echo $intervention_id; ?>;

        // Gestion de l'affichage conditionnel du formulaire d'ajout de moyen
        document.addEventListener('DOMContentLoaded', function() {
            const selectType = document.getElementById('select-type-moyen');
            const groupeEquipage = document.getElementById('groupe-equipage');
            const hiddenNbPse = document.getElementById('hidden-nb-pse');
            const hiddenNbCh = document.getElementById('hidden-nb-ch');
            const hiddenNbCi = document.getElementById('hidden-nb-ci');
            
            // Fonction pour gérer l'affichage des champs équipage
            function gererAffichageEquipage() {
                const typeSelectionne = selectType.value;
                const hiddenNbCadreLocal = document.getElementById('hidden-nb-cadre-local');
                const hiddenNbCadreDept = document.getElementById('hidden-nb-cadre-dept');
                const hiddenNbLogisticien = document.getElementById('hidden-nb-logisticien');
                
                // Types qui nécessitent l'équipage complet
                const typesAvecEquipage = ['VPSP', 'VL', 'MINIBUS', 'ETIR', 'VPSP_PCPS', 'BENEVOLE'];
                
                if (typesAvecEquipage.includes(typeSelectionne)) {
                    groupeEquipage.style.display = 'block';
                    // Désactiver les champs cachés (on utilise les champs visibles)
                    hiddenNbPse.disabled = true;
                    hiddenNbCh.disabled = true;
                    hiddenNbCi.disabled = true;
                    hiddenNbCadreLocal.disabled = true;
                    hiddenNbCadreDept.disabled = true;
                    hiddenNbLogisticien.disabled = true;
                } else {
                    // Cacher les champs équipage pour les autres types
                    groupeEquipage.style.display = 'none';
                    // Activer les champs cachés et mettre à 0
                    hiddenNbPse.disabled = false;
                    hiddenNbCh.disabled = false;
                    hiddenNbCi.disabled = false;
                    hiddenNbCadreLocal.disabled = false;
                    hiddenNbCadreDept.disabled = false;
                    hiddenNbLogisticien.disabled = false;
                    hiddenNbPse.value = 0;
                    hiddenNbCh.value = 0;
                    hiddenNbCi.value = 0;
                    hiddenNbCadreLocal.value = 0;
                    hiddenNbCadreDept.value = 0;
                    hiddenNbLogisticien.value = 0;
                }
            }
            
            // Écouter les changements sur le select
            selectType.addEventListener('change', gererAffichageEquipage);
            
            // Appeler une fois au chargement pour initialiser l'état
            gererAffichageEquipage();
        });

        // Fonction pour modifier le bilan (compteurs +/-)
        function modifierBilan(type, delta) {
            const compteur = document.getElementById('compteur-' + type);
            const input = document.getElementById('input-' + type);
            let valeur = parseInt(compteur.textContent) + delta;
            if (valeur < 0) valeur = 0;
            compteur.textContent = valeur;
            input.value = valeur;
        }

        // Fonction pour changer le statut d'un moyen via AJAX
        function changerStatutMoyen(moyenId, newStatus) {
            // Désactiver le bouton pendant le traitement
            const btn = event.target.closest('button');
            btn.disabled = true;
            
            // Préparer les données
            const data = {
                moyen_id: moyenId,
                intervention_id: INTERVENTION_ID,
                new_status: newStatus
            };

            // Envoyer la requête avec fetch
            fetch('api_moyen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    // Recharger la page pour mettre à jour l'affichage
                    window.location.reload();
                } else {
                    alert('Erreur : ' + (result.error || 'Erreur inconnue'));
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la mise à jour du statut');
                btn.disabled = false;
            });
        }

        // Gestion du widget Main Courante
        document.addEventListener('DOMContentLoaded', function() {
            const btnMessageRapide = document.getElementById('btn-message-rapide');
            const formMessageRapide = document.getElementById('form-message-rapide');
            
            // Toggle du formulaire message rapide
            if (btnMessageRapide && formMessageRapide) {
                btnMessageRapide.addEventListener('click', function() {
                    if (formMessageRapide.style.display === 'none') {
                        formMessageRapide.style.display = 'block';
                        document.getElementById('input-expediteur').focus();
                    } else {
                        formMessageRapide.style.display = 'none';
                    }
                });
            }
        });

        // Fonction pour masquer le formulaire
        function masquerFormulaireMessage() {
            const formMessageRapide = document.getElementById('form-message-rapide');
            if (formMessageRapide) {
                formMessageRapide.style.display = 'none';
                // Vider les champs
                document.getElementById('input-expediteur').value = '';
                document.getElementById('input-destinataire').value = '';
                document.getElementById('select-moyen-com').value = '';
                document.getElementById('hidden-message').value = '';
                // Réinitialiser les boutons presets
                document.querySelectorAll('.preset-btn').forEach(btn => {
                    btn.classList.remove('btn-primary', 'active');
                    btn.classList.add('btn-outline-secondary');
                });
            }
        }
        
        // Gestion de la sélection des presets
        document.addEventListener('DOMContentLoaded', function() {
            // Délégation d'événement pour les boutons presets
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('preset-btn') || e.target.closest('.preset-btn')) {
                    const btn = e.target.classList.contains('preset-btn') ? e.target : e.target.closest('.preset-btn');
                    const message = btn.getAttribute('data-message');
                    
                    // Désélectionner tous les autres boutons
                    document.querySelectorAll('.preset-btn').forEach(b => {
                        b.classList.remove('btn-primary', 'active');
                        b.classList.add('btn-outline-secondary');
                    });
                    
                    // Sélectionner le bouton cliqué
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-primary', 'active');
                    
                    // Stocker le message dans le champ caché
                    document.getElementById('hidden-message').value = message;
                }
            });
        });

        // Fonction pour envoyer un message via AJAX
        function envoyerMessage(event) {
            event.preventDefault();
            
            const expediteur = document.getElementById('input-expediteur').value.trim();
            const destinataire = document.getElementById('input-destinataire').value.trim();
            const moyen_com = document.getElementById('select-moyen-com').value;
            const hiddenMessage = document.getElementById('hidden-message').value.trim();
            const btnEnvoyer = document.getElementById('btn-envoyer-message');
            
            // Validation
            if (!expediteur || !destinataire || !moyen_com) {
                alert('Veuillez remplir tous les champs');
                return false;
            }
            
            // Validation stricte : le message doit être sélectionné
            if (!hiddenMessage) {
                alert('Veuillez sélectionner un message type');
                return false;
            }
            
            const message = hiddenMessage;
            
            // Désactiver le bouton pendant l'envoi
            btnEnvoyer.disabled = true;
            btnEnvoyer.innerHTML = '<i class="bi bi-hourglass-split"></i> Envoi...';
            
            // Préparer les données
            const data = {
                intervention_id: INTERVENTION_ID,
                expediteur: expediteur,
                destinataire: destinataire,
                moyen_com: moyen_com,
                message: message
            };
            
            // Envoyer la requête avec fetch
            fetch('api_add_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    // Vider les champs
                    document.getElementById('input-expediteur').value = '';
                    document.getElementById('input-destinataire').value = '';
                    document.getElementById('select-moyen-com').value = '';
                    document.getElementById('hidden-message').value = '';
                    
                    // Réinitialiser les boutons presets
                    document.querySelectorAll('.preset-btn').forEach(btn => {
                        btn.classList.remove('btn-primary', 'active');
                        btn.classList.add('btn-outline-secondary');
                    });
                    
                    // Ajouter le nouveau message en haut de la timeline
                    ajouterMessageAListe(result.data);
                    
                    // Masquer le formulaire
                    masquerFormulaireMessage();
                } else {
                    alert('Erreur : ' + (result.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'envoi du message');
            })
            .finally(() => {
                // Réactiver le bouton
                btnEnvoyer.disabled = false;
                btnEnvoyer.innerHTML = '<i class="bi bi-send"></i> Envoyer';
            });
            
            return false;
        }

        // Fonction pour ajouter un message à la timeline (en haut)
        function ajouterMessageAListe(messageData) {
            const historiqueMessages = document.getElementById('historique-messages');
            
            // Supprimer le message "Aucun message" s'il existe
            const messageVide = historiqueMessages.querySelector('p.text-muted.text-center');
            if (messageVide) {
                messageVide.remove();
            }
            
            // Créer le conteneur timeline s'il n'existe pas
            let timelineContainer = historiqueMessages.querySelector('.timeline-container');
            if (!timelineContainer) {
                timelineContainer = document.createElement('div');
                timelineContainer.className = 'timeline-container';
                timelineContainer.innerHTML = '<div class="timeline-line"></div>';
                historiqueMessages.appendChild(timelineContainer);
            }
            
            // Créer le nouvel élément message
            const timelineItem = document.createElement('div');
            timelineItem.className = 'timeline-item';
            timelineItem.setAttribute('data-message-id', messageData.id);
            
            // Formater la date
            const date = new Date(messageData.horodatage);
            const dateFormatee = date.toLocaleDateString('fr-FR', { 
                day: '2-digit', 
                month: '2-digit' 
            }) + ' ' + date.toLocaleTimeString('fr-FR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            // Échapper le HTML pour la sécurité
            const escapeHtml = (text) => {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            };
            
            // Formater le message (remplacer les retours à la ligne)
            const messageFormate = escapeHtml(messageData.message).replace(/\n/g, '<br>');
            
            const moyenComBadge = messageData.moyen_com ? `<span class="badge bg-secondary ms-2">${escapeHtml(messageData.moyen_com)}</span>` : '';
            const operateurNom = messageData.operateur || 'Inconnu';
            const operateurInfo = `<div class="mt-1"><small class="text-muted fst-italic"><i class="bi bi-person"></i> Saisi par : ${escapeHtml(operateurNom)}</small></div>`;
            
            timelineItem.innerHTML = `
                <div class="timeline-bullet"></div>
                <div class="timeline-content">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div class="timeline-header">
                            <strong class="text-danger">${escapeHtml(messageData.expediteur)}</strong>
                            <span class="text-muted"> → </span>
                            <strong class="text-success">${escapeHtml(messageData.destinataire)}</strong>
                            ${moyenComBadge}
                        </div>
                        <div class="timeline-time">${dateFormatee}</div>
                    </div>
                    <div class="timeline-message">${messageFormate}</div>
                    ${operateurInfo}
                </div>
            `;
            
            // Insérer en haut de la timeline (après la ligne)
            const timelineLine = timelineContainer.querySelector('.timeline-line');
            timelineContainer.insertBefore(timelineItem, timelineLine.nextSibling);
            
            // Limiter à 10 messages (supprimer le dernier si plus de 10)
            const messages = timelineContainer.querySelectorAll('.timeline-item');
            if (messages.length > 10) {
                messages[messages.length - 1].remove();
            }
        }

        // Gestion de l'édition inline des moyens
        let moyenEnEdition = null;

        function toggleEditMoyen(moyenId) {
            const moyenItem = document.querySelector(`.moyen-item[data-moyen-id="${moyenId}"]`);
            if (!moyenItem) return;

            const btnEdit = moyenItem.querySelector('.btn-edit-moyen');
            const icon = btnEdit.querySelector('i');
            const viewMode = moyenItem.querySelector('.view-mode');
            const editMode = moyenItem.querySelector('.edit-mode');

            const isEditing = !editMode.classList.contains('d-none');

            if (isEditing) {
                sauvegarderMoyen(moyenId, moyenItem);
            } else {
                if (moyenEnEdition && moyenEnEdition !== moyenId) {
                    annulerEdition(moyenEnEdition);
                }

                moyenEnEdition = moyenId;
                viewMode.classList.add('d-none');
                editMode.classList.remove('d-none');

                icon.className = 'bi bi-check-circle';
                btnEdit.title = 'Sauvegarder';
                btnEdit.classList.remove('btn-outline-secondary');
                btnEdit.classList.add('btn-success');
                
                // Afficher le bouton de suppression
                const btnDelete = moyenItem.querySelector('.btn-delete-moyen');
                if (btnDelete) {
                    btnDelete.classList.remove('d-none');
                }
            }
        }

        function annulerEdition(moyenId) {
            const moyenItem = document.querySelector(`.moyen-item[data-moyen-id="${moyenId}"]`);
            if (!moyenItem) return;

            const btnEdit = moyenItem.querySelector('.btn-edit-moyen');
            const icon = btnEdit.querySelector('i');
            const viewMode = moyenItem.querySelector('.view-mode');
            const editMode = moyenItem.querySelector('.edit-mode');

            viewMode.classList.remove('d-none');
            editMode.classList.add('d-none');

            icon.className = 'bi bi-gear';
            btnEdit.title = 'Modifier';
            btnEdit.classList.remove('btn-success');
            btnEdit.classList.add('btn-outline-secondary');

            // Cacher le bouton de suppression
            const btnDelete = moyenItem.querySelector('.btn-delete-moyen');
            if (btnDelete) {
                btnDelete.classList.add('d-none');
            }

            moyenEnEdition = null;
        }

        function sauvegarderMoyen(moyenId, moyenItem) {
            const btnEdit = moyenItem.querySelector('.btn-edit-moyen');
            const icon = btnEdit.querySelector('i');
            const viewMode = moyenItem.querySelector('.view-mode');
            const editMode = moyenItem.querySelector('.edit-mode');

            const type = editMode.querySelector('select[name="type"]').value;
            const nom_indicatif = editMode.querySelector('input[name="nom_indicatif"]').value.trim();
            const nb_pse = parseInt(editMode.querySelector('input[name="nb_pse"]').value) || 0;
            const nb_ch = parseInt(editMode.querySelector('input[name="nb_ch"]').value) || 0;
            const nb_ci = parseInt(editMode.querySelector('input[name="nb_ci"]').value) || 0;
            const nb_cadre_local = parseInt(editMode.querySelector('input[name="nb_cadre_local"]').value) || 0;
            const nb_cadre_dept = parseInt(editMode.querySelector('input[name="nb_cadre_dept"]').value) || 0;
            const nb_logisticien = parseInt(editMode.querySelector('input[name="nb_logisticien"]').value) || 0;

            if (!nom_indicatif) {
                alert('Le nom indicatif est requis');
                return;
            }

            btnEdit.disabled = true;
            icon.className = 'bi bi-hourglass-split';

            const data = {
                moyen_id: moyenId,
                intervention_id: INTERVENTION_ID,
                type: type,
                nom_indicatif: nom_indicatif,
                nb_pse: nb_pse,
                nb_ch: nb_ch,
                nb_ci: nb_ci,
                nb_cadre_local: nb_cadre_local,
                nb_cadre_dept: nb_cadre_dept,
                nb_logisticien: nb_logisticien
            };

            fetch('api_update_moyen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    mettreAJourVueLecture(moyenItem, type, nom_indicatif, nb_pse, nb_ch, nb_ci, nb_cadre_local, nb_cadre_dept, nb_logisticien);
                    editMode.classList.add('d-none');
                    viewMode.classList.remove('d-none');
                    icon.className = 'bi bi-gear';
                    btnEdit.title = 'Modifier';
                    btnEdit.classList.remove('btn-success');
                    btnEdit.classList.add('btn-outline-secondary');
                    
                    // Cacher le bouton de suppression
                    const btnDelete = moyenItem.querySelector('.btn-delete-moyen');
                    if (btnDelete) {
                        btnDelete.classList.add('d-none');
                    }
                    
                    moyenEnEdition = null;
                    mettreAJourTotaux();
                } else {
                    alert('Erreur : ' + (result.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la sauvegarde');
            })
            .finally(() => {
                btnEdit.disabled = false;
            });
        }

        function supprimerMoyen(moyenId) {
            // Demander confirmation
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce moyen ?')) {
                return;
            }

            const moyenItem = document.querySelector(`.moyen-item[data-moyen-id="${moyenId}"]`);
            if (!moyenItem) return;

            const btnDelete = moyenItem.querySelector('.btn-delete-moyen');
            if (btnDelete) {
                btnDelete.disabled = true;
            }

            const data = {
                moyen_id: moyenId,
                intervention_id: INTERVENTION_ID
            };

            fetch('api_delete_moyen.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.status === 'success') {
                    // Supprimer l'élément du DOM
                    moyenItem.remove();
                    
                    // Mettre à jour les totaux
                    mettreAJourTotaux();
                    
                    // Si c'était le moyen en édition, réinitialiser
                    if (moyenEnEdition === moyenId) {
                        moyenEnEdition = null;
                    }
                } else {
                    alert('Erreur : ' + (result.message || 'Erreur inconnue'));
                    if (btnDelete) {
                        btnDelete.disabled = false;
                    }
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de la suppression');
                if (btnDelete) {
                    btnDelete.disabled = false;
                }
            });
        }

        function mettreAJourVueLecture(moyenItem, type, nom_indicatif, nb_pse, nb_ch, nb_ci, nb_cadre_local, nb_cadre_dept, nb_logisticien) {
            const viewMode = moyenItem.querySelector('.view-mode');
            const badge = viewMode.querySelector('.badge');
            const strong = viewMode.querySelector('strong');
            const badgesContainer = viewMode.querySelector('.d-flex.gap-1');

            // Mettre à jour le badge avec la bonne couleur
            let badgeClass = 'bg-primary';
            if (type === 'VL') badgeClass = 'bg-info';
            else if (type === 'Autre') badgeClass = 'bg-secondary';
            else if (type === 'BENEVOLE' || type === 'Benevole') badgeClass = 'bg-warning text-dark';

            badge.className = 'badge ' + badgeClass;
            badge.textContent = type;

            // Mettre à jour le nom
            strong.textContent = nom_indicatif;

            // Mettre à jour les badges d'équipage
            if (badgesContainer) {
                badgesContainer.innerHTML = '';
                if (nb_pse > 0) {
                    const badgePse = document.createElement('span');
                    badgePse.className = 'badge rounded-pill bg-light text-dark border';
                    badgePse.textContent = nb_pse + ' PSE';
                    badgesContainer.appendChild(badgePse);
                }
                if (nb_ch > 0) {
                    const badgeCh = document.createElement('span');
                    badgeCh.className = 'badge rounded-pill bg-light text-dark border';
                    badgeCh.textContent = nb_ch + ' CH';
                    badgesContainer.appendChild(badgeCh);
                }
                if (nb_ci > 0) {
                    const badgeCi = document.createElement('span');
                    badgeCi.className = 'badge rounded-pill bg-light text-dark border';
                    badgeCi.textContent = nb_ci + ' CI';
                    badgesContainer.appendChild(badgeCi);
                }
                if (nb_cadre_local > 0) {
                    const badgeCl = document.createElement('span');
                    badgeCl.className = 'badge rounded-pill bg-light text-dark border';
                    badgeCl.textContent = nb_cadre_local + ' C.Loc';
                    badgesContainer.appendChild(badgeCl);
                }
                if (nb_cadre_dept > 0) {
                    const badgeCd = document.createElement('span');
                    badgeCd.className = 'badge rounded-pill bg-light text-dark border';
                    badgeCd.textContent = nb_cadre_dept + ' C.Dep';
                    badgesContainer.appendChild(badgeCd);
                }
                if (nb_logisticien > 0) {
                    const badgeLog = document.createElement('span');
                    badgeLog.className = 'badge rounded-pill bg-light text-dark border';
                    badgeLog.textContent = nb_logisticien + ' Log';
                    badgesContainer.appendChild(badgeLog);
                }
            }
        }

        function mettreAJourTotaux() {
            const moyensEngages = document.querySelectorAll('#liste-engage .moyen-item');
            let totalVPSP = 0;
            let totalVL = 0;
            let totalBenevoles = 0;

            moyensEngages.forEach(moyenItem => {
                const viewMode = moyenItem.querySelector('.view-mode');
                const editMode = moyenItem.querySelector('.edit-mode');
                
                let type, nb_pse, nb_ch, nb_ci, nb_cadre_local, nb_cadre_dept, nb_logisticien;
                
                if (!editMode.classList.contains('d-none')) {
                    type = editMode.querySelector('select[name="type"]').value;
                    nb_pse = parseInt(editMode.querySelector('input[name="nb_pse"]').value) || 0;
                    nb_ch = parseInt(editMode.querySelector('input[name="nb_ch"]').value) || 0;
                    nb_ci = parseInt(editMode.querySelector('input[name="nb_ci"]').value) || 0;
                    nb_cadre_local = parseInt(editMode.querySelector('input[name="nb_cadre_local"]').value) || 0;
                    nb_cadre_dept = parseInt(editMode.querySelector('input[name="nb_cadre_dept"]').value) || 0;
                    nb_logisticien = parseInt(editMode.querySelector('input[name="nb_logisticien"]').value) || 0;
                } else {
                    const badge = viewMode.querySelector('.badge');
                    type = badge.textContent.trim();
                    const badges = viewMode.querySelectorAll('.badge.rounded-pill');
                    nb_pse = nb_ch = nb_ci = nb_cadre_local = nb_cadre_dept = nb_logisticien = 0;
                    badges.forEach(b => {
                        const text = b.textContent;
                        if (text.includes('PSE')) nb_pse = parseInt(text) || 0;
                        if (text.includes('CH')) nb_ch = parseInt(text) || 0;
                        if (text.includes('CI')) nb_ci = parseInt(text) || 0;
                        if (text.includes('C.Loc')) nb_cadre_local = parseInt(text) || 0;
                        if (text.includes('C.Dep')) nb_cadre_dept = parseInt(text) || 0;
                        if (text.includes('Log')) nb_logisticien = parseInt(text) || 0;
                    });
                }

                if (type === 'VPSP') totalVPSP++;
                if (type === 'VL') totalVL++;
                totalBenevoles += nb_pse + nb_ch + nb_ci + nb_cadre_local + nb_cadre_dept + nb_logisticien;
            });

            const totalVPSPElem = document.querySelector('.text-primary.fw-bold');
            const totalVLElem = document.querySelector('.text-success.fw-bold');
            const totalBenevolesElem = document.querySelector('.text-info.fw-bold');

            if (totalVPSPElem) totalVPSPElem.textContent = totalVPSP;
            if (totalVLElem) totalVLElem.textContent = totalVL;
            if (totalBenevolesElem) totalBenevolesElem.textContent = totalBenevoles;
        }
    </script>
</body>
</html>