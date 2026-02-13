<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once 'includes/header.php';
require_once 'includes/init.php';

$page_title = "Dashboard";

$auth->requireLogin();
?>

<div class="row">
    <!-- User Info Card -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-primary d-inline-flex p-3">
                        <i class="fas fa-user fa-2x text-white"></i>
                    </div>
                </div>
                <h4><?php echo $_SESSION['user_name']; ?></h4>
                <p class="text-muted"><?php echo $_SESSION['user_email']; ?></p>
                <span class="badge bg-<?php echo ($_SESSION['user_role'] == 'admin') ? 'danger' : 'primary'; ?>">
                    <?php echo ucfirst($_SESSION['user_role']); ?>
                </span>
                <div class="mt-3">
                    <a href="profile.php" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-edit me-1"></i>Edit Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="col-md-8">
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-white">Quizzes Taken</h6>
                                <h3 class="mb-0 text-white">
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM results WHERE user_id = ?");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    echo $stmt->fetch()['count'];
                                    ?>
                                </h3>
                            </div>
                            <div>
                                <i class="fas fa-clipboard-list fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-subtitle mb-2 text-white">Average Score</h6>
                                <h3 class="mb-0 text-white">
                                    <?php
                                    $stmt = $pdo->prepare("
                                        SELECT AVG(r.score / q.total_marks * 100) as avg_score 
                                        FROM results r 
                                        JOIN quizzes q ON r.quiz_id = q.id 
                                        WHERE r.user_id = ?
                                    ");
                                    $stmt->execute([$_SESSION['user_id']]);
                                    $avg = $stmt->fetch()['avg_score'];
                                    echo $avg ? round($avg, 1) . '%' : 'N/A';
                                    ?>
                                </h3>
                            </div>
                            <div>
                                <i class="fas fa-chart-line fa-2x opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Activity</h5>
            </div>
            <div class="card-body">
                <?php
                $stmt = $pdo->prepare("
                    SELECT r.*, q.title 
                    FROM results r 
                    JOIN quizzes q ON r.quiz_id = q.id 
                    WHERE r.user_id = ? 
                    ORDER BY r.submitted_at DESC 
                    LIMIT 5
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $recentResults = $stmt->fetchAll();
                
                if ($recentResults):
                ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Quiz</th>
                                <th>Score</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentResults as $result): ?>
                            <tr>
                                <td><?php echo sanitize($result['title']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($result['score'] >= 70) ? 'success' : (($result['score'] >= 50) ? 'warning' : 'danger'); ?>">
                                        <?php echo $result['score']; ?> points
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($result['submitted_at'])); ?></td>
                                <td>
                                    <a href="result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        View Details
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted text-center">No quiz attempts yet. <a href="quiz.php">Take a quiz!</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Available Quizzes -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i>Available Quizzes</h5>
                <a href="quiz.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php
                    $quizzes = $quizFunctions->getAllQuizzes();
                    $limitedQuizzes = array_slice($quizzes, 0, 3);
                    
                    foreach ($limitedQuizzes as $quiz):
                    ?>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title"><?php echo sanitize($quiz['title']); ?></h6>
                                <p class="card-text small text-muted">
                                    <?php echo substr(sanitize($quiz['description'] ?? 'No description'), 0, 80); ?>...
                                </p>
                                <div class="d-flex justify-content-between small">
                                    <span>
                                        <i class="fas fa-question me-1"></i>
                                        <?php echo $quiz['question_count']; ?> Qs
                                    </span>
                                    <span>
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo $quiz['time_limit']; ?> mins
                                    </span>
                                    <span>
                                        <i class="fas fa-trophy me-1"></i>
                                        <?php echo $quiz['total_marks']; ?> pts
                                    </span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="attempt.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-play me-1"></i>Start Quiz
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>