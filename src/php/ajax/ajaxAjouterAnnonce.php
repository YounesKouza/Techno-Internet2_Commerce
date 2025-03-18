<?php
// src/php/ajax/ajaxAjouterAnnonce.php
include '../../db/dbConnect.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $categorie_id = intval($_POST['categorie_id']);
    $image = trim($_POST['image']);
    $stmt = $pdo->prepare("INSERT INTO products (titre, description, prix, stock, categorie_id, image_principale) VALUES (:titre, :description, :prix, :stock, :categorie_id, :image)");
    if ($stmt->execute([
        'titre' => $titre,
        'description' => $description,
        'prix' => $prix,
        'stock' => $stock,
        'categorie_id' => $categorie_id,
        'image' => $image
    ])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>
