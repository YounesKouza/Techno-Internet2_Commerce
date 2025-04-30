<?php
header('Content-Type: application/json');
require_once '../db/dbPgConnect.php';
require_once '../classes/Product.class.php';
require_once '../classes/CartDAO.class.php';
require_once '../classes/ProductDAO.class.php';

session_start();
$cnx = new PDO($dsn, $user, $password);
$cartDAO = new CartDAO($cnx);

// Récupérer le résultat de la suppression de l'article
$result = $cartDAO->removeFromCart(intval($_POST['product_id'] ?? 0));

// Ajouter le nombre total d'articles dans le panier
if (isset($_SESSION['panier'])) {
    $result['cart_count'] = count($_SESSION['panier']);
} else {
    $result['cart_count'] = 0;
}

echo json_encode($result);
