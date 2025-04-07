<?php
header('Content-Type: application/json');
require_once '../db/dbPgConnect.php';
require_once '../classes/Product.class.php';
require_once '../classes/CartDAO.class.php';
require_once '../classes/ProductDAO.class.php';
require_once '../classes/Cart.class.php';

session_start();
$cnx = new PDO($dsn, $user, $password);
$cartDAO = new CartDAO($cnx);
$cart = new Cart($cartDAO);

echo json_encode($cart->addProduct(intval($_POST['product_id'] ?? 0), intval($_POST['quantity'] ?? 0)));
