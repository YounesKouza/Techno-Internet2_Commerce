<?php
/**
 * Page des statistiques de l'administration
 */

// Démarrage de la session
session_start();

// Vérification si l'utilisateur est connecté et est administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Redirection vers la page de connexion
    header('Location: ../../index_.php?page=login&redirect=admin');
    exit;
}

// Inclusion des fichiers nécessaires
require_once '../src/php/utils/connexion.php';
require_once '../src/php/utils/sidebar.php';

// Récupération des paramètres de filtre
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-12 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validation des dates
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Récupération des statistiques
$pdo = getPDO();

// Statistiques des ventes par mois pour la période sélectionnée
$sales_by_month_query = "
    SELECT 
        to_char(date_commande, 'YYYY-MM') AS month,
        SUM(montant_total) AS total
    FROM orders
    WHERE date_commande >= :start_date AND date_commande <= :end_date::date + interval '1 day'
    AND statut != 'cancelled'
    GROUP BY month
    ORDER BY month ASC
";

$stmt = $pdo->prepare($sales_by_month_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$sales_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques des ventes par catégorie pour la période sélectionnée
$sales_by_category_query = "
    SELECT 
        c.nom AS category_name,
        COALESCE(SUM(ol.quantite * ol.prix_unitaire), 0) AS total
    FROM categories c
    LEFT JOIN products p ON c.id = p.categorie_id
    LEFT JOIN order_lines ol ON p.id = ol.produit_id
    LEFT JOIN orders o ON ol.order_id = o.id 
        AND o.statut != 'cancelled'
        AND o.date_commande >= :start_date 
        AND o.date_commande <= :end_date::date + interval '1 day'
    GROUP BY c.id, c.nom
    ORDER BY total DESC
";

$stmt = $pdo->prepare($sales_by_category_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$sales_by_category = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Top 10 produits les plus vendus pour la période sélectionnée
$top_products_query = "
    SELECT 
        p.id,
        p.titre AS name,
        p.prix AS price,
        COALESCE(SUM(ol.quantite), 0) AS quantity_sold,
        COALESCE(SUM(ol.quantite * ol.prix_unitaire), 0) AS total_sales
    FROM products p
    LEFT JOIN order_lines ol ON p.id = ol.produit_id
    LEFT JOIN orders o ON ol.order_id = o.id 
        AND o.statut != 'cancelled'
        AND o.date_commande >= :start_date 
        AND o.date_commande <= :end_date::date + interval '1 day'
    GROUP BY p.id, p.titre, p.prix
    ORDER BY quantity_sold DESC
    LIMIT 10
";

$stmt = $pdo->prepare($top_products_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques d'utilisateurs pour la période sélectionnée
$users_by_month_query = "
    SELECT 
        to_char(date_inscription, 'YYYY-MM') AS month,
        COUNT(*) AS count
    FROM users
    WHERE date_inscription >= :start_date 
    AND date_inscription <= :end_date::date + interval '1 day'
    GROUP BY month
    ORDER BY month ASC
";

$stmt = $pdo->prepare($users_by_month_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$users_by_month = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques de commandes par statut pour la période sélectionnée
$orders_by_status_query = "
    SELECT 
        statut,
        COUNT(*) AS count
    FROM orders
    WHERE date_commande >= :start_date 
    AND date_commande <= :end_date::date + interval '1 day'
    GROUP BY statut
    ORDER BY count DESC
";

$stmt = $pdo->prepare($orders_by_status_query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$orders_by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparation des données pour les graphiques
// Calcul du nombre de mois entre les dates
$start = new DateTime($start_date);
$end = new DateTime($end_date);
$interval = $start->diff($end);
$total_months = ($interval->y * 12) + $interval->m + 1; // +1 pour inclure le mois actuel

// Initialisation des tableaux pour tous les mois de la période sélectionnée
$months_labels = [];
$sales_data = [];
$users_data = [];

// Créer un tableau pour tous les mois dans la plage sélectionnée, sans limitation
$current_date = clone $start;
while ($current_date <= $end) {
    $month = $current_date->format('Y-m');
    $month_label = $current_date->format('M Y');
    
    $months_labels[] = $month_label;
    $sales_data[$month] = 0;
    $users_data[$month] = 0;
    
    $current_date->modify('+1 month');
}

// Remplissage avec les données réelles de ventes
foreach ($sales_by_month as $sale) {
    if (isset($sales_data[$sale['month']])) {
        $sales_data[$sale['month']] = floatval($sale['total']);
    }
}

// Remplissage avec les données réelles d'utilisateurs
foreach ($users_by_month as $user) {
    if (isset($users_data[$user['month']])) {
        $users_data[$user['month']] = intval($user['count']);
    }
}

// Conversion en tableaux simples pour JSON
$sales_values = array_values($sales_data);
$users_values = array_values($users_data);

// Conversion explicite des valeurs en nombres pour éviter les problèmes d'encodage
$sales_values = array_map('floatval', $sales_values);
$users_values = array_map('intval', $users_values);

// Statistiques pour le graphique de catégories
$category_names = [];
$category_sales = [];

foreach ($sales_by_category as $cat) {
    $category_names[] = $cat['category_name'];
    $category_sales[] = floatval($cat['total']);
}

// Statistiques pour le graphique de statuts de commandes
$status_labels = [];
$status_counts = [];

foreach ($orders_by_status as $status) {
    $status_labels[] = ucfirst($status['statut']);
    $status_counts[] = intval($status['count']);
}

// Calcul des indicateurs clés
$total_sales = array_sum($sales_values);
$total_orders = array_sum($status_counts);
$total_new_users = array_sum($users_values);
$avg_monthly_sales = $total_months > 0 ? $total_sales / $total_months : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques | Administration Furniture</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- CSS personnalisé -->
    <link rel="stylesheet" href="/Exos/Techno-internet2_commerce/admin/public/css/style.css">
</head>
<body class="admin-interface">
    <div class="container-fluid">
        <div class="row">
            <?php generate_sidebar('stats'); ?>
            
            <!-- Contenu principal -->
            <div class="col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1>Statistiques</h1>
                    <div>
                        <!-- Informations sur l'utilisateur connecté -->
                        <span class="me-3">
                            <i class="fas fa-user-circle me-1"></i> 
                            <?= htmlspecialchars($_SESSION['username']) ?>
                        </span>
                        <a href="disconnect.php" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-sign-out-alt me-1"></i> Déconnexion
                        </a>
                    </div>
                </div>
                
                <!-- Indicateurs KPI -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Total des ventes</h6>
                                        <h2 class="mt-2 mb-0"><?= number_format($total_sales, 2, ',', ' ') ?> €</h2>
                                    </div>
                                    <i class="fas fa-euro-sign fa-2x value-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-success text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Commandes</h6>
                                        <h2 class="mt-2 mb-0"><?= $total_orders ?></h2>
                                    </div>
                                    <i class="fas fa-shopping-cart fa-2x value-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-info text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Nouveaux clients</h6>
                                        <h2 class="mt-2 mb-0"><?= $total_new_users ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-2x value-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-warning text-dark h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Moy. mensuelle</h6>
                                        <h2 class="mt-2 mb-0"><?= number_format($avg_monthly_sales, 2, ',', ' ') ?> €</h2>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x value-icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filtres de dates -->
                <div class="card filter-controls mb-4">
                    <div class="card-body">
                        <h5 class="mb-3"><i class="fas fa-calendar-alt me-2"></i> Filtrer par période</h5>
                        <form class="row align-items-end" method="get" action="">
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="form-group">
                                    <label class="form-label">Date de début</label>
                                    <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="form-group">
                                    <label class="form-label">Date de fin</label>
                                    <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 mb-md-0">
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-2"></i> Appliquer le filtre
                                    </button>
                                </div>
                            </div>
                        </form>
                        <div class="text-accent mt-3">
                            <i class="fas fa-info-circle me-1"></i> 
                            Période actuelle: <strong>du <?= date('d/m/Y', strtotime($start_date)) ?> au <?= date('d/m/Y', strtotime($end_date)) ?></strong>
                        </div>
                        <div class="mt-2">
                            <a href="statistiques.php" class="btn btn-sm btn-outline-secondary">Réinitialiser</a>
                            <a href="statistiques.php?start_date=<?= date('Y-m-d', strtotime('-1 month')) ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary ms-1">Dernier mois</a>
                            <a href="statistiques.php?start_date=<?= date('Y-m-d', strtotime('-3 months')) ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary ms-1">Dernier trimestre</a>
                            <a href="statistiques.php?start_date=<?= date('Y-m-d', strtotime('-1 year')) ?>&end_date=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary ms-1">Dernière année</a>
                        </div>
                    </div>
                </div>
                
                <!-- Graphiques principaux -->
                <div class="row mb-4">
                    <!-- Graphique des ventes mensuelles -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">Évolution des ventes</h5>
                            </div>
                            <div class="card-body">
                                <div style="height: 350px;">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Graphique des catégories -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">Ventes par catégorie</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categoriesChart" height="250"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <!-- Graphique des nouveaux utilisateurs -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">Nouveaux utilisateurs</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="usersChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Graphique des statuts de commandes -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow-sm h-100">
                            <div class="card-header bg-transparent">
                                <h5 class="mb-0">Commandes par statut</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="ordersStatusChart" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top produits les plus vendus -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0">Top 10 des produits les plus vendus</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table admin-table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Produit</th>
                                        <th>Prix unitaire</th>
                                        <th>Quantité vendue</th>
                                        <th>Chiffre d'affaires</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_products)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-3">Aucune donnée disponible</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td><?= $product['id'] ?></td>
                                                <td><?= htmlspecialchars($product['name']) ?></td>
                                                <td><?= number_format($product['price'], 2, ',', ' ') ?> €</td>
                                                <td><?= $product['quantity_sold'] ?></td>
                                                <td><?= number_format($product['total_sales'], 2, ',', ' ') ?> €</td>
                                                <td>
                                                    <a href="update_meuble.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Pied de page -->
                <footer class="mt-5 text-center text-muted">
                    <p class="mb-1">Furniture - Administration &copy; <?= date('Y') ?></p>
                    <p class="small">Version 1.0.0</p>
                </footer>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JavaScript Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Initialisation des graphiques -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration des couleurs
            const primaryColor = '#2a3f54';
            const secondaryColor = '#6c757d';
            const successColor = '#198754';
            const dangerColor = '#dc3545';
            const warningColor = '#ffc107';
            const infoColor = '#0dcaf0';
            const accentColor = '#1ABB9C';
            
            // Graphique des ventes mensuelles
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode($months_labels) ?>,
                    datasets: [{
                        label: 'Ventes (€)',
                        data: <?= json_encode($sales_values) ?>,
                        backgroundColor: 'rgba(26, 187, 156, 0.1)',
                        borderColor: accentColor,
                        borderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('fr-FR', { 
                                        style: 'currency', 
                                        currency: 'EUR' 
                                    }).format(context.raw);
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                maxRotation: 45,
                                minRotation: 45,
                                autoSkip: true,
                                maxTicksLimit: 20
                            }
                        },
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value + ' €';
                                }
                            }
                        }
                    }
                }
            });
            
            // Graphique des catégories
            const categoryColors = [accentColor, primaryColor, successColor, warningColor, infoColor, dangerColor, secondaryColor];
            const categoriesCtx = document.getElementById('categoriesChart').getContext('2d');
            new Chart(categoriesCtx, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode($category_names) ?>,
                    datasets: [{
                        data: <?= json_encode($category_sales) ?>,
                        backgroundColor: categoryColors,
                        borderColor: '#ffffff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${new Intl.NumberFormat('fr-FR', { 
                                        style: 'currency', 
                                        currency: 'EUR' 
                                    }).format(value)} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
            
            // Graphique des nouveaux utilisateurs
            const usersCtx = document.getElementById('usersChart').getContext('2d');
            new Chart(usersCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($months_labels) ?>,
                    datasets: [{
                        label: 'Nouveaux utilisateurs',
                        data: <?= json_encode($users_values) ?>,
                        backgroundColor: accentColor,
                        borderColor: '#ffffff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Graphique des statuts de commandes
            const statusColors = [successColor, warningColor, primaryColor, dangerColor, secondaryColor];
            const ordersStatusCtx = document.getElementById('ordersStatusChart').getContext('2d');
            new Chart(ordersStatusCtx, {
                type: 'pie',
                data: {
                    labels: <?= json_encode($status_labels) ?>,
                    datasets: [{
                        data: <?= json_encode($status_counts) ?>,
                        backgroundColor: statusColors,
                        borderColor: '#ffffff',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw;
                                    const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
