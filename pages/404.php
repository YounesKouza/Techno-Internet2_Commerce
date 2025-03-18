<?php
http_response_code(404);
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Exos/Techno-internet2_commerce/Techno-internet2_commerce');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Page non trouvée - Furniture</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../public/includes/header.php'; ?>

    <div class="container my-5 text-center">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="display-1 text-primary mb-4">404</h1>
                <h2 class="mb-4">Page non trouvée</h2>
                <p class="lead mb-5">Désolé, la page que vous recherchez n'existe pas ou a été déplacée.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?php echo BASE_URL; ?>/" class="btn btn-primary">Retour à l'accueil</a>
                    <a href="<?php echo BASE_URL; ?>/catalogue" class="btn btn-outline-primary">Voir le catalogue</a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../public/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 