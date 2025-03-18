<?php
// pages/catalogue.php – Catalogue des annonces
include '../src/php/db/dbConnect.php';
include '../src/php/utils/fonctions_produits.php';

// Définition de la constante BASE_URL si non définie
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Exos/Techno-internet2_commerce/Techno-internet2_commerce');
}

// Récupération des catégories
$categories = getAllCategories($pdo);

// Récupération des produits
$products = [];
$categoryFilter = null;
$searchQuery = null;

// Filtre par catégorie si spécifié
if (isset($_GET['category'])) {
    $categoryName = $_GET['category'];
    foreach ($categories as $cat) {
        if ($cat['nom'] == $categoryName) {
            $categoryFilter = $cat['id'];
            break;
        }
    }
    
    if ($categoryFilter) {
        $products = getProductsByCategory($pdo, $categoryFilter);
    }
} 
// Recherche par mot-clé si spécifié
elseif (isset($_GET['search']) && !empty($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
    $products = searchProducts($pdo, $searchQuery);
}
// Sinon tous les produits
else {
    $products = getAllProducts($pdo);
}

// Pagination
$productsPerPage = 8;
$totalProducts = count($products);
$totalPages = ceil($totalProducts / $productsPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $productsPerPage;
$currentProducts = array_slice($products, $offset, $productsPerPage);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Catalogue - Furniture</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <?php include '../public/includes/header.php'; ?>
  <div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Catalogue</h1>
      <div>
        <form class="d-flex" action="catalogue.php" method="GET">
          <input type="text" name="search" class="form-control me-2" placeholder="Rechercher un meuble" value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>">
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
        </form>
      </div>
    </div>
    
    <?php if ($categoryFilter || $searchQuery): ?>
      <div class="mb-4">
        <a href="catalogue.php" class="btn btn-outline-secondary">
          <i class="fas fa-arrow-left"></i> Retour au catalogue complet
        </a>
        <?php if ($searchQuery): ?>
          <span class="ms-3">Résultats pour : <strong><?php echo htmlspecialchars($searchQuery); ?></strong></span>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    
    <div class="row">
      <!-- Filtres de catégories -->
      <div class="col-md-3 mb-4">
        <div class="card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Catégories</h5>
          </div>
          <div class="list-group list-group-flush">
            <?php foreach ($categories as $category): ?>
              <a href="?category=<?php echo urlencode($category['nom']); ?>" 
                 class="list-group-item list-group-item-action <?php echo ($categoryFilter == $category['id']) ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['nom']); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      
      <!-- Liste des produits -->
      <div class="col-md-9">
        <?php if (empty($currentProducts)): ?>
          <div class="alert alert-info">
            Aucun produit trouvé.
          </div>
        <?php else: ?>
          <div class="row">
            <?php foreach ($currentProducts as $product): ?>
              <div class="col-md-6 col-lg-4 mb-4">
                <div class="product-card h-100">
                  <div class="product-img">
                    <?php $imagePath = !empty($product['image_principale']) ? $product['image_principale'] : BASE_URL . '/public/images/produits/default.jpg'; ?>
                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['titre']); ?>">
                  </div>
                  <div class="card-body p-3">
                    <div class="eco-badge">
                      <i class="fas fa-leaf"></i> Éco-responsable
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($product['titre']); ?></h5>
                    <p class="card-text text-muted">
                      <?php echo htmlspecialchars(substr($product['description'], 0, 60)) . '...'; ?>
                    </p>
                    <div class="d-flex justify-content-between align-items-center mt-3">
                      <span class="fw-bold"><?php echo number_format($product['prix'], 2, ',', ' '); ?> €</span>
                      <div>
                        <a href="produit_detail.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary me-1">
                          <i class="fas fa-eye"></i>
                        </a>
                        <a href="<?php echo BASE_URL; ?>/pages/panier.php?action=ajouter&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                          <i class="fas fa-shopping-cart"></i>
                        </a>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <nav aria-label="Pagination du catalogue" class="mt-4">
              <ul class="pagination justify-content-center">
                <li class="page-item <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?>">
                  <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage - 1])); ?>">
                    <i class="fas fa-chevron-left"></i>
                  </a>
                </li>
                
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                      <?php echo $i; ?>
                    </a>
                  </li>
                <?php endfor; ?>
                
                <li class="page-item <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?>">
                  <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $currentPage + 1])); ?>">
                    <i class="fas fa-chevron-right"></i>
                  </a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
  
  <?php include '../public/includes/footer.php'; ?>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
