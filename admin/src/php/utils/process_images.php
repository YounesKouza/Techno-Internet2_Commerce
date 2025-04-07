<?php
/**
 * Script pour traiter les images et mettre à jour la base de données
 */

// Inclusion de l'utilitaire d'images
require_once __DIR__ . '/image_util.php';

// Définir l'encodage pour les caractères spéciaux
header('Content-Type: text/html; charset=utf-8');

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== Traitement des images et mise à jour de la base de données ===\n";
echo "Date d'exécution: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Vérifier la connexion à la base de données
    $pdo = getPDO();
    echo "Connexion à la base de données réussie.\n";
    
    // Exécuter le traitement des images
    processImages();
    
    echo "\nTraitement terminé avec succès!\n";
} catch (Exception $e) {
    echo "ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
} 