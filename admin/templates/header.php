<?php
// Inclusion du fichier de gestion des sessions
require_once __DIR__ . '/../src/php/utils/session.php';

// Vérifier que l'utilisateur est bien un administrateur
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != true) {
    // Redirection vers la page de connexion
    header('Location: /Exos/Techno-internet2_commerce/admin/login.php');
    exit;
}

// Récupérer le titre de la page (si défini)
$pageTitle = isset($pageTitle) ? $pageTitle : 'Administration';

// Récupérer le message flash (si présent)
$flashMessage = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Panel d'Administration</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/Exos/Techno-internet2_commerce/admin/public/css/style.css">
    
    <?php 
    // Ajouter la référence à la fonction add_body_class
    if (!function_exists('add_body_class')) {
        require_once __DIR__ . '/../src/php/utils/all_includes.php';
    }
    add_body_class(); // Ajout automatique de la classe admin-interface si nécessaire 
    ?>
</head>
<body>
    <!-- Overlay pour la sidebar mobile -->
    <div id="sidebarOverlay" class="position-fixed d-none d-lg-none top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 99; display: none;"></div>

    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/Exos/Techno-internet2_commerce/admin/pages/accueil_admin.php">
            Administration
        </a>
        
        <!-- Bouton pour afficher/masquer la sidebar sur mobile -->
        <button id="toggleSidebar" class="navbar-toggler position-absolute d-lg-none collapsed" type="button" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="navbar-nav ms-auto px-3">
            <div class="nav-item text-nowrap d-flex align-items-center">
                <span class="text-light d-none d-md-inline me-2">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?>
                </span>
                <a class="nav-link px-3" href="/Exos/Techno-internet2_commerce/admin/pages/disconnect.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    <span class="d-none d-sm-inline">Déconnexion</span>
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="adminSidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3 sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'accueil_admin.php' ? 'active' : ''; ?>" href="/Exos/Techno-internet2_commerce/admin/pages/accueil_admin.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                <span>Tableau de bord</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_meubles.php' ? 'active' : ''; ?>" href="/Exos/Techno-internet2_commerce/admin/pages/gestion_meubles.php">
                                <i class="fas fa-couch me-2"></i>
                                <span>Gestion des meubles</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'ajout_meuble.php' ? 'active' : ''; ?>" href="/Exos/Techno-internet2_commerce/admin/pages/ajout_meuble.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                <span>Ajouter un meuble</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_categories.php' ? 'active' : ''; ?>" href="/Exos/Techno-internet2_commerce/admin/pages/gestion_categories.php">
                                <i class="fas fa-tags me-2"></i>
                                <span>Catégories</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_commandes.php' ? 'active' : ''; ?>" href="/Exos/Techno-internet2_commerce/admin/pages/gestion_commandes.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                <span>Commandes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'gestion_clients.php' ? 'active' : ''; ?>" href="/Exos/Techno-internet2_commerce/admin/pages/gestion_clients.php">
                                <i class="fas fa-users me-2"></i>
                                <span>Clients</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'images.php' ? 'active' : ''; ?>" href="/Exos/Techno-internet2_commerce/admin/pages/images.php">
                                <i class="fas fa-images me-2"></i>
                                <span>Images de produits</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'statistiques.php' ? 'active' : ''; ?>" href="/Exos/Techno-internet2_commerce/admin/pages/statistiques.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span>Statistiques</span>
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Site</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/index_.php" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                <span>Voir le site</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenu principal -->
            <main id="contentWrapper" class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <?php if ($flashMessage): ?>
                <div class="alert alert-<?php echo $flashMessage['type']; ?> alert-dismissible fade show mt-3" role="alert">
                    <?php echo $flashMessage['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?> 