<?php
/**
 * Page de confirmation de commande
 */

// Titre de la page
$titre_page = 'Commande confirmée';

// Vérifier si l'ID de la dernière commande est en session
if (!isset($_SESSION['last_order_id']) && !isset($_SESSION['flash_messages'])) {
    // Si pas d'ID et pas de message flash (cas où on recharge la page)
    // On pourrait aussi vérifier spécifiquement un message flash de succès de commande
    header('Location: index_.php?page=accueil');
    exit;
}

$last_order_id = $_SESSION['last_order_id'] ?? null;
$order = null;

// Optionnel : Récupérer les détails de la commande pour affichage si l'ID existe
if ($last_order_id) {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$last_order_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();
}

// Afficher les messages flash (s'ils existent)
$flash_messages = $_SESSION['flash_messages'] ?? [];
unset($_SESSION['flash_messages']); // Nettoyer après affichage

// Nettoyer l'ID de commande de la session pour éviter de le réafficher
unset($_SESSION['last_order_id']);

?>

<div class="container py-5 text-center">
    <div class="card shadow-sm mx-auto" style="max-width: 600px;">
        <div class="card-body p-5">
            <i class="fas fa-check-circle fa-5x text-success mb-4"></i>
            <h1 class="card-title h2 mb-3">Merci pour votre commande !</h1>
            
            <?php foreach ($flash_messages as $msg): ?>
                <div class="alert alert-<?= htmlspecialchars($msg['type']) ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($msg['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endforeach; ?>

            <?php if ($order): ?>
                <p class="lead">Votre commande numéro <strong>#<?= htmlspecialchars($order['id']) ?></strong> a été enregistrée avec succès.</p>
                <p>Elle a été marquée comme <strong>livrée</strong> instantanément.</p>
                <p>Un récapitulatif vous sera envoyé par email (fonctionnalité non implémentée).</p>
                <hr class="my-4">
                <p class="mb-0">Total de la commande : <strong><?= number_format($order['total'], 2, ',', ' ') ?> €</strong></p>
            <?php elseif (empty($flash_messages)): // Afficher un message générique si pas d'order et pas de flash ?>
                 <p class="lead">Votre commande a été enregistrée avec succès.</p>
                 <p>Elle a été marquée comme <strong>livrée</strong> instantanément.</p>
            <?php endif; ?>

            <div class="mt-4">
                <a href="index_.php?page=compte&section=commandes" class="btn btn-outline-primary me-2">Voir mes commandes</a>
                <a href="index_.php?page=catalogue" class="btn btn-primary">Continuer mes achats</a>
            </div>
        </div>
    </div>
</div> 