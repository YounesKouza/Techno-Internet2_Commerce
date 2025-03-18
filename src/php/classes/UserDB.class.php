<?php
// src/php/classes/UserDB.class.php
require_once 'User.class.php';
class UserDB {
    private $pdo;
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            return new User($data['id'], $data['nom'], $data['email'], $data['role']);
        }
        return null;
    }
}
?>
