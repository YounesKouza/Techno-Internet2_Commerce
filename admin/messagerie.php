<?php
// admin/messagerie.php – Consultation des messages contact
include '../src/php/utils/check_connection.php';
if (!isAdmin()) {
    header("Location: ../pages/connexion.php");
    exit;
}
// Exemple statique; en production, vous récupérerez depuis une table ou un fichier
$messages = [
    ['id' => 1, 'nom' => 'Client A', 'email' => 'a@example.com', 'sujet' => 'Question', 'message' => 'Plus d\'informations sur le produit X.'],
    ['id' => 2, 'nom' => 'Client B', 'email' => 'b@example.com', 'sujet' => 'Livraison', 'message' => 'Ma commande n\'est pas arrivée.']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Messagerie - Admin</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include 'header_admin.php'; ?>
  <div class="container my-5">
    <h1>Messagerie</h1>
    <?php foreach($messages as $msg): ?>
      <div class="card mb-3">
        <div class="card-body">
          <h5 class="card-title"><?= htmlspecialchars($msg['sujet']) ?></h5>
          <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($msg['nom']) ?> (<?= htmlspecialchars($msg['email']) ?>)</h6>
          <p class="card-text"><?= nl2br(htmlspecialchars($msg['message'])) ?></p>
        </div>
      </div>
    <?php endforeach; ?>
    <a href="index.php" class="btn btn-secondary">Retour Dashboard</a>
  </div>
  <?php include 'footer_admin.php'; ?>
</body>
</html>
