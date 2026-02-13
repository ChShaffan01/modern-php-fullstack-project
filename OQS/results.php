<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once 'includes/header.php';
require_once 'includes/init.php';

$page_title = "My Results";
$auth->requireLogin();

$user_results = $quizFunctions->getUserResults($_SESSION['user_id']);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-bar me-2"></i>My Quiz Results</h2>
    <a href="dashboard.php" class="btn btn-outline-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
    </a>
</div>

<?php if (empty($user_results)): ?>
<div class="card">
    <div class="card-body text-center py-5">
        <i class="fas fa-chart-bar fa-4x text-muted mb-3"></i>
        <h4>No Quiz Results Yet</h4>
        <p class="text-muted">You haven't taken any quizzes yet.</p>
        <a href="quiz.php" class="btn btn-primary">
            <i class="fas fa-play me-2"></i>Take Your First Quiz
        </a>
    </div>
</div>
<?php else: ?>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Percentage</th>
                        <th>Correct Answers</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_results as $result): 
                        $percentage = ($result['score'] / $result['total_marks']) * 100;
                    ?>
                    <tr>
                        <td>
                            <strong><?php echo sanitize($result['quiz_title']); ?></strong>
                        </td>
                        <td>
                            <span class="badge bg-<?php echo ($percentage >= 70) ? 'success' : (($percentage >= 50) ? 'warning' : 'danger'); ?>">
                                <?php echo $result['score']; ?>/<?php echo $result['total_marks']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 me-2" style="height: 6px;">
                                    <div class="progress-bar bg-<?php echo ($percentage >= 70) ? 'success' : (($percentage >= 50) ? 'warning' : 'danger'); ?>" 
                                         style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <span><?php echo round($percentage, 1); ?>%</span>
                            </div>
                        </td>
                        <td>
                            <?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($result['submitted_at'])); ?>
                        </td>
                        <td>
                            <a href="result.php?id=<?php echo $result['id']; ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye me-1"></i>View
                            </a>
                            <a href="attempt.php?id=<?php echo $result['quiz_id']; ?>" class="btn btn-sm btn-outline-success">
                                <i class="fas fa-redo me-1"></i>Retake
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Statistics -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6>Average Score</h6>
                        <h3 class="text-primary">
                            <?php
                            $avg_score = array_sum(array_column($user_results, 'percentage')) / count($user_results);
                            echo round($avg_score, 1); ?>%
                        </h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6>Total Attempts</h6>
                        <h3 class="text-success"><?php echo count($user_results); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <h6>Best Score</h6>
                        <h3 class="text-warning">
                            <?php 
                            $best_score = max(array_column($user_results, 'percentage'));
                            echo round($best_score, 1); ?>%
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>