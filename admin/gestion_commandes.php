<?php
// admin/gestion_commandes.php – Gestion des commandes
include '../src/php/utils/check_connection.php';
if (!isAdmin()) {
    header("Location: ../pages/connexion.php");
    exit;
}
require_once '../src/php/db/dbConnect.php';
$stmt = $pdo->query("SELECT * FROM orders ORDER BY date_commande DESC");
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Commandes - Admin</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'header_admin.php'; ?>
  <div class="container my-5">
    <h1>Gestion des Commandes</h1>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Client ID</th>
          <th>Date</th>
          <th>Montant Total</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($orders as $order): ?>
        <tr>
          <td><?= $order['id'] ?></td>
          <td><?= $order['utilisateur_id'] ?></td>
          <td><?= $order['date_commande'] ?></td>
          <td><?= number_format($order['montant_total'], 2, ',', ' ') ?> €</td>
          <td><?= htmlspecialchars($order['statut']) ?></td>
          <td>
            <a href="commande_detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info">Détails</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Retour Dashboard</a>
  </div>
  <?php include 'footer_admin.php'; ?>
</body>
</html>
