<?php
/**
 * Crée une nouvelle commande et ajoute les produits du panier
 * @param PDO $pdo Instance de connexion PDO
 * @param int $utilisateurId ID de l'utilisateur
 * @param array $panier Contenu du panier avec IDs produits, quantités et prix
 * @return int|false ID de la commande créée ou false en cas d'erreur
 */
function createOrder($pdo, $utilisateurId, $panier) {
    if (empty($panier)) {
        return false;
    }
    
    // Calcul du montant total
    $montantTotal = 0;
    foreach ($panier as $item) {
        $montantTotal += $item['prix'] * $item['quantite'];
    }
    
    try {
        // Début de la transaction
        $pdo->beginTransaction();
        
        // Création de la commande en utilisant la fonction PL/pgSQL
        $stmt = $pdo->prepare("SELECT create_order(:utilisateur_id, :montant_total)");
        $stmt->execute([
            'utilisateur_id' => $utilisateurId,
            'montant_total' => $montantTotal
        ]);
        
        $orderId = $stmt->fetchColumn();
        
        // Ajout des lignes de commande en utilisant la fonction PL/pgSQL
        foreach ($panier as $item) {
            $stmt = $pdo->prepare("SELECT add_order_line(:order_id, :produit_id, :quantite, :prix_unitaire)");
            $stmt->execute([
                'order_id' => $orderId,
                'produit_id' => $item['id'],
                'quantite' => $item['quantite'],
                'prix_unitaire' => $item['prix']
            ]);
        }
        
        // Validation de la transaction
        $pdo->commit();
        return $orderId;
    } catch (PDOException $e) {
        // Annulation de la transaction en cas d'erreur
        $pdo->rollBack();
        error_log("Erreur lors de la création de la commande: " . $e->getMessage());
        return false;
    }
}

/**
 * Récupère les commandes d'un utilisateur
 * @param PDO $pdo Instance de connexion PDO
 * @param int $utilisateurId ID de l'utilisateur
 * @return array Liste des commandes
 */
function getUserOrders($pdo, $utilisateurId) {
    $query = "SELECT o.*, 
              (SELECT COUNT(*) FROM order_lines WHERE order_id = o.id) as nb_produits,
              (SELECT string_agg(p.titre, ', ') 
               FROM order_lines ol 
               JOIN products p ON ol.produit_id = p.id 
               WHERE ol.order_id = o.id) as produits
              FROM orders o 
              WHERE o.utilisateur_id = :utilisateur_id 
              ORDER BY o.date_commande DESC";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['utilisateur_id' => $utilisateurId]);
    return $stmt->fetchAll();
}

/**
 * Récupère le détail d'une commande
 * @param PDO $pdo Instance de connexion PDO
 * @param int $orderId ID de la commande
 * @param int $utilisateurId ID de l'utilisateur (pour sécurité)
 * @return array|false Détails de la commande ou false si non trouvée
 */
function getOrderDetails($pdo, $orderId, $utilisateurId) {
    // Récupère les informations de base de la commande
    $query = "SELECT * FROM orders 
              WHERE id = :id AND utilisateur_id = :utilisateur_id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([
        'id' => $orderId,
        'utilisateur_id' => $utilisateurId
    ]);
    $order = $stmt->fetch();
    
    if (!$order) {
        return false;
    }
    
    // Récupère les lignes de la commande avec les détails des produits
    $queryLines = "SELECT ol.*, p.titre, p.image_principale 
                   FROM order_lines ol 
                   JOIN products p ON ol.produit_id = p.id 
                   WHERE ol.order_id = :order_id";
    $stmtLines = $pdo->prepare($queryLines);
    $stmtLines->execute(['order_id' => $orderId]);
    $order['lignes'] = $stmtLines->fetchAll();
    
    // Récupère les informations de paiement
    $queryPayment = "SELECT * FROM payments WHERE order_id = :order_id";
    $stmtPayment = $pdo->prepare($queryPayment);
    $stmtPayment->execute(['order_id' => $orderId]);
    $order['paiement'] = $stmtPayment->fetch();
    
    return $order;
}

/**
 * Enregistre un paiement pour une commande
 * @param PDO $pdo Instance de connexion PDO
 * @param int $orderId ID de la commande
 * @param string $modePaiement Mode de paiement (ex: 'carte', 'paypal')
 * @param string $reference Référence de la transaction
 * @return bool True si le paiement a été enregistré, false sinon
 */
function registerPayment($pdo, $orderId, $modePaiement, $reference = null) {
    try {
        $query = "INSERT INTO payments (order_id, mode_paiement, reference_transaction, statut) 
                  VALUES (:order_id, :mode_paiement, :reference, 'réussi')";
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute([
            'order_id' => $orderId,
            'mode_paiement' => $modePaiement,
            'reference' => $reference
        ]);
        
        if ($result) {
            // Mise à jour du statut de la commande
            $updateOrder = "UPDATE orders SET statut = 'payée' WHERE id = :id";
            $stmtUpdate = $pdo->prepare($updateOrder);
            return $stmtUpdate->execute(['id' => $orderId]);
        }
        
        return false;
    } catch (PDOException $e) {
        error_log("Erreur lors de l'enregistrement du paiement: " . $e->getMessage());
        return false;
    }
} 