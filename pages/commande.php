<?php
/**
 * Page de commande
 * Permet aux utilisateurs de finaliser leur achat
 */

// Configuration de l'encodage
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Inclusion des fichiers nécessaires
require_once __DIR__ . '/../admin/src/php/utils/connexion.php';
require_once __DIR__ . '/../admin/src/php/utils/all_includes.php';

// Titre de la page
$titre_page = 'Finaliser votre commande';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Sauvegarder l'URL actuelle pour y revenir après connexion
    $_SESSION['redirect_after_login'] = 'index_.php?page=commande';
    
    // Rediriger vers la page de connexion
    header('Location: index_.php?page=login');
    exit;
}

// Vérifier si le panier est vide
if (!isset($_SESSION['panier']) || empty($_SESSION['panier'])) {
    header('Location: index_.php?page=panier');
    exit;
}

// Initialisation des objets DAO
$pdo = getPDO();
$userDAO = new UserDAO($pdo);
$productDAO = new ProductDAO($pdo);
$orderDAO = new OrderDAO($pdo);
$cartDAO = new CartDAO($pdo);

// Récupérer les informations de l'utilisateur
$user = $userDAO->findById($_SESSION['user_id']);

// Récupérer les produits du panier
$cartData = $cartDAO->getCartDetails();
$panier_avec_details = $cartData['items'];
$total_commande = $cartData['total'];

// Calcul des frais de livraison
$frais_livraison = 5.99; // Frais de livraison par défaut
$seuil_livraison_gratuite = 150; // Seuil pour la livraison gratuite

// Vérifier si le total dépasse le seuil pour la livraison gratuite
if ($total_commande >= $seuil_livraison_gratuite) {
    $frais_livraison = 0;
}

// Total final avec frais de livraison
$total_final = $total_commande + $frais_livraison;

// Initialisation des variables pour le formulaire
$nom = $user->nom ?? '';
$email = $user->email ?? '';
$telephone = $user->telephone ?? '';
$mode_paiement = '';
$numero_carte = '';
$date_expiration = '';
$cvv = '';

// Message d'erreur ou de succès
$error_message = '';
$success_message = '';

// Traitement du formulaire de commande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_commande'])) {
    // Récupération des données du formulaire
    $nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $telephone = isset($_POST['telephone']) ? trim($_POST['telephone']) : '';
    $mode_paiement = isset($_POST['mode_paiement']) ? $_POST['mode_paiement'] : '';
    $numero_carte = isset($_POST['numero_carte']) ? $_POST['numero_carte'] : '';
    $date_expiration = isset($_POST['date_expiration']) ? $_POST['date_expiration'] : '';
    $cvv = isset($_POST['cvv']) ? $_POST['cvv'] : '';
    
    // Debug: afficher la structure de la base de données
    try {
        $pdo = getPDO();
        $tablesQuery = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'";
        $tablesStmt = $pdo->query($tablesQuery);
        $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Journaliser les tables
        error_log("Tables dans la base de données: " . implode(", ", $tables));
        
        // Vérifier la structure de la table orders
        $ordersStructQuery = "SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'orders'";
        $ordersStructStmt = $pdo->query($ordersStructQuery);
        $ordersStruct = $ordersStructStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Journaliser la structure
        error_log("Structure de la table orders:");
        foreach($ordersStruct as $column) {
            error_log(" - " . $column['column_name'] . ": " . $column['data_type']);
        }
    } catch (Exception $e) {
        error_log("Erreur lors de l'analyse de la base de données: " . $e->getMessage());
    }
    
    // Création de l'objet Payment pour valider les informations de paiement
    $paymentData = [
        'mode_paiement' => $mode_paiement,
        'numero_carte' => $numero_carte,
        'date_expiration' => $date_expiration,
        'cvv' => $cvv
    ];
    $payment = new Payment($paymentData);
    
    // Création d'un tableau avec les données du formulaire
    $formData = [
        'nom' => $nom,
        'email' => $email,
        'telephone' => $telephone,
        'payment' => $payment
    ];
    
    // Validation des données et traitement de la commande
    try {
        // Valider les données du formulaire
        $validationResult = $orderDAO->validateOrderData($formData);
        
        if ($validationResult['valid']) {
            // Utiliser les informations existantes de l'utilisateur pour l'adresse
            $orderData = [
                'utilisateur_id' => $_SESSION['user_id'],
                'montant_total' => $total_final,
                'statut' => 'en attente',
                'adresse_livraison' => $user->adresse ?? '',
                'ville_livraison' => $user->ville ?? '',
                'code_postal_livraison' => $user->code_postal ?? '',
                'pays_livraison' => $user->pays ?? '',
                'telephone' => $telephone
            ];
            
            // Debug: Afficher les données de commande
            error_log("Données de commande: " . json_encode($orderData));
            error_log("Items panier: " . json_encode($panier_avec_details));
            
            // Vérification de l'adresse
            if (empty($orderData['adresse_livraison']) || empty($orderData['ville_livraison']) || 
                empty($orderData['code_postal_livraison']) || empty($orderData['pays_livraison'])) {
                error_log("ATTENTION: Informations d'adresse manquantes ou incomplètes");
                
                // Si l'adresse est manquante, on utilise une valeur par défaut
                if (empty($orderData['adresse_livraison'])) $orderData['adresse_livraison'] = 'Non spécifiée';
                if (empty($orderData['ville_livraison'])) $orderData['ville_livraison'] = 'Non spécifiée';
                if (empty($orderData['code_postal_livraison'])) $orderData['code_postal_livraison'] = '00000';
                if (empty($orderData['pays_livraison'])) $orderData['pays_livraison'] = 'BE';
            }
            
            // Traitement de la commande
            $processResult = $orderDAO->processOrder($orderData, $panier_avec_details, [
                'mode_paiement' => $mode_paiement,
                'numero_carte' => $numero_carte,
                'date_expiration' => $date_expiration,
                'cvv' => $cvv
            ]);
            error_log("Résultat du processus: " . json_encode($processResult));
            
            if ($processResult && $processResult !== false) {
                // Mise à jour du téléphone si modifié
                    $userData = [
                        'telephone' => $telephone
                    ];
                $userDAO->update($_SESSION['user_id'], $userData);
                
                // Vider le panier
                $cartDAO->clearCart();
                
                // Stocker l'ID de commande en session pour la page de confirmation
                $_SESSION['last_order_id'] = $processResult;
                
                // Rediriger vers la page de confirmation
                header('Location: index_.php?page=commande_succes');
                exit;
            } else {
                $error_message = "Erreur lors du traitement de la commande";
                // Debug: Afficher plus de détails sur l'erreur
                error_log("Échec du traitement de la commande: commande non créée");
            }
        } else {
            // Afficher les erreurs
            $error_message = implode('<br>', $validationResult['errors']);
            error_log("Erreur de validation: " . $error_message);
        }
    } catch (Exception $e) {
        // Afficher l'erreur pour le débogage
        $error_message = 'Erreur lors de la création de la commande: ' . $e->getMessage();
        error_log("Exception lors de la création de commande: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
    }
}
?>

<div class="container py-5">
    <h1 class="mb-4">Finaliser votre commande</h1>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle me-2"></i> <?= $success_message ?>
        </div>
        
        <div class="text-center my-5">
            <a href="index_.php?page=catalogue" class="btn btn-primary">
                <i class="fas fa-shopping-bag me-2"></i> Continuer vos achats
            </a>
            <a href="index_.php?page=compte" class="btn btn-outline-primary ms-2">
                <i class="fas fa-user me-2"></i> Accéder à votre compte
            </a>
        </div>
    <?php else: ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $error_message ?>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Formulaire de commande -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="post" action="index_.php?page=commande" id="commande-form" novalidate>
                            <!-- Informations personnelles -->
                            <h4 class="mb-3">Informations personnelles</h4>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="nom" class="form-label">Nom complet <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($nom) ?>" required>
                                    <div class="invalid-feedback">Le nom est obligatoire.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                                    <div class="invalid-feedback">Veuillez entrer une adresse email valide.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($telephone) ?>" required>
                                    <div class="invalid-feedback">Le téléphone est obligatoire pour la livraison.</div>
                                </div>
                            </div>
                            
                            <!-- Mode de paiement -->
                            <h4 class="mb-3">Mode de paiement</h4>
                            <div class="mb-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_carte" value="carte" <?= $mode_paiement === 'carte' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="paiement_carte">
                                        <i class="fas fa-credit-card me-2"></i> Carte bancaire
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_paypal" value="paypal" <?= $mode_paiement === 'paypal' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="paiement_paypal">
                                        <i class="fab fa-paypal me-2"></i> PayPal
                                    </label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_virement" value="virement" <?= $mode_paiement === 'virement' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="paiement_virement">
                                        <i class="fas fa-university me-2"></i> Virement bancaire
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Détails de la carte (affichés conditionnellement) -->
                            <div id="details-carte" class="mb-4 conditional-section <?= $mode_paiement === 'carte' ? 'active' : '' ?>">
                                <h5 class="mb-3">Détails de la carte</h5>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="numero_carte" class="form-label">Numéro de carte <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="numero_carte" name="numero_carte" value="<?= htmlspecialchars($numero_carte) ?>" placeholder="XXXX XXXX XXXX XXXX">
                                        <div class="invalid-feedback">Veuillez entrer un numéro de carte valide.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="date_expiration" class="form-label">Date d'expiration <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="date_expiration" name="date_expiration" value="<?= htmlspecialchars($date_expiration) ?>" placeholder="MM/YY">
                                        <div class="invalid-feedback">Veuillez entrer une date d'expiration valide.</div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="cvv" class="form-label">Code de sécurité <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="cvv" name="cvv" value="<?= htmlspecialchars($cvv) ?>" placeholder="123">
                                        <div class="invalid-feedback">Veuillez entrer un code de sécurité valide.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Bouton de validation -->
                            <div class="d-grid">
                                <input type="hidden" name="valider_commande" value="1">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-check-circle me-2"></i> Valider et payer
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Récapitulatif de la commande -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Récapitulatif de la commande</h5>
                    </div>
                    <div class="card-body">
                        <!-- Produits dans le panier -->
                        <?php foreach ($panier_avec_details as $produit): ?>
                            <div class="d-flex mb-3">
                                <div class="flex-shrink-0">
                                    <img src="<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['titre']) ?>" class="img-thumbnail cart-product-thumbnail">
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-0"><?= htmlspecialchars($produit['titre']) ?></h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><?= $produit['quantity'] ?> x <?= number_format($produit['prix'], 2, ',', ' ') ?> €</small>
                                        <span><?= number_format($produit['subtotal'], 2, ',', ' ') ?> €</span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <!-- Sous-total -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Sous-total</span>
                            <span><?= number_format($total_commande, 2, ',', ' ') ?> €</span>
                        </div>
                        
                        <!-- Frais de livraison -->
                        <div class="d-flex justify-content-between mb-2">
                            <span>Frais de livraison</span>
                            <?php if ($frais_livraison > 0): ?>
                                <span><?= number_format($frais_livraison, 2, ',', ' ') ?> €</span>
                            <?php else: ?>
                                <span class="text-success">Gratuit</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Total -->
                        <div class="d-flex justify-content-between fw-bold mb-0">
                            <span>Total</span>
                            <span><?= number_format($total_final, 2, ',', ' ') ?> €</span>
                        </div>
                        
                        <?php if ($total_commande < $seuil_livraison_gratuite): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">
                                    Ajoutez encore <?= number_format($seuil_livraison_gratuite - $total_commande, 2, ',', ' ') ?> € 
                                    pour bénéficier de la livraison gratuite !
                                </small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
