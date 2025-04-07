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

// Construction de la requête SQL
$sql_count = "SELECT COUNT(*) FROM products p WHERE p.actif = true";
$sql = "SELECT p.*, c.nom as category_name 
        FROM products p 
        JOIN categories c ON p.categorie_id = c.id 
        WHERE p.actif = true";

$params = [];

// Ajout des conditions de recherche/filtre
if (!empty($search)) {
    $sql .= " AND (p.titre ILIKE ? OR p.description ILIKE ?)";
    $sql_count .= " AND (p.titre ILIKE ? OR p.description ILIKE ?)";
    $search_term = '%' . $search . '%';
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($category_filter > 0) {
    $sql .= " AND p.categorie_id = ?";
    $sql_count .= " AND p.categorie_id = ?";
    $params[] = $category_filter;
}

// Ajout de l'ordre de tri
switch ($sort) {
    case 'name_asc':
        $sql .= " ORDER BY p.titre ASC";
        break;
    case 'name_desc':
        $sql .= " ORDER BY p.titre DESC";
        break;
    case 'price_asc':
        $sql .= " ORDER BY p.prix ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY p.prix DESC";
        break;
    case 'stock_asc':
        $sql .= " ORDER BY p.stock ASC";
        break;
    case 'stock_desc':
        $sql .= " ORDER BY p.stock DESC";
        break;
    case 'id_desc':
        $sql .= " ORDER BY p.id DESC";
        break;
    default:
        $sql .= " ORDER BY p.id ASC";
}

// Ajout de la limite pour la pagination
$sql .= " LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;

// Exécution des requêtes
$stmt_count = $pdo->prepare($sql_count);
$stmt_count->execute($params_count ?? []);
$total_items = $stmt_count->fetchColumn();

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Calcul du nombre total de pages
$total_pages = ceil($total_items / $items_per_page);

// Récupération des catégories pour le filtre
$categories = $pdo->query("SELECT id, nom FROM categories ORDER BY nom")->fetchAll();

// Traitement de la suppression d'un produit (soft delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = (int)$_POST['delete_product'];
    
    $stmt_delete = $pdo->prepare("UPDATE products SET actif = false WHERE id = ?");
    $stmt_delete->execute([$product_id]);
    
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
                                    <select name="category" class="form-select">
                                        <option value="0">Toutes les catégories</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" <?= $category_filter == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['nom']) ?>
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
                                        <th style="width: 70px">ID</th>
                                        <th style="width: 100px">Image</th>
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
                                            <td colspan="8" class="text-center py-4">Aucun produit trouvé</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($products as $product): ?>
                                            <tr>
                                                <td><?= $product['id'] ?></td>
                                                <td>
                                                    <?php if (!empty($product['image_principale'])): ?>
                                                        <img src="../../<?= htmlspecialchars($product['image_principale']) ?>" 
                                                             alt="<?= htmlspecialchars($product['titre']) ?>" 
                                                             class="img-thumbnail">
                                                    <?php else: ?>
                                                        <div class="bg-light d-flex align-items-center justify-content-center" 
                                                             style="width: 60px; height: 60px;">
                                                            <i class="fas fa-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($product['titre']) ?></td>
                                                <td><?= htmlspecialchars($product['category_name']) ?></td>
                                                <td><?= number_format($product['prix'], 2, ',', ' ') ?> €</td>
                                                <td>
                                                    <?php if ($product['stock'] <= 0): ?>
                                                        <span class="badge bg-danger">Rupture</span>
                                                    <?php elseif ($product['stock'] < 5): ?>
                                                        <span class="badge bg-warning text-dark"><?= $product['stock'] ?></span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success"><?= $product['stock'] ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($product['featured']) && $product['featured']): ?>
                                                        <span class="badge bg-primary"><i class="fas fa-star"></i> Oui</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Non</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="update_meuble.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#deleteModal" 
                                                                data-product-id="<?= $product['id'] ?>"
                                                                data-product-name="<?= htmlspecialchars($product['titre']) ?>"
                                                                title="Supprimer">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <!-- Pagination -->
                        <div class="card-footer bg-transparent">
                            <nav>
                                <ul class="pagination justify-content-center mb-0">
                                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page_num=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>">Précédent</a>
                                    </li>
                                    
                                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                                            <a class="page-link" href="?page_num=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page_num=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= $category_filter ?>&sort=<?= $sort ?>">Suivant</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Modal de confirmation de suppression -->
                <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmer la suppression</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Êtes-vous sûr de vouloir supprimer le produit <strong id="product-name-placeholder"></strong> ?</p>
                                <p class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i> Cette action est irréversible.</p>
                            </div>
                            <div class="modal-footer">
                                <form action="" method="post">
                                    <input type="hidden" name="delete_product" id="delete-product-id">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <button type="submit" class="btn btn-danger">Supprimer</button>
                                </form>
                            </div>
                        </div>
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
    
    <script>
        // Script pour le modal de suppression
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const productId = button.getAttribute('data-product-id');
                    const productName = button.getAttribute('data-product-name');
                    
                    const productIdInput = document.getElementById('delete-product-id');
                    const productNamePlaceholder = document.getElementById('product-name-placeholder');
                    
                    productIdInput.value = productId;
                    productNamePlaceholder.textContent = productName;
                });
            }
        });
    </script>
</body>
</html> 