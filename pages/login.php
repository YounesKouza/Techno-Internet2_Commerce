<?php
/**
 * Page de connexion
 */

// Titre de la page
$titre_page = 'Connexion';

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    // Redirection en fonction du rôle
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/pages/accueil_admin.php');
        exit;
    } else {
        header('Location: index_.php?page=compte');
        exit;
    }
}

// Traitement du formulaire de connexion
$error_msg = '';
$success_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error_msg = 'Veuillez remplir tous les champs';
    } else {
        // Connexion à la base de données
        $pdo = getPDO();
        
        // Recherche de l'utilisateur
        $stmt = $pdo->prepare("SELECT id, nom as username, mot_de_passe as password, role FROM users WHERE nom = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Connexion réussie, enregistrement des données de session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Mise à jour de la date de dernière connexion
            $update_stmt = $pdo->prepare("UPDATE users SET date_inscription = CURRENT_TIMESTAMP WHERE id = ?");
            $update_stmt->execute([$user['id']]);
            
            // Redirection en fonction du rôle
            if ($user['role'] === 'admin') {
                header('Location: admin/pages/accueil_admin.php');
                exit;
            } else {
                // Redirection vers la page précédente si spécifiée, sinon vers le compte
                if (isset($_SESSION['redirect_after_login']) && !empty($_SESSION['redirect_after_login'])) {
                    $redirect = $_SESSION['redirect_after_login'];
                    unset($_SESSION['redirect_after_login']);
                    header("Location: $redirect");
                } else {
                    header('Location: index_.php?page=compte');
                }
                exit;
            }
        } else {
            $error_msg = 'Identifiants incorrects';
        }
    }
}

// Stockage de la page en cours pour redirection après connexion
if (isset($_GET['redirect_url'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect_url'];
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Connexion</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($error_msg)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= $error_msg ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success_msg)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= $success_msg ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nom d'utilisateur ou Email</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                            <label class="form-check-label" for="rememberMe">Se souvenir de moi</label>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <a href="#" class="text-decoration-none">Mot de passe oublié ?</a>
                    </div>
                </div>
                <div class="card-footer bg-light text-center">
                    <p class="mb-0">Vous n'avez pas de compte ? <a href="index_.php?page=inscription" class="text-decoration-none">Créer un compte</a></p>
                </div>
            </div>
            
            <!-- Avantages de la connexion -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5><i class="fas fa-info-circle me-2 text-primary"></i> Avantages de la connexion</h5>
                    <ul class="mb-0">
                        <li>Accédez à votre historique de commandes</li>
                        <li>Sauvegardez vos produits favoris</li>
                        <li>Passez vos commandes plus rapidement</li>
                        <li>Bénéficiez d'offres exclusives pour les membres</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Script pour afficher/masquer le mot de passe
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.toggle-password');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentNode.querySelector('input');
                const icon = this.querySelector('i');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script> 