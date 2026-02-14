<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();
$errors = [];
$success = false;

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!$auth->validateCSRF($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid CSRF token';
    } else {
        $userData = [
            'username' => trim($_POST['username'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'full_name' => trim($_POST['full_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? '')
        ];
        
        // Validate password confirmation
        if ($userData['password'] !== $userData['confirm_password']) {
            $errors[] = 'Passwords do not match';
        } else {
            $result = $auth->register($userData);
            
            if ($result['success']) {
                $success = true;
            } else {
                if (isset($result['errors'])) {
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    $errors[] = $result['message'] ?? 'Registration failed';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
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
            --success-green: #2E7D32;
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
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .register-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
            overflow: hidden;
        }
        
        .floating-ingredient {
            position: absolute;
            font-size: 24px;
            opacity: 0.2;
            animation: float 25s infinite linear;
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg) scale(1);
            }
            25% {
                transform: translateY(-40px) rotate(90deg) scale(1.2);
            }
            50% {
                transform: translateY(0) rotate(180deg) scale(1);
            }
            75% {
                transform: translateY(40px) rotate(270deg) scale(0.8);
            }
            100% {
                transform: translateY(0) rotate(360deg) scale(1);
            }
        }
        
        /* Main Container */
        .register-container {
            width: 100%;
            max-width: 800px;
            animation: slideUp 0.8s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Register Card */
        .register-card {
            border: none;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 60px rgba(139, 69, 19, 0.25);
            background: white;
            position: relative;
            z-index: 1;
        }
        
        .register-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, var(--golden), var(--light-brown), var(--bakery-brown));
            z-index: 2;
        }
        
        /* Header */
        .register-header {
            background: linear-gradient(135deg, var(--bakery-brown) 0%, var(--warm-brown) 100%);
            color: white;
            padding: 50px 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-header::before {
            content: '🧑‍🍳👩‍🍳';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            font-size: 60px;
            opacity: 0.15;
        }
        
        .register-title {
            font-family: 'Pacifico', cursive;
            font-size: 2.8rem;
            margin-bottom: 15px;
            position: relative;
            z-index: 1;
        }
        
        .register-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Body */
        .register-body {
            padding: 40px;
        }
        
        /* Form Styles */
        .form-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            font-family: 'Pacifico', cursive;
            font-size: 1.8rem;
            color: var(--bakery-brown);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--cream);
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -3px;
            left: 0;
            width: 80px;
            height: 3px;
            background: var(--golden);
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--chocolate);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-label i {
            color: var(--light-brown);
            width: 22px;
            font-size: 1.1rem;
        }
        
        .required::after {
            content: ' *';
            color: #dc3545;
        }
        
        .form-control {
            border: 2px solid var(--cream);
            border-radius: 12px;
            padding: 14px 20px;
            font-size: 1rem;
            color: var(--chocolate);
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            border-color: var(--golden);
            box-shadow: 0 0 0 0.3rem rgba(218, 165, 32, 0.2);
            transform: translateY(-2px);
        }
        
        .form-control::placeholder {
            color: #aaa;
            font-style: italic;
        }
        
        /* Password Strength */
        .password-strength {
            height: 8px;
            margin-top: 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
            background: #e0e0e0;
            overflow: hidden;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }
        
        .strength-0 { background: #dc3545; width: 20%; }
        .strength-1 { background: #ff6b6b; width: 40%; }
        .strength-2 { background: #ffc107; width: 60%; }
        .strength-3 { background: #20c997; width: 80%; }
        .strength-4 { background: var(--success-green); width: 100%; }
        
        .strength-text {
            font-size: 0.85rem;
            margin-top: 5px;
            text-align: right;
            font-weight: 600;
        }
        
        /* Password Requirements */
        .requirements {
            background: var(--cream);
            border-radius: 10px;
            padding: 15px;
            margin-top: 10px;
            border: 1px solid rgba(139, 69, 19, 0.1);
        }
        
        .requirement-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .requirement-item.valid {
            color: var(--success-green);
        }
        
        .requirement-item.invalid {
            color: #6c757d;
        }
        
        /* Terms and Conditions */
        .terms-card {
            background: linear-gradient(135deg, var(--cream) 0%, #f8f4e6 100%);
            border-radius: 15px;
            padding: 20px;
            border: 2px solid var(--pastry-pink);
            margin-bottom: 25px;
        }
        
        .terms-check {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }
        
        .terms-check input[type="checkbox"] {
            margin-top: 5px;
            accent-color: var(--bakery-brown);
            transform: scale(1.3);
        }
        
        .terms-label {
            color: var(--chocolate);
            line-height: 1.5;
        }
        
        /* Buttons */
        .btn-register {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--bakery-brown) 100%);
            color: white;
            border: none;
            padding: 16px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            width: 100%;
        }
        
        .btn-register:hover:not(:disabled) {
            transform: translateY(-5px);
            box-shadow: 0 12px 35px rgba(139, 69, 19, 0.4);
        }
        
        .btn-register:active {
            transform: translateY(-2px);
        }
        
        .btn-register:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .btn-login {
            background: white;
            color: var(--bakery-brown);
            border: 2px solid var(--bakery-brown);
            padding: 16px 40px;
            font-size: 1.2rem;
            font-weight: 600;
            border-radius: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            width: 100%;
            text-decoration: none;
        }
        
        .btn-login:hover {
            background: var(--bakery-brown);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Success Message */
        .success-card {
            background: linear-gradient(135deg, var(--success-green) 0%, #4CAF50 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            animation: successPop 0.6s ease;
        }
        
        @keyframes successPop {
            0% {
                opacity: 0;
                transform: scale(0.8);
            }
            70% {
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .success-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            animation: bounce 1s ease infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        /* Error Message */
        .error-card {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        /* Features Grid */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
        }
        
        .feature-item {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 15px;
            border: 2px solid var(--cream);
            transition: all 0.3s ease;
        }
        
        .feature-item:hover {
            transform: translateY(-5px);
            border-color: var(--golden);
            box-shadow: 0 10px 25px rgba(139, 69, 19, 0.1);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--bakery-brown);
            margin-bottom: 15px;
        }
        
        /* Footer */
        .register-footer {
            text-align: center;
            padding: 25px;
            color: var(--chocolate);
            opacity: 0.7;
            border-top: 1px solid var(--cream);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .register-header {
                padding: 40px 20px;
            }
            
            .register-title {
                font-size: 2.2rem;
            }
            
            .register-body {
                padding: 30px 20px;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .btn-register, .btn-login {
                padding: 14px 20px;
                font-size: 1.1rem;
            }
        }
        
        /* Input with icon */
        .input-with-icon {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-brown);
            cursor: pointer;
        }
        
        /* Loading animation */
        .btn-loading .spinner-border {
            width: 1.3rem;
            height: 1.3rem;
            border-width: 0.2em;
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="register-bg">
        <?php for($i=0; $i<25; $i++): ?>
        <div class="floating-ingredient" style="
            top: <?php echo rand(5, 95); ?>%;
            left: <?php echo rand(5, 95); ?>%;
            font-size: <?php echo rand(20, 45); ?>px;
            animation-delay: -<?php echo rand(0, 25); ?>s;
            animation-duration: <?php echo rand(20, 40); ?>s;
        "><?php echo ['🥖', '🥐', '🍞', '🥨', '🍰', '🧁', '🍪', '🥮', '☕', '🥛', '🥚', '🧈', '🍯'][rand(0,12)]; ?></div>
        <?php endfor; ?>
    </div>

    <div class="register-container">
        <div class="register-card">
            <!-- Header -->
            <div class="register-header">
                <h1 class="register-title">
                    <i class="fas fa-bread-slice"></i>
                    Join Our Bakery Family
                </h1>
                <p class="register-subtitle">
                    Create your account and start managing your bakery with our deliciously simple POS system
                </p>
            </div>
            
            <!-- Body -->
            <div class="register-body">
                <?php if ($success): ?>
                    <!-- Success Message -->
                    <div class="success-card">
                        <div class="success-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="mb-3">Welcome to the Family! 🎉</h3>
                        <p class="mb-4">
                            Your account has been created successfully. You're now ready to start 
                            managing your bakery with our powerful POS system.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="login.php" class="btn btn-light btn-lg w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Proceed to Login
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="index.php" class="btn btn-outline-light btn-lg w-100">
                                    <i class="fas fa-home me-2"></i>Back to Home
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Error Message -->
                    <?php if (!empty($errors)): ?>
                        <div class="error-card">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                                <h4 class="mb-0">Please fix the following:</h4>
                            </div>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="registerForm" novalidate>
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCSRFToken(); ?>">
                        
                        <!-- Personal Information Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-user-circle me-2"></i>Personal Information
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="full_name" class="form-label required">
                                            <i class="fas fa-user"></i>Full Name
                                        </label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="full_name" 
                                               name="full_name" 
                                               required 
                                               placeholder="John Baker"
                                               value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                        <div class="invalid-feedback">Please enter your full name</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label required">
                                            <i class="fas fa-envelope"></i>Email Address
                                        </label>
                                        <input type="email" 
                                               class="form-control" 
                                               id="email" 
                                               name="email" 
                                               required 
                                               placeholder="john@bakery.com"
                                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                        <div class="invalid-feedback">Please enter a valid email address</div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone" class="form-label">
                                            <i class="fas fa-phone"></i>Phone Number
                                        </label>
                                        <input type="tel" 
                                               class="form-control" 
                                               id="phone" 
                                               name="phone" 
                                               placeholder="(123) 456-7890"
                                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Account Information Section -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-key me-2"></i>Account Details
                            </h3>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="username" class="form-label required">
                                            <i class="fas fa-user-tag"></i>Username
                                        </label>
                                        <div class="input-with-icon">
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="username" 
                                                   name="username" 
                                                   required 
                                                   minlength="3" 
                                                   maxlength="50"
                                                   placeholder="john.baker"
                                                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                            <span class="input-icon" id="usernameCheck">
                                                <i class="fas fa-question-circle"></i>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="usernameFeedback">
                                            Username must be 3-50 characters
                                        </div>
                                        <small class="form-text text-muted">
                                            Letters, numbers, dots and underscores only
                                        </small>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password" class="form-label required">
                                            <i class="fas fa-lock"></i>Password
                                        </label>
                                        <div class="input-with-icon">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="password" 
                                                   name="password" 
                                                   required 
                                                   minlength="6"
                                                   placeholder="Create a strong password">
                                            <span class="input-icon" id="togglePassword">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                        
                                        <!-- Password Strength -->
                                        <div class="password-strength mt-3">
                                            <div class="strength-bar" id="passwordStrength"></div>
                                        </div>
                                        <div class="strength-text" id="strengthText">Very Weak</div>
                                        
                                        <!-- Password Requirements -->
                                        <div class="requirements">
                                            <div class="requirement-item invalid" id="reqLength">
                                                <i class="fas fa-circle"></i>
                                                <span>At least 8 characters</span>
                                            </div>
                                            <div class="requirement-item invalid" id="reqUppercase">
                                                <i class="fas fa-circle"></i>
                                                <span>Contains uppercase letter</span>
                                            </div>
                                            <div class="requirement-item invalid" id="reqLowercase">
                                                <i class="fas fa-circle"></i>
                                                <span>Contains lowercase letter</span>
                                            </div>
                                            <div class="requirement-item invalid" id="reqNumber">
                                                <i class="fas fa-circle"></i>
                                                <span>Contains number</span>
                                            </div>
                                            <div class="requirement-item invalid" id="reqSpecial">
                                                <i class="fas fa-circle"></i>
                                                <span>Contains special character</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="confirm_password" class="form-label required">
                                            <i class="fas fa-lock"></i>Confirm Password
                                        </label>
                                        <div class="input-with-icon">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="confirm_password" 
                                                   name="confirm_password" 
                                                   required 
                                                   placeholder="Confirm your password">
                                            <span class="input-icon" id="toggleConfirmPassword">
                                                <i class="fas fa-eye"></i>
                                            </span>
                                        </div>
                                        <div class="invalid-feedback" id="confirmPasswordFeedback">
                                            Passwords do not match
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Terms and Conditions -->
                        <div class="form-section">
                            <div class="terms-card">
                                <div class="terms-check">
                                    <input type="checkbox" id="terms" name="terms" required>
                                    <label class="terms-label" for="terms">
                                        <strong>I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal" class="text-decoration-none">Terms and Conditions</a> *</strong><br>
                                        <small>By creating an account, you agree to our terms of service and privacy policy.</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="form-section">
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <button type="submit" class="btn btn-register" id="registerButton">
                                        <i class="fas fa-user-plus"></i>Create Account
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <a href="login.php" class="btn btn-login">
                                        <i class="fas fa-sign-in-alt"></i>Already have an account?
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Features -->
                    <div class="features-grid">
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-cash-register"></i>
                            </div>
                            <h6>Smart POS</h6>
                            <small>Fast & efficient checkout system</small>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h6>Analytics</h6>
                            <small>Track sales & performance</small>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <h6>Inventory</h6>
                            <small>Real-time stock management</small>
                        </div>
                        <div class="feature-item">
                            <div class="feature-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <h6>Team Management</h6>
                            <small>Multiple staff accounts</small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Footer -->
            <div class="register-footer">
                <small>
                    &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Baking success since 2023.
                </small>
            </div>
        </div>
    </div>
    
    <!-- Terms and Conditions Modal -->
    <div class="modal fade modal-bakery" id="termsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-contract me-2"></i>Terms & Conditions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-bakery">Bakery POS System Agreement</h6>
                        <p>By registering for our bakery management system, you agree to the following terms:</p>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6><i class="fas fa-shield-alt me-2 text-success"></i>Data Security</h6>
                                <p>We protect your bakery data with enterprise-grade security measures.</p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-sync-alt me-2 text-info"></i>System Updates</h6>
                                <p>Regular updates ensure you always have the latest features and security.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <h6><i class="fas fa-headset me-2 text-primary"></i>24/7 Support</h6>
                                <p>Our bakery experts are available round-the-clock to assist you.</p>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-cloud-upload-alt me-2 text-warning"></i>Data Backup</h6>
                                <p>Automatic daily backups ensure your bakery data is always safe.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This is a demonstration system. For production use, please review the complete terms of service.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            toggleConfirmPassword.addEventListener('click', function() {
                const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
                confirmPasswordInput.type = type;
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
            
            // Password strength checker
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('passwordStrength');
                const strengthText = document.getElementById('strengthText');
                
                let strength = 0;
                
                // Check requirements
                const reqLength = document.getElementById('reqLength');
                const reqUppercase = document.getElementById('reqUppercase');
                const reqLowercase = document.getElementById('reqLowercase');
                const reqNumber = document.getElementById('reqNumber');
                const reqSpecial = document.getElementById('reqSpecial');
                
                // Length check
                if (password.length >= 8) {
                    strength++;
                    reqLength.classList.remove('invalid');
                    reqLength.classList.add('valid');
                    reqLength.innerHTML = '<i class="fas fa-check-circle"></i><span>At least 8 characters ✓</span>';
                } else {
                    reqLength.classList.remove('valid');
                    reqLength.classList.add('invalid');
                    reqLength.innerHTML = '<i class="fas fa-circle"></i><span>At least 8 characters</span>';
                }
                
                // Uppercase check
                if (/[A-Z]/.test(password)) {
                    strength++;
                    reqUppercase.classList.remove('invalid');
                    reqUppercase.classList.add('valid');
                    reqUppercase.innerHTML = '<i class="fas fa-check-circle"></i><span>Contains uppercase letter ✓</span>';
                } else {
                    reqUppercase.classList.remove('valid');
                    reqUppercase.classList.add('invalid');
                    reqUppercase.innerHTML = '<i class="fas fa-circle"></i><span>Contains uppercase letter</span>';
                }
                
                // Lowercase check
                if (/[a-z]/.test(password)) {
                    strength++;
                    reqLowercase.classList.remove('invalid');
                    reqLowercase.classList.add('valid');
                    reqLowercase.innerHTML = '<i class="fas fa-check-circle"></i><span>Contains lowercase letter ✓</span>';
                } else {
                    reqLowercase.classList.remove('valid');
                    reqLowercase.classList.add('invalid');
                    reqLowercase.innerHTML = '<i class="fas fa-circle"></i><span>Contains lowercase letter</span>';
                }
                
                // Number check
                if (/[0-9]/.test(password)) {
                    strength++;
                    reqNumber.classList.remove('invalid');
                    reqNumber.classList.add('valid');
                    reqNumber.innerHTML = '<i class="fas fa-check-circle"></i><span>Contains number ✓</span>';
                } else {
                    reqNumber.classList.remove('valid');
                    reqNumber.classList.add('invalid');
                    reqNumber.innerHTML = '<i class="fas fa-circle"></i><span>Contains number</span>';
                }
                
                // Special character check
                if (/[^A-Za-z0-9]/.test(password)) {
                    strength++;
                    reqSpecial.classList.remove('invalid');
                    reqSpecial.classList.add('valid');
                    reqSpecial.innerHTML = '<i class="fas fa-check-circle"></i><span>Contains special character ✓</span>';
                } else {
                    reqSpecial.classList.remove('valid');
                    reqSpecial.classList.add('invalid');
                    reqSpecial.innerHTML = '<i class="fas fa-circle"></i><span>Contains special character</span>';
                }
                
                // Update strength bar
                strengthBar.className = 'strength-bar strength-' + Math.min(strength, 4);
                
                // Update strength text
                const strengthLabels = ['Very Weak', 'Weak', 'Fair', 'Good', 'Strong'];
                strengthText.textContent = strengthLabels[Math.min(strength, 4)];
                strengthText.style.color = ['#dc3545', '#ff6b6b', '#ffc107', '#20c997', '#28a745'][Math.min(strength, 4)];
            });
            
            // Password confirmation check
            confirmPasswordInput.addEventListener('input', function() {
                const password = passwordInput.value;
                const confirmPassword = this.value;
                const feedback = document.getElementById('confirmPasswordFeedback');
                
                if (password !== confirmPassword && confirmPassword.length > 0) {
                    this.classList.add('is-invalid');
                    feedback.style.display = 'block';
                } else {
                    this.classList.remove('is-invalid');
                    feedback.style.display = 'none';
                }
            });
            
            // Form validation
            const form = document.getElementById('registerForm');
            form.addEventListener('submit', function(event) {
                event.preventDefault();
                
                // Check if form is valid
                if (!form.checkValidity()) {
                    event.stopPropagation();
                    form.classList.add('was-validated');
                    return;
                }
                
                // Check password match
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                if (password !== confirmPassword) {
                    confirmPasswordInput.classList.add('is-invalid');
                    document.getElementById('confirmPasswordFeedback').style.display = 'block';
                    return;
                }
                
                // Disable button and show loading
                const button = document.getElementById('registerButton');
                const originalText = button.innerHTML;
                button.innerHTML = `
                    <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                    Creating Account...
                `;
                button.disabled = true;
                button.classList.add('btn-loading');
                
                // Submit form after short delay for animation
                setTimeout(() => {
                    form.submit();
                }, 1000);
            });
            
            // Phone number formatting
            const phoneInput = document.getElementById('phone');
            if (phoneInput) {
                phoneInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 3 && value.length <= 6) {
                        value = value.replace(/(\d{3})(\d+)/, '($1) $2');
                    } else if (value.length > 6) {
                        value = value.replace(/(\d{3})(\d{3})(\d+)/, '($1) $2-$3');
                    }
                    e.target.value = value.substring(0, 14);
                });
            }
            
            // Username availability check (simulated)
            const usernameInput = document.getElementById('username');
            const usernameFeedback = document.getElementById('usernameFeedback');
            const usernameCheck = document.getElementById('usernameCheck');
            
            if (usernameInput) {
                usernameInput.addEventListener('input', function() {
                    const username = this.value.trim();
                    
                    // Check username format
                    if (!/^[a-zA-Z0-9._]{3,50}$/.test(username)) {
                        this.classList.add('is-invalid');
                        usernameFeedback.textContent = 'Only letters, numbers, dots and underscores (3-50 chars)';
                        usernameCheck.innerHTML = '<i class="fas fa-times text-danger"></i>';
                        return;
                    }
                    
                    // Simulate AJAX check
                    setTimeout(() => {
                        // Demo check - in real app, this would be an AJAX call
                        const takenUsernames = ['admin', 'cashier', 'manager'];
                        if (takenUsernames.includes(username.toLowerCase())) {
                            this.classList.add('is-invalid');
                            this.classList.remove('is-valid');
                            usernameFeedback.textContent = 'Username already taken';
                            usernameCheck.innerHTML = '<i class="fas fa-times text-danger"></i>';
                        } else if (username.length >= 3) {
                            this.classList.remove('is-invalid');
                            this.classList.add('is-valid');
                            usernameFeedback.textContent = 'Username available';
                            usernameFeedback.style.display = 'none';
                            usernameCheck.innerHTML = '<i class="fas fa-check text-success"></i>';
                        }
                    }, 500);
                });
            }
            
            // Add some CSS for validation states
            const style = document.createElement('style');
            style.textContent = `
                .form-control.is-valid {
                    border-color: var(--success-green) !important;
                    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
                }
                
                .form-control.is-invalid {
                    border-color: #dc3545 !important;
                }
                
                .was-validated .form-control:invalid {
                    border-color: #dc3545 !important;
                }
                
                .was-validated .form-control:valid {
                    border-color: var(--success-green) !important;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>