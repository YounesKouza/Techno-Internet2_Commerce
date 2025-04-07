<?php
/**
 * Page d'accueil de l'administration (tableau de bord)
 */

// Démarrage de la session
session_start();

// Vérification si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirection vers la page de connexion
    header('Location: ../../index_.php?page=login&redirect=admin');
    exit;
}

// Inclusion des fichiers nécessaires
require_once '../src/php/utils/connexion.php';
require_once '../src/php/utils/sidebar.php';

// Récupération des statistiques
$pdo = getPDO();

// Nombre total de produits
$products_count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// Nombre total de commandes
$orders_count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();

// Nombre de clients
$users_count = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();

// Chiffre d'affaires total
$total_sales = $pdo->query("SELECT COALESCE(SUM(montant_total), 0) FROM orders WHERE statut != 'cancelled'")->fetchColumn();

// Commandes récentes
$recent_orders = $pdo->query("
    SELECT o.id, o.montant_total, o.statut, o.date_commande as created_at, u.nom as username, u.email 
    FROM orders o 
    LEFT JOIN users u ON o.utilisateur_id = u.id 
    ORDER BY o.date_commande DESC 
    LIMIT 5
")->fetchAll();

// Produits les plus vendus
$best_sellers = $pdo->query("
    SELECT p.id, p.titre as name, p.prix as price, p.image_principale as image_path, 
           COALESCE(SUM(ol.quantite), 0) as total_sold
    FROM products p
    LEFT JOIN order_lines ol ON p.id = ol.produit_id
    LEFT JOIN orders o ON ol.order_id = o.id AND o.statut != 'cancelled'
    GROUP BY p.id, p.titre, p.prix, p.image_principale
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll();

// Derniers clients inscrits
$recent_users = $pdo->query("
    SELECT id, nom as username, email, date_inscription as created_at 
    FROM users 
    WHERE role = 'client' 
    ORDER BY date_inscription DESC 
    LIMIT 5
")->fetchAll();

// Alertes de stock bas (moins de 5 unités)
$low_stock = $pdo->query("
    SELECT id, titre as name, stock 
    FROM products 
    WHERE stock < 5 
    ORDER BY stock ASC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Tableau de bord | Furniture</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/Exos/Techno-internet2_commerce/admin/public/css/style.css">
</head>
<body class="admin-interface">
    <div class="container-fluid">
        <div class="row">
            <?php generate_sidebar('dashboard'); ?>
            
            <!-- Contenu principal -->
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Tableau de bord</h1>
                    <div>
                        <!-- Informations sur l'utilisateur connecté -->
                        <span class="me-3">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                        <a href="disconnect.php" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                        </a>
                    </div>
                </div>
                
                <!-- Widgets statistiques -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-white">Produits</h6>
                                        <h2 class="mt-2 mb-0 text-white"><?= $products_count ?></h2>
                                    </div>
                                    <i class="fas fa-couch fa-2x value-icon"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-end">
                                <a href="gestion_meubles.php" class="text-white text-decoration-none small fw-bold">
                                    Voir tous <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-white">Commandes</h6>
                                        <h2 class="mt-2 mb-0 text-white"><?= $orders_count ?></h2>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-2x value-icon"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-end">
                                <a href="gestion_commandes.php" class="text-white text-decoration-none small fw-bold">
                                    Voir toutes <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-white">Clients</h6>
                                        <h2 class="mt-2 mb-0 text-white"><?= $users_count ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x value-icon"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-end">
                                <a href="gestion_clients.php" class="text-white text-decoration-none small fw-bold">
                                    Voir tous <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-dark">Chiffre d'affaires</h6>
                                        <h2 class="mt-2 mb-0 text-dark"><?= number_format($total_sales, 2, ',', ' ') ?> €</h2>
                                    </div>
                                    <i class="fas fa-euro-sign fa-2x value-icon"></i>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 text-end">
                                <a href="statistiques.php" class="text-dark text-decoration-none small fw-bold">
                                    Voir les stats <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Alertes de stock bas -->
                <?php if (count($low_stock) > 0): ?>
                <div class="alert alert-warning mb-4">
                    <h5><i class="fas fa-exclamation-triangle me-2"></i> Alertes de stock</h5>
                    <p class="mb-2">Ces produits ont un stock faible (moins de 5 unités) :</p>
                    <ul class="mb-0">
                        <?php foreach ($low_stock as $item): ?>
                            <li>
                                <a href="update_meuble.php?id=<?= $item['id'] ?>" class="text-decoration-none">
                                    <?= htmlspecialchars($item['name']) ?> 
                                    <strong>(<?= $item['stock'] ?> en stock)</strong>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Commandes récentes -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">Dernières commandes</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table admin-table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Client</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($recent_orders)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-3">Aucune commande à afficher</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td><?= $order['id'] ?></td>
                                                        <td>
                                                            <?= !empty($order['username']) ? htmlspecialchars($order['username']) : 'Client anonyme' ?>
                                                        </td>
                                                        <td><?= number_format($order['montant_total'], 2, ',', ' ') ?> €</td>
                                                        <td>
                                                            <?php 
                                                            $status_class = 'secondary';
                                                            switch($order['statut']) {
                                                                case 'completed': $status_class = 'success'; break;
                                                                case 'processing': $status_class = 'primary'; break;
                                                                case 'pending': $status_class = 'warning'; break;
                                                                case 'cancelled': $status_class = 'danger'; break;
                                                            }
                                                            ?>
                                                            <span class="badge bg-<?= $status_class ?>">
                                                                <?= ucfirst($order['statut']) ?>
                                                            </span>
                                                        </td>
                                                        <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent text-end">
                                <a href="gestion_commandes.php" class="text-decoration-none">Voir toutes les commandes</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Produits les plus vendus -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100 best-selling-products">
                            <div class="card-header">
                                <h5 class="mb-0">Produits les plus vendus</h5>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($best_sellers)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                                        <p class="text-muted">Aucun produit vendu pour le moment</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($best_sellers as $product): ?>
                                    <div class="best-selling-product-item">
                                        <img 
                                            src="<?php 
                                                $image_path = $product['image_path'] ?? '';
                                                if (empty($image_path)) {
                                                    echo '/Exos/Techno-internet2_commerce/public/images/logo.png';
                                                } else if (strpos($image_path, '/') === 0) {
                                                    // Chemin absolu, on l'utilise tel quel
                                                    echo htmlspecialchars($image_path);
                                                } else {
                                                    // Chemin relatif, on ajoute le préfixe
                                                    echo '/Exos/Techno-internet2_commerce/' . htmlspecialchars($image_path);
                                                }
                                            ?>" 
                                            alt="<?= htmlspecialchars($product['name']) ?>" 
                                            class="best-selling-product-image"
                                            onerror="this.src='/Exos/Techno-internet2_commerce/public/images/logo.png'"
                                        >
                                        <div class="best-selling-product-info">
                                            <h6><?= htmlspecialchars($product['name']) ?></h6>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="price"><?= number_format($product['price'], 2, ',', ' ') ?> €</span>
                                                <span class="sold">
                                                    <i class="fas fa-shopping-cart me-1"></i> 
                                                    <?= $product['total_sold'] ?> vendu(s)
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Derniers clients inscrits -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Derniers clients inscrits</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table admin-table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Nom d'utilisateur</th>
                                        <th>Email</th>
                                        <th>Date d'inscription</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_users)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3">Aucun client à afficher</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_users as $user): ?>
                                            <tr>
                                                <td><?= $user['id'] ?></td>
                                                <td><?= htmlspecialchars($user['username']) ?></td>
                                                <td><?= htmlspecialchars($user['email']) ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($user['created_at'])) ?></td>
                                                <td>
                                                    <a href="gestion_clients.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-transparent text-end">
                        <a href="gestion_clients.php" class="text-decoration-none">Voir tous les clients</a>
                    </div>
                </div>
                
                <!-- Pied de page -->
                <footer class="mt-5 text-center text-muted">
                    <p class="mb-1">Furniture - Administration &copy; <?= date('Y') ?></p>
                    <p class="small">Version 1.0.0</p>
                </footer>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</body>
</html> 