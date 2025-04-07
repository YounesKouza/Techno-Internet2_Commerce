<?php
/**
 * Page catalogue - Affichage de tous les produits avec filtres
 */

// Titre de la page
$titre_page = 'Catalogue';
$js_specifique = 'catalogue';

// Récupération des paramètres de filtre
$category_id = isset($_GET['category']) ? intval($_GET['category']) : null;
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$sort = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'name_asc';
$page_num = isset($_GET['page_num']) ? intval($_GET['page_num']) : 1;
$per_page = 12;

// Construction de la requête SQL de base
$sql = "SELECT p.*, c.nom as category_name 
        FROM products p 
        JOIN categories c ON p.categorie_id = c.id 
        WHERE p.actif = true";

$params = [];

// Ajout des filtres à la requête
if ($category_id) {
    $sql .= " AND p.categorie_id = ?";
    $params[] = $category_id;
}

if (!empty($search)) {
    $sql .= " AND (p.titre ILIKE ? OR p.description ILIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
}

// Définition de l'ordre de tri
$order_by = "";
switch ($sort) {
    case 'price_asc':
        $order_by = "ORDER BY p.prix ASC";
        break;
    case 'price_desc':
        $order_by = "ORDER BY p.prix DESC";
        break;
    case 'name_desc':
        $order_by = "ORDER BY p.titre DESC";
        break;
    case 'newest':
        $order_by = "ORDER BY p.date_creation DESC";
        break;
    default:
        $order_by = "ORDER BY p.titre ASC";
}

// Calcul pour la pagination
$pdo = getPDO();
$count_sql = "SELECT COUNT(*) as total FROM products p JOIN categories c ON p.categorie_id = c.id WHERE p.actif = true";

// Ajout des mêmes filtres à la requête de comptage
if ($category_id) {
    $count_sql .= " AND p.categorie_id = ?";
}

if (!empty($search)) {
    $count_sql .= " AND (p.titre ILIKE ? OR p.description ILIKE ?)";
}

$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();

$total_pages = ceil($total_products / $per_page);
$page_num = max(1, min($page_num, $total_pages));
$offset = ($page_num - 1) * $per_page;

// Requête principale avec l'ordre de tri et la pagination
$sql .= " " . $order_by . " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = $offset;

// Exécution de la requête principale
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Récupération des catégories pour le filtre
$categories_stmt = $pdo->query("SELECT id, nom FROM categories ORDER BY nom");
$categories = $categories_stmt->fetchAll();
?>

<div class="container py-4">
    <!-- En-tête de la page -->
    <div class="catalogue-header">
        <h1 class="catalogue-title">Notre Collection de Meubles</h1>
        <p class="catalogue-description">Explorez notre gamme complète de meubles pour trouver les pièces parfaites pour votre maison.</p>
        <form action="" method="get" class="d-flex justify-content-center mt-4">
            <input type="hidden" name="page" value="catalogue">
            <input type="text" name="search" value="<?= $search ?>" class="form-control w-50 me-2" placeholder="Rechercher par nom ou description...">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    <!-- Filtres et tri -->
    <div class="row mb-4 filters-section">
        <div class="col-md-6 mb-3 mb-md-0">
            <label for="category-filter" class="filter-label">Filtrer par catégorie :</label>
            <select name="category" id="category-filter" class="form-select">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>" <?= $category_id == $category['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($category['nom']) ?> (<?= /* TODO: Afficher le compte par catégorie */ '?' ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label for="sort-products" class="filter-label">Trier par :</label>
            <select name="sort" id="sort-products" class="form-select">
                <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Nom (A-Z)</option>
                <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Nom (Z-A)</option>
                <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Prix croissant</option>
                <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Prix décroissant</option>
                <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Plus récents</option>
            </select>
        </div>
    </div>

    <!-- Affichage du nombre de résultats -->
    <div class="mb-3 text-muted">
        Affichage de <?= count($products) ?> sur <?= $total_products ?> produits.
    </div>

    <!-- Liste des produits -->
    <div class="row">
        <?php if (empty($products)): ?>
            <div class="col-12 text-center py-5 empty-state">
                <i class="fas fa-box-open empty-state-icon"></i>
                <p class="empty-state-text">Aucun produit ne correspond à votre sélection.</p>
                <a href="index_.php?page=catalogue" class="btn btn-outline-primary">Voir tous les produits</a>
            </div>
        <?php else: ?>
            <?php foreach ($products as $product): ?>
                <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
                    <div class="card h-100 product-card">
                        <a href="index_.php?page=produit_details&id=<?= $product['id'] ?>" class="product-img-link">
                            <div class="product-img">
                                <img src="<?= htmlspecialchars($product['image_principale'] ?? 'admin/public/img/products/default.jpg') ?>" 
                                     alt="<?= htmlspecialchars($product['titre']) ?>">
                            </div>
                        </a>
                        <div class="card-body d-flex flex-column">
                            <span class="category-badge mb-2"><?= htmlspecialchars($product['category_name']) ?></span>
                            <h5 class="card-title flex-grow-1">
                                <a href="index_.php?page=produit_details&id=<?= $product['id'] ?>" class="text-decoration-none text-dark">
                                    <?= htmlspecialchars($product['titre']) ?>
                                </a>
                            </h5>
                            <p class="product-price mb-2"><?= number_format($product['prix'], 2, ',', ' ') ?> €</p>
                            <div class="mt-auto">
                                <button class="btn btn-sm btn-primary w-100 add-to-cart" data-product-id="<?= $product['id'] ?>">
                                    <i class="fas fa-cart-plus"></i> Ajouter au panier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <nav aria-label="Navigation des pages" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item <?= ($page_num <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=catalogue&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&sort=<?= $sort ?>&page_num=<?= $page_num - 1 ?>" aria-label="Précédent">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php 
                // Logique pour afficher les liens de pagination (simplifiée)
                $start_page = max(1, $page_num - 2);
                $end_page = min($total_pages, $page_num + 2);
                
                if ($start_page > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=catalogue&search='.urlencode($search).'&category='.$category_id.'&sort='.$sort.'&page_num=1">1</a></li>';
                    if ($start_page > 2) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                }
                
                for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?= ($i == $page_num) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=catalogue&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&sort=<?= $sort ?>&page_num=<?= $i ?>"><?= $i ?></a>
                    </li>
                <?php endfor; 
                
                if ($end_page < $total_pages) {
                    if ($end_page < $total_pages - 1) {
                        echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=catalogue&search='.urlencode($search).'&category='.$category_id.'&sort='.$sort.'&page_num='.$total_pages.'">'.$total_pages.'</a></li>';
                }
                ?>
                
                <li class="page-item <?= ($page_num >= $total_pages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=catalogue&search=<?= urlencode($search) ?>&category=<?= $category_id ?>&sort=<?= $sort ?>&page_num=<?= $page_num + 1 ?>" aria-label="Suivant">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    function updateURL() {
        const category = document.getElementById('category-filter').value;
        const sort = document.getElementById('sort-products').value;
        const search = document.querySelector('input[name="search"]').value;
        
        let url = 'index_.php?page=catalogue';
        if (category) url += '&category=' + category;
        if (sort) url += '&sort=' + sort;
        if (search) url += '&search=' + encodeURIComponent(search);
        
        window.location.href = url;
    }

    document.getElementById('category-filter').addEventListener('change', updateURL);
    document.getElementById('sort-products').addEventListener('change', updateURL);
    
    // Gestion de l'ajout au panier (inchangé)
    const addToCartButtons = document.querySelectorAll('.add-to-cart');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-product-id');
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin/src/php/ajax/add_to_cart.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            alert('Produit ajouté au panier !');
                            // Mettre à jour le nombre d'articles dans le panier (exemple simple)
                            const cartBadge = document.querySelector('.fa-shopping-cart + .badge');
                            if (cartBadge) {
                                cartBadge.textContent = response.cart_count;
                                cartBadge.classList.remove('d-none');
                            } else {
                                // Créer le badge si il n'existe pas
                                const cartLink = document.querySelector('a[href*="page=panier"]');
                                if(cartLink) {
                                    const newBadge = document.createElement('span');
                                    newBadge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                                    newBadge.textContent = response.cart_count;
                                    cartLink.appendChild(newBadge);
                                }
                            }
                        } else {
                            alert('Erreur: ' + (response.message || 'Impossible d\'ajouter au panier'));
                        }
                    } catch (e) {
                        console.error('Erreur JSON:', e);
                        alert('Une erreur technique est survenue.');
                    }
                } else {
                     alert('Erreur serveur: ' + xhr.status);
                }
            };
             xhr.onerror = function() {
                alert('Erreur réseau lors de l\'ajout au panier.');
            };
            xhr.send('product_id=' + productId + '&quantity=1');
        });
    });
});
</script>