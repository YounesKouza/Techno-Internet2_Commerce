<?php
/**
 * Page d'inscription
 * Permet aux visiteurs de créer un compte utilisateur
 */

// Configuration de l'encodage
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Inclusion du fichier de connexion
require_once __DIR__ . '/../admin/src/php/utils/connexion.php';

// Titre de la page
$titre_page = 'Inscription';

// Initialisation des variables
$success_message = '';
$error_message = '';
$nom = '';
$email = '';
$telephone = '';
$adresse = '';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $adresse = isset($_POST['adresse']) ? trim($_POST['adresse']) : '';
    
    // Validation des données
    $errors = [];
    
    // Nom obligatoire
    if (empty($nom)) {
        $errors[] = 'Le nom est obligatoire';
    }
    
    // Email obligatoire et format valide
    if (empty($email)) {
        $errors[] = 'L\'email est obligatoire';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide';
    } else {
        // Vérifier si l'email existe déjà
        $pdo = getPDO();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = 'Cet email est déjà utilisé';
        }
    }
    
    // Mot de passe obligatoire et confirmation
    if (empty($password)) {
        $errors[] = 'Le mot de passe est obligatoire';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Les mots de passe ne correspondent pas';
    }
    
    // Si aucune erreur, procéder à l'inscription
    if (empty($errors)) {
        try {
            $pdo = getPDO();
            
            // Préparation du hash du mot de passe
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insertion du nouvel utilisateur
            $stmt = $pdo->prepare("
                INSERT INTO users (nom, email, password, telephone, adresse, role, date_creation)
                VALUES (?, ?, ?, ?, ?, 'user', NOW())
            ");
            
            $stmt->execute([$nom, $email, $password_hash, $telephone, $adresse]);
            
            // Message de succès
            $success_message = 'Votre compte a été créé avec succès! Vous pouvez maintenant vous connecter.';
            
            // Réinitialisation des champs du formulaire
            $nom = $email = $telephone = $adresse = '';
        } catch (PDOException $e) {
            $error_message = 'Une erreur est survenue lors de l\'inscription. Veuillez réessayer plus tard.';
        }
    } else {
        // Afficher les erreurs
        $error_message = implode('<br>', $errors);
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="mb-4">Créer un compte</h1>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                </div>
                
                <div class="text-center mt-4">
                    <a href="index_.php?page=login" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i> Se connecter
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card shadow-sm">
                    <div class="card-body p-4">
                        <form method="post" action="" id="inscription-form">
                            <div class="row">
                                <!-- Informations personnelles -->
                                <div class="col-md-12 mb-3">
                                    <h5>Informations personnelles</h5>
                                    <hr>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($nom) ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Adresse email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($telephone) ?>">
                                </div>
                                
                                <div class="col-md-12 mb-4">
                                    <label for="adresse" class="form-label">Adresse</label>
                                    <textarea class="form-control" id="adresse" name="adresse" rows="3"><?= htmlspecialchars($adresse) ?></textarea>
                                </div>
                                
                                <!-- Mot de passe -->
                                <div class="col-md-12 mb-3">
                                    <h5>Sécurité</h5>
                                    <hr>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Mot de passe <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="password" name="password" required minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Le mot de passe doit contenir au moins 6 caractères</div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <label for="confirm_password" class="form-label">Confirmer le mot de passe <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                                
                                <!-- CGU et consentement -->
                                <div class="col-md-12 mb-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cgu_consent" name="cgu_consent" required>
                                        <label class="form-check-label" for="cgu_consent">
                                            J'accepte les <a href="#">conditions générales d'utilisation</a> <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-user-plus me-2"></i> Créer mon compte
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <p>Vous avez déjà un compte ? <a href="index_.php?page=login">Connectez-vous</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Afficher/masquer le mot de passe
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    
    if (togglePassword && passwordInput) {
        togglePassword.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Changer l'icône
            const icon = this.querySelector('i');
            if (type === 'text') {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    }
    
    // Validation du formulaire
    const form = document.getElementById('inscription-form');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (form && confirmPassword) {
        form.addEventListener('submit', function(event) {
            if (passwordInput.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
        
        confirmPassword.addEventListener('input', function() {
            if (passwordInput.value !== this.value) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});
</script> 