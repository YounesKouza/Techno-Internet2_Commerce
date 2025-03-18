<?php
/**
 * Récupère tous les produits actifs avec leur catégorie
 * @param PDO $pdo Instance de connexion PDO
 * @return array Liste des produits
 */
function getAllProducts($pdo) {
    $query = "SELECT p.*, c.nom as categorie_nom 
              FROM products p 
              LEFT JOIN categories c ON p.categorie_id = c.id 
              WHERE p.actif = TRUE 
              ORDER BY p.date_creation DESC";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll();
}

/**
 * Récupère les produits d'une catégorie spécifique
 * @param PDO $pdo Instance de connexion PDO
 * @param int $categorieId ID de la catégorie
 * @return array Liste des produits de la catégorie
 */
function getProductsByCategory($pdo, $categorieId) {
    $query = "SELECT p.*, c.nom as categorie_nom 
              FROM products p 
              LEFT JOIN categories c ON p.categorie_id = c.id 
              WHERE p.categorie_id = :categorie_id AND p.actif = TRUE 
              ORDER BY p.date_creation DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['categorie_id' => $categorieId]);
    return $stmt->fetchAll();
}

/**
 * Récupère un produit par son ID avec sa catégorie et ses images
 * @param PDO $pdo Instance de connexion PDO
 * @param int $productId ID du produit
 * @return array|false Détails du produit ou false si non trouvé
 */
function getProductById($pdo, $productId) {
    // Récupère les informations de base du produit et sa catégorie
    $query = "SELECT p.*, c.nom as categorie_nom 
              FROM products p 
              LEFT JOIN categories c ON p.categorie_id = c.id 
              WHERE p.id = :id AND p.actif = TRUE";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        return false;
    }
    
    // Récupère toutes les images du produit
    $queryImages = "SELECT url_image FROM images_products WHERE produit_id = :id";
    $stmtImages = $pdo->prepare($queryImages);
    $stmtImages->execute(['id' => $productId]);
    $product['images'] = $stmtImages->fetchAll(PDO::FETCH_COLUMN);
    
    return $product;
}

/**
 * Récupère les produits mis en avant (par exemple les plus récents ou les plus vendus)
 * @param PDO $pdo Instance de connexion PDO
 * @param int $limit Nombre de produits à retourner
 * @return array Liste des produits mis en avant
 */
function getFeaturedProducts($pdo, $limit = 4) {
    // Ici on prend simplement les plus récents, mais on pourrait avoir une logique différente
    $query = "SELECT p.*, c.nom as categorie_nom 
              FROM products p 
              LEFT JOIN categories c ON p.categorie_id = c.id 
              WHERE p.actif = TRUE 
              ORDER BY p.date_creation DESC 
              LIMIT :limit";
    $stmt = $pdo->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Récupère toutes les catégories
 * @param PDO $pdo Instance de connexion PDO
 * @return array Liste des catégories
 */
function getAllCategories($pdo) {
    $query = "SELECT * FROM categories ORDER BY nom";
    $stmt = $pdo->query($query);
    return $stmt->fetchAll();
}

/**
 * Recherche des produits par mot-clé
 * @param PDO $pdo Instance de connexion PDO
 * @param string $keyword Mot-clé de recherche
 * @return array Liste des produits correspondants
 */
function searchProducts($pdo, $keyword) {
    $search = '%' . $keyword . '%';
    $query = "SELECT p.*, c.nom as categorie_nom 
              FROM products p 
              LEFT JOIN categories c ON p.categorie_id = c.id 
              WHERE p.actif = TRUE AND (
                  p.titre ILIKE :search OR 
                  p.description ILIKE :search OR 
                  c.nom ILIKE :search
              ) 
              ORDER BY p.date_creation DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['search' => $search]);
    return $stmt->fetchAll();
} 