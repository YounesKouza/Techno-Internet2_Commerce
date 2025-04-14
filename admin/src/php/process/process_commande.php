<?php
/**
 * Processus de finalisation de la commande
 */

session_start();

require_once '../db/dbPgConnect.php';
require_once '../classes/Product.class.php';
require_once '../classes/OrderDAO.class.php';
require_once '../classes/Order.class.php';
require_once '../classes/ProductDAO.class.php';
require_once '../classes/OrderLineDAO.class.php';

// Fonction basique pour gérer les messages flash (à adapter si un système existe déjà)
function setFlashMessage($type, $message) {
    $_SESSION['flash_messages'][] = ['type' => $type, 'message' => $message];
}

// 1. Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    setFlashMessage('danger', 'Vous devez être connecté pour passer une commande.');
    header('Location: ../../../index_.php?page=login');
    exit;
}

// 2. Vérifier si le panier n'est pas vide
if (empty($_SESSION['panier'])) {
    setFlashMessage('warning', 'Votre panier est vide.');
    header('Location: ../../../index_.php?page=catalogue');
    exit;
}

// 3. Récupérer les informations nécessaires
$user_id = $_SESSION['user_id'];
$adresse_livraison = $_POST['adresse_livraison'] ?? null;
$adresse_facturation = $_POST['adresse_facturation'] ?? $adresse_livraison;
$methode_paiement = $_POST['methode_paiement'] ?? 'Inconnue';

// Validation basique des adresses
if (empty($adresse_livraison)) {
    setFlashMessage('danger', 'L\'adresse de livraison est obligatoire.');
    header('Location: ../../../index_.php?page=commande');
    exit;
}

// 4. Initialiser les objets nécessaires
$cnx = new PDO($dsn, $user, $password);
$orderDAO = new OrderDAO($cnx);
$productDAO = new ProductDAO($cnx);
$orderLineDAO = new OrderLineDAO($cnx);

// 5. Créer et traiter la commande
$order = new Order([], $orderDAO, $orderLineDAO, $productDAO);
$result = $order->processFromCart(
    $user_id,
    $adresse_livraison,
    $adresse_facturation,
    $methode_paiement
);

// 6. Gérer le résultat
if ($result['success']) {
    // Stocker l'ID de commande en session pour l'afficher sur la page de succès
    $_SESSION['last_order_id'] = $result['order_id'];
    setFlashMessage('success', 'Votre commande a été passée avec succès !');
    header('Location: ../../../index_.php?page=commande_succes');
} else {
    setFlashMessage('danger', 'Erreur lors de la commande : ' . $result['message']);
    header('Location: ../../../index_.php?page=panier');
}
exit;
