<?php
// src/php/ajax/ajaxGestionPanier.php – Gestion du panier en session
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if ($action == 'add' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]++;
        } else {
            $_SESSION['cart'][$id] = 1;
        }
        echo json_encode(['status' => 'success']);
    } elseif ($action == 'update' && isset($_POST['id'], $_POST['qty'])) {
        $id = intval($_POST['id']);
        $qty = intval($_POST['qty']);
        if ($qty < 1) $qty = 1;
        $_SESSION['cart'][$id] = $qty;
        echo json_encode(['status' => 'success']);
    } elseif ($action == 'remove' && isset($_POST['id'])) {
        $id = intval($_POST['id']);
        unset($_SESSION['cart'][$id]);
        echo json_encode(['status' => 'success']);
    }
    exit;
}
http_response_code(400);
echo json_encode(['status' => 'error']);
?>
