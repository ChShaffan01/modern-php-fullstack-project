<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    header('Location: ../login.php');
    exit();
}

require_once '../includes/db_connect.php';

// Handle actions
$action = $_GET['action'] ?? '';
$userId = $_GET['id'] ?? 0;

if ($action === 'approve' && $userId) {
    $sql = "UPDATE users SET status = 'active' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Update registration log
    $adminId = $_SESSION['user_id'];
    $updateLog = "UPDATE registration_logs SET approved_by = ?, approval_time = NOW(), status = 'approved' WHERE user_id = ?";
    $stmt2 = $conn->prepare($updateLog);
    $stmt2->bind_param("ii", $adminId, $userId);
    $stmt2->execute();
    
    header('Location: users.php?msg=approved');
    exit();
}

if ($action === 'reject' && $userId) {
    $sql = "UPDATE users SET status = 'inactive' WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    header('Location: users.php?msg=rejected');
    exit();
}

if ($action === 'delete' && $userId) {
    // Check if user is not deleting themselves
    if ($userId != $_SESSION['user_id']) {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        
        header('Location: users.php?msg=deleted');
        exit();
    }
}

// Get all users
$sql = "SELECT u.*, r.registration_time, r.status as reg_status 
        FROM users u 
        LEFT JOIN registration_logs r ON u.id = r.user_id 
        ORDER BY u.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container-fluid p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users"></i> User Management</h2>
                <a href="register_user.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
            </div>
            
            <?php if (isset($_GET['msg'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php
                    $messages = [
                        'approved' => 'User approved successfully',
                        'rejected' => 'User rejected',
                        'deleted' => 'User deleted successfully',
                        'added' => 'User added successfully'
                    ];
                    echo $messages[$_GET['msg']] ?? 'Action completed';
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Registered Users</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-info">You</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $user['role'] === 'admin' ? 'danger' : 
                                                 ($user['role'] === 'manager' ? 'warning' : 'primary'); 
                                        ?>">
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($user['status'] === 'inactive'): ?>
                                                <a href="users.php?action=approve&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-success" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['status'] === 'active' && $user['id'] != $_SESSION['user_id']): ?>
                                                <a href="users.php?action=reject&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-warning" title="Deactivate">
                                                    <i class="fas fa-ban"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="edit_user.php?id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-danger" 
                                                   onclick="return confirm('Delete this user permanently?')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer">
                    <small>Total Users: <?php echo $result->num_rows; ?></small>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>