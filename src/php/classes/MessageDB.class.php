<?php
// src/php/classes/MessageDB.class.php
require_once 'Message.class.php';
class MessageDB {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function saveMessage($nom, $email, $sujet, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO messages (nom, email, sujet, message) VALUES (:nom, :email, :sujet, :message)");
        $stmt->execute(['nom' => $nom, 'email' => $email, 'sujet' => $sujet, 'message' => $message]);
    }
    public function getAllMessages() {
        $stmt = $this->pdo->query("SELECT * FROM messages ORDER BY id DESC");
        $messages = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $messages[] = new Message($row['id'], $row['nom'], $row['email'], $row['sujet'], $row['message']);
        }
        return $messages;
    }
}
?>
