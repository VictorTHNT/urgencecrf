# Gestion des Opérations - Croix-Rouge Française

Application web de gestion de crise pour la Croix-Rouge Française.

## Installation

### Prérequis
- XAMPP installé sur Windows
- PHP 8
- MySQL/MariaDB
- FPDF (à télécharger et placer dans `libs/fpdf/`)

### Étapes d'installation

1. **Placer les fichiers dans XAMPP**
   - Le projet doit être dans : `C:\xampp\htdocs\urgencecrf\`

2. **Créer la base de données**
   - Ouvrir phpMyAdmin : http://localhost/phpmyadmin
   - Exécuter le script `database.sql` pour créer la base de données et les tables

3. **Installer FPDF**
   - Télécharger FPDF depuis : https://www.fpdf.org/
   - Extraire et placer le dossier dans : `libs/fpdf/`
   - Le fichier `fpdf.php` doit être accessible via : `libs/fpdf/fpdf.php`

4. **Accéder à l'application**
   - URL : http://localhost/urgencecrf/

## Structure des fichiers

```
urgencecrf/
├── includes/
│   ├── db.php              # Connexion PDO à la base de données
│   └── auth_mock.php       # Mock d'authentification
├── libs/
│   └── fpdf/               # Bibliothèque FPDF (à installer)
│       └── fpdf.php
├── index.php               # Page de création d'intervention
├── dashboard.php           # Dashboard opérationnel avec carte Leaflet
├── main_courante.php       # Gestion de la main courante
├── export_pdf.php          # Export PDF de l'intervention
├── database.sql            # Script SQL de création de la base
└── README.md               # Ce fichier
```

## Fonctionnalités

- **Création d'intervention** : Formulaire complet pour créer une nouvelle intervention
- **Dashboard opérationnel** : 
  - Carte interactive avec géolocalisation (Leaflet + Nominatim)
  - Gestion des moyens (VPSP, VL, Bénévoles)
  - Bilan humain (Impliqués/Évacués)
  - Récapitulatif des moyens engagés
- **Main courante** : 
  - Ajout de messages avec autocomplétion (datalist HTML)
  - Historique chronologique des messages
- **Export PDF** : Génération de rapport PDF avec FPDF

## Authentification

L'authentification est actuellement simulée (mock). Un utilisateur fictif est automatiquement connecté :
- Nom : Cadre Test
- Rôle : Admin

L'authentification finale sera intégrée via OKTA.

## Configuration de la base de données

Par défaut, la connexion utilise :
- Host : localhost
- User : root
- Password : (vide)
- Database : urgence_crf_db

Modifier `includes/db.php` si nécessaire.

