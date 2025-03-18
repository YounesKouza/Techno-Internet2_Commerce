<?php
// pages/inscription.php – Formulaire d’inscription
include '../src/php/db/dbConnect.php';
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirm = $_POST['confirm'];
    
    // Validation des champs obligatoires
    if (empty($nom) || empty($email) || empty($mot_de_passe) || empty($confirm)) {
        $errors[] = "Tous les champs sont obligatoires.";
    }
    
    // Validation du nom (lettres, espaces, tirets, accents)
    if (!preg_match('/^[a-zA-ZÀ-ÿ\s\-]{5,50}$/', $nom)) {
        $errors[] = "Le nom n'est pas valide. Il doit contenir entre 5 et 50 caractères (lettres, espaces et tirets uniquement).";
    }
    
    // Validation de l'email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email)) {
        $errors[] = "L'adresse email n'est pas valide.";
    }
    
    // Validation du mot de passe (min 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre)
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $mot_de_passe)) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères, une majuscule, une minuscule et un chiffre.";
    }
    
    if ($mot_de_passe !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas.";
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        $errors[] = "Cet email est déjà utilisé.";
    }
    
    if (empty($errors)) {
        $hashedPwd = password_hash($mot_de_passe, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("INSERT INTO users (nom, email, mot_de_passe) VALUES (:nom, :email, :mot_de_passe)");
        $stmt->execute(['nom' => $nom, 'email' => $email, 'mot_de_passe' => $hashedPwd]);
        session_start();
        $_SESSION['user'] = ['id' => $pdo->lastInsertId(), 'nom' => $nom, 'email' => $email, 'role' => 'client'];
        header("Location: mon_compte.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Inscription - Furniture</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include '../public/includes/header.php'; ?>
  <div class="container my-5">
    <h1>Inscription</h1>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
          <p><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <form method="POST" action="inscription.php">
      <div class="mb-3">
        <label for="nom" class="form-label">Nom</label>
        <input type="text" name="nom" id="nom" class="form-control" value="<?= isset($nom) ? htmlspecialchars($nom) : '' ?>" required pattern="[a-zA-ZÀ-ÿ\s\-]{2,50}">
        <div class="form-text text-muted">Entre 2 et 50 caractères (lettres, espaces et tirets uniquement).</div>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Adresse email</label>
        <input type="email" name="email" id="email" class="form-control" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>
        <div class="form-text text-muted">Exemple: nom@exemple.com</div>
      </div>
      <div class="mb-3">
        <label for="mot_de_passe" class="form-label">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}">
        
      </div>
      <div class="mb-3">
        <label for="confirm" class="form-label">Confirmer le mot de passe</label>
        <input type="password" name="confirm" id="confirm" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">S'inscrire</button>
      <p class="mt-3">Déjà inscrit ? <a href="connexion.php">Se connecter</a></p>
    </form>
  </div>
  <?php include '../public/includes/footer.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Référence aux éléments du formulaire
      const nomInput = document.getElementById('nom');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('mot_de_passe');
      const confirmInput = document.getElementById('confirm');
      
      // Ajout des éléments d'affichage de validation pour le nom
      const nomValidation = document.createElement('div');
      nomValidation.classList.add('mt-2');
      nomInput.parentNode.appendChild(nomValidation);
      
      // Ajout des éléments d'affichage de validation pour l'email
      const emailValidation = document.createElement('div');
      emailValidation.classList.add('mt-2');
      emailInput.parentNode.appendChild(emailValidation);
      
      // Ajout des listes de validation pour le mot de passe
      const passwordChecklist = document.createElement('div');
      passwordChecklist.classList.add('mt-2');
      passwordChecklist.innerHTML = `
        <div class="small">
          <div id="length" class="text-danger"><i class="fas fa-times-circle"></i> Au moins 8 caractères</div>
          <div id="uppercase" class="text-danger"><i class="fas fa-times-circle"></i> Au moins une majuscule</div>
          <div id="lowercase" class="text-danger"><i class="fas fa-times-circle"></i> Au moins une minuscule</div>
          <div id="number" class="text-danger"><i class="fas fa-times-circle"></i> Au moins un chiffre</div>
        </div>
      `;
      passwordInput.parentNode.insertBefore(passwordChecklist, passwordInput.nextSibling.nextSibling);
      
      // Ajout de l'élément de validation pour la confirmation
      const confirmValidation = document.createElement('div');
      confirmValidation.classList.add('mt-2');
      confirmValidation.innerHTML = `<div id="match" class="small text-danger"><i class="fas fa-times-circle"></i> Les mots de passe correspondent</div>`;
      confirmInput.parentNode.appendChild(confirmValidation);

      // Validation du nom
      nomInput.addEventListener('input', function() {
        const nomPattern = /^[a-zA-ZÀ-ÿ\s\-]{5,50}$/;
        if (nomPattern.test(this.value)) {
          nomValidation.innerHTML = '<div class="text-success"><i class="fas fa-check-circle"></i> Nom valide</div>';
          nomInput.classList.add('is-valid');
          nomInput.classList.remove('is-invalid');
        } else {
          nomValidation.innerHTML = '<div class="text-danger"><i class="fas fa-times-circle"></i> Nom invalide</div>';
          nomInput.classList.add('is-invalid');
          nomInput.classList.remove('is-valid');
        }
      });

      // Validation de l'email
      emailInput.addEventListener('input', function() {
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (emailPattern.test(this.value)) {
          emailValidation.innerHTML = '<div class="text-success"><i class="fas fa-check-circle"></i> Email valide</div>';
          emailInput.classList.add('is-valid');
          emailInput.classList.remove('is-invalid');
        } else {
          emailValidation.innerHTML = '<div class="text-danger"><i class="fas fa-times-circle"></i> Email invalide</div>';
          emailInput.classList.add('is-invalid');
          emailInput.classList.remove('is-valid');
        }
      });

      // Validation du mot de passe
      passwordInput.addEventListener('input', function() {
        const password = this.value;
        
        // Vérification de la longueur
        const lengthValid = password.length >= 8;
        document.getElementById('length').className = lengthValid ? 'text-success' : 'text-danger';
        document.getElementById('length').innerHTML = lengthValid ? 
          '<i class="fas fa-check-circle"></i> Au moins 8 caractères' : 
          '<i class="fas fa-times-circle"></i> Au moins 8 caractères';
        
        // Vérification majuscule
        const uppercaseValid = /[A-Z]/.test(password);
        document.getElementById('uppercase').className = uppercaseValid ? 'text-success' : 'text-danger';
        document.getElementById('uppercase').innerHTML = uppercaseValid ? 
          '<i class="fas fa-check-circle"></i> Au moins une majuscule' : 
          '<i class="fas fa-times-circle"></i> Au moins une majuscule';
        
        // Vérification minuscule
        const lowercaseValid = /[a-z]/.test(password);
        document.getElementById('lowercase').className = lowercaseValid ? 'text-success' : 'text-danger';
        document.getElementById('lowercase').innerHTML = lowercaseValid ? 
          '<i class="fas fa-check-circle"></i> Au moins une minuscule' : 
          '<i class="fas fa-times-circle"></i> Au moins une minuscule';
        
        // Vérification chiffre
        const numberValid = /\d/.test(password);
        document.getElementById('number').className = numberValid ? 'text-success' : 'text-danger';
        document.getElementById('number').innerHTML = numberValid ? 
          '<i class="fas fa-check-circle"></i> Au moins un chiffre' : 
          '<i class="fas fa-times-circle"></i> Au moins un chiffre';
        
        // Ajout ou suppression de la classe de validation
        if (lengthValid && uppercaseValid && lowercaseValid && numberValid) {
          passwordInput.classList.add('is-valid');
          passwordInput.classList.remove('is-invalid');
        } else {
          passwordInput.classList.add('is-invalid');
          passwordInput.classList.remove('is-valid');
        }
        
        // Mise à jour de la correspondance si la confirmation est déjà remplie
        if (confirmInput.value) {
          checkPasswordMatch();
        }
      });

      // Vérification de correspondance des mots de passe
      function checkPasswordMatch() {
        const matchValid = confirmInput.value === passwordInput.value && confirmInput.value !== '';
        document.getElementById('match').className = matchValid ? 'small text-success' : 'small text-danger';
        document.getElementById('match').innerHTML = matchValid ? 
          '<i class="fas fa-check-circle"></i> Les mots de passe correspondent' : 
          '<i class="fas fa-times-circle"></i> Les mots de passe ne correspondent pas';
        
        if (matchValid) {
          confirmInput.classList.add('is-valid');
          confirmInput.classList.remove('is-invalid');
        } else {
          confirmInput.classList.add('is-invalid');
          confirmInput.classList.remove('is-valid');
        }
      }

      confirmInput.addEventListener('input', checkPasswordMatch);
      
      // Forcer la vérification initiale si des valeurs sont déjà présentes (par exemple après un rechargement)
      if (nomInput.value) nomInput.dispatchEvent(new Event('input'));
      if (emailInput.value) emailInput.dispatchEvent(new Event('input'));
      if (passwordInput.value) passwordInput.dispatchEvent(new Event('input'));
      if (confirmInput.value) confirmInput.dispatchEvent(new Event('input'));
    });
  </script>
</body>
</html>
