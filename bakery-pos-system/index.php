<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'];
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
    <title>Welcome - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
            border-radius: 0 0 30px 30px;
            margin-bottom: 50px;
        }
        .feature-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
        .feature-icon {
            width: 70px;
            height: 70px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin: 0 auto 20px;
        }
        .admin-access {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-top: 30px;
        }
        .login-buttons .btn {
            min-width: 180px;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 10px;
        }
        .system-highlights {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 40px;
            margin: 40px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-bread-slice"></i> <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav">
                <a href="login.php" class="nav-link">Login</a>
                <a href="register.php" class="nav-link">Register</a>
                <a href="admin/login.php" class="nav-link text-warning">
                    <i class="fas fa-user-shield"></i> Admin
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-3 fw-bold mb-4">Bakery Point of Sale System</h1>
            <p class="lead mb-5">Complete bakery management solution with inventory control, sales tracking, and reporting</p>
            
            <div class="login-buttons">
                <a href="login.php" class="btn btn-light btn-lg me-3">
                    <i class="fas fa-sign-in-alt me-2"></i>Staff Login
                </a>
                <a href="admin/login.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-user-shield me-2"></i>Admin Login
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Admin Access Section -->
        <div class="admin-access text-center">
            <h3><i class="fas fa-shield-alt me-2"></i>Administrator Access</h3>
            <p class="mb-4">For system administrators and management staff</p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card bg-white text-dark">
                        <div class="card-body">
                            <h5 class="card-title">Admin Portal Features</h5>
                            <div class="row text-start mt-4">
                                <div class="col-md-6">
                                    <p><i class="fas fa-check-circle text-success me-2"></i>User Management</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i>Sales Analytics</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i>Inventory Control</p>
                                </div>
                                <div class="col-md-6">
                                    <p><i class="fas fa-check-circle text-success me-2"></i>System Settings</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i>Database Backup</p>
                                    <p><i class="fas fa-check-circle text-success me-2"></i>Reporting Tools</p>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="admin/login.php" class="btn btn-danger btn-lg">
                                    <i class="fas fa-lock me-2"></i>Access Admin Portal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="row mt-5 mb-5">
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-primary text-white">
                            <i class="fas fa-cash-register"></i>
                        </div>
                        <h4 class="card-title">Point of Sale</h4>
                        <p class="card-text">Fast and efficient checkout system with barcode scanning, multiple payment methods, and receipt printing.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-success text-white">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <h4 class="card-title">Inventory Management</h4>
                        <p class="card-text">Real-time stock tracking, low stock alerts, and supplier management to keep your bakery well-stocked.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="card feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon bg-info text-white">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h4 class="card-title">Sales Analytics</h4>
                        <p class="card-text">Comprehensive reports and analytics to track performance, identify trends, and make data-driven decisions.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Highlights -->
        <div class="system-highlights">
            <h2 class="text-center mb-4">Why Choose Our System?</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <h5><i class="fas fa-bolt text-warning me-2"></i>Fast & Efficient</h5>
                        <p>Streamlined workflows and quick transactions to serve customers faster.</p>
                    </div>
                    <div class="mb-4">
                        <h5><i class="fas fa-mobile-alt text-primary me-2"></i>Mobile Friendly</h5>
                        <p>Responsive design that works perfectly on tablets, phones, and desktops.</p>
                    </div>
                    <div>
                        <h5><i class="fas fa-shield-alt text-success me-2"></i>Secure</h5>
                        <p>Role-based access control, data encryption, and secure authentication.</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <h5><i class="fas fa-sync text-info me-2"></i>Real-time Updates</h5>
                        <p>Instant stock updates and real-time sales tracking across all devices.</p>
                    </div>
                    <div class="mb-4">
                        <h5><i class="fas fa-file-invoice-dollar text-danger me-2"></i>Professional Invoicing</h5>
                        <p>Customizable invoices with company branding and multiple print options.</p>
                    </div>
                    <div>
                        <h5><i class="fas fa-headset text-secondary me-2"></i>24/7 Support</h5>
                        <p>Round-the-clock technical support and regular system updates.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Access -->
        <div class="row mt-5">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Quick Access</h4>
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <div class="d-grid">
                                    <a href="login.php" class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-user me-2"></i>Staff/Cashier Login
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-grid">
                                    <a href="admin/login.php" class="btn btn-outline-warning btn-lg">
                                        <i class="fas fa-user-shield me-2"></i>Administrator Login
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-grid">
                                    <a href="register.php" class="btn btn-outline-success btn-lg">
                                        <i class="fas fa-user-plus me-2"></i>Register New Account
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="d-grid">
                                    <a href="#" class="btn btn-outline-info btn-lg" data-bs-toggle="modal" data-bs-target="#demoModal">
                                        <i class="fas fa-play-circle me-2"></i>View Demo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-dark text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-bread-slice fa-4x mb-3"></i>
                        <h5>Ready to Get Started?</h5>
                        <p class="mb-0">Experience the power of modern bakery management</p>
                        <hr class="bg-light">
                        <div class="mt-3">
                            <a href="login.php" class="btn btn-light me-2">Login</a>
                            <a href="admin/login.php" class="btn btn-warning">Admin</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="fas fa-bread-slice"></i> <?php echo SITE_NAME; ?></h5>
                    <p class="mb-0">Complete bakery management solution</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                        <li><a href="login.php" class="text-white-50 text-decoration-none">Staff Login</a></li>
                        <li><a href="admin/login.php" class="text-warning text-decoration-none">Admin Login</a></li>
                        <li><a href="register.php" class="text-white-50 text-decoration-none">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p class="mb-1"><i class="fas fa-phone me-2"></i> +1 (123) 456-7890</p>
                    <p class="mb-1"><i class="fas fa-envelope me-2"></i> info@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com</p>
                    <p class="mb-0"><i class="fas fa-clock me-2"></i> Support: 24/7</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                <small class="text-muted">Version 2.0.1 | For demonstration purposes only</small>
            </div>
        </div>
    </footer>

    <!-- Demo Modal -->
    <div class="modal fade" id="demoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">System Demo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                   <?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$auth = new Auth();

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    $role = $_SESSION['role'];
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
    <title>Welcome - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts for bakery vibe -->
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bakery-brown: #8B4513;
            --warm-brown: #A0522D;
            --light-brown: #D2691E;
            --cream: #FFF8DC;
            --golden: #DAA520;
            --pastry-pink: #FFE4E1;
            --bread-color: #E6C9A8;
            --chocolate: #5D4037;
            --success-green: #2E7D32;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #f9f5f0 0%, #fff8f0 100%);
            color: var(--chocolate);
            overflow-x: hidden;
        }
        
        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
            overflow: hidden;
        }
        
        .floating-icon {
            position: absolute;
            font-size: 24px;
            opacity: 0.3;
            animation: float 15s infinite linear;
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
        
        /* Navigation */
        .navbar-bakery {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(139, 69, 19, 0.1);
            padding: 15px 0;
            transition: all 0.3s ease;
        }
        
        .navbar-bakery.scrolled {
            padding: 10px 0;
            background: rgba(255, 255, 255, 0.98);
        }
        
        .navbar-brand-bakery {
            font-family: 'Pacifico', cursive;
            font-size: 2rem;
            color: var(--bakery-brown) !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar-brand-bakery i {
            color: var(--golden);
            font-size: 1.8rem;
        }
        
        .nav-link-bakery {
            color: var(--chocolate) !important;
            font-weight: 500;
            margin: 0 10px;
            padding: 8px 20px !important;
            border-radius: 25px;
            transition: all 0.3s ease;
        }
        
        .nav-link-bakery:hover {
            background: var(--pastry-pink);
            color: var(--bakery-brown) !important;
            transform: translateY(-2px);
        }
        
        .btn-bakery-primary {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--bakery-brown) 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(139, 69, 19, 0.2);
        }
        
        .btn-bakery-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(139, 69, 19, 0.3);
            color: white;
        }
        
        /* Hero Section */
        .hero-section {
            position: relative;
            padding: 150px 0 100px;
            background: linear-gradient(135deg, rgba(139, 69, 19, 0.9) 0%, rgba(160, 82, 45, 0.9) 100%);
            color: white;
            overflow: hidden;
            border-radius: 0 0 50px 50px;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('https://images.unsplash.com/photo-1509440159596-0249088772ff?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80') center/cover;
            opacity: 0.3;
            z-index: -1;
        }
        
        .hero-title {
            font-family: 'Pacifico', cursive;
            font-size: 4rem;
            margin-bottom: 20px;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
            animation: fadeInUp 1s ease;
        }
        
        .hero-subtitle {
            font-family: 'Dancing Script', cursive;
            font-size: 2rem;
            margin-bottom: 30px;
            color: var(--golden);
            animation: fadeInUp 1s ease 0.2s both;
        }
        
        .hero-description {
            font-size: 1.2rem;
            max-width: 600px;
            margin: 0 auto 40px;
            animation: fadeInUp 1s ease 0.4s both;
        }
        
        /* Feature Cards */
        .feature-card {
            border: none;
            border-radius: 20px;
            overflow: hidden;
            transition: all 0.4s ease;
            background: white;
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.1);
            height: 100%;
            position: relative;
            z-index: 1;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--golden), var(--light-brown));
            z-index: 2;
        }
        
        .feature-card:hover {
            transform: translateY(-15px) scale(1.02);
            box-shadow: 0 20px 40px rgba(139, 69, 19, 0.2);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, var(--cream) 0%, var(--pastry-pink) 100%);
            color: var(--bakery-brown);
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.15);
        }
        
        .feature-title {
            font-family: 'Dancing Script', cursive;
            font-size: 1.8rem;
            color: var(--bakery-brown);
            margin-bottom: 15px;
        }
        
        /* Admin Access Section */
        .admin-access {
            background: linear-gradient(135deg, var(--chocolate) 0%, #3e2723 100%);
            color: white;
            border-radius: 30px;
            padding: 50px;
            margin: 80px auto;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }
        
        .admin-access::before {
            content: '🔒';
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 100px;
            opacity: 0.1;
        }
        
        .admin-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        /* System Highlights */
        .system-highlights {
            background: var(--cream);
            border-radius: 30px;
            padding: 60px 40px;
            margin: 60px 0;
            position: relative;
            overflow: hidden;
        }
        
        .system-highlights::before {
            content: '🥐🍞🥖🍰';
            position: absolute;
            top: 10px;
            left: 0;
            right: 0;
            font-size: 60px;
            text-align: center;
            opacity: 0.1;
            animation: float 20s infinite linear;
        }
        
        .highlight-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 25px;
            padding: 20px;
            background: white;
            border-radius: 15px;
            transition: all 0.3s ease;
        }
        
        .highlight-item:hover {
            transform: translateX(10px);
            box-shadow: 0 5px 20px rgba(139, 69, 19, 0.1);
        }
        
        .highlight-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--bakery-brown) 100%);
            color: white;
        }
        
        /* Quick Access */
        .quick-access {
            margin: 80px 0;
        }
        
        .access-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            height: 100%;
            border: 2px solid var(--cream);
            transition: all 0.3s ease;
        }
        
        .access-card:hover {
            border-color: var(--golden);
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(139, 69, 19, 0.15);
        }
        
        .access-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            padding: 15px 25px;
            border-radius: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .access-btn-staff {
            background: linear-gradient(135deg, var(--cream) 0%, var(--pastry-pink) 100%);
            color: var(--bakery-brown);
            border-color: var(--light-brown);
        }
        
        .access-btn-staff:hover {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--bakery-brown) 100%);
            color: white;
            transform: translateY(-3px);
        }
        
        .access-btn-admin {
            background: linear-gradient(135deg, var(--golden) 0%, #ffcc33 100%);
            color: var(--chocolate);
            border-color: var(--golden);
        }
        
        .access-btn-admin:hover {
            background: linear-gradient(135deg, var(--chocolate) 0%, #3e2723 100%);
            color: white;
            transform: translateY(-3px);
        }
        
        /* Footer */
        .footer-bakery {
            background: linear-gradient(135deg, var(--chocolate) 0%, #3e2723 100%);
            color: white;
            padding: 60px 0 30px;
            margin-top: 100px;
            border-radius: 50px 50px 0 0;
        }
        
        .footer-logo {
            font-family: 'Pacifico', cursive;
            font-size: 2.5rem;
            color: var(--golden);
            margin-bottom: 20px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            display: block;
            margin-bottom: 10px;
        }
        
        .footer-links a:hover {
            color: var(--golden);
            transform: translateX(5px);
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }
        
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }
            
            .hero-subtitle {
                font-size: 1.5rem;
            }
            
            .admin-access {
                padding: 30px 20px;
            }
            
            .feature-card {
                margin-bottom: 30px;
            }
        }
        
        /* Demo Modal */
        .modal-bakery .modal-content {
            border-radius: 20px;
            overflow: hidden;
            border: 3px solid var(--golden);
        }
        
        .modal-bakery .modal-header {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--bakery-brown) 100%);
            color: white;
        }
        
        /* Bakery Stats */
        .stats-counter {
            background: linear-gradient(135deg, var(--cream) 0%, white 100%);
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            border: 2px solid var(--golden);
        }
        
        .counter-number {
            font-family: 'Dancing Script', cursive;
            font-size: 3.5rem;
            color: var(--bakery-brown);
            font-weight: 700;
        }
        
        .counter-label {
            color: var(--light-brown);
            font-weight: 500;
        }
        
        /* Testimonial */
        .testimonial-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            position: relative;
            border: 1px solid var(--cream);
        }
        
        .testimonial-card::before {
            content: '"';
            position: absolute;
            top: 20px;
            left: 20px;
            font-family: 'Pacifico', cursive;
            font-size: 4rem;
            color: var(--golden);
            opacity: 0.2;
        }
        
        .customer-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--golden);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="animated-bg">
        <?php for($i=0; $i<20; $i++): ?>
        <div class="floating-icon" style="
            top: <?php echo rand(5, 95); ?>%;
            left: <?php echo rand(5, 95); ?>%;
            font-size: <?php echo rand(20, 40); ?>px;
            animation-delay: -<?php echo rand(0, 15); ?>s;
            animation-duration: <?php echo rand(10, 25); ?>s;
        "><?php echo ['🍞', '🥐', '🥖', '🥨', '🍰', '🧁', '🍪', '🥮'][rand(0,7)]; ?></div>
        <?php endfor; ?>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-bakery fixed-top">
        <div class="container">
            <a class="navbar-brand navbar-brand-bakery" href="#">
                <i class="fas fa-bread-slice"></i>
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto align-items-center">
                    <a class="nav-link nav-link-bakery" href="#features">Features</a>
                    <a class="nav-link nav-link-bakery" href="#about">About</a>
                    <a class="nav-link nav-link-bakery" href="#contact">Contact</a>
                    <div class="d-flex gap-3 ms-4">
                        <a href="login.php" class="btn btn-bakery-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Staff Login
                        </a>
                        <a href="admin/login.php" class="btn btn-outline-warning">
                            <i class="fas fa-user-shield me-2"></i>Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="hero-title">Fresh from Our Oven to Your Business</h1>
                    <p class="hero-subtitle">Bakery Management Made Sweet & Simple</p>
                    <p class="hero-description">
                        Transform your bakery operations with our complete POS solution. 
                        Manage sales, inventory, and customers all in one beautiful system.
                    </p>
                    
                    <div class="d-flex flex-wrap justify-content-center gap-4 mt-4">
                        <a href="#demo" class="btn btn-bakery-primary btn-lg" data-bs-toggle="modal" data-bs-target="#demoModal">
                            <i class="fas fa-play-circle me-2"></i>Live Demo
                        </a>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus me-2"></i>Get Started Free
                        </a>
                    </div>
                    
                    <!-- Bakery Stats -->
                    <div class="row mt-5">
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stats-counter">
                                <div class="counter-number" data-count="500">0</div>
                                <div class="counter-label">Bakeries Served</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stats-counter">
                                <div class="counter-number" data-count="99">0</div>
                                <div class="counter-label">% Uptime</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stats-counter">
                                <div class="counter-number" data-count="24">0</div>
                                <div class="counter-label">/7 Support</div>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-4">
                            <div class="stats-counter">
                                <div class="counter-number" data-count="5">0</div>
                                <div class="counter-label">Star Rating</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="container py-5 mt-5" id="features">
        <h2 class="text-center mb-5" style="font-family: 'Pacifico', cursive; color: var(--bakery-brown);">
            Why Bakeries Love Our System
        </h2>
        
        <div class="row">
            <div class="col-lg-4 col-md-6 mb-4 animate-on-scroll">
                <div class="feature-card p-4">
                    <div class="feature-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <h3 class="feature-title">Smart POS</h3>
                    <p class="text-muted">
                        Lightning-fast checkout with barcode scanning, multiple payment options, 
                        and beautiful receipts that match your bakery's style.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4 animate-on-scroll">
                <div class="feature-card p-4">
                    <div class="feature-icon">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <h3 class="feature-title">Inventory Magic</h3>
                    <p class="text-muted">
                        Never run out of flour again! Real-time stock alerts, 
                        supplier management, and batch tracking for perfect inventory control.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4 animate-on-scroll">
                <div class="feature-card p-4">
                    <div class="feature-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3 class="feature-title">Sweet Analytics</h3>
                    <p class="text-muted">
                        Discover which pastries are your top sellers, track daily revenue, 
                        and make data-driven decisions to grow your bakery.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4 animate-on-scroll">
                <div class="feature-card p-4">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Loyal Customers</h3>
                    <p class="text-muted">
                        Build lasting relationships with customer profiles, loyalty programs, 
                        and personalized promotions that keep them coming back.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4 animate-on-scroll">
                <div class="feature-card p-4">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3 class="feature-title">Anywhere Access</h3>
                    <p class="text-muted">
                        Run your bakery from anywhere! Cloud-based system works perfectly 
                        on tablets, phones, and computers.
                    </p>
                </div>
            </div>
            
            <div class="col-lg-4 col-md-6 mb-4 animate-on-scroll">
                <div class="feature-card p-4">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h3 class="feature-title">24/7 Support</h3>
                    <p class="text-muted">
                        Our bakery experts are always here to help with setup, training, 
                        and any questions you might have along the way.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Admin Access -->
    <section class="container" id="about">
        <div class="admin-access animate-on-scroll">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h2 class="display-5 mb-4">
                        <i class="fas fa-shield-alt me-3"></i>
                        Secure Admin Dashboard
                    </h2>
                    <p class="lead mb-4">
                        Complete control over your bakery operations with our powerful admin panel. 
                        Manage everything from employee accounts to financial reports.
                    </p>
                    
                    <div class="row mt-4">
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center text-white">
                                <i class="fas fa-check-circle fa-2x me-3" style="color: var(--golden);"></i>
                                <div>
                                    <h5 class="mb-1">User Management</h5>
                                    <p class="mb-0">Create and manage staff accounts with role-based permissions</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center text-white">
                                <i class="fas fa-check-circle fa-2x me-3" style="color: var(--golden);"></i>
                                <div>
                                    <h5 class="mb-1">Advanced Reporting</h5>
                                    <p class="mb-0">Detailed analytics and custom reports for business insights</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4 mt-4 mt-lg-0">
                    <div class="admin-card animate-on-scroll" data-delay="200">
                        <h4 class="text-center mb-4" style="color: var(--bakery-brown);">
                            <i class="fas fa-user-shield me-2"></i>Admin Access
                        </h4>
                        <div class="d-grid gap-3">
                            <a href="admin/login.php" class="access-btn access-btn-admin">
                                <i class="fas fa-lock"></i>
                                <span>Admin Login</span>
                            </a>
                            <a href="login.php" class="access-btn access-btn-staff">
                                <i class="fas fa-user-tie"></i>
                                <span>Staff Login</span>
                            </a>
                            <a href="register.php" class="access-btn" style="background: var(--success-green); color: white;">
                                <i class="fas fa-user-plus"></i>
                                <span>New Account</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Highlights -->
    <section class="container system-highlights animate-on-scroll" id="contact">
        <h2 class="text-center mb-5" style="font-family: 'Dancing Script', cursive; font-size: 2.5rem; color: var(--bakery-brown);">
            Everything You Need for a Successful Bakery
        </h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div>
                        <h5>Lightning Fast</h5>
                        <p class="mb-0">Process transactions in seconds, not minutes. Keep lines moving during rush hours.</p>
                    </div>
                </div>
                
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-palette"></i>
                    </div>
                    <div>
                        <h5>Custom Branding</h5>
                        <p class="mb-0">Add your bakery logo, colors, and branding to receipts and reports.</p>
                    </div>
                </div>
                
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-sync"></i>
                    </div>
                    <div>
                        <h5>Auto Sync</h5>
                        <p class="mb-0">Real-time updates across all devices. Changes made on one device appear everywhere instantly.</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-print"></i>
                    </div>
                    <div>
                        <h5>Smart Printing</h5>
                        <p class="mb-0">Print receipts, invoices, and kitchen orders with our optimized print system.</p>
                    </div>
                </div>
                
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <div>
                        <h5>Cloud Backup</h5>
                        <p class="mb-0">Automatic daily backups keep your data safe and secure in the cloud.</p>
                    </div>
                </div>
                
                <div class="highlight-item">
                    <div class="highlight-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div>
                        <h5>Easy Training</h5>
                        <p class="mb-0">Intuitive interface means your staff can learn the system in under 30 minutes.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Access -->
    <section class="container quick-access">
        <div class="row animate-on-scroll">
            <div class="col-lg-8 mb-4">
                <div class="access-card">
                    <h3 class="mb-4" style="color: var(--bakery-brown);">
                        <i class="fas fa-rocket me-2"></i>Get Started in Minutes
                    </h3>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="login.php" class="access-btn access-btn-staff">
                                <i class="fas fa-user-tie fa-2x"></i>
                                <div>
                                    <strong>Staff Login</strong>
                                    <small class="d-block">For cashiers and sales staff</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="admin/login.php" class="access-btn access-btn-admin">
                                <i class="fas fa-user-shield fa-2x"></i>
                                <div>
                                    <strong>Admin Login</strong>
                                    <small class="d-block">For managers and owners</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="register.php" class="access-btn" style="background: var(--success-green); color: white;">
                                <i class="fas fa-user-plus fa-2x"></i>
                                <div>
                                    <strong>Register Now</strong>
                                    <small class="d-block">Create new account</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="#demo" class="access-btn" style="background: var(--primary-color); color: white;" data-bs-toggle="modal" data-bs-target="#demoModal">
                                <i class="fas fa-play-circle fa-2x"></i>
                                <div>
                                    <strong>Live Demo</strong>
                                    <small class="d-block">Try it free</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4 mb-4">
                <div class="access-card text-center">
                    <div class="mb-4">
                        <i class="fas fa-bread-slice fa-4x" style="color: var(--golden);"></i>
                    </div>
                    <h4 style="color: var(--bakery-brown);">Ready to Bake Success?</h4>
                    <p class="mb-4">Join thousands of successful bakeries using our system.</p>
                    <div class="d-grid gap-2">
                        <a href="register.php" class="btn btn-bakery-primary">Start Free Trial</a>
                        <a href="tel:+1234567890" class="btn btn-outline-secondary">
                            <i class="fas fa-phone me-2"></i>Call Now
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="container py-5">
        <h2 class="text-center mb-5" style="font-family: 'Pacifico', cursive; color: var(--bakery-brown);">
            What Our Bakers Say
        </h2>
        
        <div class="row animate-on-scroll">
            <div class="col-md-4 mb-4">
                <div class="testimonial-card">
                    <p class="mb-4">
                        "This system transformed our bakery! We've increased efficiency by 40% 
                        and our customers love the professional receipts."
                    </p>
                    <div class="d-flex align-items-center">
                        <img src="https://randomuser.me/api/portraits/women/32.jpg" class="customer-photo me-3">
                        <div>
                            <h6 class="mb-1">Sarah Johnson</h6>
                            <small class="text-muted">Owner, Sweet Treats Bakery</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="testimonial-card">
                    <p class="mb-4">
                        "The inventory management saved us from multiple stock-outs during holiday seasons. 
                        A must-have for any serious bakery!"
                    </p>
                    <div class="d-flex align-items-center">
                        <img src="https://randomuser.me/api/portraits/men/54.jpg" class="customer-photo me-3">
                        <div>
                            <h6 class="mb-1">Michael Chen</h6>
                            <small class="text-muted">Manager, Artisan Bread Co.</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4 mb-4">
                <div class="testimonial-card">
                    <p class="mb-4">
                        "Easy to learn, beautiful to use, and the support team is amazing. 
                        Our cashiers were up and running in just 20 minutes!"
                    </p>
                    <div class="d-flex align-items-center">
                        <img src="https://randomuser.me/api/portraits/women/65.jpg" class="customer-photo me-3">
                        <div>
                            <h6 class="mb-1">Lisa Rodriguez</h6>
                            <small class="text-muted">Owner, Delightful Pastries</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer-bakery">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <div class="footer-logo">
                        <i class="fas fa-bread-slice"></i>
                        <?php echo SITE_NAME; ?>
                    </div>
                    <p class="mb-0">Fresh ideas for your bakery business. Simple, powerful, and deliciously effective.</p>
                    <div class="mt-4">
                        <a href="#" class="btn btn-outline-light me-2">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="btn btn-outline-light me-2">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="btn btn-outline-light me-2">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="btn btn-outline-light">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-lg-2 col-md-4 mb-4">
                    <h5 class="mb-4">Quick Links</h5>
                    <div class="footer-links">
                        <a href="#"><i class="fas fa-chevron-right me-2"></i>Home</a>
                        <a href="#features"><i class="fas fa-chevron-right me-2"></i>Features</a>
                        <a href="#about"><i class="fas fa-chevron-right me-2"></i>About</a>
                        <a href="#contact"><i class="fas fa-chevron-right me-2"></i>Contact</a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="mb-4">Access</h5>
                    <div class="footer-links">
                        <a href="login.php" class="text-warning"><i class="fas fa-sign-in-alt me-2"></i>Staff Login</a>
                        <a href="admin/login.php" class="text-warning"><i class="fas fa-user-shield me-2"></i>Admin Login</a>
                        <a href="register.php"><i class="fas fa-user-plus me-2"></i>Register</a>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#demoModal"><i class="fas fa-play-circle me-2"></i>Live Demo</a>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-4 mb-4">
                    <h5 class="mb-4">Contact Us</h5>
                    <div class="footer-links">
                        <p><i class="fas fa-map-marker-alt me-2"></i>123 Bakery Street, nowshera</p>
                        <p><i class="fas fa-phone me-2"></i>+92 (308) 6367-9041</p>
                        <p><i class="fas fa-envelope me-2"></i>Bakeflow@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com</p>
                        <p><i class="fas fa-clock me-2"></i>Support: 24/7</p>
                    </div>
                </div>
            </div>
            
            <hr class="bg-light my-5">
            
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        Made with <i class="fas fa-heart text-danger"></i> for bakers everywhere
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Demo Modal -->
    <div class="modal fade modal-bakery" id="demoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-play-circle me-2"></i>Live Demo Access
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="mb-3" style="color: var(--bakery-brown);">
                                        <i class="fas fa-key me-2"></i>Demo Credentials
                                    </h6>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Admin Account</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user-shield"></i>
                                            </span>
                                            <input type="text" class="form-control" value="admin" readonly>
                                            <input type="text" class="form-control" value="admin123" readonly>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Cashier Account</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-user-tie"></i>
                                            </span>
                                            <input type="text" class="form-control" value="cashier" readonly>
                                            <input type="text" class="form-control" value="cashier123" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6 class="mb-3" style="color: var(--bakery-brown);">
                                        <i class="fas fa-play-circle me-2"></i>Quick Start Guide
                                    </h6>
                                    <div class="list-group list-group-flush">
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-sign-in-alt me-3 text-success"></i>
                                            <div>
                                                <small class="text-muted">Step 1</small>
                                                <p class="mb-0 fw-bold">Login with demo credentials</p>
                                            </div>
                                        </div>
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-search me-3 text-info"></i>
                                            <div>
                                                <small class="text-muted">Step 2</small>
                                                <p class="mb-0 fw-bold">Explore all features</p>
                                            </div>
                                        </div>
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-cash-register me-3 text-warning"></i>
                                            <div>
                                                <small class="text-muted">Step 3</small>
                                                <p class="mb-0 fw-bold">Test POS functionality</p>
                                            </div>
                                        </div>
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="fas fa-chart-bar me-3 text-primary"></i>
                                            <div>
                                                <small class="text-muted">Step 4</small>
                                                <p class="mb-0 fw-bold">View reports & analytics</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Note:</strong> This is a demonstration system. All data is for testing purposes only and resets daily.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="admin/login.php" class="btn btn-bakery-primary">
                        <i class="fas fa-user-shield me-2"></i>Go to Admin
                    </a>
                    <a href="login.php" class="btn btn-success">
                        <i class="fas fa-user-tie me-2"></i>Go to Staff
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar-bakery');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Animate on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.animate-on-scroll');
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.classList.add('visible');
                }
            });
        }
        
        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);

        // Counter animation
        function animateCounter() {
            const counters = document.querySelectorAll('.counter-number');
            counters.forEach(counter => {
                const target = parseInt(counter.getAttribute('data-count'));
                const duration = 2000;
                const step = target / (duration / 16);
                let current = 0;
                
                const updateCounter = () => {
                    current += step;
                    if (current < target) {
                        counter.textContent = Math.floor(current);
                        requestAnimationFrame(updateCounter);
                    } else {
                        counter.textContent = target;
                    }
                };
                
                updateCounter();
            });
        }

        // Start counter when visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        document.querySelectorAll('.stats-counter').forEach(counter => {
            observer.observe(counter);
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Auto-open demo modal if URL has demo parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('demo')) {
                const demoModal = new bootstrap.Modal(document.getElementById('demoModal'));
                demoModal.show();
            }
            
            // Initial animation
            animateOnScroll();
        });
    </script>
</body>
</html> <h6><i class="fas fa-user me-2"></i>Demo Credentials</h6>
                                    <hr>
                                    <p><strong>Admin:</strong> admin / admin123</p>
                                    <p><strong>Cashier:</strong> cashier / cashier123</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6><i class="fas fa-play-circle me-2"></i>Quick Start</h6>
                                    <hr>
                                    <p>1. Login with demo credentials</p>
                                    <p>2. Explore different features</p>
                                    <p>3. Test POS functionality</p>
                                    <p>4. View reports and analytics</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This is a demonstration system. All data is for testing purposes only.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="admin/login.php" class="btn btn-primary">Go to Admin Login</a>
                    <a href="login.php" class="btn btn-success">Go to Staff Login</a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-open demo modal on page load (optional)
        // document.addEventListener('DOMContentLoaded', function() {
        //     const urlParams = new URLSearchParams(window.location.search);
        //     if (urlParams.has('demo')) {
        //         const demoModal = new bootstrap.Modal(document.getElementById('demoModal'));
        //         demoModal.show();
        //     }
        // });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>