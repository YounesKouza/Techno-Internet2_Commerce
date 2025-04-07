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

// Vérification des permissions des dossiers d'upload
$upload_permissions = checkDirectoryPermissions('uploads/products');
$permission_error = !$upload_permissions['success'] ? $upload_permissions['message'] : "";

// Fonction pour générer un nom de fichier unique
function generateUniqueFileName($extension) {
    return 'product_' . time() . '_' . uniqid() . '.' . $extension;
}

// Initialisation des variables
$pdo = getPDO();
$error = "";
$success = "";

// Récupération des catégories pour le formulaire
try {
    $categories = $pdo->query("SELECT id, nom FROM categories ORDER BY nom ASC")->fetchAll();
} catch (PDOException $e) {
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
                    $category_query = $pdo->prepare("SELECT nom FROM categories WHERE id = ?");
                    $category_query->execute([$categorie_id]);
                    $category_name = $category_query->fetchColumn();
                    
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
                // Insertion du produit dans la base de données
                $stmt = $pdo->prepare("
                    INSERT INTO products (titre, description, prix, stock, categorie_id, image_principale, actif)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $titre,
                    $description,
                    $prix,
                    $stock,
                    $categorie_id,
                    $image_principale,
                    $actif
                ]);
                
                $product_id = $pdo->lastInsertId();
                
                // Traitement des images supplémentaires
                if (!empty($_FILES['images']['name'][0])) {
                    $files = $_FILES['images'];
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    $order = 1;
                    
                    // Déterminer le dossier selon la catégorie (déjà défini pour l'image principale)
                    $category_query = $pdo->prepare("SELECT nom FROM categories WHERE id = ?");
                    $category_query->execute([$categorie_id]);
                    $category_name = $category_query->fetchColumn();
                    
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
                                    
                                    // Ajout de l'image dans la table images_products
                                    $stmt = $pdo->prepare("
                                        INSERT INTO images_products (produit_id, url_image, ordre)
                                        VALUES (?, ?, ?)
                                    ");
                                    $stmt->execute([$product_id, $image_url, $order]);
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
        } catch (PDOException $e) {
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
<body class="admin-interface">
    <!-- Overlay pour la sidebar mobile -->
    <div id="sidebarOverlay" class="position-fixed d-lg-none top-0 start-0 w-100 h-100 bg-dark bg-opacity-50" style="z-index: 99; display: none;"></div>

    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="/Exos/Techno-internet2_commerce/admin/pages/accueil_admin.php">
            Administration
        </a>
        
        <!-- Bouton pour afficher/masquer la sidebar sur mobile -->
        <button id="toggleSidebar" class="navbar-toggler d-lg-none" type="button" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="navbar-nav ms-auto px-3">
            <div class="nav-item text-nowrap d-flex align-items-center">
                <span class="text-light d-none d-md-inline me-2">
                    <i class="fas fa-user-circle me-1"></i>
                    <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Admin'; ?>
                </span>
                <a class="nav-link px-3" href="/Exos/Techno-internet2_commerce/admin/pages/disconnect.php">
                    <i class="fas fa-sign-out-alt me-1"></i>
                    <span class="d-none d-sm-inline">Déconnexion</span>
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="adminSidebar" class="col-md-3 col-lg-2 d-md-block sidebar">
                <div class="position-sticky pt-3 sidebar-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/admin/pages/accueil_admin.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                <span>Tableau de bord</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/admin/pages/gestion_meubles.php">
                                <i class="fas fa-couch me-2"></i>
                                <span>Gestion des meubles</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="/Exos/Techno-internet2_commerce/admin/pages/ajout_meuble.php">
                                <i class="fas fa-plus-circle me-2"></i>
                                <span>Ajouter un meuble</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/admin/pages/gestion_categories.php">
                                <i class="fas fa-tags me-2"></i>
                                <span>Catégories</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/admin/pages/gestion_commandes.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                <span>Commandes</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/admin/pages/gestion_clients.php">
                                <i class="fas fa-users me-2"></i>
                                <span>Clients</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/admin/pages/images.php">
                                <i class="fas fa-images me-2"></i>
                                <span>Images de produits</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/admin/pages/statistiques.php">
                                <i class="fas fa-chart-bar me-2"></i>
                                <span>Statistiques</span>
                            </a>
                        </li>
                    </ul>
                    
                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                        <span>Site</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link" href="/Exos/Techno-internet2_commerce/index_.php" target="_blank">
                                <i class="fas fa-external-link-alt me-2"></i>
                                <span>Voir le site</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Contenu principal -->
            <main id="contentWrapper" class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
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
                            <div class="row g-3">
                                <!-- Titre -->
                                <div class="col-12 col-md-6">
                                    <label for="titre" class="form-label">Titre du produit <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="titre" name="titre" value="<?= isset($titre) ? htmlspecialchars($titre) : '' ?>" required>
                                </div>
                                
                                <!-- Prix -->
                                <div class="col-12 col-md-3">
                                    <label for="prix" class="form-label">Prix <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="prix" name="prix" step="0.01" min="0" value="<?= isset($prix) ? htmlspecialchars($prix) : '' ?>" required>
                                        <span class="input-group-text">€</span>
                                    </div>
                                </div>
                                
                                <!-- Stock -->
                                <div class="col-12 col-md-3">
                                    <label for="stock" class="form-label">Stock <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="stock" name="stock" min="0" value="<?= isset($stock) ? htmlspecialchars($stock) : '' ?>" required>
                                </div>
                                
                                <!-- Catégorie -->
                                <div class="col-12 col-md-6">
                                    <label for="categorie" class="form-label">Catégorie <span class="text-danger">*</span></label>
                                    <select class="form-select" id="categorie" name="categorie" required>
                                        <option value="">Sélectionner une catégorie</option>
                                        <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (isset($categorie) && $categorie == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nom']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Actif -->
                                <div class="col-12 col-md-6">
                                    <label class="form-label d-block">Statut du produit</label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="actif" id="actif_oui" value="1" <?= (!isset($actif) || $actif == 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="actif_oui">Actif</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="actif" id="actif_non" value="0" <?= (isset($actif) && $actif == 0) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="actif_non">Inactif</label>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="col-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="5"><?= isset($description) ? htmlspecialchars($description) : '' ?></textarea>
                                </div>
                                
                                <!-- Images -->
                                <div class="col-12">
                                    <hr class="my-3">
                                    <h5>Images du produit</h5>
                                </div>
                                
                                <!-- Image principale -->
                                <div class="col-12 col-md-6">
                                    <label for="image_principale" class="form-label">Image principale <span class="text-danger">*</span></label>
                                    <input type="file" class="form-control" id="image_principale" name="image_principale" accept="image/*" required>
                                    <div class="form-text">Format recommandé: 800x600px, max 2Mo</div>
                                </div>
                                
                                <!-- Images supplémentaires -->
                                <div class="col-12 col-md-6">
                                    <label for="images" class="form-label">Images supplémentaires</label>
                                    <input type="file" class="form-control" id="images" name="images[]" accept="image/*" multiple>
                                    <div class="form-text">Vous pouvez sélectionner plusieurs images (max 5)</div>
                                </div>
                                
                                <!-- Prévisualisation des images -->
                                <div class="col-12">
                                    <div class="mt-3 mb-3">
                                        <label class="form-label">Aperçu des images</label>
                                        <div id="image-preview" class="d-flex flex-wrap gap-2 p-2 border rounded">
                                            <div class="text-muted small text-center w-100">
                                                <i class="fas fa-images me-1"></i>
                                                Les aperçus s'afficheront ici
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Boutons d'action -->
                                <div class="col-12 d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <button type="reset" class="btn btn-outline-secondary">
                                        <i class="fas fa-undo me-1"></i> Réinitialiser
                                    </button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Ajouter le meuble
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Notre fichier JavaScript pour l'interface admin -->
    <script src="/Exos/Techno-internet2_commerce/admin/public/js/fonction.js"></script>
</body>
</html>

