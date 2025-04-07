<?php
/**
 * Page de gestion des commandes
 */

// Démarrage de la session
session_start();

// Vérification si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirection vers la page de connexion
    header('Location: ../../index_.php?page=login&redirect=admin');
    exit;
}

// Inclusion des fichiers nécessaires
require_once '../src/php/utils/connexion.php';
require_once '../src/php/utils/sidebar.php';

// Initialisation des variables
$pdo = getPDO();
$error = "";
$success = "";
$order_detail = null;
$order_lines = [];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête SQL avec les filtres
$query = "SELECT o.*, u.nom as client_name, u.email as client_email 
          FROM orders o
          LEFT JOIN users u ON o.utilisateur_id = u.id
          WHERE 1=1";

$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.statut = ?";
    $params[] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (u.nom ILIKE ? OR u.email ILIKE ? OR CAST(o.id AS TEXT) LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Comptage du nombre total de commandes
$count_query = str_replace("o.*, u.nom as client_name, u.email as client_email", "COUNT(*) as count", $query);
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetch()['count'];
$total_pages = ceil($total_rows / $limit);

// Ajout de l'ordre et de la pagination à la requête finale
$query .= " ORDER BY o.date_commande DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Exécution de la requête
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Récupération des statuts disponibles pour le filtre
$statuses = $pdo->query("SELECT DISTINCT statut FROM orders ORDER BY statut")->fetchAll(PDO::FETCH_COLUMN);

// Traitement des actions
if (isset($_GET['action']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $action = $_GET['action'];
    
    // Vérification de l'existence de la commande
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        $error = "La commande n'existe pas";
    } else {
        try {
            switch ($action) {
                case 'view':
                    // Récupération des détails de la commande
                    $stmt = $pdo->prepare("
                        SELECT o.*, u.nom as client_name, u.email as client_email, 
                               u.adresse as client_address, u.telephone as client_phone
                        FROM orders o
                        LEFT JOIN users u ON o.utilisateur_id = u.id
                        WHERE o.id = ?
                    ");
                    $stmt->execute([$order_id]);
                    $order_detail = $stmt->fetch();
                    
                    // Récupération des lignes de commande
                    $stmt = $pdo->prepare("
                        SELECT ol.*, p.titre as product_name, p.image_principale as product_image
                        FROM order_lines ol
                        LEFT JOIN products p ON ol.produit_id = p.id
                        WHERE ol.order_id = ?
                    ");
                    $stmt->execute([$order_id]);
                    $order_lines = $stmt->fetchAll();
                    
                    // Récupération des paiements associés
                    $stmt = $pdo->prepare("
                        SELECT * FROM payments WHERE order_id = ?
                    ");
                    $stmt->execute([$order_id]);
                    $payments = $stmt->fetchAll();
                    
                    $order_detail['payments'] = $payments;
                    break;
                    
                case 'update_status':
                    if (isset($_POST['status'])) {
                        $new_status = $_POST['status'];
                        $stmt = $pdo->prepare("UPDATE orders SET statut = ? WHERE id = ?");
                        $stmt->execute([$new_status, $order_id]);
                        
                        // Si le statut est "completed", mettre à jour le statut du paiement
                        if ($new_status === 'completed') {
                            $stmt = $pdo->prepare("UPDATE payments SET statut = 'payé' WHERE order_id = ?");
                            $stmt->execute([$order_id]);
                        }
                        
                        $success = "Le statut de la commande #$order_id a été mis à jour avec succès";
                    }
                    break;
                    
                case 'delete':
                    // Suppression des lignes de commande
                    $stmt = $pdo->prepare("DELETE FROM order_lines WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                    
                    // Suppression des paiements
                    $stmt = $pdo->prepare("DELETE FROM payments WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                    
                    // Suppression de la commande
                    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
                    $stmt->execute([$order_id]);
                    
                    $success = "La commande #$order_id a été supprimée avec succès";
                    break;
            }
        } catch (PDOException $e) {
            $error = "Erreur lors du traitement de l'action : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commandes | Administration Furniture</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/Exos/Techno-internet2_commerce/admin/public/css/style.css">
    
    <style>
        .status-badge {
            min-width: 100px;
            text-align: center;
        }
        
        .order-detail-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .order-summary-card {
            height: 100%;
        }
        
        .product-row {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .product-row:last-child {
            border-bottom: none;
        }
        
        .product-row img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 10px;
        }
        
        .product-details {
            flex-grow: 1;
        }
    </style>
</head>
<body class="admin-interface">
    <div class="container-fluid">
        <div class="row">
            <?php generate_sidebar('orders'); ?>
            
            <!-- Contenu principal -->
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Gestion des commandes</h1>
                    <div>
                        <span class="me-3">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                        <a href="disconnect.php" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                        </a>
                    </div>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($order_detail)): ?>
                    <!-- Détail de la commande -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2>Détails de la commande #<?= $order_detail['id'] ?></h2>
                            <a href="gestion_commandes.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                            </a>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-transparent">
                                        <h5 class="mb-0">Articles commandés</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($order_lines)): ?>
                                            <p class="text-muted">Aucun article dans cette commande</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Produit</th>
                                                            <th>Prix unitaire</th>
                                                            <th>Quantité</th>
                                                            <th class="text-end">Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($order_lines as $line): ?>
                                                            <tr>
                                                                <td>
                                                                    <div class="d-flex align-items-center">
                                                                        <?php if (!empty($line['product_image'])): ?>
                                                                            <img src="../../<?= htmlspecialchars($line['product_image']) ?>" alt="<?= htmlspecialchars($line['product_name']) ?>" class="me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                                        <?php else: ?>
                                                                            <div class="bg-light me-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                                                <i class="fas fa-image text-muted"></i>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        <div>
                                                                            <?= htmlspecialchars($line['product_name']) ?>
                                                                            <div class="small text-muted">ID: <?= $line['produit_id'] ?></div>
                                                                        </div>
                                                                    </div>
                                                                </td>
                                                                <td><?= number_format($line['prix_unitaire'], 2, ',', ' ') ?> €</td>
                                                                <td><?= $line['quantite'] ?></td>
                                                                <td class="text-end"><?= number_format($line['prix_unitaire'] * $line['quantite'], 2, ',', ' ') ?> €</td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                    <tfoot class="table-light">
                                                        <tr>
                                                            <td colspan="3" class="text-end fw-bold">Total</td>
                                                            <td class="text-end fw-bold"><?= number_format($order_detail['montant_total'], 2, ',', ' ') ?> €</td>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Informations de paiement -->
                                <?php if (!empty($order_detail['payments'])): ?>
                                    <div class="card shadow-sm mb-4">
                                        <div class="card-header bg-transparent">
                                            <h5 class="mb-0">Informations de paiement</h5>
                                        </div>
                                        <div class="card-body">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Référence</th>
                                                        <th>Mode de paiement</th>
                                                        <th>Statut</th>
                                                        <th>Date</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($order_detail['payments'] as $payment): ?>
                                                        <tr>
                                                            <td><?= !empty($payment['reference_transaction']) ? htmlspecialchars($payment['reference_transaction']) : 'Non renseigné' ?></td>
                                                            <td><?= htmlspecialchars($payment['mode_paiement']) ?></td>
                                                            <td>
                                                                <?php
                                                                $status_class = 'secondary';
                                                                switch($payment['statut']) {
                                                                    case 'payé': $status_class = 'success'; break;
                                                                    case 'en attente': $status_class = 'warning'; break;
                                                                    case 'refusé': $status_class = 'danger'; break;
                                                                }
                                                                ?>
                                                                <span class="badge bg-<?= $status_class ?>">
                                                                    <?= ucfirst($payment['statut']) ?>
                                                                </span>
                                                            </td>
                                                            <td><?= date('d/m/Y H:i', strtotime($payment['date_paiement'])) ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="col-md-4">
                                <!-- Récapitulatif de la commande -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-transparent">
                                        <h5 class="mb-0">Récapitulatif</h5>
                                    </div>
                                    <div class="card-body">
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span>Numéro de commande</span>
                                                <strong>#<?= $order_detail['id'] ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span>Date</span>
                                                <strong><?= date('d/m/Y H:i', strtotime($order_detail['date_commande'])) ?></strong>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span>Statut</span>
                                                <?php
                                                $status_class = 'secondary';
                                                switch($order_detail['statut']) {
                                                    case 'completed': $status_class = 'success'; break;
                                                    case 'processing': $status_class = 'primary'; break;
                                                    case 'pending': $status_class = 'warning'; break;
                                                    case 'cancelled': $status_class = 'danger'; break;
                                                    case 'en cours': $status_class = 'info'; break;
                                                }
                                                ?>
                                                <span class="badge bg-<?= $status_class ?>">
                                                    <?= ucfirst($order_detail['statut']) ?>
                                                </span>
                                            </li>
                                            <li class="list-group-item d-flex justify-content-between px-0">
                                                <span>Total</span>
                                                <strong><?= number_format($order_detail['montant_total'], 2, ',', ' ') ?> €</strong>
                                            </li>
                                        </ul>
                                        
                                        <form action="gestion_commandes.php?action=update_status&order_id=<?= $order_detail['id'] ?>" method="post" class="mt-3">
                                            <div class="mb-3">
                                                <label for="status" class="form-label">Mettre à jour le statut</label>
                                                <select class="form-select" id="status" name="status">
                                                    <option value="en cours" <?= $order_detail['statut'] === 'en cours' ? 'selected' : '' ?>>En cours</option>
                                                    <option value="completed" <?= $order_detail['statut'] === 'completed' ? 'selected' : '' ?>>Livré</option>
                                                </select>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-save me-1"></i> Mettre à jour
                                            </button>
                                        </form>
                                        
                                        <button type="button" class="btn btn-outline-danger w-100 mt-3" data-bs-toggle="modal" data-bs-target="#deleteOrderModal">
                                            <i class="fas fa-trash me-1"></i> Supprimer la commande
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Informations client -->
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-transparent">
                                        <h5 class="mb-0">Client</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <strong><?= htmlspecialchars($order_detail['client_name']) ?></strong>
                                            <div class="text-muted"><?= htmlspecialchars($order_detail['client_email']) ?></div>
                                            
                                            <?php if (!empty($order_detail['client_phone'])): ?>
                                                <div class="mt-2">
                                                    <i class="fas fa-phone me-1 text-muted"></i> <?= htmlspecialchars($order_detail['client_phone']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php if (!empty($order_detail['client_address'])): ?>
                                            <div class="mt-3">
                                                <h6>Adresse de livraison</h6>
                                                <p class="mb-0"><?= nl2br(htmlspecialchars($order_detail['client_address'])) ?></p>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-3">
                                            <a href="gestion_clients.php?id=<?= $order_detail['utilisateur_id'] ?>" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-user me-1"></i> Voir le profil client
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Modal de confirmation de suppression -->
                    <div class="modal fade" id="deleteOrderModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmer la suppression</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Êtes-vous sûr de vouloir supprimer la commande #<?= $order_detail['id'] ?> ?</p>
                                    <p class="text-danger mb-0">Cette action est irréversible.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <a href="gestion_commandes.php?action=delete&order_id=<?= $order_detail['id'] ?>" class="btn btn-danger">Supprimer</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php else: ?>
                    <!-- Liste des commandes -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Liste des commandes</h5>
                                <span class="badge bg-primary"><?= $total_rows ?> commande(s)</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Filtres de recherche -->
                            <form action="gestion_commandes.php" method="get" class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" placeholder="Rechercher..." value="<?= htmlspecialchars($search) ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <select name="status" class="form-select" onchange="this.form.submit()">
                                        <option value="">Tous les statuts</option>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status ?>" <?= $status_filter === $status ? 'selected' : '' ?>>
                                                <?= ucfirst($status) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <?php if (!empty($search) || !empty($status_filter)): ?>
                                    <div class="col-md-2">
                                        <a href="gestion_commandes.php" class="btn btn-outline-secondary w-100">
                                            <i class="fas fa-times me-1"></i> Réinitialiser
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </form>
                            
                            <!-- Tableau des commandes -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>Client</th>
                                            <th>Date</th>
                                            <th>Montant</th>
                                            <th>Statut</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($orders)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-3">Aucune commande trouvée</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td><?= $order['id'] ?></td>
                                                    <td>
                                                        <?php if (!empty($order['client_name'])): ?>
                                                            <div><?= htmlspecialchars($order['client_name']) ?></div>
                                                            <div class="small text-muted"><?= htmlspecialchars($order['client_email']) ?></div>
                                                        <?php else: ?>
                                                            <em>Client anonyme</em>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('d/m/Y H:i', strtotime($order['date_commande'])) ?></td>
                                                    <td><?= number_format($order['montant_total'], 2, ',', ' ') ?> €</td>
                                                    <td>
                                                        <?php
                                                        $status_class = 'secondary';
                                                        switch($order['statut']) {
                                                            case 'completed': $status_class = 'success'; break;
                                                            case 'processing': $status_class = 'primary'; break;
                                                            case 'pending': $status_class = 'warning'; break;
                                                            case 'cancelled': $status_class = 'danger'; break;
                                                            case 'en cours': $status_class = 'info'; break;
                                                        }
                                                        ?>
                                                        <span class="badge bg-<?= $status_class ?> status-badge">
                                                            <?= ucfirst($order['statut']) ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="gestion_commandes.php?action=view&order_id=<?= $order['id'] ?>" class="btn btn-sm btn-primary" title="Voir le détail">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Pagination des commandes" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="gestion_commandes.php?page=<?= $page - 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                <a class="page-link" href="gestion_commandes.php?page=<?= $i ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="gestion_commandes.php?page=<?= $page + 1 ?>&status=<?= $status_filter ?>&search=<?= urlencode($search) ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Pied de page -->
                <footer class="mt-5 text-center text-muted">
                    <p class="mb-1">Furniture - Administration &copy; <?= date('Y') ?></p>
                    <p class="small">Version 1.0.0</p>
                </footer>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</body>
</html>