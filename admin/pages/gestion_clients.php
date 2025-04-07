<?php
/**
 * Page de gestion des clients
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
$user_detail = null;
$user_orders = [];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Recherche
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête SQL avec filtres
$query = "SELECT * FROM users WHERE role = 'client'";
$params = [];

if (!empty($search)) {
    $query .= " AND (nom ILIKE ? OR email ILIKE ? OR telephone ILIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

// Comptage du nombre total de clients
$count_query = str_replace("*", "COUNT(*) as count", $query);
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_rows = $stmt->fetch()['count'];
$total_pages = ceil($total_rows / $limit);

// Ajout de l'ordre et de la pagination à la requête finale
$query .= " ORDER BY date_inscription DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Exécution de la requête
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Traitement des actions
if (isset($_GET['action']) && isset($_GET['user_id'])) {
    $user_id = intval($_GET['user_id']);
    $action = $_GET['action'];
    
    // Vérification de l'existence du client
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'client'");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $error = "Le client n'existe pas";
    } else {
        try {
            switch ($action) {
                case 'view':
                    // Récupération des détails du client
                    $user_detail = $user;
                    
                    // Récupération des commandes du client
                    $stmt = $pdo->prepare("
                        SELECT 
                            o.id, 
                            o.date_commande, 
                            o.montant_total, 
                            o.statut,
                            COUNT(ol.id) AS nb_produits
                        FROM orders o
                        LEFT JOIN order_lines ol ON o.id = ol.order_id
                        WHERE o.utilisateur_id = ?
                        GROUP BY o.id, o.date_commande, o.montant_total, o.statut
                        ORDER BY o.date_commande DESC
                    ");
                    $stmt->execute([$user_id]);
                    $user_orders = $stmt->fetchAll();
                    break;
                    
                case 'delete':
                    // Vérification si le client a des commandes
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE utilisateur_id = ?");
                    $stmt->execute([$user_id]);
                    $has_orders = $stmt->fetchColumn() > 0;
                    
                    if ($has_orders && !isset($_GET['force'])) {
                        $error = "Ce client a des commandes associées. Utilisez la suppression forcée pour supprimer quand même.";
                    } else {
                        // Si suppression forcée, on supprime d'abord les commandes et lignes de commande
                        if ($has_orders) {
                            // Récupération des commandes du client
                            $stmt = $pdo->prepare("SELECT id FROM orders WHERE utilisateur_id = ?");
                            $stmt->execute([$user_id]);
                            $order_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
                            
                            // Suppression des lignes de commande pour chaque commande
                            foreach ($order_ids as $order_id) {
                                $stmt = $pdo->prepare("DELETE FROM order_lines WHERE order_id = ?");
                                $stmt->execute([$order_id]);
                                
                                // Suppression des paiements
                                $stmt = $pdo->prepare("DELETE FROM payments WHERE order_id = ?");
                                $stmt->execute([$order_id]);
                            }
                            
                            // Suppression des commandes
                            $stmt = $pdo->prepare("DELETE FROM orders WHERE utilisateur_id = ?");
                            $stmt->execute([$user_id]);
                        }
                        
                        // Suppression du client
                        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                        $stmt->execute([$user_id]);
                        
                        $success = "Le client a été supprimé avec succès" . ($has_orders ? " (avec toutes ses commandes)" : "");
                    }
                    break;
                    
                case 'update':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $nom = $_POST['nom'] ?? '';
                        $email = $_POST['email'] ?? '';
                        $telephone = $_POST['telephone'] ?? '';
                        $adresse = $_POST['adresse'] ?? '';
                        
                        // Validation des données
                        if (empty($nom)) {
                            $error = "Le nom est obligatoire";
                        } elseif (empty($email)) {
                            $error = "L'email est obligatoire";
                        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $error = "L'email n'est pas valide";
                        } else {
                            // Vérification si l'email existe déjà (sauf pour le client actuel)
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND id != ?");
                            $stmt->execute([$email, $user_id]);
                            if ($stmt->fetchColumn() > 0) {
                                $error = "Cet email est déjà utilisé par un autre utilisateur";
                            } else {
                                // Mise à jour du client
                                $stmt = $pdo->prepare("
                                    UPDATE users 
                                    SET nom = ?, email = ?, telephone = ?, adresse = ?
                                    WHERE id = ?
                                ");
                                $stmt->execute([$nom, $email, $telephone, $adresse, $user_id]);
                                
                                // Récupération des données mises à jour
                                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                                $stmt->execute([$user_id]);
                                $user_detail = $stmt->fetch();
                                
                                $success = "Les informations du client ont été mises à jour avec succès";
                            }
                        }
                    } else {
                        $user_detail = $user;
                    }
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
    <title>Gestion des clients | Administration Furniture</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/Exos/Techno-internet2_commerce/admin/public/css/style.css">
</head>
<body class="admin-interface">
    <div class="container-fluid">
        <div class="row">
            <?php generate_sidebar('customers'); ?>
            
            <!-- Contenu principal -->
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Gestion des clients</h1>
                    <div>
                        <span class="me-3">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                        <a href="disconnect.php" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
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
                
                <?php if (isset($_GET['action']) && $_GET['action'] === 'update' && isset($user_detail)): ?>
                    <!-- Formulaire de modification du client -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Modifier le client</h5>
                            <a href="gestion_clients.php" class="btn btn-sm btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                            </a>
                        </div>
                        <div class="card-body">
                            <form action="gestion_clients.php?action=update&user_id=<?= $user_detail['id'] ?>" method="post">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($user_detail['nom']) ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user_detail['email']) ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($user_detail['telephone'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="adresse" name="adresse" rows="3"><?= htmlspecialchars($user_detail['adresse'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <a href="gestion_clients.php" class="btn btn-secondary me-2">Annuler</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php elseif (isset($_GET['action']) && $_GET['action'] === 'view' && isset($user_detail)): ?>
                    <!-- Détail du client -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h2>Détails du client</h2>
                            <a href="gestion_clients.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i> Retour à la liste
                            </a>
                        </div>
                        
                        <div class="row">
                            <div class="col-lg-4 mb-4">
                                <!-- Informations du client -->
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-transparent">
                                        <h5 class="mb-0">Informations personnelles</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-user-circle fa-3x text-secondary"></i>
                                            </div>
                                            <div>
                                                <h4 class="mb-1"><?= htmlspecialchars($user_detail['nom']) ?></h4>
                                                <div class="text-muted"><?= htmlspecialchars($user_detail['email']) ?></div>
                                            </div>
                                        </div>
                                        
                                        <ul class="list-group list-group-flush">
                                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                                <span class="text-muted">Date d'inscription</span>
                                                <span><?= date('d/m/Y H:i', strtotime($user_detail['date_inscription'])) ?></span>
                                            </li>
                                            
                                            <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                                <span class="text-muted">Téléphone</span>
                                                <span><?= !empty($user_detail['telephone']) ? htmlspecialchars($user_detail['telephone']) : '<em>Non renseigné</em>' ?></span>
                                            </li>
                                            
                                            <?php if (!empty($user_detail['adresse'])): ?>
                                                <li class="list-group-item px-0 py-2">
                                                    <div class="text-muted mb-1">Adresse</div>
                                                    <div><?= nl2br(htmlspecialchars($user_detail['adresse'])) ?></div>
                                                </li>
                                            <?php else: ?>
                                                <li class="list-group-item px-0 py-2 d-flex justify-content-between">
                                                    <span class="text-muted">Adresse</span>
                                                    <span><em>Non renseignée</em></span>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                        
                                        <div class="mt-3 d-flex">
                                            <a href="gestion_clients.php?action=update&user_id=<?= $user_detail['id'] ?>" class="btn btn-outline-primary me-2">
                                                <i class="fas fa-edit me-1"></i> Modifier
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                                <i class="fas fa-trash me-1"></i> Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-lg-8 mb-4">
                                <!-- Commandes du client -->
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-transparent">
                                        <h5 class="mb-0">Commandes du client</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <?php if (empty($user_orders)): ?>
                                            <div class="p-4 text-center">
                                                <div class="mb-3">
                                                    <i class="fas fa-shopping-cart fa-3x text-muted"></i>
                                                </div>
                                                <p class="mb-0">Ce client n'a pas encore passé de commande.</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Commande #</th>
                                                            <th>Date</th>
                                                            <th>Articles</th>
                                                            <th>Montant</th>
                                                            <th>Statut</th>
                                                            <th class="text-end">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($user_orders as $order): ?>
                                                            <tr>
                                                                <td><?= $order['id'] ?></td>
                                                                <td><?= date('d/m/Y H:i', strtotime($order['date_commande'])) ?></td>
                                                                <td><?= $order['nb_produits'] ?> article(s)</td>
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
                                                                    <span class="badge bg-<?= $status_class ?>">
                                                                        <?= ucfirst($order['statut']) ?>
                                                                    </span>
                                                                </td>
                                                                <td class="text-end">
                                                                    <a href="gestion_commandes.php?action=view&order_id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
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
                        </div>
                    </div>
                    
                    <!-- Modal de confirmation de suppression -->
                    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Confirmer la suppression</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Êtes-vous sûr de vouloir supprimer le client "<?= htmlspecialchars($user_detail['nom']) ?>" ?</p>
                                    
                                    <?php if (!empty($user_orders)): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Ce client a <?= count($user_orders) ?> commande(s) associée(s). La suppression entraînera également la suppression de ces commandes.
                                        </div>
                                    <?php endif; ?>
                                    
                                    <p class="text-danger mb-0">Cette action est irréversible.</p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                    <a href="gestion_clients.php?action=delete&user_id=<?= $user_detail['id'] ?>&force=1" class="btn btn-danger">Supprimer</a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Liste des clients -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-transparent">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Liste des clients</h5>
                                <span class="badge bg-primary"><?= $total_rows ?> client(s)</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Formulaire de recherche -->
                            <form action="gestion_clients.php" method="get" class="row mb-4">
                                <div class="col-md-6 col-lg-4">
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="search" placeholder="Rechercher un client..." value="<?= htmlspecialchars($search) ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        
                                        <?php if (!empty($search)): ?>
                                            <a href="gestion_clients.php" class="btn btn-outline-secondary">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-text">
                                        Recherche par nom, email ou téléphone
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Tableau des clients -->
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>ID</th>
                                            <th>Nom</th>
                                            <th>Email</th>
                                            <th>Téléphone</th>
                                            <th>Date d'inscription</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="6" class="text-center py-3">Aucun client trouvé</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?= $user['id'] ?></td>
                                                    <td><?= htmlspecialchars($user['nom']) ?></td>
                                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                                    <td><?= !empty($user['telephone']) ? htmlspecialchars($user['telephone']) : '<em class="text-muted">Non renseigné</em>' ?></td>
                                                    <td><?= date('d/m/Y H:i', strtotime($user['date_inscription'])) ?></td>
                                                    <td class="text-end">
                                                        <a href="gestion_clients.php?action=view&user_id=<?= $user['id'] ?>" class="btn btn-sm btn-primary" title="Voir le détail">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="gestion_clients.php?action=update&user_id=<?= $user['id'] ?>" class="btn btn-sm btn-info text-white" title="Modifier">
                                                            <i class="fas fa-edit"></i>
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
                                <nav aria-label="Pagination des clients" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="gestion_clients.php?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                        
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                                <a class="page-link" href="gestion_clients.php?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                                                    <?= $i ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                                            <a class="page-link" href="gestion_clients.php?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
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
