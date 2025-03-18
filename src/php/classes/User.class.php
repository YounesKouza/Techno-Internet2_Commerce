<?php
// src/php/classes/User.class.php
class User {
    private $id;
    private $nom;
    private $email;
    private $role;
    public function __construct($id, $nom, $email, $role) {
        $this->id = $id;
        $this->nom = $nom;
        $this->email = $email;
        $this->role = $role;
    }
    public function getId() { return $this->id; }
    public function getNom() { return $this->nom; }
    public function getEmail() { return $this->email; }
    public function getRole() { return $this->role; }
}
?>
