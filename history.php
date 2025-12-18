<?php
require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

// Récupération de toutes les interventions (ordre décroissant par date)
$stmt = $pdo->prepare("SELECT * FROM interventions ORDER BY date_creation DESC");
$stmt->execute();
$interventions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique des Interventions - Gestion des Opérations CRF</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }
        /* Couleur violette pour le statut Test */
        .bg-purple {
            background-color: #6f42c1 !important;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-light bg-white border-bottom shadow-sm">
        <div class="container-fluid">
            <span class="navbar-brand mb-0">
                <i class="bi bi-heart-pulse-fill text-danger"></i>
                <span class="text-danger fw-bold">Historique des Interventions</span>
            </span>
            <div>
                <a href="index.php" class="btn btn-danger btn-sm">
                    <i class="bi bi-plus-circle"></i> Nouvelle Intervention
                </a>
                <span class="text-muted ms-3"><?php echo htmlspecialchars($_SESSION['user']['nom']); ?></span>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0 text-secondary">
                    <i class="bi bi-clock-history"></i> Liste des Interventions
                    <span class="badge bg-secondary"><?php echo count($interventions); ?></span>
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (empty($interventions)): ?>
                    <div class="alert alert-info m-3">
                        <i class="bi bi-info-circle"></i> Aucune intervention enregistrée.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Date</th>
                                    <th>Commune</th>
                                    <th>Type d'Événement</th>
                                    <th>Demandeur</th>
                                    <th>Description</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($interventions as $inter): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-danger">#<?php echo $inter['id']; ?></strong>
                                            <?php if ($inter['numero_intervention']): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($inter['numero_intervention']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($inter['date_creation'])); ?><br>
                                            <small class="text-muted"><?php echo date('H:i', strtotime($inter['date_creation'])); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($inter['commune']); ?></td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                <?php echo htmlspecialchars($inter['type_event']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($inter['demandeur']); ?></td>
                                        <td>
                                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" 
                                                 title="<?php echo htmlspecialchars($inter['description']); ?>">
                                                <?php echo htmlspecialchars(substr($inter['description'], 0, 100)); ?>
                                                <?php if (strlen($inter['description']) > 100) echo '...'; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php 
                                            $statut = $inter['statut'] ?? 'En cours';
                                            $badge_class = 'bg-success';
                                            if ($statut === 'Cloturé') $badge_class = 'bg-danger';
                                            elseif ($statut === 'Test') $badge_class = 'bg-purple';
                                            ?>
                                            <span class="badge <?php echo $badge_class; ?>">
                                                <?php echo htmlspecialchars($statut); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <a href="dashboard.php?id=<?php echo $inter['id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm">
                                                    <i class="bi bi-eye"></i> Voir
                                                </a>
                                                <a href="export_pdf.php?id=<?php echo $inter['id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm" 
                                                   target="_blank"
                                                   title="Exporter en PDF">
                                                    <i class="bi bi-file-pdf text-danger"></i> PDF
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

