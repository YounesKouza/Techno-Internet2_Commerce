<?php

class CategoryDAO
{
    private $_bd;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
    }

    /**
     * Récupère une catégorie par son ID
     * @param int $id ID de la catégorie
     * @return Category|false Catégorie trouvée ou false
     */
    public function findById($id)
    {
        $query = "SELECT * FROM categories WHERE id = :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new Category($data);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la catégorie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les catégories
     * @return array Liste des catégories
     */
    public function findAll()
    {
        $query = "SELECT * FROM categories ORDER BY nom";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->execute();
            
            $categories = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = new Category($data);
            }
            
            return $categories;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère un nombre limité de catégories, triées par ID
     * @param int $limit Nombre maximum de catégories à récupérer
     * @return array Liste des catégories
     */
    public function findLimitedSortedById($limit)
    {
        $query = "SELECT * FROM categories ORDER BY id LIMIT :limit";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $categories = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $categories[] = new Category($data);
            }
            
            return $categories;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des catégories limitées: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Crée une nouvelle catégorie
     * @param array $data Données de la catégorie
     * @return int|false ID de la nouvelle catégorie ou false si échec
     */
    public function create(array $data)
    {
        $query = "INSERT INTO categories (nom, description) VALUES (:nom, :description)";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':nom', $data['nom']);
            $stmt->bindValue(':description', $data['description'] ?? null);
            
            $result = $stmt->execute();
            $id = $this->_bd->lastInsertId();
            $this->_bd->commit();
            
            return $result ? $id : false;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la création de la catégorie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une catégorie
     * Cette méthode accepte soit un ID et un tableau de données, soit un tableau contenant l'ID.
     * @param int|array $idOrData ID de la catégorie ou tableau contenant l'ID et les données
     * @param array|null $data Données à mettre à jour (null si le premier paramètre est un tableau complet)
     * @return bool Succès ou échec
     */
    public function update($idOrData, array $data = null)
    {
        // Déterminer si on a reçu un ID + données ou juste un tableau avec ID inclus
        if (is_array($idOrData) && isset($idOrData['id'])) {
            $categoryData = $idOrData;
            $id = $categoryData['id'];
        } else {
            $id = $idOrData;
            $categoryData = $data;
        }
        
        // Vérification si on a un ID et des données
        if (empty($id) || !is_array($categoryData)) {
            return false;
        }

        $fieldsToUpdate = [];
        $params = [':id' => $id];

        foreach ($categoryData as $key => $value) {
            if ($key !== 'id') {
                $fieldsToUpdate[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fieldsToUpdate)) {
            return false;
        }

        $query = "UPDATE categories SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
        
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
            error_log("Erreur lors de la mise à jour de la catégorie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une catégorie
     * @param int $id ID de la catégorie
     * @return bool Succès ou échec
     */
    public function delete($id)
    {
        $query = "DELETE FROM categories WHERE id = :id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la suppression de la catégorie: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compte le nombre de produits dans une catégorie
     * @param int $categoryId ID de la catégorie
     * @return int Nombre de produits
     */
    public function countProducts($categoryId)
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
     * Vérifie si une catégorie avec le nom spécifié existe déjà
     * @param string $name Nom à vérifier
     * @return bool True si une catégorie avec ce nom existe déjà, false sinon
     */
    public function categoryExistsByName($name)
    {
        $query = "SELECT COUNT(*) FROM categories WHERE nom = :nom";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':nom', $name);
            $stmt->execute();
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification d'existence de la catégorie par nom: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si une catégorie avec le nom spécifié existe déjà, en excluant une catégorie spécifique
     * @param string $name Nom à vérifier
     * @param int $exceptId ID de la catégorie à exclure
     * @return bool True si une catégorie avec ce nom existe déjà (sauf celle spécifiée), false sinon
     */
    public function categoryExistsByNameExcept($name, $exceptId)
    {
        $query = "SELECT COUNT(*) FROM categories WHERE nom = :nom AND id != :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':nom', $name);
            $stmt->bindValue(':id', $exceptId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification d'existence de la catégorie par nom: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les catégories avec le nombre de produits associés
     * @return array Liste des catégories avec le nombre de produits
     */
    public function findAllWithProductCount()
    {
        $query = "SELECT c.*, COUNT(p.id) as product_count 
                 FROM categories c
                 LEFT JOIN products p ON c.id = p.categorie_id
                 GROUP BY c.id, c.nom, c.description
                 ORDER BY c.nom ASC";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->execute();
            
            $categories = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $category = new Category($data);
                $category->product_count = (int)$data['product_count'];
                $categories[] = $category;
            }
            
            return $categories;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des catégories avec comptage des produits: " . $e->getMessage());
            return [];
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
                    c.id as id,
                    c.nom as category_name, 
                    COUNT(ol.id) as count, 
                    SUM(ol.prix_unitaire * ol.quantite) as total
                  FROM order_lines ol
                  JOIN products p ON ol.produit_id = p.id
                  JOIN orders o ON ol.order_id = o.id
                  JOIN categories c ON p.categorie_id = c.id
                  WHERE o.date_commande >= :start_date 
                  AND o.date_commande <= :end_date::date + interval '1 day'
                  AND o.statut != 'cancelled'
                  GROUP BY c.id, c.nom
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
                $categoryData->id = $data['id'];
                $categoryData->category_name = $data['category_name'];
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
} 