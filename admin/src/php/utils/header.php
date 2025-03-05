<?php

// Par défaut, si aucun titre particulier n'est défini --> titre générique
$title = "Site 2025 PUBLIC - Missions";

/*
// Définition de la page à afficher et création de la variable de session
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil.php';
$_SESSION['page'] = 'content/'.$page;
*/

if(!isset($_SESSION['page'])) {
    $_SESSION['page'] = 'content/accueil.php';
}
if (isset($_GET['page'])) {
    $_SESSION['page'] = 'content/'.$_GET['page'];
}


// Gestion des balises SEO par page
switch ($page) {
    case "pdo_db.php":
        $title = "Exercices pdo | Site 2025";
        // $canonical = "si nécessaire ... ";
        break;
}

// Vérifier si la page existe dans l'arborescence
$path = $_SESSION['page'];
if (!file_exists($path)) {
    $title = "Page introuvable | Site 2025";
    $_SESSION['page'] = 'content/page404.php';
}

