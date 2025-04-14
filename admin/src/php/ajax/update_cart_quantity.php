<?php
header('Content-Type: application/json');
require_once '../db/dbPgConnect.php';
require_once '../classes/Product.class.php';
require_once '../classes/CartDAO.class.php';
require_once '../classes/ProductDAO.class.php';

session_start();
$cnx = new PDO($dsn, $user, $password);
$cartDAO = new CartDAO($cnx);

// Récupérer et valider les paramètres
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

// Mettre à jour la quantité
echo json_encode($cartDAO->updateCartItem($productId, $quantity));
