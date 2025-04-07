<?php
/**
 * Page de détails d'un produit
 */

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

// Récupération des informations du produit
$pdo = getPDO();
$stmt = $pdo->prepare("
    SELECT p.*, c.nom as category_name 
    FROM products p 
    JOIN categories c ON p.categorie_id = c.id 
    WHERE p.id = ? AND p.actif = true
");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

// Si le produit n'existe pas, redirection vers la page 404
if (!$product) {
    header('Location: index_.php?page=page404');
    exit;
}

// Corriger le chemin de l'image principale
if (isset($product['image_principale'])) {
    $product['image_principale'] = fixImagePath($product['image_principale']);
}

// Récupération des images supplémentaires du produit
$images_stmt = $pdo->prepare("
    SELECT * FROM images_products 
    WHERE produit_id = ? 
    ORDER BY id ASC
");
$images_stmt->execute([$product_id]);
$images = $images_stmt->fetchAll();

// Corriger les chemins des images supplémentaires
foreach ($images as &$image) {
    if (isset($image['image_path'])) {
        $image['image_path'] = fixImagePath($image['image_path']);
    }
}
unset($image); // Détruire la référence

// Récupération des produits similaires
$similar_stmt = $pdo->prepare("
    SELECT p.id, p.titre, p.prix, p.image_principale as image_path
    FROM products p 
    WHERE p.categorie_id = ? AND p.id != ? AND p.actif = true AND p.stock > 0
    ORDER BY p.date_creation DESC
    LIMIT 4
");
$similar_stmt->execute([$product['categorie_id'], $product_id]);
$similar_products = $similar_stmt->fetchAll();

// Définition du titre de la page
$titre_page = htmlspecialchars($product['titre'] ?? 'Détails du produit');
$js_specifique = 'produit_details';

// Calcul du prix avec remise si applicable
$price = $product['prix'] ?? 0;
$discount_price = null;
// La colonne discount_percent n'existe pas, donc on ne calcule pas de remise
?>

<div class="container py-4">
    <!-- Fil d'Ariane -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index_.php">Accueil</a></li>
            <li class="breadcrumb-item"><a href="index_.php?page=catalogue">Catalogue</a></li>
            <li class="breadcrumb-item"><a href="index_.php?page=catalogue&category=<?= $product['categorie_id'] ?>"><?= htmlspecialchars($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($product['titre'] ?? 'Produit') ?></li>
        </ol>
    </nav>

    <!-- Informations produit -->
    <div class="row">
        <!-- Images du produit -->
        <div class="col-md-6 mb-4">
            <div id="productImageCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner rounded shadow">
                    <?php if (isset($product['image_principale']) && !empty($product['image_principale'])): ?>
                        <div class="carousel-item active">
                            <img src="<?= htmlspecialchars($product['image_principale']) ?>" class="d-block w-100" alt="<?= htmlspecialchars($product['titre'] ?? 'Image principale') ?>">
                        </div>
                    <?php endif; ?>
                    
                    <?php foreach ($images as $index => $image): ?>
                        <div class="carousel-item <?= (!isset($product['image_principale']) && $index === 0) ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($image['image_path'] ?? '') ?>" class="d-block w-100" alt="<?= htmlspecialchars($product['titre'] ?? 'Image produit') ?>">
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ((!isset($product['image_principale']) || empty($product['image_principale'])) && count($images) === 0): ?>
                        <div class="carousel-item active">
                            <img src="admin/public/images/fond/1.jpg" class="d-block w-100" alt="Image par défaut">
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ((isset($product['image_principale']) && !empty($product['image_principale'])) || count($images) > 0): ?>
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
                
            <?php if (count($images) > 0 || (isset($product['image_principale']) && !empty($product['image_principale']))): ?>
                <!-- Miniatures pour navigation -->
                <div class="row mt-3">
                    <?php if (isset($product['image_principale']) && !empty($product['image_principale'])): ?>
                        <div class="col-3 mb-3">
                            <img src="<?= htmlspecialchars($product['image_principale']) ?>" 
                                 class="img-thumbnail product-thumbnail" 
                                 alt="Image principale" 
                                 data-bs-target="#productImageCarousel" 
                                 data-bs-slide-to="0">
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    $offset = isset($product['image_principale']) && !empty($product['image_principale']) ? 1 : 0;
                    foreach ($images as $index => $image): 
                    ?>
                        <div class="col-3 mb-3">
                            <img src="<?= htmlspecialchars($image['image_path'] ?? '') ?>" 
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
            <h1 class="mb-3"><?= htmlspecialchars($product['titre'] ?? 'Produit') ?></h1>
            
            <!-- Prix et remise -->
            <div class="mb-3">
                <p class="h3 mb-0 product-price"><?= number_format($price, 2, ',', ' ') ?> €</p>
            </div>
            
            <!-- Disponibilité -->
            <div class="mb-4">
                <?php if (isset($product['stock']) && $product['stock'] > 10): ?>
                    <span class="badge bg-success"><i class="fas fa-check me-1"></i> En stock</span>
                <?php elseif (isset($product['stock']) && $product['stock'] > 0): ?>
                    <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i> Stock limité (<?= $product['stock'] ?>)</span>
                <?php else: ?>
                    <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Rupture de stock</span>
                <?php endif; ?>
                <span class="badge bg-secondary ms-2"><?= htmlspecialchars($product['category_name']) ?></span>
            </div>
            
            <!-- Description courte -->
            <div class="mb-4">
                <p><?= nl2br(htmlspecialchars($product['description'] ?? '')) ?></p>
            </div>
            
            <!-- Caractéristiques -->
            <div class="mb-4">
                <h5>Caractéristiques</h5>
                <table class="table table-sm">
                    <tbody>
                        <?php if (isset($product['materiau']) && !empty($product['materiau'])): ?>
                        <tr>
                            <th scope="row" width="40%">Matériau</th>
                            <td><?= htmlspecialchars($product['materiau']) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (isset($product['dimensions']) && !empty($product['dimensions'])): ?>
                        <tr>
                            <th scope="row">Dimensions</th>
                            <td><?= htmlspecialchars($product['dimensions']) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (isset($product['couleur']) && !empty($product['couleur'])): ?>
                        <tr>
                            <th scope="row">Couleur</th>
                            <td><?= htmlspecialchars($product['couleur']) ?></td>
                        </tr>
                        <?php endif; ?>
                        
                        <?php if (isset($product['poids']) && !empty($product['poids'])): ?>
                        <tr>
                            <th scope="row">Poids</th>
                            <td><?= htmlspecialchars($product['poids']) ?> kg</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Ajout au panier -->
            <?php if (isset($product['stock']) && $product['stock'] > 0): ?>
                <div class="mb-4">
                    <form id="add-to-cart-form" class="d-flex align-items-center">
                        <div class="input-group me-3" style="max-width: 150px;">
                            <button type="button" id="decrease-btn" class="btn btn-outline-secondary quantity-btn d-flex align-items-center justify-content-center" style="width: 40px; height: 38px;" data-action="decrease">
                                <i class="fas fa-minus"></i>
                            </button>
                            <input type="number" id="quantity_<?= $product['id'] ?>" class="form-control text-center" 
                                min="1" max="<?= $product['stock'] ?>" value="1" step="1">
                            <button type="button" id="increase-btn" class="btn btn-outline-secondary quantity-btn d-flex align-items-center justify-content-center" style="width: 40px; height: 38px;" data-action="increase">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button type="button" id="add-to-cart-button" class="btn btn-primary add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                            <i class="fas fa-shopping-cart me-2"></i> Ajouter au panier
                        </button>
                    </form>
                    <div id="cart-message" class="mt-2"></div>
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <button class="btn btn-secondary disabled">
                        <i class="fas fa-shopping-cart me-2"></i> Indisponible
                    </button>
                    <a href="#" class="btn btn-outline-primary ms-2 notify-stock">
                        <i class="fas fa-bell me-2"></i> M'alerter quand ce produit sera disponible
                    </a>
                </div>
            <?php endif; ?>
            
            <!-- Livraison et paiement -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="d-flex mb-2">
                        <i class="fas fa-truck text-primary fa-fw me-2 mt-1"></i>
                        <div>
                            <strong>Livraison gratuite</strong><br>
                            <small class="text-muted">Pour toute commande de plus de 150€</small>
                        </div>
                    </div>
                    <div class="d-flex mb-2">
                        <i class="fas fa-undo text-primary fa-fw me-2 mt-1"></i>
                        <div>
                            <strong>Retours gratuits sous 30 jours</strong><br>
                            <small class="text-muted">Si vous n'êtes pas satisfait de votre achat</small>
                        </div>
                    </div>
                    <div class="d-flex">
                        <i class="fas fa-lock text-primary fa-fw me-2 mt-1"></i>
                        <div>
                            <strong>Paiement sécurisé</strong><br>
                            <small class="text-muted">
                                <i class="fab fa-cc-visa me-1"></i>
                                <i class="fab fa-cc-mastercard me-1"></i>
                                <i class="fab fa-cc-paypal"></i>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Produits similaires -->
    <?php if (count($similar_products) > 0): ?>
    <div class="mt-5">
        <h2 class="mb-4">Produits similaires</h2>
        
        <div class="row">
            <?php foreach ($similar_products as $similar): ?>
                <div class="col-6 col-md-3 mb-4">
                    <div class="card h-100 similar-product">
                        <a href="index_.php?page=produit_details&id=<?= $similar['id'] ?>">
                            <img src="<?= htmlspecialchars($similar['image_path'] ?? 'admin/public/img/products/default.jpg') ?>" class="card-img-top" alt="<?= htmlspecialchars($similar['titre'] ?? 'Produit similaire') ?>">
                        </a>
                        <div class="card-body text-center">
                            <h6 class="card-title"><?= htmlspecialchars($similar['titre'] ?? 'Produit similaire') ?></h6>
                            <p class="card-text">
                                <strong><?= number_format($similar['prix'] ?? 0, 2, ',', ' ') ?> €</strong>
                            </p>
                            <a href="index_.php?page=produit_details&id=<?= $similar['id'] ?>" class="btn btn-sm btn-outline-primary">Voir</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Débogage des images -->
    <div class="container mt-5 border p-3 bg-light">
        <h4>Débogage des images</h4>
        <p><strong>Image principale:</strong> <?= $product['image_principale'] ?? 'Non définie' ?></p>
        
        <?php 
        // Vérifier si l'image principale existe physiquement
        $imagePrincipaleExists = false;
        $imagePrincipalePath = '';
        
        if (!empty($product['image_principale'])) {
            // Chemin relatif dans le système de fichiers
            $imagePrincipalePath = str_replace('/', DIRECTORY_SEPARATOR, $product['image_principale']);
            $fullServerPath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'Exos' . DIRECTORY_SEPARATOR . 'Techno-internet2_commerce' . DIRECTORY_SEPARATOR . $imagePrincipalePath;
            $imagePrincipaleExists = file_exists($fullServerPath);
            
            echo "<p>Chemin complet testé: " . htmlspecialchars($fullServerPath) . " - Existe: " . ($imagePrincipaleExists ? 'Oui' : 'Non') . "</p>";
            
            // Essayons un autre chemin
            $alternativePath = $_SERVER['DOCUMENT_ROOT'] . $product['image_principale'];
            $alternativeExists = file_exists($alternativePath);
            
            echo "<p>Chemin alternatif testé: " . htmlspecialchars($alternativePath) . " - Existe: " . ($alternativeExists ? 'Oui' : 'Non') . "</p>";
        }
        ?>
        
        <strong>Autres images:</strong>
        <?php if(count($images) > 0): ?>
            <ul>
                <?php foreach($images as $img): ?>
                    <?php
                    $imgExists = false;
                    if (!empty($img['image_path'])) {
                        $imgPath = str_replace('/', DIRECTORY_SEPARATOR, $img['image_path']);
                        $fullPath = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . 'Exos' . DIRECTORY_SEPARATOR . 'Techno-internet2_commerce' . DIRECTORY_SEPARATOR . $imgPath;
                        $imgExists = file_exists($fullPath);
                    }
                    ?>
                    <li>
                        <?= $img['image_path'] ?? 'Chemin invalide' ?> (ID: <?= $img['id'] ?? 'N/A' ?>) - 
                        Existe: <?= $imgExists ? 'Oui' : 'Non' ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucune image supplémentaire</p>
        <?php endif; ?>
        
        <p><strong>Requête d'images:</strong> SELECT * FROM images_products WHERE produit_id = <?= $product_id ?> ORDER BY id ASC</p>
        
        <p><strong>Document Root:</strong> <?= $_SERVER['DOCUMENT_ROOT'] ?></p>
        <p><strong>Script Filename:</strong> <?= $_SERVER['SCRIPT_FILENAME'] ?></p>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Script produit_details chargé');
    
    // Éléments du DOM
    const decreaseBtn = document.getElementById('decrease-btn');
    const increaseBtn = document.getElementById('increase-btn');
    const addToCartBtn = document.getElementById('add-to-cart-button');
    const messageDiv = document.getElementById('cart-message');
    
    // Vérifier que tous les éléments existent
    if (decreaseBtn && increaseBtn && addToCartBtn) {
        console.log('Tous les boutons ont été trouvés');
        
        // ID du produit
        const productId = addToCartBtn.getAttribute('data-product-id');
        const quantityInput = document.getElementById('quantity_' + productId);
        
        // Fonction pour mettre à jour le bouton d'ajout au panier
        function updateAddToCartButton(loading = false) {
            if (loading) {
                addToCartBtn.disabled = true;
                addToCartBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Ajout en cours...';
            } else {
                addToCartBtn.disabled = false;
                addToCartBtn.innerHTML = '<i class="fas fa-shopping-cart me-2"></i> Ajouter au panier';
            }
        }
        
        // Fonction pour afficher un message
        function showMessage(message, type = 'success') {
            messageDiv.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            `;
        }
        
        // Bouton diminuer quantité
        decreaseBtn.onclick = function() {
            const current = parseInt(quantityInput.value);
            if (current > 1) {
                quantityInput.value = current - 1;
                quantityInput.dispatchEvent(new Event('change'));
            }
        };
        
        // Bouton augmenter quantité
        increaseBtn.onclick = function() {
            const current = parseInt(quantityInput.value);
            const max = parseInt(quantityInput.getAttribute('max'));
            if (current < max) {
                quantityInput.value = current + 1;
                quantityInput.dispatchEvent(new Event('change'));
            }
        };
        
        // Validation de l'input quantité
        quantityInput.addEventListener('change', function() {
            let value = parseInt(this.value);
            const max = parseInt(this.getAttribute('max'));
            const min = parseInt(this.getAttribute('min'));
            
            if (isNaN(value) || value < min) {
                value = min;
            } else if (value > max) {
                value = max;
            }
            
            this.value = value;
        });
        
        // Bouton ajouter au panier
        addToCartBtn.onclick = function() {
            // Désactiver le bouton pendant le processus
            updateAddToCartButton(true);
            
            // Récupérer la quantité
            const quantity = parseInt(quantityInput.value) || 1;
            
            // Créer l'objet FormData
            const formData = new FormData();
            formData.append('product_id', productId);
            formData.append('quantity', quantity);
            
            // Envoyer la requête
            fetch('admin/src/php/ajax/add_to_cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`Erreur HTTP: ${response.status}`);
                }
                return response.text();
            })
            .then(text => {
                console.log('Réponse brute:', text);
                
                try {
                    const data = JSON.parse(text);
                    
                    if (data.success) {
                        showMessage('Produit ajouté au panier!');
                        
                        // Redirection après un court délai
                        setTimeout(function() {
                            window.location.href = 'index_.php?page=panier';
                        }, 1000);
                    } else {
                        showMessage(data.message, 'danger');
                    }
                } catch (e) {
                    console.error('Erreur de parsing JSON:', e);
                    showMessage('Erreur lors de l\'ajout au panier. Vérifiez la console pour plus de détails.', 'danger');
                }
            })
            .catch(error => {
                console.error('Erreur fetch:', error);
                showMessage('Erreur réseau: ' + error.message, 'danger');
            })
            .finally(() => {
                updateAddToCartButton(false);
            });
        };
    } else {
        console.error('Certains éléments n\'ont pas été trouvés dans le DOM');
    }
    
    // Gestion des miniatures
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const target = this.getAttribute('data-bs-target');
            const slideTo = this.getAttribute('data-bs-slide-to');
            const carousel = bootstrap.Carousel.getInstance(document.querySelector(target));
            if (carousel) {
                carousel.to(slideTo);
            }
        });
    });
    
    // Notification de stock
    const notifyButtons = document.querySelectorAll('.notify-stock');
    notifyButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Vous serez notifié quand ce produit sera de nouveau disponible.');
            // TODO: Implémenter la fonctionnalité de notification
        });
    });
});
</script> 