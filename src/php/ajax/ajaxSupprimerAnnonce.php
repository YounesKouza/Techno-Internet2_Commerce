<?php
// src/php/ajax/ajaxSupprimerAnnonce.php
include '../../db/dbConnect.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
    if ($stmt->execute(['id' => $id])) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error']);
    }
}
?>
