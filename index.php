<?php
require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';

// Titre de la landing page
$page_title = "Accueil - Outil de gestion opérationnelle CRF";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<?php require_once __DIR__ . '/includes/head.php'; ?>
    <style>
        body {
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-brand span {
            letter-spacing: 0.05em;
        }
        .hero-section {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 4rem 0;
            background: linear-gradient(135deg, #ffffff 0%, #ffe5e8 45%, #E2001A 100%);
        }
        .hero-badge {
            font-size: 0.8rem;
            letter-spacing: 0.08em;
        }
        .hero-title {
            font-size: 2.5rem;
            font-weight: 700;
            color: #212529;
        }
        .hero-title span {
            color: #E2001A;
        }
        .hero-subtitle {
            font-size: 1.05rem;
            color: #495057;
        }
        .pill {
            border-radius: 999px;
        }
        .hero-image-card {
            background: rgba(255, 255, 255, 0.92);
            border-radius: 1.5rem;
            box-shadow: 0 1rem 3rem rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .hero-image-top {
            background: #E2001A;
            padding: 1.25rem 1.5rem;
            color: #fff;
        }
        .hero-image-top small {
            opacity: 0.85;
            letter-spacing: 0.08em;
        }
        .hero-image-top strong {
            letter-spacing: 0.12em;
        }
        .hero-stats {
            font-size: 0.85rem;
        }
        .hero-stats .badge {
            font-weight: 500;
        }
        .logo-hero {
            max-width: 140px;
        }
        .hero-illustration {
            border-radius: 1.25rem;
            object-fit: cover;
            width: 100%;
            height: 210px;
        }
        .footer {
            border-top: 1px solid rgba(0,0,0,0.06);
            font-size: 0.85rem;
        }
        @media (max-width: 991.98px) {
            .hero-section {
                padding-top: 2.5rem;
            }
            .hero-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/img/logo.png" alt="Croix-Rouge Française" height="36" class="me-2">
                
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
                    <li class="nav-item me-2 mb-2 mb-lg-0">
                        <a class="btn btn-outline-danger btn-sm" href="history.php">
                            <i class="bi bi-clock-history me-1"></i> Historique
                        </a>
                    </li>
                    <li class="nav-item me-2 mb-2 mb-lg-0">
                        <a class="btn btn-danger btn-sm" href="creation.php">
                            <i class="bi bi-plus-circle me-1"></i> Nouvelle intervention
                        </a>
                    </li>
                    <li class="nav-item text-muted small ms-lg-2">
                        <i class="bi bi-person-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['user']['nom'] ?? 'Opérateur'); ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <span class="badge bg-light text-danger border border-danger pill hero-badge mb-3">
                        <i class="bi bi-activity me-1"></i> Outil de coordination opérationnelle
                    </span>
                    <h1 class="hero-title mb-3">
                        Bienvenue sur l'outil de<br>
                        <span>gestion opérationnelle CRF</span>
                    </h1>
                    <p class="hero-subtitle mb-4">
                        Centralisez le suivi de vos interventions, les moyens engagés et la main courante
                        dans un espace unique, pensé pour les équipes de la Croix-Rouge française.
                    </p>
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4">
                        <a href="creation.php" class="btn btn-danger btn-lg px-4">
                            <i class="bi bi-plus-circle me-2"></i> Nouvelle Intervention
                        </a>
                        <a href="history.php" class="btn btn-outline-danger btn-lg px-4">
                            <i class="bi bi-list-ul me-2"></i> Historique / Interventions en cours
                        </a>
                    </div>
                    <div class="d-flex flex-wrap align-items-center text-muted small">
                        <div class="me-3 d-flex align-items-center">
                            <i class="bi bi-shield-check text-danger me-1"></i>
                            <span>Traçabilité complète des décisions</span>
                        </div>
                        <div class="me-3 d-flex align-items-center">
                            <i class="bi bi-diagram-3 text-danger me-1"></i>
                            <span>Vue synthétique des moyens engagés</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image-card">
                        <div class="hero-image-top d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-uppercase d-block">Centre opérationnel</small>
                                <strong>Coordination Croix-Rouge 92</strong>
                            </div>
                            <img src="assets/img/crfensemble.png" alt="Logo CRF" class="logo-hero d-none d-md-block">
                        </div>
                        <div class="row g-0">
                            <div class="col-5 d-flex flex-column justify-content-between p-3">
                                <div class="mb-3 hero-stats">
                                    <div class="mb-2">
                                        <span class="badge bg-danger pill me-2">Temps réel</span>
                                        <span class="text-muted">Suivi des statuts</span>
                                    </div>
                                    <ul class="list-unstyled mb-0 text-muted">
                                        <li class="mb-1"><i class="bi bi-check-circle-fill text-success me-1"></i> Dénombrement terrain</li>
                                        <li class="mb-1"><i class="bi bi-chat-dots-fill text-danger me-1"></i> Main courante structurée</li>
                                        <li class="mb-1"><i class="bi bi-file-earmark-pdf-fill text-secondary me-1"></i> Export de synthèse</li>
                                    </ul>
                                </div>
                                <div class="border-top pt-2 hero-stats">
                                    <small class="text-muted d-block mb-1">Accès rapide</small>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-light text-dark pill">
                                            <i class="bi bi-lightning-charge-fill text-warning me-1"></i> ACEL / COT
                                        </span>
                                        <span class="badge bg-light text-dark pill">
                                            <i class="bi bi-flag-fill text-danger me-1"></i> Plan ARAMIS
                                        </span>
                                        <span class="badge bg-light text-dark pill">
                                            <i class="bi bi-building-fill-check text-primary me-1"></i> Moyens engagés
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer py-3 bg-white">
        <div class="container d-flex flex-wrap justify-content-between align-items-center">
            <span class="text-muted small">
                © <?php echo date('Y'); ?> Croix-Rouge française – Outil interne de gestion opérationnelle. - version 1.0.0 By Torciv
            </span>
            <span class="text-muted small">
                <i class="bi bi-shield-lock me-1"></i> Données réservées aux équipes habilitées.
            </span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

