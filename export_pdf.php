<?php
require_once __DIR__ . '/includes/auth_mock.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/libs/fpdf/fpdf.php'; // Vérifie que ce chemin est bon chez toi

$intervention_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$intervention_id) {
    die("ID d'intervention manquant.");
}

// 1. Récupération des données
// ---------------------------------------------------------
$stmt = $pdo->prepare("SELECT * FROM interventions WHERE id = ?");
$stmt->execute([$intervention_id]);
$intervention = $stmt->fetch();

if (!$intervention) {
    die("Intervention introuvable.");
}

// Moyens
$stmt = $pdo->prepare("SELECT * FROM moyens WHERE intervention_id = ? AND status = 'engage' ORDER BY type, nom_indicatif");
$stmt->execute([$intervention_id]);
$moyens = $stmt->fetchAll();

// Main Courante
$stmt = $pdo->prepare("SELECT * FROM main_courante WHERE intervention_id = ? ORDER BY horodatage ASC");
$stmt->execute([$intervention_id]);
$logs = $stmt->fetchAll();

// Commentaires de fin de mission (si table existante)
$commentaires = null;
try {
    $stmt = $pdo->prepare("SELECT points_positifs, points_negatifs, problemes_internes_crf, problemes_externes_crf, zone_libre FROM intervention_commentaires WHERE intervention_id = ?");
    $stmt->execute([$intervention_id]);
    $commentaires = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table absente ou erreur : on continue sans section commentaires
}

// 2. Classe PDF Personnalisée (Style "ACEL")
// ---------------------------------------------------------
class PDF_ACEL extends FPDF
{
    // Couleurs CRF
    private $col_rouge = [214, 0, 28];    // #D6001C
    private $col_gris_fonce = [60, 65, 80]; // Gris bleuté du header
    private $col_texte = [0, 0, 0];

    // Helper pour les accents (UTF-8 vers Windows-1252)
    public function cv($str) {
        return iconv('UTF-8', 'windows-1252', $str);
    }

    function Header()
    {
        // On récupère les infos passées globalement (pas très propre mais efficace pour FPDF simple)
        global $intervention;

        // Affichage du message ARAMIS si applicable (AVANT tout le reste)
        if (!empty($intervention['plan_aramis']) && $intervention['plan_aramis'] == 1) {
            // Bandeau rouge vif pour ARAMIS
            $this->SetFillColor(255, 0, 0); // Rouge vif
            $this->Rect(10, 10, 190, 12, 'F');
            $this->SetTextColor(255, 255, 255); // Blanc
            $this->SetFont('Arial', 'B', 16);
            $this->SetXY(15, 12);
            $this->Cell(0, 8, $this->cv('CADRE DU PLAN ARAMIS'), 0, 1, 'C');
            $this->Ln(3); // Espace après le bandeau ARAMIS
            $y_start = 25; // Décaler le reste du header
        } else {
            $y_start = 10; // Position normale
        }

        // 1. Fond Gris Foncé (Titre principal)
        $this->SetFillColor($this->col_gris_fonce[0], $this->col_gris_fonce[1], $this->col_gris_fonce[2]);
        $this->Rect(10, $y_start, 190, 25, 'F');

        // Titre Blanc
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Arial', 'B', 24);
        $this->SetXY(15, $y_start + 2);
        $this->Cell(0, 15, $this->cv('Compte-Rendu ACEL'), 0, 1, 'L');

        // 2. Bandeau Rouge
        $y_bandeau = $y_start + 25;
        $this->SetFillColor($this->col_rouge[0], $this->col_rouge[1], $this->col_rouge[2]);
        $this->Rect(10, $y_bandeau, 190, 6, 'F');
        
        $this->SetFont('Arial', 'B', 8);
        $this->SetXY(15, $y_bandeau);
        $this->Cell(0, 6, $this->cv('DOCUMENT INTERNE / DÉLÉGATION TERRITORIALE'), 0, 1, 'L');

        // 3. Sous-header Gris (Rédacteurs)
        $y_redacteurs = $y_bandeau + 6;
        $this->SetFillColor($this->col_gris_fonce[0], $this->col_gris_fonce[1], $this->col_gris_fonce[2]);
        $this->Rect(10, $y_redacteurs, 190, 12, 'F');

        // Construction de la chaîne rédacteurs
        $redacteurs = $intervention['cadre_permanence'];
        if ($intervention['cadre_astreinte']) {
            $redacteurs .= ' / ' . $intervention['cadre_astreinte'];
        }

        $this->SetXY(15, $y_redacteurs + 1);
        $this->SetFont('Arial', '', 8);
        $this->Cell(0, 4, $this->cv("Direction Territoriale de l'Urgence et du Secourisme"), 0, 1, 'L');
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(0, 5, $this->cv('Rédacteurs : ' . ($redacteurs ?: 'Non renseigné')), 0, 1, 'L');

        // Marge après header (ajuster selon si ARAMIS ou non)
        $marge_apres_header = !empty($intervention['plan_aramis']) && $intervention['plan_aramis'] == 1 ? 15 : 15;
        $this->Ln($marge_apres_header);
    }

    function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(128);
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb} - Genere le ' . date('d/m/Y H:i'), 0, 0, 'C');
    }

    function TitreSection($titre)
    {
        $this->Ln(5);
        $this->SetFont('Arial', 'B', 11);
        $this->SetTextColor(0); // Noir
        $this->Cell(0, 8, $this->cv(strtoupper($titre)), 0, 1, 'L');
        $this->Line($this->GetX(), $this->GetY(), $this->GetX() + 190, $this->GetY()); // Ligne soulignée
        $this->Ln(2);
    }

    function Paragraphe($texte)
    {
        $this->SetFont('Arial', '', 10);
        $this->MultiCell(0, 5, $this->cv($texte));
        $this->Ln(2);
    }

    function LigneClé($label, $valeur)
    {
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(50, 6, $this->cv($label . ' : '), 0, 0);
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 6, $this->cv($valeur), 0, 1);
    }
}

// 3. Génération du PDF
// ---------------------------------------------------------
$pdf = new PDF_ACEL();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 20);

// --- SECTION 1 : DESCRIPTION ---
$pdf->TitreSection("Description de l'événement");

// Date formatée
$date_event = date('l d F Y', strtotime($intervention['date_creation']));
// Traduction jours/mois rapide (ou utiliser setlocale si configuré)
$en = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday','January','February','March','April','May','June','July','August','September','October','November','December'];
$fr = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche','Janvier','Fevrier','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Decembre'];
$date_event = str_replace($en, $fr, $date_event);

$pdf->LigneClé("Date de l'événement", $date_event);
$pdf->Ln(2);

// Description narrative (Le gros bloc de texte)
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(5, $pdf->cv("Situation : "));
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, $pdf->cv($intervention['description']));
$pdf->Ln(4);

// Chronologie Clé (Narratif)
$heure_debut = date('H:i', strtotime($intervention['date_creation']));
$demandeur = $intervention['demandeur'];
$pdf->Paragraphe("Origine de l'alerte à la CRf : " . $demandeur . " le " . date('d/m/Y', strtotime($intervention['date_creation'])) . " à " . $heure_debut);

// On essaie de trouver l'heure de fin dans la main courante (dernier message) ou on laisse vide
$heure_fin = "En cours";
if (!empty($logs)) {
    $last_log = end($logs);
    // Si le dernier message contient "dispositif" ou "fin", on pourrait supposer que c'est la fin, 
    // mais ici on prend juste le dernier log pour l'exemple "Dernier point de situation".
    $heure_fin = date('d/m/Y H:i', strtotime($last_log['horodatage']));
}
$pdf->Paragraphe("Dernier point de situation noté à : " . $heure_fin);
$pdf->Ln(2);

$pdf->SetFont('Arial', 'U', 10); // Souligné
$pdf->Cell(0, 6, $pdf->cv("Type d'opération :"), 0, 1);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, $pdf->cv("  - " . $intervention['type_event']), 0, 1);
if ($intervention['is_acel']) $pdf->Cell(0, 6, $pdf->cv("  - Dispositif ACEL activé"), 0, 1);
if ($intervention['is_cot']) $pdf->Cell(0, 6, $pdf->cv("  - Activation COT"), 0, 1);


// --- SECTION 2 : DÉNOMBREMENT TERRAIN (Cadre Tableau) ---
$pdf->TitreSection("Dénombrement terrain");

// On dessine un cadre simple pour faire comme l'image
$pdf->SetDrawColor(0);
$pdf->SetFont('Arial', '', 10);

// Ligne UR
$pdf->Cell(100, 8, $pdf->cv("UR (Urgence Relative) : " . ($intervention['nb_ur'] ?? 0)), 1, 1, 'L');
// Ligne UA
$pdf->Cell(100, 8, $pdf->cv("UA (Urgence Absolue) : " . ($intervention['nb_ua'] ?? 0)), 1, 1, 'L');
// Ligne DCD
$pdf->Cell(100, 8, $pdf->cv("DCD (Décédé) : " . ($intervention['nb_dcd'] ?? 0)), 1, 1, 'L');
// Ligne Impliqués
$pdf->Cell(100, 8, $pdf->cv("Impliqués : " . ($intervention['nb_impliques'] ?? 0)), 1, 1, 'L');


// --- SECTION 3 : LOCALISATION ---
$pdf->TitreSection("Localisation de l'événement");
$pdf->SetFont('Arial', '', 10);

// Tableau simple
$pdf->Cell(40, 7, $pdf->cv("COMMUNE"), 1, 0, 'L', false);
$pdf->Cell(0, 7, $pdf->cv($intervention['commune']), 1, 1, 'L', false);

$pdf->Cell(40, 7, $pdf->cv("ADRESSE PMA"), 1, 0, 'L', false);
$pdf->Cell(0, 7, $pdf->cv($intervention['adresse_pma']), 1, 1, 'L', false);

if ($intervention['adresse_cai']) {
    $pdf->Cell(40, 7, $pdf->cv("ADRESSE CAI"), 1, 0, 'L', false);
    $pdf->Cell(0, 7, $pdf->cv($intervention['adresse_cai']), 1, 1, 'L', false);
}


// --- SECTION 4 : MOYENS ENGAGÉS ---
$pdf->TitreSection("Moyens CRf Engagés");

if (empty($moyens)) {
    $pdf->Paragraphe("Aucun moyen spécifique engagé.");
} else {
    // Construction d'une phrase avec toutes les qualifications
    $liste_txt = [];
    foreach ($moyens as $m) {
        $equipage = [];
        $nb_pse = (int)($m['nb_pse'] ?? 0);
        $nb_ch = (int)($m['nb_ch'] ?? 0);
        $nb_ci = (int)($m['nb_ci'] ?? 0);
        $nb_cadre_local = (int)($m['nb_cadre_local'] ?? 0);
        $nb_cadre_dept = (int)($m['nb_cadre_dept'] ?? 0);
        $nb_logisticien = (int)($m['nb_logisticien'] ?? 0);
        
        if ($nb_pse > 0) $equipage[] = $nb_pse . " PSE";
        if ($nb_ch > 0) $equipage[] = $nb_ch . " CH";
        if ($nb_ci > 0) $equipage[] = $nb_ci . " CI";
        if ($nb_cadre_local > 0) $equipage[] = $nb_cadre_local . " C.Loc";
        if ($nb_cadre_dept > 0) $equipage[] = $nb_cadre_dept . " C.Dep";
        if ($nb_logisticien > 0) $equipage[] = $nb_logisticien . " Log";
        
        $equipage_str = !empty($equipage) ? " (" . implode(", ", $equipage) . ")" : "";
        $liste_txt[] = $m['type'] . " " . $m['nom_indicatif'] . $equipage_str;
    }
    $pdf->Paragraphe("Moyens matériels et humains déployés : " . implode(', ', $liste_txt) . ".");
}

// Cadres spécifiques
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Write(5, $pdf->cv("Chaîne de commandement : "));
$pdf->SetFont('Arial', '', 10);
$txt_cadres = "";
if($intervention['dtus_permanence']) $txt_cadres .= "DTUS: " . $intervention['dtus_permanence'] . ". ";
if($intervention['logisticien_astreinte']) $txt_cadres .= "Log: " . $intervention['logisticien_astreinte'] . ". ";
$pdf->Write(5, $pdf->cv($txt_cadres));
$pdf->Ln(5);


// --- SECTION 5 : MAIN COURANTE (Chronologie) ---
$pdf->AddPage(); // Nouvelle page pour la main courante souvent longue
$pdf->TitreSection("Chronologie des communications (Main Courante)");

if (empty($logs)) {
    $pdf->Paragraphe("Aucune entrée dans la main courante.");
} else {
    // En-tête du tableau
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(25, 7, 'Heure', 1, 0, 'C', true);
    $pdf->Cell(40, 7, 'Emetteur > Recepteur', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Moyen Com', 1, 0, 'C', true);
    $pdf->Cell(30, 7, 'Opérateur', 1, 0, 'C', true);
    $pdf->Cell(0, 7, 'Message', 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 8);

    foreach ($logs as $log) {
        // Calcul hauteur ligne (MultiCell simulation)
        $nb_lignes = $pdf->GetStringWidth($pdf->cv($log['message'])) / 75; // 75 = largeur colonne message approx
        $nb_lignes = ceil($nb_lignes); 
        $hauteur = max(7, $nb_lignes * 4) + 2;

        // Si fin de page, on saute
        if ($pdf->GetY() + $hauteur > 270) {
            $pdf->AddPage();
            // Ré-affichage entête
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(25, 7, 'Heure', 1, 0, 'C', true);
            $pdf->Cell(40, 7, 'Emetteur > Recepteur', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Moyen Com', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Opérateur', 1, 0, 'C', true);
            $pdf->Cell(0, 7, 'Message', 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 8);
        }

        $heure = date('H:i', strtotime($log['horodatage']));
        $comm = $log['expediteur'] . " > " . $log['destinataire'];
        $moyen_com = $log['moyen_com'] ?? '-';
        $operateur = $log['operateur'] ?? '-';

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // Colonne Heure
        $pdf->Cell(25, $hauteur, $heure, 1, 0, 'C');
        
        // Colonne Comm (MultiCell pour éviter débordement si noms longs)
        $x_comm = $pdf->GetX();
        $pdf->Cell(40, $hauteur, '', 1, 0); // Cadre vide
        $pdf->SetXY($x_comm, $y);
        $pdf->MultiCell(40, 4, $pdf->cv($comm), 0, 'C'); // Texte dedans
        
        // Colonne Moyen Com
        $pdf->SetXY($x + 65, $y);
        $pdf->Cell(30, $hauteur, $pdf->cv($moyen_com), 1, 0, 'C');
        
        // Colonne Opérateur
        $pdf->SetXY($x + 95, $y);
        $pdf->Cell(30, $hauteur, $pdf->cv($operateur), 1, 0, 'C');
        
        // Colonne Message
        $pdf->SetXY($x + 125, $y);
        $pdf->MultiCell(0, $hauteur, '', 1, 'L'); // Cadre vide hauteur forcée
        $pdf->SetXY($x + 125, $y + 1); // Petit padding haut
        $pdf->MultiCell(0, 4, $pdf->cv($log['message']), 0, 'L');

        // Remettre le curseur au bon endroit pour la suite
        $pdf->SetXY($x, $y + $hauteur);
    }
}

// --- SECTION 6 : COMMENTAIRES ET RETOURS MISSION ---
if ($commentaires && (trim($commentaires['points_positifs'] ?? '') !== '' || trim($commentaires['points_negatifs'] ?? '') !== '' || trim($commentaires['problemes_internes_crf'] ?? '') !== '' || trim($commentaires['problemes_externes_crf'] ?? '') !== '' || trim($commentaires['zone_libre'] ?? '') !== '')) {
    $pdf->AddPage();
    $pdf->TitreSection("Commentaires et Retours Mission");
    $pdf->SetFont('Arial', '', 10);

    $libelles = [
        'points_positifs' => 'Points positifs',
        'points_negatifs' => 'Points négatifs',
        'problemes_internes_crf' => 'Problèmes internes CRF',
        'problemes_externes_crf' => 'Problèmes externes CRF',
        'zone_libre' => 'Zone libre'
    ];
    foreach ($libelles as $col => $label) {
        $texte = trim($commentaires[$col] ?? '');
        if ($texte !== '') {
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(0, 6, $pdf->cv($label . ' :'), 0, 1, 'L');
            $pdf->SetFont('Arial', '', 10);
            $pdf->MultiCell(0, 5, $pdf->cv($texte));
            $pdf->Ln(2);
        }
    }
}

$pdf->Output('I', 'Compte-Rendu-ACEL-' . $intervention['id'] . '.pdf');
?>