<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// Check if auth class exists and create instance
$auth = null;
if (class_exists('Auth')) {
    try {
        require_once '../includes/db.php';
        $auth = new Auth($pdo);
    } catch (Exception $e) {
        // Silent fail - auth will be null
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Quiz System - <?php echo $page_title ?? 'Welcome'; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        :root {
            --primary-color: #4a6fa5;
            --secondary-color: #166088;
            --accent-color: #4fc3a1;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
        }
        
        .nav-link {
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .nav-link:hover {
            color: var(--accent-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        
        .card {
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .quiz-card {
            border-left: 4px solid var(--accent-color);
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 80px 0;
            margin-bottom: 50px;
        }
        
        .feature-icon {
            font-size: 2.5rem;
            color: var(--accent-color);
            margin-bottom: 15px;
        }
        
        .footer {
            background-color: var(--dark-color);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .table th {
            background-color: var(--light-color);
        }
        
        .badge-admin {
            background-color: var(--accent-color);
        }
        
        .progress-bar {
            background-color: var(--accent-color);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-brain me-2"></i>QuizMaster
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                     <li class="nav-item">
                                <a class="nav-link" href="index.php"><i class="fas fa-cog me-1"></i>Admin Panel</a>
                            </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-1"></i>Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="quizzes.php"><i class="fas fa-question-circle me-1"></i>Quizzes</a>
                        </li>
                             <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php"><i class="fas fa-home me-1"></i>Home</a>
                    </li>

                </ul>
                
                <ul class="navbar-nav">
                    <?php if ($auth && $auth->isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo $_SESSION['user_name'] ?? 'User'; ?>
                                <?php if ($auth->isAdmin()): ?>
                                    <span class="badge bg-danger ms-1">Admin</span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                <?php if ($auth->isAdmin()): ?>
                                <li><a class="dropdown-item" href="index.php"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    
                        
                </ul>
            </div>
        </div>
        <?php endif; ?>
    </nav>
    
    <main class="container py-4">