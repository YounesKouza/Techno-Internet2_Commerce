<?php
/**
 * Fichier contenant la fonction pour générer le pied de page du site
 */

/**
 * Génère le pied de page HTML du site
 * @param array $scripts Scripts JS supplémentaires à inclure (optionnel)
 */
function generate_footer($scripts = []) {
    // Récupérer les catégories depuis la base de données
    $pdo = getPDO();
    $categories = [];
    
    try {
        $stmt = $pdo->query("SELECT id, nom FROM categories ORDER BY nom LIMIT 5");
        $categories = $stmt->fetchAll();
    } catch (PDOException $e) {
        // En cas d'erreur, on continue sans les catégories
        error_log("Erreur lors de la récupération des catégories: " . $e->getMessage());
    }
    ?>
    </main>
    
    <!-- Pied de page -->
    <footer class="footer">
        <div class="container">
            <div class="row gy-4">
                <!-- Logo et description -->
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <div class="mb-4">
                        <a href="index_.php" class="d-inline-block">
                            <img src="/Exos/Techno-internet2_commerce/admin/public/images/logo.png" alt="Furniture Logo" class="footer-logo">
                        </a>
                    </div>
                    <p class="text-white-50">
                        Donnez une seconde vie à vos meubles. Découvrez notre collection unique de meubles rénovés et upcyclés.
                        Livraison rapide et service client personnalisé.
                    </p>
                    <div class="social-icons mt-4">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-pinterest"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                
                <!-- Liens rapides -->
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h5 class="footer-heading mb-4">Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index_.php"><i class="fas fa-chevron-right me-2"></i>Accueil</a></li>
                        <li class="mb-2"><a href="index_.php?page=catalogue"><i class="fas fa-chevron-right me-2"></i>Catalogue</a></li>
                        <li class="mb-2"><a href="index_.php?page=panier"><i class="fas fa-chevron-right me-2"></i>Panier</a></li>
                        <li class="mb-2"><a href="index_.php?page=compte"><i class="fas fa-chevron-right me-2"></i>Mon compte</a></li>
                        <li class="mb-2"><a href="index_.php?page=login"><i class="fas fa-chevron-right me-2"></i>Connexion</a></li>
                        <li class="mb-2"><a href="index_.php?page=inscription"><i class="fas fa-chevron-right me-2"></i>Inscription</a></li>
                    </ul>
                </div>
                
                <!-- Catégories -->
                <div class="col-lg-2 col-md-6 mb-4 mb-lg-0">
                    <h5 class="footer-heading mb-4">Catégories</h5>
                    <ul class="list-unstyled">
                        <?php if (empty($categories)): ?>
                            <li class="mb-2"><a href="index_.php?page=catalogue"><i class="fas fa-chevron-right me-2"></i>Tous les produits</a></li>
                        <?php else: ?>
                            <?php foreach ($categories as $category): ?>
                                <li class="mb-2">
                                    <a href="index_.php?page=catalogue&category=<?= $category['id'] ?>">
                                        <i class="fas fa-chevron-right me-2"></i><?= htmlspecialchars($category['nom']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div class="col-lg-4 col-md-6">
                    <h5 class="footer-heading mb-4">Contact</h5>
                    <div class="mb-4">
                        <p class="mb-2"><i class="fas fa-map-marker-alt me-3"></i>Rue de la Grand Place 2, Mons, Belgique</p>
                        <p class="mb-2"><i class="fas fa-phone me-3"></i>+32 123 456 789</p>
                        <p class="mb-2"><i class="fas fa-envelope me-3"></i>info@furniture.be</p>
                    </div>
                    
                    <div class="newsletter">
                        <h6 class="mb-3">Newsletter</h6>
                        <form class="d-flex">
                            <input type="email" class="form-control form-control-sm" placeholder="Votre email">
                            <button type="submit" class="btn btn-primary btn-sm ms-2">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <hr class="mt-4 mb-3">
            
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-md-0 text-white-50">&copy; <?= date('Y') ?> Furniture. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <a href="#" class="text-white-50 me-3">Mentions légales</a>
                    <a href="#" class="text-white-50">Politique de confidentialité</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <?php foreach ($scripts as $script): ?>
    <script src="<?= $script ?>"></script>
    <?php endforeach; ?>
    
    </body>
    </html>
    <?php
} 