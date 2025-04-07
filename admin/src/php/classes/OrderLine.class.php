<?php

class OrderLine
{
    private $_attributs = array();
    private $_orderLineDAO;

    public function __construct(array $data = [], $orderLineDAO = null)
    {
        if (!empty($data)) {
            $this->hydrate($data);
        }
        $this->_orderLineDAO = $orderLineDAO;
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
     * Calcule le sous-total de la ligne de commande
     * @return float Sous-total
     */
    public function calculateSubtotal()
    {
        if (isset($this->_attributs['quantite']) && isset($this->_attributs['prix_unitaire'])) {
            return $this->_attributs['quantite'] * $this->_attributs['prix_unitaire'];
        }
        return 0;
    }

    /**
     * Met à jour la quantité de la ligne de commande
     * @param int $quantity Nouvelle quantité
     * @return bool Succès ou échec
     */
    public function updateQuantity($quantity)
    {
        if (!$this->_orderLineDAO) {
            throw new Exception("OrderLineDAO non initialisé");
        }
        if (!isset($this->_attributs['id'])) {
            throw new Exception("ID de ligne de commande non défini");
        }
        
        $result = $this->_orderLineDAO->update($this->_attributs['id'], ['quantite' => $quantity]);
        if ($result) {
            $this->_attributs['quantite'] = $quantity;
        }
        return $result;
    }

    /**
     * Supprime la ligne de commande
     * @return bool Succès ou échec
     */
    public function delete()
    {
        if (!$this->_orderLineDAO) {
            throw new Exception("OrderLineDAO non initialisé");
        }
        if (!isset($this->_attributs['id'])) {
            throw new Exception("ID de ligne de commande non défini");
        }
        
        return $this->_orderLineDAO->delete($this->_attributs['id']);
    }
}
