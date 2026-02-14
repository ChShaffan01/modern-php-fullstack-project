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

$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? 0;
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_product'])) {
        $productData = [
            'product_code' => trim($_POST['product_code']),
            'product_name' => trim($_POST['product_name']),
            'category' => trim($_POST['category']),
            'description' => trim($_POST['description']),
            'price' => (float)$_POST['price'],
            'cost' => (float)$_POST['cost'],
            'quantity' => (int)$_POST['quantity'],
            'min_stock_level' => (int)$_POST['min_stock_level'],
            'unit' => trim($_POST['unit'])
        ];
        
        // Validate required fields
        if (empty($productData['product_code']) || empty($productData['product_name']) || 
            empty($productData['category']) || $productData['price'] <= 0) {
            $message = 'Please fill all required fields';
            $messageType = 'danger';
        } else {
            // Check if product code already exists (for new products)
            if ($action === 'add') {
                $sql = "SELECT id FROM products WHERE product_code = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $productData['product_code']);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $message = 'Product code already exists';
                    $messageType = 'danger';
                }
            }
            
            if (!$message) {
                // Handle image upload
                $imagePath = null;
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = upload_file(
                        $_FILES['product_image'],
                        '../uploads/products/',
                        ['jpg', 'jpeg', 'png', 'gif', 'webp'],
                        5 * 1024 * 1024 // 5MB
                    );
                    
                    if ($uploadResult['success']) {
                        $imagePath = str_replace('../', '', $uploadResult['file_path']);
                    } else {
                        $message = $uploadResult['message'];
                        $messageType = 'warning';
                    }
                }
                
                if ($action === 'edit' && $productId > 0) {
                    // Update existing product
                    if ($imagePath) {
                        $sql = "UPDATE products SET 
                                product_code = ?, product_name = ?, category = ?, description = ?,
                                price = ?, cost = ?, quantity = ?, min_stock_level = ?, unit = ?, image_url = ?
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssddiiisi", 
                            $productData['product_code'], $productData['product_name'], 
                            $productData['category'], $productData['description'],
                            $productData['price'], $productData['cost'], 
                            $productData['quantity'], $productData['min_stock_level'],
                            $productData['unit'], $imagePath, $productId);
                    } else {
                        $sql = "UPDATE products SET 
                                product_code = ?, product_name = ?, category = ?, description = ?,
                                price = ?, cost = ?, quantity = ?, min_stock_level = ?, unit = ?
                                WHERE id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssddiiisi", 
                            $productData['product_code'], $productData['product_name'], 
                            $productData['category'], $productData['description'],
                            $productData['price'], $productData['cost'], 
                            $productData['quantity'], $productData['min_stock_level'],
                            $productData['unit'], $productId);
                    }
                } else {
                    // Insert new product
                    if ($imagePath) {
                        $sql = "INSERT INTO products (product_code, product_name, category, description,
                                price, cost, quantity, min_stock_level, unit, image_url) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssddiiis", 
                            $productData['product_code'], $productData['product_name'], 
                            $productData['category'], $productData['description'],
                            $productData['price'], $productData['cost'], 
                            $productData['quantity'], $productData['min_stock_level'],
                            $productData['unit'], $imagePath);
                    } else {
                        $sql = "INSERT INTO products (product_code, product_name, category, description,
                                price, cost, quantity, min_stock_level, unit) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssddiii", 
                            $productData['product_code'], $productData['product_name'], 
                            $productData['category'], $productData['description'],
                            $productData['price'], $productData['cost'], 
                            $productData['quantity'], $productData['min_stock_level'],
                            $productData['unit']);
                    }
                }
                
                if ($stmt->execute()) {
                    $message = $action === 'edit' ? 'Product updated successfully!' : 'Product added successfully!';
                    $messageType = 'success';
                    
                    if ($action === 'add') {
                        header('Location: products.php?action=list&msg=added');
                        exit();
                    }
                } else {
                    $message = 'Failed to save product: ' . $stmt->error;
                    $messageType = 'danger';
                }
            }
        }
    }
}

// Get product for editing
$product = null;
if ($action === 'edit' && $productId > 0) {
    $sql = "SELECT * FROM products WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    if (!$product) {
        $message = 'Product not found';
        $messageType = 'danger';
        $action = 'list';
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
        // Delete image file if exists
        $sql = "SELECT image_url FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        $imageData = $result->fetch_assoc();
        
        if (!empty($imageData['image_url']) && file_exists('../' . $imageData['image_url'])) {
            unlink('../' . $imageData['image_url']);
        }
        
        // Delete product
        $sql = "DELETE FROM products WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $productId);
        
        if ($stmt->execute()) {
            header('Location: products.php?action=list&msg=deleted');
            exit();
        } else {
            $message = 'Failed to delete product';
            $messageType = 'danger';
        }
    }
}

// For list view, get categories and products
if ($action === 'list') {
    // Get filter parameters
    $search = $_GET['search'] ?? '';
    $category = $_GET['category'] ?? 'all';
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
    $perPage = 15;
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
    
    // Get products
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
}

// Get existing categories for dropdown
$existingCategories = [];
$catSql = "SELECT DISTINCT category FROM products ORDER BY category";
$catResult = $conn->query($catSql);
while ($row = $catResult->fetch_assoc()) {
    $existingCategories[] = $row['category'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'add' ? 'Add' : ($action === 'edit' ? 'Edit' : 'Manage'); ?> Products - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .product-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        .price-tag {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(78, 115, 223, 0.9);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
        }
        .category-badge {
            position: absolute;
            top: 10px;
            left: 10px;
        }
        .form-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .image-preview {
            width: 200px;
            height: 200px;
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: #f8f9fa;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .stock-indicator {
            height: 8px;
            border-radius: 4px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Product Form -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?> me-2"></i>
                    <?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?>
                </h1>
                <a href="products.php?action=list" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="form-section">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="save_product" value="1">
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="product_code" class="form-label">Product Code *</label>
                                    <input type="text" class="form-control" id="product_code" name="product_code" 
                                           value="<?php echo $product['product_code'] ?? ''; ?>" required 
                                           placeholder="e.g., BKY001">
                                    <small class="text-muted">Unique identifier for the product</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="product_name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="product_name" name="product_name" 
                                           value="<?php echo $product['product_name'] ?? ''; ?>" required 
                                           placeholder="e.g., Whole Wheat Bread">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <input type="text" class="form-control" id="category" name="category" 
                                           value="<?php echo $product['category'] ?? ''; ?>" required 
                                           list="categoryList" placeholder="e.g., Bread, Pastry, Cake">
                                    <datalist id="categoryList">
                                        <?php foreach ($existingCategories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                    <small class="text-muted">Start typing to see existing categories</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="unit" class="form-label">Unit *</label>
                                    <select class="form-select" id="unit" name="unit" required>
                                        <option value="pcs" <?php echo ($product['unit'] ?? 'pcs') === 'pcs' ? 'selected' : ''; ?>>Pieces</option>
                                        <option value="kg" <?php echo ($product['unit'] ?? '') === 'kg' ? 'selected' : ''; ?>>Kilograms</option>
                                        <option value="g" <?php echo ($product['unit'] ?? '') === 'g' ? 'selected' : ''; ?>>Grams</option>
                                        <option value="lb" <?php echo ($product['unit'] ?? '') === 'lb' ? 'selected' : ''; ?>>Pounds</option>
                                        <option value="oz" <?php echo ($product['unit'] ?? '') === 'oz' ? 'selected' : ''; ?>>Ounces</option>
                                        <option value="dozen" <?php echo ($product['unit'] ?? '') === 'dozen' ? 'selected' : ''; ?>>Dozen</option>
                                        <option value="pack" <?php echo ($product['unit'] ?? '') === 'pack' ? 'selected' : ''; ?>>Pack</option>
                                        <option value="box" <?php echo ($product['unit'] ?? '') === 'box' ? 'selected' : ''; ?>>Box</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="price" class="form-label">Selling Price *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               step="0.01" min="0.01" value="<?php echo $product['price'] ?? '0.00'; ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="cost" class="form-label">Cost Price</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input type="number" class="form-control" id="cost" name="cost" 
                                               step="0.01" min="0" value="<?php echo $product['cost'] ?? '0.00'; ?>">
                                    </div>
                                    <small class="text-muted">Used for profit calculation</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="quantity" class="form-label">Initial Stock Quantity</label>
                                    <input type="number" class="form-control" id="quantity" name="quantity" 
                                           min="0" value="<?php echo $product['quantity'] ?? '0'; ?>">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="min_stock_level" class="form-label">Low Stock Alert Level</label>
                                    <input type="number" class="form-control" id="min_stock_level" name="min_stock_level" 
                                           min="1" value="<?php echo $product['min_stock_level'] ?? '10'; ?>">
                                    <small class="text-muted">Alert when stock falls below this level</small>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" 
                                              rows="3" placeholder="Product description..."><?php echo $product['description'] ?? ''; ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label">Product Image</label>
                                <div class="image-preview mb-3" id="imagePreview">
                                    <?php if (!empty($product['image_url'])): ?>
                                        <img src="../<?php echo $product['image_url']; ?>" 
                                             alt="Product Image" id="previewImage">
                                    <?php else: ?>
                                        <i class="fas fa-image fa-3x text-muted" id="previewIcon"></i>
                                    <?php endif; ?>
                                </div>
                                <input type="file" class="form-control" id="product_image" name="product_image" 
                                       accept="image/*" onchange="previewImage(this)">
                                <small class="text-muted">Max 5MB. JPG, PNG, GIF, WebP formats</small>
                                
                                <?php if (!empty($product['image_url'])): ?>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="remove_image" name="remove_image">
                                    <label class="form-check-label" for="remove_image">
                                        Remove current image
                                    </label>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Profit Calculator -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Profit Calculator</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted">Selling Price</small>
                                        <div class="fw-bold text-success" id="sellingPriceDisplay">$0.00</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Cost Price</small>
                                        <div class="fw-bold text-muted" id="costPriceDisplay">$0.00</div>
                                    </div>
                                    <hr>
                                    <div class="mb-2">
                                        <small class="text-muted">Profit per unit</small>
                                        <div class="fw-bold text-primary" id="profitPerUnit">$0.00</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Profit Margin</small>
                                        <div class="fw-bold" id="profitMargin">0%</div>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Markup Percentage</small>
                                        <div class="fw-bold" id="markupPercentage">0%</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>
                                    <?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
                                </button>
                                <a href="products.php?action=list" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            <!-- Product List View -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <i class="fas fa-boxes me-2"></i>Product Management
                </h1>
                <div>
                    <a href="products.php?action=add" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Product
                    </a>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#importModal">
                        <i class="fas fa-file-import me-2"></i>Import
                    </button>
                </div>
            </div>
            
            <?php 
            if (isset($_GET['msg'])) {
                $messages = [
                    'added' => ['Product added successfully!', 'success'],
                    'updated' => ['Product updated successfully!', 'success'],
                    'deleted' => ['Product deleted successfully!', 'success']
                ];
                if (isset($messages[$_GET['msg']])) {
                    $msg = $messages[$_GET['msg']];
                    echo '<div class="alert alert-' . $msg[1] . ' alert-dismissible fade show">
                            ' . $msg[0] . '
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                          </div>';
                }
            }
            
            if ($message) {
                echo '<div class="alert alert-' . $messageType . ' alert-dismissible fade show">
                        ' . $message . '
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                      </div>';
            }
            ?>
            
            <!-- Filters -->
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="action" value="list">
                        
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        
                        <div class="col-md-3">
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
                        
                        <div class="col-md-3">
                            <select class="form-select" name="sort">
                                <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name (A-Z)</option>
                                <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name (Z-A)</option>
                                <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price (Low-High)</option>
                                <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price (High-Low)</option>
                                <option value="quantity_asc" <?php echo $sort === 'quantity_asc' ? 'selected' : ''; ?>>Stock (Low-High)</option>
                                <option value="quantity_desc" <?php echo $sort === 'quantity_desc' ? 'selected' : ''; ?>>Stock (High-Low)</option>
                                <option value="date_desc" <?php echo $sort === 'date_desc' ? 'selected' : ''; ?>>Newest First</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter me-2"></i>Filter
                            </button>
                            <a href="products.php?action=list" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-2"></i>Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Products Grid/Table View -->
            <div class="card shadow">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-list me-2"></i>Products (<?php echo $totalRows; ?>)
                    </h6>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary btn-sm active" id="gridViewBtn">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="tableViewBtn">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Grid View -->
                <div class="card-body" id="gridView">
                    <div class="row">
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($product = $products->fetch_assoc()): 
                                $profitMargin = calculate_profit_margin($product['cost'], $product['price']);
                                $stockPercent = ($product['min_stock_level'] > 0) ? 
                                               min(100, ($product['quantity'] / ($product['min_stock_level'] * 2)) * 100) : 100;
                            ?>
                            <div class="col-md-4 col-lg-3 mb-4">
                                <div class="card product-card h-100">
                                    <div class="position-relative">
                                        <?php if (!empty($product['image_url'])): ?>
                                            <img src="../<?php echo $product['image_url']; ?>" 
                                                 class="card-img-top product-image" 
                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                        <?php else: ?>
                                            <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                                <i class="fas fa-box fa-3x text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <span class="price-tag"><?php echo format_currency($product['price']); ?></span>
                                        <span class="category-badge badge bg-info"><?php echo $product['category']; ?></span>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                        <p class="card-text text-muted small mb-2">
                                            Code: <?php echo $product['product_code']; ?>
                                            <?php if (!empty($product['description'])): ?>
                                                <br><?php echo truncate_text($product['description'], 60); ?>
                                            <?php endif; ?>
                                        </p>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span>Stock: <?php echo $product['quantity']; ?> <?php echo $product['unit']; ?></span>
                                                <span class="text-muted">Min: <?php echo $product['min_stock_level']; ?></span>
                                            </div>
                                            <div class="stock-indicator progress">
                                                <div class="progress-bar bg-<?php echo $product['quantity'] == 0 ? 'danger' : 
                                                                               ($product['quantity'] <= $product['min_stock_level'] ? 'warning' : 'success'); ?>" 
                                                     style="width: <?php echo $stockPercent; ?>%">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="badge bg-<?php echo $profitMargin >= 30 ? 'success' : 
                                                                         ($profitMargin >= 20 ? 'info' : 
                                                                         ($profitMargin >= 10 ? 'warning' : 'danger')); ?>">
                                                    <?php echo $profitMargin; ?>% margin
                                                </span>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="inventory.php" class="btn btn-outline-success">
                                                    <i class="fas fa-warehouse"></i>
                                                </a>
                                                <button class="btn btn-outline-danger" 
                                                        onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo addslashes($product['product_name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No products found</h5>
                                <p>Try adjusting your filters or add new products.</p>
                                <a href="products.php?action=add" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add Product
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Table View (Hidden by default) -->
                <div class="card-body d-none" id="tableView">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Stock</th>
                                    <th>Price</th>
                                    <th>Cost</th>
                                    <th>Margin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($products->num_rows > 0): 
                                    $products->data_seek(0);
                                    $counter = ($page - 1) * $perPage + 1;
                                ?>
                                    <?php while ($product = $products->fetch_assoc()): 
                                        $profitMargin = calculate_profit_margin($product['cost'], $product['price']);
                                    ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($product['image_url'])): ?>
                                                <img src="../<?php echo $product['image_url']; ?>" 
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
                                                    <small class="text-muted"><?php echo $product['product_code']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $product['category']; ?></span>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    <span class="fw-bold"><?php echo $product['quantity']; ?></span>
                                                    <small class="text-muted d-block"><?php echo $product['unit']; ?></small>
                                                </div>
                                                <?php if ($product['quantity'] <= $product['min_stock_level']): ?>
                                                <span class="badge bg-warning">Low</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-success fw-bold"><?php echo format_currency($product['price']); ?></td>
                                        <td class="text-muted"><?php echo format_currency($product['cost']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $profitMargin >= 30 ? 'success' : 
                                                                     ($profitMargin >= 20 ? 'info' : 
                                                                     ($profitMargin >= 10 ? 'warning' : 'danger')); ?>">
                                                <?php echo $profitMargin; ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" 
                                                   class="btn btn-outline-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="inventory.php" class="btn btn-outline-success">
                                                    <i class="fas fa-warehouse"></i>
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
                                        <td colspan="8" class="text-center py-5">
                                            <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                                            <h5 class="text-muted">No products found</h5>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="card-footer">
                    <nav aria-label="Page navigation">
                        <?php echo generate_pagination($page, $totalPages, 'products.php?action=list&page={page}&' . http_build_query($_GET)); ?>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Products</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="importForm" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="importFile" class="form-label">Select CSV File</label>
                            <input type="file" class="form-control" id="importFile" name="importFile" accept=".csv" required>
                            <small class="text-muted">
                                Format: product_code,product_name,category,description,price,cost,quantity,min_stock_level,unit<br>
                                Download <a href="#" onclick="downloadCSVTemplate()">template</a>
                            </small>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="updateExisting" name="updateExisting">
                            <label class="form-check-label" for="updateExisting">
                                Update existing products (by product_code)
                            </label>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="importProducts()">Import</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Toggle between grid and table view
    document.getElementById('gridViewBtn')?.addEventListener('click', function() {
        document.getElementById('gridView').classList.remove('d-none');
        document.getElementById('tableView').classList.add('d-none');
        this.classList.add('active');
        document.getElementById('tableViewBtn').classList.remove('active');
    });
    
    document.getElementById('tableViewBtn')?.addEventListener('click', function() {
        document.getElementById('gridView').classList.add('d-none');
        document.getElementById('tableView').classList.remove('d-none');
        this.classList.add('active');
        document.getElementById('gridViewBtn').classList.remove('active');
    });
    
    // Image preview
    function previewImage(input) {
        const preview = document.getElementById('imagePreview');
        if (!preview) return;
        
        const previewImage = document.getElementById('previewImage');
        const previewIcon = document.getElementById('previewIcon');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (previewImage) {
                    previewImage.src = e.target.result;
                    if (previewIcon) previewIcon.style.display = 'none';
                } else if (previewIcon) {
                    previewIcon.style.display = 'none';
                    preview.innerHTML = '<img src="' + e.target.result + '" id="previewImage" style="max-width:100%;max-height:100%;object-fit:contain;">';
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Profit calculator - FIXED VERSION
    function calculateProfit() {
        const priceInput = document.getElementById('price');
        const costInput = document.getElementById('cost');
        
        if (!priceInput || !costInput) return;
        
        const price = parseFloat(priceInput.value) || 0;
        const cost = parseFloat(costInput.value) || 0;
        
        const sellingPriceDisplay = document.getElementById('sellingPriceDisplay');
        const costPriceDisplay = document.getElementById('costPriceDisplay');
        const profitPerUnit = document.getElementById('profitPerUnit');
        const profitMargin = document.getElementById('profitMargin');
        const markupPercentage = document.getElementById('markupPercentage');
        
        if (sellingPriceDisplay) sellingPriceDisplay.textContent = '$' + price.toFixed(2);
        if (costPriceDisplay) costPriceDisplay.textContent = '$' + cost.toFixed(2);
        
        const profit = price - cost;
        if (profitPerUnit) profitPerUnit.textContent = '$' + profit.toFixed(2);
        
        const margin = price > 0 ? ((profit / price) * 100) : 0;
        if (profitMargin) {
            profitMargin.textContent = margin.toFixed(1) + '%';
            profitMargin.className = 'fw-bold ' + 
                (margin >= 30 ? 'text-success' : 
                 margin >= 20 ? 'text-info' : 
                 margin >= 10 ? 'text-warning' : 'text-danger');
        }
        
        const markup = cost > 0 ? ((profit / cost) * 100) : 0;
        if (markupPercentage) markupPercentage.textContent = markup.toFixed(1) + '%';
    }
    
    // Initialize profit calculator only when on add/edit page
    document.addEventListener('DOMContentLoaded', function() {
        const priceInput = document.getElementById('price');
        const costInput = document.getElementById('cost');
        
        if (priceInput && costInput) {
            // Attach profit calculator to price and cost inputs
            priceInput.addEventListener('input', calculateProfit);
            costInput.addEventListener('input', calculateProfit);
            
            // Initialize profit calculator
            calculateProfit();
        }
    });
    
    // Delete product confirmation
    function deleteProduct(id, name) {
        if (confirm('Are you sure you want to delete "' + name + '"?\n\nNote: Products with sales history cannot be deleted.')) {
            window.location.href = 'products.php?action=delete&id=' + id;
        }
    }
    
    // Download CSV template
    function downloadCSVTemplate() {
        const csvContent = "product_code,product_name,category,description,price,cost,quantity,min_stock_level,unit\n" +
                         "BKY001,Whole Wheat Bread,Bread,Healthy whole wheat bread,2.99,1.50,100,10,pcs\n" +
                         "BKY002,Chocolate Cake,Cake,Delicious chocolate cake,24.99,12.00,20,5,pcs\n" +
                         "BKY003,Croissant,Pastry,Buttery French croissant,1.99,0.80,150,20,pcs";
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'products_template.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    
    // Import products
    function importProducts() {
        const fileInput = document.getElementById('importFile');
        if (!fileInput || !fileInput.files.length) {
            alert('Please select a CSV file');
            return;
        }
        
        const updateExisting = document.getElementById('updateExisting');
        const formData = new FormData();
        formData.append('importFile', fileInput.files[0]);
        formData.append('updateExisting', updateExisting?.checked ? '1' : '0');
        
        fetch('import_products.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message + '\n\nAdded: ' + data.added + '\nUpdated: ' + data.updated + '\nFailed: ' + data.failed);
                if (data.added > 0 || data.updated > 0) {
                    location.reload();
                }
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            alert('Upload failed: ' + error.message);
        });
    }
    
    // Auto-generate product code
    document.getElementById('product_name')?.addEventListener('blur', function() {
        const productCode = document.getElementById('product_code');
        const category = document.getElementById('category');
        
        if (!productCode || !category || !this.value) return;
        
        if (!productCode.value && this.value && category.value) {
            // Generate code from first letters and random number
            const nameParts = this.value.split(' ');
            let code = '';
            nameParts.forEach(part => {
                if (part.length > 0) {
                    code += part[0].toUpperCase();
                }
            });
            const catCode = category.value.substring(0, 3).toUpperCase();
            const randomNum = Math.floor(Math.random() * 900) + 100;
            productCode.value = catCode + code + randomNum;
        }
    });
</script>
</body>
</html>