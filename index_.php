<?php
/**
 * Point d'entrée principal de la partie publique du site
 */

// Inclusion centrale (Chemin relatif depuis la racine du projet)
require_once __DIR__ . '/admin/src/php/utils/all_includes.php';

// Initialiser la connexion PDO pour qu'elle soit disponible globalement
$pdo = getPDO();

// Détermination de la page à afficher
$page = $_GET['page'] ?? 'accueil'; // Page par défaut: accueil

// Liste blanche des pages autorisées
$allowed_pages = [
    'accueil', 'catalogue', 'produit_details', 
    'panier', 'commande', 'commande_succes',
    'login', 'inscription', 'deconnexion',
    'compte' // Ajouter d'autres pages publiques ici
];

// Chemin de base pour les pages
$base_path = __DIR__ . '/pages/'; // Assumant que les pages publiques sont dans /pages/

// Vérification si la page demandée est autorisée et si le fichier existe
if (in_array($page, $allowed_pages) && file_exists($base_path . $page . '.php')) {
    $page_file = $base_path . $page . '.php';
} else {
    // Page non trouvée ou non autorisée
    $page = '404';
    // Chemin vers la page 404 (à ajuster si elle est ailleurs)
    $page_404_path = __DIR__ . '/content/page_404.php'; 
    if (!file_exists($page_404_path)) {
        // Fallback très simple si page_404.php n'existe pas
        die('Erreur 404 : Page non trouvée.'); 
    }
    $page_file = $page_404_path;
    http_response_code(404);
}

// Inclusion de l'en-tête commun
require_once __DIR__ . '/admin/src/php/utils/header.php';
// Appel de la fonction generate_header avec la page active
generate_header($page, $titre_page ?? null);

// Inclusion du contenu de la page spécifique
include $page_file;

// Inclusion du pied de page commun
require_once __DIR__ . '/admin/src/php/utils/footer.php'; 
// Appel de la fonction generate_footer
generate_footer();

?> 