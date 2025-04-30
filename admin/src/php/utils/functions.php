<?php
/**
 * Fichier contenant des fonctions utilitaires génériques pour l'application e-commerce
 */

/**
 * Formate un prix en euros avec 2 décimales
 * @param float $price Prix à formater
 * @return string Prix formaté
 */
function format_price($price) {
    return number_format($price, 2, ',', ' ') . ' €';
}

/**
 * Convertit une date au format MySQL en format français
 * @param string $date Date au format MySQL (YYYY-MM-DD)
 * @return string Date au format français (DD/MM/YYYY)
 */
function format_date($date) {
    if (empty($date)) return '';
    
    $dateObj = new DateTime($date);
    return $dateObj->format('d/m/Y');
}

/**
 * Convertit une date et heure au format MySQL en format français
 * @param string $datetime Date et heure au format MySQL (YYYY-MM-DD HH:MM:SS)
 * @return string Date et heure au format français (DD/MM/YYYY à HH:MM)
 */
function format_datetime($datetime) {
    if (empty($datetime)) return '';
    
    $dateObj = new DateTime($datetime);
    return $dateObj->format('d/m/Y à H:i');
}

/**
 * Tronque un texte à une longueur donnée et ajoute des points de suspension
 * @param string $text Texte à tronquer
 * @param int $length Longueur maximale
 * @return string Texte tronqué
 */
function truncate_text($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . '...';
}

/**
 * Génère un slug à partir d'une chaîne de caractères
 * @param string $text Texte à convertir en slug
 * @return string Slug généré
 */
function slugify($text) {
    // Remplacer les caractères non alphanumériques par des tirets
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Translitération
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Supprimer les caractères indésirables
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Supprimer les tirets en début et fin
    $text = trim($text, '-');
    // Remplacer les multiples tirets par un seul
    $text = preg_replace('~-+~', '-', $text);
    // Convertir en minuscules
    $text = strtolower($text);
    
    return $text;
}

/**
 * Génère un jeton CSRF pour les formulaires
 * @return string Jeton CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si un jeton CSRF est valide
 * @param string $token Jeton CSRF à vérifier
 * @return bool True si le jeton est valide, false sinon
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Obtient le statut d'une commande sous forme de badge HTML
 * @param string $status Statut de la commande
 * @return string Badge HTML avec la couleur appropriée
 */
function get_order_status_badge($status) {
    $badges = [
        'en cours' => 'badge-primary',
        'en attente' => 'badge-warning',
        'livré' => 'badge-success',
        'annulé' => 'badge-danger',
        'remboursé' => 'badge-info',
        'pending' => 'badge-warning',
        'completed' => 'badge-success',
        'processing' => 'badge-primary',
        'cancelled' => 'badge-danger',
        'refunded' => 'badge-info'
    ];
    
    $class = $badges[$status] ?? 'badge-secondary';
    
    return '<span class="badge ' . $class . '">' . htmlspecialchars($status) . '</span>';
}

/**
 * Redirige vers une URL avec un message flash
 * @param string $url URL de redirection
 * @param string $message Message à afficher
 * @param string $type Type de message (success, error, info, warning)
 */
function redirect_with_message($url, $message, $type = 'info') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $url);
    exit;
}

/**
 * Affiche un message flash s'il existe et le supprime
 * @return string HTML du message flash ou chaîne vide
 */
function display_flash_message() {
    if (!isset($_SESSION['flash_message'])) {
        return '';
    }
    
    $message = $_SESSION['flash_message'];
    $type = $_SESSION['flash_type'] ?? 'info';
    
    // Supprimer le message flash après l'avoir affiché
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
    
    $alertClass = 'alert-info';
    switch ($type) {
        case 'success':
            $alertClass = 'alert-success';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            break;
    }
    
    return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($message) . '
                <button type="button" class="close" data-dismiss="alert" aria-label="Fermer">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>';
} 