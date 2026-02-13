<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
 session_start();


require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/init.php';


$page_title = "Register";

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    if (headers_sent()) {
        echo '<script>window.location.href = "dashboard.php";</script>';
        exit();
    } else {
        header("Location: dashboard.php");
        exit();
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $userId = $auth->register($name, $email, $password);
        if ($userId) {
            // Auto login after registration
            if ($auth->login($email, $password)) {
                if (headers_sent()) {
                    echo '<script>window.location.href = "dashboard.php?registered=1";</script>';
                    exit();
                } else {
                    header("Location: dashboard.php?registered=1");
                    exit();
                }
            } else {
                if (headers_sent()) {
                    echo '<script>window.location.href = "login.php";</script>';
                    exit();
                } else {
                    header("Location: login.php");
                    exit();
                }
            }
        } else {
            $error = 'Email already exists';
        }
    }
}

// Now include the header AFTER all processing and potential redirects
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-user-plus me-2"></i>Create Account</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="name" name="name" required 
                               value="<?php echo $_POST['name'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus me-2"></i>Register
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center mb-0">
                    Already have an account? 
                    <a href="login.php" class="text-primary">Login here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>