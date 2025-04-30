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
     * Récupère toutes les images d'un produit
     * @param int $productId ID du produit
     * @return array Liste des images du produit
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
                // Vérification et correction du chemin d'image
                if (!empty($data['url_image']) && strpos($data['url_image'], 'http') !== 0) {
                    // S'assurer que le chemin est relatif et ne commence pas par '/'
                    $data['url_image'] = ltrim($data['url_image'], '/');
                }
                $images[] = new ProductImage($data);
            }
            
            return $images;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des images du produit: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les images avec les noms de produits associés
     * @return array Liste de toutes les images avec les noms de produits
     */
    public function findAllWithProductNames()
    {
        $query = "SELECT i.*, p.nom as product_name 
                 FROM product_images i 
                 LEFT JOIN products p ON i.product_id = p.id 
                 ORDER BY i.category, i.name";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des images avec noms de produits: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ajoute une nouvelle image de produit
     * @param array $data Données de l'image
     * @return int|false ID de la nouvelle image ou false si échec
     */
    public function create(array $data)
    {
        $query = "INSERT INTO images_products (produit_id, url_image, ordre) VALUES (:produit_id, :url_image, :ordre)";
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
            error_log("Erreur lors de l'ajout de l'image du produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une image de produit
     * @param int $id ID de l'image
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
            error_log("Erreur lors de la mise à jour de l'image du produit: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une image de produit
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
            error_log("Erreur lors de la suppression de l'image du produit: " . $e->getMessage());
            return false;
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

    /**
     * Réinitialise toutes les images d'un produit comme non principales, sauf une
     * @param int $productId ID du produit
     * @param int $exceptImageId ID de l'image à exclure
     * @return bool Succès ou échec
     */
    public function resetMainImages($productId, $exceptImageId)
    {
        $query = "UPDATE product_images SET is_main = FALSE WHERE product_id = :product_id AND id != :except_id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
            $stmt->bindValue(':except_id', $exceptImageId, PDO::PARAM_INT);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la réinitialisation des images principales: " . $e->getMessage());
            return false;
        }
    }
} 