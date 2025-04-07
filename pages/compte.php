<?php
/**
 * Page compte utilisateur
 * Affiche les informations du compte et permet de gérer le profil
 */

// Inclusion du fichier de connexion
require_once __DIR__ . '/../admin/src/php/utils/connexion.php';

// Force l'encodage en UTF-8
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Sauvegarder l'URL actuelle pour y revenir après connexion
    $_SESSION['redirect_after_login'] = 'index_.php?page=compte';
    
    // Rediriger vers la page de connexion
    header('Location: index_.php?page=login');
    exit;
}

// Récupération des informations de l'utilisateur
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    // L'utilisateur n'existe plus en base de données
    // On le déconnecte et redirige vers la page de connexion
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['role']);
    
    header('Location: index_.php?page=login');
    exit;
}

// Récupération des commandes de l'utilisateur
$orders_stmt = $pdo->prepare("
    SELECT o.*, COUNT(ol.id) as nb_products 
    FROM orders o
    LEFT JOIN order_lines ol ON o.id = ol.order_id
    WHERE o.utilisateur_id = ?
    GROUP BY o.id
    ORDER BY o.date_commande DESC
");
$orders_stmt->execute([$_SESSION['user_id']]);
$orders = $orders_stmt->fetchAll();

// Titre de la page
$titre_page = 'Mon compte';

// Traitement du formulaire de mise à jour du profil
$success_msg = '';
$error_msg = '';

// Traitement du changement de statut de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_status') {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    
    if ($order_id > 0) {
        // Vérifier que la commande appartient bien à l'utilisateur
        $check_order = $pdo->prepare("SELECT id, statut FROM orders WHERE id = ? AND utilisateur_id = ?");
        $check_order->execute([$order_id, $_SESSION['user_id']]);
        $order_data = $check_order->fetch();
        
        if ($order_data && ($order_data['statut'] === 'en attente' || $order_data['statut'] === 'en cours')) {
            // Mise à jour du statut
            $update_status = $pdo->prepare("UPDATE orders SET statut = 'livré' WHERE id = ?");
            
            if ($update_status->execute([$order_id])) {
                $success_msg = 'Le statut de la commande #' . $order_id . ' a été mis à jour avec succès';
                
                // Rafraîchir la liste des commandes
                $orders_stmt->execute([$_SESSION['user_id']]);
                $orders = $orders_stmt->fetchAll();
            } else {
                $error_msg = 'Une erreur est survenue lors de la mise à jour du statut de la commande';
            }
        } else {
            $error_msg = 'Cette commande n\'existe pas ou ne vous appartient pas';
        }
    } else {
        $error_msg = 'Identifiant de commande invalide';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';
    
    // Validation des champs
    if (empty($nom) || empty($email)) {
        $error_msg = 'Les champs nom et email sont obligatoires';
    } else {
        // Vérifier si l'email est déjà utilisé par un autre utilisateur
        $check_email = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->execute([$email, $_SESSION['user_id']]);
        
        if ($check_email->rowCount() > 0) {
            $error_msg = 'Cet email est déjà utilisé par un autre compte';
        } else {
            // Mise à jour des informations
            $update = $pdo->prepare("
                UPDATE users 
                SET nom = ?, email = ?, telephone = ?, adresse = ?
                WHERE id = ?
            ");
            
            if ($update->execute([$nom, $email, $telephone, $adresse, $_SESSION['user_id']])) {
                $success_msg = 'Votre profil a été mis à jour avec succès';
                
                // Rafraîchir les informations de l'utilisateur
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } else {
                $error_msg = 'Une erreur est survenue lors de la mise à jour de votre profil';
            }
        }
    }
}
?>

<div class="container py-4">
    <!-- En-tête avec titre et affichage des messages -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="border-bottom pb-2 mb-3">Mon compte</h1>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i> <?= $error_msg ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Contenu principal -->
    <div class="row">
        <!-- Menu latéral -->
        <div class="col-lg-3 mb-4">
            <div class="list-group">
                <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                    <i class="fas fa-user me-2"></i> Mon profil
                </a>
                <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="fas fa-shopping-bag me-2"></i> Mes commandes
                </a>
                <a href="#addresses" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="fas fa-map-marker-alt me-2"></i> Mes adresses
                </a>
                <a href="#settings" class="list-group-item list-group-item-action" data-bs-toggle="list">
                    <i class="fas fa-cog me-2"></i> Paramètres
                </a>
                <a href="index_.php?page=deconnexion" class="list-group-item list-group-item-action text-danger">
                    <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                </a>
            </div>
        </div>
        
        <!-- Contenu des onglets -->
        <div class="col-lg-9">
            <div class="tab-content">
                <!-- Onglet Profil -->
                <div class="tab-pane fade show active" id="profile">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Informations personnelles</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nom" class="form-label">Nom complet</label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Adresse email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="telephone" class="form-label">Téléphone</label>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <label for="adresse" class="form-label">Adresse complète</label>
                                        <textarea class="form-control" id="adresse" name="adresse" rows="3"><?= htmlspecialchars($user['adresse'] ?? '') ?></textarea>
                                    </div>
                                </div>
                                
                                <div class="mt-3">
                                    <input type="hidden" name="update_profile" value="1">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Enregistrer les modifications
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Commandes -->
                <div class="tab-pane fade" id="orders">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Historique de mes commandes</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                    <p>Vous n'avez pas encore passé de commande.</p>
                                    <a href="index_.php?page=catalogue" class="btn btn-outline-primary">
                                        Explorer le catalogue
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>N° de commande</th>
                                                <th>Date</th>
                                                <th>Articles</th>
                                                <th>Montant</th>
                                                <th>Statut</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>#<?= $order['id'] ?></td>
                                                    <td><?= date('d/m/Y', strtotime($order['date_commande'])) ?></td>
                                                    <td><?= $order['nb_products'] ?> article(s)</td>
                                                    <td><?= number_format($order['montant_total'], 2, ',', ' ') ?> €</td>
                                                    <td>
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
                                                    </td>
                                                    <td>
                                                        <?php if ($order['statut'] === 'en attente' || $order['statut'] === 'en cours'): ?>
                                                        <form method="post" style="display: inline-block;" action="index_.php?page=compte">
                                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                            <input type="hidden" name="action" value="change_status">
                                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Marquer comme livré">
                                                                <i class="fas fa-check"></i> Marquer comme livré
                                                            </button>
                                                        </form>
                                                        <?php else: ?>
                                                        <span class="text-muted"><i class="fas fa-check-circle"></i> Commande traitée</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Adresses -->
                <div class="tab-pane fade" id="addresses">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Mes adresses</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border">
                                        <div class="card-body">
                                            <h6 class="card-subtitle mb-3 text-muted">Adresse principale</h6>
                                            
                                            <?php if (!empty($user['adresse'])): ?>
                                                <address>
                                                    <?= nl2br(htmlspecialchars($user['adresse'])) ?>
                                                </address>
                                                
                                                <div class="mt-3">
                                                    <a href="#" class="btn btn-sm btn-outline-primary me-2">
                                                        <i class="fas fa-edit"></i> Modifier
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <p class="text-muted">Aucune adresse enregistrée</p>
                                                <a href="#" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-plus"></i> Ajouter une adresse
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-lg-6 mb-4">
                                    <div class="card h-100 border">
                                        <div class="card-body text-center d-flex flex-column justify-content-center">
                                            <i class="fas fa-plus-circle fa-3x text-muted mb-3"></i>
                                            <p>Ajouter une nouvelle adresse</p>
                                            <a href="#" class="btn btn-outline-primary">
                                                Ajouter
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Onglet Paramètres -->
                <div class="tab-pane fade" id="settings">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Paramètres du compte</h5>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-3">Changer de mot de passe</h6>
                            <form action="" method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="current_password" class="form-label">Mot de passe actuel</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirmez le mot de passe</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-primary">
                                    <i class="fas fa-lock me-2"></i> Changer le mot de passe
                                </button>
                            </form>
                            
                            <hr class="my-4">
                            
                            <h6 class="text-danger mb-3">Zone dangereuse</h6>
                            <p class="text-muted">Attention, la suppression de votre compte est irréversible et entraînera la perte de toutes vos données.</p>
                            
                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                <i class="fas fa-trash-alt me-2"></i> Supprimer mon compte
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation de suppression de compte -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.</p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> 
                    Toutes vos données personnelles, commandes et préférences seront définitivement supprimées.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form action="" method="post">
                    <input type="hidden" name="delete_account" value="1">
                    <button type="submit" class="btn btn-danger">Confirmer la suppression</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activer le bon onglet en fonction du fragment d'URL
    const hash = window.location.hash;
    if (hash) {
        const triggerEl = document.querySelector(`a[href="${hash}"]`);
        if (triggerEl) {
            new bootstrap.Tab(triggerEl).show();
        }
    }
    
    // Gérer les clics sur les onglets pour mettre à jour l'URL
    const tabLinks = document.querySelectorAll('.list-group-item[data-bs-toggle="list"]');
    tabLinks.forEach(tabLink => {
        tabLink.addEventListener('shown.bs.tab', event => {
            window.location.hash = event.target.getAttribute('href');
        });
    });
});
</script>
