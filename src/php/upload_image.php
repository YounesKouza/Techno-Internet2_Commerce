<?php
// Vérifie si l'utilisateur est connecté
session_start();
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Configuration
$uploadDir = '../../public/uploads/products/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Vérifier si un fichier a été envoyé
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'Erreur lors du téléchargement']);
    exit;
}

// Vérifier le type de fichier
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($_FILES['file']['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Type de fichier non autorisé']);
    exit;
}

// Vérifier la taille du fichier
if ($_FILES['file']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Fichier trop volumineux']);
    exit;
}

// Générer un nom de fichier unique
$extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
$filename = uniqid('product_') . '.' . $extension;
$filepath = $uploadDir . $filename;

// Déplacer le fichier
if (move_uploaded_file($_FILES['file']['tmp_name'], $filepath)) {
    echo json_encode(['success' => true, 'filename' => $filename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Erreur lors de l\'enregistrement du fichier']);
} 