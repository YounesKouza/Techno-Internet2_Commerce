<?php
// pages/mon_compte.php – Espace client
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: connexion.php");
    exit;
}
$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mon Compte - Furniture</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include '../public/includes/header.php'; ?>
  <div class="container my-5">
    <h1>Bienvenue, <?= htmlspecialchars($user['nom']) ?></h1>
    <ul class="list-group">
      <li class="list-group-item"><a href="mes_annonces.php">Mes annonces</a></li>
      <li class="list-group-item"><a href="ajouter_annonce.php">Vendre un meuble</a></li>
      <li class="list-group-item"><a href="panier.php">Consulter mon panier</a></li>
      <li class="list-group-item"><a href="deconnexion.php">Déconnexion</a></li>
    </ul>
  </div>
  <?php include '../public/includes/footer.php'; ?>
</body>
</html>
