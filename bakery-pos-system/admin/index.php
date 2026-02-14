<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once 'access_check.php'; 
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_connect.php';

// Get dashboard statistics
$stats = get_dashboard_stats();

// Get recent sales
$sql = "SELECT s.*, u.full_name as cashier_name 
        FROM sales s 
        LEFT JOIN users u ON s.user_id = u.id 
        ORDER BY s.sale_date DESC 
        LIMIT 10";
$recentSales = $conn->query($sql);

// Get low stock products
$lowStockProducts = get_low_stock_products(10);

// Get out of stock products
$outOfStockProducts = get_out_of_stock_products(10);

// Get top selling products
$topProducts = get_top_selling_products(5, 'month');

// Get monthly sales data
$monthlySales = get_monthly_sales();

// Get cashier performance
$sql = "SELECT 
            u.full_name,
            u.username,
            COUNT(s.id) as sales_count,
            SUM(s.grand_total) as total_sales,
            AVG(s.grand_total) as average_sale
        FROM users u
        LEFT JOIN sales s ON u.id = s.user_id 
            AND s.payment_status = 'paid'
            AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        WHERE u.role = 'cashier' 
        GROUP BY u.id, u.full_name
        ORDER BY total_sales DESC 
        LIMIT 5";
$cashierPerformance = $conn->query($sql);

// Get system information
$systemInfo = get_system_info();
$diskUsage = get_disk_usage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-bg: #2c3e50;
            --sidebar-accent: #8B4513;
            --sidebar-hover: #34495e;
            --sidebar-text: #ecf0f1;
            --sidebar-active: #3498db;
            --bakery-brown: #8B4513;
            --bakery-gold: #D4A017;
            --bakery-red: #C41E3A;
            --bakery-green: #556B2F;
            --bakery-cream: #FFF8DC;
            --primary-blue: #3498db;
            --success-green: #2ecc71;
            --warning-orange: #f39c12;
            --danger-red: #e74c3c;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            display: flex;
        }
        
        /* Professional Sidebar Styles */
        .sidebar {
            width: 260px;
            height: 100vh;
            background: var(--sidebar-bg);
            color: var(--sidebar-text);
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(0,0,0,0.2);
        }
        
        .brand-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--sidebar-accent), var(--bakery-gold));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .brand-text {
            line-height: 1.2;
        }
        
        .brand-name {
            margin: 0;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
        }
        
        .brand-tag {
            font-size: 0.75rem;
            color: var(--bakery-gold);
            opacity: 0.8;
        }
        
        .sidebar-toggle {
            background: transparent;
            border: none;
            color: var(--sidebar-text);
            cursor: pointer;
            font-size: 1.2rem;
            padding: 5px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255,255,255,0.1);
            transform: rotate(90deg);
        }
        
        .user-profile {
            padding: 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary-blue), #2980b9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .user-info {
            flex: 1;
        }
        
        .user-name {
            margin: 0;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        .user-role {
            background: var(--bakery-gold);
            color: white;
            font-size: 0.7rem;
            padding: 3px 8px;
            border-radius: 10px;
            margin-top: 5px;
        }
        
        .sidebar-menu {
            flex: 1;
            overflow-y: auto;
            padding: 15px 0;
        }
        
        .menu-section {
            margin-bottom: 20px;
        }
        
        .menu-title {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: rgba(255,255,255,0.5);
            padding: 0 20px;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .nav {
            padding: 0;
        }
        
        .nav-item {
            margin-bottom: 2px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.3s;
            position: relative;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            background: var(--sidebar-hover);
            color: white;
            border-left-color: var(--bakery-gold);
        }
        
        .nav-link.active {
            background: linear-gradient(90deg, rgba(139, 69, 19, 0.2), transparent);
            color: white;
            border-left-color: var(--sidebar-accent);
            font-weight: 500;
        }
        
        .nav-icon {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
            font-size: 1.1rem;
            color: var(--bakery-gold);
        }
        
        .nav-text {
            flex: 1;
            font-size: 0.9rem;
        }
        
        .sidebar-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
            padding: 15px;
            background: rgba(0,0,0,0.2);
        }
        
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .quick-action {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 10px;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            color: var(--sidebar-text);
            text-decoration: none;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        
        .quick-action:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        
        .quick-action i {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }
        
        /* Main Content Area */
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            min-height: 100vh;
            background: #f8f9fa;
            transition: all 0.3s ease;
            padding: 20px;
        }
        
        /* Dashboard Specific Styles */
        .page-header {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-left: 4px solid var(--bakery-brown);
        }
        
        .dashboard-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            overflow: hidden;
            height: 100%;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .card-primary { 
            background: linear-gradient(45deg, var(--primary-blue), #2980b9);
            border-left: 4px solid var(--primary-blue);
        }
        
        .card-success { 
            background: linear-gradient(45deg, var(--success-green), #27ae60);
            border-left: 4px solid var(--success-green);
        }
        
        .card-warning { 
            background: linear-gradient(45deg, var(--warning-orange), #d35400);
            border-left: 4px solid var(--warning-orange);
        }
        
        .card-danger { 
            background: linear-gradient(45deg, var(--danger-red), #c0392b);
            border-left: 4px solid var(--danger-red);
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            padding: 15px;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #ffffff;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: #feffff;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .quick-action-btn {
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid #eee;
            background: white;
            color: #c3fff5;
        }
        
        .quick-action-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            border-color: var(--bakery-brown);
            color: var(--bakery-brown);
        }
        
        .system-status {
            border-left: 4px solid var(--primary-blue);
            padding-left: 15px;
            margin-bottom: 15px;
        }
        
        .system-status.good { border-color: var(--success-green); }
        .system-status.warning { border-color: var(--warning-orange); }
        .system-status.danger { border-color: var(--danger-red); }
        
        .table-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .table-header {
            background: var(--sidebar-bg);
            color: white;
            padding: 15px 20px;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .bg-paid { background: #d4edda; color: #155724; }
        .bg-pending { background: #fff3cd; color: #856404; }
        .bg-cancelled { background: #f8d7da; color: #721c24; }
        
        .top-product-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }
        
        .top-product-1 { background: gold; color: #000; }
        .top-product-2 { background: silver; color: #000; }
        .top-product-3 { background: #cd7f32; color: #000; }
        .top-product-other { background: #6c757d; color: white; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
            
            .mobile-menu-toggle {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: var(--bakery-brown);
                color: white;
                border: none;
                width: 40px;
                height: 40px;
                border-radius: 8px;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="brand-info">
                <div class="brand-icon">
                    <i class="fas fa-bread-slice"></i>
                </div>
                <div class="brand-text">
                    <h5 class="brand-name"><?php echo SITE_NAME; ?></h5>
                    <small class="brand-tag">Admin Panel</small>
                </div>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>

        <div class="user-profile">
            <div class="user-avatar">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="user-info">
                <h6 class="user-name"><?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Administrator'; ?></h6>
                <span class="user-role badge">Admin</span>
            </div>
        </div>

        <div class="sidebar-menu">
            <div class="menu-section">
                <h6 class="menu-title">MAIN</h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link active">
                            <span class="nav-icon">
                                <i class="fas fa-tachometer-alt"></i>
                            </span>
                            <span class="nav-text">Dashboard</span>
                            <span class="nav-indicator"></span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <h6 class="menu-title">MANAGEMENT</h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="products.php" class="nav-link">
                            <span class="nav-icon">
                                <i class="fas fa-box"></i>
                            </span>
                            <span class="nav-text">Products</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="inventory.php" class="nav-link">
                            <span class="nav-icon">
                                <i class="fas fa-warehouse"></i>
                            </span>
                            <span class="nav-text">Inventory</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="categories.php" class="nav-link">
                            <span class="nav-icon">
                                <i class="fas fa-tags"></i>
                            </span>
                            <span class="nav-text">Categories</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <h6 class="menu-title">REPORTS</h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="sales_report.php" class="nav-link">
                            <span class="nav-icon">
                                <i class="fas fa-chart-line"></i>
                            </span>
                            <span class="nav-text">Sales Reports</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="orders.php" class="nav-link">
                            <span class="nav-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </span>
                            <span class="nav-text">Orders</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="analytics.php" class="nav-link">
                            <span class="nav-icon">
                                <i class="fas fa-chart-pie"></i>
                            </span>
                            <span class="nav-text">Analytics</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="menu-section">
                <h6 class="menu-title">SYSTEM</h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <span class="nav-icon">
                                <i class="fas fa-users-cog"></i>
                            </span>
                            <span class="nav-text">Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="settings.php" class="nav-link">
                            <span class="nav-icon">
                                <i class="fas fa-cogs"></i>
                            </span>
                            <span class="nav-text">Settings</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="sidebar-footer">
            <div class="quick-actions">
                <a href="../cashier/pos.php" class="quick-action" style="color: var(--success-green);">
                    <i class="fas fa-cash-register"></i>
                    <span>POS Terminal</span>
                </a>
                <a href="../logout.php" class="quick-action" style="color: var(--danger-red);">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </div>
            <div class="sidebar-info">
                <small class="text-muted">
                    <i class="fas fa-clock"></i> 
                    <?php echo date('h:i A'); ?>
                </small>
            </div>
        </div>
    </div>
    
    <!-- Mobile Menu Toggle -->
    <button class="mobile-menu-toggle d-lg-none" id="mobileToggle">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header fade-in">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-tachometer-alt me-2" style="color: var(--bakery-brown);"></i>
                        Dashboard Overview
                    </h1>
                    <p class="mb-0 text-muted">
                        Welcome back, <?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'Administrator'; ?>! 
                        Here's what's happening with your bakery today.
                    </p>
                </div>
                <div class="text-end">
                    <div class="text-muted small">
                        <i class="fas fa-calendar me-1"></i>
                        <span id="current-date"><?php echo date('F d, Y'); ?></span>
                    </div>
                    <div class="text-muted small">
                        <i class="fas fa-clock me-1"></i>
                        <span id="current-time"><?php echo date('h:i A'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4 fade-in">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-card card-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Today's Sales</div>
                                <div class="stat-value"><?php echo format_currency($stats['today_sales']); ?></div>
                                <div class="mt-2">
                                    <small class="text-success">
                                        <i class="fas fa-arrow-up me-1"></i>
                                        <?php echo $stats['today_transactions']; ?> transactions
                                    </small>
                                </div>
                            </div>
                            <div class="stat-icon bg-primary">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-card card-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Total Products</div>
                                <div class="stat-value"><?php echo $stats['total_products']; ?></div>
                                <div class="mt-2">
                                    <small class="text-danger">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        <?php echo $stats['low_stock']; ?> low stock
                                    </small>
                                </div>
                            </div>
                            <div class="stat-icon bg-success">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-card card-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Active Users</div>
                                <div class="stat-value"><?php echo $stats['active_users']; ?></div>
                                <div class="mt-2">
                                    <small class="text-info">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo $stats['total_customers']; ?> customers
                                    </small>
                                </div>
                            </div>
                            <div class="stat-icon bg-warning">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="dashboard-card card-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="stat-label">Monthly Revenue</div>
                                <div class="stat-value"><?php echo format_currency($stats['monthly_sales']); ?></div>
                                <div class="mt-2">
                                    <small class="text-success">
                                        <i class="fas fa-chart-line me-1"></i>
                                        Current month performance
                                    </small>
                                </div>
                            </div>
                            <div class="stat-icon bg-danger">
                                <i class="fas fa-chart-bar"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts and Main Content -->
        <div class="row">
            <!-- Sales Chart -->
            <div class="col-xl-8 fade-in">
                <div class="table-card mb-4">
                    <div class="table-header">
                        <i class="fas fa-chart-line me-2"></i>Monthly Sales Overview
                        <div class="float-end">
                            <small>
                                <i class="fas fa-info-circle me-1"></i>
                                Last 30 days
                            </small>
                        </div>
                    </div>
                    <div class="p-3">
                        <div class="chart-container">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Sales -->
                <div class="table-card fade-in">
                    <div class="table-header">
                        <i class="fas fa-history me-2"></i>Recent Sales
                        <a href="sales_report.php" class="float-end text-white small">
                            View All <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="p-3">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($sale = $recentSales->fetch_assoc()): ?>
                                    <tr>
                                        <td><strong><?php echo $sale['invoice_no']; ?></strong></td>
                                        <td><?php echo htmlspecialchars($sale['customer_name'] ?: 'Walk-in Customer'); ?></td>
                                        <td><?php echo format_date($sale['sale_date'], 'M d, h:i A'); ?></td>
                                        <td class="fw-bold text-success"><?php echo format_currency($sale['grand_total']); ?></td>
                                        <td>
                                            <?php 
                                            $statusClass = '';
                                            if ($sale['payment_status'] == 'paid') $statusClass = 'bg-paid';
                                            elseif ($sale['payment_status'] == 'pending') $statusClass = 'bg-pending';
                                            else $statusClass = 'bg-cancelled';
                                            ?>
                                            <span class="status-badge <?php echo $statusClass; ?>">
                                                <?php echo ucfirst($sale['payment_status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="../invoice.php?id=<?php echo $sale['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" target="_blank">
                                                <i class="fas fa-eye"></i> View
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

            <!-- Right Column -->
            <div class="col-xl-4">
                <!-- Low Stock Alert -->
                <div class="table-card mb-4 fade-in">
                    <div class="table-header bg-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                        <span class="float-end badge bg-white text-danger">
                            <?php echo count($lowStockProducts); ?> items
                        </span>
                    </div>
                    <div class="p-3">
                        <?php if (!empty($lowStockProducts)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($lowStockProducts as $product): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                        <small class="text-muted">
                                            Code: <?php echo $product['product_code']; ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger"><?php echo $product['quantity']; ?> left</span>
                                        <div class="mt-1">
                                            <small class="text-muted">Min: <?php echo $product['min_stock_level']; ?></small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-center mt-3">
                                <a href="inventory.php" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-warehouse me-2"></i>Manage Inventory
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-success">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <p class="mb-0 fw-bold">All stocks are sufficient!</p>
                                <small class="text-muted">No low stock items</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Selling Products -->
                <div class="table-card mb-4 fade-in">
                    <div class="table-header bg-success">
                        <i class="fas fa-star me-2"></i>Top Selling Products
                    </div>
                    <div class="p-3">
                        <?php if (!empty($topProducts)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($topProducts as $index => $product): ?>
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <div class="d-flex align-items-center">
                                        <span class="top-product-badge 
                                            <?php echo $index == 0 ? 'top-product-1' : 
                                                  ($index == 1 ? 'top-product-2' : 
                                                  ($index == 2 ? 'top-product-3' : 'top-product-other')); ?>">
                                            <?php echo $index + 1; ?>
                                        </span>
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                            <small class="text-muted">
                                                <?php echo $product['total_quantity']; ?> sold
                                            </small>
                                        </div>
                                    </div>
                                    <div class="text-success fw-bold">
                                        <?php echo format_currency($product['total_revenue']); ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No sales data available</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="table-card fade-in">
                    <div class="table-header" style="background: var(--bakery-brown);">
                        <i class="fas fa-bolt me-2"></i>Quick Actions
                    </div>
                    <div class="p-3">
                        <div class="row g-3">
                            <div class="col-6">
                                <a href="products.php?action=add" class="quick-action-btn d-block">
                                    <i class="fas fa-plus fa-2x mb-2"></i>
                                    <div>Add Product</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="sales_report.php" class="quick-action-btn d-block">
                                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                    <div>Sales Report</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="inventory.php" class="quick-action-btn d-block">
                                    <i class="fas fa-warehouse fa-2x mb-2"></i>
                                    <div>Inventory</div>
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="users.php" class="quick-action-btn d-block">
                                    <i class="fas fa-users fa-2x mb-2"></i>
                                    <div>Manage Users</div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileToggle = document.getElementById('mobileToggle');
            const sidebar = document.querySelector('.sidebar');
            
            // Desktop toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                });
            }
            
            // Mobile toggle
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Load saved sidebar state
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.add('collapsed');
            }
            
            // Update current time
            function updateTime() {
                const now = new Date();
                const dateStr = now.toLocaleDateString('en-US', { 
                    weekday: 'long', 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
                const timeStr = now.toLocaleTimeString('en-US', { 
                    hour: '2-digit', 
                    minute: '2-digit' 
                });
                
                document.getElementById('current-date').textContent = dateStr;
                document.getElementById('current-time').textContent = timeStr;
            }
            setInterval(updateTime, 1000);
            updateTime();
            
            // Sales Chart
            const ctx = document.getElementById('salesChart');
            if (ctx) {
                const monthlyData = <?php echo json_encode($monthlySales); ?>;
                const labels = [];
                const data = [];
                const transactionData = [];
                
                monthlyData.forEach(sale => {
                    labels.push(sale.day);
                    data.push(parseFloat(sale.total_sales));
                    transactionData.push(sale.transaction_count);
                });
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Sales Revenue ($)',
                            data: data,
                            borderColor: '#3498db',
                            backgroundColor: 'rgba(52, 152, 219, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return '$' + context.parsed.y.toFixed(2);
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    color: 'rgba(0,0,0,0.05)'
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>