<?php
// admin/gestion_annonces.php – Gestion de toutes les annonces
include '../src/php/utils/check_connection.php';
if (!isAdmin()) {
    header("Location: ../pages/connexion.php");
    exit;
}
require_once '../src/php/db/dbConnect.php';
$stmt = $pdo->query("SELECT * FROM products ORDER BY date_creation DESC");
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Annonces - Admin</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'header_admin.php'; ?>
  <div class="container my-5">
    <h1>Gestion des Annonces</h1>
    <table class="table">
      <thead>
        <tr>
          <th>Titre</th>
          <th>Date</th>
          <th>Statut</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($annonces as $annonce): ?>
        <tr>
          <td><?= htmlspecialchars($annonce['titre']) ?></td>
          <td><?= $annonce['date_creation'] ?></td>
          <td><?= $annonce['actif'] ? 'Approuvée' : 'En attente' ?></td>
          <td>
            <button class="btn btn-warning btn-sm toggle-status" data-id="<?= $annonce['id'] ?>">
              <?= $annonce['actif'] ? 'Désapprouver' : 'Approuver' ?>
            </button>
            <button class="btn btn-danger btn-sm delete-annonce" data-id="<?= $annonce['id'] ?>">Supprimer</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <a href="index.php" class="btn btn-secondary">Retour Dashboard</a>
  </div>
  <?php include 'footer_admin.php'; ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $('.delete-annonce').click(function(){
      if (confirm("Confirmez la suppression ?")) {
        var id = $(this).data('id');
        $.post("../src/php/ajax/ajaxSupprimerAnnonce.php", { id: id }, function(response){
          location.reload();
        });
      }
    });
    $('.toggle-status').click(function(){
      var id = $(this).data('id');
      $.post("../src/php/ajax/ajaxModifierAnnonce.php", { id: id, action: 'toggle' }, function(response){
        location.reload();
      });
    });
  </script>
</body>
</html>
