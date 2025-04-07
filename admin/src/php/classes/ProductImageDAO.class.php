<?php

class ProductImageDAO
{
    private $_bd;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
    }

    /**
     * Récupère une image par son ID
     * @param int $id ID de l'image
     * @return ProductImage|false Image trouvée ou false
     */
    public function findById($id)
    {
        $query = "SELECT * FROM images_products WHERE id = :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new ProductImage($data);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute une nouvelle image pour un produit
     * @param array $data Données de l'image
     * @return int|false ID de la nouvelle image ou false si échec
     */
    public function create(array $data)
    {
        $query = "INSERT INTO images_products (produit_id, url_image, ordre) 
                  VALUES (:produit_id, :url_image, :ordre)";
        
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':produit_id', $data['produit_id'], PDO::PARAM_INT);
            $stmt->bindValue(':url_image', $data['url_image']);
            $stmt->bindValue(':ordre', $data['ordre'] ?? 0, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            $id = $this->_bd->lastInsertId();
            $this->_bd->commit();
            
            return $result ? $id : false;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la création de l'image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une image
     * @param int $id ID de l'image
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec
     */
    public function update($id, array $data)
    {
        $fieldsToUpdate = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'date_ajout') {
                $fieldsToUpdate[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        if (empty($fieldsToUpdate)) {
            return false;
        }

        $query = "UPDATE images_products SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
        
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
            error_log("Erreur lors de la mise à jour de l'image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une image
     * @param int $id ID de l'image
     * @return bool Succès ou échec
     */
    public function delete($id)
    {
        $query = "DELETE FROM images_products WHERE id = :id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la suppression de l'image: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les images d'un produit, triées par ordre
     * @param int $productId ID du produit
     * @return array Liste des images
     */
    public function findByProductId($productId)
    {
        $query = "SELECT * FROM images_products WHERE produit_id = :produit_id ORDER BY ordre";
        
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':produit_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            $images = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $images[] = new ProductImage($data);
            }
            
            return $images;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des images du produit: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour l'ordre des images d'un produit
     * @param array $imageOrders Tableau associatif [image_id => ordre]
     * @return bool Succès ou échec
     */
    public function updateOrder(array $imageOrders)
    {
        try {
            $this->_bd->beginTransaction();
            
            $query = "UPDATE images_products SET ordre = :ordre WHERE id = :id";
            $stmt = $this->_bd->prepare($query);
            
            foreach ($imageOrders as $imageId => $order) {
                $stmt->bindValue(':id', $imageId, PDO::PARAM_INT);
                $stmt->bindValue(':ordre', $order, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            $this->_bd->commit();
            return true;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la mise à jour de l'ordre des images: " . $e->getMessage());
            return false;
        }
    }
} 