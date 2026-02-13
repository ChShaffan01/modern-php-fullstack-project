<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../includes/auth.php';
$auth->requireAdmin();


$page_title = "Admin Dashboard";
require_once 'header.php';

// Get statistics for dashboard
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
$total_quizzes = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM questions");
$total_questions = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM results");
$total_results = $stmt->fetch()['total'];

// Get recent users
$stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// Get recent quizzes
$stmt = $pdo->query("
    SELECT q.*, u.name as creator_name 
    FROM quizzes q 
    LEFT JOIN users u ON q.created_by = u.id 
    ORDER BY q.created_at DESC LIMIT 5
");
$recent_quizzes = $stmt->fetchAll();

// Get recent results
$stmt = $pdo->query("
    SELECT r.*, u.name as user_name, q.title as quiz_title 
    FROM results r 
    JOIN users u ON r.user_id = u.id 
    JOIN quizzes q ON r.quiz_id = q.id 
    ORDER BY r.submitted_at DESC LIMIT 5
");
$recent_results = $stmt->fetchAll();
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
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="results.php">
                            <i class="fas fa-chart-bar me-2"></i>View Results
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard.php">
                            <i class="fas fa-home me-2"></i>Back to Site
                        </a>
                    </li>
                </ul>
                
                <hr class="my-3">
                
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Quick Stats</h6>
                        <div class="small">
                            <div class="d-flex justify-content-between">
                                <span>Quizzes:</span>
                                <span class="badge bg-primary"><?php echo $total_quizzes; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Questions:</span>
                                <span class="badge bg-success"><?php echo $total_questions; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Users:</span>
                                <span class="badge bg-info"><?php echo $total_users; ?></span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Attempts:</span>
                                <span class="badge bg-warning"><?php echo $total_results; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="quizzes.php?action=create" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-plus me-1"></i>New Quiz
                        </a>
                        <a href="questions.php" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-question me-1"></i>Add Questions
                        </a>
                    </div>
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

            <!-- Recent Activity Row -->
            <div class="row">
                <!-- Recent Users -->
                <div class="col-xl-4 col-lg-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 fw-bold text-primary"><i class="fas fa-users me-1"></i>Recent Users</h6>
                            <a href="users.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Role</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?php echo sanitize($user['name']); ?></div>
                                                        <small class="text-muted"><?php echo sanitize($user['email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $user['role'] == 'admin' ? 'danger' : 'primary'; ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M d', strtotime($user['created_at'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Quizzes -->
                <div class="col-xl-4 col-lg-6 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 fw-bold text-success"><i class="fas fa-question-circle me-1"></i>Recent Quizzes</h6>
                            <a href="quizzes.php" class="btn btn-sm btn-outline-success">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Creator</th>
                                            <th>Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_quizzes as $quiz): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?php echo sanitize($quiz['title']); ?></div>
                                                <small class="text-muted"><?php echo $quiz['time_limit']; ?> mins</small>
                                            </td>
                                            <td>
                                                <small><?php echo sanitize($quiz['creator_name']); ?></small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo date('M d', strtotime($quiz['created_at'])); ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Results -->
                <div class="col-xl-4 col-lg-12 mb-4">
                    <div class="card shadow h-100">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 fw-bold text-warning"><i class="fas fa-chart-bar me-1"></i>Recent Attempts</h6>
                            <a href="results.php" class="btn btn-sm btn-outline-warning">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Quiz</th>
                                            <th>Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_results as $result): 
                                            $percentage = ($result['score'] / 100) * 100;
                                        ?>
                                        <tr>
                                            <td>
                                                <small><?php echo sanitize($result['user_name']); ?></small>
                                            </td>
                                            <td>
                                                <small><?php echo substr(sanitize($result['quiz_title']), 0, 20); ?>...</small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $percentage >= 70 ? 'success' : ($percentage >= 50 ? 'warning' : 'danger'); ?>">
                                                    <?php echo $result['score']; ?>%
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row">
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
                                    <a href="questions.php" class="card action-card text-center text-decoration-none">
                                        <div class="card-body">
                                            <div class="action-icon bg-success text-white rounded-circle mb-3">
                                                <i class="fas fa-question fa-2x"></i>
                                            </div>
                                            <h6 class="card-title mb-0">Add Questions</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="users.php" class="card action-card text-center text-decoration-none">
                                        <div class="card-body">
                                            <div class="action-icon bg-info text-white rounded-circle mb-3">
                                                <i class="fas fa-users fa-2x"></i>
                                            </div>
                                            <h6 class="card-title mb-0">Manage Users</h6>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3 col-6">
                                    <a href="results.php" class="card action-card text-center text-decoration-none">
                                        <div class="card-body">
                                            <div class="action-icon bg-warning text-white rounded-circle mb-3">
                                                <i class="fas fa-chart-bar fa-2x"></i>
                                            </div>
                                            <h6 class="card-title mb-0">View Results</h6>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-dark"><i class="fas fa-info-circle me-1"></i>System Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-light rounded p-2 me-3">
                                            <i class="fas fa-server text-primary"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">PHP Version</small>
                                            <strong><?php echo phpversion(); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-light rounded p-2 me-3">
                                            <i class="fas fa-database text-success"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Database</small>
                                            <strong>MySQL</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-light rounded p-2 me-3">
                                            <i class="fas fa-user-shield text-danger"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Logged in as</small>
                                            <strong><?php echo $_SESSION['user_name']; ?></strong>
                                        </div>
                                    </div>
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

@media (max-width: 768px) {
    .sidebar {
        min-height: auto;
        border-right: none;
        border-bottom: 1px solid #dee2e6;
        margin-bottom: 1rem;
    }
    
    .col-md-9 {
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
}
</style>

<?php require_once 'footer.php'; ?>