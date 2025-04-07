<?php

class OrderDAO
{
    private $_bd;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
    }

    /**
     * Récupère une commande par son ID
     * @param int $id ID de la commande
     * @return Order|false Commande trouvée ou false
     */
    public function findById($id)
    {
        $query = "SELECT * FROM orders WHERE id = :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new Order($data, $this);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Crée une nouvelle commande dans la base de données
     * @param array $data Données de la commande
     * @return int|false ID de la nouvelle commande ou false si échec
     */
    public function create(array $data)
    {
        try {
            $this->_bd->beginTransaction();
            
            $query = "INSERT INTO orders (utilisateur_id, montant_total, statut, adresse_livraison, adresse_facturation, methode_paiement, date_commande) 
                      VALUES (:utilisateur_id, :montant_total, :statut, :adresse_livraison, :adresse_facturation, :methode_paiement, NOW())";
            
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':utilisateur_id', $data['utilisateur_id'], PDO::PARAM_INT);
            $stmt->bindValue(':montant_total', $data['montant_total'], PDO::PARAM_STR);
            $stmt->bindValue(':statut', $data['statut'] ?? 'En attente');
            $stmt->bindValue(':adresse_livraison', $data['adresse_livraison'] ?? null);
            $stmt->bindValue(':adresse_facturation', $data['adresse_facturation'] ?? null);
            $stmt->bindValue(':methode_paiement', $data['methode_paiement'] ?? null);
            
            $stmt->execute();
            $orderId = $this->_bd->lastInsertId();
            
            $this->_bd->commit();
            return $orderId;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la création de la commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ajoute une ligne de commande dans la base de données
     * @param array $data Données de la ligne de commande
     * @return bool Succès ou échec
     */
    public function addOrderLine(array $data)
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
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de l'ajout de la ligne de commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour le statut d'une commande dans la base de données
     * @param int $id ID de la commande
     * @param string $status Nouveau statut
     * @return bool Succès ou échec
     */
    public function updateStatus($id, $status)
    {
        $query = "UPDATE orders SET statut = :statut WHERE id = :id";
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
            error_log("Erreur lors de la mise à jour du statut de la commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère toutes les commandes de la base de données, triées par date de commande
     * @param string|null $status Filtrer par statut (optionnel)
     * @return array Liste des commandes
     */
    public function findAll($status = null)
    {
        $query = "SELECT o.*, u.nom as client_nom, u.email as client_email 
                  FROM orders o 
                  JOIN users u ON o.utilisateur_id = u.id";
        $params = [];
        
        if ($status) {
            $query .= " WHERE o.statut = :statut";
            $params[':statut'] = $status;
        }
        
        $query .= " ORDER BY o.date_commande DESC";
        
        try {
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            $stmt->execute();
            
            $orders = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $orders[] = new Order($data, $this);
            }
            
            return $orders;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des commandes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les lignes d'une commande depuis la base de données
     * @param int $orderId ID de la commande
     * @return array Liste des lignes de commande
     */
    public function getOrderLines($orderId)
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
     * Récupère les commandes d'un utilisateur depuis la base de données
     * @param int $userId ID de l'utilisateur
     * @return array Liste des commandes
     */
    public function findByUser($userId)
    {
        $query = "SELECT * FROM orders WHERE utilisateur_id = :utilisateur_id ORDER BY date_commande DESC";
        
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':utilisateur_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $orders = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $orders[] = new Order($data, $this);
            }
            
            return $orders;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des commandes de l'utilisateur: " . $e->getMessage());
            return [];
        }
    }
}
