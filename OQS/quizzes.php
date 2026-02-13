<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once 'includes/header.php';
require_once 'includes/init.php';

$page_title = "Quizzes";
$auth->requireLogin();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-question-circle me-2"></i>Available Quizzes</h2>
    <?php if ($auth->isAdmin()): ?>
        <a href="admin/quizzes.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Create New Quiz
        </a>
    <?php endif; ?>
</div>

<div class="row">
    <?php
    $quizzes = $quizFunctions->getAllQuizzes();
    
    if (empty($quizzes)):
    ?>
    <div class="col-12">
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-question-circle fa-4x text-muted mb-3"></i>
                <h4>No Quizzes Available</h4>
                <p class="text-muted">Check back later for new quizzes.</p>
                <?php if ($auth->isAdmin()): ?>
                    <a href="admin/quizzes.php?action=create" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Create First Quiz
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php else: 
        foreach ($quizzes as $quiz):
    ?>
    <div class="col-md-6 col-lg-4 mb-4">
        <div class="card h-100 quiz-card">
            <div class="card-body">
                <h5 class="card-title"><?php echo sanitize($quiz['title']); ?></h5>
                <p class="card-text text-muted"><?php echo sanitize($quiz['description'] ?? 'No description available'); ?></p>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">
                            <i class="fas fa-question-circle me-1"></i>
                            <?php echo $quiz['question_count']; ?> Questions
                        </small>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-muted">
                            <i class="fas fa-clock me-1"></i>
                            <?php echo $quiz['time_limit']; ?> mins
                        </small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-6">
                        <small class="text-muted">
                            <i class="fas fa-trophy me-1"></i>
                            <?php echo $quiz['total_marks']; ?> Points
                        </small>
                    </div>
                    <div class="col-6 text-end">
                        <small class="text-muted">
                            <i class="fas fa-users me-1"></i>
                            <?php echo $quiz['attempts']; ?> Attempts
                        </small>
                    </div>
                </div>
                
                <?php
                // Check if user has already taken this quiz
                $stmt = $pdo->prepare("SELECT id, score FROM results WHERE user_id = ? AND quiz_id = ? ORDER BY submitted_at DESC LIMIT 1");
                $stmt->execute([$_SESSION['user_id'], $quiz['id']]);
                $previousResult = $stmt->fetch();
                ?>
                
                <?php if ($previousResult): ?>
                    <div class="alert alert-info py-2 mb-3">
                        <small>
                            <i class="fas fa-history me-1"></i>
                            Your best score: <strong><?php echo $previousResult['score']; ?></strong> points
                        </small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-footer bg-transparent">
                <div class="d-flex justify-content-between">
                    <a href="attempt.php?id=<?php echo $quiz['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-play me-1"></i>
                        <?php echo $previousResult ? 'Retake Quiz' : 'Start Quiz'; ?>
                    </a>
                    
                    <?php if ($auth->isAdmin()): ?>
                        <div class="btn-group">
                            <a href="admin/manage_quiz.php?id=<?php echo $quiz['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="admin/questions.php?quiz_id=<?php echo $quiz['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-question"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>