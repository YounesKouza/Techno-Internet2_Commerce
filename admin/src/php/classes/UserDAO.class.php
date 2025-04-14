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
     * Cette méthode peut recevoir soit un ID et un tableau de données, soit un tableau contenant l'ID.
     * @param int|array $idOrData ID de l'utilisateur ou tableau contenant l'ID et les données
     * @param array|null $data Données à mettre à jour (peut être null si le premier paramètre est un tableau complet)
     * @return bool Succès ou échec
     */
    public function update($idOrData, array $data = null)
    {
        // Déterminer si on a reçu un ID + données ou juste un tableau avec ID inclus
        if (is_array($idOrData) && isset($idOrData['id'])) {
            $userData = $idOrData;
            $id = $userData['id'];
        } else {
            $id = $idOrData;
            $userData = $data;
        }
        
        // Vérification si on a un ID et des données
        if (empty($id) || !is_array($userData)) {
            return false;
        }
        
        // Cas simple: mise à jour du téléphone uniquement (utilisé dans commande.php)
        if (isset($userData['telephone']) && count($userData) == 2) {
            $query = "UPDATE users SET telephone = :telephone WHERE id = :id";
            try {
                $stmt = $this->_bd->prepare($query);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':telephone', $userData['telephone']);
                return $stmt->execute();
            } catch (PDOException $e) {
                error_log("Erreur lors de la mise à jour du téléphone: " . $e->getMessage());
                return false;
            }
        }
        
        // Préparation de la requête dynamique
        if (isset($userData['telephone']) || isset($userData['adresse'])) {
            // Version simplifiée pour les mises à jour partielles (formulaire d'édition de profil)
            $query = "UPDATE users SET 
                        nom = :nom, 
                        email = :email, 
                        telephone = :telephone, 
                        adresse = :adresse
                      WHERE id = :id";
                      
            try {
                $stmt = $this->_bd->prepare($query);
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':nom', $userData['nom']);
                $stmt->bindValue(':email', $userData['email']);
                $stmt->bindValue(':telephone', $userData['telephone'] ?? '');
                $stmt->bindValue(':adresse', $userData['adresse'] ?? '');
                
                return $stmt->execute();
            } catch (PDOException $e) {
                error_log("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
                return false;
            }
        } else {
            // Version complète pour les mises à jour administratives (avec tous les champs potentiels)
            $fieldsToUpdate = [];
            $params = [':id' => $id];

            foreach ($userData as $key => $value) {
                if ($key !== 'id' && $key !== 'date_inscription') {
                    $fieldsToUpdate[] = "$key = :$key";
                    $params[":$key"] = $value;
                }
            }

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
    }

    /**
     * Compte le nombre d'utilisateurs avec un rôle spécifique
     * @param string $role Rôle d'utilisateur (client, admin, etc.)
     * @return int Nombre d'utilisateurs
     */
    public function countByRole($role)
    {
        $query = "SELECT COUNT(*) as count FROM users WHERE role = :role";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':role', $role);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)$result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des utilisateurs par rôle: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère les derniers utilisateurs inscrits avec un rôle spécifique
     * @param string $role Rôle d'utilisateur (client, admin, etc.)
     * @param int $limit Nombre maximum d'utilisateurs à récupérer
     * @return array Liste des utilisateurs récents
     */
    public function findRecentByRole($role, $limit)
    {
        $query = "SELECT * FROM users WHERE role = :role ORDER BY date_inscription DESC LIMIT :limit";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':role', $role);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $users = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $users[] = new User($data);
            }
            
            return $users;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des utilisateurs récents: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère tous les utilisateurs
     * @param string|null $role Filtrer par rôle (optionnel)
     * @return array Liste des utilisateurs
     */
    public function findAll($role = null)
    {
        $query = "SELECT * FROM users";
        $params = [];
        
        if ($role !== null) {
            $query .= " WHERE role = :role";
            $params[':role'] = $role;
        }
        
        $query .= " ORDER BY nom";
        
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

    /**
     * Supprime un utilisateur
     * @param int $id ID de l'utilisateur
     * @return bool Succès ou échec
     */
    public function delete($id)
    {
        try {
            $this->_bd->beginTransaction();
            
            // Log de l'action pour le débogage
            error_log("Tentative de suppression de l'utilisateur ID: $id");
            
            // Avant la suppression, vérifier si l'utilisateur existe
            $stmt = $this->_bd->prepare("SELECT id FROM users WHERE id = :id");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            if (!$stmt->fetch()) {
                error_log("Utilisateur ID: $id introuvable");
                return false;
            }
            
            // Suppression de l'utilisateur - les contraintes ON DELETE CASCADE
            // dans la base de données devraient gérer la suppression des commandes
            $query = "DELETE FROM users WHERE id = :id";
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $result = $stmt->execute();
            
            if (!$result) {
                throw new PDOException("Échec de la suppression de l'utilisateur");
            }
            
            $this->_bd->commit();
            error_log("Utilisateur ID: $id supprimé avec succès");
            
            return true;
        } catch (PDOException $e) {
            $this->_bd->rollback();
            error_log("Erreur lors de la suppression de l'utilisateur ID: $id: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère le nombre d'utilisateurs inscrits par mois pour une période donnée
     * @param string $startDate Date de début (format Y-m-d)
     * @param string $endDate Date de fin (format Y-m-d)
     * @param string $role Rôle des utilisateurs à récupérer (par défaut 'client')
     * @return array Liste des inscriptions par mois
     */
    public function getUsersByMonth($startDate, $endDate, $role = 'client')
    {
        $query = "SELECT 
                    to_char(date_inscription, 'YYYY-MM') AS month,
                    COUNT(*) AS count
                  FROM users 
                  WHERE date_inscription >= :start_date 
                  AND date_inscription <= :end_date::date + interval '1 day'
                  AND role = :role
                  GROUP BY month
                  ORDER BY month ASC";
        try {
            $stmt = $this->_bd->prepare($query);
            $stmt->bindParam(':start_date', $startDate);
            $stmt->bindParam(':end_date', $endDate);
            $stmt->bindParam(':role', $role);
            $stmt->execute();
            
            $results = [];
            while ($data = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Conversion en objet standard pour uniformité
                $userData = new stdClass();
                $userData->month = $data['month'];
                $userData->count = $data['count'];
                $results[] = $userData;
            }
            
            return $results;
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des utilisateurs par mois: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupère des utilisateurs par rôle avec filtres et pagination
     * @param string $role Rôle des utilisateurs à récupérer
     * @param string $search Terme de recherche
     * @param int $limit Nombre de résultats par page
     * @param int $offset Offset pour la pagination
     * @return array Liste des utilisateurs
     */
    public function findByRoleWithFilters($role, $search = '', $limit = 10, $offset = 0)
    {
        $query = "SELECT * FROM users WHERE role = :role";
        $params = [':role' => $role];
        
        if (!empty($search)) {
            $query .= " AND (nom ILIKE :search OR email ILIKE :search OR telephone ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        $query .= " ORDER BY date_inscription DESC LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        try {
            $stmt = $this->_bd->prepare($query);
            
            // Liaison des paramètres nommés
            foreach ($params as $key => $value) {
                if ($key == ':limit' || $key == ':offset') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération des utilisateurs par rôle avec filtres: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compte le nombre d'utilisateurs par rôle avec filtre de recherche
     * @param string $role Rôle des utilisateurs à compter
     * @param string $search Terme de recherche
     * @return int Nombre total d'utilisateurs
     */
    public function countByRoleWithFilters($role, $search = '')
    {
        $query = "SELECT COUNT(*) as count FROM users WHERE role = :role";
        $params = [':role' => $role];
        
        if (!empty($search)) {
            $query .= " AND (nom ILIKE :search OR email ILIKE :search OR telephone ILIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }
        
        try {
            $stmt = $this->_bd->prepare($query);
            
            // Liaison des paramètres nommés
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            error_log("Erreur lors du comptage des utilisateurs par rôle: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Récupère un utilisateur par son ID et son rôle
     * @param int $id ID de l'utilisateur
     * @param string $role Rôle de l'utilisateur
     * @return array|false Utilisateur trouvé ou false
     */
    public function findByIdAndRole($id, $role)
    {
        try {
            $stmt = $this->_bd->prepare("SELECT * FROM users WHERE id = :id AND role = :role");
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':role', $role);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur lors de la récupération de l'utilisateur #$id avec rôle $role: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifie si un email est déjà utilisé par un autre utilisateur
     * @param string $email Email à vérifier
     * @param int|null $excludeId ID de l'utilisateur à exclure de la vérification
     * @return bool True si l'email est déjà pris, false sinon
     */
    public function isEmailTaken($email, $excludeId = null)
    {
        $query = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = [':email' => $email];
        
        if ($excludeId !== null) {
            $query .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        
        try {
            $stmt = $this->_bd->prepare($query);
            
            foreach ($params as $key => $value) {
                if ($key == ':exclude_id') {
                    $stmt->bindValue($key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification de l'email: " . $e->getMessage());
            return false;
        }
    }
} 