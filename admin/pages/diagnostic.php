<?php
/**
 * Page de diagnostic pour vérifier les permissions et les configurations
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
require_once '../src/php/utils/sidebar.php';

// Vérification des dossiers d'upload
$upload_directories = checkAllUploadDirectories();

// Création du dossier d'upload manuellement si demandé
$create_message = '';
if (isset($_POST['create_uploads']) && $_POST['create_uploads'] === 'yes') {
    // Chemin du dossier d'upload
    $upload_path = $_SERVER['DOCUMENT_ROOT'] . '/Exos/Techno-internet2_commerce/uploads';
    
    // Créer le dossier principal s'il n'existe pas
    if (!file_exists($upload_path)) {
        if (mkdir($upload_path, 0777, true)) {
            $create_message = "Le dossier principal d'upload a été créé avec succès.";
        } else {
            $create_message = "Impossible de créer le dossier principal d'upload.";
        }
    }
    
    // Créer les sous-dossiers
    $subdirectories = ['products', 'categories', 'users', 'temp'];
    foreach ($subdirectories as $dir) {
        $subdir_path = $upload_path . '/' . $dir;
        if (!file_exists($subdir_path)) {
            if (mkdir($subdir_path, 0777, true)) {
                $create_message .= "<br>Le sous-dossier '$dir' a été créé avec succès.";
            } else {
                $create_message .= "<br>Impossible de créer le sous-dossier '$dir'.";
            }
        }
    }
    
    // Vérifier à nouveau les dossiers
    $upload_directories = checkAllUploadDirectories();
}

// Test d'écriture de fichier
$write_test_result = '';
if (isset($_POST['test_write']) && $_POST['test_write'] === 'yes') {
    $test_dir = $_POST['test_directory'] ?? 'uploads/temp';
    $full_test_path = $_SERVER['DOCUMENT_ROOT'] . '/Exos/Techno-internet2_commerce/' . $test_dir;
    
    // Vérifier si le répertoire existe
    if (!file_exists($full_test_path)) {
        $write_test_result = "Le répertoire de test n'existe pas: $full_test_path";
    } else {
        // Créer un fichier temporaire
        $test_file = $full_test_path . '/test_' . time() . '.txt';
        $content = 'Test d\'écriture effectué le ' . date('Y-m-d H:i:s');
        
        if (file_put_contents($test_file, $content) !== false) {
            $write_test_result = "Test d'écriture réussi! Fichier créé: $test_file";
            
            // Supprimer le fichier de test après 5 secondes
            if (file_exists($test_file)) {
                unlink($test_file);
                $write_test_result .= "<br>Le fichier de test a été supprimé.";
            }
        } else {
            $write_test_result = "Échec du test d'écriture pour: $test_file";
        }
    }
}

// Test d'upload d'image
$upload_test_result = '';
$upload_details = [];
$upload_success = false;

// Récupérer la liste des dossiers de catégories dans admin/public/images
$category_folders = [];
$base_images_path = $_SERVER['DOCUMENT_ROOT'] . '/Exos/Techno-internet2_commerce/admin/public/images/';
if (is_dir($base_images_path)) {
    $dir_handle = opendir($base_images_path);
    while (($dir = readdir($dir_handle)) !== false) {
        if ($dir != "." && $dir != ".." && is_dir($base_images_path . $dir) && strpos($dir, 'Furniture') === 0) {
            $category_folders[$dir] = $dir;
        }
    }
    closedir($dir_handle);
    // Trier par ordre alphabétique
    asort($category_folders);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_image_upload'])) {
    // Récupérer la catégorie sélectionnée
    $selected_category = isset($_POST['furniture_category']) ? $_POST['furniture_category'] : '';
    
    if (empty($selected_category)) {
        $upload_test_result = "Erreur: Veuillez sélectionner une catégorie.";
    } else {
        // Définir le dossier d'upload en fonction de la catégorie
        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Exos/Techno-internet2_commerce/admin/public/images/' . $selected_category . '/';
        
        // Vérifier si le répertoire existe
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $upload_test_result = "Erreur: Le dossier de la catégorie n'existe pas et n'a pas pu être créé.";
            }
        }
        
        if (empty($upload_test_result)) {
            // Vérifier si un fichier a été uploadé
            if (isset($_FILES['test_image']) && $_FILES['test_image']['error'] == 0) {
                $file = $_FILES['test_image'];
                
                // Collecter les informations sur le fichier
                $upload_details = [
                    'Nom original' => $file['name'],
                    'Type MIME' => $file['type'],
                    'Taille' => formatBytes($file['size']),
                    'Chemin temporaire' => $file['tmp_name'],
                    'Code d\'erreur' => $file['error'],
                    'Catégorie' => $selected_category
                ];
                
                // Vérifier le type de fichier
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file['type'], $allowed_types)) {
                    $upload_test_result = "Erreur: Seules les images JPEG, PNG, GIF et WEBP sont autorisées.";
                } 
                // Vérifier la taille du fichier (max 5 Mo)
                elseif ($file['size'] > 5 * 1024 * 1024) {
                    $upload_test_result = "Erreur: La taille du fichier ne doit pas dépasser 5 Mo.";
                } 
                else {
                    // Générer un nom de fichier unique
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $new_filename = 'test_upload_' . time() . '.' . $extension;
                    $destination = $upload_dir . $new_filename;
                    
                    // Tenter de déplacer le fichier
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $upload_test_result = "Succès: L'image a été uploadée avec succès dans le dossier de la catégorie '$selected_category'!";
                        $upload_details['Nom généré'] = $new_filename;
                        $upload_details['Chemin final'] = $destination;
                        $upload_success = true;
                        
                        // Chemin relatif pour l'affichage
                        $upload_details['Aperçu'] = '/Exos/Techno-internet2_commerce/admin/public/images/' . $selected_category . '/' . $new_filename;
                        
                        // Supprimer le fichier après 60 secondes (en arrière-plan)
                        register_shutdown_function(function() use ($destination) {
                            if (file_exists($destination)) {
                                @unlink($destination);
                            }
                        });
                    } else {
                        $upload_test_result = "Erreur: Impossible de déplacer le fichier uploadé vers '$destination'.";
                    }
                }
            } else {
                $error_messages = [
                    UPLOAD_ERR_INI_SIZE => "Le fichier dépasse la taille maximale définie dans php.ini",
                    UPLOAD_ERR_FORM_SIZE => "Le fichier dépasse la taille maximale définie dans le formulaire HTML",
                    UPLOAD_ERR_PARTIAL => "Le fichier n'a été que partiellement uploadé",
                    UPLOAD_ERR_NO_FILE => "Aucun fichier n'a été uploadé",
                    UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant",
                    UPLOAD_ERR_CANT_WRITE => "Échec d'écriture du fichier sur le disque",
                    UPLOAD_ERR_EXTENSION => "Une extension PHP a arrêté l'upload"
                ];
                
                $error_code = $_FILES['test_image']['error'] ?? UPLOAD_ERR_NO_FILE;
                $error_message = $error_messages[$error_code] ?? "Erreur inconnue";
                
                $upload_test_result = "Erreur d'upload: " . $error_message;
            }
        }
    }
}

// Fonction pour formater les octets
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}

// Vérification de la configuration PHP
$php_config = [
    'Version PHP' => phpversion(),
    'Extensions PHP' => implode(', ', get_loaded_extensions()),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time') . ' secondes',
    'memory_limit' => ini_get('memory_limit'),
    'display_errors' => ini_get('display_errors'),
    'file_uploads' => ini_get('file_uploads') ? 'Activé' : 'Désactivé'
];

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic du système - Administration</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/Exos/Techno-internet2_commerce/admin/public/css/style.css">
</head>
<body class="admin-interface">
    <div class="container-fluid">
        <div class="row">
            <?php generate_sidebar('diagnostic'); ?>
            
            <!-- Contenu principal -->
            <div class="col-lg-10 main-content">
                <?php if (!empty($create_message)): ?>
                <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                    <?php echo $create_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($write_test_result)): ?>
                <div class="alert alert-info alert-dismissible fade show mt-3" role="alert">
                    <?php echo $write_test_result; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($upload_test_result)): ?>
                <div class="alert alert-<?php echo $upload_success ? 'success' : 'danger'; ?> alert-dismissible fade show mt-3" role="alert">
                    <?php echo $upload_test_result; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Diagnostic du système</h1>
                </div>

                <!-- Vérification des dossiers d'upload -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Permissions des dossiers d'upload</h5>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="create_uploads" value="yes">
                            <button type="submit" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-folder-plus me-1"></i> Créer les dossiers manquants
                            </button>
                        </form>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Répertoire</th>
                                        <th>Statut</th>
                                        <th>Message</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upload_directories as $dir => $result): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($dir); ?></code></td>
                                        <td>
                                            <?php if ($result['success']): ?>
                                            <span class="badge bg-success">OK</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Erreur</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($result['message']); ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="test_write" value="yes">
                                                <input type="hidden" name="test_directory" value="<?php echo htmlspecialchars($dir); ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-info">
                                                    <i class="fas fa-file-medical me-1"></i> Tester l'écriture
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
                
                <!-- Test d'upload d'image -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0"><i class="fas fa-upload me-2"></i> Test d'upload d'image</h5>
                    </div>
                    <div class="card-body">
                        <p class="card-text">Cette section vous permet de tester l'upload d'images comme vous le feriez pour ajouter une image à un nouveau meuble. Le test vérifie le type de fichier, la taille, et les permissions d'écriture.</p>
                        
                        <form method="post" enctype="multipart/form-data" class="mb-4">
                            <div class="mb-3">
                                <label for="furniture_category" class="form-label">Sélectionner une catégorie de meuble</label>
                                <select class="form-select" id="furniture_category" name="furniture_category" required>
                                    <option value="">Sélectionnez une catégorie</option>
                                    <?php foreach ($category_folders as $category): ?>
                                    <option value="<?php echo $category; ?>"><?php echo $category; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="test_image" class="form-label">Sélectionner une image à uploader</label>
                                <input type="file" class="form-control" id="test_image" name="test_image" accept="image/jpeg,image/png,image/gif,image/webp" required>
                                <div class="form-text">Formats acceptés: JPEG, PNG, GIF, WEBP - Taille max: 5 Mo</div>
                            </div>
                            
                            <!-- Conteneur pour la prévisualisation de l'image avant upload -->
                            <div id="image-preview-container" class="mb-3"></div>
                            
                            <button type="submit" name="test_image_upload" class="btn btn-primary">
                                <i class="fas fa-upload me-1"></i> Tester l'upload
                            </button>
                        </form>
                        
                        <?php if (!empty($upload_details)): ?>
                        <h6 class="mt-4 mb-3">Détails du test d'upload</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <tbody>
                                            <?php foreach ($upload_details as $key => $value): ?>
                                                <?php if ($key !== 'Aperçu'): ?>
                                                <tr>
                                                    <th><?php echo htmlspecialchars($key); ?></th>
                                                    <td><?php echo htmlspecialchars($value); ?></td>
                                                </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <?php if (isset($upload_details['Aperçu']) && $upload_success): ?>
                            <div class="col-md-6 d-flex align-items-center justify-content-center">
                                <div class="text-center">
                                    <p><strong>Aperçu de l'image:</strong></p>
                                    <img src="<?php echo $upload_details['Aperçu']; ?>" class="img-fluid img-thumbnail" style="max-height: 200px;" alt="Aperçu de l'image téléchargée">
                                    <p class="mt-2 small text-muted">L'image sera automatiquement supprimée après quelques secondes.</p>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Configuration PHP -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0">Configuration PHP</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Paramètre</th>
                                        <th>Valeur</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($php_config as $param => $value): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($param); ?></td>
                                        <td><?php echo htmlspecialchars($value); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Conseils de dépannage -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header py-3">
                        <h5 class="mb-0">Conseils de dépannage</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong>Problèmes d'upload d'images</strong>
                                <ul class="mt-2">
                                    <li>Vérifiez que les dossiers d'upload existent et ont les permissions en écriture (chmod 777)</li>
                                    <li>Assurez-vous que la taille du fichier ne dépasse pas la limite définie dans PHP (upload_max_filesize)</li>
                                    <li>Vérifiez que le type de fichier est autorisé (JPEG, PNG, GIF, WEBP)</li>
                                    <li>Consultez les logs d'erreur PHP pour plus de détails</li>
                                </ul>
                            </li>
                            <li class="list-group-item">
                                <strong>Images qui ne s'affichent pas</strong>
                                <ul class="mt-2">
                                    <li>Vérifiez que le chemin de l'image est correct dans la base de données</li>
                                    <li>Assurez-vous que le fichier existe dans le dossier d'upload</li>
                                    <li>Vérifiez les chemins relatifs dans les balises img (../../uploads/...)</li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Notre fichier JavaScript pour l'interface admin -->
    <script src="/Exos/Techno-internet2_commerce/admin/public/js/fonction.js"></script>
    
    <!-- Script pour prévisualiser l'image avant upload -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('test_image');
            const previewContainer = document.getElementById('image-preview-container');
            
            if (fileInput && previewContainer) {
                fileInput.addEventListener('change', function() {
                    previewContainer.innerHTML = '';
                    
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        
                        reader.onload = function(e) {
                            const previewWrapper = document.createElement('div');
                            previewWrapper.className = 'text-center mb-3 p-3 border rounded';
                            
                            const previewTitle = document.createElement('p');
                            previewTitle.innerHTML = '<strong>Prévisualisation avant upload:</strong>';
                            
                            const previewImage = document.createElement('img');
                            previewImage.src = e.target.result;
                            previewImage.className = 'img-fluid img-thumbnail';
                            previewImage.style.maxHeight = '200px';
                            previewImage.alt = 'Prévisualisation de l\'image';
                            
                            previewWrapper.appendChild(previewTitle);
                            previewWrapper.appendChild(previewImage);
                            previewContainer.appendChild(previewWrapper);
                        };
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });
    </script>
</body>
</html> 