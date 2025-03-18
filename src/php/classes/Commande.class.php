<?php
// src/php/classes/Commande.class.php
class Commande {
    private $id;
    private $utilisateur_id;
    private $montant_total;
    private $date_commande;
    private $statut;
    public function __construct($id, $utilisateur_id, $montant_total, $date_commande, $statut) {
        $this->id = $id;
        $this->utilisateur_id = $utilisateur_id;
        $this->montant_total = $montant_total;
        $this->date_commande = $date_commande;
        $this->statut = $statut;
    }
    // Getters...
}
?>
