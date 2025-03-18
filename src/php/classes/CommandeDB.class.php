<?php
// src/php/classes/CommandeDB.class.php
require_once 'Commande.class.php';
class CommandeDB {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function createCommande($utilisateur_id, $montant_total) {
        $stmt = $this->pdo->prepare("SELECT create_order(:utilisateur_id, :montant_total) AS order_id");
        $stmt->execute(['utilisateur_id' => $utilisateur_id, 'montant_total' => $montant_total]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        return $data['order_id'];
    }
    public function addOrderLine($order_id, $produit_id, $quantite, $prix_unitaire) {
        $stmt = $this->pdo->prepare("SELECT add_order_line(:order_id, :produit_id, :quantite, :prix_unitaire)");
        $stmt->execute(['order_id' => $order_id, 'produit_id' => $produit_id, 'quantite' => $quantite, 'prix_unitaire' => $prix_unitaire]);
    }
}
?>
