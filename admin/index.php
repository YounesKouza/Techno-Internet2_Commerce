<?php
// admin/index.php – Dashboard Admin
include '../src/php/utils/check_connection.php';
if (!isAdmin()) {
    header("Location: ../pages/connexion.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Dashboard Admin - Furniture</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'header_admin.php'; // (à créer ou utilisez un header similaire) ?>
  <div class="container my-5">
    <h1>Dashboard Admin</h1>
    <ul class="list-group">
      <li class="list-group-item"><a href="gestion_annonces.php">Gestion des Annonces</a></li>
      <li class="list-group-item"><a href="gestion_commandes.php">Gestion des Commandes</a></li>
      <li class="list-group-item"><a href="gestion_utilisateurs.php">Gestion des Utilisateurs</a></li>
      <li class="list-group-item"><a href="messagerie.php">Messagerie</a></li>
      <li class="list-group-item"><a href="logout.php">Déconnexion Admin</a></li>
    </ul>
  </div>
  <?php include 'footer_admin.php'; // (à créer ou utiliser un footer spécifique) ?>
</body>
</html>
