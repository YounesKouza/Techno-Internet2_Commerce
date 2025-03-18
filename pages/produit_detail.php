<?php
// pages/produit_detail.php – Détail d’un produit
include '../src/php/db/dbConnect.php';
include '../src/php/utils/fonctions_produits.php';

// Définition de la constante BASE_URL si non définie
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Exos/Techno-internet2_commerce/Techno-internet2_commerce');
}

// Récupération de l'ID du produit
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Récupération du produit avec ses images
$product = getProductById($pdo, $productId);

// Redirection si le produit n'existe pas
if (!$product) {
    header('Location: catalogue.php');
    exit;
}

// Récupération des produits similaires (même catégorie)
$similarProducts = [];
if ($product['categorie_id']) {
    $similarProducts = getProductsByCategory($pdo, $product['categorie_id']);
    // Filtrer pour exclure le produit actuel et limiter à 3 produits
    $similarProducts = array_filter($similarProducts, function($item) use ($productId) {
        return $item['id'] != $productId;
    });
    $similarProducts = array_slice($similarProducts, 0, 3);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['titre']); ?> - Furniture</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
</head>
<body>
    <?php include '../public/includes/header.php'; ?>
    
    <div class="container my-5">
        <div class="mb-4">
            <a href="catalogue.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Retour au catalogue
            </a>
        </div>
        
        <div class="row">
            <!-- Images du produit -->
            <div class="col-md-6 mb-4">
                <?php
                // Image principale
                $imagePrincipale = !empty($product['image_principale']) 
                    ? $product['image_principale'] 
                    : BASE_URL . '/public/images/produits/default.jpg';
                ?>
                <div class="product-detail-image mb-3">
                    <img src="<?php echo $imagePrincipale; ?>" class="img-fluid rounded main-product-image" 
                         alt="<?php echo htmlspecialchars($product['titre']); ?>">
                </div>
                
                <?php if (!empty($product['images'])): ?>
                <div class="row product-thumbnails">
                    <!-- Vignette de l'image principale -->
                    <div class="col-3 mb-3">
                        <img src="<?php echo $imagePrincipale; ?>" class="img-thumbnail product-thumbnail active" 
                             onclick="changeMainImage(this.src)" 
                             alt="<?php echo htmlspecialchars($product['titre']); ?>">
                    </div>
                    
                    <!-- Vignettes des images supplémentaires -->
                    <?php foreach ($product['images'] as $image): ?>
                    <div class="col-3 mb-3">
                        <img src="<?php echo $image; ?>" class="img-thumbnail product-thumbnail" 
                             onclick="changeMainImage(this.src)" 
                             alt="<?php echo htmlspecialchars($product['titre']); ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Informations du produit -->
            <div class="col-md-6">
                <h1 class="mb-3"><?php echo htmlspecialchars($product['titre']); ?></h1>
                
                <div class="mb-3">
                    <span class="badge bg-primary"><?php echo htmlspecialchars($product['categorie_nom']); ?></span>
                    <span class="badge bg-success ms-2"><i class="fas fa-leaf"></i> Éco-responsable</span>
                </div>
                
                <p class="fw-bold fs-4 mb-3"><?php echo number_format($product['prix'], 2, ',', ' '); ?> €</p>
                
                <div class="mb-4">
                    <p class="text-muted">
                        <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                    </p>
                </div>
                
                <div class="mb-4">
                    <p class="mb-2">
                        <strong>Disponibilité :</strong> 
                        <?php if ($product['stock'] > 0): ?>
                            <span class="text-success">En stock (<?php echo $product['stock']; ?>)</span>
                        <?php else: ?>
                            <span class="text-danger">Rupture de stock</span>
                        <?php endif; ?>
                    </p>
                </div>
                
                <!-- Formulaire d'ajout au panier -->
                <form action="<?php echo BASE_URL; ?>/pages/panier.php" method="GET" class="mb-4">
                    <input type="hidden" name="action" value="ajouter">
                    <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                    
                    <div class="row g-3 align-items-center mb-3">
                        <div class="col-auto">
                            <label for="quantite" class="col-form-label">Quantité :</label>
                        </div>
                        <div class="col-auto">
                            <select name="quantite" id="quantite" class="form-select">
                                <?php for ($i = 1; $i <= min(10, $product['stock']); $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                        <i class="fas fa-shopping-cart me-2"></i> Ajouter au panier
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Produits similaires -->
        <?php if (!empty($similarProducts)): ?>
        <div class="mt-5">
            <h3 class="mb-4">Produits similaires</h3>
            <div class="row">
                <?php foreach ($similarProducts as $similarProduct): ?>
                <div class="col-md-4 mb-4">
                    <div class="product-card h-100">
                        <div class="product-img">
                            <?php $imagePath = !empty($similarProduct['image_principale']) 
                                ? $similarProduct['image_principale'] 
                                : BASE_URL . '/public/images/produits/default.jpg'; ?>
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($similarProduct['titre']); ?>">
                        </div>
                        <div class="card-body p-3">
                            <h5 class="card-title"><?php echo htmlspecialchars($similarProduct['titre']); ?></h5>
                            <p class="card-text text-muted">
                                <?php echo htmlspecialchars(substr($similarProduct['description'], 0, 60)) . '...'; ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="fw-bold"><?php echo number_format($similarProduct['prix'], 2, ',', ' '); ?> €</span>
                                <a href="produit_detail.php?id=<?php echo $similarProduct['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    Voir détails
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include '../public/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fonction pour changer l'image principale
        function changeMainImage(src) {
            document.querySelector('.main-product-image').src = src;
            
            // Mise à jour des classes actives sur les vignettes
            document.querySelectorAll('.product-thumbnail').forEach(thumbnail => {
                thumbnail.classList.remove('active');
                if (thumbnail.src === src) {
                    thumbnail.classList.add('active');
                }
            });
        }
    </script>
</body>
</html>
