<?php
// pages/panier.php – Affichage du panier
include '../src/php/db/dbConnect.php';
include '../src/php/utils/fonctions_produits.php';

// Définition de la constante BASE_URL si non définie
if (!defined('BASE_URL')) {
    define('BASE_URL', '/Exos/Techno-internet2_commerce/Techno-internet2_commerce');
}

// Démarrage de la session
session_start();

// Initialisation du panier si nécessaire
if (!isset($_SESSION['panier'])) {
    $_SESSION['panier'] = [];
}

// Gestion des actions sur le panier
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Ajouter un produit au panier
    if ($action === 'ajouter' && isset($_GET['id'])) {
        $productId = (int)$_GET['id'];
        $quantite = isset($_GET['quantite']) ? (int)$_GET['quantite'] : 1;
        $quantite = max(1, $quantite); // Vérification que la quantité est positive
        
        // Récupération des informations du produit
        $product = getProductById($pdo, $productId);
        
        if ($product && $product['stock'] >= $quantite) {
            // Vérifier si le produit existe déjà dans le panier
            $existingKey = null;
            foreach ($_SESSION['panier'] as $key => $item) {
                if ($item['id'] == $productId) {
                    $existingKey = $key;
                    break;
                }
            }
            
            // Mise à jour ou ajout du produit
            if ($existingKey !== null) {
                $_SESSION['panier'][$existingKey]['quantite'] += $quantite;
                // Vérification que la quantité ne dépasse pas le stock
                $_SESSION['panier'][$existingKey]['quantite'] = min(
                    $_SESSION['panier'][$existingKey]['quantite'], 
                    $product['stock']
                );
                $message = "Quantité mise à jour dans votre panier.";
            } else {
                $_SESSION['panier'][] = [
                    'id' => $productId,
                    'titre' => $product['titre'],
                    'prix' => $product['prix'],
                    'image' => $product['image_principale'],
                    'quantite' => $quantite
                ];
                $message = "Produit ajouté à votre panier.";
            }
            
            // Redirection vers la page panier avec un message
            header("Location: panier.php?status=success&message=" . urlencode($message));
            exit;
        } else {
            $message = "Ce produit n'est pas disponible ou en quantité insuffisante.";
            header("Location: panier.php?status=error&message=" . urlencode($message));
            exit;
        }
    }
    // Supprimer un produit du panier
    elseif ($action === 'supprimer' && isset($_GET['index'])) {
        $index = (int)$_GET['index'];
        if (isset($_SESSION['panier'][$index])) {
            unset($_SESSION['panier'][$index]);
            // Réindexer le tableau
            $_SESSION['panier'] = array_values($_SESSION['panier']);
        }
        header("Location: panier.php?status=success&message=" . urlencode("Produit retiré du panier."));
        exit;
    }
    // Mettre à jour la quantité d'un produit
    elseif ($action === 'update' && isset($_POST['quantite']) && is_array($_POST['quantite'])) {
        foreach ($_POST['quantite'] as $index => $quantite) {
            $index = (int)$index;
            $quantite = (int)$quantite;
            
            if (isset($_SESSION['panier'][$index]) && $quantite > 0) {
                // Vérifier le stock disponible
                $productId = $_SESSION['panier'][$index]['id'];
                $product = getProductById($pdo, $productId);
                
                if ($product) {
                    // Mettre à jour la quantité en respectant le stock
                    $_SESSION['panier'][$index]['quantite'] = min($quantite, $product['stock']);
                }
            } elseif (isset($_SESSION['panier'][$index]) && $quantite <= 0) {
                // Supprimer le produit si quantité nulle ou négative
                unset($_SESSION['panier'][$index]);
            }
        }
        // Réindexer le tableau après les suppressions
        $_SESSION['panier'] = array_values($_SESSION['panier']);
        
        header("Location: panier.php?status=success&message=" . urlencode("Panier mis à jour."));
        exit;
    }
    // Vider le panier
    elseif ($action === 'vider') {
        $_SESSION['panier'] = [];
        header("Location: panier.php?status=success&message=" . urlencode("Votre panier a été vidé."));
        exit;
    }
}

// Calcul du total du panier
$total = 0;
foreach ($_SESSION['panier'] as $item) {
    $total += $item['prix'] * $item['quantite'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre panier - Furniture</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/public/css/style.css">
</head>
<body>
    <?php include '../public/includes/header.php'; ?>
    
    <div class="container my-5">
        <h1 class="mb-4">Votre panier</h1>
        
        <?php if (isset($_GET['status'])): ?>
            <div class="alert alert-<?php echo ($_GET['status'] === 'success') ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['message'] ?? 'Action effectuée.'); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fermer"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($_SESSION['panier'])): ?>
            <div class="alert alert-info">
                Votre panier est vide. <a href="catalogue.php" class="alert-link">Parcourir notre catalogue</a>
            </div>
        <?php else: ?>
            <form action="panier.php?action=update" method="POST">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Produit</th>
                                <th>Prix unitaire</th>
                                <th>Quantité</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['panier'] as $index => $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php 
                                            $imagePath = !empty($item['image']) 
                                                ? $item['image'] 
                                                : BASE_URL . '/public/images/produits/default.jpg';
                                            ?>
                                            <img src="<?php echo $imagePath; ?>" class="img-thumbnail me-3" style="width: 60px; height: 60px; object-fit: cover;" 
                                                 alt="<?php echo htmlspecialchars($item['titre']); ?>">
                                            <div>
                                                <h6 class="mb-0"><?php echo htmlspecialchars($item['titre']); ?></h6>
                                                <a href="produit_detail.php?id=<?php echo $item['id']; ?>" class="text-muted small">
                                                    Voir le produit
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($item['prix'], 2, ',', ' '); ?> €</td>
                                    <td>
                                        <input type="number" name="quantite[<?php echo $index; ?>]" value="<?php echo $item['quantite']; ?>" 
                                               min="1" max="10" class="form-control form-control-sm" style="width: 70px;">
                                    </td>
                                    <td><?php echo number_format($item['prix'] * $item['quantite'], 2, ',', ' '); ?> €</td>
                                    <td>
                                        <a href="panier.php?action=supprimer&index=<?php echo $index; ?>" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end"><strong>Total :</strong></td>
                                <td><strong><?php echo number_format($total, 2, ',', ' '); ?> €</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="catalogue.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Continuer mes achats
                        </a>
                        <a href="panier.php?action=vider" class="btn btn-outline-danger ms-2" 
                           onclick="return confirm('Êtes-vous sûr de vouloir vider votre panier ?');">
                            <i class="fas fa-trash me-2"></i> Vider le panier
                        </a>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-outline-primary me-2">
                            <i class="fas fa-sync-alt me-2"></i> Mettre à jour le panier
                        </button>
                        <a href="checkout.php" class="btn btn-primary">
                            <i class="fas fa-check me-2"></i> Procéder au paiement
                        </a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php include '../public/includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
