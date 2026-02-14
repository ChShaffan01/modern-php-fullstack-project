<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    try {
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            // Debug: Check role
            error_log("Login successful. Role: " . $result['role']);
            
            // Redirect based on role
            if ($result['role'] === 'admin') {
                error_log("Redirecting to admin dashboard");
                header('Location: admin/dashboard.php');
                exit();
            } else {
                error_log("Redirecting to cashier POS");
                header('Location: cashier/pos.php');
                exit();
            }
        } else {
            $error = $result['message'] ?? 'Invalid credentials';
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// If already logged in, redirect based on role
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'] ?? '';
    error_log("Already logged in. Role from session: " . $role);
    
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: cashier/pos.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts for bakery vibe -->
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bakery-brown: #8B4513;
            --warm-brown: #A0522D;
            --light-brown: #D2691E;
            --cream: #FFF8DC;
            --golden: #DAA520;
            --pastry-pink: #FFE4E1;
            --chocolate: #5D4037;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #f9f5f0 0%, #fff8f0 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Animated Background */
        .login-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
            overflow: hidden;
        }
        
        .floating-bakery {
            position: absolute;
            font-size: 24px;
            opacity: 0.2;
            animation: float 20s infinite linear;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
            100% {
                transform: translateY(0) rotate(360deg);
            }
        }
        
        /* Login Container */
        .login-container {
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Login Card */
        .login-card {
            border: none;
            border-radius: 25px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(139, 69, 19, 0.2);
            background: white;
            position: relative;
            z-index: 1;
        }
        
        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--golden), var(--light-brown), var(--bakery-brown));
            z-index: 2;
        }
        
        /* Header */
        .login-header {
            background: linear-gradient(135deg, var(--bakery-brown) 0%, var(--warm-brown) 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '🍞🥐🥖';
            position: absolute;
            top: 10px;
            left: 0;
            right: 0;
            font-size: 50px;
            opacity: 0.2;
            animation: float 15s infinite linear;
        }
        
        .bakery-logo {
            font-family: 'Pacifico', cursive;
            font-size: 2.5rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        
        .login-subtitle {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* Body */
        .login-body {
            padding: 40px;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--chocolate);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-label i {
            color: var(--light-brown);
            width: 20px;
        }
        
        .form-control {
            border: 2px solid var(--cream);
            border-radius: 12px;
            padding: 12px 20px;
            font-size: 1rem;
            color: var(--chocolate);
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--golden);
            box-shadow: 0 0 0 0.25rem rgba(218, 165, 32, 0.25);
            transform: translateY(-2px);
        }
        
        .form-control::placeholder {
            color: #aaa;
        }
        
        /* Login Button */
        .btn-login {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--bakery-brown) 100%);
            color: white;
            border: none;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 12px;
            width: 100%;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(139, 69, 19, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(-1px);
        }
        
        /* Error Message */
        .alert-bakery {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 25px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Links */
        .login-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid var(--cream);
        }
        
        .link-bakery {
            color: var(--light-brown);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .link-bakery:hover {
            color: var(--bakery-brown);
            transform: translateX(5px);
        }
        
        /* Demo Info */
        .demo-info {
            background: var(--cream);
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
            border: 2px dashed var(--light-brown);
        }
        
        .demo-title {
            color: var(--bakery-brown);
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .demo-credentials {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .credential-box {
            background: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--pastry-pink);
        }
        
        .credential-label {
            font-size: 0.8rem;
            color: var(--light-brown);
            margin-bottom: 5px;
        }
        
        .credential-value {
            font-weight: 600;
            color: var(--bakery-brown);
            font-family: monospace;
        }
        
        /* Footer */
        .login-footer {
            text-align: center;
            padding: 20px;
            color: var(--chocolate);
            opacity: 0.7;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .login-body {
                padding: 30px 20px;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .bakery-logo {
                font-size: 2rem;
            }
        }
        
        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 40px;
            background: none;
            border: none;
            color: var(--light-brown);
            cursor: pointer;
        }
        
        /* Loading Animation */
        .btn-loading .spinner-border {
            width: 1.2rem;
            height: 1.2rem;
            border-width: 0.15em;
        }
        
        /* Checkbox */
        .form-check-input:checked {
            background-color: var(--bakery-brown);
            border-color: var(--bakery-brown);
        }
        
        /* Animation for success */
        @keyframes success {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .success-animation {
            animation: success 0.5s ease;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="login-bg">
        <?php for($i=0; $i<15; $i++): ?>
        <div class="floating-bakery" style="
            top: <?php echo rand(5, 95); ?>%;
            left: <?php echo rand(5, 95); ?>%;
            font-size: <?php echo rand(20, 40); ?>px;
            animation-delay: -<?php echo rand(0, 20); ?>s;
            animation-duration: <?php echo rand(15, 30); ?>s;
        "><?php echo ['🍞', '🥐', '🥖', '🥨', '🍰', '🧁', '🍪', '🥮'][rand(0,7)]; ?></div>
        <?php endfor; ?>
    </div>

    <div class="login-container">
        <div class="login-card">
            <!-- Header -->
            <div class="login-header">
                <h1 class="bakery-logo">
                    <i class="fas fa-bread-slice"></i>
                    <?php echo SITE_NAME; ?>
                </h1>
                <p class="login-subtitle">Welcome back! Please sign in to continue</p>
            </div>
            
            <!-- Body -->
            <div class="login-body">
                <?php if ($error): ?>
                    <div class="alert alert-bakery alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="loginForm">
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-user"></i>
                            Username
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               required 
                               autofocus 
                               placeholder="Enter your username"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i>
                            Password
                        </label>
                        <div class="position-relative">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   required 
                                   placeholder="Enter your password">
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-check mb-4">
                        <input class="form-check-input" type="checkbox" id="rememberMe">
                        <label class="form-check-label" for="rememberMe">
                            Remember me
                        </label>
                    </div>
                    
                    <button type="submit" class="btn btn-login" id="loginButton">
                        <i class="fas fa-sign-in-alt"></i>
                        Sign In
                    </button>
                </form>
                
                <!-- Demo Credentials -->
                <div class="demo-info">
                    <div class="demo-title">
                        <i class="fas fa-key"></i>
                        Demo Credentials
                    </div>
                    <div class="demo-credentials">
                        <div class="credential-box">
                            <div class="credential-label">Username</div>
                            <div class="credential-value">admin</div>
                        </div>
                        <div class="credential-box">
                            <div class="credential-label">Password</div>
                            <div class="credential-value">admin123</div>
                        </div>
                        <div class="credential-box">
                            <div class="credential-label">Username</div>
                            <div class="credential-value">cashier</div>
                        </div>
                        <div class="credential-box">
                            <div class="credential-label">Password</div>
                            <div class="credential-value">cashier123</div>
                        </div>
                    </div>
                </div>
                
                <!-- Links -->
                <div class="login-links">
                    <a href="index.php" class="link-bakery">
                        <i class="fas fa-home"></i>
                        Back to Home
                    </a>
                    <span class="mx-3">•</span>
                    <a href="register.php" class="link-bakery">
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div class="login-footer">
                <small>
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.
                </small>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password Toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Form submission animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const button = document.getElementById('loginButton');
            const originalText = button.innerHTML;
            
            // Add loading animation
            button.innerHTML = `
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Signing in...
            `;
            button.classList.add('btn-loading');
            button.disabled = true;
            
            // Simulate loading if form is valid
            setTimeout(() => {
                if (!this.checkValidity()) {
                    button.innerHTML = originalText;
                    button.classList.remove('btn-loading');
                    button.disabled = false;
                }
            }, 1500);
        });

        // Auto-fill demo credentials on click
        document.querySelectorAll('.credential-box').forEach(box => {
            box.addEventListener('click', function() {
                const value = this.querySelector('.credential-value').textContent;
                const label = this.querySelector('.credential-label').textContent.toLowerCase();
                
                if (label.includes('username')) {
                    document.getElementById('username').value = value;
                    document.getElementById('username').focus();
                } else if (label.includes('password')) {
                    document.getElementById('password').value = value;
                    document.getElementById('password').focus();
                }
                
                // Animate the clicked box
                this.classList.add('success-animation');
                setTimeout(() => {
                    this.classList.remove('success-animation');
                }, 500);
            });
        });

        // Enter key to submit
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && 
                (document.getElementById('username').value && 
                 document.getElementById('password').value)) {
                document.getElementById('loginForm').submit();
            }
        });

        // Focus effects
        const inputs = document.querySelectorAll('.form-control');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.classList.remove('focused');
            });
        });

        // Add some CSS for focused state
        const style = document.createElement('style');
        style.textContent = `
            .form-group.focused .form-label {
                color: var(--bakery-brown);
            }
            
            .form-group.focused .form-label i {
                color: var(--bakery-brown);
                animation: bounce 0.5s ease;
            }
            
            @keyframes bounce {
                0%, 100% { transform: translateX(0); }
                50% { transform: translateX(5px); }
            }
            
            .credential-box {
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .credential-box:hover {
                transform: translateY(-3px);
                box-shadow: 0 5px 15px rgba(139, 69, 19, 0.1);
                border-color: var(--golden);
            }
        `;
        document.head.appendChild(style);

        // Auto-focus username if empty
        window.addEventListener('load', function() {
            if (!document.getElementById('username').value) {
                document.getElementById('username').focus();
            }
        });
    </script>
</body>
</html>