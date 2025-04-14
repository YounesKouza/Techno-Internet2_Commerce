<?php
/**
 * Page de détails d'un produit
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../admin/src/php/utils/connexion.php';
require_once __DIR__ . '/../admin/src/php/utils/all_includes.php';

// Récupération de l'ID du produit
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Si pas d'ID ou ID invalide, redirection vers la page catalogue
if ($product_id <= 0) {
    header('Location: index_.php?page=catalogue');
    exit;
}

// Fonction pour corriger les chemins d'image
function fixImagePath($path) {
    if (empty($path)) {
        return '';
    }
    
    // Supprimer le slash initial s'il existe
    $path = ltrim($path, '/');
    
    // Vérifier si le chemin commence par admin/
    if (strpos($path, 'admin/') !== 0) {
        $path = 'admin/public/images/' . basename($path);
    }
    
    // Remplacer les doubles slashes par un seul
    $path = str_replace('//', '/', $path);
    
    return $path;
}

// Initialisation des objets DAO
$pdo = getPDO();
$productDAO = new ProductDAO($pdo);
$productImageDAO = new ProductImageDAO($pdo);

// Récupération des informations du produit
$product = $productDAO->findById($product_id);

// Si le produit n'existe pas ou n'est pas actif, redirection vers la page 404
if (!$product || !$product->actif) {
    header('Location: index_.php?page=page404');
    exit;
}

// Corriger le chemin de l'image principale
$product->image_principale = fixImagePath($product->image_principale);

// Récupération des images supplémentaires du produit
$product_images = $productImageDAO->findByProductId($product_id);

// Corriger les chemins des images supplémentaires
foreach ($product_images as $image) {
    $image->url_image = fixImagePath($image->url_image);
}

// Récupération des produits similaires
$similar_products = $productDAO->findAllActive($product->categorie_id, true, 'p.date_creation DESC', 4);

// Filtrer pour exclure le produit actuel et corriger les chemins d'images
$filtered_similar_products = [];
foreach ($similar_products as $similar_product) {
    if ($similar_product->id != $product_id) {
        $similar_product->image_principale = fixImagePath($similar_product->image_principale);
        $filtered_similar_products[] = $similar_product;
    }
}

// Définition du titre de la page
$titre_page = htmlspecialchars($product->titre ?? 'Détails du produit');
$js_specifique = 'produit_details';

// Calcul du prix avec remise si applicable
$price = $product->prix ?? 0;
$discount_price = null;
// La colonne discount_percent n'existe pas, donc on ne calcule pas de remise
?>

<div class="container py-4">
    <!-- Fil d'Ariane -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index_.php">Accueil</a></li>
            <li class="breadcrumb-item"><a href="index_.php?page=catalogue">Catalogue</a></li>
            <li class="breadcrumb-item"><a href="index_.php?page=catalogue&category=<?= $product->categorie_id ?>"><?= htmlspecialchars($product->categorie_nom) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product->titre ?? 'Produit') ?></li>
        </ol>
    </nav>

    <!-- Informations produit -->
    <div class="row">
        <!-- Images du produit -->
        <div class="col-md-6 mb-4">
            <div id="productImageCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner rounded shadow">
                    <?php if (isset($product->image_principale) && !empty($product->image_principale)): ?>
                        <div class="carousel-item active">
                            <img src="<?= htmlspecialchars($product->image_principale) ?>" class="d-block w-100" alt="<?= htmlspecialchars($product->titre ?? 'Image principale') ?>">
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($product_images as $index => $image): ?>
                        <div class="carousel-item <?= (!isset($product->image_principale) && $index === 0) ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($image->url_image ?? '') ?>" class="d-block w-100" alt="<?= htmlspecialchars($product->titre ?? 'Image produit') ?>">
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ((!isset($product->image_principale) || empty($product->image_principale)) && count($product_images) === 0): ?>
                        <div class="carousel-item active">
                            <img src="admin/public/images/fond/1.jpg" class="d-block w-100" alt="Image par défaut">
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ((isset($product->image_principale) && !empty($product->image_principale)) || count($product_images) > 0): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productImageCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Précédent</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productImageCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Suivant</span>
                    </button>
                <?php endif; ?>
            </div>
                
            <?php if (count($product_images) > 0 || (isset($product->image_principale) && !empty($product->image_principale))): ?>
                <!-- Miniatures pour navigation -->
                <div class="row mt-3">
                    <?php if (isset($product->image_principale) && !empty($product->image_principale)): ?>
                        <div class="col-3 mb-3">
                            <img src="<?= htmlspecialchars($product->image_principale) ?>" 
                                 class="img-thumbnail product-thumbnail" 
                                 alt="Image principale" 
                                 data-bs-target="#productImageCarousel" 
                                 data-bs-slide-to="0">
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    $offset = isset($product->image_principale) && !empty($product->image_principale) ? 1 : 0;
                    foreach ($product_images as $index => $image): 
                    ?>
                        <div class="col-3 mb-3">
                            <img src="<?= htmlspecialchars($image->url_image ?? '') ?>" 
                                 class="img-thumbnail product-thumbnail" 
                                 alt="Miniature" 
                                 data-bs-target="#productImageCarousel" 
                                 data-bs-slide-to="<?= $index + $offset ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Détails et achat -->
        <div class="col-md-6">
            <h1 class="mb-3"><?= htmlspecialchars($product->titre ?? 'Produit') ?></h1>
            
            <!-- Prix et remise -->
            <div class="mb-3">
                <p class="h3 mb-0 product-price"><?= number_format($price, 2, ',', ' ') ?> €</p>
            </div>
            
            <!-- Disponibilité -->
            <div class="mb-4">
                <?php if (isset($product->stock) && $product->stock > 10): ?>
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> En stock</span>
                <?php elseif (isset($product->stock) && $product->stock > 0): ?>
                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i> Stock limité (<?= $product->stock ?>)</span>
                <?php else: ?>
                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Rupture de stock</span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-2"><?= htmlspecialchars($product->categorie_nom) ?></span>
            </div>
            
            <!-- Description courte -->
            <div class="mb-4">
                <p><?= nl2br(htmlspecialchars($product->description ?? '')) ?></p>
            </div>
            
            <!-- Caractéristiques -->
            <div class="mb-4">
                <h5>Caractéristiques</h5>
                <table class="table table-sm">
                    <tbody>
                        <?php if (isset($product->materiau) && !empty($product->materiau)): ?>
                        <tr>
                            <th scope="row" width="40%">Matériau</th>
                            <td><?= htmlspecialchars($product->materiau) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (isset($product->dimensions) && !empty($product->dimensions)): ?>
                        <tr>
                            <th scope="row">Dimensions</th>
                            <td><?= htmlspecialchars($product->dimensions) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (isset($product->couleur) && !empty($product->couleur)): ?>
                        <tr>
                            <th scope="row">Couleur</th>
                            <td><?= htmlspecialchars($product->couleur) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (isset($product->poids) && !empty($product->poids)): ?>
                        <tr>
                            <th scope="row">Poids</th>
                            <td><?= htmlspecialchars($product->poids) ?> kg</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Ajout au panier -->
            <?php if (isset($product->stock) && $product->stock > 0): ?>
                <div class="mb-4">
                    <form id="add-to-cart-form" class="d-flex align-items-center">
                        <input type="hidden" name="product_id" value="<?= $product->id ?>">
                        <div class="me-3" style="width: 100px;">
                            <label for="quantity" class="form-label">Quantité</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" value="1" min="1" max="<?= $product->stock ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-shopping-cart me-2"></i> Ajouter au panier
                        </button>
                    </form>
                    <div id="add-to-cart-message" class="mt-2"></div>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <button type="button" class="btn btn-secondary btn-lg disabled">
                        <i class="fas fa-shopping-cart me-2"></i> Produit indisponible
                    </button>
                    <p class="text-muted mt-2">Cet article est actuellement en rupture de stock.</p>
                </div>
            <?php endif; ?>
            
            <!-- Partage -->
            <div class="mb-4">
                <h5>Partager ce produit</h5>
                <div class="d-flex">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" class="btn btn-outline-primary me-2" target="_blank">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>&text=<?= urlencode($product->titre) ?>" class="btn btn-outline-info me-2" target="_blank">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode($product->titre . ' - ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" class="btn btn-outline-success me-2" target="_blank">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="mailto:?subject=<?= urlencode('Découvrez ce produit : ' . $product->titre) ?>&body=<?= urlencode('Bonjour, je pense que ce produit pourrait t\'intéresser : ' . 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']) ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Produits similaires -->
    <?php if (count($filtered_similar_products) > 0): ?>
    <div class="mt-5">
        <h2 class="mb-4">Produits similaires</h2>
        <div class="row">
            <?php foreach ($filtered_similar_products as $similar_product): ?>
                <div class="col-md-3 col-sm-6 mb-4">
                    <div class="card h-100 product-card">
                        <a href="index_.php?page=produit_details&id=<?= $similar_product->id ?>">
                            <?php if (!empty($similar_product->image_principale)): ?>
                                <img src="<?= htmlspecialchars($similar_product->image_principale) ?>" class="card-img-top" alt="<?= htmlspecialchars($similar_product->titre) ?>">
                            <?php else: ?>
                                <div class="card-img-top d-flex align-items-center justify-content-center bg-light" style="height: 200px;">
                                    <i class="fas fa-image text-muted fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </a>
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="index_.php?page=produit_details&id=<?= $similar_product->id ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($similar_product->titre) ?></a>
                            </h5>
                            <p class="card-text fw-bold"><?= number_format($similar_product->prix, 2, ',', ' ') ?> €</p>
                            <a href="index_.php?page=produit_details&id=<?= $similar_product->id ?>" class="btn btn-sm btn-outline-primary">Voir le produit</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activer les miniatures pour navigation du carousel
    document.querySelectorAll('.product-thumbnail').forEach(function(thumbnail) {
        thumbnail.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            const slideIndex = this.getAttribute('data-bs-slide-to');
            const carousel = bootstrap.Carousel.getInstance(document.querySelector(target));
            carousel.to(slideIndex);
        });
    });

    // Gestion de l'ajout au panier via AJAX
    const addToCartForm = document.getElementById('add-to-cart-form');
    const messageDiv = document.getElementById('add-to-cart-message');

    if (addToCartForm) {
        addToCartForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            // Ajouter l'action à FormData
            formData.append('action', 'add');

            // Désactiver le bouton pendant le traitement
            const submitButton = this.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Ajout en cours...';

            fetch('admin/src/php/ajax/cart_actions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-shopping-cart me-2"></i> Ajouter au panier';

                if (data.success) {
                    // Afficher un message de succès
                    messageDiv.innerHTML = `
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i> ${data.message}
                            <a href="index_.php?page=panier" class="alert-link ms-2">Voir mon panier</a>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    
                    // Mettre à jour le compteur de panier dans la navigation
                    const cartCountElement = document.querySelector('.cart-count');
                    if (cartCountElement) {
                        cartCountElement.textContent = data.cart_count;
                        cartCountElement.classList.remove('d-none');
                    }
                } else {
                    // Afficher un message d'erreur
                    messageDiv.innerHTML = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i> ${data.message}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                
                // Réactiver le bouton
                submitButton.disabled = false;
                submitButton.innerHTML = '<i class="fas fa-shopping-cart me-2"></i> Ajouter au panier';
                
                // Afficher un message d'erreur
                messageDiv.innerHTML = `
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> Une erreur est survenue lors de l'ajout au panier.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                `;
            });
        });
    }
});
</script> 