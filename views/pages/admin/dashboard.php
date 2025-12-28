<?php
require_once __DIR__ . '/../../../includes/admin_guard.php';
require_once __DIR__ . '/../../../controllers/admin/DashboardController.php';

$pageTitle = 'Dashboard';
$dashboardController = new DashboardController();
$stats = $dashboardController->getStatistics();
$salesChart = $dashboardController->getSalesChartData(7);
$monthlySales = $dashboardController->getMonthlySalesData();
$topProducts = $dashboardController->getTopProducts(5);
$recentOrders = $dashboardController->getRecentOrders(5);

include __DIR__ . '/../../../includes/admin/header.php';
?>

<div class="admin-header-bar">
    <div>
        <p class="admin-breadcrumb">Admin / Dashboard</p>
        <h1>DASHBOARD</h1>
    </div>
    <div>
        <span class="admin-date"><?= date('l, F j, Y') ?></span>
    </div>
</div>

<!-- Statistics Cards with Hover Details -->
<div class="admin-stats">
    <!-- Users Card -->
    <div class="stat-card" data-tooltip="true">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['users']['total']) ?></h3>
            <p>Total Users</p>
        </div>
        <div class="stat-tooltip">
            <h4>User Statistics</h4>
            <ul>
                <li><span>Today:</span> <strong><?= $stats['users']['today'] ?></strong></li>
                <li><span>This Week:</span> <strong><?= $stats['users']['this_week'] ?></strong></li>
                <li><span>This Month:</span> <strong><?= $stats['users']['this_month'] ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- Products Card -->
    <div class="stat-card" data-tooltip="true">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['products']['total']) ?></h3>
            <p>Total Products</p>
        </div>
        <div class="stat-tooltip">
            <h4>Product Statistics</h4>
            <ul>
                <li><span>Active:</span> <strong><?= $stats['products']['active'] ?></strong></li>
                <li><span>Inactive:</span> <strong><?= $stats['products']['inactive'] ?></strong></li>
                <li><span>Out of Stock:</span> <strong class="text-danger"><?= $stats['products']['out_of_stock'] ?></strong></li>
                <li><span>Low Stock:</span> <strong class="text-warning"><?= $stats['products']['low_stock'] ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- Orders Card -->
    <div class="stat-card" data-tooltip="true">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3><?= number_format($stats['orders']['total']) ?></h3>
            <p>Total Orders</p>
        </div>
        <div class="stat-tooltip">
            <h4>Order Statistics</h4>
            <ul>
                <li><span>Today:</span> <strong><?= $stats['orders']['today'] ?></strong></li>
                <li><span>This Week:</span> <strong><?= $stats['orders']['this_week'] ?></strong></li>
                <li><span>This Month:</span> <strong><?= $stats['orders']['this_month'] ?></strong></li>
                <li><span>Pending:</span> <strong class="text-warning"><?= $stats['orders']['pending'] ?></strong></li>
                <li><span>Processing:</span> <strong class="text-info"><?= $stats['orders']['processing'] ?></strong></li>
                <li><span>Delivered:</span> <strong class="text-success"><?= $stats['orders']['delivered'] ?></strong></li>
            </ul>
        </div>
    </div>

    <!-- Revenue Card -->
    <div class="stat-card" data-tooltip="true">
        <div class="stat-icon">
            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div class="stat-content">
            <h3>RM <?= number_format($stats['revenue']['total'], 2) ?></h3>
            <p>Total Revenue</p>
        </div>
        <div class="stat-tooltip">
            <h4>Revenue Statistics</h4>
            <ul>
                <li><span>Today:</span> <strong>RM <?= number_format($stats['revenue']['today'], 2) ?></strong></li>
                <li><span>This Week:</span> <strong>RM <?= number_format($stats['revenue']['this_week'], 2) ?></strong></li>
                <li><span>This Month:</span> <strong>RM <?= number_format($stats['revenue']['this_month'], 2) ?></strong></li>
                <li><span>This Year:</span> <strong>RM <?= number_format($stats['revenue']['this_year'], 2) ?></strong></li>
            </ul>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="dashboard-charts">
    <!-- Sales Chart -->
    <div class="admin-card chart-card">
        <div class="admin-card-header">
            <h2>Sales Overview (Last 7 Days)</h2>
            <div class="chart-legend">
                <span class="legend-item"><span class="legend-color" style="background: #4CAF50;"></span> Revenue</span>
                <span class="legend-item"><span class="legend-color" style="background: #2196F3;"></span> Orders</span>
            </div>
        </div>
        <canvas id="salesChart" height="80"></canvas>
    </div>

    <!-- Monthly Sales Chart -->
    <div class="admin-card chart-card">
        <div class="admin-card-header">
            <h2>Monthly Sales (<?= date('Y') ?>)</h2>
        </div>
        <canvas id="monthlySalesChart" height="80"></canvas>
    </div>
</div>

<!-- Data Tables Section -->
<div class="dashboard-tables">
    <!-- Top Products -->
    <div class="admin-card">
        <h2 class="admin-card-header">Top Selling Products</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topProducts)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem;">No sales data available</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']) ?></td>
                                <td>RM <?= number_format($product['price'], 2) ?></td>
                                <td><?= number_format($product['total_sold']) ?> units</td>
                                <td>RM <?= number_format($product['total_revenue'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="admin-card">
        <h2 class="admin-card-header">Recent Orders</h2>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem;">No orders yet</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?= $order['order_id'] ?></td>
                                <td><?= htmlspecialchars($order['customer_name']) ?></td>
                                <td>RM <?= number_format($order['total_amount'], 2) ?></td>
                                <td><span class="order-status status-<?= $order['order_status'] ?>"><?= ucfirst($order['order_status']) ?></span></td>
                                <td><?= date('M d, Y', strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Dashboard Charts Script -->
<script>
// Prepare sales chart data
const salesChartData = <?= json_encode($salesChart) ?>;
const salesLabels = salesChartData.map(item => {
    const date = new Date(item.date);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
});
const salesRevenue = salesChartData.map(item => parseFloat(item.revenue));
const salesOrders = salesChartData.map(item => parseInt(item.orders));

// Sales Chart (Last 7 Days)
const salesCtx = document.getElementById('salesChart').getContext('2d');
new Chart(salesCtx, {
    type: 'line',
    data: {
        labels: salesLabels,
        datasets: [
            {
                label: 'Revenue (RM)',
                data: salesRevenue,
                borderColor: '#4CAF50',
                backgroundColor: 'rgba(76, 175, 80, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y'
            },
            {
                label: 'Orders',
                data: salesOrders,
                borderColor: '#2196F3',
                backgroundColor: 'rgba(33, 150, 243, 0.1)',
                tension: 0.4,
                fill: true,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        interaction: {
            mode: 'index',
            intersect: false,
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        if (label) {
                            label += ': ';
                        }
                        if (context.parsed.y !== null) {
                            if (context.dataset.label.includes('Revenue')) {
                                label += '$' + context.parsed.y.toFixed(2);
                            } else {
                                label += context.parsed.y + ' orders';
                            }
                        }
                        return label;
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Revenue (RM)'
                },
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(0);
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Orders'
                },
                grid: {
                    drawOnChartArea: false,
                },
                ticks: {
                    callback: function(value) {
                        return value;
                    }
                }
            }
        }
    }
});

// Prepare monthly sales data
const monthlySalesData = <?= json_encode($monthlySales) ?>;
const monthLabels = monthlySalesData.map(item => item.month_name);
const monthlyRevenue = monthlySalesData.map(item => parseFloat(item.revenue));

// Monthly Sales Chart
const monthlyCtx = document.getElementById('monthlySalesChart').getContext('2d');
new Chart(monthlyCtx, {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [{
            label: 'Revenue (RM)',
            data: monthlyRevenue,
            backgroundColor: 'rgba(76, 175, 80, 0.8)',
            borderColor: '#4CAF50',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Revenue: $' + context.parsed.y.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(0);
                    }
                }
            }
        }
    }
});
</script>

<?php include __DIR__ . '/../../../includes/admin/footer.php'; ?>
