<?php
// src/php/classes/Message.class.php
class Message {
    private $id;
    private $nom;
    private $email;
    private $sujet;
    private $message;
    public function __construct($id, $nom, $email, $sujet, $message) {
        $this->id = $id;
        $this->nom = $nom;
        $this->email = $email;
        $this->sujet = $sujet;
        $this->message = $message;
    }
    // Getters...
}
?>
