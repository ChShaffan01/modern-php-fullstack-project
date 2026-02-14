<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('cashier')) {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_connect.php';

// Get current user
$userId = $_SESSION['user_id'];

// Filter parameters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? 'all';
$paymentMethod = $_GET['payment_method'] ?? 'all';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build WHERE clause
$whereConditions = ["s.user_id = ?"];
$params = [$userId];
$paramTypes = "i";

// Date filter
if (!empty($startDate) && !empty($endDate)) {
    $whereConditions[] = "DATE(s.sale_date) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $paramTypes .= "ss";
}

// Search filter
if (!empty($search)) {
    $whereConditions[] = "(s.invoice_no LIKE ? OR s.customer_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $paramTypes .= "ss";
}

// Status filter
if ($status !== 'all') {
    $whereConditions[] = "s.payment_status = ?";
    $params[] = $status;
    $paramTypes .= "s";
}

// Payment method filter
if ($paymentMethod !== 'all') {
    $whereConditions[] = "s.payment_method = ?";
    $params[] = $paymentMethod;
    $paramTypes .= "s";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count
$countSql = "SELECT COUNT(*) as total FROM sales s $whereClause";
$stmt = $conn->prepare($countSql);

// Bind parameters for count
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$countResult = $stmt->get_result();
$totalRows = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Get sales with pagination
$salesSql = "SELECT s.*, 
                    (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count,
                    (SELECT SUM(quantity) FROM sale_items WHERE sale_id = s.id) as total_quantity
             FROM sales s 
             $whereClause 
             ORDER BY s.sale_date DESC 
             LIMIT ? OFFSET ?";

$params[] = $perPage;
$params[] = $offset;
$paramTypes .= "ii";

$stmt = $conn->prepare($salesSql);
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$salesResult = $stmt->get_result();

// Get statistics for the filter period
$statsSql = "SELECT 
                COUNT(*) as total_sales,
                SUM(grand_total) as total_amount,
                AVG(grand_total) as average_sale,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_count,
                SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN payment_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_count
            FROM sales 
            WHERE user_id = ? AND DATE(sale_date) BETWEEN ? AND ?";

$stmt = $conn->prepare($statsSql);
$stmt->bind_param("iss", $userId, $startDate, $endDate);
$stmt->execute();
$statsResult = $stmt->get_result();
$periodStats = $statsResult->fetch_assoc();

// Get payment method distribution
$paymentStatsSql = "SELECT 
                        payment_method,
                        COUNT(*) as count,
                        SUM(grand_total) as total
                    FROM sales 
                    WHERE user_id = ? AND DATE(sale_date) BETWEEN ? AND ?
                    GROUP BY payment_method";
$stmt = $conn->prepare($paymentStatsSql);
$stmt->bind_param("iss", $userId, $startDate, $endDate);
$stmt->execute();
$paymentStatsResult = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Sales - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker@3.1.0/daterangepicker.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .stats-card {
            transition: transform 0.3s;
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .filter-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(78, 115, 223, 0.05);
        }
        .badge-sale {
            font-size: 0.8em;
            padding: 4px 8px;
        }
        .export-buttons .btn {
            min-width: 120px;
        }
        .pagination .page-link {
            color: #4e73df;
        }
        .pagination .page-item.active .page-link {
            background-color: #4e73df;
            border-color: #4e73df;
        }
        .date-range {
            cursor: pointer;
            background: white;
            border: 1px solid #ced4da;
            border-radius: 0.25rem;
            padding: 0.375rem 0.75rem;
        }
        .chart-container {
            position: relative;
            height: 300px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'cashier_nav.php'; ?>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h2><i class="fas fa-receipt me-2"></i>My Sales</h2>
                <p class="text-muted">View and manage your sales transactions</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="pos.php" class="btn btn-primary me-2">
                    <i class="fas fa-cash-register me-2"></i>New Sale
                </a>
                <a href="profile.php" class="btn btn-outline-secondary">
                    <i class="fas fa-user-circle me-2"></i>My Profile
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-2">Total Sales</h6>
                                <h3 class="mb-0"><?php echo $periodStats['total_sales'] ?? 0; ?></h3>
                            </div>
                            <div class="bg-white rounded-circle p-3">
                                <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                            </div>
                        </div>
                        <small>Period: <?php echo format_date($startDate, 'M d') . ' - ' . format_date($endDate, 'M d'); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-2">Total Revenue</h6>
                                <h3 class="mb-0"><?php echo format_currency($periodStats['total_amount'] ?? 0); ?></h3>
                            </div>
                            <div class="bg-white rounded-circle p-3">
                                <i class="fas fa-dollar-sign fa-2x text-success"></i>
                            </div>
                        </div>
                        <small>Average: <?php echo format_currency($periodStats['average_sale'] ?? 0); ?></small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-2">Paid</h6>
                                <h3 class="mb-0"><?php echo $periodStats['paid_count'] ?? 0; ?></h3>
                            </div>
                            <div class="bg-white rounded-circle p-3">
                                <i class="fas fa-check-circle fa-2x text-info"></i>
                            </div>
                        </div>
                        <small><?php echo $periodStats['pending_count'] ?? 0; ?> pending</small>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card stats-card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-2">Items Sold</h6>
                                <h3 class="mb-0">
                                    <?php 
                                    $sql = "SELECT SUM(si.quantity) as total 
                                            FROM sale_items si 
                                            JOIN sales s ON si.sale_id = s.id 
                                            WHERE s.user_id = ? AND DATE(s.sale_date) BETWEEN ? AND ?";
                                    $stmt = $conn->prepare($sql);
                                    $stmt->bind_param("iss", $userId, $startDate, $endDate);
                                    $stmt->execute();
                                    $itemResult = $stmt->get_result();
                                    $itemStats = $itemResult->fetch_assoc();
                                    echo $itemStats['total'] ?? 0;
                                    ?>
                                </h3>
                            </div>
                            <div class="bg-white rounded-circle p-3">
                                <i class="fas fa-boxes fa-2x text-warning"></i>
                            </div>
                        </div>
                        <small>Total quantity sold</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section">
            <form method="GET" action="" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <input type="text" class="form-control date-range" id="dateRange" 
                           value="<?php echo format_date($startDate, 'M d, Y') . ' - ' . format_date($endDate, 'M d, Y'); ?>">
                    <input type="hidden" id="startDate" name="start_date" value="<?php echo $startDate; ?>">
                    <input type="hidden" id="endDate" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Payment Status</label>
                    <select class="form-select" name="status">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" name="payment_method">
                        <option value="all" <?php echo $paymentMethod === 'all' ? 'selected' : ''; ?>>All Methods</option>
                        <option value="cash" <?php echo $paymentMethod === 'cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="card" <?php echo $paymentMethod === 'card' ? 'selected' : ''; ?>>Card</option>
                        <option value="mobile_money" <?php echo $paymentMethod === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" class="form-control" name="search" 
                           placeholder="Invoice # or Customer Name" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">
                                Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $perPage, $totalRows); ?> of <?php echo $totalRows; ?> results
                            </span>
                        </div>
                        <div class="export-buttons">
                            <button type="button" class="btn btn-outline-success btn-sm" onclick="exportToCSV()">
                                <i class="fas fa-file-csv me-1"></i>CSV
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm ms-2" onclick="exportToPDF()">
                                <i class="fas fa-file-pdf me-1"></i>PDF
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm ms-2" onclick="printSales()">
                                <i class="fas fa-print me-1"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Date & Time</th>
                                <th>Items</th>
                                <th>Payment Method</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($salesResult->num_rows > 0): ?>
                                <?php while ($sale = $salesResult->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $sale['invoice_no']; ?></strong>
                                        <br>
                                        <small class="text-muted">ID: <?php echo $sale['id']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo !empty($sale['customer_name']) ? htmlspecialchars($sale['customer_name']) : '<span class="text-muted">Walk-in</span>'; ?>
                                        <?php if (!empty($sale['customer_phone'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($sale['customer_phone']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo format_date($sale['sale_date'], 'M d, Y'); ?>
                                        <br>
                                        <small class="text-muted"><?php echo format_date($sale['sale_date'], 'h:i A'); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-info badge-sale">
                                            <?php echo $sale['item_count']; ?> items
                                        </span>
                                        <span class="badge bg-secondary badge-sale">
                                            <?php echo $sale['total_quantity']; ?> qty
                                        </span>
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
                                            Sub: <?php echo format_currency($sale['total_amount']); ?>
                                            | Tax: <?php echo format_currency($sale['tax']); ?>
                                            <?php if ($sale['discount'] > 0): ?>
                                                | Disc: -<?php echo format_currency($sale['discount']); ?>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../invoice.php?id=<?php echo $sale['id']; ?>" 
                                               class="btn btn-outline-primary" target="_blank" title="View Invoice">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../invoice.php?id=<?php echo $sale['id']; ?>&print=1" 
                                               class="btn btn-outline-success" target="_blank" title="Print Invoice">
                                                <i class="fas fa-print"></i>
                                            </a>
                                            <?php if ($sale['payment_status'] === 'pending'): ?>
                                                <button class="btn btn-outline-warning mark-paid" 
                                                        data-id="<?php echo $sale['id']; ?>" title="Mark as Paid">
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
                                        <p>Try adjusting your filters or create a new sale.</p>
                                        <a href="pos.php" class="btn btn-primary">
                                            <i class="fas fa-cash-register me-2"></i>Create New Sale
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <?php echo generate_pagination($page, $totalPages, 'sales.php?page={page}&' . http_build_query($_GET)); ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>

        <!-- Charts and Statistics -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Payment Method Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="paymentChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Daily Sales Trend</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="dailySalesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payment Statistics -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-money-check-alt me-2"></i>Payment Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php while ($payment = $paymentStatsResult->fetch_assoc()): ?>
                            <div class="col-md-3 mb-3">
                                <div class="border rounded p-3 text-center">
                                    <h6><?php echo get_payment_method_name($payment['payment_method']); ?></h6>
                                    <h3 class="text-success"><?php echo format_currency($payment['total']); ?></h3>
                                    <span class="badge bg-primary"><?php echo $payment['count']; ?> transactions</span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
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
        
        // Mark as Paid
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
        
        // Export to CSV
        function exportToCSV() {
            const params = new URLSearchParams(window.location.search);
            window.open('export_sales.php?format=csv&' + params.toString(), '_blank');
        }
        
        // Export to PDF
        function exportToPDF() {
            const params = new URLSearchParams(window.location.search);
            window.open('export_sales.php?format=pdf&' + params.toString(), '_blank');
        }
        
        // Print Sales
        function printSales() {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Sales Report - <?php echo SITE_NAME; ?></title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        h2 { color: #333; }
                        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .total { font-weight: bold; color: #28a745; }
                        .header { text-align: center; margin-bottom: 30px; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2><?php echo SITE_NAME; ?></h2>
                        <h3>Sales Report</h3>
                        <p>Cashier: <?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                        <p>Period: <?php echo format_date($startDate, 'M d, Y') . ' to ' . format_date($endDate, 'M d, Y'); ?></p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $salesResult->data_seek(0); // Reset pointer
                            while ($sale = $salesResult->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><?php echo $sale['invoice_no']; ?></td>
                                <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                <td><?php echo format_date($sale['sale_date'], 'M d, Y'); ?></td>
                                <td><?php echo $sale['item_count']; ?> items</td>
                                <td><?php echo get_payment_method_name($sale['payment_method']); ?></td>
                                <td><?php echo ucfirst($sale['payment_status']); ?></td>
                                <td><?php echo format_currency($sale['grand_total']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    <div class="footer">
                        <p>Generated on: <?php echo date('F d, Y h:i A'); ?></p>
                        <p>Total Sales: <?php echo $periodStats['total_sales'] ?? 0; ?> | 
                           Total Revenue: <?php echo format_currency($periodStats['total_amount'] ?? 0); ?></p>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Payment Method Chart
            const paymentCtx = document.getElementById('paymentChart');
            if (paymentCtx) {
                const paymentData = {
                    labels: [
                        <?php 
                        $paymentStatsResult->data_seek(0);
                        $labels = [];
                        while ($payment = $paymentStatsResult->fetch_assoc()) {
                            $labels[] = "'" . get_payment_method_name($payment['payment_method']) . "'";
                        }
                        echo implode(', ', $labels);
                        ?>
                    ],
                    datasets: [{
                        data: [
                            <?php 
                            $paymentStatsResult->data_seek(0);
                            $data = [];
                            while ($payment = $paymentStatsResult->fetch_assoc()) {
                                $data[] = $payment['total'];
                            }
                            echo implode(', ', $data);
                            ?>
                        ],
                        backgroundColor: [
                            '#4e73df',
                            '#1cc88a',
                            '#36b9cc',
                            '#f6c23e',
                            '#e74a3b'
                        ]
                    }]
                };
                
                new Chart(paymentCtx, {
                    type: 'doughnut',
                    data: paymentData,
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
            
            // Daily Sales Chart
            const dailyCtx = document.getElementById('dailySalesChart');
            if (dailyCtx) {
                // This would typically come from an AJAX call
                // For now, using sample data
                const dailyData = {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Daily Sales ($)',
                       