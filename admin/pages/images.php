<?php
/**
 * Page d'administration des images de produits
 */

// Inclusion des fichiers nécessaires
require_once '../src/php/utils/session.php';
require_once '../src/php/utils/connexion.php';
require_once '../src/php/utils/image_util.php';

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != true) {
    // Rediriger vers la page de connexion
    header('Location: login.php');
    exit;
}

// Traitement des actions
$message = '';
$messageType = '';

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'scan_images') {
        // Lancer le scan des images
        ob_start();
        processImages();
        $result = ob_get_clean();
        
        $message = "Scan des images terminé avec succès!";
        $messageType = 'success';
    } elseif ($_POST['action'] === 'link_product' && isset($_POST['image_id']) && isset($_POST['product_id'])) {
        // Lier l'image à un produit
        $imageId = $_POST['image_id'];
        $productId = $_POST['product_id'];
        $isMain = isset($_POST['is_main']) ? 1 : 0;
        
        try {
            $pdo = getPDO();
            
            // Mettre à jour l'image
            $stmt = $pdo->prepare("
                UPDATE product_images 
                SET product_id = ?, is_main = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$productId, $isMain, $imageId]);
            
            // Si c'est l'image principale, mettre à jour le produit
            if ($isMain) {
                // D'abord, réinitialiser toutes les autres images comme non-principales
                $stmt = $pdo->prepare("
                    UPDATE product_images 
                    SET is_main = FALSE 
                    WHERE product_id = ? AND id != ?
                ");
                $stmt->execute([$productId, $imageId]);
                
                // Récupérer l'URL de l'image
                $stmt = $pdo->prepare("SELECT url FROM product_images WHERE id = ?");
                $stmt->execute([$imageId]);
                $imageUrl = $stmt->fetchColumn();
                
                // Mettre à jour l'image principale du produit
                $stmt = $pdo->prepare("
                    UPDATE products 
                    SET image_principale = ? 
                    WHERE id = ?
                ");
                $stmt->execute([$imageUrl, $productId]);
            }
            
            $message = "Image liée au produit avec succès!";
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = "Erreur lors de la liaison de l'image: " . $e->getMessage();
            $messageType = 'danger';
        }
    } elseif ($_POST['action'] === 'delete_image' && isset($_POST['image_id'])) {
        // Supprimer une image
        $imageId = $_POST['image_id'];
        
        try {
            $pdo = getPDO();
            
            // Récupérer les infos de l'image avant suppression
            $stmt = $pdo->prepare("
                SELECT url, product_id, is_main 
                FROM product_images 
                WHERE id = ?
            ");
            $stmt->execute([$imageId]);
            $image = $stmt->fetch();
            
            if ($image) {
                // Si c'était une image principale, mettre à jour le produit
                if ($image['is_main'] && $image['product_id']) {
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET image_principale = NULL 
                        WHERE id = ?
                    ");
                    $stmt->execute([$image['product_id']]);
                }
                
                // Supprimer l'image de la base de données
                $stmt = $pdo->prepare("DELETE FROM product_images WHERE id = ?");
                $stmt->execute([$imageId]);
                
                $message = "Image supprimée avec succès!";
                $messageType = 'success';
            } else {
                $message = "Image non trouvée!";
                $messageType = 'warning';
            }
        } catch (PDOException $e) {
            $message = "Erreur lors de la suppression de l'image: " . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Récupérer la liste des images
$images = [];
$products = [];

try {
    $pdo = getPDO();
    
    // Récupérer les images
    $stmt = $pdo->prepare("
        SELECT i.*, p.nom as product_name 
        FROM product_images i 
        LEFT JOIN products p ON i.product_id = p.id 
        ORDER BY i.category, i.name
    ");
    $stmt->execute();
    $images = $stmt->fetchAll();
    
    // Récupérer les produits pour le formulaire de liaison
    $stmt = $pdo->prepare("
        SELECT id, nom, categorie 
        FROM products 
        ORDER BY categorie, nom
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $message = "Erreur lors de la récupération des données: " . $e->getMessage();
    $messageType = 'danger';
}

// Nombre d'images par catégorie
$imagesByCategory = [];
foreach ($images as $image) {
    $category = $image['category'] ?: 'Non classé';
    if (!isset($imagesByCategory[$category])) {
        $imagesByCategory[$category] = 0;
    }
    $imagesByCategory[$category]++;
}

// Nombre d'images liées/non liées
$linkedImages = array_filter($images, function($img) { return $img['product_id'] !== null; });
$unlinkedImages = array_filter($images, function($img) { return $img['product_id'] === null; });

// Charger la vue
include('../templates/header.php');
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-12">
            <h1 class="h2 mb-4">Gestion des images de produits</h1>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">Statistiques</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Images totales</h5>
                                    <p class="card-text display-4"><?php echo count($images); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Images liées</h5>
                                    <p class="card-text display-4"><?php echo count($linkedImages); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Images non liées</h5>
                                    <p class="card-text display-4"><?php echo count($unlinkedImages); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h5>Images par catégorie</h5>
                    <div class="row">
                        <?php foreach ($imagesByCategory as $category => $count): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($category); ?></h6>
                                    <p class="card-text"><?php echo $count; ?> images</p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <form method="post" action="">
                        <input type="hidden" name="action" value="scan_images">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Scanner les dossiers d'images
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Liste des images</h5>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="show-all">Toutes</button>
                        <button type="button" class="btn btn-sm btn-outline-success" id="show-linked">Liées</button>
                        <button type="button" class="btn btn-sm btn-outline-warning" id="show-unlinked">Non liées</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover" id="images-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Aperçu</th>
                                    <th>Nom</th>
                                    <th>Catégorie</th>
                                    <th>Produit lié</th>
                                    <th>Est principale</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($images as $image): ?>
                                <tr class="<?php echo $image['product_id'] ? 'linked' : 'unlinked'; ?>">
                                    <td><?php echo $image['id']; ?></td>
                                    <td>
                                        <img src="<?php echo $image['url']; ?>" alt="<?php echo htmlspecialchars($image['name']); ?>" 
                                            class="img-thumbnail" style="max-width: 100px; max-height: 100px;">
                                    </td>
                                    <td><?php echo htmlspecialchars($image['name']); ?></td>
                                    <td><?php echo htmlspecialchars($image['category'] ?: 'Non classé'); ?></td>
                                    <td><?php echo $image['product_id'] ? htmlspecialchars($image['product_name']) : 'Non lié'; ?></td>
                                    <td>
                                        <?php if ($image['is_main']): ?>
                                            <span class="badge bg-success">Principale</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Non</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" data-bs-target="#linkModal"
                                                data-id="<?php echo $image['id']; ?>"
                                                data-current-product="<?php echo $image['product_id']; ?>">
                                            <i class="fas fa-link"></i> Lier
                                        </button>
                                        <form method="post" action="" class="d-inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette image?');">
                                            <input type="hidden" name="action" value="delete_image">
                                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i> Supprimer
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour lier une image à un produit -->
<div class="modal fade" id="linkModal" tabindex="-1" aria-labelledby="linkModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="linkModalLabel">Lier l'image à un produit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="link_product">
                    <input type="hidden" name="image_id" id="modal-image-id" value="">
                    
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Produit</label>
                        <select class="form-select" id="product_id" name="product_id" required>
                            <option value="">Sélectionner un produit</option>
                            <?php foreach ($products as $product): ?>
                            <option value="<?php echo $product['id']; ?>">
                                <?php echo htmlspecialchars($product['categorie'] . ' - ' . $product['nom']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="is_main" name="is_main">
                        <label class="form-check-label" for="is_main">
                            Définir comme image principale
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtres pour afficher les images liées/non liées
    document.getElementById('show-all').addEventListener('click', function() {
        document.querySelectorAll('#images-table tbody tr').forEach(row => {
            row.style.display = '';
        });
    });
    
    document.getElementById('show-linked').addEventListener('click', function() {
        document.querySelectorAll('#images-table tbody tr').forEach(row => {
            if (row.classList.contains('linked')) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    document.getElementById('show-unlinked').addEventListener('click', function() {
        document.querySelectorAll('#images-table tbody tr').forEach(row => {
            if (row.classList.contains('unlinked')) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Initialisation du modal pour lier des images
    document.querySelectorAll('[data-bs-target="#linkModal"]').forEach(button => {
        button.addEventListener('click', function() {
            const imageId = this.getAttribute('data-id');
            const currentProductId = this.getAttribute('data-current-product');
            
            document.getElementById('modal-image-id').value = imageId;
            
            const productSelect = document.getElementById('product_id');
            if (currentProductId) {
                productSelect.value = currentProductId;
            } else {
                productSelect.selectedIndex = 0;
            }
        });
    });
});
</script>

<?php include('../templates/footer.php'); ?> 