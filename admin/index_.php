<?php
/**
 * Tableau de bord d'administration
 */

// Titre de la page
$pageTitle = 'Tableau de bord';

// Inclusion des fichiers nécessaires
require_once 'src/php/utils/connexion.php';

// Inclusion de l'en-tête
require_once 'templates/header.php';

// Récupération des statistiques
try {
    $pdo = getPDO();
    
    // Nombre total de produits
    $stmt = $pdo->query('SELECT COUNT(*) FROM products');
    $totalProducts = $stmt->fetchColumn();
    
    // Nombre total de commandes
    $stmt = $pdo->query('SELECT COUNT(*) FROM orders');
    $totalOrders = $stmt->fetchColumn();
    
    // Nombre total d'utilisateurs
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    $totalUsers = $stmt->fetchColumn();
    
    // Nombre total de catégories
    $stmt = $pdo->query('SELECT COUNT(*) FROM categories');
    $totalCategories = $stmt->fetchColumn();
    
    // Chiffre d'affaires total
    $stmt = $pdo->query('SELECT SUM(montant_total) FROM orders');
    $totalRevenue = $stmt->fetchColumn() ?: 0;
    
    // Commandes récentes
    $stmt = $pdo->query('
        SELECT o.id, o.date_commande, o.montant_total, u.nom as nom_client, u.email
        FROM orders o
        JOIN users u ON o.utilisateur_id = u.id
        ORDER BY o.date_commande DESC
        LIMIT 5
    ');
    $recentOrders = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Erreur lors de la récupération des statistiques: ' . $e->getMessage();
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Tableau de bord</h1>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger" role="alert">
        <?php echo $error; ?>
    </div>
<?php else: ?>
    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <div class="col-md-3 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Produits</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalProducts; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Chiffre d'affaires</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalRevenue, 2, ',', ' '); ?> €</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-euro-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Commandes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalOrders; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Utilisateurs</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalUsers; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Commandes récentes -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-shopping-cart me-1"></i>
            Commandes récentes
        </div>
        <div class="card-body">
            <?php if (empty($recentOrders)): ?>
                <p class="text-center">Aucune commande récente à afficher.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Montant</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td><?php echo $order['id']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></td>
                                    <td><?php echo htmlspecialchars($order['nom_client']); ?> (<?php echo htmlspecialchars($order['email']); ?>)</td>
                                    <td><?php echo number_format($order['montant_total'], 2, ',', ' '); ?> €</td>
                                    <td>
                                        <a href="pages/orders.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Voir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Accès rapides -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tools me-1"></i>
                    Accès rapides
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="pages/products.php?action=add" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-1"></i> Ajouter un produit
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="pages/categories.php?action=add" class="btn btn-secondary w-100">
                                <i class="fas fa-plus me-1"></i> Ajouter une catégorie
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="pages/images.php" class="btn btn-info w-100 text-white">
                                <i class="fas fa-images me-1"></i> Gérer les images
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="pages/users.php" class="btn btn-warning w-100">
                                <i class="fas fa-users me-1"></i> Gérer les utilisateurs
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Statistiques des catégories
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Catégorie</th>
                                    <th>Produits</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $stmt = $pdo->query('
                                    SELECT c.nom, COUNT(p.id) as product_count
                                    FROM categories c
                                    LEFT JOIN products p ON c.id = p.categorie_id
                                    GROUP BY c.id, c.nom
                                    ORDER BY product_count DESC
                                ');
                                $categories = $stmt->fetchAll();
                                
                                foreach ($categories as $category):
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($category['nom']); ?></td>
                                    <td><?php echo $category['product_count']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
// Inclusion du pied de page
require_once 'templates/footer.php';
?> 