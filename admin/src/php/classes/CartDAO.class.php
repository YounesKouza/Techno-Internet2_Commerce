<?php

class CartDAO
{
    private $_bd;
    private $_productDAO;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
        $this->_productDAO = new ProductDAO($cnx);
    }

    /**
     * Ajoute un produit au panier en session
     * @param int $productId ID du produit
     * @param int $quantity Quantité à ajouter
     * @return array Résultat de l'opération
     */
    public function addToCart($productId, $quantity)
    {
        // Vérifier les paramètres
        if (!$productId || !$quantity || $quantity <= 0) {
            return ['success' => false, 'message' => 'Paramètres invalides'];
        }

        // Vérifier l'existence du produit et son stock
        $product = $this->_productDAO->findById($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Produit introuvable'];
        }
        
        if ($product->stock < $quantity) {
            return ['success' => false, 'message' => 'Stock insuffisant (disponible: '.$product->stock.')'];
        }

        // Initialiser le panier si nécessaire
        if (!isset($_SESSION['panier'])) {
            $_SESSION['panier'] = [];
        }

        // Ajouter ou mettre à jour le produit dans le panier
        if (isset($_SESSION['panier'][$productId])) {
            $newQuantity = $_SESSION['panier'][$productId]['quantity'] + $quantity;
            
            // Limiter à la quantité en stock
            if ($newQuantity > $product->stock) {
                $newQuantity = $product->stock;
            }
            
            $_SESSION['panier'][$productId]['quantity'] = $newQuantity;
        } else {
            $_SESSION['panier'][$productId] = [
                'quantity' => $quantity
            ];
        }

        // Retourner le résultat
        return [
            'success' => true, 
            'message' => 'Produit ajouté au panier', 
            'cart_count' => count($_SESSION['panier'])
        ];
    }

    /**
     * Supprime un produit du panier en session
     * @param int $productId ID du produit à supprimer
     * @return array Résultat de l'opération
     */
    public function removeFromCart($productId)
    {
        // Vérifier si le panier existe et si le produit est dedans
        if (isset($_SESSION['panier']) && isset($_SESSION['panier'][$productId])) {
            unset($_SESSION['panier'][$productId]);
            return [
                'success' => true, 
                'message' => 'Produit supprimé du panier', 
                'cart_count' => count($_SESSION['panier'])
            ];
        }
        
        return ['success' => false, 'message' => 'Produit non trouvé dans le panier'];
    }

    /**
     * Met à jour la quantité d'un produit dans le panier en session
     * @param int $productId ID du produit
     * @param int $quantity Nouvelle quantité
     * @return array Résultat de l'opération
     */
    public function updateCartItem($productId, $quantity)
    {
        // Vérifier les paramètres
        if (!$productId) {
            return ['success' => false, 'message' => 'ID du produit manquant'];
        }

        // Si la quantité est 0 ou négative, supprimer l'article
        if ($quantity <= 0) {
            return $this->removeFromCart($productId);
        }

        // Vérifier si le produit est dans le panier
        if (!isset($_SESSION['panier'][$productId])) {
            return ['success' => false, 'message' => 'Produit non trouvé dans le panier'];
        }

        // Vérifier le stock
        $product = $this->_productDAO->findById($productId);
        if (!$product) {
            return ['success' => false, 'message' => 'Produit introuvable'];
        }

        // Limiter à la quantité en stock
        if ($quantity > $product->stock) {
            $quantity = $product->stock;
        }

        // Mettre à jour la quantité
        $_SESSION['panier'][$productId]['quantity'] = $quantity;

        return [
            'success' => true, 
            'message' => 'Quantité mise à jour', 
            'cart_count' => count($_SESSION['panier'])
        ];
    }

    /**
     * Récupère le contenu détaillé du panier depuis la session
     * @return array Détails du panier (produits, total, etc.)
     */
    public function getCartDetails()
    {
        $cartItems = [];
        $total = 0;
        
        if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])) {
            foreach ($_SESSION['panier'] as $productId => $item) {
                $product = $this->_productDAO->findById($productId);
                
                if ($product) {
                    $quantity = $item['quantity'];
                    $subtotal = $product->prix * $quantity;
                    $total += $subtotal;
                    
                    $cartItems[] = [
                        'id' => $product->id,
                        'titre' => $product->titre,
                        'prix' => $product->prix,
                        'image' => $product->image_principale,
                        'quantity' => $quantity,
                        'subtotal' => $subtotal
                    ];
                }
            }
        }
        
        return [
            'success' => true,
            'items' => $cartItems,
            'total' => $total,
            'cart_count' => count($_SESSION['panier'] ?? [])
        ];
    }

    /**
     * Vide complètement le panier en session
     * @return array Résultat de l'opération
     */
    public function clearCart()
    {
        $_SESSION['panier'] = [];
        return [
            'success' => true,
            'message' => 'Panier vidé',
            'cart_count' => 0
        ];
    }
}
