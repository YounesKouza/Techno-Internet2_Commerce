<?php
/**
 * Fichier d'inclusion de la barre latérale d'administration commune
 * Ce fichier permet d'assurer une cohérence visuelle sur toutes les pages administratives
 * 
 * @param string $active_page La page active dans le menu
 */

function generate_sidebar($active_page = '') {
?>
<!-- Sidebar / Menu latéral -->
<div class="col-lg-2 admin-sidebar p-0 min-vh-100">
    <div class="sidebar-logo p-3 text-center">
        <a href="accueil_admin.php" class="text-decoration-none d-flex align-items-center justify-content-center">
            <img src="../public/images/logo/furniture-logo.svg" alt="Logo Furniture">
            <div>
                <h4 class="text-white mb-0">Furniture</h4>
                <div class="text-white-50 small">Administration</div>
            </div>
        </a>
    </div>
    <hr class="bg-white">
    
    <!-- Menu de navigation -->
    <ul class="nav flex-column">
        <li class="nav-item">
            <a href="accueil_admin.php" class="nav-link text-white <?= $active_page === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Tableau de bord
            </a>
        </li>
        <li class="nav-item">
            <a href="gestion_meubles.php" class="nav-link text-white <?= $active_page === 'products' ? 'active' : '' ?>">
                <i class="fas fa-couch me-2"></i> Gestion des meubles
            </a>
        </li>
        <li class="nav-item">
            <a href="gestion_categories.php" class="nav-link text-white <?= $active_page === 'categories' ? 'active' : '' ?>">
                <i class="fas fa-tags me-2"></i> Gestion des catégories
            </a>
        </li>
        <li class="nav-item">
            <a href="gestion_commandes.php" class="nav-link text-white <?= $active_page === 'orders' ? 'active' : '' ?>">
                <i class="fas fa-shopping-cart me-2"></i> Gestion des commandes
            </a>
        </li>
        <li class="nav-item">
            <a href="gestion_clients.php" class="nav-link text-white <?= $active_page === 'customers' ? 'active' : '' ?>">
                <i class="fas fa-users me-2"></i> Gestion des clients
            </a>
        </li>
        <li class="nav-item">
            <a href="statistiques.php" class="nav-link text-white <?= $active_page === 'stats' ? 'active' : '' ?>">
                <i class="fas fa-chart-bar me-2"></i> Statistiques
            </a>
        </li>
        <li class="nav-item">
            <a href="diagnostic.php" class="nav-link text-white <?= $active_page === 'diagnostic' ? 'active' : '' ?>">
                <i class="fas fa-stethoscope me-2"></i> Diagnostic système
            </a>
        </li>
    </ul>
    
    <hr class="bg-white">
    
    <!-- Actions utilisateur -->
    <ul class="nav flex-column mt-auto">
        <li class="nav-item">
            <a href="../../index_.php" class="nav-link text-white">
                <i class="fas fa-home me-2"></i> Retour au site
            </a>
        </li>
        <li class="nav-item">
            <a href="disconnect.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
            </a>
        </li>
    </ul>
</div>
<?php
}
?> 