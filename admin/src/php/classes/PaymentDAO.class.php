<?php

class PaymentDAO
{
    private $_bd;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
    }

    /**
     * Récupère un paiement par son ID
     * @param int $id ID du paiement
     * @return Payment|false Paiement trouvé ou false
     */
    public function findById($id)
    {
        $query = "SELECT * FROM payments WHERE id = :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new Payment($data);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération du paiement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée un nouveau paiement
     * @param array $data Données du paiement
     * @return int|false ID du nouveau paiement ou false si échec
     */
    public function create(array $data)
    {
        $query = "INSERT INTO payments (order_id, mode_paiement, reference_transaction, statut) 
                  VALUES (:order_id, :mode_paiement, :reference_transaction, :statut)";
        
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $data['order_id'], PDO::PARAM_INT);
            $stmt->bindValue(':mode_paiement', $data['mode_paiement']);
            $stmt->bindValue(':reference_transaction', $data['reference_transaction'] ?? null);
            $stmt->bindValue(':statut', $data['statut'] ?? 'en attente');
            
            $result = $stmt->execute();
            $id = $this->_bd->lastInsertId();
            $this->_bd->commit();
            
            return $result ? $id : false;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la création du paiement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour le statut d'un paiement
     * @param int $id ID du paiement
     * @param string $status Nouveau statut
     * @return bool Succès ou échec
     */
    public function updateStatus($id, $status)
    {
        $query = "UPDATE payments SET statut = :statut WHERE id = :id";
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':statut', $status);
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la mise à jour du statut du paiement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les paiements d'une commande
     * @param int $orderId ID de la commande
     * @return array Liste des paiements
     */
    public function findByOrderId($orderId)
    {
        $query = "SELECT * FROM payments WHERE order_id = :order_id ORDER BY date_paiement DESC";
        
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            $payments = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $payments[] = new Payment($data);
            }
            
            return $payments;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des paiements de la commande: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les paiements, triés par date
     * @param string|null $status Filtrer par statut (optionnel)
     * @return array Liste des paiements
     */
    public function findAll($status = null)
    {
        $query = "SELECT p.*, o.id as order_number 
                  FROM payments p 
                  JOIN orders o ON p.order_id = o.id";
        $params = [];
        
        if ($status) {
            $query .= " WHERE p.statut = :statut";
            $params[':statut'] = $status;
        }
        
        $query .= " ORDER BY p.date_paiement DESC";
        
        try {
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            $stmt->execute();
            
            $payments = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $payments[] = new Payment($data);
            }
            
            return $payments;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des paiements: " . $e->getMessage());
            return [];
        }
    }
} 