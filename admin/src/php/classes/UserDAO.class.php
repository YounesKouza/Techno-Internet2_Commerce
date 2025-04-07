<?php

class UserDAO
{
    private $_bd;

    public function __construct($cnx)
    {
        $this->_bd = $cnx;
    }

    /**
     * Récupère un utilisateur par son ID
     * @param int $id ID de l'utilisateur
     * @return User|false Utilisateur trouvé ou false
     */
    public function findById($id)
    {
        $query = "SELECT * FROM users WHERE id = :id";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new User($data);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère un utilisateur par son email
     * @param string $email Email de l'utilisateur
     * @return User|false Utilisateur trouvé ou false
     */
    public function findByEmail($email)
    {
        $query = "SELECT * FROM users WHERE email = :email";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
            
            if ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                return new User($data);
            }
            return false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisateur par email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Authentifie un utilisateur
     * @param string $email Email de l'utilisateur
     * @param string $password Mot de passe en clair
     * @return User|false Utilisateur si authentifié, sinon false
     */
    public function authenticate($email, $password)
    {
        $user = $this->findByEmail($email);
        if ($user && password_verify($password, $user->mot_de_passe)) {
            return $user;
        }
        return false;
    }

    /**
     * Crée un nouvel utilisateur
     * @param array $data Données de l'utilisateur
     * @return int|false ID du nouvel utilisateur ou false si échec
     */
    public function create(array $data)
    {
        // Hasher le mot de passe
        if (isset($data['mot_de_passe'])) {
            $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
        }

        $query = "INSERT INTO users (nom, email, mot_de_passe, role, adresse, telephone) 
                  VALUES (:nom, :email, :mot_de_passe, :role, :adresse, :telephone)";
        
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':nom', $data['nom']);
            $stmt->bindValue(':email', $data['email']);
            $stmt->bindValue(':mot_de_passe', $data['mot_de_passe']);
            $stmt->bindValue(':role', $data['role'] ?? 'client');
            $stmt->bindValue(':adresse', $data['adresse'] ?? null);
            $stmt->bindValue(':telephone', $data['telephone'] ?? null);
            
            $result = $stmt->execute();
            $id = $this->_bd->lastInsertId();
            $this->_bd->commit();
            
            return $result ? $id : false;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la création de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un utilisateur
     * @param int $id ID de l'utilisateur
     * @param array $data Données à mettre à jour
     * @return bool Succès ou échec
     */
    public function update($id, array $data)
    {
        // Préparer les champs à mettre à jour
        $fieldsToUpdate = [];
        $params = [':id' => $id];

        // Construire la requête dynamiquement
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $key !== 'date_inscription') {
                $fieldsToUpdate[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        // Si pas de champs à mettre à jour
        if (empty($fieldsToUpdate)) {
            return false;
        }

        $query = "UPDATE users SET " . implode(', ', $fieldsToUpdate) . " WHERE id = :id";
        
        try {
            $this->_bd->beginTransaction();
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            $result = $stmt->execute();
            $this->_bd->commit();
            
            return $result;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les utilisateurs
     * @param string $role Filtrer par rôle (optionnel)
     * @return array Liste des utilisateurs
     */
    public function findAll($role = null)
    {
        $query = "SELECT * FROM users";
        $params = [];
        
        if ($role) {
            $query .= " WHERE role = :role";
            $params[':role'] = $role;
        }
        
        $query .= " ORDER BY id";
        
        try {
            $stmt = $this->_bd->prepare($query);
            foreach ($params as $param => $val) {
                $stmt->bindValue($param, $val);
            }
            $stmt->execute();
            
            $users = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = new User($data);
            }
            
            return $users;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des utilisateurs: " . $e->getMessage());
            return [];
        }
    }
} 