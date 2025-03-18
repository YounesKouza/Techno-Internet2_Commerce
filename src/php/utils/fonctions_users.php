<?php
/**
 * Récupère un utilisateur par son email
 * @param PDO $pdo Instance de connexion PDO
 * @param string $email Email de l'utilisateur
 * @return array|false Données de l'utilisateur ou false si non trouvé
 */
function getUserByEmail($pdo, $email) {
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['email' => $email]);
    return $stmt->fetch();
}

/**
 * Récupère un utilisateur par son ID
 * @param PDO $pdo Instance de connexion PDO
 * @param int $id ID de l'utilisateur
 * @return array|false Données de l'utilisateur ou false si non trouvé
 */
function getUserById($pdo, $id) {
    $query = "SELECT * FROM users WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    return $stmt->fetch();
}

/**
 * Crée un nouvel utilisateur
 * @param PDO $pdo Instance de connexion PDO
 * @param string $nom Nom de l'utilisateur
 * @param string $email Email de l'utilisateur
 * @param string $motDePasse Mot de passe (non hashé)
 * @param string $role Rôle de l'utilisateur (default: 'client')
 * @return int|false ID de l'utilisateur créé ou false en cas d'erreur
 */
function createUser($pdo, $nom, $email, $motDePasse, $role = 'client') {
    try {
        $hashedPwd = password_hash($motDePasse, PASSWORD_BCRYPT);
        
        $query = "INSERT INTO users (nom, email, mot_de_passe, role) 
                  VALUES (:nom, :email, :mot_de_passe, :role) 
                  RETURNING id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            'nom' => $nom,
            'email' => $email,
            'mot_de_passe' => $hashedPwd,
            'role' => $role
        ]);
        
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Erreur lors de la création de l'utilisateur: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour les informations d'un utilisateur
 * @param PDO $pdo Instance de connexion PDO
 * @param int $id ID de l'utilisateur
 * @param array $userData Données à mettre à jour (nom, email, adresse, telephone)
 * @return bool True si mise à jour réussie, false sinon
 */
function updateUserInfo($pdo, $id, $userData) {
    try {
        $fields = [];
        $params = ['id' => $id];
        
        // Construction dynamique des champs à mettre à jour
        foreach (['nom', 'email', 'adresse', 'telephone'] as $field) {
            if (isset($userData[$field])) {
                $fields[] = "$field = :$field";
                $params[$field] = $userData[$field];
            }
        }
        
        if (empty($fields)) {
            return false; // Rien à mettre à jour
        }
        
        $query = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $pdo->prepare($query);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
        return false;
    }
}

/**
 * Met à jour le mot de passe d'un utilisateur
 * @param PDO $pdo Instance de connexion PDO
 * @param int $id ID de l'utilisateur
 * @param string $nouveauMotDePasse Nouveau mot de passe (non hashé)
 * @return bool True si mise à jour réussie, false sinon
 */
function updateUserPassword($pdo, $id, $nouveauMotDePasse) {
    try {
        $hashedPwd = password_hash($nouveauMotDePasse, PASSWORD_BCRYPT);
        
        $query = "UPDATE users SET mot_de_passe = :mot_de_passe WHERE id = :id";
        $stmt = $pdo->prepare($query);
        return $stmt->execute([
            'id' => $id,
            'mot_de_passe' => $hashedPwd
        ]);
    } catch (PDOException $e) {
        error_log("Erreur lors de la mise à jour du mot de passe: " . $e->getMessage());
        return false;
    }
}

/**
 * Vérifie si un utilisateur est administrateur
 * @param array $user Données de l'utilisateur
 * @return bool True si l'utilisateur est admin, false sinon
 */
function isAdmin($user) {
    return isset($user['role']) && $user['role'] === 'admin';
} 