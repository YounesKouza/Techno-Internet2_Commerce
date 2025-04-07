<?php
/**
 * Page de commande
 * Permet aux utilisateurs de finaliser leur achat
 */

// Configuration de l'encodage
header('Content-Type: text/html; charset=utf-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

// Inclusion du fichier de connexion
require_once __DIR__ . '/../admin/src/php/utils/connexion.php';

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

// Récupérer les informations de l'utilisateur
$pdo = getPDO();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Récupérer les produits du panier
$panier_avec_details = [];
$total_commande = 0;
$frais_livraison = 5.99; // Frais de livraison par défaut
$seuil_livraison_gratuite = 150; // Seuil pour la livraison gratuite

foreach ($_SESSION['panier'] as $productId => $item) {
    $stmt = $pdo->prepare("SELECT id, titre, prix, image_principale FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $produit = $stmt->fetch();
    
    if ($produit) {
        $sous_total = $produit['prix'] * $item['quantity'];
        $total_commande += $sous_total;
        
        $panier_avec_details[] = [
            'id' => $produit['id'],
            'titre' => $produit['titre'],
            'prix' => $produit['prix'],
            'image' => $produit['image_principale'],
            'quantite' => $item['quantity'],
            'sous_total' => $sous_total
        ];
    }
}

// Vérifier si le total dépasse le seuil pour la livraison gratuite
if ($total_commande >= $seuil_livraison_gratuite) {
    $frais_livraison = 0;
}

// Total final avec frais de livraison
$total_final = $total_commande + $frais_livraison;

// Initialisation des variables pour le formulaire
$nom = $user['nom'] ?? '';
$email = $user['email'] ?? '';
$telephone = $user['telephone'] ?? '';
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
    
    // Validation des données
    $errors = [];
    
    if (empty($nom)) {
        $errors[] = 'Le nom est obligatoire';
    }
    
    if (empty($email)) {
        $errors[] = 'L\'email est obligatoire';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'L\'email n\'est pas valide';
    }
    
    if (empty($telephone)) {
        $errors[] = 'Le téléphone est obligatoire pour la livraison';
    }
    
    if (empty($mode_paiement)) {
        $errors[] = 'Veuillez sélectionner un mode de paiement';
    } elseif ($mode_paiement === 'carte') {
        if (empty($numero_carte)) {
            $errors[] = 'Le numéro de carte est obligatoire';
        } elseif (!preg_match('/^[0-9]{16}$/', preg_replace('/\s+/', '', $numero_carte))) {
            $errors[] = 'Le numéro de carte n\'est pas valide';
        }
        
        if (empty($date_expiration)) {
            $errors[] = 'La date d\'expiration est obligatoire';
        } elseif (!preg_match('/^(0[1-9]|1[0-2])\/[0-9]{2}$/', $date_expiration)) {
            $errors[] = 'La date d\'expiration n\'est pas valide (MM/AA)';
        }
        
        if (empty($cvv)) {
            $errors[] = 'Le code de sécurité est obligatoire';
        } elseif (!preg_match('/^[0-9]{3,4}$/', $cvv)) {
            $errors[] = 'Le code de sécurité n\'est pas valide';
        }
    }
    
    // Si aucune erreur, procéder à l'enregistrement de la commande
    if (empty($errors)) {
        try {
            // Démarrer une transaction
            $pdo->beginTransaction();
            
            // Enregistrer la commande
            $stmt = $pdo->prepare("
                INSERT INTO orders (
                    utilisateur_id, date_commande, montant_total, statut
                ) VALUES (?, CURRENT_TIMESTAMP, ?, 'en attente')
            ");
            
            $stmt->execute([
                $_SESSION['user_id'],
                $total_final
            ]);
            
            $commande_id = $pdo->lastInsertId();
            
            // Enregistrer les lignes de commande
            foreach ($panier_avec_details as $produit) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_lines (
                        order_id, produit_id, quantite, prix_unitaire
                    ) VALUES (?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $commande_id,
                    $produit['id'],
                    $produit['quantite'],
                    $produit['prix']
                ]);
                
                // Mettre à jour le stock du produit
                $update_stock = $pdo->prepare("
                    UPDATE products 
                    SET stock = stock - ? 
                    WHERE id = ? AND stock >= ?
                ");
                
                $update_stock->execute([
                    $produit['quantite'],
                    $produit['id'],
                    $produit['quantite']
                ]);
            }
            
            // Mise à jour des infos utilisateur si modifiées
            $stmt = $pdo->prepare("
                UPDATE users SET telephone = ? WHERE id = ?
            ");
            
            $stmt->execute([$telephone, $_SESSION['user_id']]);
            
            // Valider la transaction
            $pdo->commit();
            
            // Vider le panier
            $_SESSION['panier'] = [];
            
            // Message de succès
            $success_message = 'Votre commande a été enregistrée avec succès! Le numéro de votre commande est: ' . $commande_id;
            
        } catch (PDOException $e) {
            // Annuler la transaction en cas d'erreur
            $pdo->rollBack();
            // Afficher l'erreur réelle pour le débogage
            $error_message = 'Erreur: ' . $e->getMessage();
        }
    } else {
        // Afficher les erreurs
        $error_message = implode('<br>', $errors);
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
                                    <div class="invalid-feedback">Une adresse email valide est requise.</div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="telephone" class="form-label">Téléphone <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($telephone) ?>" required>
                                    <div class="form-text">Nécessaire pour la livraison</div>
                                </div>
                            </div>
                            
                            <!-- Mode de paiement -->
                            <h4 class="mb-3">Mode de paiement</h4>
                            <div class="mb-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_carte" value="carte" <?= $mode_paiement === 'carte' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="paiement_carte">
                                        <i class="fab fa-cc-visa me-2"></i> Carte bancaire
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_paypal" value="paypal" <?= $mode_paiement === 'paypal' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="paiement_paypal">
                                        <i class="fab fa-paypal me-2"></i> PayPal
                                    </label>
                                </div>
                                
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="mode_paiement" id="paiement_virement" value="virement" <?= $mode_paiement === 'virement' ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="paiement_virement">
                                        <i class="fas fa-university me-2"></i> Virement bancaire
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Détails de la carte (conditionnellement affichés) -->
                            <div id="details-carte" class="mb-4 border p-3 rounded <?= $mode_paiement === 'carte' ? '' : 'd-none' ?>">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="numero_carte" class="form-label">Numéro de carte</label>
                                        <input type="text" class="form-control" id="numero_carte" name="numero_carte" value="<?= htmlspecialchars($numero_carte) ?>" placeholder="1234 5678 9012 3456">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label for="date_expiration" class="form-label">Date d'expiration</label>
                                        <input type="text" class="form-control" id="date_expiration" name="date_expiration" value="<?= htmlspecialchars($date_expiration) ?>" placeholder="MM/AA">
                                    </div>
                                    
                                    <div class="col-md-3 mb-3">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" class="form-control" id="cvv" name="cvv" value="<?= htmlspecialchars($cvv) ?>" placeholder="123">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Conditions générales -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="cgv_accept" name="cgv_accept" required>
                                    <label class="form-check-label" for="cgv_accept">
                                        J'accepte les <a href="#" target="_blank">conditions générales de vente</a> <span class="text-danger">*</span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Bouton de soumission -->
                            <input type="hidden" name="valider_commande" value="1">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-lock me-2"></i> Valider et payer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Récapitulatif de la commande -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Récapitulatif de votre commande</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Articles (<?= count($panier_avec_details) ?>)</span>
                            <span><?= number_format($total_commande, 2, ',', ' ') ?> €</span>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-1 small">
                            <span>Frais de livraison</span>
                            <?php if ($frais_livraison > 0): ?>
                                <span><?= number_format($frais_livraison, 2, ',', ' ') ?> €</span>
                            <?php else: ?>
                                <span class="text-success">Gratuit</span>
                            <?php endif; ?>
                        </div>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total</strong>
                            <strong><?= number_format($total_final, 2, ',', ' ') ?> €</strong>
                        </div>
                        
                        <?php if ($frais_livraison > 0): ?>
                            <div class="alert alert-info small mb-0">
                                <i class="fas fa-info-circle me-2"></i> Plus que <?= number_format($seuil_livraison_gratuite - $total_commande, 2, ',', ' ') ?> € d'achat pour bénéficier de la livraison gratuite !
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success small mb-0">
                                <i class="fas fa-check-circle me-2"></i> Vous bénéficiez de la livraison gratuite !
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Articles dans votre panier</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach($panier_avec_details as $produit): ?>
                                <li class="list-group-item">
                                    <div class="d-flex">
                                        <?php if (!empty($produit['image'])): ?>
                                            <img src="<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['titre']) ?>" class="img-thumbnail me-2" style="width: 60px; height: 60px; object-fit: cover;">
                                        <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center me-2" style="width: 60px; height: 60px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div>
                                            <div class="fw-bold small"><?= htmlspecialchars($produit['titre']) ?></div>
                                            <div class="small">
                                                <?= $produit['quantite'] ?> × <?= number_format($produit['prix'], 2, ',', ' ') ?> €
                                            </div>
                                            <div class="text-primary">
                                                <?= number_format($produit['sous_total'], 2, ',', ' ') ?> €
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="index_.php?page=panier" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-edit me-1"></i> Modifier le panier
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Besoin d'aide ?</h5>
                        <p class="card-text small">Notre service client est disponible du lundi au vendredi de 9h à 18h</p>
                        <p class="mb-0">
                            <i class="fas fa-phone me-1"></i> <a href="tel:+33123456789">01 23 45 67 89</a>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope me-1"></i> <a href="mailto:contact@example.com">contact@example.com</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'affichage des détails de carte selon le mode de paiement
    const radioPaiements = document.querySelectorAll('input[name="mode_paiement"]');
    const detailsCarte = document.getElementById('details-carte');
    
    radioPaiements.forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'carte') {
                detailsCarte.classList.remove('d-none');
            } else {
                detailsCarte.classList.add('d-none');
            }
        });
    });
    
    // Formatage du numéro de carte
    const numeroCarte = document.getElementById('numero_carte');
    if (numeroCarte) {
        numeroCarte.addEventListener('input', function(e) {
            // Enlever tous les espaces
            let value = this.value.replace(/\s+/g, '');
            // Ajouter un espace tous les 4 chiffres
            value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
            // Limiter à 19 caractères (16 chiffres + 3 espaces)
            if (value.length > 19) {
                value = value.substr(0, 19);
            }
            this.value = value;
        });
    }
    
    // Formatage de la date d'expiration
    const dateExpiration = document.getElementById('date_expiration');
    if (dateExpiration) {
        dateExpiration.addEventListener('input', function(e) {
            // Enlever tous les caractères non numériques
            let value = this.value.replace(/\D/g, '');
            
            // Formater MM/AA
            if (value.length > 2) {
                value = value.substr(0, 2) + '/' + value.substr(2, 2);
            }
            
            this.value = value;
        });
    }
    
    // Validation du formulaire
    const form = document.getElementById('commande-form');
    if (form) {
        form.addEventListener('submit', function(event) {
            const modeSelected = document.querySelector('input[name="mode_paiement"]:checked');
            
            if (!modeSelected) {
                alert('Veuillez sélectionner un mode de paiement');
                event.preventDefault();
                return false;
            }
            
            if (modeSelected.value === 'carte') {
                const numeroCarte = document.getElementById('numero_carte').value.replace(/\s+/g, '');
                const dateExpiration = document.getElementById('date_expiration').value;
                const cvv = document.getElementById('cvv').value;
                
                if (numeroCarte.length !== 16 || !/^\d+$/.test(numeroCarte)) {
                    alert('Veuillez entrer un numéro de carte valide');
                    event.preventDefault();
                    return false;
                }
                
                if (!dateExpiration || !/^(0[1-9]|1[0-2])\/[0-9]{2}$/.test(dateExpiration)) {
                    alert('Veuillez entrer une date d\'expiration valide (MM/AA)');
                    event.preventDefault();
                    return false;
                }
                
                if (!cvv || !/^[0-9]{3,4}$/.test(cvv)) {
                    alert('Veuillez entrer un code de sécurité valide');
                    event.preventDefault();
                    return false;
                }
            }
        });
    }
});
</script>
