<?php
/**
 * Page d'accueil du site e-commerce Furniture
 */

// Titre de la page
$titre_page = 'Accueil';

// Ajout d'une classe spéciale pour le main de la page d'accueil
$main_class = 'home-page';

// --- Utilisation des DAO ---
// L'instance $pdo est maintenant créée dans all_includes.php

// Instanciation des DAO nécessaires
$productDAO = new ProductDAO($pdo);
$categoryDAO = new CategoryDAO($pdo);

// Récupération des produits à l'honneur (Exemple: les 4 plus récents)
// Note: findFeatured pourrait être une méthode dédiée dans ProductDAO
$featured_products = $productDAO->findAllActive(null, null, 'p.date_creation DESC', 4, 0);

// Récupération des catégories principales
$categories = $categoryDAO->findLimitedSortedById(4); // Récupérer 4 catégories

?>

<!-- Hero Section avec arrière-plan plein écran -->
<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-md-10 col-lg-8 mx-auto text-center">
                <h1 class="display-4 fw-bold mb-4">Bienvenue chez Furniture</h1>
                <p class="lead mb-5">Découvrez notre collection de meubles élégants et fonctionnels pour sublimer votre intérieur.</p>
                <div class="d-flex justify-content-center">
                    <a href="index_.php?page=catalogue" class="btn btn-primary btn-lg">Découvrir le catalogue</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Produits à la une -->
<section class="mb-5">
    <h2 class="text-center mb-4">Nos produits à la une</h2>
    
    <div class="row">
        <?php if (empty($featured_products)): ?>
            <div class="col-12 text-center">
                <p>Aucun produit à la une pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($featured_products as $product): ?>
                <div class="col-sm-6 col-lg-3 mb-4">
                    <div class="card h-100 product-card">
                        <a href="index_.php?page=produit_details&id=<?= $product->id ?>" class="product-img-link">
                            <div class="product-img">
                                <img src="<?= htmlspecialchars($product->image_principale ? '/Exos/Techno-internet2_commerce/'.$product->image_principale : '/Exos/Techno-internet2_commerce/admin/public/img/products/default.jpg') ?>" 
                                     alt="<?= htmlspecialchars($product->titre) ?>">
                            </div>
                        </a>
                        <div class="card-body d-flex flex-column">
                            <span class="category-badge mb-2"><?= htmlspecialchars($product->categorie_nom) ?></span>
                            <h5 class="card-title flex-grow-1">
                                <a href="index_.php?page=produit_details&id=<?= $product->id ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($product->titre) ?>
                                </a>
                            </h5>
                            <p class="product-price mb-2"><?= number_format($product->prix, 2, ',', ' ') ?> €</p>
                            <div class="mt-auto">
                                <button class="btn btn-sm btn-primary w-100 add-to-cart" data-product-id="<?= $product->id ?>">
                                    <i class="fas fa-cart-plus"></i> Ajouter au panier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <div class="text-center mt-4">
        <a href="index_.php?page=catalogue" class="btn btn-lg btn-outline-primary">Voir tous les produits</a>
    </div>
</section>

<!-- Catégories principales -->
<section class="mb-5">
    <h2 class="text-center mb-4">Explorez nos catégories</h2>
    
    <div class="row">
        <?php if (empty($categories)): ?>
             <div class="col-12 text-center">
                <p>Aucune catégorie à afficher pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($categories as $category): ?>
                <div class="col-md-6 col-lg-3 mb-4">
                    <div class="category-card">
                        <img src="/Exos/Techno-internet2_commerce/admin/public/images/fond/<?= $category->id ?>.jpg" alt="<?= htmlspecialchars($category->nom) ?>">
                        <div class="category-overlay">
                            <h3 class="mb-2"><?= htmlspecialchars($category->nom) ?></h3>
                            <a href="index_.php?page=catalogue&category=<?= $category->id ?>" class="btn btn-sm btn-primary">
                                Découvrir <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Avantages -->
<section class="bg-light py-4 mb-5 rounded shadow">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="p-3">
                    <i class="fas fa-truck fa-2x text-primary mb-3"></i>
                    <h5>Livraison gratuite</h5>
                    <p class="text-muted small">Pour toute commande de plus de 150€</p>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="p-3">
                    <i class="fas fa-shield-alt fa-2x text-primary mb-3"></i>
                    <h5>Garantie 2 ans</h5>
                    <p class="text-muted small">Sur tous nos produits</p>
                </div>
            </div>
            <div class="col-md-3 mb-3 mb-md-0">
                <div class="p-3">
                    <i class="fas fa-exchange-alt fa-2x text-primary mb-3"></i>
                    <h5>Retour facile</h5>
                    <p class="text-muted small">30 jours pour changer d'avis</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-3">
                    <i class="fas fa-headset fa-2x text-primary mb-3"></i>
                    <h5>Support 24/7</h5>
                    <p class="text-muted small">Service client à votre écoute</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter-section py-5 rounded">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <h3 class="text-primary fw-bold mb-3">Restez informé de nos nouveautés</h3>
                <p>Inscrivez-vous à notre newsletter pour recevoir nos offres exclusives et découvrir nos nouvelles collections en avant-première.</p>
            </div>
            <div class="col-md-6">
                <form action="#" method="post" class="d-flex">
                    <input type="email" class="form-control me-2" placeholder="Votre adresse email" required>
                    <button type="submit" class="btn btn-primary">S'inscrire</button>
                </form>
                <small class="form-text text-muted mt-2">Nous respectons votre vie privée. Désinscription possible à tout moment.</small>
            </div>
        </div>
    </div>
</section> 