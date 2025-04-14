<?php
/**
 * Page de déconnexion
 * Détruit la session et redirige vers la page d'accueil
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Supprimer toutes les variables de session
$_SESSION = array();

// Si un cookie de session existe, le supprimer
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Détruire la session
session_destroy();

// Rediriger vers la page d'accueil
header('Location: index_.php');
exit; 