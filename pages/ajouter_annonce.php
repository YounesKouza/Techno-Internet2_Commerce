<?php
// pages/ajouter_annonce.php – Ajouter une annonce
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: connexion.php");
    exit;
}
require_once '../src/php/db/dbConnect.php';
require_once '../src/php/utils/fonctions_produits.php';

$errors = [];
$categories = getAllCategories($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);
    $stock = intval($_POST['stock']);
    $categorie_id = intval($_POST['categorie_id']);
    $product_images = isset($_POST['product_images']) ? json_decode($_POST['product_images'], true) : [];
    
    if (empty($titre) || empty($description) || $prix <= 0) {
        $errors[] = "Veuillez remplir les champs obligatoires.";
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            // Déterminer l'image principale
            $image_principale = null;
            if (!empty($product_images)) {
                $image_principale = '/public/uploads/products/' . $product_images[0];
            }
            
            // Insérer le produit (sans la colonne utilisateur_id qui n'existe pas dans la DB)
            $stmt = $pdo->prepare("INSERT INTO products (titre, description, prix, stock, categorie_id, image_principale, date_creation, actif) 
                                  VALUES (:titre, :description, :prix, :stock, :categorie_id, :image_principale, NOW(), TRUE) 
                                  RETURNING id");
            
            $stmt->execute([
                'titre' => $titre,
                'description' => $description,
                'prix' => $prix,
                'stock' => $stock,
                'categorie_id' => $categorie_id,
                'image_principale' => $image_principale
            ]);
            
            $productId = $stmt->fetchColumn();
            
            // Ajouter toutes les images à la table images_products
            if (!empty($product_images)) {
                $stmtImages = $pdo->prepare("INSERT INTO images_products (produit_id, url_image) VALUES (:produit_id, :url_image)");
                
                foreach ($product_images as $img) {
                    $imgPath = '/public/uploads/products/' . $img;
                    $stmtImages->execute([
                        'produit_id' => $productId,
                        'url_image' => $imgPath
                    ]);
                }
            }
            
            $pdo->commit();
            header("Location: mes_annonces.php");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Une erreur est survenue : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Ajouter une Annonce - Furniture</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include '../public/includes/header.php'; ?>
  <div class="container my-5">
    <h1>Ajouter une Annonce</h1>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
          <p><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="ajouter_annonce.php">
      <div class="mb-3">
        <label for="titre" class="form-label">Titre</label>
        <input type="text" name="titre" id="titre" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <textarea name="description" id="description" class="form-control" required></textarea>
      </div>
      <div class="mb-3">
        <label for="prix" class="form-label">Prix (€)</label>
        <input type="number" step="0.01" name="prix" id="prix" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="stock" class="form-label">Stock</label>
        <input type="number" name="stock" id="stock" class="form-control" value="1" required>
      </div>
      <div class="mb-3">
        <label for="categorie_id" class="form-label">Catégorie</label>
        <select name="categorie_id" id="categorie_id" class="form-select" required>
          <?php foreach ($categories as $categorie): ?>
            <option value="<?php echo $categorie['id']; ?>"><?php echo htmlspecialchars($categorie['nom']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="mb-3">
        <label for="images" class="form-label">Images du produit</label>
        <div id="drop-area" class="border rounded p-4 text-center">
          <p>Glissez et déposez vos images ici ou</p>
          <input type="file" id="fileInput" multiple accept="image/*" style="display: none;">
          <button type="button" id="browseButton" class="btn btn-outline-primary">Parcourir</button>
          <p class="small text-muted mt-2">Formats acceptés : JPG, PNG, GIF. Max 5MB par image.</p>
        </div>
        <div id="preview-container" class="d-flex flex-wrap gap-2 mt-3"></div>
        <!-- Champ caché pour stocker les noms de fichiers -->
        <input type="hidden" id="product-images" name="product_images">
      </div>
      <button type="submit" class="btn btn-primary">Publier l'annonce</button>
    </form>
  </div>
  <?php include '../public/includes/footer.php'; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.14.0/Sortable.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/browser-image-compression/2.0.0/browser-image-compression.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const dropArea = document.getElementById('drop-area');
  const fileInput = document.getElementById('fileInput');
  const browseButton = document.getElementById('browseButton');
  const previewContainer = document.getElementById('preview-container');
  const productImagesInput = document.getElementById('product-images');
  
  // Variables pour stocker les fichiers et les noms de fichiers
  let uploadedFiles = [];
  let uploadedFilenames = [];
  
  // Ajouter un message lorsqu'aucune image n'est présente
  const noImagesMessage = document.createElement('p');
  noImagesMessage.id = 'no-images-message';
  noImagesMessage.className = 'text-muted fst-italic';
  noImagesMessage.textContent = 'Aucune image téléchargée';
  previewContainer.appendChild(noImagesMessage);
  
  // Créer la barre de progression
  const progressContainer = document.createElement('div');
  progressContainer.className = 'progress mt-2';
  progressContainer.style.display = 'none';
  progressContainer.id = 'upload-progress-container';
  
  const progressBar = document.createElement('div');
  progressBar.id = 'upload-progress';
  progressBar.className = 'progress-bar progress-bar-striped progress-bar-animated';
  progressBar.setAttribute('role', 'progressbar');
  progressBar.style.width = '0%';
  
  progressContainer.appendChild(progressBar);
  dropArea.parentNode.insertBefore(progressContainer, previewContainer);
  
  // Ajouter un message d'information sur la compression
  const compressionInfo = document.createElement('div');
  compressionInfo.className = 'form-text text-muted mb-2';
  compressionInfo.textContent = 'Les images seront automatiquement compressées pour optimiser le temps de chargement.';
  dropArea.parentNode.insertBefore(compressionInfo, progressContainer);
  
  // Ajouter un message pour l'image principale
  const mainImageInfo = document.createElement('div');
  mainImageInfo.className = 'form-text text-info mt-2';
  mainImageInfo.innerHTML = '<i class="fas fa-info-circle"></i> La première image (à gauche) sera utilisée comme image principale du produit. Glissez les images pour les réorganiser.';
  previewContainer.parentNode.insertBefore(mainImageInfo, previewContainer.nextSibling);
  
  // Initialiser Sortable pour permettre le réarrangement des images
  const sortable = new Sortable(previewContainer, {
    animation: 150,
    ghostClass: 'sortable-ghost',
    onSort: function() {
      // Mettre à jour l'ordre des images dans l'input caché
      updateImageOrder();
    }
  });
  
  // Fonction pour mettre à jour l'ordre des images
  function updateImageOrder() {
    const previews = previewContainer.querySelectorAll('.preview-item');
    const newOrder = [];
    
    previews.forEach(preview => {
      const filename = preview.dataset.filename;
      if (filename) {
        newOrder.push(filename);
      }
    });
    
    uploadedFilenames = newOrder;
    productImagesInput.value = JSON.stringify(newOrder);
    
    // Mettre à jour les classes pour indiquer l'image principale
    previews.forEach((preview, index) => {
      if (index === 0) {
        preview.classList.add('main-image');
        if (preview.querySelector('.main-badge')) {
          preview.querySelector('.main-badge').style.display = 'block';
        }
      } else {
        preview.classList.remove('main-image');
        if (preview.querySelector('.main-badge')) {
          preview.querySelector('.main-badge').style.display = 'none';
        }
      }
    });
  }
  
  // Événements pour le drag and drop
  ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, preventDefaults, false);
  });
  
  function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
  }
  
  ['dragenter', 'dragover'].forEach(eventName => {
    dropArea.addEventListener(eventName, highlight, false);
  });
  
  ['dragleave', 'drop'].forEach(eventName => {
    dropArea.addEventListener(eventName, unhighlight, false);
  });
  
  function highlight() {
    dropArea.classList.add('bg-light');
  }
  
  function unhighlight() {
    dropArea.classList.remove('bg-light');
  }
  
  // Gérer le dépôt de fichiers
  dropArea.addEventListener('drop', handleDrop, false);
  
  // Gérer la sélection de fichiers via le bouton
  browseButton.addEventListener('click', () => fileInput.click());
  fileInput.addEventListener('change', () => {
    handleFiles(fileInput.files);
  });
  
  function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
  }
  
  async function handleFiles(files) {
    if (files.length > 0) {
      noImagesMessage.style.display = 'none';
    }
    
    const filesToProcess = [...files];
    const totalFiles = filesToProcess.length;
    let processedFiles = 0;
    
    // Afficher la barre de progression
    progressContainer.style.display = 'flex';
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    
    for (const file of filesToProcess) {
      // Vérification du type
      if (!file.type.match('image.*')) {
        alert(`Le fichier "${file.name}" n'est pas une image.`);
        continue;
      }
      
      // Vérification de la taille
      if (file.size > 10 * 1024 * 1024) { // 10MB max avant compression
        alert(`L'image "${file.name}" est trop volumineuse (max 10MB avant compression).`);
        continue;
      }
      
      try {
        // Compression de l'image
        const options = {
          maxSizeMB: 1,          // Taille maximale après compression (1MB)
          maxWidthOrHeight: 1920, // Dimension maximale
          useWebWorker: true,    // Utiliser un Web Worker pour ne pas bloquer l'UI
          fileType: file.type
        };
        
        // Mise à jour de la barre de progression - étape compression
        const currentProgress = Math.round((processedFiles / totalFiles) * 100);
        progressBar.style.width = `${currentProgress}%`;
        progressBar.textContent = `Compression: ${currentProgress}%`;
        
        // Compression de l'image
        const compressedFile = await imageCompression(file, options);
        
        // Prévisualisation de l'image
        previewFile(file, null); // Afficher la prévisualisation avant l'upload
        
        // Préparation du FormData pour l'upload
        const formData = new FormData();
        formData.append('file', compressedFile);
        
        // Upload du fichier avec suivi de progression
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '../src/php/upload_image.php', true);
        
        xhr.upload.onprogress = function(e) {
          if (e.lengthComputable) {
            const fileProgress = Math.round((e.loaded / e.total) * 100);
            const overallProgress = Math.round(((processedFiles + fileProgress/100) / totalFiles) * 100);
            progressBar.style.width = `${overallProgress}%`;
            progressBar.textContent = `${overallProgress}%`;
          }
        };
        
        xhr.onload = function() {
          if (xhr.status === 200) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.success) {
                // Mise à jour de la prévisualisation avec le nom de fichier
                updatePreviewWithFilename(file, response.filename);
                
                // Ajouter le nom du fichier à notre liste
                uploadedFilenames.push(response.filename);
                productImagesInput.value = JSON.stringify(uploadedFilenames);
                
                // Mettre à jour l'ordre des images
                updateImageOrder();
              } else {
                alert(`Erreur lors du téléchargement de "${file.name}": ${response.error}`);
              }
            } catch (e) {
              console.error('Erreur de parsing JSON:', e);
            }
          }
          
          // Mise à jour du compteur de fichiers traités
          processedFiles++;
          
          // Si tous les fichiers ont été traités, masquer la barre de progression
          if (processedFiles === totalFiles) {
            setTimeout(() => {
              progressContainer.style.display = 'none';
            }, 1000);
          }
        };
        
        xhr.onerror = function() {
          processedFiles++;
          alert(`Erreur réseau lors du téléchargement de "${file.name}"`);
        };
        
        xhr.send(formData);
        
      } catch (error) {
        console.error('Erreur de compression:', error);
        alert(`Erreur lors de la compression de "${file.name}"`);
        processedFiles++;
      }
    }
  }
  
  function previewFile(file, filename) {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onloadend = function() {
      const preview = document.createElement('div');
      preview.className = 'position-relative preview-item';
      preview.dataset.file = file.name;
      
      if (filename) {
        preview.dataset.filename = filename;
      }
      
      preview.innerHTML = `
        <div class="card" style="width: 120px;">
          <div class="image-container" style="height: 120px; overflow: hidden;">
            <img src="${reader.result}" class="card-img-top" style="object-fit: cover; height: 100%; width: 100%;">
          </div>
          <div class="card-body p-1">
            <div class="d-flex justify-content-between">
              <span class="badge bg-success main-badge" style="display: none;">Principal</span>
              <button type="button" class="btn btn-danger btn-sm delete-btn">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </div>
        </div>
      `;
      
      // Bouton de suppression
      const deleteBtn = preview.querySelector('.delete-btn');
      deleteBtn.addEventListener('click', function() {
        preview.remove();
        
        // Mettre à jour l'input caché
        const index = uploadedFilenames.indexOf(filename);
        if (index > -1) {
          uploadedFilenames.splice(index, 1);
          productImagesInput.value = JSON.stringify(uploadedFilenames);
        }
        
        // Afficher le message si plus d'images
        if (previewContainer.querySelectorAll('.preview-item').length === 0) {
          noImagesMessage.style.display = 'block';
        }
        
        // Mettre à jour l'ordre des images
        updateImageOrder();
      });
      
      previewContainer.appendChild(preview);
      uploadedFiles.push(file);
      
      // Mettre à jour l'ordre des images
      updateImageOrder();
    }
  }
  
  function updatePreviewWithFilename(file, filename) {
    // Trouver la prévisualisation correspondant au fichier
    const previews = previewContainer.querySelectorAll('.preview-item');
    for (const preview of previews) {
      if (preview.dataset.file === file.name && !preview.dataset.filename) {
        preview.dataset.filename = filename;
        break;
      }
    }
  }
});
</script>

<style>
.preview-item {
  cursor: grab;
  transition: transform 0.2s;
}
.preview-item:hover {
  transform: translateY(-5px);
}
.sortable-ghost {
  opacity: 0.5;
}
.main-image .card {
  border: 2px solid #198754;
}
</style>
</body>
</html>
