<?php
/**
 * Page de gestion des meubles (produits)
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
require_once '../src/php/utils/all_includes.php';

// Paramètres de pagination
$page = isset($_GET['page_num']) ? (int)$_GET['page_num'] : 1;
$items_per_page = 10;
$offset = ($page - 1) * $items_per_page;

// Paramètres de recherche et filtre
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'id_asc';

// Connexion à la base de données
$pdo = getPDO();

// Initialisation des objets DAO
$productDAO = new ProductDAO($pdo);
$categoryDAO = new CategoryDAO($pdo);

// Détermination de l'ordre de tri
$orderBy = 'p.id ASC'; // par défaut
switch ($sort) {
    case 'name_asc':
        $orderBy = 'p.titre ASC';
        break;
    case 'name_desc':
        $orderBy = 'p.titre DESC';
        break;
    case 'price_asc':
        $orderBy = 'p.prix ASC';
        break;
    case 'price_desc':
        $orderBy = 'p.prix DESC';
        break;
    case 'stock_asc':
        $orderBy = 'p.stock ASC';
        break;
    case 'stock_desc':
        $orderBy = 'p.stock DESC';
        break;
    case 'id_desc':
        $orderBy = 'p.id DESC';
        break;
}

// Récupération des produits avec filtre et pagination
if (!empty($search)) {
    // Si recherche, utiliser la méthode de recherche
    $total_items = $productDAO->countSearchResults($search, true, $category_filter > 0 ? $category_filter : null);
    $products = $productDAO->search($search, true, $items_per_page, $offset, $category_filter > 0 ? $category_filter : null, $orderBy);
} else {
    // Sinon, récupérer tous les produits actifs avec filtrage
    $total_items = $productDAO->countAll($category_filter > 0 ? $category_filter : null, true);
    $products = $productDAO->findAll($category_filter > 0 ? $category_filter : null, true, $orderBy, $items_per_page, $offset);
}

// Calcul du nombre total de pages
$total_pages = ceil($total_items / $items_per_page);

// Récupération des catégories pour le filtre
$categories = $categoryDAO->findAll();

// Traitement de la suppression d'un produit (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['delete_product'];
    
    // Désactiver le produit (soft delete)
    $productDAO->toggleActiveStatus($product_id, false);
    
    // Redirection pour éviter les soumissions multiples
    header('Location: gestion_meubles.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des meubles | Furniture Admin</title>
    
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
            <?php generate_sidebar('products'); ?>
            
            <!-- Contenu principal -->
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Gestion des meubles</h1>
                    <div>
                        <a href="ajout_meuble.php" class="btn btn-success">
                            <i class="fas fa-plus me-1"></i> Ajouter un produit
                        </a>
                    </div>
                </div>
                
                <!-- Barre de filtres et recherche -->
                <div class="card filter-controls mb-4">
                    <div class="card-body">
                        <form action="" method="get" class="row g-3">
                            <!-- Recherche -->
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Rechercher</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Rechercher un produit..." value="<?= $search ?>">
                                        <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Filtre par catégorie -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Catégorie</label>
                                    <select class="form-select" id="category-filter" name="category">
                                        <option value="">Toutes les catégories</option>
                                    <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category->id ?>" <?= $category_filter == $category->id ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category->nom) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                </div>
                            </div>
                            
                            <!-- Tri -->
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Trier par</label>
                                <select name="sort" class="form-select">
                                    <option value="id_asc" <?= $sort === 'id_asc' ? 'selected' : '' ?>>ID (croissant)</option>
                                    <option value="id_desc" <?= $sort === 'id_desc' ? 'selected' : '' ?>>ID (décroissant)</option>
                                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>Nom (A-Z)</option>
                                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>Nom (Z-A)</option>
                                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Prix (croissant)</option>
                                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Prix (décroissant)</option>
                                    <option value="stock_asc" <?= $sort === 'stock_asc' ? 'selected' : '' ?>>Stock (faible-élevé)</option>
                                    <option value="stock_desc" <?= $sort === 'stock_desc' ? 'selected' : '' ?>>Stock (élevé-faible)</option>
                                </select>
                                </div>
                            </div>
                            
                            <!-- Boutons d'action -->
                            <div class="col-md-2 d-flex align-items-end">
                                <div class="form-group w-100">
                                    <button type="submit" class="btn btn-primary me-2 w-100">Filtrer</button>
                                    <a href="gestion_meubles.php" class="btn btn-outline-secondary w-100 mt-2">Réinitialiser</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tableau des produits -->
                <div class="card shadow-sm">
                    <div class="card-header bg-transparent py-3">
                        <h5 class="mb-0 fw-bold">Liste des produits</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table admin-table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="col-id">ID</th>
                                        <th class="col-img">Image</th>
                                        <th>Nom</th>
                                        <th>Catégorie</th>
                                        <th>Prix</th>
                                        <th>Stock</th>
                                        <th>Mise en avant</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($products)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-4">Aucun produit trouvé</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?= $product->id ?></td>
                                                <td><?= htmlspecialchars($product->titre) ?></td>
                                                <td><?= htmlspecialchars($product->categorie_nom ?? 'Non catégorisé') ?></td>
                                                <td><?= number_format($product->prix, 2, ',', ' ') ?> €</td>
                                                <td>
                                                    <?php if ($product->stock < 5): ?>
                                                        <span class="badge bg-danger"><?= $product->stock ?></span>
                                                    <?php elseif ($product->stock < 10): ?>
                                                        <span class="badge bg-warning text-dark"><?= $product->stock ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><?= $product->stock ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($product->featured): ?>
                                                        <span class="badge bg-primary">Oui</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Non</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="update_meuble.php?id=<?= $product->id ?>" class="btn btn-outline-primary" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                            data-bs-target="#deleteProductModal<?= $product->id ?>" 
                                                                title="Supprimer">
                                                        <i class="fas fa-trash"></i>
                                                        </button>
                                                    
                                                    <!-- Modal de confirmation de suppression -->
                                                    <div class="modal fade" id="deleteProductModal<?= $product->id ?>" tabindex="-1" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Confirmer la suppression</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>Êtes-vous sûr de vouloir supprimer le produit <strong><?= htmlspecialchars($product->titre) ?></strong> ?</p>
                                                                    <p class="mb-0 text-muted small">Note: Le produit sera marqué comme inactif mais restera dans la base de données.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                    <form action="" method="post">
                                                                        <input type="hidden" name="delete_product" value="<?= $product->id ?>">
                                                                        <button type="submit" class="btn btn-danger">Supprimer</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="card-footer bg-transparent">
                            <nav>
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page_num=<?= $page-1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>">
                                            <i class="fas fa-chevron-left small"></i> Précédent
                                        </a>
                                    </li>
                                    
                                    <?php
                                    // Affichage limité de pages
                                    $max_visible_pages = 5;
                                    $start_page = max(1, min($page - floor($max_visible_pages/2), $total_pages - $max_visible_pages + 1));
                                    $end_page = min($start_page + $max_visible_pages - 1, $total_pages);
                                    
                                    // Première page
                                    if ($start_page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page_num=1&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>">1</a>
                                        </li>
                                        <?php if ($start_page > 2): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Pages visibles -->
                                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page_num=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <!-- Dernière page -->
                                    <?php if ($end_page < $total_pages): ?>
                                        <?php if ($end_page < $total_pages - 1): ?>
                                            <li class="page-item disabled">
                                                <span class="page-link">...</span>
                                            </li>
                                        <?php endif; ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page_num=<?= $total_pages ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>"><?= $total_pages ?></a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page_num=<?= $page+1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>">
                                            Suivant <i class="fas fa-chevron-right small"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
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
    
    <!-- JavaScript principal -->
    <script src="/Exos/Techno-internet2_commerce/admin/public/js/fonction.js"></script>
</body>
</html> 