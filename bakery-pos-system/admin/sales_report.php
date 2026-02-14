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

// Default date range: current month
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$cashierId = $_GET['cashier_id'] ?? 'all';
$paymentMethod = $_GET['payment_method'] ?? 'all';
$paymentStatus = $_GET['payment_status'] ?? 'all';

// Get cashiers for filter
$cashierSql = "SELECT id, username, full_name FROM users WHERE role = 'cashier' ORDER BY full_name";
$cashiers = $conn->query($cashierSql);

// Build WHERE clause for main query
$whereConditions = ["DATE(s.sale_date) BETWEEN ? AND ?"];
$params = [$startDate, $endDate];
$paramTypes = "ss";

if ($cashierId !== 'all') {
    $whereConditions[] = "s.user_id = ?";
    $params[] = $cashierId;
    $paramTypes .= "i";
}

if ($paymentMethod !== 'all') {
    $whereConditions[] = "s.payment_method = ?";
    $params[] = $paymentMethod;
    $paramTypes .= "s";
}

if ($paymentStatus !== 'all') {
    $whereConditions[] = "s.payment_status = ?";
    $params[] = $paymentStatus;
    $paramTypes .= "s";
}

$whereClause = "WHERE " . implode(" AND ", $whereConditions);

// Get sales statistics
$statsSql = "SELECT 
                COUNT(*) as total_sales,
                SUM(s.grand_total) as total_revenue,
                AVG(s.grand_total) as average_sale,
                SUM(s.tax) as total_tax,
                SUM(s.discount) as total_discount,
                SUM(CASE WHEN s.payment_status = 'paid' THEN s.grand_total ELSE 0 END) as paid_amount,
                SUM(CASE WHEN s.payment_status = 'pending' THEN s.grand_total ELSE 0 END) as pending_amount,
                SUM(CASE WHEN s.payment_status = 'cancelled' THEN s.grand_total ELSE 0 END) as cancelled_amount
            FROM sales s
            $whereClause";

$stmt = $conn->prepare($statsSql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$statsResult = $stmt->get_result();
$stats = $statsResult->fetch_assoc();

// Get daily sales data for chart
$dailySql = "SELECT 
                DATE(s.sale_date) as sale_date,
                COUNT(*) as sales_count,
                SUM(s.grand_total) as daily_revenue,
                AVG(s.grand_total) as average_sale
            FROM sales s
            $whereClause
            GROUP BY DATE(s.sale_date)
            ORDER BY sale_date";

$stmt = $conn->prepare($dailySql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$dailySales = $stmt->get_result();

// Get top selling products
$topProductsSql = "SELECT 
                    p.product_name,
                    p.product_code,
                    p.category,
                    SUM(si.quantity) as total_quantity,
                    SUM(si.total_price) as total_revenue,
                    COUNT(DISTINCT s.id) as sales_count
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                $whereClause AND s.payment_status = 'paid'
                GROUP BY p.id, p.product_name, p.product_code, p.category
                ORDER BY total_quantity DESC
                LIMIT 10";

$stmt = $conn->prepare($topProductsSql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$topProducts = $stmt->get_result();

// Get cashier performance
$cashierPerfSql = "SELECT 
                    u.full_name,
                    u.username,
                    COUNT(s.id) as sales_count,
                    SUM(s.grand_total) as total_sales,
                    AVG(s.grand_total) as average_sale,
                    SUM(CASE WHEN s.payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count
                FROM users u
                LEFT JOIN sales s ON u.id = s.user_id 
                    AND DATE(s.sale_date) BETWEEN ? AND ?
                WHERE u.role = 'cashier'
                GROUP BY u.id, u.full_name, u.username
                ORDER BY total_sales DESC";

$stmt = $conn->prepare($cashierPerfSql);
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$cashierPerformance = $stmt->get_result();

// Get payment method distribution
$paymentDistSql = "SELECT 
                    s.payment_method,
                    COUNT(*) as transaction_count,
                    SUM(s.grand_total) as total_amount,
                    AVG(s.grand_total) as average_amount
                FROM sales s
                $whereClause
                GROUP BY s.payment_method
                ORDER BY total_amount DESC";

$stmt = $conn->prepare($paymentDistSql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$paymentDistribution = $stmt->get_result();

// Pagination for sales list
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total sales count
$countSql = "SELECT COUNT(*) as total FROM sales s $whereClause";
$stmt = $conn->prepare($countSql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$countResult = $stmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Get sales list with pagination
$salesSql = "SELECT s.*, u.full_name as cashier_name 
            FROM sales s 
            LEFT JOIN users u ON s.user_id = u.id 
            $whereClause 
            ORDER BY s.sale_date DESC 
            LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$paramTypes .= "ii";

$stmt = $conn->prepare($salesSql);
$stmt->bind_param($paramTypes, ...$params);
$stmt->execute();
$salesList = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Reports - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .report-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }
        .date-range {
            cursor: pointer;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .badge-sale {
            font-size: 0.75em;
            padding: 3px 8px;
        }
        .progress-thin {
            height: 8px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Heading -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-bar me-2"></i>Sales Reports
            </h1>
            <div class="btn-group">
                <button type="button" class="btn btn-success" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf me-2"></i>PDF
                </button>
                <button type="button" class="btn btn-primary" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel me-2"></i>Excel
                </button>
                <button type="button" class="btn btn-secondary" onclick="exportReport('csv')">
                    <i class="fas fa-file-csv me-2"></i>CSV
                </button>
                <button type="button" class="btn btn-info" onclick="printReport()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
                        <input type="text" class="form-control date-range" id="dateRange" 
                               value="<?php echo format_date($startDate, 'M d, Y') . ' - ' . format_date($endDate, 'M d, Y'); ?>">
                        <input type="hidden" id="startDate" name="start_date" value="<?php echo $startDate; ?>">
                        <input type="hidden" id="endDate" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Cashier</label>
                        <select class="form-select" name="cashier_id">
                            <option value="all">All Cashiers</option>
                            <?php while ($cashier = $cashiers->fetch_assoc()): ?>
                            <option value="<?php echo $cashier['id']; ?>" 
                                    <?php echo $cashierId == $cashier['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cashier['full_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="all">All Methods</option>
                            <option value="cash" <?php echo $paymentMethod === 'cash' ? 'selected' : ''; ?>>Cash</option>
                            <option value="card" <?php echo $paymentMethod === 'card' ? 'selected' : ''; ?>>Card</option>
                            <option value="mobile_money" <?php echo $paymentMethod === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label">Payment Status</label>
                        <select class="form-select" name="payment_status">
                            <option value="all">All Status</option>
                            <option value="paid" <?php echo $paymentStatus === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="pending" <?php echo $paymentStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="cancelled" <?php echo $paymentStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <a href="sales_report.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card report-card border-left-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary text-white me-3">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Total Sales</div>
                                <div class="h5 mb-0"><?php echo $stats['total_sales'] ?? 0; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card report-card border-left-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success text-white me-3">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Total Revenue</div>
                                <div class="h5 mb-0"><?php echo format_currency($stats['total_revenue'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card report-card border-left-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info text-white me-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Average Sale</div>
                                <div class="h5 mb-0"><?php echo format_currency($stats['average_sale'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card report-card border-left-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning text-white me-3">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div>
                                <div class="text-muted small">Total Tax</div>
                                <div class="h5 mb-0"><?php echo format_currency($stats['total_tax'] ?? 0); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-xl-8">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-line me-2"></i>Daily Sales Trend
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dailySalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-chart-pie me-2"></i>Payment Method Distribution
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="paymentMethodChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products & Cashier Performance -->
        <div class="row mb-4">
            <!-- Top Selling Products -->
            <div class="col-xl-6">
                <div class="card shadow">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-success">
                            <i class="fas fa-star me-2"></i>Top Selling Products
                        </h6>
                        <span class="badge bg-success">Top 10</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Quantity</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($topProducts->num_rows > 0): ?>
                                        <?php $rank = 1; ?>
                                        <?php while ($product = $topProducts->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $rank == 1 ? 'warning' : 
                                                         ($rank == 2 ? 'secondary' : 
                                                         ($rank == 3 ? 'danger' : 'info')); 
                                                ?>">
                                                    #<?php echo $rank; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo $product['product_code']; ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $product['category']; ?></span>
                                            </td>
                                            <td class="fw-bold"><?php echo $product['total_quantity']; ?></td>
                                            <td class="text-success fw-bold"><?php echo format_currency($product['total_revenue']); ?></td>
                                        </tr>
                                        <?php $rank++; endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">
                                                No sales data for selected period
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Cashier Performance -->
            <div class="col-xl-6">
                <div class="card shadow">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-info">
                            <i class="fas fa-users me-2"></i>Cashier Performance
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Cashier</th>
                                        <th>Sales</th>
                                        <th>Revenue</th>
                                        <th>Avg. Sale</th>
                                        <th>Performance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($cashierPerformance->num_rows > 0): ?>
                                        <?php while ($cashier = $cashierPerformance->fetch_assoc()): 
                                            $performance = $cashier['total_sales'] ? ($cashier['paid_count'] / $cashier['sales_count']) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($cashier['full_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">@<?php echo $cashier['username']; ?></small>
                                            </td>
                                            <td class="fw-bold"><?php echo $cashier['sales_count']; ?></td>
                                            <td class="text-success fw-bold"><?php echo format_currency($cashier['total_sales']); ?></td>
                                            <td><?php echo format_currency($cashier['average_sale']); ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress-thin progress flex-grow-1 me-2">
                                                        <div class="progress-bar bg-<?php 
                                                            echo $performance >= 90 ? 'success' : 
                                                                 ($performance >= 70 ? 'info' : 
                                                                 ($performance >= 50 ? 'warning' : 'danger')); 
                                                        ?>" style="width: <?php echo $performance; ?>%"></div>
                                                    </div>
                                                    <span class="small"><?php echo number_format($performance, 1); ?>%</span>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">
                                                No cashier data available
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales List -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-list me-2"></i>Sales List
                </h6>
                <span class="badge bg-primary">Total: <?php echo $totalRows; ?> sales</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Cashier</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($salesList->num_rows > 0): ?>
                                <?php while ($sale = $salesList->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $sale['invoice_no']; ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($sale['customer_name']); ?>
                                        <?php if (!empty($sale['customer_phone'])): ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($sale['cashier_name']); ?>
                                    </td>
                                    <td>
                                        <?php echo format_date($sale['sale_date'], 'M d, Y'); ?>
                                        <br>
                                        <small class="text-muted"><?php echo format_date($sale['sale_date'], 'h:i A'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $sale['payment_method'] === 'cash' ? 'success' : 
                                                 ($sale['payment_method'] === 'card' ? 'primary' : 'warning'); 
                                        ?>">
                                            <?php echo get_payment_method_name($sale['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo get_status_badge($sale['payment_status']); ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="fw-bold text-success"><?php echo format_currency($sale['grand_total']); ?></div>
                                        <small class="text-muted">
                                            Items: 
                                            <?php 
                                            $sql = "SELECT COUNT(*) as item_count FROM sale_items WHERE sale_id = ?";
                                            $stmt2 = $conn->prepare($sql);
                                            $stmt2->bind_param("i", $sale['id']);
                                            $stmt2->execute();
                                            $itemResult = $stmt2->get_result();
                                            $itemCount = $itemResult->fetch_assoc()['item_count'];
                                            echo $itemCount;
                                            ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../invoice.php?id=<?php echo $sale['id']; ?>" 
                                               class="btn btn-outline-primary" target="_blank">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../invoice.php?id=<?php echo $sale['id']; ?>&print=1" 
                                               class="btn btn-outline-success" target="_blank">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if ($sale['payment_status'] === 'pending'): ?>
                                                <button class="btn btn-outline-warning mark-paid" 
                                                        data-id="<?php echo $sale['id']; ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                                        <h5 class="text-muted">No sales found</h5>
                                        <p>Try adjusting your filters or select a different date range.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <?php echo generate_pagination($page, $totalPages, 'sales_report.php?page={page}&' . http_build_query($_GET)); ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.min.js"></script>
    
    <script>
        // Date Range Picker
        $(function() {
            $('#dateRange').daterangepicker({
                opens: 'left',
                startDate: moment('<?php echo $startDate; ?>'),
                endDate: moment('<?php echo $endDate; ?>'),
                locale: {
                    format: 'MMM DD, YYYY'
                }
            }, function(start, end, label) {
                $('#startDate').val(start.format('YYYY-MM-DD'));
                $('#endDate').val(end.format('YYYY-MM-DD'));
            });
        });
        
        // Daily Sales Chart
        document.addEventListener('DOMContentLoaded', function() {
            const dailyCtx = document.getElementById('dailySalesChart');
            if (dailyCtx) {
                const dailyData = <?php
                    $dailySales->data_seek(0);
                    $labels = [];
                    $data = [];
                    while ($day = $dailySales->fetch_assoc()) {
                        $labels[] = date('M d', strtotime($day['sale_date']));
                        $data[] = $day['daily_revenue'];
                    }
                    echo json_encode(['labels' => $labels, 'data' => $data]);
                ?>;
                
                new Chart(dailyCtx, {
                    type: 'line',
                    data: {
                        labels: dailyData.labels,
                        datasets: [{
                            label: 'Daily Revenue ($)',
                            data: dailyData.data,
                            borderColor: '#4e73df',
                            backgroundColor: 'rgba(78, 115, 223, 0.1)',
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: function(value) {
                                        return '$' + value;
                                    }
                                }
                            }
                        }
                    }
                });
            }
            
            // Payment Method Chart
            const paymentCtx = document.getElementById('paymentMethodChart');
            if (paymentCtx) {
                const paymentData = <?php
                    $paymentDistribution->data_seek(0);
                    $labels = [];
                    $data = [];
                    $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e'];
                    $i = 0;
                    while ($payment = $paymentDistribution->fetch_assoc()) {
                        $labels[] = get_payment_method_name($payment['payment_method']);
                        $data[] = $payment['total_amount'];
                        $i++;
                    }
                    echo json_encode(['labels' => $labels, 'data' => $data, 'colors' => array_slice($colors, 0, count($data))]);
                ?>;
                
                new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: {
                        labels: paymentData.labels,
                        datasets: [{
                            data: paymentData.data,
                            backgroundColor: paymentData.colors
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        });
        
        // Mark sale as paid
        document.querySelectorAll('.mark-paid').forEach(button => {
            button.addEventListener('click', function() {
                const saleId = this.dataset.id;
                if (confirm('Mark this sale as paid?')) {
                    fetch('mark_paid.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: 'sale_id=' + saleId
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Sale marked as paid!');
                            location.reload();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    });
                }
            });
        });
        
        // Export reports
        function exportReport(format) {
            const params = new URLSearchParams(window.location.search);
            window.open('export_report.php?format=' + format + '&' + params.toString(), '_blank');
        }
        
        // Print report
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>