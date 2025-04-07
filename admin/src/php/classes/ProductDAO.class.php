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
     * @return array Liste des produits
     */
    public function findAll($categoryId = null, $active = null, $orderBy = 'p.id', $limit = null, $offset = null)
    {
        $query = "SELECT p.*, c.nom as categorie_nom 
                  FROM products p 
                  LEFT JOIN categories c ON p.categorie_id = c.id";
        
        $conditions = [];
        $params = [];
        
        if ($categoryId !== null) {
            $conditions[] = "p.categorie_id = :categorie_id";
            $params[':categorie_id'] = $categoryId;
        }
        
        if ($active !== null) {
            $conditions[] = "p.actif = :actif";
            $params[':actif'] = $active;
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
            
            return $products;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des produits: " . $e->getMessage());
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
     * @return array Liste des produits actifs
     */
    public function findAllActive($categoryId = null, $active = true, $orderBy = 'p.date_creation DESC', $limit = null, $offset = null)
    {
        return $this->findAll($categoryId, $active, $orderBy, $limit, $offset);
    }

    /**
     * Compte le nombre total de produits avec filtres optionnels
     * @param int|null $categoryId Filtrer par catégorie (optionnel)
     * @param bool|null $active Filtrer par statut actif (optionnel)
     * @return int Nombre de produits
     */
    public function countAll($categoryId = null, $active = null)
    {
        $query = "SELECT COUNT(*) as count FROM products p";
        
        $conditions = [];
        $params = [];
        
        if ($categoryId !== null) {
            $conditions[] = "p.categorie_id = :categorie_id";
            $params[':categorie_id'] = $categoryId;
        }
        
        if ($active !== null) {
            $conditions[] = "p.actif = :actif";
            $params[':actif'] = $active;
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(' AND ', $conditions);
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
            error_log("Erreur lors du comptage des produits: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Recherche des produits par mot-clé
     * @param string $keyword Mot-clé à rechercher
     * @param bool|null $active Filtrer par statut actif (optionnel)
     * @param int|null $limit Nombre maximum de produits (optionnel)
     * @param int|null $offset Décalage pour la pagination (optionnel)
     * @return array Liste des produits correspondants
     */
    public function search($keyword, $active = null, $limit = null, $offset = null)
    {
        $query = "SELECT p.*, c.nom as categorie_nom 
                  FROM products p 
                  LEFT JOIN categories c ON p.categorie_id = c.id 
                  WHERE p.titre ILIKE :keyword OR p.description ILIKE :keyword";
        
        $params = [':keyword' => '%' . $keyword . '%'];
        
        if ($active !== null) {
            $query .= " AND p.actif = :actif";
            $params[':actif'] = $active;
        }
        
        $query .= " ORDER BY p.date_creation DESC";
        
        if ($limit !== null) {
            $query .= " LIMIT :limit";
            $params[':limit'] = $limit;
            
            if ($offset !== null) {
                $query .= " OFFSET :offset";
                $params[':offset'] = $offset;
            }
        }
        
        try {
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
            
            return $products;
        } catch (PDOException $e) {
            error_log("Erreur lors de la recherche de produits: " . $e->getMessage());
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
} 