<?php
require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

$intervention_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$intervention_id) {
    header("Location: creation.php");
    exit;
}

// Vérification que l'intervention existe
$stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
$stmt->execute([$intervention_id]);
$intervention = $stmt->fetch();

if (!$intervention) {
    die("Intervention introuvable.");
}

// Titre de page
$page_title = "Main Courante - Intervention #{$intervention_id}";

// Traitement de l'ajout d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_message') {
    $expediteur = trim($_POST['expediteur'] ?? '');
    $destinataire = trim($_POST['destinataire'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $moyen_com = trim($_POST['moyen_com'] ?? '');
    $operateur = isset($_SESSION['user']['nom']) ? trim($_SESSION['user']['nom']) : 'Inconnu';
    
    if ($expediteur && $message) {
        $stmt = $pdo->prepare("INSERT INTO main_courante (intervention_id, expediteur, destinataire, message, moyen_com, operateur) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$intervention_id, $expediteur, $destinataire, $message, $moyen_com, $operateur]);
        header("Location: main_courante.php?id=" . $intervention_id);
        exit;
    }
}

// Récupération des messages (ordre chronologique décroissant)
$stmt = $pdo->prepare("SELECT * FROM main_courante WHERE intervention_id = ? ORDER BY horodatage DESC");
$stmt->execute([$intervention_id]);
$messages = $stmt->fetchAll();

// Récupération des presets pour l'autocomplétion (anciens presets)
$stmt = $pdo->prepare("SELECT texte FROM presets_messages WHERE categorie = ? ORDER BY texte");
$presets_expediteur = [];
$presets_destinataire = [];
$presets_message = [];

try {
    $stmt->execute(['expediteur']);
    $presets_expediteur = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt->execute(['destinataire']);
    $presets_destinataire = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $stmt->execute(['message']);
    $presets_message = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Si les anciennes catégories n'existent plus, on continue
}

// Récupération de tous les presets pour les Quick Texts (nouvelles catégories)
$quick_texts = [];
try {
    $stmt_quick = $pdo->prepare("SELECT categorie, titre, contenu FROM presets_messages WHERE categorie IN ('Renseignement', 'Ambiance', 'Demande') ORDER BY categorie, titre");
    $stmt_quick->execute();
    $quick_texts_raw = $stmt_quick->fetchAll(PDO::FETCH_ASSOC);
    
    // Organiser les Quick Texts par catégorie pour faciliter l'accès en JavaScript
    foreach ($quick_texts_raw as $row) {
        $categorie = $row['categorie'];
        if (!isset($quick_texts[$categorie])) {
            $quick_texts[$categorie] = [];
        }
        $quick_texts[$categorie][] = [
            'titre' => $row['titre'] ?? '',
            'contenu' => $row['contenu'] ?? ''
        ];
    }
} catch (PDOException $e) {
    // Si les colonnes titre/contenu n'existent pas encore, on continue avec un tableau vide
    // L'utilisateur devra exécuter le script SQL d'abord
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php require_once __DIR__ . '/includes/head.php'; ?>
    <style>
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
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-danger">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="bi bi-chat-dots"></i> Main Courante
            </span>
            <div>
                <a href="dashboard.php?id=<?php echo $intervention_id; ?>" class="btn btn-outline-light btn-sm me-2">
                    <i class="bi bi-arrow-left"></i> Retour au Dashboard
                </a>
                <span class="text-white">
                    <?php echo htmlspecialchars($_SESSION['user']['nom']); ?>
                </span>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-lg-12">
                <!-- En-tête -->
                <div class="card mb-4 shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">
                            <i class="bi bi-info-circle"></i> Intervention #<?php echo $intervention_id; ?> - 
                            <?php echo htmlspecialchars($intervention['commune']); ?>
                        </h4>
                    </div>
                </div>

                <!-- Formulaire d'ajout de message -->
                <div class="card mb-4 shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Ajouter un Message</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="add_message">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="expediteur" class="form-label">Expéditeur</label>
                                    <input type="text" class="form-control" id="expediteur" name="expediteur" 
                                           list="list-expediteur" placeholder="Saisir ou sélectionner" required>
                                    <datalist id="list-expediteur">
                                        <?php foreach ($presets_expediteur as $preset): ?>
                                            <option value="<?php echo htmlspecialchars($preset); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                    <small class="text-muted">Vous pouvez saisir un texte libre ou choisir dans la liste</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="destinataire" class="form-label">Destinataire</label>
                                    <input type="text" class="form-control" id="destinataire" name="destinataire" 
                                           list="list-destinataire" placeholder="Saisir ou sélectionner (optionnel)">
                                    <datalist id="list-destinataire">
                                        <?php foreach ($presets_destinataire as $preset): ?>
                                            <option value="<?php echo htmlspecialchars($preset); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                    <small class="text-muted">Vous pouvez saisir un texte libre ou choisir dans la liste (optionnel)</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="moyen_com" class="form-label">Moyen de communication</label>
                                    <select class="form-control" id="moyen_com" name="moyen_com">
                                        <option value="" selected>Aucun (Facultatif)</option>
                                        <option value="Radio">Radio</option>
                                        <option value="Téléphone">Téléphone</option>
                                        <option value="Face à face">Face à face</option>
                                        <option value="Mail">Mail</option>
                                        <option value="Appli">Appli</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="categorie_quick" class="form-label">Catégorie</label>
                                    <select class="form-control" id="categorie_quick" name="categorie_quick">
                                        <option value="" selected>Sélectionner une catégorie</option>
                                        <option value="Renseignement">Renseignement</option>
                                        <option value="Ambiance">Ambiance</option>
                                        <option value="Demande">Demande</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="message_predefini" class="form-label">Message Prédéfini</label>
                                    <select class="form-control" id="message_predefini" name="message_predefini" disabled>
                                        <option value="">Sélectionner une catégorie d'abord</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="3" 
                                          placeholder="Saisir le message..." required></textarea>
                                <small class="text-muted">Suggestions rapides :</small>
                                <div class="mt-2" style="max-height: 150px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 8px; background-color: #f8f9fa;">
                                    <?php foreach ($presets_message as $preset): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1" 
                                                onclick="document.getElementById('message').value = '<?php echo addslashes($preset); ?>'">
                                            <?php echo htmlspecialchars($preset); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-send"></i> Envoyer le Message
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des messages -->
                <div class="card shadow">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history"></i> Historique des Messages 
                            <span class="badge bg-light text-dark"><?php echo count($messages); ?></span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($messages)): ?>
                            <p class="text-muted text-center">Aucun message pour le moment</p>
                        <?php else: ?>
                            <div class="timeline-container">
                                <div class="timeline-line"></div>
                                <?php foreach ($messages as $msg): ?>
                                    <div class="timeline-item" data-message-id="<?php echo $msg['id']; ?>">
                                        <div class="timeline-bullet"></div>
                                        <div class="timeline-content">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div class="timeline-header">
                                                    <strong class="text-danger"><?php echo htmlspecialchars($msg['expediteur']); ?></strong>
                                                    <?php if (!empty($msg['destinataire'])): ?>
                                                        <span class="text-muted"> → </span>
                                                        <strong class="text-success"><?php echo htmlspecialchars($msg['destinataire']); ?></strong>
                                                    <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Données des Quick Texts passées depuis PHP
        const quickTexts = <?php echo json_encode($quick_texts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE); ?>;
        
        // Éléments du DOM
        const categorieSelect = document.getElementById('categorie_quick');
        const messagePredefiniSelect = document.getElementById('message_predefini');
        const messageTextarea = document.getElementById('message');
        
        // Gestion du changement de catégorie
        categorieSelect.addEventListener('change', function() {
            const categorie = this.value;
            
            // Réinitialiser le select des messages prédéfinis
            messagePredefiniSelect.innerHTML = '<option value="">Sélectionner un message</option>';
            
            if (categorie && quickTexts[categorie]) {
                // Activer le select et remplir avec les messages de la catégorie
                messagePredefiniSelect.disabled = false;
                
                quickTexts[categorie].forEach(function(item) {
                    const option = document.createElement('option');
                    option.value = item.contenu;
                    option.textContent = item.titre;
                    messagePredefiniSelect.appendChild(option);
                });
            } else {
                // Désactiver le select si aucune catégorie sélectionnée
                messagePredefiniSelect.disabled = true;
            }
        });
        
        // Gestion de la sélection d'un message prédéfini
        messagePredefiniSelect.addEventListener('change', function() {
            const contenu = this.value;
            
            if (contenu) {
                // Ajouter le contenu au textarea
                if (messageTextarea.value.trim() !== '') {
                    messageTextarea.value += '\n' + contenu;
                } else {
                    messageTextarea.value = contenu;
                }
                
                // Réinitialiser la sélection pour permettre de réutiliser le même message
                this.value = '';
            }
        });
        
        // Auto-scroll vers le haut pour voir le dernier message ajouté
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
        });
    </script>
</body>
</html>

