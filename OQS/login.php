<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include configuration first
require_once 'includes/auth.php';

// Initialize database connection
try {
    require_once 'includes/db.php';
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Include Auth class and create instance
require_once 'includes/Auth.php';
$auth = new Auth($pdo);

$page_title = "Login";

// Check if user is already logged in
if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Both fields are required';
    } elseif ($auth->login($email, $password)) {
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Invalid email or password';
    }
}

// Now include the header after initializing everything
require_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="fas fa-sign-in-alt me-2"></i>Login to Your Account</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert alert-success">
                        Registration successful! Please login.
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required
                               value="<?php echo $_POST['email'] ?? ''; ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                </form>
                
                <hr class="my-4">
                
                <p class="text-center mb-0">
                    Don't have an account? 
                    <a href="register.php" class="text-primary">Register here</a>
                </p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body text-center">
                <h6>Demo Accounts</h6>
                <div class="row justify-content-center">
                    <div class="col-6">
                        <p class="mb-1"><strong>Admin</strong></p>
                        <p class="mb-1">admin@quiz.com</p>
                        <p class="mb-0">password</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>