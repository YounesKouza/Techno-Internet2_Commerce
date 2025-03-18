<?php
// src/php/classes/Produit.class.php
class Produit {
    private $id;
    private $titre;
    private $description;
    private $prix;
    private $stock;
    private $categorie_id;
    private $image_principale;
    public function __construct($id, $titre, $description, $prix, $stock, $categorie_id, $image_principale) {
        $this->id = $id;
        $this->titre = $titre;
        $this->description = $description;
        $this->prix = $prix;
        $this->stock = $stock;
        $this->categorie_id = $categorie_id;
        $this->image_principale = $image_principale;
    }
    public function getId() { return $this->id; }
    public function getTitre() { return $this->titre; }
    public function getPrix() { return $this->prix; }
    // Autres getters selon besoin
}
?>
