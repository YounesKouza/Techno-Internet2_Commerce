<?php
/**
 * Page de connexion pour l'administration
 */

// Démarrage de la session
session_start();

// Redirection si déjà connecté
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: accueil_admin.php');
    exit;
}

// Inclusion du fichier de connexion à la base de données
require_once '../src/php/utils/connexion.php';

$error = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        try {
            // Vérifier les identifiants
            $pdo = getPDO();
            
            // Debug: Afficher la requête pour vérifier
            error_log("Tentative de connexion avec email: $email");
            
            // Récupérer l'utilisateur par email, quelle que soit son rôle
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                error_log("Utilisateur trouvé: ID=" . $user['id'] . ", Role=" . $user['role']);
                
                // Vérifier le mot de passe
                if (password_verify($password, $user['mot_de_passe'])) {
                    // Vérifier si l'utilisateur est admin
                    if ($user['role'] === 'admin') {
                // Connexion réussie - Création de la session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['nom'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Redirection vers le tableau de bord
                header('Location: accueil_admin.php');
                exit;
            } else {
                        $error = 'Vous n\'avez pas les droits d\'accès à l\'administration';
                        error_log("Accès refusé: l'utilisateur n'est pas admin");
                    }
                } else {
                    $error = 'Mot de passe incorrect';
                    error_log("Mot de passe incorrect pour l'utilisateur: " . $user['email']);
                }
            } else {
                $error = 'Aucun utilisateur trouvé avec cet email';
                error_log("Aucun utilisateur trouvé avec l'email: $email");
            }
        } catch (PDOException $e) {
            $error = 'Erreur de connexion à la base de données: ' . $e->getMessage();
            error_log("Erreur PDO: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Administration | Furniture</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            background-color: #f5f5f5;
        }
        
        .form-signin {
            max-width: 400px;
            padding: 15px;
        }
        
        .form-signin .form-floating:focus-within {
            z-index: 2;
        }
        
        .form-signin input[type="email"] {
            margin-bottom: -1px;
            border-bottom-right-radius: 0;
            border-bottom-left-radius: 0;
        }
        
        .form-signin input[type="password"] {
            margin-bottom: 10px;
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }
        
        .admin-header {
            background-color: #343a40;
            color: white;
            padding: 20px;
            border-radius: 5px 5px 0 0;
        }
    </style>
</head>
<body class="text-center">
    <main class="form-signin w-100 m-auto">
        <div class="card shadow-lg">
            <div class="admin-header">
                <i class="fas fa-couch fa-3x mb-2"></i>
                <h1 class="h3 mb-0 fw-normal">Administration Furniture</h1>
                <p class="text-white-50">Connectez-vous pour accéder au panneau d'administration</p>
            </div>
            
            <div class="card-body p-4">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="nom@exemple.com" required>
                        <label for="email">Adresse e-mail</label>
                    </div>
                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" required>
                        <label for="password">Mot de passe</label>
                    </div>
                    
                    <button class="w-100 btn btn-lg btn-primary" type="submit">Se connecter</button>
                    <p class="mt-3 mb-2">
                        <a href="../../index_.php">Retour au site</a>
                    </p>
                </form>
            </div>
        </div>
        <p class="mt-3 text-muted">&copy; <?= date('Y') ?> Furniture Shop</p>
    </main>
    
    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 