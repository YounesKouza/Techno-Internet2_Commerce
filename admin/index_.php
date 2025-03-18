<?php
// Informations pour les produits mis en avant (simulées - à remplacer par des données de base de données)
$featuredProducts = [
    [
        'id' => 1,
        'name' => 'Bureau Vintage Restauré',
        'price' => 249.99,
        'image' => '../assets/images/desk1.jpg',
        'description' => 'Bureau en chêne des années 60, entièrement restauré avec finition naturelle.'
    ],
    [
        'id' => 2,
        'name' => 'Table à Manger Scandinave',
        'price' => 399.99,
        'image' => '../assets/images/table1.jpg',
        'description' => 'Table à manger style scandinave rénovée, idéale pour 6 personnes.'
    ],
    [
        'id' => 3,
        'name' => 'Bureau d\'Angle Industriel',
        'price' => 279.99,
        'image' => '../assets/images/desk2.jpg',
        'description' => 'Bureau d\'angle style industriel avec plateau en bois massif et pieds en métal.'
    ],
    [
        'id' => 4,
        'name' => 'Table Basse Rustique',
        'price' => 199.99,
        'image' => '../assets/images/table2.jpg',
        'description' => 'Table basse en bois de récupération avec tiroir de rangement intégré.'
    ]
];

// Catégories de produits
$categories = [
    ['name' => 'Bureaux', 'icon' => 'fa-desk', 'image' => '../assets/images/category-desk.jpg'],
    ['name' => 'Tables à manger', 'icon' => 'fa-utensils', 'image' => '../assets/images/category-dining.jpg'],
    ['name' => 'Tables basses', 'icon' => 'fa-coffee', 'image' => '../assets/images/category-coffee.jpg'],
    ['name' => 'Consoles', 'icon' => 'fa-table', 'image' => '../assets/images/category-console.jpg']
];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Furniture - Marketplace de meubles rénovés</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('../assets/images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            margin-bottom: 40px;
        }
        
        .category-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
            border-radius: 10px;
            height: 200px;
        }
        
        .category-card:hover {
            transform: translateY(-5px);
        }
        
        .category-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .category-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 15px;
            text-align: center;
        }
        
        .product-card {
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 20px;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .product-img {
            height: 200px;
            overflow: hidden;
        }
        
        .product-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .value-prop {
            background-color: #f8f9fa;
            padding: 60px 0;
            margin: 40px 0;
        }
        
        .navbar-brand {
            font-weight: bold;
            font-size: 24px;
        }
        
        .eco-badge {
            background-color: #28a745;
            color: white;
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Furniture</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Catalogue</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Notre Histoire</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Contact</a>
                    </li>
                </ul>
                <form class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Rechercher un meuble..." aria-label="Search">
                    <button class="btn btn-outline-light" type="submit">Rechercher</button>
                </form>
                <div class="ms-3">
                    <a href="#" class="btn btn-outline-light me-2"><i class="fas fa-user"></i> Compte</a>
                    <a href="#" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Panier (0)</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="display-4">Donnez une seconde vie à votre intérieur</h1>
            <p class="lead">Découvrez notre collection de meubles rénovés avec soin et passion</p>
            <div class="mt-4">
                <a href="#" class="btn btn-primary btn-lg me-2">Découvrir les produits</a>
                <a href="#" class="btn btn-outline-light btn-lg">Comment ça marche</a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="container mb-5">
        <h2 class="text-center mb-4">Parcourez nos catégories</h2>
        <div class="row">
            <?php foreach($categories as $category): ?>
                <div class="col-md-3 col-6">
                    <div class="category-card">
                        <img src="<?= $category['image'] ?>" alt="<?= $category['name'] ?>" onerror="this.src='https://via.placeholder.com/300x200?text=<?= urlencode($category['name']) ?>'">
                        <div class="category-overlay">
                            <h5><?= $category['name'] ?></h5>
                            <a href="#" class="btn btn-sm btn-outline-light">Voir plus</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Nos pièces coup de cœur</h2>
            <a href="#" class="btn btn-outline-primary">Voir tous les produits</a>
        </div>
        <div class="row">
            <?php foreach($featuredProducts as $product): ?>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="product-card h-100">
                        <div class="product-img">
                            <img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>" onerror="this.src='https://via.placeholder.com/300x200?text=<?= urlencode($product['name']) ?>'">
                        </div>
                        <div class="card-body p-3">
                            <div class="eco-badge">
                                <i class="fas fa-leaf"></i> Éco-responsable
                            </div>
                            <h5 class="card-title"><?= $product['name'] ?></h5>
                            <p class="card-text text-muted"><?= substr($product['description'], 0, 60) ?>...</p>
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <span class="fw-bold"><?= number_format($product['price'], 2, ',', ' ') ?> €</span>
                                <a href="#" class="btn btn-sm btn-primary"><i class="fas fa-shopping-cart"></i> Ajouter</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Value Proposition -->
    <section class="value-prop">
        <div class="container">
            <h2 class="text-center mb-5">Pourquoi choisir des meubles rénovés ?</h2>
            <div class="row">
                <div class="col-md-4 text-center mb-4">
                    <div class="rounded-circle bg-primary p-3 d-inline-flex mb-3" style="width: 80px; height: 80px; justify-content: center; align-items: center;">
                        <i class="fas fa-leaf fa-2x text-white"></i>
                    </div>
                    <h4>Écologique</h4>
                    <p>Chaque meuble rénové permet de réduire les déchets et d'économiser les ressources naturelles.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="rounded-circle bg-primary p-3 d-inline-flex mb-3" style="width: 80px; height: 80px; justify-content: center; align-items: center;">
                        <i class="fas fa-paint-brush fa-2x text-white"></i>
                    </div>
                    <h4>Pièces uniques</h4>
                    <p>Nos artisans redonnent vie à chaque meuble avec une attention particulière aux détails.</p>
                </div>
                <div class="col-md-4 text-center mb-4">
                    <div class="rounded-circle bg-primary p-3 d-inline-flex mb-3" style="width: 80px; height: 80px; justify-content: center; align-items: center;">
                        <i class="fas fa-euro-sign fa-2x text-white"></i>
                    </div>
                    <h4>Économique</h4>
                    <p>Des meubles de qualité à des prix accessibles, alliant durabilité et style.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonial Section -->
    <section class="container my-5">
        <h2 class="text-center mb-4">Ce que disent nos clients</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-4">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="card-text">"J'ai acheté un bureau vintage pour mon salon et je suis ravie. La qualité est exceptionnelle et il apporte beaucoup de caractère à la pièce."</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">ML</div>
                        <div>
                            <h6 class="mb-0">Marie L.</h6>
                            <small class="text-muted">Cliente depuis 2023</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-4">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                    </div>
                    <p class="card-text">"Le service client est impeccable et la livraison a été très rapide. Ma table à manger est absolument magnifique, on voit le travail artisanal."</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">TD</div>
                        <div>
                            <h6 class="mb-0">Thomas D.</h6>
                            <small class="text-muted">Client depuis 2022</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <div class="card h-100 p-4">
                    <div class="mb-3">
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="fas fa-star text-warning"></i>
                        <i class="far fa-star text-warning"></i>
                    </div>
                    <p class="card-text">"J'apprécie vraiment l'aspect éco-responsable. Avoir un meuble avec une histoire tout en faisant un geste pour la planète, c'est parfait !"</p>
                    <div class="d-flex align-items-center mt-3">
                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">SB</div>
                        <div>
                            <h6 class="mb-0">Sophie B.</h6>
                            <small class="text-muted">Cliente depuis 2024</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 text-center">
                    <h3>Restez informé de nos nouveautés</h3>
                    <p class="mb-4">Inscrivez-vous à notre newsletter pour recevoir nos dernières trouvailles et offres spéciales</p>
                    <form class="row g-3 justify-content-center">
                        <div class="col-md-8">
                            <input type="email" class="form-control form-control-lg" placeholder="Votre adresse email">
                        </div>
                        <div class="col-md-auto">
                            <button type="submit" class="btn btn-primary btn-lg">S'inscrire</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white pt-5 pb-4">
        <div class="container">
            <div class="row">
                <div class="col-md-3 mb-4">
                    <h5>Furniture</h5>
                    <p>Spécialiste de la rénovation et de la vente de meubles de qualité depuis 2020.</p>
                    <div class="d-flex mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-pinterest"></i></a>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Liens rapides</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-white-50">Accueil</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Catalogue</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">À propos</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Blog</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Catégories</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-decoration-none text-white-50">Bureaux</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Tables à manger</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Tables basses</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Consoles</a></li>
                        <li><a href="#" class="text-decoration-none text-white-50">Toutes les catégories</a></li>
                    </ul>
                </div>
                <div class="col-md-3 mb-4">
                    <h5>Contact</h5>
                    <address class="text-white-50">
                        <i class="fas fa-map-marker-alt me-2"></i> 123 Rue de la Rénovation<br>
                        75001 Paris, France<br>
                        <i class="fas fa-phone me-2"></i> +33 1 23 45 67 89<br>
                        <i class="fas fa-envelope me-2"></i> contact@furniture.com
                    </address>
                </div>
            </div>
            <hr class="my-4 bg-secondary">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start">
                    <p class="mb-0 text-white-50">&copy; 2025 Furniture. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <ul class="list-inline mb-0">
                        <li class="list-inline-item"><a href="#" class="text-white-50">Conditions générales</a></li>
                        <li class="list-inline-item"><a href="#" class="text-white-50">Politique de confidentialité</a></li>
                        <li class="list-inline-item"><a href="#" class="text-white-50">Mentions légales</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>