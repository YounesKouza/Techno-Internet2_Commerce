<?php
// src/php/classes/ProduitDB.class.php
require_once 'Produit.class.php';
class ProduitDB {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function getAllActive() {
        $stmt = $this->pdo->query("SELECT * FROM products WHERE actif = TRUE ORDER BY date_creation DESC");
        $products = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $products[] = new Produit($row['id'], $row['titre'], $row['description'], $row['prix'], $row['stock'], $row['categorie_id'], $row['image_principale']);
        }
        return $products;
    }
}
?>
