<?php

class ProductDAO
{
    private $_bd;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
    }

    /**
     * Récupère un produit par son ID
     * @param int $id ID du produit
     * @return Product|false Produit trouvé ou false
     */
    public function findById($id)
    {
        $query = "SELECT p.*, c.nom as categorie_nom 
                  FROM products p 
                  LEFT JOIN categories c ON p.categorie_id = c.id 
                  WHERE p.id = :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new Product($data);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les produits avec filtres optionnels
     * @param int|null $categoryId Filtrer par catégorie (optionnel)
     * @param bool|null $active Filtrer par statut actif (optionnel)
     * @param string $orderBy Champ de tri (par défaut: id)
     * @param int|null $limit Nombre maximum de produits (optionnel)
     * @param int|null $offset Décalage pour la pagination (optionnel)
     * @param bool|null $featured Filtrer par statut à la une (optionnel)
     * @return array Liste des produits
     */
    public function findAll($categoryId = null, $active = null, $orderBy = 'p.id', $limit = null, $offset = null, $featured = null)
    {
        // Débogage
        error_log("findAll appelé avec categoryId=$categoryId, active=" . ($active === null ? 'null' : ($active ? 'true' : 'false')));
    
        $query = "SELECT p.*, c.nom as categorie_nom 
                  FROM products p 
                  LEFT JOIN categories c ON p.categorie_id = c.id";
        
        $conditions = [];
        $params = [];
        
        if ($categoryId !== null) {
            $conditions[] = "p.categorie_id = :categorie_id";
            $params[':categorie_id'] = $categoryId;
        }
        
        // Modification pour PostgreSQL - ne pas utiliser de paramètres pour les booléens
        if ($active !== null) {
            $boolValue = $active ? 'TRUE' : 'FALSE';
            $conditions[] = "p.actif = $boolValue";
        }

        if ($featured !== null) {
            $boolValue = $featured ? 'TRUE' : 'FALSE';
            $conditions[] = "p.featured = $boolValue";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $query .= " ORDER BY " . $orderBy;
        
        if ($limit !== null) {
            $query .= " LIMIT :limit";
            $params[':limit'] = $limit;
            
            if ($offset !== null) {
                $query .= " OFFSET :offset";
                $params[':offset'] = $offset;
            }
        }
        
        try {
            error_log("Exécution de la requête SQL: $query avec params: " . print_r($params, true));
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                if ($param == ':limit' || $param == ':offset') {
                    $stmt->bindValue($param, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($param, $val);
                }
            }
            $stmt->execute();
            
            $products = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = new Product($data);
            }
            
            error_log("Nombre de produits récupérés: " . count($products));
            return $products;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des produits: " . $e->getMessage() . " SQL: " . $query);
            return [];
        }
    }

    /**
     * Récupère tous les produits actifs
     * @param int|null $categoryId Filtrer par catégorie (optionnel)
     * @param bool|null $active Filtrer par statut actif (true par défaut)
     * @param string $orderBy Champ de tri (par défaut: date_creation DESC)
     * @param int|null $limit Nombre maximum de produits (optionnel)
     * @param int|null $offset Décalage pour la pagination (optionnel)
     * @param bool|null $featured Filtrer par statut à la une (optionnel)
     * @return array Liste des produits actifs
     */
    public function findAllActive($categoryId = null, $active = true, $orderBy = 'p.date_creation DESC', $limit = null, $offset = null, $featured = null)
    {
        return $this->findAll($categoryId, $active, $orderBy, $limit, $offset, $featured);
    }

    /**
     * Compte le nombre total de produits avec filtres optionnels
     * @param int|null $categoryId Filtrer par catégorie (optionnel)
     * @param bool|null $active Filtrer par statut actif (optionnel)
     * @return int Nombre de produits
     */
    public function countAll($categoryId = null, $active = null)
    {
        // Débogage
        error_log("countAll appelé avec categoryId=$categoryId, active=" . ($active === null ? 'null' : ($active ? 'true' : 'false')));
        
        $query = "SELECT COUNT(*) as count FROM products p";
        
        $conditions = [];
        $params = [];
        
        if ($categoryId !== null) {
            $conditions[] = "p.categorie_id = :categorie_id";
            $params[':categorie_id'] = $categoryId;
        }
        
        // Modification pour PostgreSQL - ne pas utiliser de paramètres pour les booléens
        if ($active !== null) {
            $boolValue = $active ? 'TRUE' : 'FALSE';
            $conditions[] = "p.actif = $boolValue";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
        }
        
        try {
            error_log("Exécution de la requête SQL: $query avec params: " . print_r($params, true));
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = (int)$result['count'];
            error_log("Nombre de produits comptés: $count");
            return $count;
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des produits: " . $e->getMessage() . " SQL: " . $query);
            return 0;
        }
    }

    /**
     * Recherche des produits par mot-clé
     * @param string $keyword Mot-clé à rechercher
     * @param bool|null $active Filtrer par statut actif (optionnel)
     * @param int|null $limit Nombre maximum de produits (optionnel)
     * @param int|null $offset Décalage pour la pagination (optionnel)
     * @param int|null $categoryId Filtrer par catégorie (optionnel)
     * @param string $orderBy Champ de tri (optionnel)
     * @return array Liste des produits correspondants
     */
    public function search($keyword, $active = null, $limit = null, $offset = null, $categoryId = null, $orderBy = 'p.id DESC')
    {
        // Débogage
        error_log("search appelé avec keyword=$keyword, active=" . ($active === null ? 'null' : ($active ? 'true' : 'false')));
        
        $query = "SELECT p.*, c.nom as categorie_nom 
                  FROM products p 
                  LEFT JOIN categories c ON p.categorie_id = c.id 
                  WHERE (p.titre ILIKE :keyword OR p.description ILIKE :keyword)";
        
        $params = [':keyword' => '%' . $keyword . '%'];
        
        // Modification pour PostgreSQL - ne pas utiliser de paramètres pour les booléens
        if ($active !== null) {
            $boolValue = $active ? 'TRUE' : 'FALSE';
            $query .= " AND p.actif = $boolValue";
        }
        
        if ($categoryId !== null) {
            $query .= " AND p.categorie_id = :categorie_id";
            $params[':categorie_id'] = $categoryId;
        }
        
        $query .= " ORDER BY " . $orderBy;
        
        if ($limit !== null) {
            $query .= " LIMIT :limit";
            $params[':limit'] = $limit;
            
            if ($offset !== null) {
                $query .= " OFFSET :offset";
                $params[':offset'] = $offset;
            }
        }
        
        try {
            error_log("Exécution de la requête SQL: $query avec params: " . print_r($params, true));
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                if ($param == ':limit' || $param == ':offset') {
                    $stmt->bindValue($param, $val, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($param, $val);
                }
            }
            $stmt->execute();
            
            $products = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = new Product($data);
            }
            
            error_log("Nombre de produits trouvés par recherche: " . count($products));
            return $products;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de produits: " . $e->getMessage() . " SQL: " . $query);
            return [];
        }
    }

    /**
     * Crée un nouveau produit
     * @param array $data Données du produit
     * @return int|false ID du nouveau produit ou false si échec
     */
    public function create(array $data)
    {
        $query = "INSERT INTO products (titre, description, prix, stock, categorie_id, image_principale, actif) 
                  VALUES (:titre, :description, :prix, :stock, :categorie_id, :image_principale, :actif)";
        
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':titre', $data['titre']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            $stmt->bindValue(':prix', $data['prix']);
            $stmt->bindValue(':stock', $data['stock'] ?? 0, PDO::PARAM_INT);
            $stmt->bindValue(':categorie_id', $data['categorie_id'] ?? null, PDO::PARAM_INT);
            $stmt->bindValue(':image_principale', $data['image_principale'] ?? null);
            $stmt->bindValue(':actif', $data['actif'] ?? true, PDO::PARAM_BOOL);
            
            $result = $stmt->execute();
            $id = $this->_bd->lastInsertId();
            $this->_bd->commit();
            
            return $result ? $id : false;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la création du produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un produit
     * @param int $id ID du produit
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec
     */
    public function update($id, array $data)
    {
        $fieldsToUpdate = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'date_creation') {
                $fieldsToUpdate[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fieldsToUpdate)) {
            return false;
        }

        $query = "UPDATE products SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
        
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la mise à jour du produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour le stock d'un produit
     * @param int $id ID du produit
     * @param int $quantity Quantité à ajouter (positif) ou retirer (négatif)
     * @return bool Succès ou échec
     */
    public function updateStock($id, $quantity)
    {
        $query = "UPDATE products SET stock = stock + :quantity WHERE id = :id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la mise à jour du stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime un produit
     * @param int $id ID du produit
     * @return bool Succès ou échec
     */
    public function delete($id)
    {
        $query = "DELETE FROM products WHERE id = :id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la suppression du produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Active ou désactive un produit
     * @param int $id ID du produit
     * @param bool $active Statut actif ou inactif
     * @return bool Succès ou échec
     */
    public function setActive($id, $active)
    {
        $query = "UPDATE products SET actif = :actif WHERE id = :id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':actif', $active, PDO::PARAM_BOOL);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la modification du statut actif: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compte le nombre de produits dans une catégorie spécifique
     * @param int $categoryId ID de la catégorie
     * @return int Nombre de produits dans la catégorie
     */
    public function countByCategory($categoryId)
    {
        $query = "SELECT COUNT(*) as count FROM products WHERE categorie_id = :categorie_id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':categorie_id', $categoryId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des produits par catégorie: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Efface la catégorie pour tous les produits d'une catégorie spécifique
     * @param int $categoryId ID de la catégorie
     * @return bool Succès ou échec
     */
    public function clearCategoryForProducts($categoryId)
    {
        $query = "UPDATE products SET categorie_id = NULL WHERE categorie_id = :categorie_id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':categorie_id', $categoryId, PDO::PARAM_INT);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de l'effacement de la catégorie des produits: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les produits les plus vendus
     * @param int $limit Nombre de produits à récupérer
     * @return array Liste des produits les plus vendus
     */
    public function findBestSellers($limit)
    {
        $query = "SELECT 
                    p.id, 
                    p.titre as name, 
                    p.prix as price, 
                    p.image_principale, 
                    COALESCE(SUM(ol.quantite), 0) as total_sold
                  FROM products p
                  LEFT JOIN order_lines ol ON p.id = ol.produit_id
                  LEFT JOIN orders o ON ol.order_id = o.id AND o.statut != 'annulé'
                  WHERE p.actif = TRUE
                  GROUP BY p.id, p.titre, p.prix, p.image_principale
                  ORDER BY total_sold DESC, p.id ASC
                  LIMIT :limit";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des best-sellers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les produits avec un stock faible
     * @param int $limit Nombre maximum de produits à récupérer
     * @param int $threshold Seuil de stock bas (défaut: 5)
     * @return array Liste des produits avec un stock faible
     */
    public function findLowStock($limit, $threshold = 5)
    {
        $query = "SELECT p.*, c.nom as categorie_nom 
                  FROM products p 
                  LEFT JOIN categories c ON p.categorie_id = c.id 
                  WHERE p.stock < :threshold AND p.actif = TRUE
                  ORDER BY p.stock ASC 
                  LIMIT :limit";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $products = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $products[] = new Product($data);
            }
            
            return $products;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des produits à stock bas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte le nombre de résultats de recherche
     * @param string $keyword Mot-clé à rechercher
     * @param bool|null $active Filtrer par statut actif (optionnel)
     * @param int|null $categoryId Filtrer par catégorie (optionnel)
     * @return int Nombre de résultats
     */
    public function countSearchResults($keyword, $active = null, $categoryId = null)
    {
        $query = "SELECT COUNT(*) as count 
                  FROM products p 
                  WHERE (p.titre ILIKE :keyword OR p.description ILIKE :keyword)";
        
        $params = [':keyword' => '%' . $keyword . '%'];
        
        if ($active !== null) {
            $query .= " AND p.actif = :actif";
            $params[':actif'] = $active;
        }
        
        if ($categoryId !== null) {
            $query .= " AND p.categorie_id = :categorie_id";
            $params[':categorie_id'] = $categoryId;
        }
        
        try {
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des résultats de recherche: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Active ou désactive un produit (soft delete)
     * @param int $id ID du produit
     * @param bool $active Statut actif (true) ou inactif (false)
     * @return bool Succès ou échec
     */
    public function toggleActiveStatus($id, $active = true)
    {
        $query = "UPDATE products SET actif = :actif WHERE id = :id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':actif', $active, PDO::PARAM_BOOL);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la modification du statut actif du produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les ventes par catégorie pour une période donnée
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return array Liste des ventes par catégorie
     */
    public function getSalesByCategory($startDate, $endDate)
    {
        $query = "SELECT 
                    c.nom AS category,
                    SUM(oi.quantite) AS count,
                    SUM(oi.prix_unitaire * oi.quantite) AS total
                  FROM order_items oi
                  JOIN products p ON oi.produit_id = p.id
                  JOIN orders o ON oi.commande_id = o.id
                  JOIN categories c ON p.categorie_id = c.id
                  WHERE o.date_commande >= :start_date 
                  AND o.date_commande <= :end_date::date + interval '1 day'
                  AND o.statut != 'cancelled'
                  GROUP BY c.nom
                  ORDER BY total DESC";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $results = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Conversion en objet standard pour uniformité
                $categoryData = new stdClass();
                $categoryData->category = $data['category'];
                $categoryData->count = $data['count'];
                $categoryData->total = $data['total'];
                $results[] = $categoryData;
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des ventes par catégorie: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les produits les plus vendus sur une période donnée
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @param int $limit Nombre maximum de produits à récupérer
     * @return array Liste des produits les plus vendus
     */
    public function getTopSellingProducts($startDate, $endDate, $limit = 10)
    {
        $query = "SELECT 
                    p.id,
                    p.titre as name,
                    p.prix as price,
                    p.image_principale,
                    SUM(ol.quantite) AS quantite_vendue,
                    SUM(ol.quantite * ol.prix_unitaire) AS ca_genere
                  FROM products p
                  JOIN order_lines ol ON p.id = ol.produit_id
                  JOIN orders o ON ol.order_id = o.id
                  WHERE o.date_commande >= :start_date 
                  AND o.date_commande <= :end_date::date + interval '1 day'
                  AND o.statut != 'annulé'
                  GROUP BY p.id, p.titre, p.prix, p.image_principale
                  ORDER BY quantite_vendue DESC
                  LIMIT :limit";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des produits les plus vendus: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Convertit le paramètre de tri en ordre SQL
     * @param string $sort Paramètre de tri (price_asc, price_desc, name_desc, newest, name_asc)
     * @return string Clause ORDER BY SQL
     */
    public function getSortOrderBy($sort)
    {
        $orderBy = 'p.titre ASC'; // par défaut
        
        switch ($sort) {
            case 'price_asc':
                $orderBy = 'p.prix ASC';
                break;
            case 'price_desc':
                $orderBy = 'p.prix DESC';
                break;
            case 'name_desc':
                $orderBy = 'p.titre DESC';
                break;
            case 'newest':
                $orderBy = 'p.date_creation DESC';
                break;
        }
        
        return $orderBy;
    }
    
    /**
     * Calcule les informations de pagination
     * @param int $total_items Nombre total d'éléments
     * @param int $per_page Nombre d'éléments par page
     * @param int $current_page Page actuelle
     * @return array Informations de pagination
     */
    public function calculatePagination($total_items, $per_page, $current_page)
    {
        $total_pages = ceil($total_items / $per_page);
        $current_page = max(1, min($current_page, $total_pages));
        
        return [
            'total_pages' => $total_pages,
            'current_page' => $current_page,
            'total_items' => $total_items,
            'per_page' => $per_page
        ];
    }
}
