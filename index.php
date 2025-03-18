<?php
define('BASE_URL', '/Exos/Techno-internet2_commerce/Techno-internet2_commerce');

// Informations pour les produits mis en avant (simulées - à remplacer par des données de base de données)
$featuredProducts = [
    [
        'id' => 1,
        'name' => 'Bureau Vintage Restauré',
        'price' => 249.99,
        'image' => BASE_URL . '\public\images\Furniture Tables & Bureaux\3.png',
        'description' => 'Bureau en chêne des années 60, entièrement restauré avec finition naturelle.'
    ],
    [
        'id' => 2,
        'name' => 'Table à Manger Scandinave',
        'price' => 399.99,
        'image' => BASE_URL . '\public\images\Furniture Tables & Bureaux\7.png',
        'description' => 'Table à manger style scandinave rénovée, idéale pour 6 personnes.'
    ],
    [
        'id' => 3,
        'name' => 'Bureau d\'Angle Industriel',
        'price' => 279.99,
        'image' => BASE_URL . '\public\images\Furniture Tables & Bureaux\2.png',
        'description' => 'Bureau d\'angle style industriel avec plateau en bois massif et pieds en métal.'
    ],
    [
        'id' => 4,
        'name' => 'Table Basse Rustique',
        'price' => 199.99,
        'image' => BASE_URL . '\public\images\Furniture Tables & Bureaux\6.png',
        'description' => 'Table basse en bois de récupération avec tiroir de rangement intégré.'
    ]
];

// Catégories de produits
$categories = [
    ['name' => 'Furniture Assises', 'icon' => 'fa-desk', 'image' => BASE_URL . '\public\images\Furniture Assises\2.png'],
    ['name' => 'Furniture Tables & Bureaux', 'icon' => 'fa-utensils', 'image' => BASE_URL . '\public\images\Furniture Tables & Bureaux\2.png'],
    ['name' => 'Furniture Rangement', 'icon' => 'fa-coffee', 'image' => BASE_URL . '\public\images\Furniture Rangement\1.png'],
    ['name' => 'Furniture Décoration & Accessoires', 'icon' => 'fa-table', 'image' => BASE_URL . '\public\images\Furniture Décoration & Accessoires\tapis\3.png']
];

include './src/php/utils/check_connection.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Accueil - Furniture</title>
  <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
  <?php include './public/includes/header.php'; ?>
  
  <!-- Hero Section -->
  <div class="hero-section">
    <div class="container py-5">
      <div class="row align-items-center min-vh-75">
        <div class="col-md-6">
          <h1 class="display-4 fw-bold mb-4 text-white">Donnez une seconde vie à vos meubles</h1>
          <p class="lead mb-4 text-white-50">Découvrez notre collection unique de meubles rénovés et upcyclés. Des pièces authentiques qui racontent une histoire.</p>
          <div class="d-flex gap-3">
            <a href="<?php echo BASE_URL; ?>/pages/catalogue.php" class="btn btn-primary btn-lg">Explorer le catalogue</a>
            <a href="<?php echo BASE_URL; ?>/pages/ajouter_annonce.php" class="btn btn-outline-light btn-lg">Vendre un meuble</a>
          </div>
        </div>
      </div>
    </div>
  </div>

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
              <a href="<?php echo BASE_URL; ?>/pages/catalogue.php?category=<?= urlencode($category['name']) ?>" class="btn btn-sm btn-outline-light">Voir plus</a>
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
      <a href="<?php echo BASE_URL; ?>/pages/catalogue.php" class="btn btn-outline-primary">Voir tous les produits</a>
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
                <a href="<?php echo BASE_URL; ?>/pages/panier.php?action=ajouter&id=<?= $product['id'] ?>" class="btn btn-sm btn-primary">
                  <i class="fas fa-shopping-cart"></i> Ajouter
                </a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- Testimonials Section -->
  <section class="container my-5">
    <h2 class="text-center mb-4">Ce que disent nos clients</h2>
    <div class="row">
      <div class="col-md-4 mb-4">
        <div class="testimonial-card">
          <div class="mb-3">
            <?php for($i = 0; $i < 5; $i++): ?>
              <i class="fas fa-star text-warning"></i>
            <?php endfor; ?>
          </div>
          <p class="card-text">"J'ai acheté un bureau vintage pour mon salon et je suis ravie. La qualité est exceptionnelle et il apporte beaucoup de caractère à la pièce."</p>
          <div class="d-flex align-items-center mt-3">
            <img src="<?php echo BASE_URL; ?>/public/images/avis/1.png" alt="Marie L." class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
            <div>
              <h6 class="mb-0">Marie L.</h6>
              <small class="text-muted">Cliente depuis 2023</small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="testimonial-card">
          <div class="mb-3">
            <?php for($i = 0; $i < 5; $i++): ?>
              <i class="fas fa-star text-warning"></i>
            <?php endfor; ?>
          </div>
          <p class="card-text">"Le service client est impeccable et la livraison a été très rapide. Ma table à manger est absolument magnifique, on voit le travail artisanal."</p>
          <div class="d-flex align-items-center mt-3">
            <img src="<?php echo BASE_URL; ?>/public/images/avis/2.png" alt="Thomas D." class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
            <div>
              <h6 class="mb-0">Thomas D.</h6>
              <small class="text-muted">Client depuis 2022</small>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4 mb-4">
        <div class="testimonial-card">
          <div class="mb-3">
            <?php for($i = 0; $i < 4; $i++): ?>
              <i class="fas fa-star text-warning"></i>
            <?php endfor; ?>
            <i class="far fa-star text-warning"></i>
          </div>
          <p class="card-text">"J'apprécie vraiment l'aspect éco-responsable. Avoir un meuble avec une histoire tout en faisant un geste pour la planète, c'est parfait !"</p>
          <div class="d-flex align-items-center mt-3">
            <img src="<?php echo BASE_URL; ?>/public/images/avis/3.png" alt="Sophie B." class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
            <div>
              <h6 class="mb-0">Sophie B.</h6>
              <small class="text-muted">Cliente depuis 2024</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Newsletter Section -->
  <section class="newsletter-section py-5">
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

  <?php include './public/includes/footer.php'; ?>
  
  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
