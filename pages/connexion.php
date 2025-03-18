<?php
// pages/connexion.php – Formulaire de connexion
include '../src/php/db/dbConnect.php';
session_start();
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['mot_de_passe'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($mot_de_passe, $user['mot_de_passe'])) {
        $_SESSION['user'] = ['id' => $user['id'], 'nom' => $user['nom'], 'email' => $user['email'], 'role' => $user['role']];
        if ($user['role'] === 'admin') {
            header("Location: ../admin/index.php");
        } else {
            header("Location: mon_compte.php");
        }
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Connexion - Furniture</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include '../public/includes/header.php'; ?>
  <div class="container my-5">
    <h1>Connexion</h1>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST" action="connexion.php">
      <div class="mb-3">
        <label for="email" class="form-label">Adresse email</label>
        <input type="email" name="email" id="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="mot_de_passe" class="form-label">Mot de passe</label>
        <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Se connecter</button>
      <p class="mt-3">Pas encore inscrit ? <a href="inscription.php">S'inscrire</a></p>
    </form>
  </div>
  <?php include '../public/includes/footer.php'; ?>
</body>
</html>
