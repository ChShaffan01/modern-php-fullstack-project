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

// Get current user details
$userId = $_SESSION['user_id'];
$user = $auth->getUserById($userId);

if (!$user) {
    die('User not found');
}

$errors = [];
$success = false;
$successMessage = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        $updateData = [
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        $result = $auth->updateUserProfile($userId, $updateData);
        
        if ($result['success']) {
            $success = true;
            $successMessage = 'Profile updated successfully!';
            
            // Update session
            $_SESSION['full_name'] = $updateData['full_name'];
            
            // Refresh user data
            $user = $auth->getUserById($userId);
        } else {
            $errors = $result['errors'] ?? ['Failed to update profile'];
        }
    }
    
    elseif ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // Validate
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'All password fields are required';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'New password must be at least 6 characters';
        } else {
            $result = $auth->changePassword($userId, $currentPassword, $newPassword);
            
            if ($result['success']) {
                $success = true;
                $successMessage = 'Password changed successfully!';
            } else {
                $errors[] = $result['message'];
            }
        }
    }
    
    elseif ($action === 'upload_avatar') {
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = upload_file(
                $_FILES['avatar'],
                '../uploads/avatars/',
                ['jpg', 'jpeg', 'png', 'gif'],
                2 * 1024 * 1024 // 2MB
            );
            
            if ($uploadResult['success']) {
                // Update avatar in database
                $avatarPath = str_replace('../', '', $uploadResult['file_path']);
                $sql = "UPDATE users SET avatar = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $avatarPath, $userId);
                
                if ($stmt->execute()) {
                    $success = true;
                    $successMessage = 'Avatar updated successfully!';
                    $user['avatar'] = $avatarPath;
                } else {
                    $errors[] = 'Failed to update avatar in database';
                }
            } else {
                $errors[] = $uploadResult['message'];
            }
        } else {
            $errors[] = 'Please select a valid image file';
        }
    }
}

// Get user statistics
$sql = "SELECT 
            COUNT(*) as total_sales,
            SUM(grand_total) as total_amount,
            AVG(grand_total) as average_sale
        FROM sales 
        WHERE user_id = ? AND payment_status = 'paid'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$statsResult = $stmt->get_result();
$stats = $statsResult->fetch_assoc();

// Get recent sales
$sql = "SELECT * FROM sales 
        WHERE user_id = ? 
        ORDER BY sale_date DESC 
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$recentSales = $stmt->get_result();

// Get sales by month
$sql = "SELECT 
            DATE_FORMAT(sale_date, '%Y-%m') as month,
            COUNT(*) as sales_count,
            SUM(grand_total) as total_amount
        FROM sales 
        WHERE user_id = ? 
        GROUP BY DATE_FORMAT(sale_date, '%Y-%m')
        ORDER BY month DESC 
        LIMIT 6";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$monthlySales = $stmt->get_result();

// Get performance compared to other cashiers
$sql = "SELECT 
            u.full_name,
            COUNT(s.id) as sales_count,
            SUM(s.grand_total) as total_amount
        FROM users u
        LEFT JOIN sales s ON u.id = s.user_id 
            AND s.payment_status = 'paid'
            AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        WHERE u.role = 'cashier' 
        GROUP BY u.id
        ORDER BY total_amount DESC";
$cashierStats = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 20px 20px;
        }
        .avatar-container {
            position: relative;
            width: 150px;
            height: 150px;
            margin: 0 auto 20px;
        }
        .avatar-img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 5px solid white;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: #4e73df;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            border: 3px solid white;
        }
        .avatar-upload input {
            display: none;
        }
        .stat-card {
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .nav-tabs .nav-link {
            border: none;
            border-bottom: 3px solid transparent;
            color: #6c757d;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #4e73df;
            border-bottom: 3px solid #4e73df;
            background: transparent;
        }
        .profile-section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .badge-rank {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 20px;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        .activity-item {
            border-left: 3px solid #4e73df;
            padding-left: 15px;
            margin-bottom: 15px;
        }
        .activity-item.success {
            border-left-color: #28a745;
        }
        .activity-item.warning {
            border-left-color: #ffc107;
        }
        .activity-item.danger {
            border-left-color: #dc3545;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'cashier_nav.php'; ?>
    
    <!-- Profile Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-3 text-center">
                    <div class="avatar-container">
                        <img src="<?php echo !empty($user['avatar']) ? '../' . $user['avatar'] : '../assets/images/default-avatar.png'; ?>" 
                             alt="Avatar" class="avatar-img" id="avatarPreview">
                        <div class="avatar-upload" data-bs-toggle="modal" data-bs-target="#avatarModal">
                            <i class="fas fa-camera"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <h1 class="display-5 mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                    <p class="lead mb-3">
                        <i class="fas fa-user-tag"></i> <?php echo get_role_name($user['role']); ?> 
                        <span class="mx-2">•</span>
                        <i class="fas fa-id-badge"></i> @<?php echo htmlspecialchars($user['username']); ?>
                        <span class="mx-2">•</span>
                        <i class="fas fa-calendar-alt"></i> Member since <?php echo format_date($user['created_at'], 'F Y'); ?>
                    </p>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-white rounded-circle p-3 me-3">
                                    <i class="fas fa-shopping-cart text-primary fa-lg"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo $stats['total_sales'] ?? 0; ?></h4>
                                    <small>Total Sales</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-white rounded-circle p-3 me-3">
                                    <i class="fas fa-dollar-sign text-success fa-lg"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo format_currency($stats['total_amount'] ?? 0); ?></h4>
                                    <small>Total Revenue</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex align-items-center">
                                <div class="bg-white rounded-circle p-3 me-3">
                                    <i class="fas fa-chart-line text-info fa-lg"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo format_currency($stats['average_sale'] ?? 0); ?></h4>
                                    <small>Average Sale</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($successMessage); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="profileTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button">
                    <i class="fas fa-user-circle me-2"></i>Profile
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button">
                    <i class="fas fa-chart-bar me-2"></i>Statistics
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sales-tab" data-bs-toggle="tab" data-bs-target="#sales" type="button">
                    <i class="fas fa-receipt me-2"></i>Recent Sales
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button">
                    <i class="fas fa-shield-alt me-2"></i>Security
                </button>
            </li>
        </ul>

        <!-- Tab Content -->
        <div class="tab-content" id="profileTabContent">
            
            <!-- Profile Tab -->
            <div class="tab-pane fade show active" id="profile" role="tabpanel">
                <div class="row">
                    <div class="col-md-8">
                        <div class="profile-section">
                            <h4 class="mb-4"><i class="fas fa-user-edit me-2"></i>Edit Profile</h4>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" 
                                               value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" 
                                               value="<?php echo get_role_name($user['role']); ?>" disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <?php echo get_status_badge($user['status']); ?>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Account Created</label>
                                    <p class="text-muted"><?php echo format_date($user['created_at']); ?></p>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Last Login</label>
                                    <p class="text-muted">
                                        <?php echo !empty($user['last_login']) ? format_date($user['last_login']) : 'Never logged in'; ?>
                                    </p>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Save Changes
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <!-- Performance Card -->
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Performance</h5>
                            </div>
                            <div class="card-body">
                                <?php 
                                $rank = 1;
                                $userRank = 0;
                                while ($cashier = $cashierStats->fetch_assoc()) {
                                    if ($cashier['full_name'] == $user['full_name']) {
                                        $userRank = $rank;
                                        break;
                                    }
                                    $rank++;
                                }
                                $cashierStats->data_seek(0); // Reset pointer
                                ?>
                                <div class="text-center mb-3">
                                    <span class="badge bg-<?php echo $userRank <= 3 ? 'warning' : 'info'; ?> badge-rank">
                                        Rank #<?php echo $userRank; ?> of <?php echo $cashierStats->num_rows; ?>
                                    </span>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Monthly Performance</small>
                                    <div class="progress">
                                        <div class="progress-bar bg-success" style="width: <?php echo min(($stats['total_sales'] ?? 0) * 5, 100); ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="text-muted d-block mb-1">Sales Target</small>
                                    <div class="progress">
                                        <div class="progress-bar bg-info" style="width: <?php echo min(($stats['total_sales'] ?? 0) * 10, 100); ?>%"></div>
                                    </div>
                                </div>
                                
                                <h6 class="mt-4">Top Cashiers This Month</h6>
                                <div class="list-group list-group-flush">
                                    <?php 
                                    $topRank = 1;
                                    while ($cashier = $cashierStats->fetch_assoc() && $topRank <= 3): 
                                        $cashier = $cashierStats->fetch_assoc();
                                    ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                                        <div>
                                            <span class="badge bg-<?php echo $topRank == 1 ? 'warning' : ($topRank == 2 ? 'secondary' : 'danger'); ?> me-2">
                                                #<?php echo $topRank; ?>
                                            </span>
                                            <?php echo htmlspecialchars($cashier['full_name']); ?>
                                        </div>
                                        <span class="text-success"><?php echo format_currency($cashier['total_amount'] ?? 0); ?></span>
                                    </div>
                                    <?php $topRank++; endwhile; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Monthly Sales</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php while ($month = $monthlySales->fetch_assoc()): ?>
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-2 px-0">
                                        <span><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></span>
                                        <div>
                                            <span class="badge bg-primary me-2"><?php echo $month['sales_count']; ?> sales</span>
                                            <span class="text-success"><?php echo format_currency($month['total_amount']); ?></span>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Tab -->
            <div class="tab-pane fade" id="stats" role="tabpanel">
                <div class="row">
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-primary text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                                <h3><?php echo $stats['total_sales'] ?? 0; ?></h3>
                                <p class="mb-0">Total Sales</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-success text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign fa-3x mb-3"></i>
                                <h3><?php echo format_currency($stats['total_amount'] ?? 0); ?></h3>
                                <p class="mb-0">Total Revenue</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-info text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-3x mb-3"></i>
                                <h3><?php echo format_currency($stats['average_sale'] ?? 0); ?></h3>
                                <p class="mb-0">Average Sale</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="card stat-card bg-warning text-white">
                            <div class="card-body text-center">
                                <i class="fas fa-bullseye fa-3x mb-3"></i>
                                <h3><?php echo calculate_profit_margin(($stats['total_amount'] ?? 0) * 0.7, $stats['total_amount'] ?? 0); ?>%</h3>
                                <p class="mb-0">Profit Margin</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sales Chart -->
                <div class="profile-section">
                    <h4 class="mb-4"><i class="fas fa-chart-line me-2"></i>Sales Trend</h4>
                    <canvas id="salesChart" height="100"></canvas>
                </div>
                
                <!-- Performance Metrics -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="profile-section">
                            <h4 class="mb-4"><i class="fas fa-tachometer-alt me-2"></i>Performance Metrics</h4>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Customer Satisfaction</small>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-success" style="width: 85%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Transaction Speed</small>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-info" style="width: 92%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Upsell Rate</small>
                                <div class="progress mb-3">
                                    <div class="progress-bar bg-warning" style="width: 68%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="profile-section">
                            <h4 class="mb-4"><i class="fas fa-award me-2"></i>Achievements</h4>
                            <div class="row text-center">
                                <div class="col-4 mb-3">
                                    <div class="bg-primary rounded-circle p-3 mx-auto" style="width: 80px; height: 80px;">
                                        <i class="fas fa-star fa-2x text-white"></i>
                                    </div>
                                    <small class="d-block mt-2">First Sale</small>
                                </div>
                                <div class="col-4 mb-3">
                                    <div class="bg-success rounded-circle p-3 mx-auto" style="width: 80px; height: 80px;">
                                        <i class="fas fa-trophy fa-2x text-white"></i>
                                    </div>
                                    <small class="d-block mt-2">Top Seller</small>
                                </div>
                                <div class="col-4 mb-3">
                                    <div class="bg-warning rounded-circle p-3 mx-auto" style="width: 80px; height: 80px;">
                                        <i class="fas fa-bolt fa-2x text-white"></i>
                                    </div>
                                    <small class="d-block mt-2">Quick Seller</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Sales Tab -->
            <div class="tab-pane fade" id="sales" role="tabpanel">
                <div class="profile-section">
                    <h4 class="mb-4"><i class="fas fa-history me-2"></i>Recent Sales (Last 10)</h4>
                    
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
                                    <th>Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($sale = $recentSales->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $sale['invoice_no']; ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($sale['customer_name']); ?></td>
                                    <td><?php echo format_date($sale['sale_date']); ?></td>
                                    <td>
                                        <?php 
                                        $sql = "SELECT COUNT(*) as item_count FROM sale_items WHERE sale_id = ?";
                                        $stmt2 = $conn->prepare($sql);
                                        $stmt2->bind_param("i", $sale['id']);
                                        $stmt2->execute();
                                        $itemResult = $stmt2->get_result();
                                        $itemCount = $itemResult->fetch_assoc()['item_count'];
                                        echo $itemCount . ' item' . ($itemCount != 1 ? 's' : '');
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo get_payment_method_name($sale['payment_method']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo get_status_badge($sale['payment_status']); ?></td>
                                    <td class="text-success fw-bold"><?php echo format_currency($sale['grand_total']); ?></td>
                                    <td>
                                        <a href="../invoice.php?id=<?php echo $sale['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-print"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                
                                <?php if ($recentSales->num_rows == 0): ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-receipt fa-3x mb-3"></i>
                                        <p>No sales records found</p>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="sales.php" class="btn btn-primary">
                            <i class="fas fa-list me-2"></i>View All Sales
                        </a>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">Today's Sales</h6>
                                <?php 
                                $sql = "SELECT SUM(grand_total) as total FROM sales 
                                        WHERE user_id = ? AND DATE(sale_date) = CURDATE()";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $userId);
                                $stmt->execute();
                                $todayResult = $stmt->get_result();
                                $todaySales = $todayResult->fetch_assoc();
                                ?>
                                <h3 class="text-success"><?php echo format_currency($todaySales['total'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">This Week</h6>
                                <?php 
                                $sql = "SELECT SUM(grand_total) as total FROM sales 
                                        WHERE user_id = ? AND sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $userId);
                                $stmt->execute();
                                $weekResult = $stmt->get_result();
                                $weekSales = $weekResult->fetch_assoc();
                                ?>
                                <h3 class="text-primary"><?php echo format_currency($weekSales['total'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <h6 class="text-muted mb-2">This Month</h6>
                                <?php 
                                $sql = "SELECT SUM(grand_total) as total FROM sales 
                                        WHERE user_id = ? AND MONTH(sale_date) = MONTH(CURDATE()) 
                                        AND YEAR(sale_date) = YEAR(CURDATE())";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("i", $userId);
                                $stmt->execute();
                                $monthResult = $stmt->get_result();
                                $monthSales = $monthResult->fetch_assoc();
                                ?>
                                <h3 class="text-warning"><?php echo format_currency($monthSales['total'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Security Tab -->
            <div class="tab-pane fade" id="security" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <div class="profile-section">
                            <h4 class="mb-4"><i class="fas fa-key me-2"></i>Change Password</h4>
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="change_password">
                                
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password *</label>
                                    <input type="password" class="form-control" id="current_password" 
                                           name="current_password" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password *</label>
                                    <input type="password" class="form-control" id="new_password" 
                                           name="new_password" required>
                                    <div class="password-strength mt-2" id="passwordStrength"></div>
                                    <small class="text-muted">Minimum 6 characters with letters and numbers</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" required>
                                    <div class="invalid-feedback" id="passwordMatchError"></div>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-2"></i>Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="profile-section">
                            <h4 class="mb-4"><i class="fas fa-shield-alt me-2"></i>Security Settings</h4>
                            
                            <div class="mb-4">
                                <h6><i class="fas fa-user-lock me-2"></i>Account Security</h6>
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="twoFactorAuth" checked>
                                    <label class="form-check-label" for="twoFactorAuth">
                                        Two-Factor Authentication
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="loginAlerts" checked>
                                    <label class="form-check-label" for="loginAlerts">
                                        Login Activity Alerts
                                    </label>
                                </div>
                                
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="sessionTimeout">
                                    <label class="form-check-label" for="sessionTimeout">
                                        Auto-logout after 30 minutes
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <h6><i class="fas fa-history me-2"></i>Login History</h6>
                                <?php 
                                $sql = "SELECT * FROM login_attempts 
                                        WHERE username = ? 
                                        ORDER BY attempt_time DESC 
                                        LIMIT 5";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("s", $user['username']);
                                $stmt->execute();
                                $loginHistory = $stmt->get_result();
                                ?>
                                
                                <div class="list-group list-group-flush">
                                    <?php while ($login = $loginHistory->fetch_assoc()): ?>
                                    <div class="list-group-item px-0 py-2">
                                        <div class="d-flex justify-content-between">
                                            <span>
                                                <i class="fas fa-<?php echo $login['success'] ? 'check-circle text-success' : 'times-circle text-danger'; ?> me-2"></i>
                                                <?php echo format_date($login['attempt_time'], 'M d, h:i A'); ?>
                                            </span>
                                            <small class="text-muted"><?php echo $login['ip_address']; ?></small>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button class="btn btn-outline-danger">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout All Sessions
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Avatar Upload Modal -->
    <div class="modal fade" id="avatarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_avatar">
                    
                    <div class="modal-body">
                        <div class="text-center mb-4">
                            <div class="avatar-preview mb-3" style="width: 200px; height: 200px; margin: 0 auto;">
                                <img src="<?php echo !empty($user['avatar']) ? '../' . $user['avatar'] : '../assets/images/default-avatar.png'; ?>" 
                                     alt="Avatar Preview" class="img-fluid rounded-circle" 
                                     style="width: 100%; height: 100%; object-fit: cover;" id="modalAvatarPreview">
                            </div>
                            
                            <div class="mb-3">
                                <input type="file" class="form-control" id="avatar" name="avatar" 
                                       accept="image/*" onchange="previewAvatar(this)">
                                <small class="text-muted">Max size: 2MB. Supported: JPG, PNG, GIF</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload Picture</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p class="mb-0">Cashier Profile Management</p>
                </div>
                <div class="col-md-6 text-end">
                    <small>Logged in as: <?php echo htmlspecialchars($user['full_name']); ?></small>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        // Avatar preview
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('modalAvatarPreview').src = e.target.result;
                    document.getElementById('avatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Password strength indicator
        document.getElementById('new_password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[\W_]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength strength-' + Math.min(strength, 4);
            strengthBar.style.width = (strength * 20) + '%';
            
            // Set color based on strength
            const colors = ['#dc3545', '#ffc107', '#17a2b8', '#28a745'];
            strengthBar.style.backgroundColor = colors[Math.min(strength, 4) - 1];
        });
        
        // Password confirmation
        document.getElementById('confirm_password')?.addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirm = this.value;
            const errorDiv = document.getElementById('passwordMatchError');
            
            if (confirm.length > 0 && password !== confirm) {
                this.classList.add('is-invalid');
                errorDiv.textContent = 'Passwords do not match';
                errorDiv.style.display = 'block';
            } else {
                this.classList.remove('is-invalid');
                errorDiv.style.display = 'none';
            }
        });
        
        // Sales Chart
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('salesChart');
            if (ctx) {
                // Get data from PHP (you would need to pass this data from PHP)
                const salesData = {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sales ($)',
                        data: [1200, 1900, 3000, 5000, 2000, 3000],
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.1)',
                        fill: true,
                        tension: 0.4
                    }]
                };
                
                new Chart(ctx, {
                    type: 'line',
                    data: salesData,
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                mode: 'index',
                                intersect: false
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
        });
    </script>
</body>
</html>