<?php
include '../src/php/db/dbConnect.php';
include '../src/php/utils/fonctions_users.php';
include '../src/php/utils/fonctions_commandes.php';

// Définition de la constante BASE_URL si non définie
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Exos/Techno-internet2_commerce/Techno-internet2_commerce');
}

// Démarrage de la session
session_start();

// Vérification que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php?status=info&message=" . urlencode("Veuillez vous connecter pour accéder à votre compte."));
    exit;
}

// Récupération des informations de l'utilisateur
$user = getUserById($pdo, $_SESSION['user_id']);
if (!$user) {
    header("Location: deconnexion.php?status=error&message=" . urlencode("Erreur de session. Veuillez vous reconnecter."));
    exit;
}

// Récupération des commandes de l'utilisateur
$orders = getUserOrders($pdo, $_SESSION['user_id']);

// Traitement du formulaire de mise à jour des informations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $success = false;
    
    // Mise à jour des informations personnelles
    if (isset($_POST['update_info'])) {
        $userData = [
            'nom' => $_POST['nom'],
            'email' => $_POST['email'],
            'adresse' => $_POST['adresse'],
            'ville' => $_POST['ville'],
            'code_postal' => $_POST['code_postal'],
            'pays' => $_POST['pays'],
            'telephone' => $_POST['telephone']
        ];
        
        if (updateUserInfo($pdo, $_SESSION['user_id'], $userData)) {
            $success = "Vos informations ont été mises à jour avec succès.";
            // Mise à jour des informations en session
            $user = getUserById($pdo, $_SESSION['user_id']);
        } else {
            $errors[] = "Une erreur est survenue lors de la mise à jour de vos informations.";
        }
    }
    
    // Mise à jour du mot de passe
    if (isset($_POST['update_password'])) {
        if (empty($_POST['current_password']) || empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
            $errors[] = "Tous les champs du mot de passe sont requis.";
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        } elseif (strlen($_POST['new_password']) < 8) {
            $errors[] = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
        } else {
            // Vérification de l'ancien mot de passe
            if (password_verify($_POST['current_password'], $user['mot_de_passe'])) {
                if (updateUserPassword($pdo, $_SESSION['user_id'], $_POST['new_password'])) {
                    $success = "Votre mot de passe a été mis à jour avec succès.";
                } else {
                    $errors[] = "Une erreur est survenue lors de la mise à jour du mot de passe.";
                }
            } else {
                $errors[] = "Le mot de passe actuel est incorrect.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon compte - Furniture</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
</head>
<body>
    <?php include '../public/includes/header.php'; ?>
    
    <div class="container my-5">
        <h1 class="mb-4">Mon compte</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Informations personnelles -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informations personnelles</h5>
                    </div>
                    <div class="card-body">
                        <form action="compte.php" method="POST">
                            <div class="mb-3">
                                <label for="nom" class="form-label">Nom complet</label>
                                <input type="text" class="form-control" id="nom" name="nom" value="<?php echo htmlspecialchars($user['nom']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" value="<?php echo htmlspecialchars($user['telephone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="adresse" class="form-label">Adresse</label>
                                <textarea class="form-control" id="adresse" name="adresse" rows="3"><?php echo htmlspecialchars($user['adresse'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ville" class="form-label">Ville</label>
                                    <input type="text" class="form-control" id="ville" name="ville" value="<?php echo htmlspecialchars($user['ville'] ?? ''); ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="code_postal" class="form-label">Code postal</label>
                                    <input type="text" class="form-control" id="code_postal" name="code_postal" value="<?php echo htmlspecialchars($user['code_postal'] ?? ''); ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="pays" class="form-label">Pays</label>
                                <input type="text" class="form-control" id="pays" name="pays" value="<?php echo htmlspecialchars($user['pays'] ?? 'Belgique'); ?>">
                            </div>
                            
                            <button type="submit" name="update_info" class="btn btn-primary w-100">
                                <i class="fas fa-save me-2"></i> Mettre à jour mes informations
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Changer le mot de passe</h5>
                    </div>
                    <div class="card-body">
                        <form action="compte.php" method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mot de passe actuel</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" name="update_password" class="btn btn-primary w-100">
                                <i class="fas fa-key me-2"></i> Changer le mot de passe
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Historique des commandes -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Historique des commandes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                            <div class="alert alert-info">
                                Vous n'avez pas encore passé de commande.
                                <a href="catalogue.php" class="alert-link">Parcourir notre catalogue</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Commande #</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Statut</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td><?php echo $order['id']; ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($order['date_commande'])); ?></td>
                                                <td><?php echo number_format($order['total'], 2, ',', ' '); ?> €</td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        $statusClass = 'secondary';
                                                        switch($order['statut']) {
                                                            case 'en_attente':
                                                                $statusClass = 'warning';
                                                                break;
                                                            case 'en_cours':
                                                                $statusClass = 'info';
                                                                break;
                                                            case 'expediee':
                                                                $statusClass = 'primary';
                                                                break;
                                                            case 'livree':
                                                                $statusClass = 'success';
                                                                break;
                                                        }
                                                        echo $statusClass;
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $order['statut'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="confirmation.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-outline-primary">
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
    
    <?php include '../public/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 