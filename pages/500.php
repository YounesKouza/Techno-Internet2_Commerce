<?php
http_response_code(500);
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Exos/Techno-internet2_commerce/Techno-internet2_commerce');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Erreur serveur - Furniture</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../public/includes/header.php'; ?>

    <div class="container my-5 text-center">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="display-1 text-primary mb-4">500</h1>
                <h2 class="mb-4">Erreur serveur</h2>
                <p class="lead mb-5">Désolé, une erreur inattendue s'est produite. Notre équipe technique a été notifiée et travaille à résoudre le problème.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo BASE_URL; ?>/" class="btn btn-primary">Retour à l'accueil</a>
                    <a href="<?php echo BASE_URL; ?>/contact" class="btn btn-outline-primary">Nous contacter</a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../public/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 