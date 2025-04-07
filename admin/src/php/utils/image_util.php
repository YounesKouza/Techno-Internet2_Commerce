<?php
/**
 * Utilitaire pour explorer les images et mettre à jour la base de données
 * avec leurs références
 */

// Inclusion du fichier de connexion à la base de données
require_once __DIR__ . '/connexion.php';

// Chemin de base des images - Utilisation de DIRECTORY_SEPARATOR pour compatibilité Windows
define('BASE_IMAGE_PATH', '/admin/public/images/');

// Garantir que nous avons le bon chemin racine du projet
$utilPath = realpath(__FILE__);
$adminSrcPhpUtils = dirname($utilPath);
$adminSrcPhp = dirname($adminSrcPhpUtils);
$adminSrc = dirname($adminSrcPhp);
$admin = dirname($adminSrc);
$projectRoot = dirname($admin);

define('PROJECT_ROOT', $projectRoot);
define('SERVER_IMAGE_PATH', $projectRoot . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images');
define('WEB_IMAGE_PATH', '/Exos/Techno-internet2_commerce' . BASE_IMAGE_PATH);

/**
 * Découvre récursivement toutes les images dans un dossier
 * 
 * @param string $dir Le dossier à explorer
 * @param string $relativePath Le chemin relatif pour construire l'URL
 * @return array Liste des images trouvées avec leur chemin
 */
function discoverImages($dir, $relativePath = '') {
    $images = [];
    
    if (!is_dir($dir)) {
        echo "Le répertoire $dir n'existe pas.\n";
        return $images;
    }
    
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        $relPath = $relativePath . '/' . $file;
        
        if (is_dir($path)) {
            // Parcourir récursivement les sous-dossiers
            $subImages = discoverImages($path, $relPath);
            $images = array_merge($images, $subImages);
        } else {
            // Vérifier si c'est une image
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $images[] = [
                    'path' => $relPath,
                    'name' => $file,
                    'category' => basename(dirname($relPath)),
                    'url' => WEB_IMAGE_PATH . ltrim($relPath, '/')
                ];
            }
        }
    }
    
    return $images;
}

/**
 * Insère ou met à jour une image dans la table images
 * 
 * @param array $image Information sur l'image
 * @return int|bool ID de l'image insérée/mise à jour ou false si erreur
 */
function insertOrUpdateImage($image) {
    $pdo = getPDO();
    
    try {
        // Vérifier si l'image existe déjà
        $stmt = $pdo->prepare("SELECT id FROM product_images WHERE url = ?");
        $stmt->execute([$image['url']]);
        $existingImage = $stmt->fetch();
        
        if ($existingImage) {
            // Mise à jour
            $stmt = $pdo->prepare("
                UPDATE product_images 
                SET name = ?, category = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $image['name'],
                $image['category'],
                $existingImage['id']
            ]);
            return $existingImage['id'];
        } else {
            // Insertion
            $stmt = $pdo->prepare("
                INSERT INTO product_images (name, url, category, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([
                $image['name'],
                $image['url'],
                $image['category']
            ]);
            return $pdo->lastInsertId();
        }
    } catch (PDOException $e) {
        echo "Erreur lors de l'insertion/mise à jour de l'image : " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Crée la table product_images si elle n'existe pas
 * 
 * @return bool True si la table a été créée ou existe déjà, false sinon
 */
function ensureImagesTableExists() {
    $pdo = getPDO();
    
    try {
        // Vérifier si la table existe
        $stmt = $pdo->prepare("
            SELECT EXISTS (
                SELECT FROM information_schema.tables 
                WHERE table_schema = 'public'
                AND table_name = 'product_images'
            )
        ");
        $stmt->execute();
        $tableExists = $stmt->fetchColumn();
        
        if (!$tableExists) {
            // Créer la table
            $pdo->exec("
                CREATE TABLE product_images (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(255) NOT NULL,
                    url VARCHAR(255) NOT NULL,
                    category VARCHAR(255),
                    product_id INTEGER,
                    is_main BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP NOT NULL,
                    updated_at TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
                )
            ");
            
            echo "Table product_images créée avec succès.\n";
        }
        
        return true;
    } catch (PDOException $e) {
        echo "Erreur lors de la création de la table product_images : " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Lie les images aux produits basés sur la catégorie
 * 
 * @return bool True si l'opération a réussi, false sinon
 */
function linkImagesToProducts() {
    $pdo = getPDO();
    
    try {
        // Récupérer toutes les images sans produit associé
        $stmt = $pdo->prepare("
            SELECT id, category FROM product_images 
            WHERE product_id IS NULL
        ");
        $stmt->execute();
        $images = $stmt->fetchAll();
        
        echo "Nombre d'images à associer: " . count($images) . "\n";
        
        // D'abord, récupérer les catégories disponibles
        $stmt = $pdo->prepare("
            SELECT id, nom
            FROM categories
        ");
        $stmt->execute();
        $categories = $stmt->fetchAll();
        
        echo "Catégories disponibles: \n";
        foreach ($categories as $category) {
            echo " - {$category['id']}: {$category['nom']}\n";
        }
        
        $matches = 0;
        
        foreach ($images as $image) {
            // Trouver la catégorie qui correspond le mieux
            $bestCategoryId = null;
            $bestScore = 0;
            
            foreach ($categories as $category) {
                $categoryName = strtolower($category['nom']);
                $imageCategoryName = strtolower($image['category']);
                
                // Compare les noms
                if (strpos($categoryName, $imageCategoryName) !== false ||
                    strpos($imageCategoryName, $categoryName) !== false) {
                    
                    $score = similar_text($categoryName, $imageCategoryName);
                    if ($score > $bestScore) {
                        $bestScore = $score;
                        $bestCategoryId = $category['id'];
                    }
                }
            }
            
            if ($bestCategoryId) {
                echo "Image '{$image['category']}' associée à la catégorie ID: $bestCategoryId\n";
                
                // Trouver un produit de cette catégorie
                $stmt = $pdo->prepare("
                    SELECT id FROM products 
                    WHERE categorie_id = ? 
                    ORDER BY id 
                    LIMIT 1
                ");
                $stmt->execute([$bestCategoryId]);
                $product = $stmt->fetch();
                
                if ($product) {
                    $matches++;
                    
                    // Associer l'image au produit
                    $stmt = $pdo->prepare("
                        UPDATE product_images 
                        SET product_id = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$product['id'], $image['id']]);
                    
                    // Vérifier si le produit a déjà une image principale
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM product_images 
                        WHERE product_id = ? AND is_main = TRUE
                    ");
                    $stmt->execute([$product['id']]);
                    $hasMainImage = $stmt->fetchColumn() > 0;
                    
                    if (!$hasMainImage) {
                        // Définir cette image comme principale
                        $stmt = $pdo->prepare("
                            UPDATE product_images 
                            SET is_main = TRUE, updated_at = NOW() 
                            WHERE id = ?
                        ");
                        $stmt->execute([$image['id']]);
                        
                        // Mettre à jour le produit pour référencer l'image principale
                        $stmt = $pdo->prepare("
                            UPDATE products 
                            SET image_principale = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([$image['url'], $product['id']]);
                    }
                } else {
                    echo "Aucun produit trouvé pour la catégorie ID: $bestCategoryId\n";
                }
            } else {
                echo "Aucune correspondance de catégorie trouvée pour '{$image['category']}'\n";
            }
        }
        
        echo "Nombre d'images associées à des produits: $matches\n";
        
        return true;
    } catch (PDOException $e) {
        echo "Erreur lors de l'association des images aux produits : " . $e->getMessage() . "\n";
        return false;
    }
}

/**
 * Script principal pour traiter les images
 */
function processImages() {
    // S'assurer que la table existe
    if (!ensureImagesTableExists()) {
        echo "Impossible de continuer sans la table product_images.\n";
        return;
    }
    
    // Utiliser le chemin défini globalement
    $baseDir = SERVER_IMAGE_PATH;
    
    echo "Chemin du projet: " . PROJECT_ROOT . "\n";
    echo "Chemin de recherche des images: $baseDir\n";
    
    // Vérifier si le chemin existe bien
    if (!file_exists($baseDir)) {
        echo "ERREUR: Le chemin n'existe pas du tout.\n";
        // Essayons un autre chemin en dernier recours
        $testDir = realpath(PROJECT_ROOT . DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'images');
        echo "Test avec le chemin alternatif: $testDir\n";
        if ($testDir && is_dir($testDir)) {
            $baseDir = $testDir;
            echo "Utilisation du chemin alternatif qui fonctionne.\n";
        } else {
            return;
        }
    }
    
    if (!is_dir($baseDir)) {
        echo "Le répertoire $baseDir n'existe pas.\n";
        return;
    }
    
    $images = discoverImages($baseDir);
    echo "Nombre d'images découvertes : " . count($images) . "\n";
    
    if (count($images) > 0) {
        echo "Premières images trouvées:\n";
        $counter = 0;
        foreach ($images as $image) {
            if ($counter < 5) {
                echo " - {$image['name']} (Catégorie: {$image['category']}) - URL: {$image['url']}\n";
                $counter++;
            } else {
                break;
            }
        }
    }
    
    // Insérer ou mettre à jour chaque image dans la base de données
    $count = 0;
    foreach ($images as $image) {
        if (insertOrUpdateImage($image)) {
            $count++;
        }
    }
    echo "Nombre d'images traitées : $count\n";
    
    // Associer les images aux produits
    if (linkImagesToProducts()) {
        echo "Association des images aux produits terminée.\n";
    }
}

// Exécuter le traitement si ce fichier est exécuté directement
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    processImages();
} 