<?php
// pages/contact.php – Page de contact
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Exos/Techno-internet2_commerce/Techno-internet2_commerce');
}
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = $_POST['nom'] ?? '';
    $email = $_POST['email'] ?? '';
    $sujet = $_POST['sujet'] ?? '';
    $contenu = $_POST['message'] ?? '';
    
    // Ici, vous pourriez envoyer un email ou enregistrer le message dans la base de données
    
    $message = "Votre message a été envoyé avec succès. Nous vous répondrons dans les meilleurs délais.";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact - Furniture</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <?php include '../public/includes/header.php'; ?>
  <div class="container my-5">
    <div class="row justify-content-center">
      <div class="col-md-8">
        <h1 class="mb-4">Contactez-nous</h1>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <form method="post">
              <div class="mb-3">
                <label for="nom" class="form-label">Votre nom</label>
                <input type="text" class="form-control" id="nom" name="nom" required>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Votre email</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
              <div class="mb-3">
                <label for="sujet" class="form-label">Sujet</label>
                <input type="text" class="form-control" id="sujet" name="sujet" required>
              </div>
              <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
              </div>
              <button type="submit" class="btn btn-primary">Envoyer</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php include '../public/includes/footer.php'; ?>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
