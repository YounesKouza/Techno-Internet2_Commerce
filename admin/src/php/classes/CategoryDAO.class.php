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
     * @param int $id ID de la catégorie
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec
     */
    public function update($id, array $data)
    {
        $fieldsToUpdate = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
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
            error_log("Erreur lors du comptage des produits de la catégorie: " . $e->getMessage());
            return 0;
        }
    }
} 