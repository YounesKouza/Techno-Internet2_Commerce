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
     * @return int|false ID du paiement créé ou false en cas d'échec
     */
    public function create(array $data)
    {
        try {
            // Utiliser la fonction PostgreSQL create_payment
            $query = "SELECT create_payment(:order_id, :mode, :reference, :statut)";
            
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $data['order_id'], PDO::PARAM_INT);
            $stmt->bindValue(':mode', $data['mode']);
            $stmt->bindValue(':reference', $data['reference'] ?? null);
            $stmt->bindValue(':statut', $data['statut'] ?? 'en_attente');
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && isset($result['create_payment'])) {
                return $result['create_payment'];
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Erreur lors de la création du paiement: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour le statut d'un paiement
     * 
     * @param int $id Identifiant du paiement
     * @param string $status Nouveau statut
     * @return bool True si la mise à jour a réussi, false sinon
     */
    public function updateStatus($id, $status) {
        try {
            $sql = "SELECT update_payment_status(:id, :status)";
            $stmt = $this->_bd->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
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
            
            // Modification pour retourner directement un tableau associatif plutôt que des objets Payment
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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

    /**
     * Met à jour le statut des paiements associés à une commande
     * 
     * @param int $orderId Identifiant de la commande
     * @param string $status Nouveau statut
     * @return bool True si la mise à jour a réussi, false sinon
     */
    public function updateStatusByOrderId($orderId, $status) {
        try {
            $sql = "SELECT update_payment_status_by_order_id(:orderId, :status)";
            $stmt = $this->_bd->prepare($sql);
            $stmt->bindParam(':orderId', $orderId, PDO::PARAM_INT);
            $stmt->bindParam(':status', $status, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            error_log("Erreur lors de la mise à jour du statut des paiements pour la commande: " . $e->getMessage());
            return false;
        }
    }
} 