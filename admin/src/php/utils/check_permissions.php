<?php
/**
 * Utilitaire pour vérifier les permissions des dossiers d'upload
 */

/**
 * Vérifie si un répertoire a les droits d'écriture nécessaires
 * et le crée s'il n'existe pas
 * 
 * @param string $directory Le chemin du répertoire à vérifier
 * @return array Statut de l'opération et message
 */
function checkDirectoryPermissions($directory) {
    $result = [
        'success' => false,
        'message' => '',
        'directory' => $directory
    ];
    
    // Créer le chemin complet avec le répertoire du site
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/Exos/Techno-internet2_commerce/' . $directory;
    
    // Vérifier si le répertoire existe
    if (!file_exists($fullPath)) {
        // Tenter de créer le répertoire
        if (!mkdir($fullPath, 0777, true)) {
            $result['message'] = "Impossible de créer le répertoire : $fullPath";
            return $result;
        }
        $result['message'] = "Répertoire créé avec succès : $fullPath";
    } else {
        $result['message'] = "Le répertoire existe déjà : $fullPath";
    }
    
    // Vérifier si le répertoire est accessible en écriture
    if (!is_writable($fullPath)) {
        // Tenter de changer les permissions
        if (!chmod($fullPath, 0777)) {
            $result['message'] .= " Mais il n'est pas accessible en écriture et impossible de modifier les permissions.";
            return $result;
        }
        $result['message'] .= " Permissions modifiées pour autoriser l'écriture.";
    } else {
        $result['message'] .= " Accessible en écriture.";
    }
    
    $result['success'] = true;
    return $result;
}

/**
 * Vérifie tous les répertoires d'upload nécessaires
 * 
 * @return array Résultats pour chaque répertoire
 */
function checkAllUploadDirectories() {
    $directories = [
        'uploads/products',
        'uploads/categories',
        'uploads/users',
        'uploads/temp'
    ];
    
    $results = [];
    
    foreach ($directories as $dir) {
        $results[$dir] = checkDirectoryPermissions($dir);
    }
    
    return $results;
} 