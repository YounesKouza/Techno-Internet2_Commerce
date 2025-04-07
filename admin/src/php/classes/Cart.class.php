<?php

class Cart
{
    private $_attributs = array();
    private $_cartDAO;

    public function __construct($cartDAO)
    {
        $this->_cartDAO = $cartDAO;
    }

    /**
     * Ajoute un produit au panier
     * @param int $productId ID du produit
     * @param int $quantity Quantité à ajouter
     * @return array Résultat de l'opération
     */
    public function addProduct($productId, $quantity)
    {
        return $this->_cartDAO->addToCart($productId, $quantity);
    }

    /**
     * Supprime un produit du panier
     * @param int $productId ID du produit à supprimer
     * @return array Résultat de l'opération
     */
    public function removeProduct($productId)
    {
        return $this->_cartDAO->removeFromCart($productId);
    }

    /**
     * Met à jour la quantité d'un produit dans le panier
     * @param int $productId ID du produit
     * @param int $quantity Nouvelle quantité
     * @return array Résultat de l'opération
     */
    public function updateProductQuantity($productId, $quantity)
    {
        return $this->_cartDAO->updateCartItem($productId, $quantity);
    }

    /**
     * Récupère le contenu détaillé du panier
     * @return array Détails du panier (produits, total, etc.)
     */
    public function getDetails()
    {
        return $this->_cartDAO->getCartDetails();
    }

    /**
     * Vide complètement le panier
     * @return array Résultat de l'opération
     */
    public function clear()
    {
        return $this->_cartDAO->clearCart();
    }

    /**
     * Gère toutes les actions sur le panier
     * @param string $action L'action à effectuer (add, remove, update, get_cart, clear)
     * @param array $params Les paramètres de l'action
     * @return array Résultat de l'opération
     */
    public function processAction($action, $params = [])
    {
        switch ($action) {
            case 'add':
                return $this->addProduct(
                    isset($params['product_id']) ? intval($params['product_id']) : 0,
                    isset($params['quantity']) ? intval($params['quantity']) : 0
                );
                
            case 'remove':
                return $this->removeProduct(
                    isset($params['product_id']) ? intval($params['product_id']) : 0
                );
                
            case 'update':
                return $this->updateProductQuantity(
                    isset($params['product_id']) ? intval($params['product_id']) : 0,
                    isset($params['quantity']) ? intval($params['quantity']) : 0
                );
                
            case 'get_cart':
                return $this->getDetails();
                
            case 'clear':
                return $this->clear();
                
            default:
                return ['success' => false, 'message' => 'Action non reconnue'];
        }
    }
}
