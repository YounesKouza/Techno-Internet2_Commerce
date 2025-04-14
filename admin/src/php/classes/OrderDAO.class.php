<?php

class OrderDAO
{
    private $_bd;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
    }

    /**
     * Compte le nombre total de commandes
     * @return int Nombre de commandes
     */
    public function countAll()
    {
        $query = "SELECT COUNT(*) as count FROM orders";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des commandes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcule le chiffre d'affaires total (commandes non annulées)
     * @return float Montant total des ventes
     */
    public function getTotalSales()
    {
        $query = "SELECT SUM(montant_total) as total FROM orders WHERE statut != 'cancelled'";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (float)($result['total'] ?? 0);
        } catch (PDOException $e) {
            error_log("Erreur lors du calcul du chiffre d'affaires: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les commandes récentes
     * @param int $limit Nombre maximum de commandes à récupérer
     * @return array Liste des commandes récentes
     */
    public function findRecent($limit)
    {
        $query = "SELECT o.*, u.nom as client_nom 
                  FROM orders o 
                  LEFT JOIN users u ON o.utilisateur_id = u.id 
                  ORDER BY o.date_commande DESC 
                  LIMIT :limit";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $orders = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $orders[] = new Order($data);
            }
            
            return $orders;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des commandes récentes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère une commande par son ID
     * @param int $id ID de la commande
     * @return Order|false Commande trouvée ou false
     */
    public function findById($id)
    {
        $query = "SELECT o.*, u.nom as client_nom, u.email as client_email 
                  FROM orders o 
                  LEFT JOIN users u ON o.utilisateur_id = u.id 
                  WHERE o.id = :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new Order($data);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de la commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les détails d'une commande (produits)
     * @param int $orderId ID de la commande
     * @return array Liste des produits de la commande
     */
    public function getOrderDetails($orderId)
    {
        $query = "SELECT od.*, p.titre, p.image_principale 
                  FROM order_lines od 
                  LEFT JOIN products p ON od.produit_id = p.id 
                  WHERE od.order_id = :order_id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des détails de la commande: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Met à jour le statut d'une commande
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
     * Crée une nouvelle commande dans la base de données
     * @param array $data Données de la commande
     * @return int|false ID de la nouvelle commande ou false si échec
     */
    public function create(array $data)
    {
        try {
            $this->_bd->beginTransaction();
            
            // Structure simplifiée correspondant au schéma PostgreSQL
            $query = "INSERT INTO orders (utilisateur_id, montant_total, statut) 
                      VALUES (:utilisateur_id, :montant_total, :statut)";
            
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':utilisateur_id', $data['utilisateur_id'], PDO::PARAM_INT);
            $stmt->bindValue(':montant_total', $data['montant_total'], PDO::PARAM_STR);
            $stmt->bindValue(':statut', $data['statut'] ?? 'en attente');
            
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
            
            // Utilisation de la procédure stockée PostgreSQL pour ajouter une ligne de commande
            $query = "SELECT add_order_line(:order_id, :produit_id, :quantite, :prix_unitaire)";
            
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
     * Récupère les lignes d'une commande
     * @param int $orderId ID de la commande
     * @return array Liste des lignes de commande
     */
    public function getOrderLines($orderId)
    {
        $query = "SELECT ol.*, p.titre, p.image_principale 
                  FROM order_lines ol 
                  JOIN products p ON ol.produit_id = p.id 
                  WHERE ol.order_id = :order_id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    public function findByUserId($userId)
    {
        try {
            $stmt = $this->_bd->prepare("
                SELECT 
                    o.id, 
                    o.date_commande, 
                    o.montant_total, 
                    o.statut,
                    COUNT(ol.id) AS nb_produits
                FROM orders o
                LEFT JOIN order_lines ol ON o.id = ol.order_id
                WHERE o.utilisateur_id = :utilisateur_id
                GROUP BY o.id, o.date_commande, o.montant_total, o.statut
                ORDER BY o.date_commande DESC
            ");
            $stmt->bindValue(':utilisateur_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des commandes de l'utilisateur #$userId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les ventes par mois pour une période donnée
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return array Liste des ventes par mois
     */
    public function getSalesByMonth($startDate, $endDate)
    {
        $query = "SELECT 
                    to_char(date_commande, 'YYYY-MM') AS month,
                    SUM(montant_total) AS total
                  FROM orders
                  WHERE date_commande >= :start_date 
                  AND date_commande <= :end_date::date + interval '1 day'
                  AND statut != 'cancelled'
                  GROUP BY month
                  ORDER BY month ASC";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $results = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Conversion en objet standard pour uniformité
                $salesData = new stdClass();
                $salesData->month = $data['month'];
                $salesData->total = $data['total'];
                $results[] = $salesData;
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des ventes par mois: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les commandes par statut pour une période donnée
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @return array Liste des commandes par statut
     */
    public function getOrdersByStatus($startDate, $endDate)
    {
        $query = "SELECT 
                    statut,
                    COUNT(*) AS count
                  FROM orders
                  WHERE date_commande >= :start_date 
                  AND date_commande <= :end_date::date + interval '1 day'
                  GROUP BY statut
                  ORDER BY count DESC";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->execute();
            
            $results = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Conversion en objet standard pour uniformité
                $statusData = new stdClass();
                $statusData->statut = $data['statut'];
                $statusData->count = $data['count'];
                $results[] = $statusData;
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des commandes par statut: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère toutes les commandes avec les filtres appliqués
     * @param string $search Terme de recherche
     * @param string $status Filtre par statut
     * @param int $limit Nombre de résultats par page
     * @param int $offset Offset pour la pagination
     * @return array Liste des commandes
     */
    public function findAllWithFilters($search = '', $status = '', $limit = 10, $offset = 0)
    {
        $query = "SELECT o.*, u.nom as client_name, u.email as client_email 
                 FROM orders o
                 LEFT JOIN users u ON o.utilisateur_id = u.id
                 WHERE 1=1";
        
        $params = [];
        
        if (!empty($status)) {
            $query .= " AND o.statut = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (u.nom ILIKE :search OR u.email ILIKE :search OR CAST(o.id AS TEXT) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $query .= " ORDER BY o.date_commande DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        try {
            $stmt = $this->_bd->prepare($query);
            
            // Liaison des paramètres nommés
            foreach ($params as $key => $value) {
                if ($key == ':limit' || $key == ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des commandes avec filtres: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte le nombre de commandes avec les filtres appliqués
     * @param string $search Terme de recherche
     * @param string $status Filtre par statut
     * @return int Nombre total de commandes
     */
    public function countWithFilters($search = '', $status = '')
    {
        $query = "SELECT COUNT(*) as count
                 FROM orders o
                 LEFT JOIN users u ON o.utilisateur_id = u.id
                 WHERE 1=1";
        
        $params = [];
        
        if (!empty($status)) {
            $query .= " AND o.statut = :status";
            $params[':status'] = $status;
        }
        
        if (!empty($search)) {
            $query .= " AND (u.nom ILIKE :search OR u.email ILIKE :search OR CAST(o.id AS TEXT) LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        try {
            $stmt = $this->_bd->prepare($query);
            
            // Liaison des paramètres nommés
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des commandes avec filtres: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère tous les statuts distincts des commandes
     * @return array Liste des statuts
     */
    public function getAllStatuses()
    {
        try {
            $stmt = $this->_bd->query("SELECT DISTINCT statut FROM orders ORDER BY statut");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des statuts de commandes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère les détails d'une commande par son ID
     * @param int $id ID de la commande
     * @return array|false Détails de la commande ou false si non trouvée
     */
    public function findDetailById($id)
    {
        $query = "SELECT o.*, u.nom as client_name, u.email as client_email, 
                        u.adresse as client_address, u.telephone as client_phone
                 FROM orders o
                 LEFT JOIN users u ON o.utilisateur_id = u.id
                 WHERE o.id = :id";
        
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des détails de la commande #$id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les lignes de commande pour une commande donnée
     * @param int $orderId ID de la commande
     * @return array Liste des lignes de commande
     */
    public function findOrderLinesByOrderId($orderId)
    {
        $query = "SELECT ol.*, p.titre as product_name, p.image_principale as product_image
                 FROM order_lines ol
                 LEFT JOIN products p ON ol.produit_id = p.id
                 WHERE ol.order_id = :order_id";
        
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des lignes de commande pour la commande #$orderId: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Supprime une commande et toutes ses dépendances
     * @param int $id ID de la commande
     * @return bool True si succès, false sinon
     */
    public function deleteWithDependencies($id)
    {
        try {
            $this->_bd->beginTransaction();
            
            // Suppression des lignes de commande
            $stmt = $this->_bd->prepare("DELETE FROM order_lines WHERE order_id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Suppression des paiements
            $stmt = $this->_bd->prepare("DELETE FROM payments WHERE order_id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Suppression de la commande
            $stmt = $this->_bd->prepare("DELETE FROM orders WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->_bd->commit();
            return true;
        } catch (PDOException $e) {
            $this->_bd->rollBack();
            error_log("Erreur lors de la suppression de la commande #$id et ses dépendances: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compte le nombre de commandes d'un utilisateur
     * @param int $userId ID de l'utilisateur
     * @return int Nombre de commandes
     */
    public function countByUserId($userId)
    {
        try {
            $stmt = $this->_bd->prepare("SELECT COUNT(*) FROM orders WHERE utilisateur_id = :utilisateur_id");
            $stmt->bindValue(':utilisateur_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des commandes de l'utilisateur #$userId: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Supprime toutes les commandes d'un utilisateur et leurs dépendances
     * @param int $userId ID de l'utilisateur
     * @return bool Succès ou échec
     */
    public function deleteAllByUserId($userId)
    {
        try {
            $this->_bd->beginTransaction();
            
            // Récupération des commandes de l'utilisateur
            $stmt = $this->_bd->prepare("SELECT id FROM orders WHERE utilisateur_id = :utilisateur_id");
            $stmt->bindValue(':utilisateur_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $order_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Suppression des lignes de commande pour chaque commande
            foreach ($order_ids as $order_id) {
                $stmt = $this->_bd->prepare("DELETE FROM order_lines WHERE order_id = :order_id");
                $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
                $stmt->execute();
                
                // Suppression des paiements
                $stmt = $this->_bd->prepare("DELETE FROM payments WHERE order_id = :order_id");
                $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
                $stmt->execute();
            }
            
            // Suppression des commandes
            $stmt = $this->_bd->prepare("DELETE FROM orders WHERE utilisateur_id = :utilisateur_id");
            $stmt->bindValue(':utilisateur_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $this->_bd->commit();
            return true;
        } catch (PDOException $e) {
            $this->_bd->rollBack();
            error_log("Erreur lors de la suppression des commandes de l'utilisateur #$userId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour une commande dans la base de données
     * @param array $data Données de la commande
     * @return bool Succès ou échec
     */
    public function update(array $data)
    {
        try {
            $this->_bd->beginTransaction();
            
            $query = "UPDATE orders SET ";
            $params = [];
            $updateFields = [];
            
            // Vérification des champs à mettre à jour
            if (isset($data['statut'])) {
                $updateFields[] = "statut = :statut";
                $params[':statut'] = $data['statut'];
            }
            
            if (isset($data['montant_total'])) {
                $updateFields[] = "montant_total = :montant_total";
                $params[':montant_total'] = $data['montant_total'];
            }
            
            if (isset($data['adresse_livraison'])) {
                $updateFields[] = "adresse_livraison = :adresse_livraison";
                $params[':adresse_livraison'] = $data['adresse_livraison'];
            }
            
            if (isset($data['adresse_facturation'])) {
                $updateFields[] = "adresse_facturation = :adresse_facturation";
                $params[':adresse_facturation'] = $data['adresse_facturation'];
            }
            
            if (isset($data['methode_paiement'])) {
                $updateFields[] = "methode_paiement = :methode_paiement";
                $params[':methode_paiement'] = $data['methode_paiement'];
            }
            
            // Si aucun champ à mettre à jour
            if (empty($updateFields)) {
                return true; // Rien à mettre à jour
            }
            
            $query .= implode(", ", $updateFields);
            $query .= " WHERE id = :id";
            $params[':id'] = $data['id'];
            
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la mise à jour de la commande: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compte le nombre de produits dans une commande
     * @param int $orderId ID de la commande
     * @return int Nombre de produits
     */
    public function countProductsInOrder($orderId)
    {
        $query = "SELECT COALESCE(SUM(quantite), 0) as count FROM order_lines WHERE order_id = :order_id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des produits dans la commande: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les détails d'une commande spécifique pour un utilisateur
     * @param int $orderId ID de la commande
     * @param int $userId ID de l'utilisateur
     * @return array|false Détails de la commande ou false si la commande n'existe pas ou n'appartient pas à l'utilisateur
     */
    public function findOrderDetailsByUserId($orderId, $userId)
    {
        try {
            // Récupérer les informations de base de la commande
            $stmt = $this->_bd->prepare("
                SELECT o.*, COUNT(ol.id) AS nb_produits
                FROM orders o
                LEFT JOIN order_lines ol ON o.id = ol.order_id
                WHERE o.id = :order_id AND o.utilisateur_id = :utilisateur_id
                GROUP BY o.id, o.utilisateur_id, o.date_commande, o.montant_total, o.statut
            ");
            $stmt->bindValue(':order_id', $orderId, PDO::PARAM_INT);
            $stmt->bindValue(':utilisateur_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $orderDetails = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$orderDetails) {
                return false;
            }
            
            // Récupérer les lignes de commande
            $orderLines = $this->getOrderLines($orderId);
            
            return [
                'order' => $orderDetails,
                'lines' => $orderLines
            ];
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des détails de la commande #$orderId pour l'utilisateur #$userId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Valide les données de commande
     * @param array $formData Données du formulaire
     * @return array Résultat de la validation
     */
    public function validateOrderData($formData)
    {
        $errors = [];
        $isValid = true;
        
        // Validation du nom
        if (empty($formData['nom'])) {
            $errors[] = 'Le nom est obligatoire.';
            $isValid = false;
        }
        
        // Validation de l'email
        if (empty($formData['email'])) {
            $errors[] = 'L\'email est obligatoire.';
            $isValid = false;
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Veuillez entrer une adresse email valide.';
            $isValid = false;
        }
        
        // Validation du téléphone
        if (empty($formData['telephone'])) {
            $errors[] = 'Le téléphone est obligatoire pour la livraison.';
            $isValid = false;
        }
        
        // Validation du mode de paiement
        if (isset($formData['payment'])) {
            $payment = $formData['payment'];
            $mode_paiement = $payment->mode_paiement ?? '';
            
            if (empty($mode_paiement)) {
                $errors[] = 'Veuillez sélectionner un mode de paiement.';
                $isValid = false;
            }
            
            // Validation des détails de la carte si paiement par carte
            if ($mode_paiement === 'carte') {
                if (empty($payment->numero_carte)) {
                    $errors[] = 'Le numéro de carte est obligatoire.';
                    $isValid = false;
                }
                
                if (empty($payment->date_expiration)) {
                    $errors[] = 'La date d\'expiration est obligatoire.';
                    $isValid = false;
                }
                
                if (empty($payment->cvv)) {
                    $errors[] = 'Le code de sécurité est obligatoire.';
                    $isValid = false;
                }
            }
        } else {
            $errors[] = 'Informations de paiement manquantes.';
            $isValid = false;
        }
        
        return [
            'valid' => $isValid,
            'errors' => $errors
        ];
    }
    
    /**
     * Traite une commande complète
     * @param array $orderData Données de la commande
     * @param array $items Articles du panier
     * @param object $user Utilisateur
     * @return array Résultat du traitement
     */
    public function processOrder($orderData, $items, $user)
    {
        try {
            $this->_bd->beginTransaction();
            
            // Journaliser les données pour le débogage
            error_log("Début du traitement de la commande avec les données: " . json_encode($orderData));
            
            // Verifier que les données nécessaires sont présentes
            if (empty($orderData['utilisateur_id']) || empty($orderData['montant_total'])) {
                throw new Exception("Données de commande incomplètes");
            }
            
            // S'assurer que le panier n'est pas vide
            if (empty($items)) {
                throw new Exception("Le panier est vide, impossible de créer une commande");
            }
            
            // Créer la commande
            $query = "INSERT INTO orders (utilisateur_id, montant_total, statut) 
                      VALUES (:utilisateur_id, :montant_total, :statut) RETURNING id";
            
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':utilisateur_id', $orderData['utilisateur_id'], PDO::PARAM_INT);
            $stmt->bindValue(':montant_total', $orderData['montant_total'], PDO::PARAM_STR);
            $stmt->bindValue(':statut', $orderData['statut']);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$result || !isset($result['id'])) {
                throw new Exception("Erreur lors de la création de la commande");
            }
            
            $orderId = $result['id'];
            error_log("Commande créée avec l'ID: " . $orderId);
            
            // Ajouter les articles à la commande
            foreach ($items as $item) {
                $queryDetails = "INSERT INTO order_lines (order_id, produit_id, quantite, prix_unitaire) 
                               VALUES (:order_id, :produit_id, :quantite, :prix_unitaire)";
                
                $stmtDetails = $this->_bd->prepare($queryDetails);
                $stmtDetails->bindValue(':order_id', $orderId, PDO::PARAM_INT);
                $stmtDetails->bindValue(':produit_id', $item['id'], PDO::PARAM_INT);
                $stmtDetails->bindValue(':quantite', $item['quantity'], PDO::PARAM_INT);
                $stmtDetails->bindValue(':prix_unitaire', $item['prix'], PDO::PARAM_STR);
                
                if (!$stmtDetails->execute()) {
                    throw new Exception("Erreur lors de l'ajout des détails de la commande");
                }
                
                // Mettre à jour le stock du produit
                $queryStock = "UPDATE products SET stock = stock - :quantite WHERE id = :produit_id";
                $stmtStock = $this->_bd->prepare($queryStock);
                $stmtStock->bindValue(':quantite', $item['quantity'], PDO::PARAM_INT);
                $stmtStock->bindValue(':produit_id', $item['id'], PDO::PARAM_INT);
                
                if (!$stmtStock->execute()) {
                    throw new Exception("Erreur lors de la mise à jour du stock");
                }
            }
            
            $this->_bd->commit();
            
            return [
                'success' => true,
                'order_id' => $orderId,
                'message' => 'Commande créée avec succès'
            ];
            
        } catch (Exception $e) {
            $this->_bd->rollback();
            error_log("Erreur lors du traitement de la commande: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
