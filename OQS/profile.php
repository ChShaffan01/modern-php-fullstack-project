<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once 'includes/header.php';
require_once 'includes/init.php';

$page_title = "My Profile";
$auth->requireLogin();

$user = $auth->getUser($_SESSION['user_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $error = '';
    $success = '';
    
    if (empty($name)) {
        $error = 'Name is required';
    } else {
        // Update name
        $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->execute([$name, $_SESSION['user_id']]);
        $_SESSION['user_name'] = $name;
        $success = 'Profile updated successfully!';
        
        // Update password if provided
        if (!empty($current_password) || !empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error = 'All password fields are required to change password';
            } elseif ($new_password !== $confirm_password) {
                $error = 'New passwords do not match';
            } elseif (strlen($new_password) < 6) {
                $error = 'New password must be at least 6 characters';
            } else {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user_db = $stmt->fetch();
                
                if (password_verify($current_password, $user_db['password'])) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed_password, $_SESSION['user_id']]);
                    $success = 'Profile and password updated successfully!';
                } else {
                    $error = 'Current password is incorrect';
                }
            }
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>My Profile</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <div class="mb-3">
                                <div class="rounded-circle bg-primary d-inline-flex p-4 mb-2">
                                    <i class="fas fa-user fa-3x text-white"></i>
                                </div>
                                <h5><?php echo $_SESSION['user_name']; ?></h5>
                                <span class="badge bg-<?php echo ($_SESSION['user_role'] == 'admin') ? 'danger' : 'primary'; ?>">
                                    <?php echo ucfirst($_SESSION['user_role']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" value="<?php echo $user['email']; ?>" disabled>
                                <small class="text-muted">Email cannot be changed</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?php echo $user['name']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Account Created</label>
                                <input type="text" class="form-control" 
                                       value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h5 class="mb-3">Change Password</h5>
                    <p class="text-muted">Leave blank to keep current password</p>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password">
                            <small class="text-muted">Min. 6 characters</small>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>