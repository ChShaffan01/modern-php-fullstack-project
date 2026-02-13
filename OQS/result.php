<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once 'includes/header.php';
require_once 'includes/init.php';

$page_title = "Quiz Result";
$auth->requireLogin();

if (!isset($_GET['id'])) {
    header("Location: results.php");
    exit();
}

$result_id = (int)$_GET['id'];

// Get result details
$stmt = $pdo->prepare("
    SELECT r.*, q.title, q.total_marks, q.description, u.name as user_name
    FROM results r 
    JOIN quizzes q ON r.quiz_id = q.id 
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ? AND (r.user_id = ? OR ? = 'admin')
");
$stmt->execute([$result_id, $_SESSION['user_id'], $_SESSION['user_role']]);
$result = $stmt->fetch();

if (!$result) {
    header("Location: results.php");
    exit();
}

// Get user answers with correct answers
$stmt = $pdo->prepare("
    SELECT ua.*, q.question_text, q.marks, 
           o1.option_text as selected_option_text,
           o2.option_text as correct_option_text
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    LEFT JOIN options o1 ON ua.option_id = o1.id
    LEFT JOIN options o2 ON o2.question_id = q.id AND o2.is_correct = 1
    WHERE ua.result_id = ?
");
$stmt->execute([$result_id]);
$user_answers = $stmt->fetchAll();

// Calculate percentage
$percentage = ($result['score'] / $result['total_marks']) * 100;
$percentage_formatted = round($percentage, 1);
?>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-<?php echo ($percentage >= 70) ? 'success' : (($percentage >= 50) ? 'warning' : 'danger'); ?> text-white">
                <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Quiz Results</h4>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="display-1 fw-bold text-<?php echo ($percentage >= 70) ? 'success' : (($percentage >= 50) ? 'warning' : 'danger'); ?>">
                        <?php echo $percentage_formatted; ?>%
                    </div>
                    <h3><?php echo sanitize($result['title']); ?></h3>
                    <p class="text-muted">Completed on <?php echo date('F j, Y \a\t g:i A', strtotime($result['submitted_at'])); ?></p>
                </div>
                
                <div class="row text-center mb-4">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Score</h6>
                                <h3 class="text-primary"><?php echo $result['score']; ?>/<?php echo $result['total_marks']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Correct Answers</h6>
                                <h3 class="text-success"><?php echo $result['correct_answers']; ?>/<?php echo $result['total_questions']; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="card-title">Accuracy</h6>
                                <h3 class="text-info">
                                    <?php echo round(($result['correct_answers'] / $result['total_questions']) * 100, 1); ?>%
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h5 class="mb-3">Question Review:</h5>
                <div class="accordion" id="reviewAccordion">
                    <?php foreach ($user_answers as $index => $answer): ?>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?php echo ($index > 0) ? 'collapsed' : ''; ?>" 
                                    type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#question<?php echo $index; ?>">
                                <span class="me-2">
                                    <i class="fas fa-<?php echo $answer['is_correct'] ? 'check text-success' : 'times text-danger'; ?>"></i>
                                </span>
                                Question <?php echo $index + 1; ?> 
                                <span class="badge bg-<?php echo $answer['is_correct'] ? 'success' : 'danger'; ?> ms-2">
                                    <?php echo $answer['marks']; ?> pts
                                </span>
                            </button>
                        </h2>
                        <div id="question<?php echo $index; ?>" 
                             class="accordion-collapse collapse <?php echo ($index === 0) ? 'show' : ''; ?>" 
                             data-bs-parent="#reviewAccordion">
                            <div class="accordion-body">
                                <p><strong>Question:</strong> <?php echo sanitize($answer['question_text']); ?></p>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card <?php echo $answer['is_correct'] ? 'border-success' : 'border-danger'; ?>">
                                            <div class="card-header">
                                                <strong>Your Answer:</strong>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-0">
                                                    <?php 
                                                    echo $answer['selected_option_text'] 
                                                        ? sanitize($answer['selected_option_text']) 
                                                        : '<span class="text-muted">Not answered</span>';
                                                    ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card border-success">
                                            <div class="card-header">
                                                <strong>Correct Answer:</strong>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-0"><?php echo sanitize($answer['correct_option_text']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-4">
                    <a href="results.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to All Results
                    </a>
                    <a href="attempt.php?id=<?php echo $result['quiz_id']; ?>" class="btn btn-primary ms-2">
                        <i class="fas fa-redo me-2"></i>Retake Quiz
                    </a>
                    <?php if ($auth->isAdmin()): ?>
                        <button class="btn btn-info ms-2" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Result
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-award me-2"></i>Performance Summary</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="mb-3">
                        <?php if ($percentage >= 90): ?>
                            <i class="fas fa-trophy fa-4x text-warning"></i>
                            <h5 class="mt-2">Excellent!</h5>
                        <?php elseif ($percentage >= 70): ?>
                            <i class="fas fa-medal fa-4x text-secondary"></i>
                            <h5 class="mt-2">Very Good!</h5>
                        <?php elseif ($percentage >= 50): ?>
                            <i class="fas fa-star fa-4x text-success"></i>
                            <h5 class="mt-2">Good Job!</h5>
                        <?php else: ?>
                            <i class="fas fa-redo fa-4x text-info"></i>
                            <h5 class="mt-2">Keep Practicing!</h5>
                        <?php endif; ?>
                    </div>
                    
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-<?php echo ($percentage >= 70) ? 'success' : (($percentage >= 50) ? 'warning' : 'danger'); ?>" 
                             role="progressbar" style="width: <?php echo $percentage; ?>%">
                            <?php echo $percentage_formatted; ?>%
                        </div>
                    </div>
                </div>
                
                <hr>
                
                <h6>Quiz Details:</h6>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item d-flex justify-content-between">
                        <span>User:</span>
                        <strong><?php echo sanitize($result['user_name']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Quiz:</span>
                        <strong><?php echo sanitize($result['title']); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Date Taken:</span>
                        <strong><?php echo date('M d, Y', strtotime($result['submitted_at'])); ?></strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between">
                        <span>Time Taken:</span>
                        <strong><?php echo date('g:i A', strtotime($result['submitted_at'])); ?></strong>
                    </li>
                </ul>
                
                <hr>
                
                <h6>Recommendations:</h6>
                <div class="alert alert-info">
                    <?php if ($percentage >= 90): ?>
                        <p>Outstanding performance! You have mastered this topic.</p>
                    <?php elseif ($percentage >= 70): ?>
                        <p>Good work! Consider reviewing the questions you missed.</p>
                    <?php elseif ($percentage >= 50): ?>
                        <p>You passed! Focus on the areas where you struggled.</p>
                    <?php else: ?>
                        <p>Review the material and try again. Focus on understanding the concepts.</p>
                    <?php endif; ?>
                </div>
                
                <div class="d-grid">
                    <a href="quizzes.php" class="btn btn-outline-primary">
                        <i class="fas fa-list me-2"></i>Browse More Quizzes
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .btn, .footer, .card-header button {
        display: none !important;
    }
    
    .accordion-button {
        color: #000 !important;
        background-color: #fff !important;
        border: 1px solid #ddd !important;
    }
    
    .accordion-button::after {
        display: none !important;
    }
    
    .accordion-collapse {
        display: block !important;
        height: auto !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>