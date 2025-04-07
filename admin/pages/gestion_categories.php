<?php
/**
 * Page de gestion des catégories
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

// Initialisation des variables
$pdo = getPDO();
$error = "";
$success = "";
$category_detail = null;
$edit_mode = false;

// Traitement des actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    switch ($action) {
        case 'add':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Récupération des données du formulaire
                $nom = $_POST['nom'] ?? '';
                $description = $_POST['description'] ?? '';
                
                // Validation des données
                if (empty($nom)) {
                    $error = "Le nom de la catégorie est obligatoire";
                } else {
                    try {
                        // Vérification si une catégorie avec ce nom existe déjà
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE nom = ?");
                        $stmt->execute([$nom]);
                        if ($stmt->fetchColumn() > 0) {
                            $error = "Une catégorie avec ce nom existe déjà";
                        } else {
                            // Ajout de la catégorie
                            $stmt = $pdo->prepare("INSERT INTO categories (nom, description) VALUES (?, ?)");
                            $stmt->execute([$nom, $description]);
                            
                            $success = "La catégorie a été ajoutée avec succès";
                            // Réinitialisation du formulaire
                            $nom = '';
                            $description = '';
                        }
                    } catch (PDOException $e) {
                        $error = "Erreur lors de l'ajout de la catégorie : " . $e->getMessage();
                    }
                }
            }
            break;
            
        case 'edit':
            $edit_mode = true;
            $category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($category_id <= 0) {
                $error = "ID de catégorie invalide";
            } else {
                try {
                    // Récupération de la catégorie
                    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
                    $stmt->execute([$category_id]);
                    $category_detail = $stmt->fetch();
                    
                    if (!$category_detail) {
                        $error = "La catégorie n'a pas été trouvée";
                    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        // Récupération des données du formulaire
                        $nom = $_POST['nom'] ?? '';
                        $description = $_POST['description'] ?? '';
                        
                        // Validation des données
                        if (empty($nom)) {
                            $error = "Le nom de la catégorie est obligatoire";
                        } else {
                            // Vérification si une catégorie avec ce nom existe déjà (hors celle qu'on édite)
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE nom = ? AND id != ?");
                            $stmt->execute([$nom, $category_id]);
                            if ($stmt->fetchColumn() > 0) {
                                $error = "Une catégorie avec ce nom existe déjà";
                            } else {
                                // Mise à jour de la catégorie
                                $stmt = $pdo->prepare("UPDATE categories SET nom = ?, description = ? WHERE id = ?");
                                $stmt->execute([$nom, $description, $category_id]);
                                
                                $success = "La catégorie a été mise à jour avec succès";
                                
                                // Récupération des données mises à jour
                                $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
                                $stmt->execute([$category_id]);
                                $category_detail = $stmt->fetch();
                            }
                        }
                    }
                } catch (PDOException $e) {
                    $error = "Erreur lors de la récupération ou la mise à jour de la catégorie : " . $e->getMessage();
                }
            }
            break;
            
        case 'delete':
            $category_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if ($category_id <= 0) {
                $error = "ID de catégorie invalide";
            } else {
                try {
                    // Vérification si des produits utilisent cette catégorie
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE categorie_id = ?");
                    $stmt->execute([$category_id]);
                    $product_count = $stmt->fetchColumn();
                    
                    if ($product_count > 0 && !isset($_GET['force'])) {
                        $error = "Cette catégorie est utilisée par $product_count produit(s). Utilisez la suppression forcée pour définir la catégorie à NULL pour ces produits et supprimer la catégorie.";
                    } else {
                        // Si suppression forcée, mettre à NULL la categorie_id des produits
                        if ($product_count > 0) {
                            $stmt = $pdo->prepare("UPDATE products SET categorie_id = NULL WHERE categorie_id = ?");
                            $stmt->execute([$category_id]);
                        }
                        
                        // Suppression de la catégorie
                        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
                        $stmt->execute([$category_id]);
                        
                        $success = "La catégorie a été supprimée avec succès" . ($product_count > 0 ? " ($product_count produits ont été mis à jour)" : "");
                    }
                } catch (PDOException $e) {
                    $error = "Erreur lors de la suppression de la catégorie : " . $e->getMessage();
                }
            }
            break;
    }
}

// Récupération de toutes les catégories
try {
    $categories = $pdo->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c
        LEFT JOIN products p ON c.id = p.categorie_id
        GROUP BY c.id
        ORDER BY c.nom ASC
    ")->fetchAll();
} catch (PDOException $e) {
    $error = "Erreur lors de la récupération des catégories : " . $e->getMessage();
    $categories = [];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des catégories | Administration Furniture</title>
    
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
            <?php generate_sidebar('categories'); ?>
            
            <!-- Contenu principal -->
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Gestion des catégories</h1>
                    <div>
                        <span class="me-3">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                        <a href="disconnect.php" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Formulaire d'ajout/modification -->
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">
                                    <?= $edit_mode ? 'Modifier la catégorie' : 'Ajouter une catégorie' ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form action="gestion_categories.php?action=<?= $edit_mode ? 'edit&id=' . $category_detail['id'] : 'add' ?>" method="post">
                                    <div class="mb-3">
                                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?= $edit_mode ? htmlspecialchars($category_detail['nom']) : '' ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?= $edit_mode ? htmlspecialchars($category_detail['description']) : '' ?></textarea>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <?php if ($edit_mode): ?>
                                            <a href="gestion_categories.php" class="btn btn-secondary">Annuler</a>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-1"></i> Mettre à jour
                                            </button>
                                        <?php else: ?>
                                            <button type="reset" class="btn btn-outline-secondary">Réinitialiser</button>
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-plus me-1"></i> Ajouter
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liste des catégories -->
                    <div class="col-md-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header bg-transparent">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Liste des catégories</h5>
                                    <span class="badge bg-primary"><?= count($categories) ?> catégorie(s)</span>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Nom</th>
                                                <th>Description</th>
                                                <th>Produits</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($categories)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-3">Aucune catégorie trouvée</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($categories as $category): ?>
                                                    <tr>
                                                        <td><?= $category['id'] ?></td>
                                                        <td><?= htmlspecialchars($category['nom']) ?></td>
                                                        <td><?= !empty($category['description']) ? htmlspecialchars(substr($category['description'], 0, 50) . (strlen($category['description']) > 50 ? '...' : '')) : '<em class="text-muted">Non renseignée</em>' ?></td>
                                                        <td>
                                                            <?php if ($category['product_count'] > 0): ?>
                                                                <a href="gestion_meubles.php?category=<?= $category['id'] ?>" class="text-decoration-none">
                                                                    <?= $category['product_count'] ?> produit(s)
                                                                </a>
                                                            <?php else: ?>
                                                                <span class="text-muted">0 produit</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="gestion_categories.php?action=edit&id=<?= $category['id'] ?>" class="btn btn-sm btn-info text-white" title="Modifier">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal<?= $category['id'] ?>" title="Supprimer">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            
                                                            <!-- Modal de confirmation de suppression -->
                                                            <div class="modal fade" id="deleteCategoryModal<?= $category['id'] ?>" tabindex="-1" aria-hidden="true">
                                                                <div class="modal-dialog">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header">
                                                                            <h5 class="modal-title">Confirmer la suppression</h5>
                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                        </div>
                                                                        <div class="modal-body text-start">
                                                                            <p>Êtes-vous sûr de vouloir supprimer la catégorie "<?= htmlspecialchars($category['nom']) ?>" ?</p>
                                                                            
                                                                            <?php if ($category['product_count'] > 0): ?>
                                                                                <div class="alert alert-warning">
                                                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                                                    Cette catégorie est utilisée par <?= $category['product_count'] ?> produit(s). La suppression définira la catégorie à NULL pour ces produits.
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            
                                                                            <p class="text-danger mb-0">Cette action est irréversible.</p>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                                                            <a href="gestion_categories.php?action=delete&id=<?= $category['id'] ?>&force=1" class="btn btn-danger">Supprimer</a>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
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
</body>
</html>
