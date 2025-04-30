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
        if (!isset($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Vérifier si l'email existe déjà
        if ($this->findByEmail($data['email'])) {
            return false;
        }

        try {
            // Hachage du mot de passe s'il est fourni
            if (isset($data['mot_de_passe']) && !empty($data['mot_de_passe'])) {
                $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
            }

            // Utiliser la fonction PostgreSQL create_user
            $query = "SELECT create_user(:nom, :email, :mot_de_passe, :role, :adresse, :telephone) AS user_id";
            
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':nom', $data['nom'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'], PDO::PARAM_STR);
            $stmt->bindValue(':mot_de_passe', $data['mot_de_passe'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':role', $data['role'] ?? 'client', PDO::PARAM_STR);
            $stmt->bindValue(':adresse', $data['adresse'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':telephone', $data['telephone'] ?? null, PDO::PARAM_STR);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result && isset($result['user_id']) ? (int)$result['user_id'] : false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la création de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Met à jour un utilisateur existant
     * @param int $userId ID de l'utilisateur à mettre à jour
     * @param array $data Nouvelles données
     * @return bool Succès ou échec
     */
    public function update($userId, array $data)
    {
        if (!is_numeric($userId) || $userId <= 0) {
            error_log("UserDAO::update - ID utilisateur invalide: " . $userId);
            return false;
        }

        try {
            // Hachage du mot de passe si fourni et non vide
            if (isset($data['mot_de_passe']) && !empty($data['mot_de_passe'])) {
                $data['mot_de_passe'] = password_hash($data['mot_de_passe'], PASSWORD_DEFAULT);
            }

            // Log des données pour debug
            error_log("UserDAO::update - Données reçues: " . json_encode($data));

            // Utiliser la fonction PostgreSQL update_user
            $query = "SELECT update_user(:id, :nom, :email, :role, :adresse, :telephone) AS success";
            
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':nom', $data['nom'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':email', $data['email'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':role', $data['role'] ?? 'client', PDO::PARAM_STR); // Valeur par défaut 'client'
            $stmt->bindValue(':adresse', $data['adresse'] ?? null, PDO::PARAM_STR);
            $stmt->bindValue(':telephone', $data['telephone'] ?? null, PDO::PARAM_STR);
            
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!($result && isset($result['success']) && $result['success'])) {
                error_log("UserDAO::update - Échec de la mise à jour: " . json_encode($result));
            }
            
            return $result && isset($result['success']) ? (bool)$result['success'] : false;
        } catch (PDOException $e) {
            error_log("Erreur lors de la mise à jour de l'utilisateur: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Compte le nombre d'utilisateurs avec un rôle spécifique
     * @param string $role Rôle d'utilisateur (client, admin, etc.)
     * @return int Nombre d'utilisateurs
     */
    public function countByRole($role)
    {
        // Si on compte les clients, inclure 'client' et 'user'
        if ($role === 'client') {
            $query = "SELECT COUNT(*) as count FROM users WHERE role IN ('client', 'user')";
        } else {
            $query = "SELECT COUNT(*) as count FROM users WHERE role = :role";
        }
        
        try {
            $stmt = $this->_bd->prepare($query);
            if ($role !== 'client') {
                $stmt->bindValue(':role', $role);
            }
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
        // Si on cherche les clients, inclure à la fois 'client' et 'user'
        if ($role === 'client') {
            $query = "SELECT * FROM users WHERE role IN ('client', 'user') ORDER BY date_inscription DESC LIMIT :limit";
        } else {
            $query = "SELECT * FROM users WHERE role = :role ORDER BY date_inscription DESC LIMIT :limit";
        }
        
        try {
            $stmt = $this->_bd->prepare($query);
            if ($role !== 'client') {
                $stmt->bindValue(':role', $role);
            }
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
            // Utiliser la fonction PL/pgSQL pour supprimer l'utilisateur
            $query = "SELECT delete_user(:id) AS success";
            $stmt = $this->_bd->prepare($query);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return isset($result['success']) && $result['success'];
        } catch (PDOException $e) {
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
        // Si on cherche les clients, inclure à la fois 'client' et 'user'
        if ($role === 'client') {
            $query = "SELECT * FROM users WHERE role IN ('client', 'user')";
            $params = [];
        } else {
            $query = "SELECT * FROM users WHERE role = :role";
            $params = [':role' => $role];
        }
        
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
        // Si on compte les clients, inclure 'client' et 'user'
        if ($role === 'client') {
            $query = "SELECT COUNT(*) as count FROM users WHERE role IN ('client', 'user')";
            $params = [];
        } else {
            $query = "SELECT COUNT(*) as count FROM users WHERE role = :role";
            $params = [':role' => $role];
        }
        
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
            // Si on cherche un client, inclure à la fois 'client' et 'user'
            if ($role === 'client') {
                $stmt = $this->_bd->prepare("SELECT * FROM users WHERE id = :id AND role IN ('client', 'user')");
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            } else {
                $stmt = $this->_bd->prepare("SELECT * FROM users WHERE id = :id AND role = :role");
                $stmt->bindValue(':id', $id, PDO::PARAM_INT);
                $stmt->bindValue(':role', $role);
            }
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