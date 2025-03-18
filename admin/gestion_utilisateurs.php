<?php
// admin/gestion_utilisateurs.php – Gestion des utilisateurs
include '../src/php/utils/check_connection.php';
if (!isAdmin()) {
    header("Location: ../pages/connexion.php");
    exit;
}
require_once '../src/php/db/dbConnect.php';
$stmt = $pdo->query("SELECT * FROM users ORDER BY date_inscription DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Gestion des Utilisateurs - Admin</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'header_admin.php'; ?>
  <div class="container my-5">
    <h1>Gestion des Utilisateurs</h1>
    <table class="table">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nom</th>
          <th>Email</th>
          <th>Rôle</th>
          <th>Date d'inscription</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach($users as $user): ?>
        <tr>
          <td><?= $user['id'] ?></td>
          <td><?= htmlspecialchars($user['nom']) ?></td>
          <td><?= htmlspecialchars($user['email']) ?></td>
          <td><?= htmlspecialchars($user['role']) ?></td>
          <td><?= $user['date_inscription'] ?></td>
          <td>
            <a href="modifier_utilisateur.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">Modifier</a>
            <button class="btn btn-sm btn-danger delete-user" data-id="<?= $user['id'] ?>">Supprimer</button>
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
    $('.delete-user').click(function(){
      if(confirm("Confirmez-vous la suppression de cet utilisateur ?")){
        var id = $(this).data('id');
        $.post("../src/php/ajax/ajaxSupprimerUser.php", { id: id }, function(){
          location.reload();
        });
      }
    });
  </script>
</body>
</html>
