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

// Handle actions
$action = $_GET['action'] ?? '';
$productId = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

// Add stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $productId = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $reason = sanitize_input($_POST['reason'] ?? '');
    
    if ($productId > 0 && $quantity > 0) {
        // Update product quantity
        $sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $quantity, $productId);
        
        if ($stmt->execute()) {
            // Get current quantity for logging
            $sql = "SELECT quantity FROM products WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $result = $stmt->get_result();
            $newQuantity = $result->fetch_assoc()['quantity'];
            
            // Log inventory change
            $sql = "INSERT INTO inventory_logs (product_id, user_id, action, quantity_change, new_quantity, reason) 
                    VALUES (?, ?, 'add', ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $userId = $_SESSION['user_id'];
            $stmt->bind_param("iiiss", $productId, $userId, $quantity, $newQuantity, $reason);
            $stmt->execute();
            
            $message = 'Stock added successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to add stock';
            $messageType = 'danger';
        }
    }
}

// Adjust stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_stock'])) {
    $productId = (int)$_POST['product_id'];
    $newQuantity = (int)$_POST['new_quantity'];
    $reason = sanitize_input($_POST['reason'] ?? '');
    
    if ($productId > 0 && $newQuantity >= 0) {
        // Get current quantity
        $sql = "SELECT quantity FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentQuantity = $result->fetch_assoc()['quantity'];
        
        $quantityChange = $newQuantity - $currentQuantity;
        
        // Update product quantity
        $sql = "UPDATE products SET quantity = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $newQuantity, $productId);
        
        if ($stmt->execute()) {
            // Log inventory change
            $sql = "INSERT INTO inventory_logs (product_id, user_id, action, quantity_change, new_quantity, reason) 
                    VALUES (?, ?, 'adjust', ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $userId = $_SESSION['user_id'];
            $stmt->bind_param("iiiss", $productId, $userId, $quantityChange, $newQuantity, $reason);
            $stmt->execute();
            
            $message = 'Stock adjusted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to adjust stock';
            $messageType = 'danger';
        }
    }
}

// Delete product
if ($action === 'delete' && $productId > 0) {
    // Check if product has sales
    $sql = "SELECT COUNT(*) as sale_count FROM sale_items WHERE product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $saleCount = $result->fetch_assoc()['sale_count'];
    
    if ($saleCount > 0) {
        $message = 'Cannot delete product with sales history.';
        $messageType = 'warning';
    } else {
        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        
        if ($stmt->execute()) {
            $message = 'Product deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Failed to delete product';
            $messageType = 'danger';
        }
    }
}

// Filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';
$stockStatus = $_GET['stock_status'] ?? 'all';
$sort = $_GET['sort'] ?? 'name_asc';

// Build WHERE clause
$whereConditions = [];
$params = [];
$paramTypes = '';

if (!empty($search)) {
    $whereConditions[] = "(product_name LIKE ? OR product_code LIKE ? OR description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $paramTypes .= "sss";
}

if ($category !== 'all') {
    $whereConditions[] = "category = ?";
    $params[] = $category;
    $paramTypes .= "s";
}

if ($stockStatus === 'low') {
    $whereConditions[] = "quantity <= min_stock_level AND quantity > 0";
} elseif ($stockStatus === 'out') {
    $whereConditions[] = "quantity = 0";
} elseif ($stockStatus === 'normal') {
    $whereConditions[] = "quantity > min_stock_level";
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Build ORDER BY
$orderBy = "product_name ASC";
switch ($sort) {
    case 'name_desc': $orderBy = "product_name DESC"; break;
    case 'price_asc': $orderBy = "price ASC"; break;
    case 'price_desc': $orderBy = "price DESC"; break;
    case 'quantity_asc': $orderBy = "quantity ASC"; break;
    case 'quantity_desc': $orderBy = "quantity DESC"; break;
    case 'date_desc': $orderBy = "created_at DESC"; break;
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total count
$countSql = "SELECT COUNT(*) as total FROM products $whereClause";
if (!empty($params)) {
    $stmt = $conn->prepare($countSql);
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($countSql);
}
$totalRows = $result->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $perPage);

// Get products with pagination
$sql = "SELECT * FROM products $whereClause ORDER BY $orderBy LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$paramTypes .= "ii";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$products = $stmt->get_result();

// Get categories for filter
$categorySql = "SELECT DISTINCT category FROM products ORDER BY category";
$categories = $conn->query($categorySql);

// Get inventory statistics
$statsSql = "SELECT 
                COUNT(*) as total_products,
                SUM(quantity) as total_quantity,
                SUM(quantity * cost) as total_cost,
                SUM(quantity * price) as total_value,
                SUM(CASE WHEN quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                SUM(CASE WHEN quantity <= min_stock_level AND quantity > 0 THEN 1 ELSE 0 END) as low_stock
            FROM products";
$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .inventory-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .inventory-card:hover {
            transform: translateY(-5px);
        }
        .stock-low { background-color: #fff3cd; }
        .stock-out { background-color: #f8d7da; }
        .stock-normal { background-color: #d1ecf1; }
        .stock-level-bar {
            height: 10px;
            border-radius: 5px;
            margin-top: 5px;
        }
        .modal-xl { max-width: 800px; }
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,0.02);
        }
        .badge-stock {
            font-size: 0.75em;
            padding: 3px 8px;
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
                <i class="fas fa-warehouse me-2"></i>Inventory Management
            </h1>
            <div>
                <a href="products.php?action=add" class="btn btn-primary me-2">
                    <i class="fas fa-plus me-2"></i>Add Product
                </a>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                    <i class="fas fa-upload me-2"></i>Bulk Update
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Inventory Statistics -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card inventory-card border-left-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-primary text-white me-3">
                                <i class="fas fa-box"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?php echo $stats['total_products']; ?></h5>
                                <small class="text-muted">Total Products</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="card inventory-card border-left-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-success text-white me-3">
                                <i class="fas fa-cubes"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?php echo $stats['total_quantity']; ?></h5>
                                <small class="text-muted">Total Quantity</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="card inventory-card border-left-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-info text-white me-3">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?php echo format_currency($stats['total_cost']); ?></h5>
                                <small class="text-muted">Total Cost</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="card inventory-card border-left-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-warning text-white me-3">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?php echo format_currency($stats['total_value']); ?></h5>
                                <small class="text-muted">Total Value</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="card inventory-card border-left-danger">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-danger text-white me-3">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?php echo $stats['low_stock']; ?></h5>
                                <small class="text-muted">Low Stock</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-2">
                <div class="card inventory-card border-left-secondary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="stat-icon bg-secondary text-white me-3">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <div>
                                <h5 class="mb-0"><?php echo $stats['out_of_stock']; ?></h5>
                                <small class="text-muted">Out of Stock</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-select" name="category">
                            <option value="all">All Categories</option>
                            <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['category']; ?>" 
                                    <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-select" name="stock_status">
                            <option value="all" <?php echo $stockStatus === 'all' ? 'selected' : ''; ?>>All Stock</option>
                            <option value="normal" <?php echo $stockStatus === 'normal' ? 'selected' : ''; ?>>Normal</option>
                            <option value="low" <?php echo $stockStatus === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                            <option value="out" <?php echo $stockStatus === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                        </select>
                    </div>
                    
                    <div class="col-md-2">
                        <select class="form-select" name="sort">
                            <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                            <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low-High)</option>
                            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High-Low)</option>
                            <option value="quantity_asc" <?php echo $sort === 'quantity_asc' ? 'selected' : ''; ?>>Quantity (Low-High)</option>
                            <option value="quantity_desc" <?php echo $sort === 'quantity_desc' ? 'selected' : ''; ?>>Quantity (High-Low)</option>
                            <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                        <a href="inventory.php" class="btn btn-outline-secondary">
                            <i class="fas fa-redo me-2"></i>Reset
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportInventory()">
                            <i class="fas fa-file-export me-2"></i>Export
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card shadow">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas fa-boxes me-2"></i>Product Inventory
                </h6>
                <span class="badge bg-primary">Total: <?php echo $totalRows; ?> products</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Stock Level</th>
                                <th>Price</th>
                                <th>Cost</th>
                                <th>Profit Margin</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products->num_rows > 0): ?>
                                <?php $counter = ($page - 1) * $perPage + 1; ?>
                                <?php while ($product = $products->fetch_assoc()): 
                                    $stockClass = $product['quantity'] == 0 ? 'stock-out' : 
                                                 ($product['quantity'] <= $product['min_stock_level'] ? 'stock-low' : 'stock-normal');
                                    $stockPercent = ($product['min_stock_level'] > 0) ? 
                                                   min(100, ($product['quantity'] / ($product['min_stock_level'] * 2)) * 100) : 100;
                                    $profitMargin = calculate_profit_margin($product['cost'], $product['price']);
                                ?>
                                <tr class="<?php echo $stockClass; ?>">
                                    <td><?php echo $counter++; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($product['image_url'])): ?>
                                            <img src="../<?php echo $product['image_url']; ?>" 
                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                 class="rounded me-3" width="40" height="40">
                                            <?php else: ?>
                                            <div class="rounded bg-light me-3 d-flex align-items-center justify-content-center" 
                                                 style="width: 40px; height: 40px;">
                                                <i class="fas fa-box text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                                <br>
                                                <small class="text-muted">Code: <?php echo $product['product_code']; ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo htmlspecialchars($product['category']); ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="fw-bold"><?php echo $product['quantity']; ?> <?php echo $product['unit']; ?></span>
                                                <span class="text-muted">Min: <?php echo $product['min_stock_level']; ?></span>
                                            </div>
                                            <div class="stock-level-bar progress" style="height: 5px;">
                                                <div class="progress-bar bg-<?php echo $product['quantity'] == 0 ? 'danger' : 
                                                                               ($product['quantity'] <= $product['min_stock_level'] ? 'warning' : 'success'); ?>" 
                                                     style="width: <?php echo $stockPercent; ?>%">
                                                </div>
                                            </div>
                                            <?php if ($product['quantity'] <= $product['min_stock_level']): ?>
                                            <small class="text-danger">
                                                <i class="fas fa-exclamation-circle"></i> Low stock alert!
                                            </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-success"><?php echo format_currency($product['price']); ?></span>
                                    </td>
                                    <td>
                                        <span class="text-muted"><?php echo format_currency($product['cost']); ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $profitMargin >= 30 ? 'success' : 
                                                                 ($profitMargin >= 20 ? 'info' : 
                                                                 ($profitMargin >= 10 ? 'warning' : 'danger')); ?>">
                                            <?php echo $profitMargin; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <small><?php echo format_date($product['updated_at']); ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#addStockModal"
                                                    onclick="setAddStockProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                            <button class="btn btn-outline-warning"
                                                    data-bs-toggle="modal" data-bs-target="#adjustStockModal"
                                                    onclick="setAdjustStockProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>', <?php echo $product['quantity']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" 
                                               class="btn btn-outline-info">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <button class="btn btn-outline-danger" 
                                                    onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center py-5">
                                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                        <h5 class="text-muted">No products found</h5>
                                        <p>Try adjusting your filters or add new products.</p>
                                        <a href="products.php?action=add" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add Product
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
                    <?php echo generate_pagination($page, $totalPages, 'inventory.php?page={page}&' . http_build_query($_GET)); ?>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Stock Modal -->
    <div class="modal fade" id="addStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="product_id" id="addProductId">
                    <input type="hidden" name="add_stock" value="1">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" class="form-control" id="addProductName" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="quantity" class="form-label">Quantity to Add *</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                   min="1" max="10000" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason (Optional)</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" 
                                      placeholder="E.g., New stock received, Returned items, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Adjust Stock Modal -->
    <div class="modal fade" id="adjustStockModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="product_id" id="adjustProductId">
                    <input type="hidden" name="adjust_stock" value="1">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Product</label>
                            <input type="text" class="form-control" id="adjustProductName" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Quantity</label>
                            <input type="text" class="form-control" id="currentQuantity" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_quantity" class="form-label">New Quantity *</label>
                            <input type="number" class="form-control" id="new_quantity" name="new_quantity" 
                                   min="0" max="10000" required>
                            <small class="text-muted">Set to 0 to mark as out of stock</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason (Required)</label>
                            <textarea class="form-control" id="reason" name="reason" rows="3" 
                                      placeholder="E.g., Stock count correction, Damaged items, etc." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Adjust Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bulk Stock Update</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-file-upload me-2"></i>Import from CSV</h6>
                                </div>
                                <div class="card-body">
                                    <form id="importForm" enctype="multipart/form-data">
                                        <div class="mb-3">
                                            <label for="csvFile" class="form-label">Select CSV File</label>
                                            <input type="file" class="form-control" id="csvFile" name="csvFile" accept=".csv" required>
                                            <small class="text-muted">
                                                Format: product_code, quantity_change, reason<br>
                                                Download <a href="#" onclick="downloadCSVTemplate()">template</a>
                                            </small>
                                        </div>
                                        <button type="button" class="btn btn-primary" onclick="importCSV()">
                                            <i class="fas fa-upload me-2"></i>Import
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-list-ol me-2"></i>Quick Add</h6>
                                </div>
                                <div class="card-body">
                                    <form id="quickAddForm">
                                        <div class="mb-3">
                                            <label for="category" class="form-label">Category</label>
                                            <select class="form-select" id="category" name="category">
                                                <option value="">All Products</option>
                                                <?php 
                                                $categories->data_seek(0);
                                                while ($cat = $categories->fetch_assoc()): 
                                                ?>
                                                <option value="<?php echo $cat['category']; ?>">
                                                    <?php echo htmlspecialchars($cat['category']); ?>
                                                </option>
                                                <?php endwhile; ?>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="bulkQuantity" class="form-label">Add Quantity to All</label>
                                            <input type="number" class="form-control" id="bulkQuantity" name="bulkQuantity" min="1" value="10">
                                        </div>
                                        <div class="mb-3">
                                            <label for="bulkReason" class="form-label">Reason</label>
                                            <input type="text" class="form-control" id="bulkReason" name="bulkReason" 
                                                   value="Bulk stock addition" required>
                                        </div>
                                        <button type="button" class="btn btn-success" onclick="bulkAddStock()">
                                            <i class="fas fa-plus-circle me-2"></i>Add to All
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Set product for add stock modal
        function setAddStockProduct(id, name) {
            document.getElementById('addProductId').value = id;
            document.getElementById('addProductName').value = name;
            document.getElementById('quantity').value = '';
            document.getElementById('reason').value = '';
        }
        
        // Set product for adjust stock modal
        function setAdjustStockProduct(id, name, currentQty) {
            document.getElementById('adjustProductId').value = id;
            document.getElementById('adjustProductName').value = name;
            document.getElementById('currentQuantity').value = currentQty;
            document.getElementById('new_quantity').value = currentQty;
            document.getElementById('reason').value = '';
        }
        
        // Delete product confirmation
        function deleteProduct(id, name) {
            if (confirm('Are you sure you want to delete "' + name + '"?\n\nNote: Products with sales history cannot be deleted.')) {
                window.location.href = 'inventory.php?action=delete&id=' + id;
            }
        }
        
        // Export inventory
        function exportInventory() {
            const params = new URLSearchParams(window.location.search);
            window.open('export_inventory.php?' + params.toString(), '_blank');
        }
        
        // Download CSV template
        function downloadCSVTemplate() {
            const csvContent = "product_code,quantity_change,reason\nBKY001,10,New stock received\nBKY002,-5,Expired items\nBKY003,20,Supplier delivery";
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'stock_update_template.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        }
        
        // Import CSV
        function importCSV() {
            const fileInput = document.getElementById('csvFile');
            if (!fileInput.files.length) {
                alert('Please select a CSV file');
                return;
            }
            
            const formData = new FormData(document.getElementById('importForm'));
            formData.append('action', 'import_stock');
            
            fetch('import_stock.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Upload failed: ' + error.message);
            });
        }
        
        // Bulk add stock
        function bulkAddStock() {
            const category = document.getElementById('category').value;
            const quantity = document.getElementById('bulkQuantity').value;
            const reason = document.getElementById('bulkReason').value;
            
            if (!reason.trim()) {
                alert('Please enter a reason');
                return;
            }
            
            if (confirm(`Add ${quantity} units to ${category ? 'selected category' : 'all products'}?`)) {
                fetch('bulk_stock.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `category=${encodeURIComponent(category)}&quantity=${quantity}&reason=${encodeURIComponent(reason)}`
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) {
                        location.reload();
                    }
                })
                .catch(error => {
                    alert('Error: ' + error.message);
                });
            }
        }
    </script>
</body>
</html>