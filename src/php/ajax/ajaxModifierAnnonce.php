<?php
// src/php/ajax/ajaxModifierAnnonce.php
include '../../db/dbConnect.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['action'])) {
    $id = intval($_POST['id']);
    // Exemple : basculer l'état "actif" du produit
    $stmt = $pdo->prepare("UPDATE products SET actif = NOT actif WHERE id = :id");
    if ($stmt->execute(['id' => $id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>
