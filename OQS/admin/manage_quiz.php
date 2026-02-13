<?php
require_once '../includes/auth.php';
$auth->requireAdmin();

$page_title = "Manage Quiz";
require_once 'header.php';



$action = $_GET['action'] ?? 'edit';
$quiz_id = $_GET['id'] ?? 0;
$quiz = [];
$questions = [];

// Fetch quiz data if editing
if ($quiz_id) {
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();
    
    if (!$quiz) {
        header("Location: quizzes.php");
        exit();
    }
    
    // Fetch quiz questions
    $stmt = $pdo->prepare("
        SELECT q.*, 
               (SELECT COUNT(*) FROM options WHERE question_id = q.id) as option_count
        FROM questions q 
        WHERE q.quiz_id = ? 
        ORDER BY q.question_order ASC
    ");
    $stmt->execute([$quiz_id]);
    $questions = $stmt->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $total_marks = (int)$_POST['total_marks'];
    $time_limit = (int)$_POST['time_limit'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    if ($action === 'create') {
        // Create new quiz
        $stmt = $pdo->prepare("
            INSERT INTO quizzes (title, description, total_marks, time_limit, is_active, created_by) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $total_marks, $time_limit, $is_active, $_SESSION['user_id']]);
        $quiz_id = $pdo->lastInsertId();
        
        $_SESSION['success'] = "Quiz created successfully!";
        header("Location: manage_quiz.php?action=edit&id=$quiz_id");
        exit();
        
    } else {
        // Update existing quiz
        $stmt = $pdo->prepare("
            UPDATE quizzes SET 
                title = ?, 
                description = ?, 
                total_marks = ?, 
                time_limit = ?, 
                is_active = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $total_marks, $time_limit, $is_active, $quiz_id]);
        
        $_SESSION['success'] = "Quiz updated successfully!";
        header("Location: manage_quiz.php?action=edit&id=$quiz_id");
        exit();
    }
}

// Handle quiz deletion
if (isset($_GET['delete'])) {
    $confirm = $_GET['confirm'] ?? false;
    
    if ($confirm === 'true') {
        $stmt = $pdo->prepare("DELETE FROM quizzes WHERE id = ?");
        $stmt->execute([$quiz_id]);
        
        $_SESSION['success'] = "Quiz deleted successfully!";
        header("Location: quizzes.php");
        exit();
    }
}

// Calculate total questions marks
$total_questions_marks = 0;
foreach ($questions as $question) {
    $total_questions_marks += $question['marks'];
}
$marks_diff = $quiz['total_marks'] - $total_questions_marks;
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="position-sticky pt-3">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="quizzes.php">
                            <i class="fas fa-arrow-left me-2"></i>Back to Quizzes
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_quiz.php?action=edit&id=<?php echo $quiz_id; ?>">
                            <i class="fas fa-edit me-2"></i>Edit Quiz
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_question.php?action=add&quiz_id=<?php echo $quiz_id; ?>">
                            <i class="fas fa-plus me-2"></i>Add Question
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="questions.php?quiz_id=<?php echo $quiz_id; ?>">
                            <i class="fas fa-question me-2"></i>View Questions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="fas fa-trash me-2"></i>Delete Quiz
                        </a>
                    </li>
                </ul>
                
                <hr class="my-3">
                
                <div class="card bg-light">
                    <div class="card-body">
                        <h6 class="card-title">Quiz Stats</h6>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Questions:</span>
                                <span class="badge bg-primary"><?php echo count($questions); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Marks:</span>
                                <span class="badge bg-success"><?php echo $quiz['total_marks'] ?? 0; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Time Limit:</span>
                                <span class="badge bg-info"><?php echo $quiz['time_limit'] ?? 0; ?> mins</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Status:</span>
                                <span class="badge bg-<?php echo ($quiz['is_active'] ?? 0) ? 'success' : 'warning'; ?>">
                                    <?php echo ($quiz['is_active'] ?? 0) ? 'Active' : 'Inactive'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($quiz_id): ?>
                <div class="card bg-light mt-3">
                    <div class="card-body">
                        <h6 class="card-title">Marks Summary</h6>
                        <div class="small">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Quiz Total:</span>
                                <span><?php echo $quiz['total_marks']; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Questions Total:</span>
                                <span><?php echo $total_questions_marks; ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Difference:</span>
                                <span class="fw-bold text-<?php echo $marks_diff == 0 ? 'success' : ($marks_diff > 0 ? 'warning' : 'danger'); ?>">
                                    <?php echo $marks_diff; ?>
                                </span>
                            </div>
                            <?php if ($marks_diff != 0): ?>
                            <div class="alert alert-<?php echo $marks_diff > 0 ? 'warning' : 'danger'; ?> py-2 mb-0 mt-2">
                                <small>
                                    <i class="fas fa-exclamation-triangle me-1"></i>
                                    <?php echo $marks_diff > 0 ? 'Add more questions' : 'Reduce question marks'; ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">
                    <i class="fas fa-question-circle me-2"></i>
                    <?php echo $action === 'create' ? 'Create New Quiz' : 'Edit Quiz'; ?>
                </h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if ($quiz_id): ?>
                    <div class="btn-group me-2">
                        <a href="../attempt.php?id=<?php echo $quiz_id; ?>" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fas fa-eye me-1"></i>Preview
                        </a>
                        <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-sm btn-outline-success">
                            <i class="fas fa-question me-1"></i>Questions (<?php echo count($questions); ?>)
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Quiz Form -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-primary">Quiz Details</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="title" class="form-label">Quiz Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" 
                                               value="<?php echo $quiz['title'] ?? ''; ?>" required>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo $quiz['description'] ?? ''; ?></textarea>
                                        <small class="text-muted">Describe what this quiz is about</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="total_marks" class="form-label">Total Marks *</label>
                                        <input type="number" class="form-control" id="total_marks" name="total_marks" 
                                               value="<?php echo $quiz['total_marks'] ?? 100; ?>" min="1" required>
                                        <small class="text-muted">Total marks for this quiz</small>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="time_limit" class="form-label">Time Limit (minutes) *</label>
                                        <input type="number" class="form-control" id="time_limit" name="time_limit" 
                                               value="<?php echo $quiz['time_limit'] ?? 30; ?>" min="1" required>
                                        <small class="text-muted">Time limit in minutes</small>
                                    </div>
                                    
                                    <div class="col-md-12 mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                                   value="1" <?php echo ($quiz['is_active'] ?? 1) ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="is_active">
                                                Active (Visible to users)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="quizzes.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i>Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>
                                        <?php echo $action === 'create' ? 'Create Quiz' : 'Update Quiz'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Questions List -->
                    <?php if ($quiz_id && !empty($questions)): ?>
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 fw-bold text-success">Questions (<?php echo count($questions); ?>)</h6>
                            <a href="manage_question.php?action=add&quiz_id=<?php echo $quiz_id; ?>" class="btn btn-sm btn-success">
                                <i class="fas fa-plus me-1"></i>Add Question
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th width="50">#</th>
                                            <th>Question</th>
                                            <th width="80">Marks</th>
                                            <th width="100">Options</th>
                                            <th width="120">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($questions as $index => $question): 
                                            // Get options for this question
                                            $stmt = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
                                            $stmt->execute([$question['id']]);
                                            $options = $stmt->fetchAll();
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary"><?php echo $index + 1; ?></span>
                                            </td>
                                            <td>
                                                <div class="fw-bold"><?php echo substr(sanitize($question['question_text']), 0, 60); ?>...</div>
                                                <small class="text-muted">
                                                    <?php echo $question['option_count']; ?> options
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $question['marks']; ?></span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php foreach ($options as $opt): ?>
                                                        <?php if ($opt['is_correct']): ?>
                                                            <span class="badge bg-success me-1">✓</span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="manage_question.php?action=edit&id=<?php echo $question['id']; ?>" 
                                                       class="btn btn-outline-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?action=edit&id=<?php echo $quiz_id; ?>&delete_question=<?php echo $question['id']; ?>" 
                                                       class="btn btn-outline-danger" 
                                                       onclick="return confirm('Delete this question?')" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="#" class="btn btn-outline-info" title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="col-lg-4">
                    <!-- Quick Actions -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-dark">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="manage_question.php?action=add&quiz_id=<?php echo $quiz_id; ?>" class="btn btn-success">
                                    <i class="fas fa-plus me-2"></i>Add Question
                                </a>
                                <a href="questions.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-list me-2"></i>Manage Questions
                                </a>
                                <a href="../attempt.php?id=<?php echo $quiz_id; ?>" class="btn btn-info" target="_blank">
                                    <i class="fas fa-eye me-2"></i>Preview Quiz
                                </a>
                                <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#duplicateModal">
                                    <i class="fas fa-copy me-2"></i>Duplicate Quiz
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quiz Preview -->
                    <?php if ($quiz_id): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-info">Quiz Preview</h6>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="display-6 fw-bold text-primary"><?php echo sanitize($quiz['title']); ?></div>
                                <p class="text-muted"><?php echo sanitize($quiz['description']); ?></p>
                            </div>
                            
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Questions</small>
                                            <div class="h5 mb-0"><?php echo count($questions); ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Time Limit</small>
                                            <div class="h5 mb-0"><?php echo $quiz['time_limit']; ?> min</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Total Marks</small>
                                            <div class="h5 mb-0"><?php echo $quiz['total_marks']; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Status</small>
                                            <div class="h5 mb-0">
                                                <span class="badge bg-<?php echo $quiz['is_active'] ? 'success' : 'warning'; ?>">
                                                    <?php echo $quiz['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-3">
                                <a href="../attempt.php?id=<?php echo $quiz_id; ?>" class="btn btn-primary" target="_blank">
                                    <i class="fas fa-play me-2"></i>Take Quiz
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quiz Statistics -->
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 fw-bold text-warning">Statistics</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            // Get quiz statistics
                            $stmt = $pdo->prepare("
                                SELECT 
                                    COUNT(DISTINCT r.user_id) as unique_users,
                                    COUNT(r.id) as total_attempts,
                                    AVG(r.score) as avg_score,
                                    MAX(r.score) as best_score
                                FROM results r 
                                WHERE r.quiz_id = ?
                            ");
                            $stmt->execute([$quiz_id]);
                            $stats = $stmt->fetch();
                            ?>
                            
                            <div class="row text-center">
                                <div class="col-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Unique Users</small>
                                            <div class="h5 mb-0"><?php echo $stats['unique_users'] ?? 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6 mb-3">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Total Attempts</small>
                                            <div class="h5 mb-0"><?php echo $stats['total_attempts'] ?? 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Average Score</small>
                                            <div class="h5 mb-0"><?php echo $stats['avg_score'] ? round($stats['avg_score'], 1) : 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card bg-light">
                                        <div class="card-body py-2">
                                            <small class="text-muted d-block">Best Score</small>
                                            <div class="h5 mb-0"><?php echo $stats['best_score'] ?? 0; ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Delete Quiz</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-trash fa-4x text-danger mb-3"></i>
                    <h4>Are you sure?</h4>
                    <p>This action will delete the quiz and all associated questions, options, and results. This cannot be undone.</p>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Warning:</strong> This will delete:
                        <ul class="mb-0 mt-2">
                            <li>Quiz: <?php echo sanitize($quiz['title']); ?></li>
                            <li>Questions: <?php echo count($questions); ?></li>
                            <li>Results: <?php echo $stats['total_attempts'] ?? 0; ?> attempts</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="?action=edit&id=<?php echo $quiz_id; ?>&delete=true&confirm=true" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Delete Quiz
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Quiz Modal -->
<div class="modal fade" id="duplicateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-copy me-2"></i>Duplicate Quiz</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="duplicateForm" method="POST" action="duplicate_quiz.php">
                    <input type="hidden" name="source_quiz_id" value="<?php echo $quiz_id; ?>">
                    
                    <div class="mb-3">
                        <label for="new_title" class="form-label">New Quiz Title *</label>
                        <input type="text" class="form-control" id="new_title" name="new_title" 
                               value="Copy of <?php echo sanitize($quiz['title']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Copy Options</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="copy_questions" name="copy_questions" checked>
                            <label class="form-check-label" for="copy_questions">
                                Copy all questions and options
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="copy_settings" name="copy_settings" checked>
                            <label class="form-check-label" for="copy_settings">
                                Copy quiz settings (marks, time limit)
                            </label>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        This will create a new quiz with the same structure as the current one.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="duplicateForm" class="btn btn-primary">
                    <i class="fas fa-copy me-2"></i>Duplicate Quiz
                </button>
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

.nav-link.text-danger:hover {
    background-color: #f8d7da;
    color: #dc3545 !important;
}

.card {
    border: none;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: all 0.3s;
}

.card:hover {
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>

<?php require_once 'footer.php'; ?>