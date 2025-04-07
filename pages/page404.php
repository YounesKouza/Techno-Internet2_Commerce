<?php
/**
 * Page d'erreur 404 - Page non trouvée
 */

// Titre de la page
$titre_page = 'Page non trouvée';

// Envoi de l'en-tête HTTP 404
http_response_code(404);
?>

<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="mb-4">
                        <i class="fas fa-exclamation-triangle text-warning display-1"></i>
                    </div>
                    
                    <h1 class="display-4 mb-4">Erreur 404</h1>
                    <h2 class="mb-4">Oops! Page non trouvée</h2>
                    
                    <p class="lead mb-4">
                        La page que vous recherchez n'existe pas ou a été déplacée.
                    </p>
                    
                    <div class="mb-4">
                        <a href="index_.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-home me-2"></i> Retour à l'accueil
                        </a>
                    </div>
                    
                    <div class="mt-5">
                        <p class="text-muted">
                            Si vous pensez qu'il s'agit d'une erreur, n'hésitez pas à nous contacter.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 