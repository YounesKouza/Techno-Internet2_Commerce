<?php
/**
 * Fichier de connexion à la base de données PostgreSQL
 * Utilisé pour établir et gérer les connexions à la base de données
 */

// Configuration globale de l'encodage
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Inclusion du fichier de configuration de la base de données
require_once __DIR__ . '/../db/dbPgConnect.php';

/**
 * Fonction qui retourne une instance PDO connectée à la base de données
 * @return PDO Instance PDO connectée à la base de données
 */
function getPDO() {
    static $pdo = null;
    
    if ($pdo === null) {
        global $dsn, $user, $password;
        
        try {
            // Création d'une nouvelle instance PDO avec configuration PostgreSQL
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $pdo = new PDO($dsn, $user, $password, $options);
            
            // Configuration de l'encodage en UTF-8
            $pdo->exec("SET NAMES 'UTF8'");
            $pdo->exec("SET client_encoding TO 'UTF8'");
            
        } catch (PDOException $e) {
            // En cas d'erreur
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        }
    }
    
    return $pdo;
}

/**
 * Exécute une requête SQL et retourne les résultats
 * @param string $query Requête SQL à exécuter
 * @param array $params Paramètres pour la requête préparée
 * @return array Résultats de la requête
 */
function executeQuery($query, $params = []) {
    $pdo = getPDO();
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        die("Erreur lors de l'exécution de la requête : " . $e->getMessage());
    }
}

/**
 * Exécute une requête SQL sans retourner de résultats (INSERT, UPDATE, DELETE)
 * @param string $query Requête SQL à exécuter
 * @param array $params Paramètres pour la requête préparée
 * @return int Nombre de lignes affectées
 */
function executeNonQuery($query, $params = []) {
    $pdo = getPDO();
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch (PDOException $e) {
        die("Erreur lors de l'exécution de la requête : " . $e->getMessage());
    }
}

/**
 * Récupère le dernier ID inséré
 * @return string Dernier ID inséré
 */
function getLastInsertId() {
    $pdo = getPDO();
    return $pdo->lastInsertId();
}

/**
 * Effectue une transaction en base de données
 * @param callable $callback Fonction à exécuter dans la transaction
 * @return mixed Résultat de la fonction callback
 */
function executeTransaction($callback) {
    $pdo = getPDO();
    
    try {
        $pdo->beginTransaction();
        $result = $callback($pdo);
        $pdo->commit();
        return $result;
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Erreur lors de la transaction : " . $e->getMessage());
    }
} 