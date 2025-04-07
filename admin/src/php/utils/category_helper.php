<?php
/**
 * Utilitaire pour gérer les catégories et leurs dossiers associés
 */

/**
 * Détermine le dossier d'upload à utiliser en fonction de la catégorie
 * 
 * @param string $category_name Nom de la catégorie
 * @return string Nom du dossier d'upload
 */
function getCategoryFolder($category_name) {
    // Conversion en minuscules pour la comparaison
    $category_lower = strtolower($category_name);
    
    // Détermination du dossier selon la catégorie
    if (strpos($category_lower, 'assise') !== false || 
        strpos($category_lower, 'chaise') !== false || 
        strpos($category_lower, 'fauteuil') !== false || 
        strpos($category_lower, 'canapé') !== false || 
        strpos($category_lower, 'canape') !== false) {
        return "Furniture Assises";
    } else if (strpos($category_lower, 'décoration') !== false || 
              strpos($category_lower, 'decoration') !== false || 
              strpos($category_lower, 'accessoire') !== false || 
              strpos($category_lower, 'déco') !== false || 
              strpos($category_lower, 'deco') !== false) {
        return "Furniture Décoration & Accessoires";
    } else {
        // Par défaut, utilisez le dossier Rangement
        return "Furniture Rangement";
    }
}

/**
 * Génère un nom de fichier unique pour les uploads d'images
 * 
 * @param string $extension Extension du fichier
 * @param int $index Index optionnel pour les uploads multiples
 * @return string Nom de fichier unique
 */
function generateUniqueImageName($extension, $index = null) {
    $name = 'product_' . time() . '_' . uniqid();
    
    if ($index !== null) {
        $name .= '_' . $index;
    }
    
    return $name . '.' . $extension;
}

/**
 * Crée le dossier d'upload s'il n'existe pas
 * 
 * @param string $folder_name Nom du dossier à créer
 * @return array Statut de l'opération et message
 */
function ensureUploadFolder($folder_name) {
    $result = [
        'success' => true,
        'message' => ''
    ];
    
    $upload_dir = '../public/images/' . $folder_name . '/';
    
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            $result['success'] = false;
            $result['message'] = "Impossible de créer le répertoire d'upload: " . $upload_dir;
        }
    }
    
    $result['path'] = $upload_dir;
    return $result;
} 