<?php
/**
 * Page panier
 * Affiche les produits ajoutés au panier et permet de passer commande
 */

// Titre de la page
$titre_page = 'Votre panier';
$js_specifique = 'panier'; // Ajout d'un identifiant pour le JS spécifique

// Initialisation du panier si nécessaire
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Récupération de la liste des produits du panier avec leurs détails
$panier_avec_details = [];
$total_panier = 0;

if (!empty($_SESSION['panier'])) {
    $pdo = getPDO();
    $productIds = array_keys($_SESSION['panier']);
    
    if (!empty($productIds)) {
        $in = str_repeat('?,', count($productIds) - 1) . '?';
        $stmt = $pdo->prepare("SELECT id, titre, prix, image_principale, stock FROM products WHERE id IN ($in)");
        $stmt->execute($productIds);
        // Utilisation de FETCH_UNIQUE pour avoir les IDs comme clés
        $productsData = $stmt->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);

        foreach ($_SESSION['panier'] as $productId => $item) {
            if (isset($productsData[$productId])) {
                $produit = $productsData[$productId];
                $quantite_demandee = $item['quantity'];
                // S'assurer que la quantité ne dépasse pas le stock
                $quantite_panier = min($quantite_demandee, $produit['stock']); 
                if ($quantite_panier != $quantite_demandee) {
                    // Optionnel: informer l'utilisateur que la quantité a été ajustée
                    $_SESSION['panier'][$productId]['quantity'] = $quantite_panier;
                    // Pour afficher le message, il faudrait une fonction comme displayFlashMessages()
                    // flashMessage('info', "La quantité pour '{$produit['titre']}' a été ajustée au stock disponible ({$quantite_panier}).");
                }
                
                if ($quantite_panier > 0) {
                    $sous_total = $produit['prix'] * $quantite_panier;
                    $total_panier += $sous_total;
                    
                    $panier_avec_details[$productId] = [
                        'id' => $productId,
                        'titre' => $produit['titre'],
                        'prix' => $produit['prix'],
                        'image' => $produit['image_principale'],
                        'stock' => $produit['stock'],
                        'quantite' => $quantite_panier,
                        'sous_total' => $sous_total
                    ];
                } else {
                    // Si la quantité est 0 (ou stock épuisé), on le retire du panier session
                    unset($_SESSION['panier'][$productId]);
                    // flashMessage('info', "Le produit '{$produit['titre']}' a été retiré car son stock est épuisé.");
                }
            } else {
                // Le produit n'existe plus en BDD, on le retire
                unset($_SESSION['panier'][$productId]);
                 // flashMessage('warning', "Un produit anciennement dans votre panier n'est plus disponible et a été retiré.");
            }
        }
    }
}
?>

<div class="container py-5">
    <h1 class="mb-4">Votre panier</h1>
    
    <?php /* Affichage des messages flash si la fonction existe */
    // if (function_exists('displayFlashMessages')) { displayFlashMessages(); } 
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
        <div class="card shadow-sm mb-4 cart-summary">
            <div class="card-header bg-light">
                <h5 class="mb-0">Récapitulatif de votre commande</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 cart-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;"></th>
                                <th>Produit</th>
                                <th style="width: 120px;" class="text-center">Prix</th>
                                <th style="width: 150px;" class="text-center">Quantité</th>
                                <th style="width: 120px;" class="text-end">Sous-total</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($panier_avec_details as $productId => $item): ?>
                                <tr id="cart-item-<?= $productId ?>">
                                    <td>
                                        <a href="index_.php?page=produit_details&id=<?= $productId ?>">
                                            <img src="<?= htmlspecialchars($item['image'] ?? 'admin/public/img/products/default.jpg') ?>" 
                                                 alt="<?= htmlspecialchars($item['titre']) ?>" 
                                                 class="img-thumbnail cart-item-image">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="index_.php?page=produit_details&id=<?= $productId ?>" class="text-decoration-none fw-bold cart-item-title">
                                            <?= htmlspecialchars($item['titre']) ?>
                                        </a>
                                        <small class="d-block text-muted">Stock disponible: <?= $item['stock'] ?></small>
                                    </td>
                                    <td class="text-center price-col"><span><?= number_format($item['prix'], 2, ',', ' ') ?></span> €</td>
                                    <td class="text-center quantity-col">
                                        <div class="input-group input-group-sm justify-content-center">
                                            <button type="button" class="btn btn-outline-secondary update-quantity d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;" data-product-id="<?= $productId ?>" data-action="decrease">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="text" class="form-control text-center quantity-input border-secondary" value="<?= $item['quantite'] ?>" readonly style="max-width: 50px; background-color: white;">
                                            <button type="button" class="btn btn-outline-secondary update-quantity d-flex align-items-center justify-content-center" style="width: 30px; height: 30px;" data-product-id="<?= $productId ?>" data-action="increase" <?= $item['quantite'] >= $item['stock'] ? 'disabled' : '' ?>>
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="text-end subtotal-col"><strong><span><?= number_format($item['sous_total'], 2, ',', ' ') ?></span> €</strong></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-item" data-product-id="<?= $productId ?>" title="Supprimer l'article">
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

<?php 
// Inclusion du script JS spécifique au panier si on a choisi cette approche
// ou intégration directe ici
?>
<script>
document.addEventListener('DOMContentLoaded', function() {

    // Fonction pour mettre à jour le total global du panier
    function updateCartTotal() {
        let total = 0;
        let itemCount = 0;
        document.querySelectorAll('.cart-table tbody tr').forEach(row => {
            const subtotalText = row.querySelector('.subtotal-col span').textContent.replace(/\s/g, '').replace(',', '.');
            total += parseFloat(subtotalText) || 0;
            itemCount++;
        });
        
        const totalElement = document.getElementById('cart-total');
        if (totalElement) {
            totalElement.textContent = total.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
        }
        
        // Activer/désactiver le bouton de paiement
        const checkoutButton = document.querySelector('.checkout-button');
        if (checkoutButton) {
             checkoutButton.disabled = itemCount === 0;
        }
        
        // Afficher/Masquer le message panier vide et les éléments associés
        const emptyCartMessage = document.querySelector('.empty-cart-message');
        const cartSummary = document.querySelector('.cart-summary');
        const cartActions = document.querySelector('.cart-actions');

        if (itemCount === 0) {
            if (emptyCartMessage) emptyCartMessage.classList.remove('d-none');
            if (cartSummary) cartSummary.classList.add('d-none');
            if (cartActions) cartActions.classList.add('d-none');
        } else {
            // Assurer que les éléments sont visibles si le panier n'est pas vide
            if (emptyCartMessage) emptyCartMessage.classList.add('d-none');
            if (cartSummary) cartSummary.classList.remove('d-none');
            if (cartActions) cartActions.classList.remove('d-none');
        }
    }

    // Gestion de la suppression d'un article
    document.querySelectorAll('.remove-item').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            if (!confirm('Voulez-vous vraiment supprimer cet article du panier ?')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin/src/php/ajax/remove_from_cart.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            // Supprimer la ligne du tableau
                            const row = document.getElementById('cart-item-' + productId);
                            if (row) {
                                row.remove();
                            }
                            // Mettre à jour le total et le compteur
                            updateCartTotal();
                            updateCartBadge(response.cart_count);
                            // Afficher un message de succès (peut-être plus discret qu'une alerte)
                            console.log(response.message);
                            // alert(response.message); // Décommenter si l'alerte est souhaitée
                        } else {
                            alert('Erreur: ' + (response.message || 'Impossible de supprimer l\'article.'));
                        }
                    } catch (e) {
                        console.error('Erreur JSON:', e, xhr.responseText);
                        alert('Une erreur technique est survenue lors de la suppression.');
                    }
                } else {
                    alert('Erreur serveur: ' + xhr.status + ' lors de la suppression.');
                }
            };
            
            xhr.onerror = function() {
                alert('Erreur réseau lors de la suppression de l\'article.');
            };
            
            xhr.send('product_id=' + productId);
        });
    });

    // Gestion de la mise à jour de la quantité (non implémenté ici, juste placeholder)
    document.querySelectorAll('.update-quantity').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            const action = this.getAttribute('data-action');
            // TODO: Ajouter la logique AJAX pour appeler update_cart_quantity.php
            console.log('Mise à jour quantité pour', productId, 'action:', action);
            alert('Fonctionnalité de mise à jour de quantité non implémentée.');
        });
    });

    // Fonction pour mettre à jour le badge du panier dans le header
    function updateCartBadge(count) {
        const cartBadge = document.querySelector('.navbar .fa-shopping-cart ~ .badge'); // Utilisation du tilde
        if (cartBadge) {
            cartBadge.textContent = count;
            if (count > 0) {
                cartBadge.classList.remove('d-none');
            } else {
                cartBadge.classList.add('d-none');
            }
        } else if (count > 0) {
            // Créer le badge s'il n'existe pas
            const cartLink = document.querySelector('.navbar a[href*="page=panier"]');
            if (cartLink) {
                let existingBadge = cartLink.querySelector('.badge');
                if (!existingBadge) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    newBadge.textContent = count;
                    cartLink.appendChild(newBadge);
                } else {
                    existingBadge.textContent = count;
                    existingBadge.classList.remove('d-none');
                }
            }
        }
    }
    
    // Initialiser l'état du panier au chargement
    updateCartTotal();

});
</script> 