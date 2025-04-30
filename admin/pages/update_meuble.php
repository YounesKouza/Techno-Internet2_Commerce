<?php
/**
 * Page de mise à jour d'un meuble
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
require_once '../src/php/utils/category_helper.php';
require_once '../src/php/utils/sidebar.php';
require_once '../src/php/utils/all_includes.php';

// Initialisation des variables
$pdo = getPDO();
$error = "";
$success = "";

// Initialisation des objets DAO
$productDAO = new ProductDAO($pdo);
$productImageDAO = new ProductImageDAO($pdo);
$categoryDAO = new CategoryDAO($pdo);

// Vérification si l'ID est valide
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: gestion_meubles.php?error=invalid_id');
    exit;
}

// Récupération des catégories pour le formulaire
$categories = $categoryDAO->findAll();

// Récupération des informations du produit
$product = $productDAO->findById($id);

if (!$product) {
    header('Location: gestion_meubles.php?error=product_not_found');
    exit;
}

// Récupération des images associées au produit
$images = $productImageDAO->findByProductId($id);

// Traitement du formulaire de mise à jour du produit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $prix = floatval($_POST['prix'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $categorie_id = !empty($_POST['categorie_id']) ? intval($_POST['categorie_id']) : null;
    $actif = isset($_POST['actif']) ? 1 : 0;
    
    // Validation des données
    if (empty($titre)) {
        $error = "Le titre du produit est obligatoire";
    } else if ($prix <= 0) {
        $error = "Le prix doit être supérieur à 0";
    } else if ($stock < 0) {
        $error = "Le stock ne peut pas être négatif";
    } else {
        try {
            // Création d'un tableau de données pour la mise à jour
            $productData = [
                'titre' => $titre,
                'description' => $description,
                'prix' => $prix,
                'stock' => $stock,
                'categorie_id' => $categorie_id,
                'actif' => $actif
            ];
            
            // Mise à jour du produit via le DAO
            $updateResult = $productDAO->update($id, $productData);
            
            if (!$updateResult) {
                throw new Exception("Erreur lors de la mise à jour du produit");
            }
            
            // Gestion de l'image principale si une nouvelle image est téléchargée
            if (!empty($_FILES['image_principale']['name'])) {
                $file = $_FILES['image_principale'];
                
                // Vérification du type de fichier
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($file['type'], $allowed_types)) {
                    // Récupération du nom de la catégorie
                    $category = $categoryDAO->findById($categorie_id);
                    $category_name = $category ? $category->nom : '';
                    
                    // Utiliser la fonction utilitaire pour déterminer le dossier
                    $folder_name = getCategoryFolder($category_name);
                    
                    // Création du dossier s'il n'existe pas
                    $folder_result = ensureUploadFolder($folder_name);
                    if (!$folder_result['success']) {
                        $error = $folder_result['message'];
                    } else {
                        $upload_dir = $folder_result['path'];
                        
                        // Génération d'un nom de fichier unique
                        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $new_filename = generateUniqueImageName($extension);
                        $destination = $upload_dir . $new_filename;
                        
                        // Déplacement du fichier temporaire vers le dossier de destination
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            // Suppression de l'ancienne image si elle existe
                            if (!empty($product->image_principale)) {
                                $old_image_path = $_SERVER['DOCUMENT_ROOT'] . '/Exos/Techno-internet2_commerce/' . $product->image_principale;
                                if (file_exists($old_image_path)) {
                                    unlink($old_image_path);
                                }
                            }
                            
                            $image_principale = 'admin/public/images/' . $folder_name . '/' . $new_filename;
                            
                            // Mise à jour de l'image principale dans la base de données via le DAO
                            $productDAO->update($id, ['image_principale' => $image_principale]);
                        } else {
                            $error = "Échec du téléchargement de l'image principale";
                        }
                    }
                } else {
                    $error = "Le type de fichier de l'image principale n'est pas autorisé (JPEG, PNG, GIF ou WEBP uniquement)";
                }
            }
            
            // Traitement des images supplémentaires
            if (!empty($_FILES['images']['name'][0])) {
                // Récupération de l'ordre maximum actuel
                $max_order = 0;
                foreach ($images as $img) {
                    if ($img->ordre > $max_order) {
                        $max_order = $img->ordre;
                    }
                }
                
                $files = $_FILES['images'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                
                // Récupération du nom de la catégorie
                $category = $categoryDAO->findById($categorie_id);
                $category_name = $category ? $category->nom : '';
                
                // Utiliser la fonction utilitaire pour déterminer le dossier
                $folder_name = getCategoryFolder($category_name);
                
                // Création du dossier s'il n'existe pas
                $folder_result = ensureUploadFolder($folder_name);
                if (!$folder_result['success']) {
                    $error = $folder_result['message'];
                } else {
                    $upload_dir = $folder_result['path'];
                    
                    for ($i = 0; $i < count($files['name']); $i++) {
                        if (!empty($files['name'][$i]) && in_array($files['type'][$i], $allowed_types)) {
                            // Génération d'un nom de fichier unique
                            $extension = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                            $new_filename = generateUniqueImageName($extension, $i);
                            $destination = $upload_dir . $new_filename;
                            
                            // Déplacement du fichier vers le dossier de destination
                            if (move_uploaded_file($files['tmp_name'][$i], $destination)) {
                                $max_order++;
                                $image_url = 'admin/public/images/' . $folder_name . '/' . $new_filename;
                                
                                // Ajout de l'image dans la table images_products via le DAO
                                $imageData = [
                                    'produit_id' => $id,
                                    'url_image' => $image_url,
                                    'ordre' => $max_order
                                ];
                                $productImageDAO->create($imageData);
                            }
                        }
                    }
                }
            }
            
            // Traitement des suppressions d'images
            if (isset($_POST['delete_images']) && is_array($_POST['delete_images'])) {
                foreach ($_POST['delete_images'] as $image_id) {
                    $image_id = intval($image_id);
                    
                    // Récupération de l'image à supprimer
                    $image = $productImageDAO->findById($image_id);
                    
                    if ($image && $image->produit_id == $id) {
                        // Suppression du fichier physique
                        if (!empty($image->url_image)) {
                            $file_path = $_SERVER['DOCUMENT_ROOT'] . '/Exos/Techno-internet2_commerce/' . $image->url_image;
                            if (file_exists($file_path)) {
                                unlink($file_path);
                            }
                        }
                        
                        // Suppression de l'image de la base de données via le DAO
                        $productImageDAO->delete($image_id);
                    }
                }
            }
            
            // Mise à jour de l'ordre des images
            if (isset($_POST['image_order']) && is_array($_POST['image_order'])) {
                foreach ($_POST['image_order'] as $image_id => $order) {
                    $productImageDAO->update(intval($image_id), ['ordre' => intval($order)]);
                }
            }
            
            $success = "Le produit a bien été mis à jour !";
            
            // Rechargement des informations du produit après mise à jour
            $product = $productDAO->findById($id);
            
            // Rechargement des images
            $images = $productImageDAO->findByProductId($id);
            
        } catch (Exception $e) {
            $error = "Erreur lors de la mise à jour du produit : " . $e->getMessage();
        }
    }
}

// Conversion des données du produit pour l'affichage
$product_data = [];
if ($product) {
    $product_data = [
        'id' => $product->id,
        'titre' => $product->titre,
        'description' => $product->description,
        'prix' => $product->prix,
        'stock' => $product->stock,
        'categorie_id' => $product->categorie_id,
        'image_principale' => $product->image_principale,
        'actif' => $product->actif
    ];
}

// Conversion des données des images pour l'affichage
$images_data = [];
foreach ($images as $image) {
    $images_data[] = [
        'id' => $image->id,
        'url_image' => $image->url_image,
        'ordre' => $image->ordre
    ];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un meuble | Administration Furniture</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/Exos/Techno-internet2_commerce/admin/public/css/style.css">
    
    <?php 
    // Ajouter la référence à la fonction add_body_class
    if (!function_exists('add_body_class')) {
        require_once __DIR__ . '/../src/php/utils/all_includes.php';
        }
    add_body_class(); // Ajout automatique de la classe admin-interface si nécessaire 
    ?>
</head>
<body class="admin-interface">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar / Menu latéral -->
            <?php generate_sidebar('products'); ?>
            
            <!-- Contenu principal -->
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Modifier un meuble</h1>
                    <div>
                        <a href="gestion_meubles.php" class="btn btn-secondary me-2">
                            <i class="fas fa-arrow-left me-1"></i> Retour
                        </a>
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
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Informations du produit</h5>
                    </div>
                    <div class="card-body">
                        <form action="update_meuble.php?id=<?= $id ?>" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="titre" name="titre" value="<?= htmlspecialchars($product_data['titre']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="5"><?= htmlspecialchars($product_data['description']) ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="prix" class="form-label">Prix (€) <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="prix" name="prix" step="0.01" min="0.01" value="<?= number_format($product_data['prix'], 2, '.', '') ?>" required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?= $product_data['stock'] ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="categorie_id" class="form-label">Catégorie</label>
                                        <select class="form-select" id="categorie_id" name="categorie_id">
                                            <option value="">Aucune catégorie</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category->id ?>" <?= ($product_data['categorie_id'] == $category->id) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($category->nom) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3 form-check">
                                        <input type="checkbox" class="form-check-input" id="actif" name="actif" <?= $product_data['actif'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="actif">Produit actif</label>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="image_principale" class="form-label">Image principale</label>
                                        <?php if (!empty($product_data['image_principale'])): ?>
                                            <div class="mb-2">
                                                <img src="../../<?= htmlspecialchars($product_data['image_principale']) ?>" alt="Image principale" class="img-fluid img-thumbnail preview-thumb">
                                            </div>
                                        <?php endif; ?>
                                        <input type="file" class="form-control" id="image_principale" name="image_principale" accept="image/*">
                                        <div class="form-text">
                                            Formats acceptés : JPEG, PNG, GIF, WEBP. Taille maximale : 5 Mo.
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Images supplémentaires</label>
                                        <input type="file" class="form-control" name="images[]" multiple accept="image/*">
                                        <div class="form-text">
                                            Vous pouvez sélectionner plusieurs images à la fois.
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($images_data)): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Images actuelles</label>
                                            <div class="row g-2">
                                                <?php foreach ($images_data as $image): ?>
                                                    <div class="col-6">
                                                        <div class="product-image-container">
                                                            <img src="../../<?= htmlspecialchars($image['url_image']) ?>" alt="Image produit" class="img-thumbnail">
                                                            <div class="image-actions">
                                                                <button type="button" class="btn-delete" onclick="toggleDeleteImage(<?= $image['id'] ?>)" title="Supprimer">
                                                                    <i class="fas fa-trash-alt text-danger"></i>
                                                                </button>
                                                            </div>
                                                            <input type="checkbox" name="delete_images[]" value="<?= $image['id'] ?>" id="delete_image_<?= $image['id'] ?>" class="d-none">
                                                            <input type="number" name="image_order[<?= $image['id'] ?>]" value="<?= $image['ordre'] ?>" class="form-control form-control-sm image-order" min="0" title="Ordre d'affichage">
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <div class="form-text">
                                                Cliquez sur l'icône de corbeille pour marquer une image à supprimer.
                                                Utilisez les champs numériques pour définir l'ordre d'affichage.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end mt-4">
                                <a href="gestion_meubles.php" class="btn btn-secondary me-2">Annuler</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Enregistrer les modifications
                                </button>
                            </div>
                        </form>
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
    
    <!-- JavaScript principal -->
    <script src="/Exos/Techno-internet2_commerce/admin/public/js/fonction.js"></script>
</body>
</html>
