<?php
// pages/mes_annonces.php – Mes annonces du client
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: connexion.php");
    exit;
}
require_once '../src/php/db/dbConnect.php';
// En production, filtrer selon l'utilisateur connecté (ex: WHERE utilisateur_id = ...)
$stmt = $pdo->query("SELECT * FROM products ORDER BY date_creation DESC");
$annonces = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Mes Annonces - Furniture</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include '../public/includes/header.php'; ?>
  <div class="container my-5">
    <h1>Mes Annonces</h1>
    <table class="table">
      <thead>
        <tr>
          <th>Titre</th>
          <th>Prix</th>
          <th>Date de publication</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($annonces as $annonce): ?>
        <tr>
          <td><?= htmlspecialchars($annonce['titre']) ?></td>
          <td><?= number_format($annonce['prix'], 2, ',', ' ') ?> €</td>
          <td><?= $annonce['date_creation'] ?></td>
          <td>
            <a href="modifier_annonce.php?id=<?= $annonce['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
            <button class="btn btn-sm btn-danger delete-annonce" data-id="<?= $annonce['id'] ?>">Supprimer</button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php include '../public/includes/footer.php'; ?>
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
  </script>
</body>
</html>
