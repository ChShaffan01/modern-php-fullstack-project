<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once 'access_check.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: login.php');
    exit();
}

require_once '../includes/db_connect.php';

// Get dashboard statistics
$stats = [];
$sql = "SELECT COUNT(*) as total_products FROM products";
$result = $conn->query($sql);
$stats['total_products'] = $result->fetch_assoc()['total_products'];

$sql = "SELECT COUNT(*) as low_stock FROM products WHERE quantity <= min_stock_level";
$result = $conn->query($sql);
$stats['low_stock'] = $result->fetch_assoc()['low_stock'];

$sql = "SELECT SUM(grand_total) as today_sales FROM sales WHERE DATE(sale_date) = CURDATE()";
$result = $conn->query($sql);
$stats['today_sales'] = $result->fetch_assoc()['today_sales'] ?? 0;

$sql = "SELECT COUNT(*) as total_sales FROM sales WHERE DATE(sale_date) = CURDATE()";
$result = $conn->query($sql);
$stats['today_transactions'] = $result->fetch_assoc()['total_sales'];

// Get recent sales
$sql = "SELECT s.*, u.full_name as cashier_name 
        FROM sales s 
        LEFT JOIN users u ON s.user_id = u.id 
        ORDER BY s.sale_date DESC 
        LIMIT 10";
$recent_sales = $conn->query($sql);

// Get low stock products
$sql = "SELECT * FROM products WHERE quantity <= min_stock_level ORDER BY quantity ASC LIMIT 10";
$low_stock_products = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="d-flex">
        <div class="bg-dark text-white vh-100" style="width: 250px;">
            <div class="p-3">
                <h4 class="text-center">
                    <i class="fas fa-bread-slice"></i> <?php echo SITE_NAME; ?>
                </h4>
                <hr class="bg-light">
                <div class="list-group list-group-flush">
                    <a href="dashboard.php" class="list-group-item list-group-item-action bg-dark text-white active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="products.php" class="list-group-item list-group-item-action bg-dark text-white">
                        <i class="fas fa-box"></i> Products
                    </a>
                    <a href="inventory.php" class="list-group-item list-group-item-action bg-dark text-white">
                        <i class="fas fa-warehouse"></i> Inventory
                    </a>
                    <a href="sales_report.php" class="list-group-item list-group-item-action bg-dark text-white">
                        <i class="fas fa-chart-bar"></i> Sales Report
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action bg-dark text-white">
                        <i class="fas fa-users"></i> Users
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action bg-dark text-white">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <li class="nav-item">
    <a class="nav-link text-danger" href="../logout.php">
        <i class="fas fa-sign-out-alt"></i> Logout
    </a>
</li>

                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="flex-grow-1 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Admin Dashboard</h2>
                <div class="text-muted">
                    Welcome, <?php echo $_SESSION['full_name']; ?>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Total Products</h6>
                                    <h2><?php echo $stats['total_products']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-box fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Low Stock Items</h6>
                                    <h2><?php echo $stats['low_stock']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-exclamation-triangle fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Today's Sales</h6>
                                    <h2>$<?php echo number_format($stats['today_sales'], 2); ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-dollar-sign fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title">Today's Transactions</h6>
                                    <h2><?php echo $stats['today_transactions']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-receipt fa-3x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Sales & Low Stock -->
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5>Recent Sales</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Invoice #</th>
                                            <th>Customer</th>
                                            <th>Cashier</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($sale = $recent_sales->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $sale['invoice_no']; ?></td>
                                            <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                            <td><?php echo $sale['cashier_name']; ?></td>
                                            <td class="text-success fw-bold">
                                                $<?php echo number_format($sale['grand_total'], 2); ?>
                                            </td>
                                            <td><?php echo date('M d, H:i', strtotime($sale['sale_date'])); ?></td>
                                            <td>
                                                <a href="../invoice.php?id=<?php echo $sale['id']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" target="_blank">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0">Low Stock Alert</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($low_stock_products->num_rows > 0): ?>
                                <div class="list-group">
                                    <?php while ($product = $low_stock_products->fetch_assoc()): ?>
                                    <a href="inventory.php" class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <strong><?php echo $product['product_name']; ?></strong>
                                            <span class="badge bg-danger">
                                                <?php echo $product['quantity']; ?> left
                                            </span>
                                        </div>
                                        <small>Min: <?php echo $product['min_stock_level']; ?> | 
                                               Unit: <?php echo $product['unit']; ?></small>
                                    </a>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center text-success">
                                    <i class="fas fa-check-circle fa-3x mb-2"></i>
                                    <p>All stocks are sufficient!</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>