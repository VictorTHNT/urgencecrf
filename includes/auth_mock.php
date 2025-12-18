<?php
/**
 * Mock d'authentification
 * Simule une connexion utilisateur pour le développement
 * L'authentification finale sera via OKTA
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si l'utilisateur n'est pas connecté, connecter automatiquement un utilisateur fictif
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    $_SESSION['user'] = [
        'nom' => 'Cadre Test',
        'role' => 'Admin',
        'email' => 'cadre.test@croix-rouge.fr'
    ];
}
?>

