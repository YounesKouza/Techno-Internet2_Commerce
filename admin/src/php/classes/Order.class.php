<?php

class Order
{
    private $_attributs = array();
    private $_orderDAO;
    private $_orderLineDAO;
    private $_productDAO;

    public function __construct(array $data = [], $orderDAO = null, $orderLineDAO = null, $productDAO = null)
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
        $this->_orderDAO = $orderDAO;
        $this->_orderLineDAO = $orderLineDAO;
        $this->_productDAO = $productDAO;
    }

    public function hydrate(array $data)
    {
        foreach ($data as $champ => $valeur) {
            $this->$champ = $valeur;
        }
    }

    public function __get($champ)
    {
        if (isset($this->_attributs[$champ])) {
            return $this->_attributs[$champ];
        }
    }

    public function __set($champ, $valeur)
    {
        $this->_attributs[$champ] = $valeur;
    }

    /**
     * Crée une nouvelle commande
     * @param array $data Données de la commande
     * @return int|false ID de la nouvelle commande ou false si échec
     */
    public function create(array $data)
    {
        if (!$this->_orderDAO) {
            throw new Exception("OrderDAO non initialisé");
        }
        return $this->_orderDAO->create($data);
    }

    /**
     * Ajoute une ligne de commande
     * @param array $data Données de la ligne de commande
     * @return bool Succès ou échec
     */
    public function addOrderLine(array $data)
    {
        if (!$this->_orderDAO) {
            throw new Exception("OrderDAO non initialisé");
        }
        return $this->_orderDAO->addOrderLine($data);
    }

    /**
     * Met à jour le statut d'une commande
     * @param string $status Nouveau statut
     * @return bool Succès ou échec
     */
    public function updateStatus($status)
    {
        if (!$this->_orderDAO) {
            throw new Exception("OrderDAO non initialisé");
        }
        if (!isset($this->_attributs['id'])) {
            throw new Exception("ID de commande non défini");
        }
        return $this->_orderDAO->updateStatus($this->_attributs['id'], $status);
    }

    /**
     * Récupère les lignes d'une commande
     * @return array Liste des lignes de commande
     */
    public function getOrderLines()
    {
        if (!$this->_orderDAO) {
            throw new Exception("OrderDAO non initialisé");
        }
        if (!isset($this->_attributs['id'])) {
            throw new Exception("ID de commande non défini");
        }
        return $this->_orderDAO->getOrderLines($this->_attributs['id']);
    }

    /**
     * Traite une commande à partir du panier
     * @param int $userId ID de l'utilisateur
     * @param string $shippingAddress Adresse de livraison
     * @param string $billingAddress Adresse de facturation
     * @param string $paymentMethod Méthode de paiement
     * @return array Résultat de l'opération
     */
    public function processFromCart($userId, $shippingAddress, $billingAddress, $paymentMethod)
    {
        if (!$this->_orderDAO || !$this->_productDAO) {
            throw new Exception("DAO non initialisés");
        }

        if (empty($_SESSION['panier'])) {
            return ['success' => false, 'message' => 'Le panier est vide'];
        }

        try {
            // Récupérer les détails des produits du panier
            $productIds = array_keys($_SESSION['panier']);
            $totalAmount = 0;
            $orderItems = [];

            foreach ($_SESSION['panier'] as $productId => $item) {
                $product = $this->_productDAO->findById($productId);
                
                if (!$product) {
                    throw new Exception("Produit ID {$productId} introuvable");
                }
                
                $quantity = $item['quantity'];
                
                if ($product->stock < $quantity) {
                    // Ajuster la quantité au stock disponible
                    $quantity = $product->stock;
                    if ($quantity <= 0) {
                        continue; // Passer au produit suivant si plus de stock
                    }
                }
                
                $subtotal = $product->prix * $quantity;
                $totalAmount += $subtotal;
                
                $orderItems[$productId] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => $product->prix,
                    'subtotal' => $subtotal
                ];
            }
            
            if (empty($orderItems)) {
                return ['success' => false, 'message' => 'Tous les produits sont en rupture de stock'];
            }

            // Créer la commande
            $orderData = [
                'utilisateur_id' => $userId,
                'montant_total' => $totalAmount,
                'adresse_livraison' => $shippingAddress,
                'adresse_facturation' => $billingAddress,
                'methode_paiement' => $paymentMethod,
                'statut' => 'En attente'
            ];
            
            $orderId = $this->create($orderData);
            
            if (!$orderId) {
                throw new Exception("Erreur lors de la création de la commande");
            }
            
            // Ajouter les lignes de commande
            foreach ($orderItems as $item) {
                $lineData = [
                    'order_id' => $orderId,
                    'produit_id' => $item['product_id'],
                    'quantite' => $item['quantity'],
                    'prix_unitaire' => $item['unit_price']
                ];
                
                if (!$this->addOrderLine($lineData)) {
                    throw new Exception("Erreur lors de l'ajout d'une ligne de commande");
                }
                
                // Mettre à jour le stock du produit
                $product = $this->_productDAO->findById($item['product_id']);
                $newStock = $product->stock - $item['quantity'];
                $this->_productDAO->updateStock($item['product_id'], $newStock);
            }
            
            // Vider le panier
            $_SESSION['panier'] = [];
            
            return [
                'success' => true,
                'message' => 'Commande créée avec succès',
                'order_id' => $orderId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }
}
