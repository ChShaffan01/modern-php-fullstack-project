<?php
// Start session and output buffering at the very beginning
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/db.php';
require_once '../includes/auth.php';

// Create auth instance
$auth = new Auth($pdo);

// Check admin access
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    // Use safe redirect
    if (headers_sent()) {
        echo '<script>window.location.href = "../dashboard.php";</script>';
        exit();
    } else {
        header("Location: ../dashboard.php");
        exit();
    }
}

$page_title = "Admin Dashboard";

// Now include the header
require_once 'header.php';

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
$total_quizzes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
$total_questions = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM results");
$total_results = $stmt->fetch()['total'];
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quizzes.php">
                            <i class="fas fa-question-circle me-2"></i>Manage Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php">
                            <i class="fas fa-question me-2"></i>Manage Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-home me-2"></i>Back to Site
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-cog me-2"></i>Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="quizzes.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create Quiz
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start border-primary border-5 shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Users</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_users; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start border-success border-5 shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Total Quizzes</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_quizzes; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start border-info border-5 shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-info text-uppercase mb-1">Total Questions</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_questions; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-question fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-start border-warning border-5 shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs fw-bold text-warning text-uppercase mb-1">Quiz Attempts</div>
                                    <div class="h5 mb-0 fw-bold text-gray-800"><?php echo $total_results; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-bar fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-bolt me-1"></i>Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-3 col-6">
                                    <a href="quizzes.php?action=create" class="card action-card text-center text-decoration-none">
                                        <div class="card-body">
                                            <div class="action-icon bg-primary text-white rounded-circle mb-3">
                                                <i class="fas fa-plus fa-2x"></i>
                                            </div>
                                            <h6 class="card-title mb-0">Create Quiz</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="quizzes.php" class="card action-card text-center text-decoration-none">
                                        <div class="card-body">
                                            <div class="action-icon bg-success text-white rounded-circle mb-3">
                                                <i class="fas fa-question-circle fa-2x"></i>
                                            </div>
                                            <h6 class="card-title mb-0">Manage Quizzes</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="questions.php" class="card action-card text-center text-decoration-none">
                                        <div class="card-body">
                                            <div class="action-icon bg-warning text-white rounded-circle mb-3">
                                                <i class="fas fa-question fa-2x"></i>
                                            </div>
                                            <h6 class="card-title mb-0">Manage Questions</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="../dashboard.php" class="card action-card text-center text-decoration-none">
                                        <div class="card-body">
                                            <div class="action-icon bg-info text-white rounded-circle mb-3">
                                                <i class="fas fa-home fa-2x"></i>
                                            </div>
                                            <h6 class="card-title mb-0">Back to Site</h6>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sidebar {
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    min-height: calc(100vh - 56px);
    padding-top: 20px;
}

.nav-link {
    color: #495057;
    padding: 0.75rem 1rem;
    border-radius: 0.375rem;
    margin-bottom: 0.25rem;
    transition: all 0.3s;
}

.nav-link:hover {
    background-color: #e9ecef;
    color: #4a6fa5;
}

.nav-link.active {
    background-color: #4a6fa5;
    color: white !important;
}

.action-card {
    border: 1px solid #dee2e6;
    transition: all 0.3s;
    height: 100%;
}

.action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #4a6fa5;
}

.action-icon {
    width: 70px;
    height: 70px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
}

.border-start {
    border-left-width: 0.25rem !important;
}
</style>

<?php 
// Include footer
require_once 'footer.php'; 
?>