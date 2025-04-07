<?php
/**
 * Gestion des sessions utilisateurs
 * Ce fichier gère l'initialisation des sessions et les fonctions associées
 */

// Démarrer ou reprendre une session
if (session_status() === PHP_SESSION_NONE) {
    // Configuration sécurisée des cookies de session
    session_set_cookie_params([
        'lifetime' => 3600, // 1 heure
        'path' => '/',
        'domain' => '',
        'secure' => true, // Cookies uniquement sur HTTPS
        'httponly' => true, // Protection contre les attaques XSS
        'samesite' => 'Lax' // Protection contre CSRF
    ]);
    
    session_start();
}

/**
 * Vérifie si l'utilisateur est connecté
 * 
 * @return bool True si l'utilisateur est connecté, false sinon
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Vérifie si l'utilisateur connecté est un administrateur
 * 
 * @return bool True si l'utilisateur est un administrateur, false sinon
 */
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

/**
 * Créé une session utilisateur après connexion réussie
 * 
 * @param array $user Données de l'utilisateur (id, nom, email, role)
 * @return void
 */
function createUserSession($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_name'] = $user['nom'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['is_admin'] = ($user['role'] === 'admin');
    
    // Régénérer l'ID de session pour la sécurité (éviter fixation de session)
    session_regenerate_id(true);
}

/**
 * Détruit la session de l'utilisateur (déconnexion)
 * 
 * @return void
 */
function destroyUserSession() {
    // Vider toutes les variables de session
    $_SESSION = [];
    
    // Détruire le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
}

/**
 * Vérifie les permissions de l'utilisateur pour accéder à une page admin
 * Si l'utilisateur n'est pas autorisé, il est redirigé
 * 
 * @param bool $adminOnly Si true, vérifie que l'utilisateur est admin
 * @param string $redirectUrl URL de redirection si non autorisé
 * @return void
 */
function checkPermissions($adminOnly = true, $redirectUrl = '/admin/login.php') {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectUrl);
        exit;
    }
    
    if ($adminOnly && !isAdmin()) {
        header('Location: ' . $redirectUrl);
        exit;
    }
}

/**
 * Définit un message flash dans la session
 * 
 * @param string $message Le message à afficher
 * @param string $type Le type de message (success, danger, warning, info)
 * @return void
 */
function setFlashMessage($message, $type = 'info') {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Récupère et supprime le message flash de la session
 * 
 * @return array|null Le message flash ou null si aucun message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flashMessage = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flashMessage;
    }
    
    return null;
} 