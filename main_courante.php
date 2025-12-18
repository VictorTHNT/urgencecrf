<?php
require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

$intervention_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$intervention_id) {
    header("Location: index.php");
    exit;
}

// Vérification que l'intervention existe
$stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
$stmt->execute([$intervention_id]);
$intervention = $stmt->fetch();

if (!$intervention) {
    die("Intervention introuvable.");
}

// Traitement de l'ajout d'un message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_message') {
    $expediteur = trim($_POST['expediteur'] ?? '');
    $destinataire = trim($_POST['destinataire'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if ($expediteur && $destinataire && $message) {
        $stmt = $pdo->prepare("INSERT INTO main_courante (intervention_id, expediteur, destinataire, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$intervention_id, $expediteur, $destinataire, $message]);
        header("Location: main_courante.php?id=" . $intervention_id);
        exit;
    }
}

// Récupération des messages (ordre chronologique décroissant)
$stmt = $pdo->prepare("SELECT * FROM main_courante WHERE intervention_id = ? ORDER BY horodatage DESC");
$stmt->execute([$intervention_id]);
$messages = $stmt->fetchAll();

// Récupération des presets pour l'autocomplétion
$stmt = $pdo->prepare("SELECT texte FROM presets_messages WHERE categorie = ? ORDER BY texte");
$stmt->execute(['expediteur']);
$presets_expediteur = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt->execute(['destinataire']);
$presets_destinataire = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt->execute(['message']);
$presets_message = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main Courante - Intervention #<?php echo $intervention_id; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .message-card {
            margin-bottom: 15px;
            border-left: 4px solid #0d6efd;
        }
        .message-card.old {
            opacity: 0.8;
            border-left-color: #6c757d;
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
                                           list="list-destinataire" placeholder="Saisir ou sélectionner" required>
                                    <datalist id="list-destinataire">
                                        <?php foreach ($presets_destinataire as $preset): ?>
                                            <option value="<?php echo htmlspecialchars($preset); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                    <small class="text-muted">Vous pouvez saisir un texte libre ou choisir dans la liste</small>
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
                                                onclick="document.getElementById('message').value = '<?php echo htmlspecialchars($preset, ENT_QUOTES); ?>'">
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
                            <div class="alert alert-info text-center">
                                <i class="bi bi-info-circle"></i> Aucun message pour le moment.
                            </div>
                        <?php else: ?>
                            <?php 
                            $now = new DateTime();
                            foreach ($messages as $msg): 
                                $msgDate = new DateTime($msg['horodatage']);
                                $diff = $now->diff($msgDate);
                                $isOld = $diff->days > 0 || $diff->h > 1;
                            ?>
                                <div class="card message-card <?php echo $isOld ? 'old' : ''; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <strong class="text-primary">
                                                    <i class="bi bi-person"></i> <?php echo htmlspecialchars($msg['expediteur']); ?>
                                                </strong>
                                                <span class="text-muted"> → </span>
                                                <strong class="text-success">
                                                    <i class="bi bi-person-check"></i> <?php echo htmlspecialchars($msg['destinataire']); ?>
                                                </strong>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> 
                                                <?php echo date('d/m/Y H:i:s', strtotime($msg['horodatage'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-scroll vers le haut pour voir le dernier message ajouté
        window.addEventListener('load', function() {
            window.scrollTo(0, 0);
        });
    </script>
</body>
</html>

