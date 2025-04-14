<?php
/**
 * Page d'ajout d'un nouveau meuble (produit)
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
require_once '../src/php/utils/check_permissions.php';
require_once '../src/php/utils/category_helper.php';
require_once '../src/php/utils/sidebar.php';
require_once '../src/php/utils/all_includes.php';

// Vérification des permissions des dossiers d'upload
$upload_permissions = checkDirectoryPermissions('uploads/products');
$permission_error = !$upload_permissions['success'] ? $upload_permissions['message'] : "";

// Initialisation des variables
$pdo = getPDO();
$error = "";
$success = "";

// Initialisation des objets DAO
$productDAO = new ProductDAO($pdo);
$categoryDAO = new CategoryDAO($pdo);
$productImageDAO = new ProductImageDAO($pdo);

// Récupération des catégories pour le formulaire
try {
    $categories = $categoryDAO->findAll();
} catch (Exception $e) {
    $error = "Erreur lors de la récupération des catégories : " . $e->getMessage();
    $categories = [];
}

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $titre = $_POST['titre'] ?? '';
    $description = $_POST['description'] ?? '';
    $prix = floatval($_POST['prix'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $categorie_id = !empty($_POST['categorie']) ? intval($_POST['categorie']) : null;
    $actif = isset($_POST['actif']) ? intval($_POST['actif']) : 1;
    
    // Validation des données
    if (empty($titre)) {
        $error = "Le titre du produit est obligatoire";
    } else if ($prix <= 0) {
        $error = "Le prix doit être supérieur à 0";
    } else if ($stock < 0) {
        $error = "Le stock ne peut pas être négatif";
    } else {
        try {
            // Gestion de l'image principale
            $image_principale = null;
            if (!empty($_FILES['image_principale']['name'])) {
                $file = $_FILES['image_principale'];
                
                // Vérification du type de fichier
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (in_array($file['type'], $allowed_types)) {
                    // Déterminer le dossier selon la catégorie
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
                            $image_principale = 'admin/public/images/' . $folder_name . '/' . $new_filename;
                        } else {
                            $error = "Échec du téléchargement de l'image principale. Code d'erreur: " . $file['error'];
                        }
                    }
                } else {
                    $error = "Le type de fichier de l'image principale n'est pas autorisé (JPEG, PNG, GIF ou WEBP uniquement). Type détecté: " . $file['type'];
                }
            } else {
                $error = "L'image principale est obligatoire";
            }
            
            if (empty($error)) {
                // Création du produit via le DAO
                $productData = [
                    'titre' => $titre,
                    'description' => $description,
                    'prix' => $prix,
                    'stock' => $stock,
                    'categorie_id' => $categorie_id,
                    'image_principale' => $image_principale,
                    'actif' => $actif
                ];
                
                $product_id = $productDAO->create($productData);
                
                if (!$product_id) {
                    throw new Exception("Erreur lors de la création du produit");
                }
                
                // Traitement des images supplémentaires
                if (!empty($_FILES['images']['name'][0])) {
                    $files = $_FILES['images'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $order = 1;
                    
                    // Déterminer le dossier selon la catégorie (déjà défini pour l'image principale)
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
                                    $image_url = 'admin/public/images/' . $folder_name . '/' . $new_filename;
                                    
                                    // Ajout de l'image via le DAO
                                    $imageData = [
                                        'produit_id' => $product_id,
                                        'url_image' => $image_url,
                                        'ordre' => $order
                                    ];
                                    $productImageDAO->create($imageData);
                                    $order++;
                                } else {
                                    // Enregistrer l'erreur mais continuer avec les autres images
                                    $error_log = "Échec du téléchargement de l'image supplémentaire " . ($i+1) . ". Code d'erreur: " . $files['error'][$i];
                                    error_log($error_log);
                                }
                            }
                        }
                    }
                }
                
                $success = "Le produit a été ajouté avec succès !";
                
                // Réinitialisation du formulaire
                $titre = $description = '';
                $prix = $stock = 0;
                $categorie_id = null;
                $actif = 1;
            }
        } catch (Exception $e) {
            $error = "Erreur lors de l'ajout du produit : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un meuble - Administration</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
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
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php generate_sidebar('products'); ?>
            
            <!-- Contenu principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Alertes de succès ou d'erreur -->
                <?php if (!empty($permission_error)): ?>
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                    <strong>Attention aux permissions :</strong> <?php echo $permission_error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    <?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Ajouter un nouveau meuble</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="/Exos/Techno-internet2_commerce/admin/pages/gestion_meubles.php" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-list me-1"></i> Retour à la liste
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Formulaire d'ajout de meuble -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0">Informations du produit</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="post" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Titre -->
                                <div class="col-md-8 mb-3">
                                    <label for="titre" class="form-label">Titre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="titre" name="titre" value="<?= htmlspecialchars($titre ?? '') ?>" required>
                                </div>
                                
                                <!-- Prix -->
                                <div class="col-md-4 mb-3">
                                    <label for="prix" class="form-label">Prix (€) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="prix" name="prix" step="0.01" min="0" value="<?= $prix ?? 0 ?>" required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($description ?? '') ?></textarea>
                                </div>
                                
                                <!-- Catégorie -->
                                <div class="col-md-4 mb-3">
                                    <label for="categorie" class="form-label">Catégorie</label>
                                    <select class="form-select" id="categorie" name="categorie">
                                        <option value="">-- Sélectionner une catégorie --</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category->id ?>" <?= (isset($categorie_id) && $categorie_id == $category->id) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category->nom) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Stock -->
                                <div class="col-md-4 mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?= $stock ?? 0 ?>">
                                </div>
                                
                                <!-- Statut -->
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Statut</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="actif" name="actif" value="1" <?= (!isset($actif) || $actif) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="actif">
                                            Actif (visible sur le site)
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- Image principale -->
                                <div class="col-md-12 mb-4">
                                    <label for="image_principale" class="form-label">Image principale <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="image_principale" name="image_principale" accept="image/*" required>
                                    <div id="image_preview_container" class="mt-2 d-none">
                                        <img id="image_preview" src="#" alt="Aperçu de l'image" class="img-thumbnail" style="max-height: 150px;">
                                    </div>
                                    <div class="form-text">L'image principale apparaîtra comme image de couverture du produit. Formats acceptés : JPG, PNG, GIF, WebP.</div>
                                </div>
                                
                                <!-- Images supplémentaires -->
                                <div class="col-md-12 mb-4">
                                    <label for="images" class="form-label">Images supplémentaires (facultatif)</label>
                                    <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                                    <div id="additional_images_preview" class="mt-2 row g-2"></div>
                                    <div class="form-text">Vous pouvez sélectionner plusieurs images. Elles seront affichées dans la galerie du produit.</div>
                                </div>
                                
                                <!-- Boutons d'action -->
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="reset" class="btn btn-outline-secondary me-2">Réinitialiser</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer le produit
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Script pour l'aperçu des images -->
    <script>
        // Aperçu de l'image principale
        document.getElementById('image_principale').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.getElementById('image_preview');
                    preview.src = e.target.result;
                    document.getElementById('image_preview_container').classList.remove('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
        
        // Aperçu des images supplémentaires
        document.getElementById('images').addEventListener('change', function() {
            const previewContainer = document.getElementById('additional_images_preview');
            previewContainer.innerHTML = '';
            
            for (let i = 0; i < this.files.length; i++) {
                const file = this.files[i];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const col = document.createElement('div');
                        col.className = 'col-auto';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.className = 'img-thumbnail';
                        img.style.height = '100px';
                        img.alt = 'Aperçu image ' + (i + 1);
                        
                        col.appendChild(img);
                        previewContainer.appendChild(col);
                    }
                    reader.readAsDataURL(file);
                }
            }
        });
    </script>
</body>
</html>

