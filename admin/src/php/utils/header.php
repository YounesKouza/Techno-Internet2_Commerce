<?php
/**
 * Fichier contenant la fonction pour générer l'en-tête du site
 */

/**
 * Génère l'en-tête HTML du site
 * @param string $page_active Nom de la page active
 * @param string $titre_page Titre de la page (optionnel)
 */
function generate_header($page_active, $titre_page = null) {
    // Déterminer le titre de la page
    if ($titre_page === null) {
        switch ($page_active) {
            case 'accueil':
                $titre_page = 'Accueil';
                break;
            case 'catalogue':
                $titre_page = 'Catalogue';
                break;
            case 'produit_details':
                $titre_page = 'Détails du produit';
                break;
            case 'panier':
                $titre_page = 'Votre panier';
                break;
            case 'commande':
                $titre_page = 'Finaliser votre commande';
                break;
            case 'compte':
                $titre_page = 'Mon compte';
                break;
            case 'login':
                $titre_page = 'Connexion';
                break;
            case 'inscription':
                $titre_page = 'Inscription';
                break;
            case 'page404':
                $titre_page = 'Page non trouvée';
                break;
            default:
                $titre_page = 'Boutique en ligne';
        }
    }
    
    // Début du HTML
    ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Boutique en ligne de meubles design pour votre intérieur">
    <title>Furniture - <?= $titre_page ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/Exos/Techno-internet2_commerce/admin/public/css/style.css">
    
    <?php add_body_class(); // Ajout automatique de la classe admin-interface si nécessaire ?>
</head>
<body>
    <!-- En-tête du site -->
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
            <div class="container">
                <a class="navbar-brand" href="index_.php">
                    <img src="/Exos/Techno-internet2_commerce/admin/public/images/logo.png" alt="Furniture Logo">
                    Furniture
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse justify-content-between" id="navbarMain">
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link <?= $page_active === 'catalogue' ? 'active' : '' ?>" href="index_.php?page=catalogue">
                                <i class="fas fa-store"></i> Catalogue
                            </a>
                        </li>
                    </ul>
                    
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="index_.php?page=panier">
                                <i class="fas fa-shopping-cart"></i> 
                                <span class="d-lg-none">Panier</span>
                                <?php if (isset($_SESSION['panier']) && count($_SESSION['panier']) > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?= count($_SESSION['panier']) ?>
                                </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        
                        <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> 
                                <span class="d-lg-none">Mon compte</span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="index_.php?page=compte">Profil</a></li>
                                <li><a class="dropdown-item" href="index_.php?page=deconnexion">Déconnexion</a></li>
                            </ul>
                        </li>
                        <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index_.php?page=login">
                                <i class="fas fa-sign-in-alt"></i>
                                <span class="d-lg-none">Connexion</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <script>
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
    
    <!-- Contenu principal -->
    <main<?= isset($main_class) ? ' class="'.$main_class.'"' : '' ?>>
    <?php
} 