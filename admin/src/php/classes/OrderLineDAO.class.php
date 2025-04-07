<?php

class OrderLineDAO
{
    private $_bd;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
    }

    /**
     * Récupère une ligne de commande par son ID
     * @param int $id ID de la ligne de commande
     * @return OrderLine|false Ligne de commande trouvée ou false
     */
    public function findById($id)
    {
        $query = "SELECT * FROM order_lines WHERE id = :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new OrderLine($data);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la ligne de commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée une nouvelle ligne de commande
     * @param array $data Données de la ligne de commande
     * @return int|false ID de la nouvelle ligne de commande ou false si échec
     */
    public function create(array $data)
    {
        try {
            $this->_bd->beginTransaction();
            
            $query = "INSERT INTO order_lines (order_id, produit_id, quantite, prix_unitaire) 
                      VALUES (:order_id, :produit_id, :quantite, :prix_unitaire)";
            
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $data['order_id'], PDO::PARAM_INT);
            $stmt->bindValue(':produit_id', $data['produit_id'], PDO::PARAM_INT);
            $stmt->bindValue(':quantite', $data['quantite'], PDO::PARAM_INT);
            $stmt->bindValue(':prix_unitaire', $data['prix_unitaire'], PDO::PARAM_STR);
            
            $result = $stmt->execute();
            $id = $this->_bd->lastInsertId();
            $this->_bd->commit();
            
            return $result ? $id : false;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la création de la ligne de commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les lignes d'une commande
     * @param int $orderId ID de la commande
     * @return array Liste des lignes de commande
     */
    public function findByOrderId($orderId)
    {
        $query = "SELECT ol.*, p.titre as produit_titre, p.image_principale as produit_image 
                  FROM order_lines ol 
                  JOIN products p ON ol.produit_id = p.id 
                  WHERE ol.order_id = :order_id";
        
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            $orderLines = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $orderLines[] = new OrderLine($data);
            }
            
            return $orderLines;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des lignes de commande: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcule le total d'une commande à partir de ses lignes
     * @param int $orderId ID de la commande
     * @return float Total de la commande
     */
    public function calculateOrderTotal($orderId)
    {
        $query = "SELECT SUM(quantite * prix_unitaire) as total FROM order_lines WHERE order_id = :order_id";
        
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du total de la commande: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Met à jour une ligne de commande
     * @param int $id ID de la ligne de commande
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

        $query = "UPDATE order_lines SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
        
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
            error_log("Erreur lors de la mise à jour de la ligne de commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Supprime une ligne de commande
     * @param int $id ID de la ligne de commande
     * @return bool Succès ou échec
     */
    public function delete($id)
    {
        $query = "DELETE FROM order_lines WHERE id = :id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la suppression de la ligne de commande: " . $e->getMessage());
            return false;
        }
    }
}
