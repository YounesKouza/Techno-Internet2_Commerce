<?php
/**
 * Page panier
 * Affiche les produits ajoutés au panier et permet de passer commande
 */

// Titre de la page
$titre_page = 'Votre panier';
$js_specifique = 'panier'; // Ajout d'un identifiant pour le JS spécifique

// Inclusion des classes nécessaires
require_once __DIR__ . '/../admin/src/php/utils/connexion.php';
require_once __DIR__ . '/../admin/src/php/utils/all_includes.php';

// Initialisation des objets DAO
$pdo = getPDO();
$cartDAO = new CartDAO($pdo);

// Récupération des détails du panier
$panier_info = $cartDAO->getCartDetails();
$panier_avec_details = $panier_info['items'];
$total_panier = $panier_info['total'];
?>

<div class="container py-5">
    <h1 class="mb-4">Votre panier</h1>
    
    <?php /* Affichage des messages flash si la fonction existe */
    if (function_exists('display_flash_message')) { display_flash_message(); } 
    ?>

    <?php if (empty($panier_avec_details)): ?>
        <div class="alert alert-info text-center empty-cart-message">
            <i class="fas fa-shopping-cart fa-2x mb-3"></i>
            <p class="mb-3">Votre panier est actuellement vide.</p>
            <a href="index_.php?page=catalogue" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i> Découvrir nos produits
            </a>
        </div>
         <div class="cart-summary d-none"></div> <!-- Placeholder pour JS -->
         <div class="cart-actions d-none"></div> <!-- Placeholder pour JS -->
    <?php else: ?>
        <!-- Récapitulatif du panier -->
        <!-- Débogage: Affichage des données -->
        <?php if(isset($_GET['debug'])): ?>
        <div class="alert alert-info mb-3">
            <h5>Débogage des données panier</h5>
            <pre><?php print_r($panier_avec_details); ?></pre>
        </div>
        <?php endif; ?>
        <!-- Fin débogage -->
        <div class="card shadow-sm mb-4 cart-summary">
            <div class="card-header bg-light">
                <h5 class="mb-0">Récapitulatif de votre commande</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover cart-table">
                        <thead class="table-light">
                            <tr>
                                <th class="col-img"></th>
                                <th>Produit</th>
                                <th class="col-price">Prix</th>
                                <th class="col-quantity">Quantité</th>
                                <th class="col-subtotal">Sous-total</th>
                                <th class="col-action"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($panier_avec_details as $item): ?>
                                <tr id="cart-item-<?= $item['id'] ?>">
                                    <td>
                                        <a href="index_.php?page=produit_details&id=<?= $item['id'] ?>">
                                            <img src="<?= htmlspecialchars($item['image'] ?? 'admin/public/img/products/default.jpg') ?>" 
                                                 alt="<?= htmlspecialchars($item['titre']) ?>" 
                                                 class="img-thumbnail cart-item-image">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="index_.php?page=produit_details&id=<?= $item['id'] ?>" class="text-decoration-none fw-bold cart-item-title">
                                            <?= htmlspecialchars($item['titre']) ?>
                                        </a>
                                        <small class="d-block text-muted">Stock disponible: <?= $item['stock'] ?? '?' ?></small>
                                    </td>
                                    <td class="text-center price-col"><span><?= number_format($item['prix'], 2, ',', ' ') ?></span> €</td>
                                    <td class="text-center quantity-col">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <button class="btn btn-sm btn-outline-secondary quantity-btn update-quantity" data-product-id="<?= $item['id'] ?>" data-action="decrease">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            
                                            <input type="text" class="form-control mx-2 quantity-input" value="<?= htmlspecialchars($item['quantity']) ?>" readonly>
                                            
                                            <button class="btn btn-sm btn-outline-secondary quantity-btn update-quantity" data-product-id="<?= $item['id'] ?>" data-action="increase" <?= isset($item['stock']) && $item['quantity'] >= $item['stock'] ? 'disabled' : '' ?>>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-end subtotal-col"><strong><span><?= number_format($item['subtotal'], 2, ',', ' ') ?></span> €</strong></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-product-id="<?= $item['id'] ?>" title="Supprimer l'article">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light d-flex justify-content-end align-items-center">
                <span class="me-3 fs-5">Total :</span>
                <strong class="text-primary fs-4" id="cart-total"><?= number_format($total_panier, 2, ',', ' ') ?> €</strong>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="d-flex justify-content-between cart-actions">
            <a href="index_.php?page=catalogue" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i> Continuer mes achats
            </a>
            <a href="index_.php?page=commande" class="btn btn-primary btn-lg checkout-button" <?= empty($panier_avec_details) ? 'disabled' : '' ?>>
                <i class="fas fa-credit-card me-2"></i> Procéder au paiement
            </a>
        </div>
        <div class="empty-cart-message d-none"></div> <!-- Placeholder pour JS -->
    <?php endif; ?>
</div>
