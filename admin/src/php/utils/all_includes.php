<?php
/**
 * Fichier d'inclusion central pour toutes les classes et utilitaires
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclure la configuration de la base de données PostgreSQL
require_once __DIR__ . '/../db/dbPgConnect.php';

// Inclure les classes DAO
require_once __DIR__ . '/../classes/CartDAO.class.php';
require_once __DIR__ . '/../classes/CategoryDAO.class.php';
require_once __DIR__ . '/../classes/OrderDAO.class.php';
require_once __DIR__ . '/../classes/OrderLineDAO.class.php';
require_once __DIR__ . '/../classes/ProductDAO.class.php';
require_once __DIR__ . '/../classes/ProductImageDAO.class.php';
require_once __DIR__ . '/../classes/UserDAO.class.php';
require_once __DIR__ . '/../classes/PaymentDAO.class.php';

// Inclure les classes métier
require_once __DIR__ . '/../classes/Cart.class.php';
require_once __DIR__ . '/../classes/Category.class.php';
require_once __DIR__ . '/../classes/Order.class.php';
require_once __DIR__ . '/../classes/OrderLine.class.php';
require_once __DIR__ . '/../classes/Product.class.php';
require_once __DIR__ . '/../classes/ProductImage.class.php';
require_once __DIR__ . '/../classes/User.class.php';
require_once __DIR__ . '/../classes/Payment.class.php';

// Inclure les utilitaires
require_once __DIR__ . '/connexion.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/sidebar.php';

/**
 * Détermine si la page en cours fait partie de l'interface d'administration
 * @return bool
 */
function is_admin_interface() {
    // Vérifier si l'URL contient /admin/ 
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    return (strpos($request_uri, '/admin/') !== false);
}

/**
 * Ajoute automatiquement la classe 'admin-interface' au body lorsqu'on est dans l'interface admin
 * Cette fonction est utilisée dans le header pour appliquer les styles spécifiques à l'admin
 * Note: La fonctionnalité JavaScript a été déplacée vers fonction.js
 */
function add_body_class() {
    // Cette fonction est conservée pour compatibilité, mais le JavaScript
    // a été déplacé dans fonction.js et n'est plus injecté ici
    // Pour assurer la compatibilité avec le code existant
    return;
}
