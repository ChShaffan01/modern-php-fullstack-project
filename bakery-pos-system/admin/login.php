<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$error = '';
$success = '';

// Debug: Check if files are loaded
if (!defined('SITE_NAME')) {
    $error = "Configuration not loaded. Check config.php path.";
}

// Redirect if already logged in as admin
if ($auth->isLoggedIn() && $auth->hasRole('admin')) {
    header('Location: dashboard.php');
    exit();
}

// Redirect if logged in as non-admin
if ($auth->isLoggedIn() && !$auth->hasRole('admin')) {
    header('Location: ../cashier/pos.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            // Check if user is admin
            if ($result['role'] === 'admin' ) {
                header('Location: dashboard.php');
                exit();
            } else {
                // Non-admin trying to access admin login
                $error = 'Access denied. Admin privileges required.';
                // Log them out since they shouldn't be here
                $auth->logout();
            }
        } else {
            $error = $result['message'] ?? 'Invalid credentials';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fallback site name if config not loaded
$siteName = defined('SITE_NAME') ? SITE_NAME : 'Bakery Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo htmlspecialchars($siteName); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Basic Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --bakery-brown: #8B4513;
            --bakery-cream: #FFF8DC;
            --bakery-gold: #D4A017;
            --bakery-red: #C41E3A;
            --bakery-green: #556B2F;
        }
        
        body {
            background: #FFF8DC;
            font-family: 'Quicksand', sans-serif;
            min-height: 100vh;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .bakery-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            border: 5px solid var(--bakery-brown);
        }
        
        .bakery-header {
            background: linear-gradient(45deg, var(--bakery-brown), var(--bakery-gold));
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        
        .bakery-icon {
            font-size: 3rem;
            background: rgba(255, 255, 255, 0.2);
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 3px solid white;
        }
        
        .bakery-title {
            font-family: 'Pacifico', cursive;
            font-size: 2rem;
            margin-bottom: 5px;
        }
        
        .form-control {
            border: 2px solid var(--bakery-brown);
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 16px;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(139, 69, 19, 0.25);
            border-color: var(--bakery-gold);
        }
        
        .input-group-text {
            background: var(--bakery-brown);
            border: 2px solid var(--bakery-brown);
            color: white;
        }
        
        .login-btn {
            background: linear-gradient(45deg, var(--bakery-brown), var(--bakery-red));
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
            width: 100%;
            color: white;
            transition: transform 0.3s;
        }
        
        .login-btn:hover {
            transform: translateY(-2px);
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
            padding: 8px;
            border-radius: 8px;
            background: #f8f8f8;
        }
        
        .feature-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--bakery-brown);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 10px;
        }
        
        .back-link {
            color: var(--bakery-brown);
            text-decoration: none;
            font-weight: 600;
        }
        
        .back-link:hover {
            color: var(--bakery-red);
        }
    </style>
</head>
<body>
    <!-- Debug Info (remove in production) -->
    <?php if ($error && strpos($error, 'Configuration not loaded') !== false): ?>
        <div style="position: fixed; top: 10px; left: 10px; background: red; color: white; padding: 10px; border-radius: 5px; z-index: 1000;">
            Debug: Config.php path issue. Check file exists at: <?php echo realpath('../includes/config.php'); ?>
        </div>
    <?php endif; ?>
    
    <div class="bakery-card">
        <!-- Header -->
        <div class="bakery-header">
            <div class="bakery-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <h1 class="bakery-title"><?php echo htmlspecialchars($siteName); ?></h1>
            <p>Bakery Admin Portal</p>
        </div>
        
        <!-- Login Form -->
        <div class="card-body p-4">
            <h4 class="text-center mb-4" style="color: var(--bakery-brown);">
                <i class="fas fa-key me-2"></i>Administrator Login
            </h4>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i> 
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i> 
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i> Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" 
                           required placeholder="Enter admin username">
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i> Password
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="password" name="password" 
                               required placeholder="Enter password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>
                
                <button type="submit" class="login-btn mb-4">
                    <i class="fas fa-sign-in-alt me-2"></i> Login
                </button>
            </form>
            
            <!-- Admin Features -->
            <div class="mt-4 p-3" style="background: #f8f8f8; border-radius: 10px;">
                <h6 class="mb-3"><i class="fas fa-star me-2"></i>Admin Features:</h6>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <span>Staff Management</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <span>Sales Reports</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <span>System Settings</span>
                </div>
            </div>
            
            <!-- Links -->
            <div class="text-center mt-4">
                <div class="mb-2">
                    <a href="../login.php" class="back-link">
                        <i class="fas fa-arrow-left me-2"></i>Back to Main Login
                    </a>
                </div>
                <div>
                    <a href="../index.php" class="back-link">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple and reliable JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin login page loaded');
            
            // Auto-focus on username field
            const usernameField = document.getElementById('username');
            if (usernameField) {
                usernameField.focus();
            }
            
            // Toggle password visibility
            const toggleBtn = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            
            if (toggleBtn && passwordField) {
                toggleBtn.addEventListener('click', function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                });
            }
            
            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const username = document.getElementById('username').value.trim();
                    const password = document.getElementById('password').value.trim();
                    
                    if (!username || !password) {
                        e.preventDefault();
                        alert('Please fill in both username and password');
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>