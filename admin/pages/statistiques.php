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
require_once '../src/php/utils/all_includes.php';

// Récupération des paramètres de filtre
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-12 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validation des dates
if (strtotime($start_date) > strtotime($end_date)) {
    $temp = $start_date;
    $start_date = $end_date;
    $end_date = $temp;
}

// Connexion à la base de données et initialisation des DAO
$pdo = getPDO();
$orderDAO = new OrderDAO($pdo);
$productDAO = new ProductDAO($pdo);
$categoryDAO = new CategoryDAO($pdo);
$userDAO = new UserDAO($pdo);

// Statistiques des ventes par mois pour la période sélectionnée
$sales_by_month = $orderDAO->getSalesByMonth($start_date, $end_date);

// Statistiques des ventes par catégorie pour la période sélectionnée
$sales_by_category = $categoryDAO->getSalesByCategory($start_date, $end_date);

// Top 10 produits les plus vendus pour la période sélectionnée
$top_products = $productDAO->getTopSellingProducts($start_date, $end_date, 10);

// Statistiques d'utilisateurs pour la période sélectionnée
$users_by_month = $userDAO->getUsersByMonth($start_date, $end_date);

// Statistiques de commandes par statut pour la période sélectionnée
$orders_by_status = $orderDAO->getOrdersByStatus($start_date, $end_date);

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
    if (isset($sales_data[$sale->month])) {
        $sales_data[$sale->month] = floatval($sale->total);
    }
}

// Remplissage avec les données réelles d'utilisateurs
foreach ($users_by_month as $user) {
    if (isset($users_data[$user->month])) {
        $users_data[$user->month] = intval($user->count);
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
    $category_names[] = $cat->category_name;
    $category_sales[] = floatval($cat->total);
}

// Statistiques pour le graphique de statuts de commandes
$status_labels = [];
$status_counts = [];

foreach ($orders_by_status as $status) {
    $status_labels[] = ucfirst($status->statut);
    $status_counts[] = intval($status->count);
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
    
    <?php 
    // Appel à la fonction add_body_class pour ajouter automatiquement la classe admin-interface si nécessaire
    add_body_class();
    ?>
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
                
                <!-- Sélection de la période -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form action="" method="get" class="row align-items-end g-3">
                            <div class="col-md-4">
                                <label for="start_date" class="form-label">Date de début</label>
                                <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="end_date" class="form-label">Date de fin</label>
                                <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100">Appliquer le filtre</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Indicateurs KPI -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-primary text-white h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">Chiffre d'affaires</h6>
                                        <h2 class="mt-2 mb-0"><?= number_format($total_sales, 2, ',', ' ') ?> €</h2>
                                    </div>
                                    <i class="fas fa-euro-sign fa-2x"></i>
                                </div>
                                <div class="small mt-2">
                                    <?= $start_date ?> - <?= $end_date ?>
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
                                    <i class="fas fa-shopping-cart fa-2x"></i>
                                </div>
                                <div class="small mt-2">
                                    <?= number_format($total_sales / ($total_orders ?: 1), 2, ',', ' ') ?> € panier moyen
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
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                                <div class="small mt-2">
                                    <?= round($total_new_users / ($total_months ?: 1), 1) ?> nouveaux clients/mois
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card dashboard-card bg-warning h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0 text-dark">Ventes mensuelles</h6>
                                        <h2 class="mt-2 mb-0 text-dark"><?= number_format($avg_monthly_sales, 2, ',', ' ') ?> €</h2>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x text-dark"></i>
                                </div>
                                <div class="small mt-2 text-dark">
                                    Moyenne sur <?= $total_months ?> mois
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Graphiques -->
                <div class="row mb-4">
                    <!-- Graphique des ventes mensuelles -->
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-transparent py-3">
                                <h5 class="mb-0">Évolution des ventes</h5>
                            </div>
                            <div class="card-body">
                                    <canvas id="salesChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Graphique des ventes par catégorie -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent py-3">
                                <h5 class="mb-0">Ventes par catégorie</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <!-- Graphique des nouveaux utilisateurs -->
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-header bg-transparent py-3">
                                <h5 class="mb-0">Nouveaux clients</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="usersChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Graphique des statuts de commande -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-header bg-transparent py-3">
                                <h5 class="mb-0">Commandes par statut</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="statusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top 10 des produits les plus vendus -->
                <div class="card mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Top 10 produits les plus vendus</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>PRODUIT</th>
                                        <th>PRIX UNITAIRE</th>
                                        <th>QUANTITÉ VENDUE</th>
                                        <th>CA GÉNÉRÉ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $index => $product): ?>
                                            <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td><?= htmlspecialchars($product->name) ?></td>
                                            <td><?= number_format($product->price, 2, ',', ' ') ?> €</td>
                                            <td><?= $product->quantite_vendue ?></td>
                                            <td><?= number_format($product->ca_genere, 2, ',', ' ') ?> €</td>
                                            </tr>
                                        <?php endforeach; ?>
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
    
    <!-- JavaScript principal -->
    <script src="/Exos/Techno-internet2_commerce/admin/public/js/fonction.js"></script>
    
    <!-- JavaScript pour les graphiques -->
    <script>
        // Transmission des données PHP vers JavaScript
        const chartData = {
            months_labels: <?= json_encode($months_labels) ?>,
            sales_values: <?= json_encode($sales_values) ?>,
            users_values: <?= json_encode($users_values) ?>,
            category_names: <?= json_encode($category_names) ?>,
            category_sales: <?= json_encode($category_sales) ?>,
            status_labels: <?= json_encode($status_labels) ?>,
            status_counts: <?= json_encode($status_counts) ?>
        };
        
        // Définir window.chartData pour être accessible dans fonction.js
        window.chartData = chartData;
        
        // Initialisation des graphiques
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration globale de Chart.js
            Chart.defaults.font.family = "'Poppins', 'Helvetica', 'Arial', sans-serif";
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#555';
            
            // Graphique des ventes mensuelles
            const salesCtx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: chartData.months_labels,
                    datasets: [{
                        label: 'Chiffre d\'affaires (€)',
                        data: chartData.sales_values,
                        backgroundColor: 'rgba(13, 110, 253, 0.2)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 2,
                        tension: 0.1,
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
                                    return context.parsed.y.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' €';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' €';
                                }
                            }
                        }
                    }
                }
            });
            
            // Graphique des ventes par catégorie
            const categoryCtx = document.getElementById('categoryChart').getContext('2d');
            const categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: chartData.category_names,
                    datasets: [{
                        data: chartData.category_sales,
                        backgroundColor: [
                            'rgba(13, 110, 253, 0.7)',   // Bleu
                            'rgba(25, 135, 84, 0.7)',    // Vert
                            'rgba(220, 53, 69, 0.7)',    // Rouge
                            'rgba(255, 193, 7, 0.7)',    // Jaune
                            'rgba(111, 66, 193, 0.7)',   // Violet
                            'rgba(23, 162, 184, 0.7)',   // Cyan
                            'rgba(102, 16, 242, 0.7)',   // Indigo
                            'rgba(253, 126, 20, 0.7)',   // Orange
                            'rgba(32, 201, 151, 0.7)',   // Teal
                            'rgba(108, 117, 125, 0.7)',  // Gris
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return label + ': ' + value.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + ' € (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
            
            // Graphique des nouveaux utilisateurs
            const usersCtx = document.getElementById('usersChart').getContext('2d');
            const usersChart = new Chart(usersCtx, {
                type: 'bar',
                data: {
                    labels: chartData.months_labels,
                    datasets: [{
                        label: 'Nouveaux clients',
                        data: chartData.users_values,
                        backgroundColor: 'rgba(23, 162, 184, 0.7)',
                        borderColor: 'rgba(23, 162, 184, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Graphique des statuts de commandes
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            const statusChart = new Chart(statusCtx, {
                type: 'pie',
                data: {
                    labels: chartData.status_labels,
                    datasets: [{
                        data: chartData.status_counts,
                        backgroundColor: [
                            'rgba(25, 135, 84, 0.7)',   // Vert (completed)
                            'rgba(13, 110, 253, 0.7)',  // Bleu (processing)
                            'rgba(255, 193, 7, 0.7)',   // Jaune (pending)
                            'rgba(220, 53, 69, 0.7)',   // Rouge (cancelled)
                            'rgba(108, 117, 125, 0.7)', // Gris (autres)
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                                    return label + ': ' + value + ' (' + percentage + '%)';
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
