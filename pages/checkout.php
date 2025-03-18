<?php
// pages/checkout.php – Validation de commande
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: connexion.php");
    exit;
}
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) {
    header("Location: panier.php");
    exit;
}
require_once '../src/php/db/dbConnect.php';
$productsInCart = [];
$ids = implode(',', array_keys($cart));
$stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
$productsInCart = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total = 0;
foreach ($productsInCart as $product) {
    $total += $product['prix'] * $cart[$product['id']];
}
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adresse = trim($_POST['adresse']);
    if (empty($adresse)) {
        $errors[] = "L'adresse de livraison est obligatoire.";
    }
    if (empty($errors)) {
        // Créer la commande via la fonction PL/pgSQL
        $stmt = $pdo->prepare("SELECT create_order(:utilisateur_id, :montant_total) AS order_id");
        $stmt->execute(['utilisateur_id' => $_SESSION['user']['id'], 'montant_total' => $total]);
        $orderData = $stmt->fetch(PDO::FETCH_ASSOC);
        $order_id = $orderData['order_id'];
        // Ajouter les lignes de commande
        foreach ($productsInCart as $product) {
            $stmtLine = $pdo->prepare("SELECT add_order_line(:order_id, :produit_id, :quantite, :prix_unitaire)");
            $stmtLine->execute([
                'order_id' => $order_id,
                'produit_id' => $product['id'],
                'quantite' => $cart[$product['id']],
                'prix_unitaire' => $product['prix']
            ]);
        }
        unset($_SESSION['cart']);
        header("Location: confirmation.php?order_id=" . $order_id);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Checkout - Furniture</title>
  <link rel="stylesheet" href="../public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
  <?php include '../public/includes/header.php'; ?>
  <div class="container my-5">
    <h1>Validation de la commande</h1>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
          <p><?= htmlspecialchars($error) ?></p>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <h3>Récapitulatif du panier</h3>
    <ul class="list-group mb-4">
      <?php foreach ($productsInCart as $product): ?>
        <li class="list-group-item">
          <?= htmlspecialchars($product['titre']) ?> - Quantité : <?= $cart[$product['id']] ?> - Sous-total : <?= number_format($product['prix'] * $cart[$product['id']], 2, ',', ' ') ?> €
        </li>
      <?php endforeach; ?>
      <li class="list-group-item fw-bold">Total : <?= number_format($total, 2, ',', ' ') ?> €</li>
    </ul>
    <h3>Informations de livraison</h3>
    <form method="POST" action="checkout.php">
      <div class="mb-3">
        <label for="adresse" class="form-label">Adresse de livraison</label>
        <textarea name="adresse" id="adresse" class="form-control" required></textarea>
      </div>
      <button type="submit" class="btn btn-primary">Confirmer la commande</button>
      <a href="panier.php" class="btn btn-secondary">Retour au panier</a>
    </form>
  </div>
  <?php include '../public/includes/footer.php'; ?>
</body>
</html>
