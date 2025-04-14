<?php
/**
 * Page de détails d'une commande
 * Affiche les informations détaillées d'une commande pour un utilisateur connecté
 */

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../admin/src/php/utils/connexion.php';
require_once __DIR__ . '/../admin/src/php/utils/all_includes.php';

// Configuration de l'encodage
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Titre de la page
$titre_page = 'Détails de la commande';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Sauvegarder l'URL actuelle pour y revenir après connexion
    $_SESSION['redirect_after_login'] = 'index_.php?page=compte&section=commandes';
    
    // Rediriger vers la page de connexion
    header('Location: index_.php?page=login');
    exit;
}

// Initialisation des objets DAO
$pdo = getPDO();
$userDAO = new UserDAO($pdo);
$orderDAO = new OrderDAO($pdo);
$productDAO = new ProductDAO($pdo);

// Récupérer l'ID de la commande depuis l'URL
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Récupérer les détails de la commande
$orderDetails = $orderDAO->findOrderDetailsByUserId($order_id, $_SESSION['user_id']);

// Vérifier si la commande existe et appartient à l'utilisateur
if (!$orderDetails) {
    // Rediriger vers la page des commandes avec un message d'erreur
    $_SESSION['error_message'] = "La commande demandée n'existe pas ou ne vous appartient pas.";
    header('Location: index_.php?page=compte&section=commandes#orders');
    exit;
}

// Récupérer les informations de l'utilisateur
$user = $userDAO->findById($_SESSION['user_id']);

// Récupérer les détails de base de la commande
$order = $orderDetails['order'];
$orderLines = $orderDetails['lines'];
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mb-0">Détails de la commande #<?= $order_id ?></h1>
        <a href="index_.php?page=compte&section=commandes#orders" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Retour aux commandes
        </a>
    </div>
    
    <div class="row">
        <!-- Informations générales de la commande -->
        <div class="col-lg-12 mb-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Informations de la commande</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Numéro de commande :</strong> #<?= $order['id'] ?></p>
                            <p><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($order['date_commande'])) ?></p>
                            <p><strong>Statut :</strong> 
                                <?php 
                                $badge_class = 'bg-warning';
                                switch ($order['statut']) {
                                    case 'livré':
                                        $badge_class = 'bg-success';
                                        break;
                                    case 'en cours':
                                        $badge_class = 'bg-info';
                                        break;
                                    case 'annulé':
                                        $badge_class = 'bg-danger';
                                        break;
                                    default:
                                        $badge_class = 'bg-secondary';
                                }
                                ?>
                                <span class="badge <?= $badge_class ?>"><?= ucfirst($order['statut']) ?></span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Client :</strong> <?= htmlspecialchars($user->nom) ?></p>
                            <p><strong>Email :</strong> <?= htmlspecialchars($user->email) ?></p>
                            <p><strong>Téléphone :</strong> <?= htmlspecialchars($user->telephone ?? 'Non renseigné') ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Produits de la commande -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Produits commandés</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th class="text-end">Prix unitaire</th>
                                    <th class="text-center">Quantité</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderLines as $line): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($line['image_principale'])): ?>
                                                    <img src="<?= htmlspecialchars($line['image_principale']) ?>" alt="<?= htmlspecialchars($line['titre']) ?>" class="img-thumbnail me-3" style="width: 50px; height: 50px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light me-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($line['titre']) ?></h6>
                                                    <small class="text-muted">Réf: #<?= $line['produit_id'] ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end"><?= number_format($line['prix_unitaire'], 2, ',', ' ') ?> €</td>
                                        <td class="text-center"><?= $line['quantite'] ?></td>
                                        <td class="text-end"><?= number_format($line['prix_unitaire'] * $line['quantite'], 2, ',', ' ') ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Récapitulatif et actions -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Récapitulatif</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total</span>
                        <span class="fw-bold"><?= number_format($order['montant_total'], 2, ',', ' ') ?> €</span>
                    </div>
                    
                    <hr>
                    
                    <?php if ($order['statut'] === 'en attente' || $order['statut'] === 'en cours'): ?>
                        <form method="post" action="index_.php?page=compte&section=commandes">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <input type="hidden" name="action" value="change_status">
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-2"></i> Marquer comme livré
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i> Cette commande est marquée comme <strong><?= ucfirst($order['statut']) ?></strong>.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 